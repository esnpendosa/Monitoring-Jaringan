const { 
    makeWASocket, 
    useMultiFileAuthState, 
    DisconnectReason, 
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    Browsers,
    downloadMediaMessage
} = require("@whiskeysockets/baileys");
const pino    = require("pino");
const path    = require("path");
const fs      = require("fs");
const express = require('express');
const axios   = require('axios');
const https   = require('https');
const Tesseract = require('tesseract.js');

// Config SSL untuk support self-signed certificate (HTTP/HTTPS)
process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

const axiosInstance = axios.create({
    httpsAgent: new https.Agent({  
        rejectUnauthorized: false
    })
});

// ─── Global Error Handlers ─────────────────────────────────────────────────────
process.on('unhandledRejection', (reason) => console.error('[DEBUG] Unhandled Rejection:', reason));
process.on('uncaughtException',  (err)    => console.error('[DEBUG] Uncaught Exception:', err));

require('dotenv').config({ path: path.resolve(__dirname, '.env') });

const app  = express();
app.use(express.json({ limit: '50mb' }));

const PORT             = process.env.BOT_PORT   || 3000;
const BOT_SECRET       = process.env.BOT_SECRET || 'rozitech-bot-secret-2024';
const LARAVEL_URL      = process.env.APP_URL    || 'http://127.0.0.1:8000';
const ALLOWED_SESSIONS = (process.env.ALLOWED_SESSIONS || 'main').split(',').map(s => s.trim());
const SESSIONS_PATH = path.resolve(__dirname, 'sessions');

if (!fs.existsSync(SESSIONS_PATH)) fs.mkdirSync(SESSIONS_PATH, { recursive: true });

const sessions      = new Map(); // id -> sock
const sessionStates = new Map(); // id -> { id, status, user, qr, ... }
const sessionContacts = new Map(); // id -> [jid1, jid2, ...]
const processedMessages = new Set(); // Cache for message IDs to prevent loops

// ─── Middleware ────────────────────────────────────────────────────────────────
const requireSecret = (req, res, next) => {
    const secret = req.headers['x-bot-secret'] || req.body?.secret || req.query?.secret;
    if (secret !== BOT_SECRET) return res.status(403).json({ error: 'Unauthorized' });
    next();
};

// ─── Helpers ───────────────────────────────────────────────────────────────────
function deleteSessionDir(sessionDir) {
    try {
        if (fs.existsSync(sessionDir)) {
            fs.rmSync(sessionDir, { recursive: true, force: true });
            console.log(`[LOG] Folder sesi dihapus: ${path.basename(sessionDir)}`);
        }
    } catch (e) {
        console.error('[WARN] Gagal hapus sesi:', e.message);
    }
}

const sleep = (ms) => new Promise(r => setTimeout(r, ms));

function closeSock(cleanId) {
    if (sessions.has(cleanId)) {
        const old = sessions.get(cleanId);
        try { old.ev.removeAllListeners(); old.end?.(); } catch(_) {}
        sessions.delete(cleanId);
    }
}

// ─── Laravel HTTP Helper (with retry on DNS/connection errors) ────────────────
// Retries up to 3 times with exponential backoff when DNS resolution fails
// (EAI_AGAIN, ENOTFOUND) or the connection drops — keeps working through
// transient network hiccups without falling back to a different host.
const RETRYABLE_CODES = ['EAI_AGAIN', 'ENOTFOUND', 'ECONNRESET', 'ETIMEDOUT', 'ECONNREFUSED'];
const RETRY_DELAYS    = [2000, 5000, 10000]; // ms between attempts 1→2, 2→3, 3→fail

async function laravelPost(path, data, options = {}) {
    const defaultOpts = { headers: { 'X-Bot-Secret': BOT_SECRET }, timeout: 30000, ...options };
    const url         = `${LARAVEL_URL}${path}`;
    let lastErr;

    for (let attempt = 0; attempt <= RETRY_DELAYS.length; attempt++) {
        try {
            return await axiosInstance.post(url, data, defaultOpts);
        } catch (err) {
            lastErr = err;
            const isRetryable = RETRYABLE_CODES.includes(err.code) ||
                                 err.message.includes('getaddrinfo') ||
                                 err.message.includes('Connection Closed') ||
                                 err.message.includes('socket hang up');

            if (!isRetryable || attempt === RETRY_DELAYS.length) break;

            const delay = RETRY_DELAYS[attempt];
            console.warn(`[HTTP] ${err.code || err.message} on ${path} — retry ${attempt + 1}/${RETRY_DELAYS.length} in ${delay}ms`);
            await sleep(delay);
        }
    }

    throw lastErr;
}

// ─── Core: Start Session ───────────────────────────────────────────────────────
async function startSession(id, opts = {}) {
    const cleanId    = id.replace(/[^a-zA-Z0-9_]/g, '');
    const sessionDir = path.join(SESSIONS_PATH, cleanId);

    if (sessions.has(cleanId)) {
        console.log(`[LOG] Sesi ${cleanId} sudah aktif, digunakan kembali.`);
        return sessions.get(cleanId);
    }

    console.log(`[LOG] Memulai Sesi: ${cleanId}`);

    let { version, isLatest } = await fetchLatestBaileysVersion().catch(() => ({ version: [2, 3000, 1015901307], isLatest: false }));
    console.log(`[LOG] WA Web v${version.join('.')} (Latest: ${isLatest})`);

    const { state, saveCreds } = await useMultiFileAuthState(sessionDir);

    const sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: opts.printQR ?? false,
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, pino({ level: 'silent' }))
        },
        browser: Browsers.ubuntu('Chrome'), 
        connectTimeoutMs:     60000,
        defaultQueryTimeoutMs: 60000,
        keepAliveIntervalMs:  30000,
        retryRequestDelayMs:  250,
        maxMsgRetryCount:     5,
        shouldSyncHistoryMessage: () => false,
        syncFullHistory:  false,
        markOnlineOnConnect: true, 
        generateHighQualityLinkPreview: false,
    });

    sessions.set(cleanId, sock);
    sessionStates.set(cleanId, { id: cleanId, status: 'connecting' });

    // Load saved contacts from local file if exists to prevent empty list on PM2 reloads
    const contactsFile = path.join(SESSIONS_PATH, `${cleanId}_contacts.json`);
    if (fs.existsSync(contactsFile)) {
        try {
            const list = JSON.parse(fs.readFileSync(contactsFile, 'utf-8'));
            sessionContacts.set(cleanId, list);
            console.log(`[STORE] Berhasil memuat ${list.length} kontak tersimpan dari berkas untuk sesi ${cleanId}`);
        } catch (e) {
            console.error(`[STORE] Gagal memuat kontak dari berkas: ${e.message}`);
        }
    }

    const saveContactsToFile = (id, list) => {
        try {
            fs.writeFileSync(path.join(SESSIONS_PATH, `${id}_contacts.json`), JSON.stringify(list, null, 2));
        } catch (e) {
            console.error(`[STORE] Gagal menyimpan kontak ke berkas: ${e.message}`);
        }
    };

    sock.ev.on('creds.update', saveCreds);

    // Helper: Lapor status ke Laravel
    const notifyLaravel = async (status) => {
        try {
            await laravelPost('/whatsapp/status', {
                sessionId: cleanId,
                status: status,
                user: sock.user || null
            }, { timeout: 5000 });
        } catch (e) {
            console.error(`[STATUS] Gagal lapor status ke Laravel (${cleanId}): ${e.message}`);
        }
    };

    // ── Connection Update ──
    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            sessionStates.set(cleanId, { ...sessionStates.get(cleanId), qr, status: 'qr' });
            console.log(`[QR] Sesi ${cleanId} menampilkan QR...`);
            notifyLaravel('qr');
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const errMsg     = lastDisconnect?.error?.message || '';
            const fullError  = lastDisconnect?.error;
            console.error(`[ERR] Sesi ${cleanId} terputus — Code: ${statusCode}, Msg: ${errMsg}`);
            
            if (fullError && statusCode !== 408) {
                console.log(`[DEBUG] Full Error:`, JSON.stringify(fullError, null, 2));
            }
            
            sessions.delete(cleanId);
            notifyLaravel('closed');

            const fatal = 
                statusCode === DisconnectReason.loggedOut ||
                statusCode === 401 ||
                statusCode === 500 ||
                errMsg.includes('Invalid account signature') ||
                errMsg.includes('conflict');

            if (fatal) {
                console.log(`[LOG] Sesi ${cleanId} dihapus (fatal error)`);
                sessionStates.delete(cleanId);
                deleteSessionDir(sessionDir);
            } else {
                console.log(`[LOG] Reconnect sesi ${cleanId} dalam 5 detik...`);
                sessionStates.set(cleanId, { ...sessionStates.get(cleanId), status: 'reconnecting' });
                setTimeout(() => startSession(cleanId), 5000);
            }
        } else if (connection === 'open') {
            console.log(`[OK] ✅ Sesi ${cleanId} TERHUBUNG! User: ${sock.user?.id}`);
            sessionStates.set(cleanId, { 
                id: cleanId, status: 'open',
                user: sock.user,
                connectedAt: new Date().toISOString()
            });
            notifyLaravel('open');
        }
    });

    // ── Contacts & History Sync ──
    sock.ev.on('messaging-history.set', ({ contacts }) => {
        if (contacts) {
            const list = contacts.map(c => c.id).filter(id => id && id.endsWith('@s.whatsapp.net'));
            sessionContacts.set(cleanId, list);
            saveContactsToFile(cleanId, list);
            console.log(`[STORE] Berhasil menyimpan ${list.length} kontak dari whatsapp untuk sesi ${cleanId}`);
        }
    });

    sock.ev.on('contacts.upsert', (newContacts) => {
        const existing = sessionContacts.get(cleanId) || [];
        let updated = false;
        newContacts.forEach(c => {
            if (c.id && c.id.endsWith('@s.whatsapp.net') && !existing.includes(c.id)) {
                existing.push(c.id);
                updated = true;
            }
        });
        if (updated) {
            sessionContacts.set(cleanId, existing);
            saveContactsToFile(cleanId, existing);
        }
    });

    sock.ev.on('contacts.update', (updates) => {
        const existing = sessionContacts.get(cleanId) || [];
        let updated = false;
        updates.forEach(c => {
            if (c.id && c.id.endsWith('@s.whatsapp.net') && !existing.includes(c.id)) {
                existing.push(c.id);
                updated = true;
            }
        });
        if (updated) {
            sessionContacts.set(cleanId, existing);
            saveContactsToFile(cleanId, existing);
        }
    });

    // ── Incoming Messages → Forward to Laravel ──
    sock.ev.on('messages.upsert', async ({ messages: msgs, type }) => {
        if (type !== 'notify') return;

        for (const msg of msgs) {
            const msgId = msg.key.id;
            const fromMe = msg.key.fromMe;
            
            // IGNORE if: no message body, from self, or already processed
            if (!msg.message || fromMe || processedMessages.has(msgId)) {
                continue;
            }

            // IGNORE protocol messages (often the cause of 'message cannot be loaded' loops)
            const msgType = Object.keys(msg.message || {})[0];
            if (['protocolMessage', 'senderKeyDistributionMessage', 'reactionMessage', 'readReceiptMessage'].includes(msgType)) {
                continue;
            }

            // Mark as processed
            processedMessages.add(msgId);
            setTimeout(() => processedMessages.delete(msgId), 30000); // Cleanup after 30s

            const remoteJid = msg.key.remoteJid;
            const sender    = msg.key.participant || remoteJid;
            const pushName  = msg.pushName || '';

            console.log(`[MSG] Dari: ${pushName} (${sender}) | Tipe: ${msgType}`);

            let textContent = '';
            let mediaBase64 = null;
            let ocrText     = '';

            console.log(`[MSG] Dari: ${pushName} (${sender}) | Tipe: ${msgType}`);

            if (msgType === 'conversation') {
                textContent = msg.message.conversation;
            } else if (msgType === 'extendedTextMessage') {
                textContent = msg.message.extendedTextMessage?.text || '';
            } else if (msgType === 'buttonsResponseMessage') {
                textContent = msg.message.buttonsResponseMessage?.selectedButtonId || '';
            } else if (msgType === 'templateButtonReplyMessage') {
                textContent = msg.message.templateButtonReplyMessage?.selectedId || '';
            } else if (msgType === 'listResponseMessage') {
                textContent = msg.message.listResponseMessage?.singleSelectReply?.selectedRowId || '';
            } else if (msgType === 'imageMessage') {
                textContent = msg.message.imageMessage?.caption || '';
                try {
                    const buffer = await downloadMediaMessage(msg, 'buffer', {});
                    mediaBase64 = buffer.toString('base64');
                    
                    console.log(`[OCR] Memproses gambar dari ${pushName}...`);
                    const { data: { text } } = await Tesseract.recognize(buffer, 'ind+eng');
                    ocrText = text;
                    console.log(`[OCR] Selesai. Terdeteksi ${ocrText.length} karakter.`);
                } catch(e) {
                    console.error('[MEDIA/OCR] Gagal proses:', e.message);
                }
            } else if (msgType === 'locationMessage') {
                const loc = msg.message.locationMessage;
                textContent = `LOKASI_SHARE:${loc.degreesLatitude},${loc.degreesLongitude}`;
            } else if (msgType === 'liveLocationMessage') {
                const loc = msg.message.liveLocationMessage;
                textContent = `LOKASI_SHARE:${loc.degreesLatitude},${loc.degreesLongitude}`;
            }

            const payload = {
                sessionId: cleanId,
                remoteJid,
                sender,
                pushName,
                message: textContent,
                type: msgType,
                isGroup: remoteJid.endsWith('@g.us'),
                mentionedJid: msg.message?.extendedTextMessage?.contextInfo?.mentionedJid || [],
                timestamp: msg.messageTimestamp,
                media: mediaBase64,
                ocrText,
            };

            try {
                const resp = await laravelPost('/whatsapp/webhook', payload, { timeout: 30000 });

                const data = resp.data;
                console.log(`[WEBHOOK] Respon dari Laravel:`, JSON.stringify(data));
                if (!data) return;

                // Simulasi Jeda & Mengetik agar lebih "Manusiawi" (Anti-Ban)
                await sock.sendPresenceUpdate('composing', remoteJid);
                const delay = Math.floor(Math.random() * 1500) + 1500; // 1.5 - 3 detik
                await sleep(delay);
                await sock.sendPresenceUpdate('paused', remoteJid);

                // Case 1: Simple reply wrapped in { reply: "..." }
                if (data.reply && typeof data.reply === 'string') {
                    await sock.sendMessage(remoteJid, { text: data.reply }, { quoted: msg });
                } 
                // Case 2: Direct message object (e.g., from showMenu)
                else if (data.text || data.buttons || data.sections || data.image || data.document) {
                    await sock.sendMessage(remoteJid, data, { quoted: msg });
                }
            } catch (e) {
                console.error(`[WEBHOOK] Gagal kirim ke Laravel: ${e.message}`);
                if (e.response) console.error(`[WEBHOOK] Response Data:`, e.response.data);
            }
        }
    });

    return sock;
}

/** Auto-load sessions from folder */
async function loadExistingSessions() {
    if (!fs.existsSync(SESSIONS_PATH)) return;
    const folders = fs.readdirSync(SESSIONS_PATH);
    for (const folder of folders) {
        const fullPath = path.join(SESSIONS_PATH, folder);
        if (fs.statSync(fullPath).isDirectory()) {
            console.log(`[INIT] Memuat sesi tersimpan: ${folder}...`);
            await startSession(folder).catch(e => console.error(`[INIT] Gagal memuat ${folder}:`, e.message));
        }
    }
}

// ─── REST API ─────────────────────────────────────────────────────────────────

/** GET /sessions — daftar semua sesi */
app.get('/sessions', (req, res) => {
    res.json(Array.from(sessionStates.values()));
});

/** GET /groups — daftar semua grup WA */
app.get('/groups', requireSecret, async (req, res) => {
    let targetId = Array.from(sessions.keys()).find(id => sessionStates.get(id)?.status === 'open');
    if (!targetId) {
        return res.status(503).json({ error: 'Tidak ada sesi aktif yang tersedia' });
    }
    const sock = sessions.get(targetId);
    try {
        const groups = await sock.groupFetchAllParticipating();
        const groupList = Object.values(groups).map(g => ({
            id: g.id,
            subject: g.subject
        }));
        res.json(groupList);
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

/** GET /session/:id — status satu sesi */
app.get('/session/:id', (req, res) => {
    const cleanId = req.params.id.replace(/[^a-zA-Z0-9_]/g, '');
    const state   = sessionStates.get(cleanId);
    if (!state) return res.status(404).json({ error: 'Session not found' });
    res.json(state);
});

/**
 * POST /session/start — mulai sesi (QR mode)
 * Body: { id }
 */
app.post('/session/start', requireSecret, async (req, res) => {
    const cleanId    = (req.body.id || 'main').replace(/[^a-zA-Z0-9_]/g, '');
    const sessionDir = path.join(SESSIONS_PATH, cleanId);

    try {
        closeSock(cleanId);
        await new Promise(r => setTimeout(r, 500));
        await startSession(cleanId, { printQR: true });
        res.json({ success: true, sessionId: cleanId, status: 'connecting' });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

/**
 * POST /session/pairing — pairing code mode
 * Body: { id, phone }
 */
app.post('/session/pairing', requireSecret, async (req, res) => {
    const { id, phone } = req.body;
    if (!id || !phone) return res.status(400).json({ error: 'id dan phone wajib diisi' });

    const cleanId    = id.replace(/[^a-zA-Z0-9_]/g, '');
    const sessionDir = path.join(SESSIONS_PATH, cleanId);

    try {
        console.log(`[PAIR] Request Pairing untuk ${phone} (sesi: ${cleanId})`);

        // Matikan & bersihkan sesi lama
        closeSock(cleanId);
        deleteSessionDir(sessionDir);
        await new Promise(r => setTimeout(r, 1500));

        const sock       = await startSession(cleanId, { printQR: true });
        const cleanPhone = phone.replace(/[^0-9]/g, '');

        console.log(`[PAIR] Menunggu socket siap (8 detik)...`);
        await new Promise(r => setTimeout(r, 8000));

        if (sock.authState.creds.registered) {
            console.log(`[PAIR] Sesi ${cleanId} sudah terdaftar, membatalkan request pairing.`);
            return res.json({ success: true, message: 'Sudah terhubung', sessionId: cleanId });
        }

        console.log(`[PAIR] Meminta kode pairing untuk ${cleanPhone}...`);
        const code          = await sock.requestPairingCode(cleanPhone);
        const formattedCode = code?.match(/.{1,4}/g)?.join('-') || code;
        console.log(`[PAIR] ✅ Kode pairing: ${formattedCode}`);

        return res.json({ 
            success: true,
            pairingCode: formattedCode,
            sessionId: cleanId,
            phone: cleanPhone
        });

    } catch (e) {
        console.error(`[PAIR] Error:`, e.message);
        closeSock(cleanId);
        sessionStates.delete(cleanId);
        deleteSessionDir(sessionDir);
        return res.status(500).json({ error: e.message });
    }
});

/**
 * POST /session/stop — logout & hapus sesi
 * Body: { id }
 */
app.post('/session/stop', requireSecret, async (req, res) => {
    const cleanId    = (req.body.id || '').replace(/[^a-zA-Z0-9_]/g, '');
    const sessionDir = path.join(SESSIONS_PATH, cleanId);
    closeSock(cleanId);
    sessionStates.delete(cleanId);
    deleteSessionDir(sessionDir);
    res.json({ success: true, message: `Sesi ${cleanId} dihapus` });
});

/**
 * DELETE /session/:id — alias untuk stop
 */
app.delete('/session/:id', requireSecret, async (req, res) => {
    const cleanId    = req.params.id.replace(/[^a-zA-Z0-9_]/g, '');
    const sessionDir = path.join(SESSIONS_PATH, cleanId);
    closeSock(cleanId);
    sessionStates.delete(cleanId);
    deleteSessionDir(sessionDir);
    res.json({ success: true, message: `Sesi ${cleanId} dihapus` });
});

// ─── Message Queueing to prevent rate limits & timeouts ───────────────────────
const messageQueue = [];
let isProcessingQueue = false;

async function processQueue() {
    if (isProcessingQueue) return;
    isProcessingQueue = true;

    while (messageQueue.length > 0) {
        const item = messageQueue[0];
        const { sock, jid, payload, resolve, reject, cleanPhone, isAsync } = item;

        // Check if this sock is still connected; if not, wait for a reconnect (up to 30s)
        let activeSock = sock;
        const sessionId = [...sessions.entries()].find(([, s]) => s === sock)?.[0];
        if (sessionId && sessionStates.get(sessionId)?.status !== 'open') {
            console.log(`[QUEUE] Sesi sedang reconnect, menunggu... (${cleanPhone})`);
            let waited = 0;
            while (waited < 30000) {
                await sleep(2000);
                waited += 2000;
                if (sessionStates.get(sessionId)?.status === 'open') {
                    activeSock = sessions.get(sessionId);
                    break;
                }
            }
            if (sessionStates.get(sessionId)?.status !== 'open') {
                console.error(`[QUEUE] Sesi tidak kunjung terhubung, melewati pesan ke ${cleanPhone}`);
                messageQueue.shift();
                if (!isAsync) reject(new Error('Session not available'));
                continue;
            }
        }

        let lastErr = null;
        let sent = false;
        for (let attempt = 1; attempt <= 3; attempt++) {
            try {
                console.log(`[QUEUE] Sending message to ${cleanPhone}... (attempt ${attempt})`);
                await activeSock.sendMessage(jid, payload);
                console.log(`[QUEUE] Success to ${cleanPhone}`);
                sent = true;
                break;
            } catch (e) {
                lastErr = e;
                const isRetryable = e.message.includes('Connection Closed') ||
                                    e.message.includes('not-connected') ||
                                    e.message.includes('Connection Failure');
                console.error(`[QUEUE] Error to ${cleanPhone} (attempt ${attempt}): ${e.message}`);
                if (!isRetryable || attempt === 3) break;
                console.log(`[QUEUE] Retry in 5s...`);
                await sleep(5000);
                // Refresh sock reference in case session reconnected
                if (sessionId && sessions.has(sessionId)) {
                    activeSock = sessions.get(sessionId);
                }
            }
        }

        if (!isAsync) {
            if (sent) resolve({ success: true, to: cleanPhone });
            else reject(lastErr);
        }

        messageQueue.shift();
        
        // Anti-ban delay: 20 to 30 seconds
        const delay = Math.floor(Math.random() * 10000) + 20000;
        await sleep(delay);
    }

    isProcessingQueue = false;
}

function queueMessage(sock, jid, payload, cleanPhone, isAsync) {
    return new Promise((resolve, reject) => {
        messageQueue.push({ sock, jid, payload, resolve, reject, cleanPhone, isAsync });
        if (isAsync) {
            resolve({ success: true, queued: true, to: cleanPhone });
        }
        processQueue();
    });
}

/**
 * POST /send-message — kirim pesan teks / file
 * Body: { phone, message, sessionId, media, filename, mimetype, url, async }
 */
app.post('/send-message', requireSecret, async (req, res) => {
    const { phone, message, sessionId, media, filename, mimetype, url, caption } = req.body;
    const isAsync = req.body.async === true || req.body.async === 'true';

    if (!phone) return res.status(400).json({ error: 'phone wajib diisi' });

    // Pilih sesi
    let targetId = null;
    if (sessionId && sessions.has(sessionId) && sessionStates.get(sessionId)?.status === 'open') {
        targetId = sessionId;
    } else {
        targetId = Array.from(sessions.keys()).find(id => sessionStates.get(id)?.status === 'open');
    }

    if (!targetId) {
        return res.status(503).json({ error: 'Tidak ada sesi aktif yang tersedia' });
    }

    const sock  = sessions.get(targetId);
    const state = sessionStates.get(targetId);

    let jid;
    let cleanPhone = '';
    if (phone.endsWith('@g.us')) {
        jid = phone;
        cleanPhone = phone;
    } else {
        cleanPhone = phone.replace(/[^0-9]/g, '');
        jid = `${cleanPhone}@s.whatsapp.net`;
    }

    try {
        let payload = null;

        // ── Kirim File via URL ──
        if (url && filename) {
            const fileResp = await axiosInstance.get(url, { responseType: 'arraybuffer', timeout: 30000 });
            const buffer   = Buffer.from(fileResp.data);
            const mime     = mimetype || fileResp.headers['content-type'] || 'application/octet-stream';
            payload = {
                document: buffer,
                mimetype: mime,
                fileName: filename,
                caption: caption || message || ''
            };
        }
        // ── Kirim File via Base64 ──
        else if (media && filename) {
            const buffer = Buffer.from(media, 'base64');
            const mime   = mimetype || 'application/octet-stream';
            payload = {
                document: buffer,
                mimetype: mime,
                fileName: filename,
                caption: caption || message || ''
            };
        }
        // ── Kirim Teks ──
        else if (message) {
            payload = { text: message };
        }

        if (!payload) {
            return res.status(400).json({ error: 'Butuh message, media+filename, atau url+filename' });
        }

        // Gunakan antrean (queue) pesan agar stabil dan aman dari limit/rate limit
        const result = await queueMessage(sock, jid, payload, cleanPhone, isAsync);
        return res.json(result);

    } catch (e) {
        console.error('[SEND] Error:', e.message);
        res.status(500).json({ error: e.message });
    }
});

/**
 * POST /send-status — posting status WA (Stories)
 * Body: { message, media, mimetype, caption, sessionId }
 */
app.post('/send-status', requireSecret, async (req, res) => {
    const { message, media, mimetype, caption, sessionId } = req.body;

    // Pilih sesi
    let targetId = null;
    if (sessionId && sessions.has(sessionId) && sessionStates.get(sessionId)?.status === 'open') {
        targetId = sessionId;
    } else {
        targetId = Array.from(sessions.keys()).find(id => sessionStates.get(id)?.status === 'open');
    }

    if (!targetId) {
        return res.status(503).json({ error: 'Tidak ada sesi aktif yang tersedia' });
    }

    const sock = sessions.get(targetId);
    const jid  = 'status@broadcast';

    const statusJidList = req.body.statusJidList || [];

    // Auto-fetch all contacts saved in this session's phonebook memory
    const savedContacts = sessionContacts.get(targetId) || [];
    savedContacts.forEach(jid => {
        if (!statusJidList.includes(jid)) {
            statusJidList.push(jid);
        }
    });

    if (sock.user && sock.user.id) {
        const ownJid = sock.user.id.split(':')[0] + '@s.whatsapp.net';
        if (!statusJidList.includes(ownJid)) {
            statusJidList.push(ownJid);
        }
    }

    try {
        const sendOptions = {
            statusJidList: statusJidList,
            broadcast: true
        };

        // ── Status Gambar/Video (Media) ──
        if (media) {
            const buffer = Buffer.from(media, 'base64');
            const mime   = mimetype || 'image/jpeg';
            
            if (mime.startsWith('image/')) {
                await sock.sendMessage(jid, {
                    image: buffer,
                    caption: caption || message || ''
                }, sendOptions);
            } else if (mime.startsWith('video/')) {
                await sock.sendMessage(jid, {
                    video: buffer,
                    caption: caption || message || ''
                }, sendOptions);
            } else {
                return res.status(400).json({ error: 'Mimetype media status harus berupa image/ atau video/' });
            }
            return res.json({ success: true });
        }

        // ── Status Teks ──
        if (message) {
            await sock.sendMessage(jid, { text: message }, sendOptions);
            return res.json({ success: true });
        }

        return res.status(400).json({ error: 'Butuh message atau media untuk update status' });
    } catch (e) {
        console.error('[STATUS] Error:', e.message);
        res.status(500).json({ error: e.message });
    }
});

// ─── Start Server ─────────────────────────────────────────────────────────────
app.listen(PORT, async () => {
    console.log(`✅ WhatsApp Bot Aktif di Port ${PORT}`);
    console.log(`📋 Endpoint:`);
    console.log(`   GET    /sessions`);
    console.log(`   GET    /session/:id`);
    console.log(`   POST   /session/start    { id }`);
    console.log(`   POST   /session/pairing  { id, phone }`);
    console.log(`   POST   /session/stop     { id }`);
    console.log(`   DELETE /session/:id`);
    console.log(`   POST   /send-message     { phone, message|media|url, sessionId }`);
    console.log(`   (Semua butuh X-Bot-Secret header atau ?secret=)`);

    console.log(`\n[INIT] Mencari sesi tersimpan...`);
    await loadExistingSessions();
});

<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\TiketGangguan;
use App\Models\BotResponse;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TelegramBotController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Telegram Webhook Handler
     * POST /api/telegram/webhook
     */
    public function webhook(Request $request)
    {
        $update = $request->all();
        Log::info('Telegram Webhook Payload: ', $update);

        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!$message || !isset($message['chat']['id'])) {
            return response()->json(['status' => 'ignored_no_message']);
        }

        $chatId    = $message['chat']['id'];
        $text      = trim($message['text'] ?? '');
        $firstName = $message['from']['first_name'] ?? 'Pelanggan';
        $username  = $message['from']['username'] ?? '';

        return $this->processIncomingMessage($chatId, $text, $firstName, $username);
    }

    /**
     * Long Polling Fetcher (untuk Localhost tanpa Webhook HTTPS)
     */
    public function pollOnce()
    {
        $setting = DB::table('telegram_settings')->first();
        if (!$setting || empty($setting->bot_token)) {
            return response()->json(['success' => false, 'message' => 'Bot Token belum dikonfigurasi']);
        }

        $token = $setting->bot_token;
        $offset = cache()->get('telegram_last_offset', 0);

        try {
            $this->telegramService->deleteWebhook(false);

            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                'offset' => $offset,
                'limit' => 20
            ]);

            if ($response->successful()) {
                $results = $response->json()['result'] ?? [];
                $processedCount = 0;

                foreach ($results as $update) {
                    $offset = $update['update_id'] + 1;
                    cache()->put('telegram_last_offset', $offset, 86400);

                    $message = $update['message'] ?? $update['edited_message'] ?? null;
                    if ($message && isset($message['chat']['id'])) {
                        $chatId    = $message['chat']['id'];
                        $text      = trim($message['text'] ?? '');
                        $firstName = $message['from']['first_name'] ?? 'Pelanggan';
                        $username  = $message['from']['username'] ?? '';

                        $this->processIncomingMessage($chatId, $text, $firstName, $username);
                        $processedCount++;
                    }
                }

                return response()->json([
                    'success' => true,
                    'processed_messages' => $processedCount,
                    'new_offset' => $offset
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Telegram Poll Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'message' => 'Gagal mengambil pesan dari Telegram']);
    }

    /**
     * Core Logic untuk memproses pesan Telegram & Membalas Otomatis
     */
    public function processIncomingMessage($chatId, $text, $firstName = 'Pelanggan', $username = '')
    {
        if (empty($text)) {
            return response()->json(['status' => 'empty_text']);
        }

        $lowerText = strtolower($text);

        // 1. Sapaan Ramah: halo, hi, p, bot, ping, tes, pagi, siang, malam, salam
        $greetings = ['halo', 'hi', 'p', 'bot', 'ping', 'tes', 'pagi', 'siang', 'malam', 'assalamualaikum', 'salam'];
        if (in_array($lowerText, $greetings)) {
            $msg = "Halo Kak <b>" . htmlspecialchars($firstName) . "</b>!\n\n";
            $msg .= "Selamat datang di <b>Rozitech Network Bot</b>!\n\n";
            $msg .= "Ada yang bisa kami bantu? Ketik <b>/menu</b> atau <b>menu</b> untuk melihat daftar layanan otomatis kami:\n\n";
            $msg .= "[BAYAR] <code>/bayar</code> -> Cek Tagihan & Pembayaran Otomatis\n";
            $msg .= "[TIKET] <code>/tiket [keluhan]</code> -> Lapor Gangguan Jaringan\n";
            $msg .= "[STATUS] <code>/status</code> -> Cek Status Sinyal ONT & Paket";
            $this->telegramService->sendMessage($chatId, $msg);
            return response()->json(['status' => 'greeting_sent']);
        }

        // 2. Command /start, /menu, /help, menu
        if ($lowerText === '/start' || $lowerText === '/menu' || $lowerText === '/help' || $lowerText === 'menu') {
            return $this->sendMenu($chatId, $firstName);
        }

        // 3. Command /bayar, tagihan, bayar, cek tagihan
        if (str_starts_with($lowerText, '/bayar') || str_starts_with($lowerText, 'bayar') || str_starts_with($lowerText, 'tagihan') || str_starts_with($lowerText, 'cek tagihan')) {
            return $this->handleBayarCommand($chatId, $text, $firstName);
        }

        // 4. Command /tiket, tiket, lapor, trouble, gangguan, komplain
        if (str_starts_with($lowerText, '/tiket') || str_starts_with($lowerText, 'tiket') || str_starts_with($lowerText, 'lapor') || str_starts_with($lowerText, 'trouble') || str_starts_with($lowerText, 'gangguan') || str_starts_with($lowerText, 'komplain')) {
            return $this->handleTiketCommand($chatId, $text, $firstName);
        }

        // 5. Command /status, status, cek status
        if (str_starts_with($lowerText, '/status') || str_starts_with($lowerText, 'status') || str_starts_with($lowerText, 'cek status')) {
            return $this->handleStatusCommand($chatId, $text, $firstName);
        }

        // 6. Check Keyword Response in Database
        $botResponse = $this->findKeywordResponse($text);
        if ($botResponse) {
            $reply = str_replace(
                ['{Nama Customer}', '{Nama}', '{PushName}'],
                [$firstName, $firstName, $firstName],
                $botResponse->response
            );
            $this->telegramService->sendMessage($chatId, $this->formatForTelegram($reply));
            return response()->json(['status' => 'matched_keyword']);
        }

        // 7. AI Fallback Response
        $aiReply = $this->getAiResponse($text, $firstName);
        if ($aiReply) {
            $this->telegramService->sendMessage($chatId, $aiReply);
            return response()->json(['status' => 'ai_reply']);
        }

        // Fallback default
        $defaultMsg = "<b>ROZITECH TELEGRAM BOT</b>\n\nHalo Kak <b>" . htmlspecialchars($firstName) . "</b>, ketik <b>/menu</b> atau <b>menu</b> untuk melihat daftar opsi bantuan otomatis.";
        $this->telegramService->sendMessage($chatId, $defaultMsg);
        return response()->json(['status' => 'default_reply']);
    }

    /**
     * Render Menu Utama
     */
    protected function sendMenu($chatId, $firstName)
    {
        $msg = "<b>SELAMAT DATANG DI ROZITECH BOT AUTOMATION</b>\n";
        $msg .= "Halo Kak <b>" . htmlspecialchars($firstName) . "</b>!\n\n";
        $msg .= "Silakan pilih layanan otomatis yang Anda butuhkan:\n\n";
        $msg .= "<b>[PEMBAYARAN]</b>\n";
        $msg .= "• Ketik <code>/bayar</code> atau <code>TAGIHAN</code> -> Cek rincian tagihan & Pembayaran Otomatis via QRIS/VA\n\n";
        $msg .= "<b>[TICKETING & GANGGUAN]</b>\n";
        $msg .= "• Ketik <code>/tiket [keluhan]</code> atau <code>LAPOR [keluhan]</code> -> Buat tiket penanganan teknisi otomatis\n\n";
        $msg .= "<b>[MONITORING KONEKSI]</b>\n";
        $msg .= "• Ketik <code>/status</code> -> Cek status ONT, redaman sinyal RX (dBm), & paket aktif\n\n";
        $msg .= "<b>[CUSTOMER SERVICE]</b>\n";
        $msg .= "• Ketik <code>CS</code> untuk terhubung langsung dengan Tim Support Rozitech.";

        $this->telegramService->sendMessage($chatId, $msg);
        return response()->json(['status' => 'menu_sent']);
    }

    /**
     * Smart Customer Resolution Helper
     */
    protected function findCustomer(?string $code = null): ?Pelanggan
    {
        if (!empty($code)) {
            $codeStr = strtoupper(trim($code));
            
            // 1. Exact match by kode_pelanggan, mikrotik_username, or id_pelanggan
            $p = Pelanggan::whereRaw('UPPER(kode_pelanggan) = ?', [$codeStr])
                ->orWhereRaw('UPPER(mikrotik_username) = ?', [$codeStr])
                ->orWhere('id_pelanggan', $codeStr)
                ->first();
            if ($p) return $p;

            // 2. Partial match
            $p = Pelanggan::whereRaw('UPPER(kode_pelanggan) LIKE ?', ["%{$codeStr}%"])
                ->orWhereRaw('UPPER(nama_pelanggan) LIKE ?', ["%{$codeStr}%"])
                ->orWhereRaw('UPPER(mikrotik_username) LIKE ?', ["%{$codeStr}%"])
                ->first();
            if ($p) return $p;
        }

        // 3. Smart Fallback: customer with unpaid bill or first customer
        $p = Pelanggan::whereHas('tagihan', fn($q) => $q->where('status', '!=', 'paid'))->first();
        return $p ?: Pelanggan::first();
    }

    /**
     * Handle Bayar Command
     */
    protected function handleBayarCommand($chatId, $text, $firstName)
    {
        $parts = explode(' ', trim($text));
        $code  = count($parts) > 1 ? trim($parts[1]) : null;

        $pelanggan = $this->findCustomer($code);

        if (!$pelanggan) {
            $msg = "<b>CEK TAGIHAN & PEMBAYARAN OTOMATIS</b>\n\n";
            $msg .= "Silakan ketik <code>/bayar [KODE_PELANGGAN]</code>\n";
            $msg .= "Contoh: <code>/bayar KTR01</code>\n\n";
            $msg .= "<i>Anda dapat melihat Kode Pelanggan pada stiker router ONT Anda.</i>";
            $this->telegramService->sendMessage($chatId, $msg);
            return response()->json(['status' => 'prompt_code']);
        }

        $tagihanUnpaid = Tagihan::where('id_pelanggan', $pelanggan->id_pelanggan)
            ->where('status', '!=', 'paid')
            ->get();

        if ($tagihanUnpaid->isEmpty()) {
            $msg = "<b>STATUS TAGIHAN LUNAS</b>\n\n";
            $msg .= "Pelanggan: <b>" . htmlspecialchars($pelanggan->nama_pelanggan) . "</b> (" . $pelanggan->kode_pelanggan . ")\n";
            $msg .= "Status: <b>LUNAS (Tidak ada tunggakan)</b>\n\n";
            $msg .= "Terima kasih telah menggunakan layanan Rozitech Network!";
            $this->telegramService->sendMessage($chatId, $msg);
            return response()->json(['status' => 'paid']);
        }

        $total = 0;
        $info  = "<b>RINCIAN TAGIHAN UNPAID</b>\n";
        $info .= "--------------------------------------\n";
        $info .= "Pelanggan: <b>" . htmlspecialchars($pelanggan->nama_pelanggan) . "</b> (" . $pelanggan->kode_pelanggan . ")\n";
        $info .= "Paket: <b>" . htmlspecialchars($pelanggan->paket ?? 'Paket WiFi') . "</b>\n";
        $info .= "--------------------------------------\n";

        foreach ($tagihanUnpaid as $t) {
            $info .= "• Periode " . sprintf('%02d', $t->bulan) . "/" . $t->tahun . ": <b>Rp " . number_format($t->jumlah, 0, ',', '.') . "</b>\n";
            $total += $t->jumlah;
        }

        $paymentUrl = route('payment.by-id', ['kode_pelanggan' => $pelanggan->kode_pelanggan]);

        $info .= "--------------------------------------\n";
        $info .= "<b>TOTAL TAGIHAN: Rp " . number_format($total, 0, ',', '.') . "</b>\n\n";
        $info .= "<b>LINK PEMBAYARAN OTOMATIS:</b>\n";
        $info .= "<a href=\"{$paymentUrl}\">Klik Di Sini Untuk Bayar (QRIS/Transfer/E-Wallet)</a>\n\n";
        $info .= "<i>Setelah pembayaran selesai di link di atas, koneksi internet Anda akan otomatis aktif kembali secara instan!</i>";

        $this->telegramService->sendMessage($chatId, $info);
        return response()->json(['status' => 'bill_sent']);
    }

    /**
     * Handle Tiket / Trouble Command
     */
    protected function handleTiketCommand($chatId, $text, $firstName)
    {
        $cleanText = preg_replace('/^(\/tiket|tiket|lapor|trouble|gangguan|komplain)\s*/i', '', $text);
        $cleanText = trim(str_replace(['[', ']'], '', $cleanText));
        
        $pelanggan = $this->findCustomer(null);
        $keluhan   = !empty($cleanText) ? $cleanText : 'Laporan gangguan koneksi internet';

        $kodeTiket = 'TKT-' . date('YmdHis');
        $tiket = TiketGangguan::create([
            'kode_tiket'   => $kodeTiket,
            'id_pelanggan' => $pelanggan?->id_pelanggan ?? 1,
            'prioritas'    => 'Sedang',
            'keluhan'      => $keluhan,
            'status'       => 'Open',
        ]);

        // Push In-App Notification to Web Navbar Dropdown
        $pelangganName = $pelanggan->nama_pelanggan ?? $firstName;
        try {
            \App\Helpers\NotificationHelper::sendToAll(
                'tiket_baru',
                'Tiket Gangguan Baru (Telegram)',
                "Tiket #{$kodeTiket} dari {$pelangganName}: {$keluhan}",
                [
                    'icon'       => 'bx-error-circle',
                    'color'      => 'danger',
                    'action_url' => route('tiket.index'),
                ]
            );
        } catch (\Exception $e) {
            Log::error('In-app notification broadcast error: ' . $e->getMessage());
        }

        $reply = "<b>TIKET GANGGUAN BERHASIL DIBUAT!</b>\n";
        $reply .= "--------------------------------------\n";
        $reply .= "No Tiket  : <code>{$kodeTiket}</code>\n";
        $reply .= "Pelanggan : <b>" . htmlspecialchars($pelanggan->nama_pelanggan ?? $firstName) . "</b>\n";
        $reply .= "Keluhan   : <i>" . htmlspecialchars($keluhan) . "</i>\n";
        $reply .= "Status    : <b>OPEN (Dalam Antrean Teknisi)</b>\n";
        $reply .= "--------------------------------------\n";
        $reply .= "Notifikasi laporan ini telah otomatis diteruskan ke Tim Teknisi & Manajer Support. Terima kasih!";

        $this->telegramService->sendMessage($chatId, $reply);

        $adminAlert = "<b>TIKET GANGGUAN BARU (#{$kodeTiket})</b>\n";
        $adminAlert .= "Pelanggan : <b>" . htmlspecialchars($pelanggan->nama_pelanggan ?? $firstName) . "</b>\n";
        $adminAlert .= "No WA     : " . ($pelanggan->no_wa ?? '-') . "\n";
        $adminAlert .= "Keluhan   : " . htmlspecialchars($keluhan) . "\n";
        $adminAlert .= "Waktu     : " . now()->format('H:i:s d/m/Y');
        $this->telegramService->sendAdminAlert($adminAlert);

        return response()->json(['status' => 'ticket_created']);
    }

    /**
     * Handle Status Command
     */
    protected function handleStatusCommand($chatId, $text, $firstName)
    {
        $parts = explode(' ', trim($text));
        $code  = count($parts) > 1 ? trim($parts[1]) : null;

        $pelanggan = $this->findCustomer($code);

        if (!$pelanggan) {
            $msg = "<b>MONITORING STATUS KONEKSI ONT</b>\n\n";
            $msg .= "Silakan ketik: <code>/status [KODE_PELANGGAN]</code>\n";
            $msg .= "Contoh: <code>/status KTR01</code>";
            $this->telegramService->sendMessage($chatId, $msg);
            return response()->json(['status' => 'prompt_status']);
        }

        $rx = $pelanggan->onu_rx_power ? number_format($pelanggan->onu_rx_power, 2) . " dBm" : "N/A";
        $statusOnt = $pelanggan->is_online ? "<b>ONLINE</b>" : "<b>OFFLINE / LOS</b>";

        $msg = "<b>STATUS MONITORING KONEKSI PELANGGAN</b>\n";
        $msg .= "--------------------------------------\n";
        $msg .= "Pelanggan : <b>" . htmlspecialchars($pelanggan->nama_pelanggan) . "</b> (" . $pelanggan->kode_pelanggan . ")\n";
        $msg .= "Status ONT: {$statusOnt}\n";
        $msg .= "Redaman RX: <b>{$rx}</b>\n";
        $msg .= "Paket     : " . htmlspecialchars($pelanggan->paket ?? 'Internet') . "\n";
        $msg .= "Terisolir : " . ($pelanggan->is_isolated ? "YA (Tunggakan Bayar)" : "TIDAK (Normal)") . "\n";
        $msg .= "Terakhir Inform: " . ($pelanggan->last_inform_at ? $pelanggan->last_inform_at : '-') . "\n";
        $msg .= "--------------------------------------";

        $this->telegramService->sendMessage($chatId, $msg);
        return response()->json(['status' => 'status_sent']);
    }

    /**
     * Find keyword response from DB
     */
    protected function findKeywordResponse($message)
    {
        $lowerMsg = strtolower(trim($message));
        $responses = BotResponse::where('is_active', true)->get();

        foreach ($responses as $bot) {
            $keywords = array_filter(array_map('trim', explode(',', strtolower($bot->keyword))));
            foreach ($keywords as $kw) {
                if ($lowerMsg === $kw) return $bot;
                if (!$bot->is_exact_match && strlen($kw) >= 3 && str_contains($lowerMsg, $kw)) return $bot;
            }
        }
        return null;
    }

    /**
     * Get AI Response fallback
     */
    protected function getAiResponse($prompt, $userName)
    {
        $apiKey = env('GEMINI_API_KEY') ?: env('OPENAI_API_KEY');
        if (empty($apiKey)) return null;

        try {
            if (env('GEMINI_API_KEY')) {
                $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => "Kamu adalah AI Customer Support Rozitech NMS (ISP & FTTH). Jawab dengan sopan, ringkas, & ramah tanpa menggunakan emoji/stiker kepada pelanggan bernama {$userName}. Pertanyaan: {$prompt}"]
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                }
            }
        } catch (\Exception $e) {
            Log::error("Telegram AI Error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Format text for Telegram HTML
     */
    protected function formatForTelegram($text)
    {
        $formatted = preg_replace('/\*(.*?)\*/s', '<b>$1</b>', $text);
        return $formatted;
    }
}

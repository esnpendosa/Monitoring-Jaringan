@extends('layouts/contentNavbarLayout')

@section('title', 'WhatsApp Manager - Multi Device & Dynamic Bot Connection')

@section('content')
<div class="d-flex align-items-center justify-content-between py-3 mb-3">
    <h4 class="fw-bold m-0"><span class="text-muted fw-light">WhatsApp /</span> Multi Device & Dynamic Bot Manager</h4>
    <div>
        <span id="liveStatusBadge" class="badge {{ $isServerOnline ? 'bg-success' : 'bg-danger' }} fs-6 px-3 py-2">
            <i class="bx {{ $isServerOnline ? 'bx-check-circle' : 'bx-x-circle' }} me-1"></i>
            <span id="liveStatusText">Server Bot {{ $isServerOnline ? 'ONLINE' : 'OFFLINE' }}</span>
        </span>
    </div>
</div>

<!-- SERVER STATUS BANNER -->
<div id="serverAlertBanner" class="alert {{ $isServerOnline ? 'alert-success' : 'alert-danger' }} d-flex align-items-center justify-content-between mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="bx {{ $isServerOnline ? 'bx-check-circle' : 'bx-error-circle' }} fs-4 me-2"></i>
        <div>
            <strong id="bannerTitle">{{ $isServerOnline ? 'Server Bot WhatsApp Terhubung' : 'Server Bot WhatsApp OFFLINE' }}</strong>
            <div id="bannerText" class="small">
                {{ $isServerOnline ? 'Sistem dapat menerima & mengirim pesan WhatsApp otomatis secara dinamis.' : 'Silakan klik tombol di samping untuk menjalankan ulang server bot.' }}
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-dark btn-sm" id="btnTestConn">
            <i class="bx bx-refresh me-1"></i> Tes Koneksi
        </button>
        <button class="btn {{ $isServerOnline ? 'btn-outline-danger' : 'btn-danger' }} btn-sm" id="btnStartBot">
            <i class="bx bx-play me-1"></i> Jalankan Server Bot
        </button>
    </div>
</div>

<div class="row">
    <!-- DYNAMIC CONNECTION CONFIGURATION CARD -->
    <div class="col-12 mb-4">
        <div class="card border-top border-primary border-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 text-primary d-flex align-items-center gap-2">
                    <i class="bx bx-slider-alt"></i> Pengaturan Koneksi Server Bot Dinamis
                </h5>
                <span class="badge bg-label-secondary">Rest API / Baileys Engine</span>
            </div>
            <div class="card-body">
                <form id="formBotConfig" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">URL Server Bot WhatsApp</label>
                        <input type="text" id="botUrl" class="form-control" value="{{ $botUrl }}" placeholder="http://127.0.0.1:3000">
                        <div class="form-text">Contoh: <code>http://127.0.0.1:3000</code> atau IP Server Node.js Anda</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bot Secret Key (X-Bot-Secret)</label>
                        <input type="password" id="botSecret" class="form-control" value="{{ $botSecret }}" placeholder="rozitech-bot-secret-2024">
                        <div class="form-text">Kunci rahasia enkripsi keamanan API Bot</div>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100" id="btnSaveConfig">
                            <i class="bx bx-save me-1"></i> Simpan & Sambungkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SESSION LIST TABLE -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 d-flex align-items-center gap-2">
                    <i class="bx bx-devices text-info"></i> Perangkat Multi-Device Terhubung
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-info btn-sm" id="btnSyncSessions">
                        <i class="bx bx-sync me-1"></i> Sinkronkan Sesi
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                        <i class="bx bx-plus me-1"></i> Tambah Perangkat
                    </button>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Nomor WA</th>
                            <th>Status Sesi</th>
                            <th>Aksi Perangkat</th>
                        </tr>
                    </thead>
                    <tbody id="sessionTbody" class="table-border-bottom-0">
                        @forelse($sessions ?? [] as $session)
                        <tr data-session-id="{{ $session['id'] }}">
                            <td><strong>{{ $session['id'] }}</strong></td>
                            <td>{{ $session['user']['id'] ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $session['status'] === 'open' ? 'bg-label-success' : 'bg-label-warning' }}">
                                    {{ strtoupper($session['status']) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($session['status'] !== 'open' && isset($session['qr']))
                                    <button class="btn btn-sm btn-info" onclick="showQrModal('{{ $session['qr'] }}')" title="Scan QR">
                                        <i class="bx bx-qr-scan"></i>
                                    </button>
                                    @endif
                                    @if($session['status'] === 'open')
                                    <button class="btn btn-sm btn-warning stop-session" data-id="{{ $session['id'] }}" title="Disconnect/Logout">
                                        <i class="bx bx-log-out"></i>
                                    </button>
                                    @endif
                                    <button class="btn btn-sm btn-danger delete-session" data-id="{{ $session['id'] }}" title="Hapus Permanen">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bx bx-devices fs-2 mb-2 d-block text-secondary"></i>
                                {{ $isServerOnline ? 'Belum ada perangkat terhubung. Silakan klik + Tambah Perangkat.' : 'Server Bot Offline. Jalankan server bot untuk melihat perangkat terhubung.' }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- INSTRUCTIONS CARD -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center gap-2">
                    <i class="bx bx-bulb text-warning"></i> Petunjuk Multi-Device
                </h5>
                <p class="card-text small">
                    Fitur ini memungkinkan Anda menghubungkan lebih dari satu nomor WhatsApp ke sistem secara <strong>dinamis</strong>.
                </p>
                <ul class="small ps-3">
                    <li>Gunakan <strong>Session ID</strong> unik (misal: <code>cs1</code>, <code>admin</code>, <code>kasir</code>).</li>
                    <li>Menghubungkan via <strong>Pairing Code 8 Digit</strong> (Tanpa Scan QR).</li>
                    <li>Status <strong>OPEN</strong> menandakan bot sudah aktif dan dapat merespons otomatis.</li>
                </ul>
                <div class="alert alert-warning py-2 small mb-0">
                    <strong>Sistem Dinamis:</strong> Pengiriman pesan keluar akan berotasi menggunakan sesi aktif yang tersedia.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ADD SESSION -->
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bx bx-mobile-alt text-primary"></i> Tambah Perangkat WhatsApp Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Session ID (Bebas, Tanpa Spasi)</label>
                    <input type="text" id="newSessionId" class="form-control" placeholder="misal: wa_admin">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Metode Koneksi</label>
                    <select id="connectMethod" class="form-select">
                        <option value="pairing" selected>Kode Pairing 8 Digit (Tanpa Scan QR)</option>
                        <option value="qr">Scan Kode QR</option>
                    </select>
                </div>
                <div id="pairingSection" style="display: block;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor WhatsApp (Format 628xxx)</label>
                        <input type="text" id="pairingPhone" class="form-control" placeholder="628123456789">
                    </div>
                </div>

                <div id="resultArea" class="text-center mt-4" style="display: none;">
                    <div id="qrContainer" style="display: none;">
                        <canvas id="qrCanvas" class="border rounded p-2 bg-white"></canvas>
                        <p class="mt-2 text-muted small">Scan QR di atas lewat WhatsApp > Perangkat Tertaut</p>
                    </div>
                    <div id="pairingContainer" style="display: none;" class="p-3 bg-light rounded border">
                        <div class="text-muted small mb-1">KODE PAIRING WHATSAPP:</div>
                        <h2 id="pairingDisplay" class="fw-bold text-primary tracking-widest my-2"></h2>
                        <p class="mt-2 text-muted small mb-0">Masukkan 8 digit kode di atas pada aplikasi WA HP Anda (<strong>Perangkat Tertaut > Tautkan dengan nomor telepon saja</strong>)</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnGenerate">Dapatkan Kode / Hubungkan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script>
    if (typeof axios !== 'undefined') {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    let isServerOnline = {{ $isServerOnline ? 'true' : 'false' }};
    let pollingInterval = null;

    document.addEventListener('DOMContentLoaded', function() {
        const btnStartBot    = document.getElementById('btnStartBot');
        const btnTestConn    = document.getElementById('btnTestConn');
        const btnSyncSessions = document.getElementById('btnSyncSessions');
        const formBotConfig  = document.getElementById('formBotConfig');
        const methodSelect   = document.getElementById('connectMethod');
        const pairingSection = document.getElementById('pairingSection');
        const btnGenerate    = document.getElementById('btnGenerate');
        const resultArea     = document.getElementById('resultArea');
        const qrCanvas       = document.getElementById('qrCanvas');

        // Toggle pairing section
        methodSelect.addEventListener('change', function() {
            pairingSection.style.display = this.value === 'pairing' ? 'block' : 'none';
        });

        // 1. Simpan & Sambungkan Config Dinamis
        formBotConfig.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveConfig');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

            axios.post("{{ route('whatsapp.config.save') }}", {
                bot_url: document.getElementById('botUrl').value,
                bot_secret: document.getElementById('botSecret').value
            })
            .then(res => {
                Swal.fire({
                    icon: res.data.is_online ? 'success' : 'warning',
                    title: res.data.is_online ? 'Koneksi Berhasil!' : 'Tersimpan',
                    text: res.data.message
                });
                updateServerStatus(res.data.is_online);
                refreshSessions();
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    text: err.response?.data?.message || 'Terjadi kesalahan'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-save me-1"></i> Simpan & Sambungkan';
            });
        });

        // 2. Tes Koneksi Realtime
        btnTestConn.addEventListener('click', function() {
            btnTestConn.disabled = true;
            btnTestConn.innerHTML = '<i class="bx bx-sync bx-spin me-1"></i> Memeriksa...';

            axios.post("{{ route('whatsapp.config.test') }}")
                .then(res => {
                    updateServerStatus(res.data.is_online);
                    Swal.fire({
                        icon: res.data.is_online ? 'success' : 'error',
                        title: res.data.is_online ? 'Server Bot ONLINE' : 'Server Bot OFFLINE',
                        text: res.data.message
                    });
                })
                .finally(() => {
                    btnTestConn.disabled = false;
                    btnTestConn.innerHTML = '<i class="bx bx-refresh me-1"></i> Tes Koneksi';
                });
        });

        // 3. Jalankan Server Bot Daemon
        if (btnStartBot) {
            btnStartBot.addEventListener('click', function() {
                btnStartBot.disabled = true;
                btnStartBot.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memulai Bot...';

                axios.post("{{ route('whatsapp.bot.start') }}")
                    .then(res => {
                        Swal.fire({
                            icon: 'info',
                            title: 'Memulai Server Bot...',
                            text: res.data.message || 'Harap tunggu beberapa detik, status akan ter-update otomatis.',
                            timer: 6000,
                            showConfirmButton: false
                        });
                        setTimeout(refreshSessions, 5000);
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menjalankan Bot',
                            text: err.response?.data?.message || 'Terjadi kesalahan sistem.'
                        });
                    })
                    .finally(() => {
                        btnStartBot.disabled = false;
                        btnStartBot.innerHTML = '<i class="bx bx-play me-1"></i> Jalankan Server Bot';
                    });
            });
        }

        // 4. Manual Sync Sessions
        btnSyncSessions.addEventListener('click', refreshSessions);

        // 5. Generate Session / Pairing Code
        btnGenerate.addEventListener('click', function() {
            const id = document.getElementById('newSessionId').value.trim().replace(/\s+/g, '_');
            const method = methodSelect.value;
            const phone = document.getElementById('pairingPhone').value;

            if(!id) return Swal.fire({ icon: 'warning', title: 'Session ID Kosong', text: 'Masukkan Session ID terlebih dahulu.' });

            btnGenerate.disabled = true;
            btnGenerate.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghubungkan...';

            if(method === 'qr') {
                axios.post("{{ route('whatsapp.session.start') }}", { id: id })
                    .then(res => {
                        if (res.data.error) {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error });
                            return;
                        }
                        startPollingSession(id);
                        resultArea.style.display = 'block';
                        document.getElementById('qrContainer').style.display = 'block';
                        document.getElementById('pairingContainer').style.display = 'none';
                        btnGenerate.innerHTML = '<i class="bx bx-sync bx-spin me-1"></i> Menunggu Scan QR...';
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Gagal Memulai Sesi', text: err.response?.data?.error || 'Gagal konek ke server bot.' });
                    })
                    .finally(() => {
                        btnGenerate.disabled = false;
                        btnGenerate.innerHTML = 'Hubungkan';
                    });
            } else {
                if (!phone) {
                    Swal.fire({ icon: 'warning', title: 'Nomor Kosong', text: 'Masukkan nomor WhatsApp format 628xxx terlebih dahulu.' });
                    btnGenerate.disabled = false;
                    btnGenerate.innerHTML = 'Hubungkan';
                    return;
                }
                axios.post("{{ route('whatsapp.session.pairing') }}", { id: id, phone: phone })
                    .then(res => {
                        if (res.data.pairingCode) {
                            resultArea.style.display = 'block';
                            document.getElementById('pairingContainer').style.display = 'block';
                            document.getElementById('qrContainer').style.display = 'none';
                            document.getElementById('pairingDisplay').innerText = res.data.pairingCode;
                            startPollingSession(id);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error || 'Gagal mendapatkan kode pairing.' });
                        }
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Gagal Pairing', text: err.response?.data?.error || 'Terjadi kesalahan saat meminta kode pairing.' });
                    })
                    .finally(() => {
                        btnGenerate.disabled = false;
                        btnGenerate.innerHTML = 'Hubungkan';
                    });
            }
        });

        // Event delegation for Disconnect and Delete
        document.getElementById('sessionTbody').addEventListener('click', function(e) {
            const stopBtn = e.target.closest('.stop-session');
            const delBtn  = e.target.closest('.delete-session');

            if (stopBtn) {
                const id = stopBtn.dataset.id;
                Swal.fire({
                    title: 'Putuskan Sesi?',
                    text: 'Nomor WhatsApp ' + id + ' akan terlepas dari bot.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Putuskan'
                }).then(res => {
                    if (res.isConfirmed) {
                        axios.post("{{ route('whatsapp.session.stop') }}", { id: id })
                            .then(() => {
                                Swal.fire({ icon: 'success', title: 'Terputus', timer: 1500, showConfirmButton: false });
                                refreshSessions();
                            });
                    }
                });
            }

            if (delBtn) {
                const id = delBtn.dataset.id;
                Swal.fire({
                    title: 'Hapus Permanen Sesi?',
                    text: 'Sesi ' + id + ' akan dihapus dari server.',
                    icon: 'danger',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus'
                }).then(res => {
                    if (res.isConfirmed) {
                        axios.post("{{ route('whatsapp.session.stop') }}", { id: id })
                            .then(() => {
                                Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1500, showConfirmButton: false });
                                refreshSessions();
                            });
                    }
                });
            }
        });

        // Polling realtime otomatis setiap 4 detik untuk update status server & sesi
        setInterval(refreshSessions, 4000);
    });

    // Helper to update server status banner & badge
    function updateServerStatus(online) {
        isServerOnline = online;
        const badge = document.getElementById('liveStatusBadge');
        const badgeText = document.getElementById('liveStatusText');
        const banner = document.getElementById('serverAlertBanner');
        const bannerTitle = document.getElementById('bannerTitle');
        const bannerText = document.getElementById('bannerText');

        if (online) {
            badge.className = 'badge bg-success fs-6 px-3 py-2';
            badge.innerHTML = '<i class="bx bx-check-circle me-1"></i> Server Bot ONLINE';
            banner.className = 'alert alert-success d-flex align-items-center justify-content-between mb-4';
            bannerTitle.innerText = 'Server Bot WhatsApp Terhubung & Active';
            bannerText.innerText = 'Sistem dapat menerima & mengirim pesan WhatsApp otomatis secara dinamis.';
        } else {
            badge.className = 'badge bg-danger fs-6 px-3 py-2';
            badge.innerHTML = '<i class="bx bx-x-circle me-1"></i> Server Bot OFFLINE';
            banner.className = 'alert alert-danger d-flex align-items-center justify-content-between mb-4';
            bannerTitle.innerText = 'Server Bot WhatsApp OFFLINE';
            bannerText.innerText = 'Silakan klik tombol di samping untuk menjalankan ulang server bot.';
        }
    }

    // Refresh Session list via AJAX
    function refreshSessions() {
        axios.get("{{ route('whatsapp.index') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => {
                updateServerStatus(res.data.is_server_online);
                renderSessionTable(res.data.sessions, res.data.is_server_online);
            })
            .catch(() => {
                updateServerStatus(false);
            });
    }

    // Render session table dynamically
    function renderSessionTable(sessions, online) {
        const tbody = document.getElementById('sessionTbody');
        if (!online || !sessions || sessions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted">
                        <i class="bx bx-devices fs-2 mb-2 d-block text-secondary"></i>
                        ${online ? 'Belum ada perangkat terhubung. Silakan klik + Tambah Perangkat.' : 'Server Bot Offline. Jalankan server bot untuk melihat perangkat terhubung.'}
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        sessions.forEach(s => {
            const isConnected = s.status === 'open';
            const badgeClass = isConnected ? 'bg-label-success' : 'bg-label-warning';
            html += `
                <tr data-session-id="${s.id}">
                    <td><strong>${s.id}</strong></td>
                    <td>${s.user?.id || 'N/A'}</td>
                    <td><span class="badge ${badgeClass}">${s.status.toUpperCase()}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            ${!isConnected && s.qr ? `<button class="btn btn-sm btn-info" onclick="showQrModal('${s.qr}')" title="Scan QR"><i class="bx bx-qr-scan"></i></button>` : ''}
                            ${isConnected ? `<button class="btn btn-sm btn-warning stop-session" data-id="${s.id}" title="Disconnect"><i class="bx bx-log-out"></i></button>` : ''}
                            <button class="btn btn-sm btn-danger delete-session" data-id="${s.id}" title="Hapus"><i class="bx bx-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    function startPollingSession(targetId) {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(() => {
            axios.get("{{ route('whatsapp.index') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => {
                    const sessions = res.data.sessions || [];
                    const session = sessions.find(s => s.id === targetId);
                    if (session && session.status === 'open') {
                        clearInterval(pollingInterval);
                        Swal.fire({ icon: 'success', title: 'Berhasil Terhubung!', text: 'Perangkat ' + targetId + ' aktif.', timer: 2000 });
                        refreshSessions();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addSessionModal'));
                        if (modal) modal.hide();
                    }
                });
        }, 3000);
    }

    function showQrModal(qrData) {
        const modal = new bootstrap.Modal(document.getElementById('addSessionModal'));
        document.getElementById('newSessionId').value = "Active Session";
        document.getElementById('resultArea').style.display = 'block';
        document.getElementById('qrContainer').style.display = 'block';
        document.getElementById('pairingContainer').style.display = 'none';
        
        new QRious({
            element: document.getElementById('qrCanvas'),
            value: qrData,
            size: 200
        });
        modal.show();
    }
</script>
@endsection

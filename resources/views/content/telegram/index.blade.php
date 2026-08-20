@extends('layouts/contentNavbarLayout')

@section('title', 'Telegram Bot Manager')

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Sistem /</span> Telegram Bot Manager
</h4>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Status Banner -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-md flex-shrink-0">
                <span class="avatar-initial rounded bg-label-info">
                    <i class="bx bxl-telegram fs-3"></i>
                </span>
            </div>
            <div>
                <h5 class="mb-1 text-dark fw-bold">Pengaturan Bot Telegram Dinamis & Watchdog</h5>
                <p class="text-muted mb-0 small">Cukup masukkan Bot Token & Admin Group/Chat ID untuk notifikasi otomatis, pembayaran 1-click, & ticketing.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Form Configuration -->
    <div class="col-lg-7 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bx bx-cog text-info me-2"></i> Konfigurasi Telegram Bot API
                </h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('telegram.update') }}" method="POST">
                    @csrf

                    <!-- Status Toggle -->
                    <div class="form-check form-switch mb-3 bg-light p-3 rounded border">
                        <input class="form-check-input" type="checkbox" id="enabled" name="enabled" {{ ($setting && $setting->enabled) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark" for="enabled">
                            Aktifkan Layanan Bot Telegram
                        </label>
                        <div class="form-text">Jika diaktifkan, Bot Telegram akan merespons chat pelanggan & mengirimkan notifikasi gangguan secara otomatis.</div>
                    </div>

                    <!-- Bot Token -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="bot_token">Bot Token (dari @BotFather) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-key"></i></span>
                            <input type="text" class="form-control" id="bot_token" name="bot_token" value="{{ $setting->bot_token ?? '' }}" placeholder="Contoh: 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
                        </div>
                        <div class="form-text">Dapatkan Token ini dengan membuat Bot baru di Telegram via username <code>@BotFather</code>.</div>
                    </div>

                    <!-- Admin Chat ID -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="chat_id">Admin Group / Chat ID <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-group"></i></span>
                            <input type="text" class="form-control" id="chat_id" name="chat_id" value="{{ $setting->chat_id ?? '' }}" placeholder="Contoh: -1001234567890 atau 12345678">
                        </div>
                        <div class="form-text">ID Grup Telegram atau ID Chat Admin untuk menerima alert Watchdog & Tiket Gangguan Baru.</div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3"><i class="bx bx-bell me-1 text-primary"></i> Pengaturan Notifikasi Watchdog:</h6>

                    <!-- Notify ONU Offline -->
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="notify_onu_offline" name="notify_onu_offline" {{ ($setting && $setting->notify_onu_offline) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notify_onu_offline">
                            Notifikasi ONT / Pelanggan Offline (Watchdog Alert)
                        </label>
                    </div>

                    <!-- Notify ODP Full -->
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="notify_odp_full" name="notify_odp_full" {{ ($setting && $setting->notify_odp_full) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notify_odp_full">
                            Notifikasi Kapasitas ODP Penuh / Warning
                        </label>
                    </div>

                    <!-- Notify Kabel Offline -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_kabel_offline" name="notify_kabel_offline" {{ ($setting && $setting->notify_kabel_offline) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notify_kabel_offline">
                            Notifikasi Kabel FO Putus / Redaman Tinggi
                        </label>
                    </div>

                    <!-- Offline Threshold -->
                    <div class="mb-3">
                        <label class="form-label" for="offline_threshold_minutes">Toleransi Waktu Offline (Menit)</label>
                        <input type="number" class="form-control" id="offline_threshold_minutes" name="offline_threshold_minutes" value="{{ $setting->offline_threshold_minutes ?? 5 }}" min="1">
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Webhook & Action Controls -->
    <div class="col-lg-5 mb-4">
        <!-- Webhook Integration Card -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bx bx-link text-success me-2"></i> Webhook Server & Actions
                </h5>
            </div>
            <div class="card-body pt-4">
                <p class="small text-muted mb-3">Sistem secara otomatis menyediakan URL Webhook publik untuk memproses pesan Telegram secara realtime.</p>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Public Webhook URL:</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-light" value="{{ $webhookUrl }}" readonly>
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $webhookUrl }}')" type="button" title="Salin URL">
                            <i class="bx bx-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <form action="{{ route('telegram.webhook.set') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bx bx-sync me-1"></i> Hubungkan Webhook Otomatis (Production HTTPS)
                        </button>
                    </form>

                    <form action="{{ route('telegram.poll-once') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning text-dark w-100 fw-bold">
                            <i class="bx bx-refresh me-1"></i> Sinkronkan & Balas Pesan Masuk (Localhost)
                        </button>
                    </form>

                    <form action="{{ route('telegram.test') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bx bx-paper-plane me-1"></i> Tes Kirim Pesan Telegram
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Instructions Card -->
        <div class="card border-0 shadow-sm bg-label-secondary">
            <div class="card-body">
                <h6 class="fw-bold text-dark mb-2">
                    <i class="bx bx-info-circle me-1"></i> Cara Menghubungkan Bot Telegram:
                </h6>
                <ol class="ps-3 mb-0 small text-muted">
                    <li class="mb-1">Buka Telegram dan cari akun <b>@BotFather</b>.</li>
                    <li class="mb-1">Kirim perintah <code>/newbot</code> lalu ikuti petunjuk hingga mendapat <b>HTTP API Token</b>.</li>
                    <li class="mb-1">Salin Token tersebut dan tempel pada kolom <b>Bot Token</b> di sebelah kiri.</li>
                    <li class="mb-1">Masukkan grup Telegram Anda, lalu masukkan bot <b>@myidbot</b> untuk melihat <b>Chat ID</b> grup (-100xxx).</li>
                    <li class="mb-1">Klik tombol <b>Hubungkan Webhook Otomatis</b> di atas!</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts/contentNavbarLayout')

@section('title', 'Pengaturan Pembayaran')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Pengaturan /</span> Pembayaran</h4>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <h5 class="card-header">Konfigurasi Metode Pembayaran</h5>
            <div class="card-body">
                <form action="{{ route('settings.payment.update') }}" method="POST">
                    @csrf
                    <div class="mb-3 border-bottom pb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="gateway_enabled" id="gw_switch" {{ \App\Models\Setting::get('payment_gateway_enabled', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="gw_switch">Aktifkan Payment Gateway (Midtrans)</label>
                        </div>
                        <small class="text-muted d-block mb-3">Jika dinonaktifkan, tombol bayar otomatis di sisi pelanggan akan hilang.</small>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Midtrans Merchant ID</label>
                                <input type="text" name="midtrans_merchant_id" class="form-control" value="{{ \App\Models\Setting::get('midtrans_merchant_id', 'G119447430') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Midtrans Client Key</label>
                                <input type="text" name="midtrans_client_key" class="form-control" value="{{ \App\Models\Setting::get('midtrans_client_key', 'SB-Mid-client-2PTBae_2bJth_7-g') }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Midtrans Server Key</label>
                                <input type="password" name="midtrans_server_key" class="form-control" value="{{ \App\Models\Setting::get('midtrans_server_key', 'SB-Mid-server-y7UffH6OadAjC36h_gKcNSLV') }}">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="midtrans_is_production" id="prod_switch" {{ \App\Models\Setting::get('midtrans_is_production', '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="prod_switch">Mode Produksi (LIVE)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Biaya Admin (Payment Gateway)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="payment_fee" class="form-control" value="{{ \App\Models\Setting::get('payment_fee', '2500') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 border-bottom pb-3 mt-4">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="manual_enabled" id="manual_switch" {{ \App\Models\Setting::get('manual_payment_enabled', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="manual_switch">Aktifkan Pembayaran Manual (Transfer/Cash)</label>
                        </div>
                        <small class="text-muted d-block mb-3">Jika aktif, pelanggan dapat mengunggah bukti bayar secara manual.</small>

                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran Manual (Pisahkan dengan Koma)</label>
                            <input type="text" name="manual_methods" class="form-control" value="{{ \App\Models\Setting::get('manual_payment_methods', 'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay') }}">
                            <small class="text-muted">Contoh: Transfer Bank, Bayar Tunai, Titip Teknisi</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Informasi Rekening / Instruksi Bayar Manual</label>
                            <textarea name="bank_info" class="form-control" rows="4">{{ \App\Models\Setting::get('manual_bank_info', '') }}</textarea>
                            <small class="text-muted">Muncul saat pelanggan memilih metode pembayaran manual.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <h5 class="card-header">⚙️ Otomatisasi Tagihan & Isolir</h5>
            <div class="card-body">
                <form action="{{ route('settings.payment.update') }}" method="POST">
                    @csrf

                    {{-- Timeline Info --}}
                    @php
                        $genDate    = \App\Models\Setting::get('billing_generate_date', '1');
                        $startDate  = \App\Models\Setting::get('billing_start_date', '1');
                        $remindDate = \App\Models\Setting::get('billing_reminder_date', '5');
                        $isolirDate = \App\Models\Setting::get('billing_isolir_date', '10');
                        $isolirHour = \App\Models\Setting::get('billing_isolir_hour', '12');
                    @endphp
                    <div class="alert alert-light border mb-4 p-3" style="font-size:0.85rem;">
                        <strong>📅 Alur Billing Bulan Ini:</strong><br>
                        🔔 Tgl <strong>{{ $genDate }}</strong> → Tagihan dibuat & notif WA terkirim<br>
                        💳 Tgl <strong>{{ $startDate }}</strong> → Mulai bisa bayar (periode pembayaran dibuka)<br>
                        📢 Tgl <strong>{{ $remindDate }}</strong> → Pengingat (Reminder) tagihan belum bayar dikirim via WA<br>
                        ⚠️ Tgl <strong>{{ $isolirDate }}</strong> Jam <strong>{{ sprintf('%02d:00', $isolirHour) }}</strong> → Jatuh tempo, internet dimatikan jika belum bayar
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">📅 Tanggal Buat Tagihan</label>
                            <select name="billing_generate_date" class="form-select">
                                @for($i=1; $i<=28; $i++)
                                    <option value="{{ $i }}" {{ $genDate == $i ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Tagihan otomatis dibuat & notif WA dikirim.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">💳 Tanggal Mulai Bayar</label>
                            <select name="billing_start_date" class="form-select">
                                @for($i=1; $i<=28; $i++)
                                    <option value="{{ $i }}" {{ $startDate == $i ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Periode pembayaran mulai dibuka.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">📢 Tanggal Reminder WA</label>
                            <select name="billing_reminder_date" class="form-select">
                                @for($i=1; $i<=28; $i++)
                                    <option value="{{ $i }}" {{ $remindDate == $i ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Notif WA bagi yang belum bayar.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">⚠️ Tanggal Jatuh Tempo (Isolir)</label>
                            <select name="billing_isolir_date" class="form-select">
                                @for($i=1; $i<=28; $i++)
                                    <option value="{{ $i }}" {{ $isolirDate == $i ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Internet mati jika belum bayar sampai tanggal ini.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">🕐 Jam Eksekusi Isolir</label>
                            <select name="billing_isolir_hour" class="form-select">
                                @for($i=0; $i<=23; $i++)
                                    <option value="{{ $i }}" {{ $isolirHour == $i ? 'selected' : '' }}>Jam {{ sprintf('%02d:00', $i) }} WIB</option>
                                @endfor
                            </select>
                            <small class="text-muted">Waktu eksekusi pemutusan internet.</small>
                        </div>
                        
                        <div class="col-md-12 mt-4 border-top pt-3">
                            <h6 class="mb-3">Konfigurasi Job Otomatis (Cron)</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="auto_generate_enabled" id="auto_gen" {{ \App\Models\Setting::get('billing_auto_generate_enabled', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="auto_gen">Aktifkan Pembuatan Tagihan Otomatis</label>
                            </div>
                            <small class="text-muted d-block mb-3">Jika dimatikan, tagihan bulan baru tidak akan dibuat otomatis.</small>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="reminder_enabled" id="auto_remind" {{ \App\Models\Setting::get('billing_reminder_enabled', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="auto_remind">Aktifkan Reminder Tagihan Otomatis</label>
                            </div>
                            <small class="text-muted d-block mb-3">Jika dimatikan, notif reminder WA tidak akan terkirim.</small>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="auto_isolir_enabled" id="auto_isolir" {{ \App\Models\Setting::get('billing_auto_isolir_enabled', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="auto_isolir">Aktifkan Isolir Otomatis (On/Off Mikrotik)</label>
                            </div>
                            <small class="text-muted d-block mb-3">Jika dimatikan, internet tidak akan dimatikan otomatis meski lewat jatuh tempo.</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-info mt-3">💾 Simpan Pengaturan Otomatisasi</button>
                </form>

                <hr class="my-4">
                
                <h6 class="mb-3">🚀 Eksekusi Manual (Force Sync)</h6>
                <div class="d-flex flex-wrap gap-2">
                    <form action="{{ route('settings.billing.isolir', ['type' => 'reminder']) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-sm">
                            <i class="bx bx-bell me-1"></i> Kirim Reminder WA (Tagihan Unpaid)
                        </button>
                    </form>
                    
                    <form action="{{ route('settings.billing.isolir', ['type' => 'disable']) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bx bx-power-off me-1"></i> Jalankan Isolir (Matikan Unpaid)
                        </button>
                    </form>
                    
                    <form action="{{ route('settings.billing.isolir', ['type' => 'enable']) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">
                            <i class="bx bx-play-circle me-1"></i> Jalankan Aktivasi (Nyalakan Paid)
                        </button>
                    </form>

                    <form action="{{ route('settings.billing.isolir', ['type' => 'all']) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-sync me-1"></i> Sinkronisasi Keduanya
                        </button>
                    </form>
                </div>
                <p class="text-muted small mt-2 mb-0">Gunakan tombol di atas untuk memaksa sistem mengecek status pembayaran dan mengupdate akses Mikrotik saat ini juga.</p>
            </div>
        </div>

        <div class="card mb-4 border border-success shadow-sm">
            <h5 class="card-header d-flex align-items-center">
                <i class="bx bxl-whatsapp me-2 fs-4 text-success"></i> Manajemen Notifikasi WhatsApp (Global)
            </h5>
            <div class="card-body">
                <p class="small text-muted mb-3">Gunakan sakelar di bawah ini untuk mematikan atau mengaktifkan pengiriman semua notifikasi WhatsApp otomatis ke nomor pelanggan tanpa menghapus nomor HP mereka dari database.</p>
                
                <form action="{{ route('settings.payment.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="wa_billing_switch_present" value="1">
                    
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                        <div>
                            <h6 class="mb-1">Status Pengiriman WhatsApp</h6>
                            <span class="text-muted small">Notifikasi tagihan baru, reminder isolir, dan struk lunas.</span>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="wa_billing_notification_enabled" id="wa_billing_card_switch" onchange="this.form.submit()" {{ \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold {{ \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1' ? 'text-success' : 'text-danger' }}" for="wa_billing_card_switch">
                                {{ \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1' ? 'AKTIF (Mengirim WA)' : 'NON-AKTIF (Mati/Tidak Mengirim)' }}
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <h5 class="card-header">Midtrans Notification URLs</h5>
            <div class="card-body">
                <p class="text-muted small mb-3">Salin URL di bawah ini dan masukkan ke Dashboard Midtrans <strong>(Settings > Configuration)</strong>.</p>
                
                @if(str_contains(url('/'), '127.0.0.1') || str_contains(url('/'), 'localhost'))
                <div class="alert alert-danger p-2 mb-3" style="font-size: 0.75rem;">
                    <i class="bx bx-error-circle me-1"></i> <strong>PENTING:</strong> Midtrans tidak bisa mengirim notifikasi ke <code>localhost/127.0.0.1</code>. Gunakan <strong>Ngrok</strong> agar mendapatkan URL publik (https).
                </div>
                @endif
                
                <div class="mb-3">
                    <label class="form-label small mb-0">Payment Notification URL</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control bg-light" readonly value="{{ url('payment/callback') }}">
                        <button class="btn btn-outline-secondary copy-btn" type="button"><i class="bx bx-copy"></i></button>
                    </div>
                    <small class="text-danger" style="font-size: 0.7rem;">*Hapus http:// jika di dashboard Midtrans sudah ada</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small mb-0">Finish Redirect URL</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control bg-light" readonly value="{{ url('billing') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h5 class="card-header">Status Gateway</h5>
            <div class="card-body">
                @php
                    $isConfigured = !empty(\App\Models\Setting::get('midtrans_server_key')) || !empty(config('services.midtrans.server_key'));
                @endphp
                
                @if($isConfigured)
                    <div class="alert alert-success">
                        <i class="bx bx-check-circle me-1"></i> Midtrans Terkonfigurasi
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bx bx-error me-1"></i> Midtrans Belum Dikonfigurasi
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

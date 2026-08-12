@extends('layouts/contentNavbarLayout')

@section('title', 'Pengaturan Alat Fingerprint')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius: 16px;">
            <div class="position-absolute end-0 bottom-0 opacity-10" style="font-size: 15rem; transform: translate(10%, 20%); line-height: 1;">
                <i class="bx bx-cog"></i>
            </div>
            <div class="card-body p-4 p-md-5">
                <h4 class="card-title text-white mb-2 fw-bold"><i class="bx bx-cog me-2"></i> PENGATURAN ALAT FINGERPRINT</h4>
                <p class="mb-0 text-white-50">Konfigurasi mesin absensi Solution X105, batas toleransi waktu, dan jeda scan minimal.</p>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center" style="border-radius: 12px;">
    <i class="bx bx-check-circle me-2 fs-4"></i> {{ session('success') }}
</div>
@endif

<form action="{{ route('absensi.settings.store') }}" method="POST">
    @csrf
    <div class="row g-4">
        <!-- Settings Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-primary mb-4 d-flex align-items-center">
                        <i class="bx bx-fingerprint me-2 fs-4"></i> DAFTAR MESIN FINGERPRINT SOLUTIONS X105
                    </h5>
                    
                    <div id="device-container">
                        @forelse($devices as $index => $device)
                        <div class="device-row mb-4 border rounded-3 p-4 bg-light bg-opacity-50 position-relative animate__animated animate__fadeIn" data-index="{{ $index }}" style="border-radius: 12px;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-indigo mb-0">
                                    <i class="bx bx-devices me-2"></i> PERANGKAT SOLUTIONS X105 #<span class="device-number">{{ $index + 1 }}</span>
                                </h6>
                                <div class="d-flex align-items-center gap-2">
                                    @if(isset($device['last_seen']) && $device['last_seen'])
                                    <span class="badge bg-label-success rounded-pill px-3 py-1 fw-bold">
                                        <i class="bx bx-wifi me-1"></i> ONLINE: {{ \Carbon\Carbon::parse($device['last_seen'])->diffForHumans() }}
                                    </span>
                                    @else
                                    <span class="badge bg-label-secondary rounded-pill px-3 py-1 fw-bold">
                                        <i class="bx bx-wifi-off me-1"></i> BELUM TERHUBUNG
                                    </span>
                                    @endif
                                    
                                    @if($index > 0)
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 remove-device">
                                        <i class="bx bx-trash me-1"></i> Hapus
                                    </button>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">NAMA ALAT / LOKASI</label>
                                    <input type="text" name="devices[{{ $index }}][name]" class="form-control" value="{{ $device['name'] ?? '' }}" placeholder="Contoh: Kantor Utama" required style="border-radius: 8px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">SERIAL NUMBER (SN) SOLUTIONS X105</label>
                                    <input type="text" name="devices[{{ $index }}][sn]" class="form-control" value="{{ $device['sn'] ?? '' }}" placeholder="Contoh: CS105XXXXXXXX" required style="border-radius: 8px;">
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5" id="no-device-alert">
                            <i class="bx bx-devices fs-1 text-muted mb-3 opacity-50"></i>
                            <p class="text-muted">Belum ada perangkat sidik jari yang ditambahkan.</p>
                        </div>
                        @endforelse
                    </div>

                    <div class="mt-3">
                        <button type="button" id="add-device" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                            <i class="bx bx-plus-circle me-1"></i> Tambah Perangkat Baru
                        </button>
                    </div>
                </div>
            </div>

            <!-- ADMS Connection Instructions -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bx bx-info-circle me-2 text-warning fs-4"></i> CARA PENGATURAN KONEKSI MESIN (SUPPORT DOMAIN & IP)</h5>
                    <p class="text-muted small">Untuk menghubungkan mesin **Solutions X105** ke aplikasi ini, masuk ke menu **Komunikasi (Communication) -> ADMS / Cloud Server Settings** di dalam menu mesin sidik jari, lalu atur sebagai berikut:</p>
                    
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3" style="border-radius: 10px;">
                                <div class="small fw-bold text-muted text-uppercase mb-1">Server Address (Domain)</div>
                                <div class="fw-bold text-primary fs-5">{{ request()->getHost() }}</div>
                                <small class="text-muted">Gunakan opsi ini jika mesin terhubung ke internet menggunakan nama domain.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3" style="border-radius: 10px;">
                                <div class="small fw-bold text-muted text-uppercase mb-1">Server Address (IP Lokal/Server)</div>
                                <div class="fw-bold text-success fs-5">{{ gethostbyname(request()->getHost()) ?: '127.0.0.1' }}</div>
                                <small class="text-muted">Gunakan opsi ini jika mesin berada dalam satu jaringan IP yang sama.</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3" style="border-radius: 10px;">
                                <div class="small fw-bold text-muted text-uppercase mb-1">Server Port</div>
                                <div class="fw-bold fs-5 text-dark">{{ request()->getPort() == 443 ? '443' : '80' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3" style="border-radius: 10px;">
                                <div class="small fw-bold text-muted text-uppercase mb-1">HTTPS (SSL)</div>
                                <div class="fw-bold fs-5 text-indigo">{{ request()->isSecure() ? 'ON (Enable)' : 'OFF (Disable)' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded-3" style="border-radius: 10px;">
                                <div class="small fw-bold text-muted text-uppercase mb-1">Push Path Endpoint</div>
                                <div class="fw-bold fs-6 text-dark text-truncate">/iclock/cdata</div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info border-0 rounded-3 small mb-0 mt-4 d-flex align-items-start" style="border-radius: 10px;">
                        <i class="bx bx-bulb me-2 fs-5 mt-1"></i>
                        <div>
                            <strong>Tips Penting:</strong> Protokol ADMS pada Solutions X105 secara otomatis mengenali port `80` untuk HTTP dan `443` untuk HTTPS. Pastikan status **Cloud Server Connected** di layar mesin berubah menjadi ikon terhubung berwarna hijau.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Config Column -->
        <div class="col-lg-4">
            <!-- Presence Rules -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="bx bx-time me-2 text-primary"></i> JAM OPERASIONAL</h5>
                </div>
                <div class="card-body px-4 pb-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">BATAS JAM MASUK (CHECK-IN)</label>
                        <input type="time" name="absensi_batas_masuk" class="form-control" value="{{ substr($batasMasuk, 0, 5) }}" required style="border-radius: 8px;">
                        <small class="text-muted d-block mt-1">Scan setelah jam ini akan otomatis ditandai sebagai **Terlambat**.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">BATAS JAM PULANG (CHECK-OUT)</label>
                        <input type="time" name="absensi_batas_pulang" class="form-control" value="{{ substr($batasPulang, 0, 5) }}" required style="border-radius: 8px;">
                        <small class="text-muted d-block mt-1">Scan sebelum jam ini akan otomatis ditandai sebagai **Pulang Lebih Awal**.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">JEDA SCAN MINIMAL</label>
                        <div class="input-group">
                            <input type="number" name="absensi_min_interval" class="form-control" value="{{ $minInterval }}" min="0" required style="border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                            <span class="input-group-text bg-light text-muted" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;">Menit</span>
                        </div>
                        <small class="text-muted d-block mt-1">Mencegah dobel scan masuk/pulang yang tidak sengaja dalam rentang menit ini.</small>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Recap Settings -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="bx bxl-whatsapp me-2 text-success"></i> REKAP BULANAN WA</h5>
                </div>
                <div class="card-body px-4 pb-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">NO. WHATSAPP PENERIMA REKAP</label>
                        <input type="text" name="wa_rekap_absensi_target" class="form-control" value="{{ $waRekapTarget }}" required style="border-radius: 8px;" placeholder="Contoh: +6282187827382">
                        <small class="text-muted d-block mt-1">Nomor WhatsApp tujuan untuk pengiriman otomatis PDF Rekap Absensi setiap tanggal 10.</small>
                    </div>
                </div>
            </div>

            <!-- Submit Button Card -->
            <div class="card border-0 shadow-sm text-white overflow-hidden" style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-white mb-2">Simpan Konfigurasi</h5>
                    <p class="small text-white-50 mb-4">Pastikan Serial Number mesin sidik jari dan jam operasional sudah benar sebelum menyimpan.</p>
                    <button type="submit" class="btn btn-light w-100 rounded-pill py-2 fw-bold text-primary shadow-sm" style="border-radius: 30px;">
                        <i class="bx bx-save me-1"></i> SIMPAN SEMUA
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="device-template">
    <div class="device-row mb-4 border rounded-3 p-4 bg-light bg-opacity-50 position-relative animate__animated animate__fadeInUp" data-index="__INDEX__" style="border-radius: 12px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-indigo mb-0">
                <i class="bx bx-devices me-2"></i> PERANGKAT SOLUTIONS X105 #<span class="device-number">__NUMBER__</span>
            </h6>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 remove-device">
                <i class="bx bx-trash me-1"></i> Hapus
            </button>
        </div>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">NAMA ALAT / LOKASI</label>
                <input type="text" name="devices[__INDEX__][name]" class="form-control" placeholder="Contoh: Kantor Utama" required style="border-radius: 8px;">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">SERIAL NUMBER (SN) SOLUTIONS X105</label>
                <input type="text" name="devices[__INDEX__][sn]" class="form-control" placeholder="Contoh: CS105XXXXXXXX" required style="border-radius: 8px;">
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('device-container');
    const addButton = document.getElementById('add-device');
    const noDeviceAlert = document.getElementById('no-device-alert');
    const template = document.getElementById('device-template').innerHTML;

    addButton.addEventListener('click', addDeviceRow);

    function addDeviceRow() {
        if (noDeviceAlert) {
            noDeviceAlert.classList.add('d-none');
        }
        
        const index = container.querySelectorAll('.device-row').length;
        let html = template.replace(/__INDEX__/g, index);
        html = html.replace(/__NUMBER__/g, index + 1);
        
        const div = document.createElement('div');
        div.innerHTML = html;
        container.appendChild(div.firstElementChild);
    }

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-device') || e.target.closest('.remove-device')) {
            const row = e.target.closest('.device-row');
            row.remove();
            reindexRows();
        }
    });

    function reindexRows() {
        const rows = container.querySelectorAll('.device-row');
        if (rows.length === 0 && noDeviceAlert) {
            noDeviceAlert.classList.remove('d-none');
        }
        
        rows.forEach((row, idx) => {
            row.setAttribute('data-index', idx);
            row.querySelector('.device-number').textContent = idx + 1;
            
            row.querySelectorAll('input').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/devices\[\d+\]/, `devices[${idx}]`));
                }
            });
        });
    }
});
</script>

<style>
.text-indigo {
    color: #4f46e5 !important;
}
.border-dashed {
    border-style: dashed !important;
}
</style>
@endsection

@extends('layouts.app')

@section('title', 'Konfigurasi Sistem')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 24px;">
            <div class="card-body p-0">
                <div class="bg-pmu p-5 text-white">
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-gears me-2"></i> PENGATURAN ALAT</h3>
                    <p class="opacity-75 mb-0">Konfigurasi alat Fingerprint Solutions X100-C dan Gateway WhatsApp Fonnte.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success border-0 shadow-sm rounded-4 p-4 mb-4">
    <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
</div>
@endif

<form action="{{ route('settings.fingerprint.store') }}" method="POST">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-body p-4 p-lg-5">
                    <div id="device-container">
                        @forelse($devices as $index => $device)
                        <div class="device-row mb-5 border rounded-4 p-4 bg-light bg-opacity-50 position-relative animate__animated animate__fadeIn" data-index="{{ $index }}">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold text-pmu mb-0">
                                    <i class="fa-solid fa-fingerprint me-2"></i> PERANGKAT SOLUTIONS X100-C #<span class="device-number">{{ $index + 1 }}</span>
                                </h6>
                                <div class="d-flex align-items-center gap-3">
                                    @if(isset($device['last_seen']))
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 small fw-bold animate__animated animate__pulse animate__infinite">
                                        <i class="fa-solid fa-cloud-check me-2"></i> ONLINE: {{ \Carbon\Carbon::parse($device['last_seen'])->diffForHumans() }}
                                    </span>
                                    @else
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 small fw-bold">
                                        <i class="fa-solid fa-cloud-slash me-2"></i> BELUM TERHUBUNG
                                    </span>
                                    @endif
                                    
                                    @if($index > 0)
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 remove-device">
                                        <i class="fa-solid fa-trash-can me-1"></i> HAPUS
                                    </button>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">NAMA ALAT / LOKASI</label>
                                    <input type="text" name="devices[{{ $index }}][name]" class="form-control" value="{{ $device['name'] }}" placeholder="Contoh: Gedung Timur" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">SERIAL NUMBER (SN)</label>
                                    <input type="text" name="devices[{{ $index }}][sn]" class="form-control" value="{{ $device['sn'] }}" placeholder="Ex: NJF7254700390" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-muted">API PUSH URL (Informasi Saja)</label>
                                    <input type="url" name="devices[{{ $index }}][url]" class="form-control" value="{{ $device['url'] }}" readonly style="background-color: #f8f9fa;">
                                    <small class="text-muted" style="font-size: 0.7rem;">Url ini adalah endpoint internal aplikasi untuk menerima data dari mesin.</small>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-4 border-dashed rounded-4 bg-white shadow-sm border-primary border-opacity-25">
                                <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-circle-info me-2"></i> Instruksi Setting Mesin X100-C (ADMS):</h6>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="small fw-bold text-muted text-uppercase mb-1">Server Address</div>
                                            <div class="fw-bold fs-5">{{ request()->getHost() }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="small fw-bold text-muted text-uppercase mb-1">Server Port</div>
                                            <div class="fw-bold fs-5">{{ request()->getPort() == 443 ? '443' : '80' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="small fw-bold text-muted text-uppercase mb-1">HTTPS</div>
                                            <div class="fw-bold fs-5 text-success">{{ request()->isSecure() ? 'ON' : 'OFF' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <p class="small text-muted mb-0 mt-3">Pastikan mesin terhubung ke internet. Aktifkan **ADMS/Cloud Server** di menu Komunikasi mesin.</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <i class="fa-solid fa-plug-circle-exclamation fa-3x text-muted mb-3 opacity-25"></i>
                            <p class="text-muted">Belum ada alat yang dikonfigurasi. Klik tombol di bawah untuk menambah.</p>
                        </div>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        <button type="button" id="add-device" class="btn btn-outline-success rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-plus-circle me-2"></i> TAMBAH PERANGKAT BARU
                        </button>
                    </div>
                </div>
            </div>
        </div>

            <!-- Presence Logic Settings -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
                <div class="card-header bg-primary bg-opacity-10 py-4 px-4 border-0">
                    <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i> LOGIKA KEHADIRAN</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Jeda Scan Minimal (Menit)</label>
                        <div class="input-group">
                            <input type="number" name="absensi_min_interval" class="form-control" value="{{ $minInterval }}" min="0">
                            <span class="input-group-text bg-light text-muted small">Menit</span>
                        </div>
                    </div>
                    <div class="alert alert-warning border-0 rounded-3 small mb-0">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> 
                        Jarak waktu minimal antara scan **Masuk** ke **Pulang** agar tidak terhitung double scan tidak sengaja.
                    </div>
                </div>
            </div>

            <!-- Fonnte Settings -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
                <div class="card-header bg-success bg-opacity-10 py-4 px-4 border-0">
                    <h6 class="fw-bold text-success mb-0"><i class="fa-brands fa-whatsapp me-2"></i> FONNTE WA GATEWAY</h6>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-4">Gunakan Fonnte untuk mengirim notifikasi WhatsApp otomatis saat akun baru dibuat.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">API TOKEN</label>
                        <input type="password" name="fonnte_token" class="form-control" value="{{ $fonnteToken }}" placeholder="Masukkan API Token Fonnte">
                    </div>
                    <div class="alert alert-info border-0 rounded-3 small">
                        <i class="fa-solid fa-circle-info me-2"></i> Token dapat diambil di dashboard Fonnte Anda.
                    </div>
                </div>
            </div>

            <!-- Submit Button Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-pmu text-white mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Simpan Perubahan</h6>
                    <p class="small opacity-75 mb-4">Pastikan data yang Anda masukkan sudah benar sebelum menyimpan.</p>
                    <button type="submit" class="btn btn-light w-100 rounded-pill py-3 fw-bold shadow-sm">
                         SIMPAN SEMUA PENGATURAN
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="device-template">
    <div class="device-row mb-5 border rounded-4 p-4 bg-light bg-opacity-50 position-relative animate__animated animate__fadeInUp" data-index="__INDEX__">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-bold text-pmu mb-0">
                <i class="fa-solid fa-fingerprint me-2"></i> PERANGKAT SOLUTIONS X100-C #<span class="device-number">__NUMBER__</span>
            </h6>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 remove-device">
                <i class="fa-solid fa-trash-can me-1"></i> HAPUS
            </button>
        </div>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">NAMA ALAT / LOKASI</label>
                <input type="text" name="devices[__INDEX__][name]" class="form-control" placeholder="Contoh: X100-C Kantor" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">SERIAL NUMBER (SN)</label>
                <input type="text" name="devices[__INDEX__][sn]" class="form-control" placeholder="Ex: NJF7254700390" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold small text-muted">API PUSH URL</label>
                <input type="url" name="devices[__INDEX__][url]" class="form-control" value="https://{{ request()->getHost() }}/iclock/cdata" readonly style="background-color: #f8f9fa;">
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('device-container');
    const addButton = document.getElementById('add-device');
    const template = document.getElementById('device-template').innerHTML;

    addButton.addEventListener('click', addDeviceRow);

    function addDeviceRow() {
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
@endsection

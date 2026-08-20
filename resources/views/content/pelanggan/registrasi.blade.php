@extends('layouts/contentNavbarLayout')

@section('title', 'Registrasi Pasang WiFi Baru')

@section('page-style')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map-picker {
        height: 300px;
        width: 100%;
        border-radius: 12px;
        border: 1px solid #d9ade5;
    }
</style>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-user-plus text-primary me-2"></i> Registrasi Pasang WiFi Baru
            </h4>
            <p class="text-muted mb-0">Input pendaftaran baru pelanggan atau kelola calon pelanggan yang mendaftar online.</p>
        </div>
        <div>
            <a href="{{ route('public.register') }}" target="_blank" class="btn btn-outline-primary rounded-pill shadow-sm">
                <i class="bx bx-link-external me-1"></i> Buka Form Publik (Untuk Customer)
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
    <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
    <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Nav Tabs -->
<ul class="nav nav-pills mb-3" role="tablist">
    <li class="nav-item">
        <button type="button" class="nav-link active fw-bold" role="tab" data-bs-toggle="tab" data-bs-target="#tab-form-registrasi">
            <i class="bx bx-edit-alt me-1"></i> Form Input Registrasi Baru
        </button>
    </li>
    <li class="nav-item">
        <button type="button" class="nav-link fw-bold" role="tab" data-bs-toggle="tab" data-bs-target="#tab-daftar-pendaftar">
            <i class="bx bx-list-ol me-1"></i> Data Pendaftaran Online <span class="badge bg-primary ms-1">{{ count($registrations) }}</span>
        </button>
    </li>
</ul>

<div class="tab-content p-0">
    <!-- TAB 1: FORM INPUT REGISTRASI BARU -->
    <div class="tab-pane fade show active" id="tab-form-registrasi" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <i class="bx bx-user-plus me-2 fs-5"></i>
                <h5 class="card-title text-white mb-0">Form Pendaftaran Pasang WiFi Baru</h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('public.register.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nama Lengkap Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Budi Santoso" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">No. WhatsApp Aktif <span class="text-danger">*</span></label>
                            <input type="text" name="no_wa" class="form-control" placeholder="Contoh: 628123456789" required>
                            <small class="text-muted">Gunakan format internasional diawali 628xxx</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Pilih Paket Layanan WiFi <span class="text-danger">*</span></label>
                            <select name="paket" class="form-select" required>
                                <option value="">-- Pilih Paket Kecepatan --</option>
                                @foreach($packages as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Foto Depan Rumah (Opsional)</label>
                            <input type="file" name="foto_rumah" class="form-control" accept="image/*">
                            <small class="text-muted">Upload foto rumah untuk mempermudah survei teknisi.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat Rumah Lengkap <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Nama jalan, RT/RW, Dusun, Desa, Kecamatan..." required></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold">Latitude Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="latitude" id="latitude" class="form-control" value="-7.1590" required readonly>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold">Longitude Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="longitude" id="longitude" class="form-control" value="112.6510" required readonly>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold mb-2">Pilih Titik Lokasi Pada Peta (Klik / Geser Pin)</label>
                        <div id="map-picker"></div>
                        <small class="text-muted mt-1 d-block">Gunakan tombol 'Gunakan Lokasi Saya' atau geser pin ke posisi tepat rumah pelanggan.</small>
                        <button type="button" onclick="getLocation()" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bx bx-target-lock me-1"></i> Gunakan Lokasi GPS Saya
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm py-2">
                        <i class="bx bx-paper-plane me-1"></i> Simpan & Daftarkan Pelanggan Baru
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 2: DATA PENDAFTARAN ONLINE MANDIRI -->
    <div class="tab-pane fade" id="tab-daftar-pendaftar" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <div>
                    <h5 class="mb-0 text-dark fw-bold">
                        <i class="bx bx-list-check me-1 text-primary"></i> Daftar Pendaftaran Online Mandiri
                    </h5>
                    <small class="text-muted">Kelola calon pelanggan yang mendaftar via form online</small>
                </div>
                <div>
                    <form action="{{ route('pelanggan.registrasi.index') }}" method="GET">
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari pendaftar..." value="{{ request('search') }}">
                        </div>
                    </form>
                </div>
            </div>

            @if($registrations->isEmpty())
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="bx bx-user-voice text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="text-muted">Tidak Ada Pendaftaran Baru</h5>
                    <p class="text-muted mb-0">Belum ada calon pelanggan yang mendaftar secara online atau pencarian Anda tidak ditemukan.</p>
                </div>
            @else
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode Reg</th>
                                <th>Nama Pelanggan</th>
                                <th>No. WhatsApp</th>
                                <th>Alamat</th>
                                <th>Foto Rumah</th>
                                <th>Paket Pilihan</th>
                                <th>Koordinat & Maps</th>
                                <th>Status Aktif</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($registrations as $p)
                            <tr>
                                <td><strong>{{ $p->kode_pelanggan }}</strong></td>
                                <td><span class="fw-semibold text-dark">{{ $p->nama_pelanggan }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span>{{ $p->no_wa }}</span>
                                        @if($p->no_wa)
                                        <form action="{{ route('pelanggan.registrasi.send-to-group', $p->id_pelanggan) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-icon btn-sm btn-outline-success" title="Kirim Info Registrasi ke Grup WhatsApp">
                                                <i class="bx bxl-whatsapp"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                                <td><span title="{{ $p->alamat }}">{{ Str::limit($p->alamat, 25) }}</span></td>
                                <td>
                                    @if($p->foto_rumah)
                                    <a href="{{ asset('storage/' . $p->foto_rumah) }}" target="_blank" class="btn btn-xs btn-outline-info">
                                        <i class="bx bx-image-alt me-1"></i> Lihat Foto
                                    </a>
                                    @else
                                    <span class="text-muted small">Tidak ada foto</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-label-info">{{ $p->paket ?? 'umum' }}</span>
                                    <div class="small text-muted">Rp {{ number_format($p->harga_layanan, 0, ',', '.') }}</div>
                                </td>
                                <td>
                                    @if($p->latitude && $p->longitude)
                                    <a href="https://www.google.com/maps?q={{ $p->latitude }},{{ $p->longitude }}" target="_blank" class="btn btn-xs btn-outline-primary">
                                        <i class="bx bx-map-alt me-1"></i> Google Maps
                                    </a>
                                    @else
                                    <span class="text-muted small">Tidak ada koordinat</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('pelanggan.toggle-status', $p->id_pelanggan) }}" method="POST" class="d-inline">
                                        @csrf
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" role="switch" onchange="this.form.submit()" style="cursor: pointer;" {{ $p->is_active ? 'checked' : '' }}>
                                        </div>
                                    </form>
                                    @if($p->is_active)
                                    <span class="badge bg-label-success ms-1">Aktif</span>
                                    @else
                                    <span class="badge bg-label-warning ms-1">Menunggu</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <a href="{{ route('pelanggan.show', $p->id_pelanggan) }}" class="btn btn-xs btn-outline-info" title="Detail">
                                            <i class="bx bx-show-alt"></i>
                                        </a>
                                        <a href="{{ route('pelanggan.edit', $p->id_pelanggan) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                                            <i class="bx bx-edit-alt"></i>
                                        </a>
                                        <a href="{{ route('pelanggan.destroy-direct', $p->id_pelanggan) }}" onclick="return confirm('Yakin ingin menghapus pendaftaran ini?');" class="btn btn-xs btn-outline-danger" title="Hapus">
                                            <i class="bx bx-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let map, marker;
    let defaultLat = -7.1590;
    let defaultLng = 112.6510;

    document.addEventListener("DOMContentLoaded", function () {
        map = L.map('map-picker').setView([defaultLat, defaultLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

        function updateCoords(lat, lng) {
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
        }

        marker.on('dragend', function (e) {
            let position = marker.getLatLng();
            updateCoords(position.lat, position.lng);
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });
    });

    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                let lat = position.coords.latitude;
                let lng = position.coords.longitude;
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                document.getElementById('latitude').value = lat.toFixed(6);
                document.getElementById('longitude').value = lng.toFixed(6);
            }, function (error) {
                alert("Gagal mendapatkan lokasi GPS: " + error.message);
            });
        } else {
            alert("Browser Anda tidak mendukung Geolocation.");
        }
    }
</script>
@endsection

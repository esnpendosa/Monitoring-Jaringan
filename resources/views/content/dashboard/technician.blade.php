@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard Teknisi - Rozitech')

@section('content')
<!-- Leaflet & Routing Map Assets loaded first for perfect rendering -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

<div class="row">
    <!-- Welcome Header Banner -->
    <div class="col-12 mb-4">
        <div class="card bg-label-primary border-0 shadow-sm overflow-hidden position-relative">
            <div class="card-body p-4 position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="fw-bold mb-1 text-primary">Selamat Bekerja, {{ $teknisi->nama_teknisi }}! 👋</h4>
                        <p class="mb-0 text-muted">Berikut ringkasan tugas lapangan, navigasi rute, dan status tiket gangguan Anda hari ini.</p>
                    </div>
                    <div class="d-none d-sm-block">
                        <span class="badge bg-primary px-3 py-2 fw-semibold fs-7 shadow-sm">
                            <i class="bx bx-calendar me-1"></i> {{ now()->translatedFormat('d F Y') }}
                        </span>
                    </div>
                </div>
            </div>
            <!-- Decorative Background Glows -->
            <div class="position-absolute" style="width: 250px; height: 250px; background: rgba(105, 108, 255, 0.15); border-radius: 50%; right: -50px; top: -50px; filter: blur(30px); z-index: 1;"></div>
            <div class="position-absolute" style="width: 150px; height: 150px; background: rgba(105, 108, 255, 0.1); border-radius: 50%; left: -20px; bottom: -50px; filter: blur(20px); z-index: 1;"></div>
        </div>
    </div>
</div>

<!-- Stats Indicators -->
<div class="row g-4 mb-4">
    <!-- Tugas Saya Card -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-border-shadow-primary h-100 shadow-sm transition-hover">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-primary shadow-sm"><i class="bx bx-briefcase-alt-2 fs-4"></i></span>
                    </div>
                    <h3 class="ms-1 mb-0 text-primary fw-bold">{{ $stats['my_tickets_count'] }}</h3>
                </div>
                <p class="mb-1 text-muted fw-semibold">Tugas Saya</p>
                <p class="mb-0">
                    <small class="text-muted">Tiket sedang ditangani</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Tiket Baru Tersedia Card -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-border-shadow-warning h-100 shadow-sm transition-hover">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-warning shadow-sm"><i class="bx bx-error-circle fs-4 text-warning"></i></span>
                    </div>
                    <h3 class="ms-1 mb-0 text-warning fw-bold">{{ $stats['open_tickets_count'] }}</h3>
                </div>
                <p class="mb-1 text-muted fw-semibold">Tiket Antrean</p>
                <p class="mb-0">
                    <small class="text-muted">Belum ada teknisi</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Alat Kerja Saya Card -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-border-shadow-success h-100 shadow-sm transition-hover">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-success shadow-sm"><i class="bx bx-wrench fs-4 text-success"></i></span>
                    </div>
                    <h3 class="ms-1 mb-0 text-success fw-bold">{{ $stats['my_inventory_count'] }}</h3>
                </div>
                <p class="mb-1 text-muted fw-semibold">Alat Kerja</p>
                <p class="mb-0">
                    <small class="text-muted">Inventaris terpinjam</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Infrastruktur Router Card -->
    <div class="col-sm-6 col-lg-3">
        <div class="card card-border-shadow-info h-100 shadow-sm transition-hover">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-info shadow-sm"><i class="bx bx-server fs-4 text-info"></i></span>
                    </div>
                    <h3 class="ms-1 mb-0 text-info fw-bold">{{ $stats['total_routers'] }}</h3>
                </div>
                <p class="mb-1 text-muted fw-semibold">Infrastruktur</p>
                <p class="mb-0">
                    <small class="text-muted">Total router terpantau</small>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Map & Active Tickets -->
    <div class="col-lg-8">
        <!-- Leaflet Map Card -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 py-3 border-bottom">
                <div>
                    <h5 class="mb-1 fw-bold text-dark"><i class="bx bx-map-pin text-primary me-2"></i>Navigasi Rute & Sebaran GIS Pelanggan</h5>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <span class="badge bg-success" style="font-size: 0.65rem; padding: 3px 6px;">🟢 Online</span>
                        <span class="badge bg-warning text-dark" style="font-size: 0.65rem; padding: 3px 6px;">🟡 Loss (Offline)</span>
                        <span class="badge bg-danger" style="font-size: 0.65rem; padding: 3px 6px;">🔴 Isolir</span>
                        <span class="badge bg-primary" style="font-size: 0.65rem; padding: 3px 6px;">🔵 Gangguan / Perbaikan</span>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                    <i class="bx bx-refresh me-1"></i> Perbarui Jalur
                </button>
            </div>
            <div class="card-body p-0">
                <div id="nav-map" style="height: 50vh; width: 100%;"></div>
            </div>
            <div class="card-footer bg-white py-3 border-top">
                <div class="row text-center gx-2">
                    <div class="col-4 border-end">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.65rem;">Total Jarak (Jalan)</small>
                        <span class="fw-bold text-primary fs-5" id="total-dist">--</span> <span class="text-muted small">KM</span>
                    </div>
                    <div class="col-4 border-end">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.65rem;">Estimasi Waktu</small>
                        <span class="fw-bold text-success fs-5" id="total-time">--</span> <span class="text-muted small">Mnt</span>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.65rem;">Titik Rute Gangguan</small>
                        <span class="fw-bold text-danger fs-5" id="cust-count">0</span> <span class="text-muted small">Titik</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tugas Tiket Aktif Saya -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-task text-primary me-2"></i>Tugas Gangguan Aktif Saya</h5>
                <span class="badge bg-label-primary">{{ $myTickets->count() }} Tugas</span>
            </div>
            <div class="card-body p-0">
                @if($myTickets->isEmpty())
                <div class="text-center py-5">
                    <i class="bx bx-check-circle text-success" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0 fw-semibold text-dark">Luar biasa! Tidak ada tiket gangguan aktif untuk Anda.</p>
                    <small class="text-muted">Semua keluhan lapangan Anda telah selesai ditangani.</small>
                </div>
                @else
                <div class="px-3 pt-3">
                    @php
                        $baseLat = $teknisi->base_latitude != 0 ? $teknisi->base_latitude : -7.1593;
                        $baseLng = $teknisi->base_longitude != 0 ? $teknisi->base_longitude : 112.6519;
                    @endphp
                    <a href="https://www.google.com/maps/dir/?api=1&origin={{ $baseLat }},{{ $baseLng }}&destination={{ $myTickets->last()->pelanggan->latitude }},{{ $myTickets->last()->pelanggan->longitude }}&waypoints={{ $myTickets->slice(0, -1)->map(function($t) { return $t->pelanggan->latitude . ',' . $t->pelanggan->longitude; })->implode('|') }}" target="_blank" class="btn btn-primary w-100 text-white shadow-sm">
                        <i class="bx bx-map-alt me-1"></i> Navigasi Seluruh Rute Hari Ini
                    </a>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($myTickets as $ticket)
                    <div class="list-group-item p-3 border-bottom-0">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-2 bg-light rounded p-3 shadow-sm border-start border-4 border-primary">
                            <div>
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                    <span class="badge bg-primary fw-bold"><i class="bx bx-map-pin me-1"></i>Rute #{{ $loop->iteration }}</span>
                                    <span class="badge bg-label-primary fw-bold">{{ $ticket->kode_tiket }}</span>
                                    <span class="badge bg-{{ $ticket->prioritas == 'High' ? 'danger' : ($ticket->prioritas == 'Medium' ? 'warning' : 'info') }}">{{ $ticket->prioritas }}</span>
                                    <span class="badge bg-label-info">{{ $ticket->status }}</span>
                                </div>
                                <h6 class="mb-1 fw-bold text-dark">{{ $ticket->pelanggan->nama_pelanggan }}</h6>
                                <p class="mb-1 text-muted small"><i class="bx bx-map me-1"></i>{{ $ticket->pelanggan->alamat }}</p>
                                <p class="mb-0 text-dark small bg-white p-2 rounded border border-light mt-2" style="font-style: italic;">
                                    "{{ Str::limit($ticket->keluhan, 120) }}"
                                </p>
                            </div>
                            <div class="mt-2 mt-sm-0 flex-shrink-0 d-flex gap-2 w-100 w-sm-auto justify-content-end">
                                @php
                                    $originLat = $loop->first ? ($teknisi->base_latitude != 0 ? $teknisi->base_latitude : -7.1593) : $myTickets[$loop->index - 1]->pelanggan->latitude;
                                    $originLng = $loop->first ? ($teknisi->base_longitude != 0 ? $teknisi->base_longitude : 112.6519) : $myTickets[$loop->index - 1]->pelanggan->longitude;
                                @endphp
                                <a href="https://www.google.com/maps/dir/?api=1&origin={{ $originLat }},{{ $originLng }}&destination={{ $ticket->pelanggan->latitude }},{{ $ticket->pelanggan->longitude }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-navigation me-1"></i> Navigasi
                                </a>
                                <a href="{{ route('tiket.show', $ticket->id_tiket) }}" class="btn btn-sm btn-primary">
                                    <i class="bx bx-chat me-1"></i> Detail / Chat
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Tiket Baru yang Belum Ditangani (Antrean Baru) -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-notification text-warning me-2"></i>Tiket Masuk (Menunggu Teknisi)</h5>
                <span class="badge bg-label-warning">{{ $availableTickets->count() }} Menunggu</span>
            </div>
            <div class="card-body p-0">
                @if($availableTickets->isEmpty())
                <div class="text-center py-5">
                    <i class="bx bx-list-check text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0 fw-semibold text-dark">Tidak ada tiket antrean.</p>
                    <small class="text-muted">Semua tiket gangguan masuk telah diambil alih atau selesai.</small>
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($availableTickets as $ticket)
                    <div class="list-group-item p-3 border-bottom-0">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-2 bg-light rounded p-3 border-start border-4 border-warning shadow-sm">
                            <div>
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                    <span class="badge bg-label-warning fw-bold">{{ $ticket->kode_tiket }}</span>
                                    <span class="badge bg-{{ $ticket->prioritas == 'High' ? 'danger' : ($ticket->prioritas == 'Medium' ? 'warning' : 'info') }}">{{ $ticket->prioritas }}</span>
                                </div>
                                <h6 class="mb-1 fw-bold text-dark">{{ $ticket->pelanggan->nama_pelanggan }}</h6>
                                <p class="mb-1 text-muted small"><i class="bx bx-map me-1"></i>{{ $ticket->pelanggan->alamat }}</p>
                                <p class="mb-0 text-dark small bg-white p-2 rounded border border-light mt-2">
                                    "{{ Str::limit($ticket->keluhan, 100) }}"
                                </p>
                            </div>
                            <div class="mt-2 mt-sm-0 flex-shrink-0 w-100 w-sm-auto text-end">
                                <a href="{{ route('tiket.show', $ticket->id_tiket) }}" class="btn btn-sm btn-outline-warning w-100 w-sm-auto">
                                    <i class="bx bx-user-plus me-1"></i> Detail / Ambil Alih
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Links & Router Status -->
    <div class="col-lg-4">
        <!-- Quick Action Menu -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-grid-alt text-primary me-2"></i>Akses Cepat Pintasan</h5>
            </div>
            <div class="card-body py-3">
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('scan.index') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-3 rounded shadow-sm text-start w-100">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-qr-scan fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-primary">Scan QR / Barcode</h6>
                                <small class="text-muted">Cek ODP / Perangkat via QR</small>
                            </div>
                        </div>
                        <i class="bx bx-chevron-right fs-4"></i>
                    </a>
                    
                    <a href="{{ route('tiket.index') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-between p-3 rounded shadow-sm text-start w-100">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-error-circle fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-secondary">Manajemen Tiket</h6>
                                <small class="text-muted">Update status, chat, & laporan</small>
                            </div>
                        </div>
                        <i class="bx bx-chevron-right fs-4"></i>
                    </a>

                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-success d-flex align-items-center justify-content-between p-3 rounded shadow-sm text-start w-100">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-wrench fs-3 me-3 text-success"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-success">Inventaris Alat Kerja</h6>
                                <small class="text-muted">Daftar alat bantu & stok teknisi</small>
                            </div>
                        </div>
                        <i class="bx bx-chevron-right fs-4"></i>
                    </a>

                    <a href="{{ route('mikrotik.index') }}" class="btn btn-outline-info d-flex align-items-center justify-content-between p-3 rounded shadow-sm text-start w-100">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-server fs-3 me-3 text-info"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-info">Monitoring Mikrotik</h6>
                                <small class="text-muted">Status konektivitas & bandwidth</small>
                            </div>
                        </div>
                        <i class="bx bx-chevron-right fs-4"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Infrastructure Summary Status -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-server text-primary me-2"></i>Infrastruktur Router</h5>
                <span class="badge bg-label-info">{{ $routers->count() }} Router</span>
            </div>
            <div class="card-body p-0">
                @if($routers->isEmpty())
                <div class="text-center py-4">
                    <small class="text-muted">Tidak ada data router terdaftar.</small>
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($routers as $router)
                    <div class="list-group-item d-flex align-items-center justify-content-between py-3 border-bottom-0">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-label-info p-2 me-3 rounded"><i class="bx bx-hard-drive text-info"></i></span>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">{{ $router->nama_router }}</h6>
                                <small class="text-muted">{{ $router->ip_address }}</small>
                            </div>
                        </div>
                        <span class="badge bg-label-success">Online</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Map Scripts and Leaflet configuration -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var baseLat = {{ $teknisi->base_latitude && $teknisi->base_latitude != 0 ? $teknisi->base_latitude : -7.1593 }};
        var baseLng = {{ $teknisi->base_longitude && $teknisi->base_longitude != 0 ? $teknisi->base_longitude : 112.6519 }};
        
        var map = L.map('nav-map', {zoomControl: false}).setView([baseLat, baseLng], 14);
        L.control.zoom({position: 'bottomright'}).addTo(map);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var waypoints = [];
        waypoints.push(L.latLng(baseLat, baseLng));

        // Basecamp (Start)
        L.marker([baseLat, baseLng], {
            icon: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/609/609803.png',
                iconSize: [35, 35],
                iconAnchor: [17, 35]
            })
        }).addTo(map).bindPopup("<b>Basecamp (Start Point)</b>");

        var allCustomers = @json($pelangganMap ?? []);
        var routeData = @json($optimizedRoute['route'] ?? []);
        
        // Map to quickly check route sequence stops
        var routeMap = {};
        routeData.forEach(function(d, index) {
            routeMap[d.id_pelanggan] = index + 1; // 1-indexed Stop number
        });

        document.getElementById('cust-count').innerText = routeData.length;

        allCustomers.forEach(function(c) {
            if (!c.latitude || !c.longitude) return;

            var latlng = L.latLng(c.latitude, c.longitude);
            var stopIndex = routeMap[c.id_pelanggan];

            // 1. Determine Marker Styles
            var color = '#28a745'; // Online - Hijau
            if (c.status_gis === 'offline')   color = '#ffc107'; // Offline - Kuning
            if (c.status_gis === 'timeout')   color = '#dc3545'; // Isolir - Merah
            if (c.status_gis === 'perbaikan') color = '#007bff'; // Perbaikan - Biru

            var statusLabel = '🟢 ONLINE';
            if (c.status_gis === 'offline')   statusLabel = '🟡 OFFLINE (LOSS)';
            if (c.status_gis === 'timeout')   statusLabel = '🔴 ISOLIR';
            if (c.status_gis === 'perbaikan') statusLabel = '🔵 GANGGUAN / PERBAIKAN';

            var tagihanInfo = c.tagihan && c.tagihan.length > 0
                ? (['unpaid','belum_bayar'].includes(c.tagihan[0].status) ? '❌ BELUM BAYAR' : '✅ LUNAS')
                : 'Tidak ada';

            var isStopOnRoute = !!stopIndex;

            var markerOptions = {
                radius: isStopOnRoute ? 13 : 8,
                fillColor: color,
                color: isStopOnRoute ? '#696cff' : '#fff', // Highlight route stop with a gorgeous border
                weight: isStopOnRoute ? 3.5 : 2,
                opacity: 1,
                fillOpacity: isStopOnRoute ? 0.95 : 0.8
            };

            // Push route waypoints in chronological sequence order
            if (isStopOnRoute) {
                waypoints[stopIndex] = latlng;
            }

            var marker = L.circleMarker(latlng, markerOptions).addTo(map);

            var popupContent = `
                <div style="min-width: 200px;">
                    ${isStopOnRoute ? `<span class="badge bg-primary mb-2 w-100 text-white">📍 Tugas Pemberhentian Ke-${stopIndex}</span>` : ''}
                    <h6 class="mb-1 fw-bold text-dark">${c.nama_pelanggan}</h6>
                    <p class="mb-1 small text-muted">${c.kode_pelanggan} | ${c.ip_address || '-'}</p>
                    <p class="mb-1 small">Status: <strong>${statusLabel}</strong></p>
                    <hr class="my-1">
                    <p class="mb-1 small">Tagihan: ${tagihanInfo}</p>
                    <p class="mb-2 small text-muted">${c.alamat}</p>
                    <div class="d-flex justify-content-between align-items-center gap-1">
                        <a href="https://www.google.com/maps/dir/?api=1&destination=${c.latitude},${c.longitude}" target="_blank" class="btn btn-xs btn-outline-secondary" style="font-size:10px; padding: 2px 5px;"><i class="bx bx-navigation"></i> Google Maps</a>
                        <a href="/tiket?search=${c.kode_pelanggan}" class="btn btn-xs btn-primary text-white" style="font-size:10px; padding: 2px 5px;"><i class="bx bx-chat"></i> Tiket</a>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent);

            // Draw sequence number inside the circle marker if on the route
            if (isStopOnRoute) {
                L.marker(latlng, {
                    icon: L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style='color: white; font-weight: bold; font-size: 11px; margin-top: -6px; margin-left: -3.5px;'>${stopIndex}</div>`,
                        iconSize: [0, 0]
                    })
                }).addTo(map);
            }
        });

        // Clean waypoints to remove any undefined elements
        waypoints = waypoints.filter(function(wp) {
            return wp !== undefined;
        });

        // Initialize Fallback dashed polyline connecting points
        var fallbackLine = null;
        if (waypoints.length > 1) {
            fallbackLine = L.polyline(waypoints, {
                color: '#696cff',
                weight: 4,
                dashArray: '5, 10',
                opacity: 0.7
            }).addTo(map);
        }

        // Initialize Routing Control (FOLLOWING ACTUAL ROADS)
        if (waypoints.length > 1) {
            try {
                var control = L.Routing.control({
                    waypoints: waypoints,
                    router: L.Routing.osrmv1({
                        serviceUrl: 'https://router.project-osrm.org/route/v1'
                    }),
                    lineOptions: {
                        styles: [{color: '#696cff', opacity: 0.8, weight: 6}]
                    },
                    addWaypoints: false,
                    draggableWaypoints: false,
                    fitSelectedRoutes: true,
                    showAlternatives: false,
                    createMarker: function() { return null; }
                }).addTo(map);

                control.on('routesfound', function(e) {
                    var routes = e.routes;
                    var summary = routes[0].summary;
                    document.getElementById('total-dist').innerText = (summary.totalDistance / 1000).toFixed(2);
                    document.getElementById('total-time').innerText = Math.round(summary.totalTime / 60);
                    // Remove fallback dashed line once actual routing line is drawn
                    if (fallbackLine) {
                        map.removeLayer(fallbackLine);
                    }
                });

                // Hide the default routing instructions container
                setTimeout(function() {
                    var container = document.querySelector('.leaflet-routing-container');
                    if (container) container.style.display = 'none';
                }, 500);
            } catch (err) {
                console.warn("Leaflet Routing Machine solver failed. Using polyline fallback.", err);
            }
        } else {
            document.getElementById('total-dist').innerText = "0";
            document.getElementById('total-time').innerText = "0";
        }

        // Classic Leaflet fix: Invalidate size after map load to ensure tiles render immediately
        setTimeout(function() {
            map.invalidateSize();
        }, 300);

        // Routers / Mikrotik Locations
        @foreach($routers as $router)
            L.marker([baseLat + 0.005, baseLng + 0.005], {
                icon: L.icon({
                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/1000/1000966.png',
                    iconSize: [25, 25]
                })
            }).addTo(map).bindPopup("<b>Router Infrastructure: {{ $router->nama_router }}</b>");
        @endforeach
    });
</script>

<style>
    .transition-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .transition-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
    }
    .custom-div-icon { background: none; border: none; }
</style>
@endsection

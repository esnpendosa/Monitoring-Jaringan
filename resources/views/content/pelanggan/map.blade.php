@extends('layouts/contentNavbarLayout')

@section('title', 'Web GIS Pelanggan')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Pelanggan /</span> Web GIS</h4>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="input-group input-group-merge">
                    <span class="input-group-text" id="basic-addon-search31"><i class="bx bx-search"></i></span>
                    <input type="text" id="map-search" class="form-control" placeholder="Cari Nama atau Kode Pelanggan..." aria-label="Search..." aria-describedby="basic-addon-search31">
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <button class="btn btn-primary w-100" onclick="alert('Klik pada peta untuk menambah pelanggan di lokasi tersebut')">
                    <i class="bx bx-plus me-1"></i> Tambah Pelanggan dari Peta
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Sebaran Lokasi Pelanggan</h5>
        <div>
            <span class="badge bg-success me-2">Online</span>
            <span class="badge bg-warning me-2">Offline / Loss</span>
            <span class="badge bg-danger me-2">Timeout / Isolir</span>
            <span class="badge bg-primary">Perbaikan</span>
        </div>
    </div>
    <div class="card-body">
        <div id="map-full" style="height: 600px; border-radius: 8px;"></div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initial view centered around a default location
        var map = L.map('map-full').setView([-7.1207, 112.5959], 14);
        
        L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            attribution: '© Google Earth Satellite',
            maxZoom: 20
        }).addTo(map);

        var customers = @json($pelanggan);
        var markerLayers = L.layerGroup().addTo(map);
        var allMarkers = [];

        function renderMarkers(filter = '') {
            markerLayers.clearLayers();
            var bounds = [];

            customers.forEach(function(c) {
                if (filter && !c.nama_pelanggan.toLowerCase().includes(filter.toLowerCase()) && !c.kode_pelanggan.toLowerCase().includes(filter.toLowerCase())) {
                    return;
                }

                var isOnline = (c.last_online_status == 1 || c.status === 'online') && (c.status_gis !== 'offline' && c.status_gis !== 'timeout');
                if (c.last_online_status == 0 || c.status === 'offline' || c.status_gis === 'offline' || c.status_gis === 'timeout') {
                    isOnline = false;
                }
                var color = isOnline ? '#16a34a' : '#dc2626'; // Offline = Merah (#dc2626)
                if (c.status_gis === 'perbaikan') color = '#007bff';

                var marker = L.circleMarker([c.latitude, c.longitude], {
                    radius: 10,
                    fillColor: color,
                    color: "#fff",
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9
                });

                var statusLabel = isOnline ? '🟢 ONLINE' : '🔴 OFFLINE / PUTUS';
                if (c.status_gis === 'perbaikan') statusLabel = '🔵 PERBAIKAN';
                if(c.status_gis === 'online') statusLabel = '🟢 ONLINE';
                if(c.status_gis === 'offline') statusLabel = '🟡 OFFLINE (LOSS)';
                if(c.status_gis === 'timeout') statusLabel = '🔴 TIMEOUT (ISOLIR)';
                if(c.status_gis === 'perbaikan') statusLabel = '🔵 PERBAIKAN';

                marker.bindPopup(`
                    <div style="min-width: 200px;">
                        <h6 class="mb-1">${c.nama_pelanggan}</h6>
                        <p class="mb-1 small text-muted">${c.kode_pelanggan} | ${c.ip_address || '-'}</p>
                        <p class="mb-1 small">Status: <strong>${statusLabel}</strong></p>
                        <hr class="my-1">
                        <p class="mb-1 small">Tagihan: ${c.tagihan.length > 0 ? (['unpaid', 'belum_bayar'].includes(c.tagihan[0].status) ? '❌ BELUM BAYAR' : '✅ LUNAS') : 'Tidak ada'}</p>
                        <p class="mb-1 small">Terakhir Aktif: ${c.last_ping_at || '-'}</p>
                        <p class="mb-2 small">${c.alamat}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-label-primary" style="font-size: 10px;">Usage: ${c.usage_gb}GB</span>
                            <a href="/pelanggan/${c.id_pelanggan}/edit" class="btn btn-xs btn-primary" style="padding: 2px 5px; font-size: 10px; color:white;">Edit</a>
                        </div>
                    </div>
                `);
                
                markerLayers.addLayer(marker);
                bounds.push([c.latitude, c.longitude]);
            });

            if (bounds.length > 0 && filter) {
                map.fitBounds(bounds, {padding: [50, 50]});
            }
        }

        // Search event
        document.getElementById('map-search').addEventListener('input', function(e) {
            renderMarkers(e.target.value);
        });

        // Click on map to add new customer
        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(8);
            var lng = e.latlng.lng.toFixed(8);
            
            var popup = L.popup()
                .setLatLng(e.latlng)
                .setContent(`
                    <div class="text-center p-2">
                        <h6>Tambah Pelanggan Baru?</h6>
                        <p class="small text-muted">Koordinat: ${lat}, ${lng}</p>
                        <a href="/pelanggan/create?lat=${lat}&lng=${lng}" class="btn btn-sm btn-primary text-white">Ya, Tambah Disini</a>
                    </div>
                `)
                .openOn(map);
        });

        renderMarkers();
        
        // Fit map initially
        var initialBounds = customers.map(c => [c.latitude, c.longitude]);
        if (initialBounds.length > 0) {
            map.fitBounds(initialBounds, {padding: [50, 50]});
        }
    });
</script>
@endsection

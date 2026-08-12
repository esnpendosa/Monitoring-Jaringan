@extends('layouts/contentNavbarLayout')

@section('title', 'Detail Rute Kunjungan')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rute /</span> Detail Kunjungan #{{ $rute->id_rute }}</h4>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informasi Rute</h5>
            </div>
            <div class="card-body">
                <p><strong>Teknisi:</strong> {{ $rute->teknisi->nama_teknisi }}</p>
                <p><strong>Tanggal:</strong> {{ $rute->tanggal_kunjungan }}</p>
                <p><strong>Total Jarak:</strong> {{ number_format($rute->total_jarak_km, 2) }} KM</p>
                @if($rute->details->isNotEmpty())
                <a href="https://www.google.com/maps/dir/?api=1&origin={{ $rute->titik_awal_lat }},{{ $rute->titik_awal_lng }}&destination={{ $rute->details->last()->pelanggan->latitude }},{{ $rute->details->last()->pelanggan->longitude }}&waypoints={{ $rute->details->slice(0, -1)->map(function($d) { return $d->pelanggan->latitude . ',' . $d->pelanggan->longitude; })->implode('|') }}" target="_blank" class="btn btn-primary w-100 mb-3 mt-2 text-white">
                    <i class="bx bx-map-alt me-1"></i> Navigasi Seluruh Rute
                </a>
                @endif
                <hr>
                <h6>Urutan Kunjungan:</h6>
                <ul class="list-group">
                    <li class="list-group-item bg-light">Titik Awal (Kantor CV. ROZITECH)</li>
                    @foreach($rute->details as $d)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $d->urutan }}. {{ $d->pelanggan->nama_pelanggan }}</strong> <br>
                            <small class="text-muted">
                                Jarak: {{ number_format($d->jarak_dari_sebelumnya_km, 2) }} KM | 
                                Estimasi: {{ $d->estimasi_waktu_menit }} Menit
                            </small> <br>
                            <span class="badge {{ $d->status_kunjungan == 'Visited' ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $d->status_kunjungan == 'Visited' ? 'Selesai' : ($d->status_kunjungan == 'Pending' ? 'Menunggu' : $d->status_kunjungan) }}
                            </span>
                        </div>
                        @if($d->status_kunjungan == 'Pending')
                        <div class="d-flex gap-2">
                            @php
                                $originLat = $loop->first ? $rute->titik_awal_lat : $rute->details[$loop->index - 1]->pelanggan->latitude;
                                $originLng = $loop->first ? $rute->titik_awal_lng : $rute->details[$loop->index - 1]->pelanggan->longitude;
                            @endphp
                            <a href="https://www.google.com/maps/dir/?api=1&origin={{ $originLat }},{{ $originLng }}&destination={{ $d->pelanggan->latitude }},{{ $d->pelanggan->longitude }}" target="_blank" class="btn btn-sm btn-info">
                                <i class="bx bx-navigation"></i> Navigasi
                            </a>
                            <form action="{{ route('rute.detail.status', $d->id_rute_detail) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">Selesai</button>
                            </form>
                        </div>
                        @else
                        @php
                            $originLat = $loop->first ? $rute->titik_awal_lat : $rute->details[$loop->index - 1]->pelanggan->latitude;
                            $originLng = $loop->first ? $rute->titik_awal_lng : $rute->details[$loop->index - 1]->pelanggan->longitude;
                        @endphp
                        <a href="https://www.google.com/maps/dir/?api=1&origin={{ $originLat }},{{ $originLng }}&destination={{ $d->pelanggan->latitude }},{{ $d->pelanggan->longitude }}" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="bx bx-navigation"></i> Peta
                        </a>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Visualisasi Rute & Titik Jaringan (ODC/ODP)</h5>
                <div class="legend small">
                    <span class="badge bg-primary">Kantor</span>
                    <span class="badge bg-danger">Pelanggan</span>
                    <span class="badge bg-warning text-dark">ODC</span>
                    <span class="badge bg-success">ODP</span>
                </div>
            </div>
            <div class="card-body">
                <div id="map" style="height: 600px; border-radius: 8px;"></div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<style>
    /* CSS Animation for ODC/ODP */
    .pulse-animation {
        border-radius: 50%;
        box-shadow: 0 0 0 0 rgba(255, 165, 0, 0.7);
        animation: pulse 2s infinite;
    }
    
    .pulse-odp {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
        animation: pulse-green 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 165, 0, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 165, 0, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 165, 0, 0); }
    }

    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }

    .popup-photo {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 4px;
        margin-top: 8px;
    }
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var baseLat = {{ $rute->titik_awal_lat }};
        var baseLng = {{ $rute->titik_awal_lng }};
        
        var map = L.map('map').setView([baseLat, baseLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        var waypoints = [];
        waypoints.push(L.latLng(baseLat, baseLng));

        // Base Marker
        L.marker([baseLat, baseLng], {
            icon: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/609/609803.png',
                iconSize: [30, 30]
            })
        }).addTo(map).bindPopup("<b>Kantor CV. ROZITECH</b>");

        // Render Pelanggan
        var details = @json($rute->details->load('pelanggan'));
        details.forEach(function(d) {
            if (d.pelanggan && d.pelanggan.latitude && d.pelanggan.longitude) {
                var p = L.latLng(d.pelanggan.latitude, d.pelanggan.longitude);
                waypoints.push(p);
                
                L.circleMarker([d.pelanggan.latitude, d.pelanggan.longitude], {
                    radius: 8, 
                    fillColor: 'red', 
                    color: '#fff', 
                    weight: 2, 
                    fillOpacity: 1
                }).addTo(map).bindPopup("<b>" + d.urutan + ". " + d.pelanggan.nama_pelanggan + "</b><br>" + d.pelanggan.alamat);
            }
        });

        // Render ODC & ODP with Animations & Photos
        var odcOdpData = @json($odc_odp);
        odcOdpData.forEach(function(item) {
            var iconClass = item.tipe === 'ODC' ? 'pulse-animation' : 'pulse-odp';
            var iconUrl = item.tipe === 'ODC' 
                ? 'https://cdn-icons-png.flaticon.com/512/2885/2885417.png' // ODC Icon
                : 'https://cdn-icons-png.flaticon.com/512/944/944455.png';  // ODP Icon
            
            var customIcon = L.divIcon({
                className: iconClass,
                html: `<img src="${iconUrl}" style="width:24px;height:24px;">`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            var photoHtml = item.foto ? `<img src="${item.foto}" class="popup-photo">` : '';
            
            L.marker([item.latitude, item.longitude], { icon: customIcon })
                .addTo(map)
                .bindPopup(`
                    <div style="width:200px">
                        <span class="badge ${item.tipe === 'ODC' ? 'bg-warning text-dark' : 'bg-success'} mb-1">${item.tipe}</span>
                        <h6 class="mb-1">${item.nama}</h6>
                        <p class="small text-muted mb-0">${item.deskripsi || ''}</p>
                        ${photoHtml}
                        <div class="mt-2">
                            <small>Lat: ${item.latitude}</small><br>
                            <small>Lng: ${item.longitude}</small>
                        </div>
                    </div>
                `);
        });

        // Clean waypoints to remove any undefined elements
        waypoints = waypoints.filter(function(wp) {
            return wp !== undefined;
        });

        // Initialize Fallback dashed polyline connecting points
        var fallbackLine = null;
        if (waypoints.length > 1) {
            fallbackLine = L.polyline(waypoints, {
                color: 'blue',
                weight: 4,
                dashArray: '5, 10',
                opacity: 0.5
            }).addTo(map);
        }

        // Road-Following Route
        if (waypoints.length > 1) {
            try {
                var control = L.Routing.control({
                    waypoints: waypoints,
                    router: L.Routing.osrmv1({
                        serviceUrl: 'https://router.project-osrm.org/route/v1'
                    }),
                    addWaypoints: false,
                    draggableWaypoints: false,
                    fitSelectedRoutes: true,
                    showAlternatives: false,
                    lineOptions: {
                        styles: [{color: 'blue', opacity: 0.6, weight: 4}]
                    },
                    createMarker: function() { return null; }
                }).addTo(map);

                control.on('routesfound', function(e) {
                    // Remove fallback dashed line once actual routing line is drawn
                    if (fallbackLine) {
                        map.removeLayer(fallbackLine);
                    }
                });

                setTimeout(function() {
                    var container = document.querySelector('.leaflet-routing-container');
                    if (container) container.style.display = 'none';
                }, 500);
            } catch (err) {
                console.warn("Leaflet Routing Machine solver failed. Using polyline fallback.", err);
            }
        }
    });
</script>
@endsection

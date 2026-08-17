@extends('layouts/contentNavbarLayout')

@section('title', 'Peta Topologi FTTH')

@section('content')
<h4 class="fw-bold py-3 mb-2"><span class="text-muted fw-light">Jaringan /</span> Peta Topologi FTTH</h4>

{{-- Status Legend --}}
<div class="row mb-3 g-2">
  <div class="col-auto">
    <div class="card card-body py-2 px-3 d-flex flex-row align-items-center gap-2">
      <span class="badge rounded-pill" style="background:#28a745;font-size:12px;">● Online</span>
      <span class="badge rounded-pill" style="background:#ffc107;color:#000;font-size:12px;">● Warning / Redaman Tinggi</span>
      <span class="badge rounded-pill" style="background:#dc3545;font-size:12px;">● Offline / Putus</span>
      <span class="text-muted small ms-2">Kabel: tebal=feeder, sedang=distribusi, tipis=drop</span>
    </div>
  </div>
  <div class="col-auto ms-auto d-flex gap-2">
    <a href="{{ route('olt.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-server me-1"></i>Kelola OLT</a>
    <a href="{{ route('odc-odp.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-layer me-1"></i>ODC & ODP</a>
    <a href="{{ route('kabel.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-cable-car me-1"></i>Kabel ({{ $kabelCount }})</a>
    <button id="btn-refresh" class="btn btn-sm btn-primary"><i class="bx bx-refresh me-1"></i>Refresh Peta</button>
  </div>
</div>

{{-- Stats Row --}}
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md bg-label-primary rounded"><i class="bx bx-server fs-4"></i></div>
        <div><div class="fw-bold fs-5">{{ $olts->count() }}</div><div class="text-muted small">OLT</div></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md bg-label-info rounded"><i class="bx bx-layer fs-4"></i></div>
        <div><div class="fw-bold fs-5">{{ $odcs->count() }}</div><div class="text-muted small">ODC</div></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md bg-label-success rounded"><i class="bx bx-box fs-4"></i></div>
        <div><div class="fw-bold fs-5">{{ $odps->count() }}</div><div class="text-muted small">ODP / FAT</div></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md bg-label-warning rounded"><i class="bx bx-home fs-4"></i></div>
        <div><div class="fw-bold fs-5">{{ $pelanggan->count() }}</div><div class="text-muted small">Pelanggan (ONT)</div></div>
      </div>
    </div>
  </div>
</div>

{{-- Map --}}
<div class="card shadow-sm">
  <div class="card-body p-0" style="border-radius:12px;overflow:hidden;">
    <div id="ftth-map" style="height:620px;width:100%;"></div>
  </div>
</div>

{{-- Panel kanan: info node yang diklik --}}
<div id="node-panel" class="card shadow-sm mt-3 d-none">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0" id="panel-title">Detail Node</h6>
    <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('node-panel').classList.add('d-none')">✕</button>
  </div>
  <div class="card-body" id="panel-body"></div>
</div>

{{-- Leaflet CSS + JS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // ─── Init Map ───────────────────────────────────────────────
  var map = L.map('ftth-map').setView([-7.1207, 112.5959], 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // ─── Data dari Laravel ──────────────────────────────────────
  var olts      = @json($olts);
  var odcs      = @json($odcs);
  var odps      = @json($odps);
  var pelanggan = @json($pelanggan);

  // ─── Layer groups ───────────────────────────────────────────
  var kabelLayer     = L.layerGroup().addTo(map);
  var nodeLayer      = L.layerGroup().addTo(map);
  var breakMarkers   = L.layerGroup().addTo(map);

  var overlays = {
    'Kabel Polyline': kabelLayer,
    'Node (OLT/ODC/ODP/ONT)': nodeLayer,
    'Titik Putus': breakMarkers,
  };
  L.control.layers(null, overlays, { collapsed: false }).addTo(map);

  // ─── Icon Factory ───────────────────────────────────────────
  function makeIcon(emoji, color, size) {
    size = size || 36;
    return L.divIcon({
      className: '',
      html: `<div style="width:${size}px;height:${size}px;background:${color};border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:${size*0.45}px;box-shadow:0 2px 6px rgba(0,0,0,.35);">${emoji}</div>`,
      iconSize: [size, size],
      iconAnchor: [size/2, size/2],
      popupAnchor: [0, -(size/2+4)],
    });
  }

  var ICONS = {
    olt:      (s) => makeIcon('🖥️', colorOf(s), 44),
    odc:      (s) => makeIcon('📦', colorOf(s), 38),
    odp:      (s) => makeIcon('🔲', colorOf(s), 32),
    pelanggan:(s) => makeIcon('🏠', s === 'offline' ? '#ffc107' : '#28a745', 26),
    break:    ()  => makeIcon('⚠️', '#dc3545', 30),
  };

  function colorOf(status) {
    return { online:'#28a745', warning:'#ffc107', offline:'#dc3545' }[status] || '#6c757d';
  }

  function kabelWeight(tipe) {
    return { feeder: 6, distribusi: 4, drop: 2 }[tipe] || 3;
  }

  // ─── Render OLT markers ─────────────────────────────────────
  olts.forEach(function(o) {
    if (!o.latitude || !o.longitude) return;
    var m = L.marker([o.latitude, o.longitude], { icon: ICONS.olt(o.status) });
    m.bindPopup(`
      <div style="min-width:220px">
        <h6 class="mb-1">🖥️ OLT: ${o.nama}</h6>
        <p class="mb-1 small text-muted">${o.lokasi || '-'}</p>
        <p class="mb-1 small">IP: <code>${o.ip_address || '-'}</code></p>
        <p class="mb-1 small">Kapasitas PON: <strong>${o.kapasitas_pon} port</strong></p>
        <p class="mb-1 small">Status: <span class="badge" style="background:${colorOf(o.status||'online')}">${(o.status||'online').toUpperCase()}</span></p>
        <div class="mt-2">
          <a href="/olt/${o.id}/edit" class="btn btn-xs btn-sm btn-primary" style="font-size:11px;">Edit OLT</a>
        </div>
      </div>`);
    nodeLayer.addLayer(m);
  });

  // ─── Render ODC markers ─────────────────────────────────────
  odcs.forEach(function(o) {
    if (!o.latitude || !o.longitude) return;
    var m = L.marker([o.latitude, o.longitude], { icon: ICONS.odc(o.status) });
    m.bindPopup(`
      <div style="min-width:210px">
        <h6 class="mb-1">📦 ODC: ${o.nama}</h6>
        <p class="mb-1 small">Kapasitas Core: <strong>${o.kapasitas_core || '-'}</strong></p>
        <p class="mb-1 small">Status: <span class="badge" style="background:${colorOf(o.status || 'online')}">${(o.status||'online').toUpperCase()}</span></p>
        <div class="mt-2">
          <a href="/odc-odp/${o.id}/edit" class="btn btn-xs btn-sm btn-outline-primary" style="font-size:11px;">Edit ODC</a>
        </div>
      </div>`);
    nodeLayer.addLayer(m);
  });

  // ─── Render ODP markers ─────────────────────────────────────
  odps.forEach(function(o) {
    if (!o.latitude || !o.longitude) return;
    var m = L.marker([o.latitude, o.longitude], { icon: ICONS.odp(o.status) });
    var pelCount = (o.pelanggan || []).length;
    m.bindPopup(`
      <div style="min-width:200px">
        <h6 class="mb-1">🔲 ODP: ${o.nama}</h6>
        <p class="mb-1 small">ODC Induk: <strong>${o.parent ? o.parent.nama : '-'}</strong></p>
        <p class="mb-1 small">Kapasitas Port: <strong>${o.kapasitas_port || '-'}</strong></p>
        <p class="mb-1 small">Pelanggan: <strong>${pelCount} terhubung</strong></p>
        <p class="mb-1 small">Status: <span class="badge" style="background:${colorOf(o.status || 'online')}">${(o.status||'online').toUpperCase()}</span></p>
        <div class="mt-2">
          <a href="/odc-odp/${o.id}/edit" class="btn btn-xs btn-sm btn-outline-primary" style="font-size:11px;">Edit ODP</a>
        </div>
      </div>`);
    nodeLayer.addLayer(m);
  });

  // ─── Render Pelanggan (ONT) markers ─────────────────────────
  pelanggan.forEach(function(p) {
    if (!p.latitude || !p.longitude) return;
    var m = L.marker([p.latitude, p.longitude], { icon: ICONS.pelanggan(p.last_online_status) });
    var rxPower = p.onu_rx_power ? p.onu_rx_power + ' dBm' : '-';
    m.bindPopup(`
      <div style="min-width:200px">
        <h6 class="mb-1">🏠 ${p.nama_pelanggan}</h6>
        <p class="mb-1 small text-muted">${p.kode_pelanggan} | ${p.ip_address || '-'}</p>
        <p class="mb-1 small">ONT Serial: <code>${p.serial_ont || '-'}</code></p>
        <p class="mb-1 small">RX Power: <strong>${rxPower}</strong></p>
        <p class="mb-1 small">ACS Last Inform: ${p.last_inform_at || '-'}</p>
        <p class="mb-1 small">Status: <span class="badge" style="background:${p.last_online_status==='offline'?'#ffc107':'#28a745'};color:${p.last_online_status==='offline'?'#000':'#fff'}">${(p.last_online_status||'online').toUpperCase()}</span></p>
        <div class="mt-2">
          <a href="/pelanggan/${p.id_pelanggan}/edit" class="btn btn-xs btn-sm btn-outline-secondary" style="font-size:11px;">Edit</a>
        </div>
      </div>`);
    nodeLayer.addLayer(m);
  });

  // ─── Render Kabel Polylines via GeoJSON API ──────────────────
  function loadKabels() {
    kabelLayer.clearLayers();
    breakMarkers.clearLayers();

    fetch('/kabel-geojson')
      .then(r => r.json())
      .then(function(geoJson) {
        geoJson.features.forEach(function(f) {
          var p    = f.properties;
          var coords = f.geometry.coordinates.map(c => [c[1], c[0]]); // GeoJSON [lng,lat] → Leaflet [lat,lng]

          if (coords.length < 2) return;

          var line = L.polyline(coords, {
            color:   p.color,
            weight:  kabelWeight(p.tipe),
            opacity: 0.85,
          });

          var rftsInfo = p.monitoring_type === 'realtime'
            ? `<span class="badge bg-info text-dark" style="font-size:10px;">📡 RFTS Realtime</span>`
            : `<span class="badge bg-secondary" style="font-size:10px;">🔧 Manual/Inferensi</span>`;

          var putusInfo = p.titik_putus_meter
            ? `<p class="mb-1 small text-danger">⚠️ Titik putus: ±${p.titik_putus_meter} m dari asal</p>`
            : '';

          line.bindPopup(`
            <div style="min-width:230px">
              <h6 class="mb-1">🔌 ${p.label}</h6>
              <p class="mb-1 small">${p.tipe_label}</p>
              ${rftsInfo}
              <hr class="my-2">
              <p class="mb-1 small">Jumlah Core: <strong>${p.jumlah_core}</strong></p>
              <p class="mb-1 small">Redaman: <strong>${p.redaman_db ? p.redaman_db + ' dB' : '-'}</strong></p>
              ${putusInfo}
              <p class="mb-1 small">Status: <span class="badge" style="background:${p.color}">${(p.status||'online').toUpperCase()}</span></p>
              <p class="mb-1 small text-muted">Update: ${p.updated_by || '-'} · ${p.updated_at || '-'}</p>
              ${p.catatan ? `<p class="mb-1 small">${p.catatan}</p>` : ''}
              <div class="mt-2 d-flex gap-1">
                <a href="/kabel/${p.id}/edit" class="btn btn-xs btn-sm btn-outline-primary" style="font-size:11px;">Edit</a>
                <button onclick="quickStatus(${p.id})" class="btn btn-xs btn-sm btn-outline-warning" style="font-size:11px;">Update Status</button>
              </div>
            </div>`);

          kabelLayer.addLayer(line);

          // Tandai titik putus jika ada
          if (p.status === 'offline' && p.titik_putus_meter && coords.length >= 2) {
            var breakMarker = L.marker(coords[0], { icon: ICONS.break() });
            breakMarker.bindTooltip(`Titik putus ±${p.titik_putus_meter}m`);
            breakMarkers.addLayer(breakMarker);
          }
        });
      })
      .catch(function(e) {
        console.error('Gagal load kabel GeoJSON:', e);
      });
  }

  // ─── Quick status update (modal sederhana) ───────────────────
  window.quickStatus = function(kabelId) {
    var status = prompt('Update status kabel:\nonline / warning / offline');
    if (!['online','warning','offline'].includes(status)) return;
    var catatan = prompt('Catatan (opsional):') || '';

    fetch('/kabel/' + kabelId + '/status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
      },
      body: JSON.stringify({ status, catatan }),
    })
    .then(r => r.json())
    .then(function(res) {
      if (res.success) {
        alert('✅ Status berhasil diperbarui ke: ' + res.status);
        loadKabels();
      }
    });
  };

  // ─── Refresh button ──────────────────────────────────────────
  document.getElementById('btn-refresh').addEventListener('click', function() {
    loadKabels();
    this.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Loading...';
    setTimeout(() => { this.innerHTML = '<i class="bx bx-refresh me-1"></i>Refresh Peta'; }, 1500);
  });

  // ─── Fit bounds ke semua node ────────────────────────────────
  var allPoints = [];
  [...olts, ...odcs, ...odps, ...pelanggan].forEach(function(n) {
    if (n.latitude && n.longitude) allPoints.push([n.latitude, n.longitude]);
  });
  if (allPoints.length > 0) map.fitBounds(allPoints, { padding: [40, 40] });

  // ─── Load kabel pertama kali ────────────────────────────────
  loadKabels();
});
</script>
@endsection

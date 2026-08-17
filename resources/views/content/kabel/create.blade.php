@extends('layouts/contentNavbarLayout')
@section('title', 'Tambah Kabel FTTH')
@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Kabel /</span> Tambah Kabel Baru</h4>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header"><h6 class="mb-0">Informasi Kabel</h6></div>
      <div class="card-body">
        <form id="kabel-form" action="{{ route('kabel.store') }}" method="POST">
          @csrf
          <input type="hidden" name="geometry" id="geometry-input">

          <div class="mb-3">
            <label class="form-label fw-semibold">Label / Kode Kabel <span class="text-danger">*</span></label>
            <input type="text" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label') }}" placeholder="FDR-OLT01-ODC01" required>
            @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Tipe Kabel</label>
              <select name="tipe" id="tipe" class="form-select">
                <option value="feeder" @selected(old('tipe')=='feeder')>Feeder (OLT→ODC)</option>
                <option value="distribusi" @selected(old('tipe')=='distribusi')>Distribusi (ODC→ODP)</option>
                <option value="drop" @selected(old('tipe')=='drop')>Drop Core (ODP→Pelanggan)</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Monitoring</label>
              <select name="monitoring_type" class="form-select">
                <option value="manual" @selected(old('monitoring_type')=='manual')>Manual / Inferensi</option>
                <option value="realtime" @selected(old('monitoring_type')=='realtime')>Realtime (RFTS/OTDR)</option>
              </select>
            </div>
          </div>

          {{-- From Node --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Dari (Asal)</label>
            <div class="row g-2">
              <div class="col-5">
                <select name="from_type" id="from_type" class="form-select form-select-sm" onchange="updateFromList()">
                  <option value="olt">OLT</option>
                  <option value="odc">ODC</option>
                  <option value="odp">ODP</option>
                </select>
              </div>
              <div class="col-7">
                <select name="from_id" id="from_id" class="form-select form-select-sm">
                  @foreach($olts as $o)
                    <option value="{{ $o->id }}">{{ $o->nama }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          {{-- To Node --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Ke (Tujuan)</label>
            <div class="row g-2">
              <div class="col-5">
                <select name="to_type" id="to_type" class="form-select form-select-sm" onchange="updateToList()">
                  <option value="odc">ODC</option>
                  <option value="odp">ODP</option>
                  <option value="pelanggan">Pelanggan</option>
                </select>
              </div>
              <div class="col-7">
                <select name="to_id" id="to_id" class="form-select form-select-sm">
                  @foreach($odcs as $o)
                    <option value="{{ $o->id }}">{{ $o->nama }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Jumlah Core</label>
            <input type="number" name="jumlah_core" class="form-control" value="{{ old('jumlah_core', 6) }}" min="1">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Catatan / Kondisi</label>
            <textarea name="catatan" class="form-control" rows="2" placeholder="Kabel terpasang tgl ..., kondisi ...">{{ old('catatan') }}</textarea>
          </div>

          <div class="alert alert-info py-2 small">
            <i class="bx bx-info-circle me-1"></i>
            Gambar jalur kabel di peta kanan dengan klik titik-titik, lalu klik <strong>Simpan Jalur</strong>.
          </div>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearPolyline()"><i class="bx bx-trash me-1"></i>Hapus Jalur</button>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="savePolyline()"><i class="bx bx-check me-1"></i>Simpan Jalur</button>
          </div>

          <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="btn-submit" disabled>
              <i class="bx bx-save me-1"></i>Simpan Kabel
            </button>
            <a href="{{ route('kabel.index') }}" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Peta untuk menggambar polyline --}}
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header"><h6 class="mb-0">Gambar Jalur Kabel di Peta</h6></div>
      <div class="card-body p-2">
        <div id="draw-map" style="height:520px;border-radius:8px;"></div>
        <p class="text-muted small mt-2 mb-0"><i class="bx bx-mouse-alt me-1"></i>Klik peta untuk menambah titik jalur kabel. Klik titik pertama untuk menutup jalur.</p>
        <div id="point-count" class="badge bg-label-primary mt-1">0 titik</div>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Node data dari Laravel
var nodes = {
  olt:      @json($olts->map(fn($o)=>['id'=>$o->id,'nama'=>$o->nama])),
  odc:      @json($odcs->map(fn($o)=>['id'=>$o->id,'nama'=>$o->nama])),
  odp:      @json($odps->map(fn($o)=>['id'=>$o->id,'nama'=>$o->nama])),
  pelanggan:@json($pelanggan->map(fn($p)=>['id'=>$p->id_pelanggan,'nama'=>$p->nama_pelanggan.' ('.$p->kode_pelanggan.')'])),
};

function updateFromList() {
  var type = document.getElementById('from_type').value;
  updateSelect('from_id', nodes[type] || []);
}
function updateToList() {
  var type = document.getElementById('to_type').value;
  updateSelect('to_id', nodes[type] || []);
}
function updateSelect(id, data) {
  var sel = document.getElementById(id);
  sel.innerHTML = '';
  data.forEach(function(n) {
    var o = document.createElement('option');
    o.value = n.id; o.text = n.nama; sel.appendChild(o);
  });
}

// Peta & gambar polyline
var drawMap = L.map('draw-map').setView([-7.1207, 112.5959], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OSM' }).addTo(drawMap);

var points  = [];
var tempLine = null;
var markers  = [];

drawMap.on('click', function(e) {
  var latlng = e.latlng;
  points.push([latlng.lat, latlng.lng]);

  var m = L.circleMarker(latlng, { radius:6, color:'#007bff', fillColor:'#007bff', fillOpacity:1 });
  m.addTo(drawMap);
  markers.push(m);

  if (tempLine) drawMap.removeLayer(tempLine);
  if (points.length > 1) {
    tempLine = L.polyline(points, { color:'#007bff', weight:3, dashArray:'6,4' }).addTo(drawMap);
  }

  document.getElementById('point-count').textContent = points.length + ' titik';
});

function clearPolyline() {
  points = [];
  markers.forEach(function(m) { drawMap.removeLayer(m); });
  markers = [];
  if (tempLine) { drawMap.removeLayer(tempLine); tempLine = null; }
  document.getElementById('geometry-input').value = '';
  document.getElementById('btn-submit').disabled = true;
  document.getElementById('point-count').textContent = '0 titik';
}

function savePolyline() {
  if (points.length < 2) { alert('Minimal 2 titik untuk membuat jalur kabel!'); return; }
  document.getElementById('geometry-input').value = JSON.stringify(points);
  document.getElementById('btn-submit').disabled = false;
  if (tempLine) tempLine.setStyle({ dashArray: null, color:'#28a745', weight:4 });
  alert('✅ Jalur disimpan (' + points.length + ' titik). Klik "Simpan Kabel" untuk submit.');
}
</script>
@endsection

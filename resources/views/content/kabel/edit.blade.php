@extends('layouts/contentNavbarLayout')
@section('title', 'Edit Kabel')
@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Kabel /</span> Edit: {{ $kabel->label }}</h4>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header"><h6 class="mb-0">Informasi Kabel</h6></div>
      <div class="card-body">
        <form id="kabel-form" action="{{ route('kabel.update', $kabel) }}" method="POST">
          @csrf @method('PUT')
          <input type="hidden" name="geometry" id="geometry-input">

          <div class="mb-3">
            <label class="form-label fw-semibold">Label / Kode Kabel <span class="text-danger">*</span></label>
            <input type="text" name="label" class="form-control" value="{{ old('label', $kabel->label) }}" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Tipe</label>
              <select name="tipe" class="form-select">
                @foreach(['feeder','distribusi','drop'] as $t)
                  <option value="{{ $t }}" @selected(old('tipe', $kabel->tipe)===$t)>{{ ucfirst($t) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Monitoring</label>
              <select name="monitoring_type" class="form-select">
                <option value="manual"   @selected(old('monitoring_type',$kabel->monitoring_type)==='manual')>Manual</option>
                <option value="realtime" @selected(old('monitoring_type',$kabel->monitoring_type)==='realtime')>Realtime (RFTS)</option>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Jumlah Core</label>
              <input type="number" name="jumlah_core" class="form-control" value="{{ old('jumlah_core', $kabel->jumlah_core) }}" min="1">
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select">
                @foreach(['online','warning','offline'] as $s)
                  <option value="{{ $s }}" @selected(old('status',$kabel->status)===$s)>{{ strtoupper($s) }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Catatan / Kondisi</label>
            <textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $kabel->catatan) }}</textarea>
          </div>

          <div class="alert alert-warning py-2 small">
            <i class="bx bx-info-circle me-1"></i>
            Edit jalur kabel di peta lalu klik <strong>Simpan Jalur</strong>, atau biarkan kosong untuk tidak mengubah geometri.
          </div>

          <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearPolyline()">Hapus Jalur Baru</button>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="savePolyline()">Simpan Jalur</button>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan Perubahan</button>
            <a href="{{ route('kabel.index') }}" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header"><h6 class="mb-0">Jalur Kabel (Edit / Gambar Ulang)</h6></div>
      <div class="card-body p-2">
        <div id="draw-map" style="height:500px;border-radius:8px;"></div>
        <p class="text-muted small mt-2 mb-0">Jalur lama ditampilkan biru. Klik peta untuk menggambar ulang.</p>
        <div id="point-count" class="badge bg-label-primary mt-1">0 titik baru</div>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var existingGeometry = @json($kabel->geometry ?? []);

var drawMap = L.map('draw-map').setView([-7.1207, 112.5959], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OSM' }).addTo(drawMap);

// Tampilkan jalur lama
if (existingGeometry && existingGeometry.length > 1) {
  var oldLine = L.polyline(existingGeometry, { color:'#007bff', weight:4, opacity:0.6 }).addTo(drawMap);
  drawMap.fitBounds(oldLine.getBounds(), { padding:[30,30] });
}

var points=[], tempLine=null, markers=[];

drawMap.on('click', function(e) {
  var latlng = e.latlng;
  points.push([latlng.lat, latlng.lng]);
  var m = L.circleMarker(latlng, { radius:5, color:'#e67e22', fillColor:'#e67e22', fillOpacity:1 }).addTo(drawMap);
  markers.push(m);
  if (tempLine) drawMap.removeLayer(tempLine);
  if (points.length > 1) tempLine = L.polyline(points, { color:'#e67e22', weight:3, dashArray:'5,4' }).addTo(drawMap);
  document.getElementById('point-count').textContent = points.length + ' titik baru';
});

function clearPolyline() {
  points = [];
  markers.forEach(function(m){ drawMap.removeLayer(m); });
  markers = [];
  if (tempLine) { drawMap.removeLayer(tempLine); tempLine = null; }
  document.getElementById('geometry-input').value = '';
  document.getElementById('point-count').textContent = '0 titik baru';
}

function savePolyline() {
  if (points.length < 2) { alert('Minimal 2 titik!'); return; }
  document.getElementById('geometry-input').value = JSON.stringify(points);
  if (tempLine) tempLine.setStyle({ dashArray:null, color:'#28a745', weight:4 });
  alert('✅ Jalur baru disimpan. Klik "Simpan Perubahan" untuk submit.');
}
</script>
@endsection

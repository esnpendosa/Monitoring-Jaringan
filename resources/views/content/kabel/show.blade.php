@extends('layouts/contentNavbarLayout')
@section('title', 'Detail Kabel')
@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Kabel /</span> {{ $kabel->label }}</h4>

<div class="row g-4">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Informasi Kabel</h6>
        <a href="{{ route('kabel.edit', $kabel) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i></a>
      </div>
      <div class="card-body">
        @php $sc=['online'=>'success','warning'=>'warning','offline'=>'danger'][$kabel->status]??'secondary' @endphp
        <span class="badge bg-{{ $sc }} fs-6 mb-3">{{ strtoupper($kabel->status) }}</span>
        <table class="table table-sm table-borderless mb-0">
          <tr><th class="text-muted">Tipe</th><td>{{ $kabel->tipe_label }}</td></tr>
          <tr><th class="text-muted">Monitoring</th><td>{{ $kabel->monitoring_type === 'realtime' ? '📡 Realtime (RFTS)' : '🔧 Manual/Inferensi' }}</td></tr>
          <tr><th class="text-muted">Jumlah Core</th><td>{{ $kabel->jumlah_core }} core</td></tr>
          <tr><th class="text-muted">Redaman</th><td>{{ $kabel->redaman_db ? $kabel->redaman_db.' dB' : '-' }}</td></tr>
          <tr><th class="text-muted">Titik Putus</th><td>{{ $kabel->titik_putus_meter ? '±'.$kabel->titik_putus_meter.' m' : '-' }}</td></tr>
          <tr><th class="text-muted">Catatan</th><td>{{ $kabel->catatan ?? '-' }}</td></tr>
          <tr><th class="text-muted">Update By</th><td>{{ $kabel->updated_by ?? '-' }}</td></tr>
          <tr><th class="text-muted">Terakhir Update</th><td>{{ $kabel->updated_at?->format('d/m/Y H:i') }}</td></tr>
        </table>
      </div>
    </div>

    {{-- Riwayat RFTS --}}
    @if($kabel->rftsReadings->isNotEmpty())
    <div class="card shadow-sm mt-3">
      <div class="card-header"><h6 class="mb-0">Riwayat RFTS (10 Terakhir)</h6></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Waktu</th><th>Status</th><th>Redaman</th><th>Jarak</th></tr></thead>
          <tbody>
            @foreach($kabel->rftsReadings as $r)
            <tr>
              <td class="small">{{ $r->waktu_baca->format('d/m H:i') }}</td>
              <td>{{ $r->status_label }}</td>
              <td class="small">{{ $r->redaman ? $r->redaman.' dB' : '-' }}</td>
              <td class="small">{{ $r->jarak_putus_meter ? $r->jarak_putus_meter.'m' : '-' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif
  </div>

  {{-- Peta jalur kabel --}}
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header"><h6 class="mb-0">Jalur Kabel di Peta</h6></div>
      <div class="card-body p-2">
        <div id="kabel-map" style="height:480px;border-radius:8px;"></div>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var geometry = @json($kabel->geometry ?? []);
var status   = '{{ $kabel->status }}';
var colors   = { online:'#28a745', warning:'#ffc107', offline:'#dc3545' };
var color    = colors[status] || '#6c757d';

var map = L.map('kabel-map').setView([-7.1207, 112.5959], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OSM' }).addTo(map);

if (geometry && geometry.length > 1) {
  var weights = { feeder:6, distribusi:4, drop:2 };
  var line = L.polyline(geometry, {
    color: color,
    weight: weights['{{ $kabel->tipe }}'] || 3,
    opacity: 0.9,
  }).addTo(map);
  line.bindTooltip('{{ $kabel->label }} — {{ strtoupper($kabel->status) }}');
  map.fitBounds(line.getBounds(), { padding:[40,40] });

  // Titik awal dan akhir
  L.circleMarker(geometry[0], { radius:8, color:'#28a745', fillColor:'#28a745', fillOpacity:1 })
    .bindTooltip('Awal kabel').addTo(map);
  L.circleMarker(geometry[geometry.length-1], { radius:8, color:'#dc3545', fillColor:'#dc3545', fillOpacity:1 })
    .bindTooltip('Ujung kabel').addTo(map);

  @if($kabel->titik_putus_meter)
  // Marker titik putus perkiraan
  var breakPoint = geometry[Math.floor(geometry.length / 2)];
  L.marker(breakPoint, {
    icon: L.divIcon({ className:'', html:'<div style="font-size:24px;">⚠️</div>', iconSize:[24,24] })
  }).bindPopup('Titik putus perkiraan ±{{ $kabel->titik_putus_meter }} m dari asal').addTo(map);
  @endif
} else {
  map.setView([-7.1207, 112.5959], 13);
  L.popup().setLatLng([-7.1207, 112.5959]).setContent('<p class="p-2">Belum ada data geometri jalur untuk kabel ini.</p>').openOn(map);
}
</script>
@endsection

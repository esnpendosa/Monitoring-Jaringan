@extends('layouts/contentNavbarLayout')
@section('title', 'Edit OLT')
@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">OLT /</span> Edit: {{ $olt->nama }}</h4>

<div class="card shadow-sm" style="max-width:720px;">
  <div class="card-body">
    <form action="{{ route('olt.update', $olt) }}" method="POST">
      @csrf @method('PUT')
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label fw-semibold">Nama OLT <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $olt->nama) }}" required>
          @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            @foreach(['online','warning','offline'] as $s)
              <option value="{{ $s }}" @selected(old('status', $olt->status) === $s)>{{ strtoupper($s) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">IP Address</label>
          <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror" value="{{ old('ip_address', $olt->ip_address) }}">
          @error('ip_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">SNMP Community</label>
          <input type="text" name="snmp_community" class="form-control" value="{{ old('snmp_community', $olt->snmp_community) }}">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Kapasitas PON</label>
          <input type="number" name="kapasitas_pon" class="form-control" value="{{ old('kapasitas_pon', $olt->kapasitas_pon) }}" min="1">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Latitude</label>
          <input type="text" name="latitude" id="lat" class="form-control" value="{{ old('latitude', $olt->latitude) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Longitude</label>
          <input type="text" name="longitude" id="lng" class="form-control" value="{{ old('longitude', $olt->longitude) }}">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Lokasi / Alamat</label>
          <input type="text" name="lokasi" class="form-control" value="{{ old('lokasi', $olt->lokasi) }}">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Deskripsi</label>
          <textarea name="deskripsi" class="form-control" rows="2">{{ old('deskripsi', $olt->deskripsi) }}</textarea>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Geser pin di peta</label>
          <div id="pick-map" style="height:280px;border-radius:8px;border:1px solid #dee2e6;"></div>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan Perubahan</button>
        <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  var initLat = {{ $olt->latitude ?? -7.1207 }};
  var initLng = {{ $olt->longitude ?? 112.5959 }};
  var pickMap = L.map('pick-map').setView([initLat, initLng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OSM' }).addTo(pickMap);
  var pin = L.marker([initLat, initLng], { draggable: true }).addTo(pickMap);
  pin.on('dragend', function(e) {
    document.getElementById('lat').value = e.target.getLatLng().lat.toFixed(8);
    document.getElementById('lng').value = e.target.getLatLng().lng.toFixed(8);
  });
  pickMap.on('click', function(e) {
    pin.setLatLng(e.latlng);
    document.getElementById('lat').value = e.latlng.lat.toFixed(8);
    document.getElementById('lng').value = e.latlng.lng.toFixed(8);
  });
</script>
@endsection

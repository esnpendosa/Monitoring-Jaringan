@extends('layouts/contentNavbarLayout')
@section('title', 'Tambah OLT')
@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">OLT /</span> Tambah OLT Baru</h4>

<div class="card shadow-sm" style="max-width:720px;">
  <div class="card-body">
    <form action="{{ route('olt.store') }}" method="POST">
      @csrf
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label fw-semibold">Nama OLT <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" placeholder="Contoh: OLT-Pusat-01" required>
          @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Kapasitas PON (port)</label>
          <input type="number" name="kapasitas_pon" class="form-control" value="{{ old('kapasitas_pon', 16) }}" min="1">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">IP Address</label>
          <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror" value="{{ old('ip_address') }}" placeholder="192.168.1.1">
          @error('ip_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">SNMP Community</label>
          <input type="text" name="snmp_community" class="form-control" value="{{ old('snmp_community', 'public') }}">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Latitude</label>
          <input type="text" name="latitude" id="lat" class="form-control" value="{{ old('latitude') }}" placeholder="-7.1207">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Longitude</label>
          <input type="text" name="longitude" id="lng" class="form-control" value="{{ old('longitude') }}" placeholder="112.5959">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Lokasi / Alamat</label>
          <input type="text" name="lokasi" class="form-control" value="{{ old('lokasi') }}" placeholder="Jl. ...">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Deskripsi</label>
          <textarea name="deskripsi" class="form-control" rows="2" placeholder="Keterangan tambahan...">{{ old('deskripsi') }}</textarea>
        </div>
        {{-- Mini map untuk pilih koordinat --}}
        <div class="col-12">
          <label class="form-label fw-semibold">Pilih Koordinat di Peta</label>
          <div id="pick-map" style="height:300px;border-radius:8px;border:1px solid #dee2e6;"></div>
          <small class="text-muted">Klik peta untuk mengisi koordinat otomatis.</small>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan OLT</button>
        <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  var pickMap = L.map('pick-map').setView([-7.1207, 112.5959], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OSM' }).addTo(pickMap);
  var pin = null;
  pickMap.on('click', function(e) {
    document.getElementById('lat').value = e.latlng.lat.toFixed(8);
    document.getElementById('lng').value = e.latlng.lng.toFixed(8);
    if (pin) pin.setLatLng(e.latlng); else pin = L.marker(e.latlng).addTo(pickMap);
  });
</script>
@endsection

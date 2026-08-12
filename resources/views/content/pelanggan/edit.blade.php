@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Pelanggan')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Pelanggan /</span> Edit</h4>

<div class="card">
  <div class="card-body">
    <form action="{{ route('pelanggan.update', $pelanggan->id_pelanggan) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Kode Pelanggan</label>
          <input type="text" name="kode_pelanggan" class="form-control" value="{{ $pelanggan->kode_pelanggan }}" readonly />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Nama Pelanggan</label>
          <input type="text" name="nama_pelanggan" class="form-control" value="{{ $pelanggan->nama_pelanggan }}" required />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">No. WhatsApp</label>
          <input type="text" name="no_wa" class="form-control" value="{{ $pelanggan->no_wa }}" placeholder="6281xxx" />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Email (Untuk Akun Login)</label>
          <input type="email" name="email" class="form-control" value="{{ $pelanggan->email }}" placeholder="pelanggan@example.com" />
          <small class="text-muted">Digunakan untuk email login akun pelanggan.</small>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Alamat</label>
          <textarea name="alamat" class="form-control" required>{{ $pelanggan->alamat }}</textarea>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Foto Rumah</label>
          <input type="file" name="foto_rumah" class="form-control" accept="image/*" />
          <small class="text-muted">Opsional, upload foto baru untuk mengganti foto lama.</small>
          @if($pelanggan->foto_rumah)
            <div class="mt-2">
              <a href="{{ asset('storage/' . $pelanggan->foto_rumah) }}" target="_blank">
                <img src="{{ asset('storage/' . $pelanggan->foto_rumah) }}" alt="Foto Rumah" class="img-thumbnail" style="max-height: 100px;" />
              </a>
              <div class="small text-muted">Klik gambar untuk memperbesar</div>
            </div>
          @endif
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Latitude</label>
          <input type="text" name="latitude" id="lat" class="form-control" value="{{ $pelanggan->latitude }}" required />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Longitude</label>
          <input type="text" name="longitude" id="lng" class="form-control" value="{{ $pelanggan->longitude }}" required />
        </div>
        <div class="col-md-12 mb-2 d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" onclick="getMyLocation()">
                <i class="bx bx-current-location me-1"></i> Gunakan Lokasi Saya Sekarang
            </button>
            @if($pelanggan->latitude && $pelanggan->longitude)
            <a href="https://www.google.com/maps?q={{ $pelanggan->latitude }},{{ $pelanggan->longitude }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-map-alt me-1"></i> Buka di Google Maps
            </a>
            @endif
        </div>
        <div class="col-md-12 mb-3">
            <div id="map" style="height: 300px; border-radius: 8px;"></div>
            <small class="text-muted">Tarik marker atau klik pada peta untuk mengubah lokasi</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Usage (GB)</label>
            <input type="number" name="usage_gb" class="form-control" value="{{ $pelanggan->usage_gb }}" />
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Jumlah Device</label>
            <input type="number" name="jumlah_device" class="form-control" value="{{ $pelanggan->jumlah_device }}" />
        </div>
        <hr>
        <h5>Mapping Mikrotik & Billing</h5>
        <div class="col-md-6 mb-3">
            <label class="form-label">Router</label>
            <select name="id_router" class="form-select">
                <option value="">-- Pilih Router --</option>
                @foreach($routers as $r)
                <option value="{{ $r->id_router }}" {{ $pelanggan->id_router == $r->id_router ? 'selected' : '' }}>{{ $r->nama_router }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Username Mikrotik (Kosongkan jika sama dengan Kode Pelanggan)</label>
            <input type="text" name="mikrotik_username" class="form-control" value="{{ $pelanggan->mikrotik_username }}" placeholder="Contoh: KTR01" />
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Tipe Layanan</label>
            <select name="mikrotik_type" class="form-select">
                <option value="pppoe" {{ $pelanggan->mikrotik_type == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                <option value="hotspot" {{ $pelanggan->mikrotik_type == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                <option value="static" {{ $pelanggan->mikrotik_type == 'static' ? 'selected' : '' }}>Static/Firewall</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Paket maks</label>
            <input type="text" name="paket" id="paket_select" list="paket_list" class="form-control" value="{{ $pelanggan->paket ?? 'umum' }}" placeholder="Masukkan atau pilih paket..." required />
            <datalist id="paket_list">
                <option value="umum">umum</option>
                <option value="100rb 3mb">100rb 3mb</option>
                <option value="120rb 8mb">120rb 8mb</option>
                <option value="130rb 12mb">130rb 12mb</option>
                <option value="150rb 20mb">150rb 20mb</option>
                <option value="200rb 35mb">200rb 35mb</option>
            </datalist>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Harga Layanan (Rp)</label>
            <input type="number" name="harga_layanan" id="harga_layanan" class="form-control" value="{{ $pelanggan->harga_layanan }}" required />
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Status Aktif</label>
            <select name="is_active" class="form-select">
                <option value="1" {{ $pelanggan->is_active ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ !$pelanggan->is_active ? 'selected' : '' }}>Non-Aktif</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Notifikasi WhatsApp Pelanggan</label>
            <select name="wa_active" class="form-select">
                <option value="1" {{ $pelanggan->wa_active ? 'selected' : '' }}>Aktif (Kirim WA)</option>
                <option value="0" {{ !$pelanggan->wa_active ? 'selected' : '' }}>Non-Aktif (Matikan WA)</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Alamat IP (Static/ONT)</label>
            <input type="text" name="ip_address" class="form-control" value="{{ $pelanggan->ip_address }}" placeholder="192.168.1.1" />
            <small class="text-muted">Diisi jika ingin monitoring IP spesifik secara manual</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Tanggal Penagihan (Billing Date)</label>
            <select name="billing_date" class="form-select" required>
                @for($i=1; $i<=28; $i++)
                <option value="{{ $i }}" {{ $pelanggan->billing_date == $i ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                @endfor
            </select>
            <small class="text-muted">Tagihan otomatis dibuat setiap bulan pada tanggal ini.</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Tanggal Pasang (PSB)</label>
            <input type="date" name="tanggal_pasang" class="form-control" value="{{ $pelanggan->tanggal_pasang ? \Carbon\Carbon::parse($pelanggan->tanggal_pasang)->format('Y-m-d') : date('Y-m-d') }}" />
            <small class="text-muted">Tanggal pemasangan internet baru.</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label d-block">Opsi Bonus Gratis Pemasangan</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="gratis_pemasangan" id="gratis_pemasangan" value="1" {{ $pelanggan->gratis_pemasangan ? 'checked' : '' }}>
                <label class="form-check-label" for="gratis_pemasangan">Aktifkan Bonus Gratis Layanan 2 Bulan Pertama</label>
            </div>
            <small class="text-muted">Jika diaktifkan, 2 bulan pertama gratis (Jika pasang &lt; tgl 27, gratis terhitung bulan ini. Jika &gt;= tgl 27, gratis mulai bulan depan).</small>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Update</button>
      <a href="{{ route('pelanggan.index') }}" class="btn btn-label-secondary">Batal</a>
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var lat = {{ $pelanggan->latitude ?: -7.0 }};
    var lng = {{ $pelanggan->longitude ?: 112.0 }};
    var map, marker;

    document.addEventListener('DOMContentLoaded', function() {
        map = L.map('map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        marker = L.marker([lat, lng], {draggable: true}).addTo(map);

        marker.on('dragend', function(e) {
            var position = marker.getLatLng();
            document.getElementById('lat').value = position.lat.toFixed(8);
            document.getElementById('lng').value = position.lng.toFixed(8);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value = e.latlng.lat.toFixed(8);
            document.getElementById('lng').value = e.latlng.lng.toFixed(8);
        });

        // Dynamic MikroTik profiles logic
        const routerSelect = document.querySelector('select[name="id_router"]');
        const typeSelect = document.querySelector('select[name="mikrotik_type"]');
        const paketSelect = document.getElementById('paket_select');
        const paketDatalist = document.getElementById('paket_list');
        const hargaInput = document.getElementById('harga_layanan');

        const paketPrices = {
            'umum': 100000,
            '100rb 3mb': 100000,
            '120rb 8mb': 120000,
            '130rb 12mb': 130000,
            '150rb 20mb': 150000,
            '200rb 35mb': 200000
        };

        const defaultOptions = `
            <option value="umum">umum</option>
            <option value="100rb 3mb">100rb 3mb</option>
            <option value="120rb 8mb">120rb 8mb</option>
            <option value="130rb 12mb">130rb 12mb</option>
            <option value="150rb 20mb">150rb 20mb</option>
            <option value="200rb 35mb">200rb 35mb</option>
        `;

        const currentSelected = "{{ $pelanggan->paket ?? '' }}";

        function guessPrice(profileName) {
            const lower = profileName.toLowerCase();
            if (lower === 'umum') return 100000;
            if (lower.includes('100')) return 100000;
            if (lower.includes('120')) return 120000;
            if (lower.includes('130')) return 130000;
            if (lower.includes('150')) return 150000;
            if (lower.includes('200')) return 200000;
            if (lower.includes('3mb') || lower.includes('3m')) return 100000;
            if (lower.includes('8mb') || lower.includes('8m')) return 120000;
            if (lower.includes('12mb') || lower.includes('12m')) return 130000;
            if (lower.includes('20mb') || lower.includes('20m')) return 150000;
            if (lower.includes('35mb') || lower.includes('35m')) return 200000;
            return '';
        }

        function loadProfiles() {
            const routerId = routerSelect.value;
            const type = typeSelect.value;

            if (!routerId || type === 'static') {
                paketDatalist.innerHTML = defaultOptions;
                return;
            }

            fetch(`/mikrotik/${routerId}/profiles/${type}`)
                .then(response => response.json())
                .then(profiles => {
                    let html = '<option value="umum">umum</option>';
                    profiles.forEach(p => {
                        html += `<option value="${p}">${p}</option>`;
                    });
                    paketDatalist.innerHTML = html;
                })
                .catch(err => {
                    console.error('Gagal memuat profil:', err);
                    paketDatalist.innerHTML = defaultOptions;
                });
        }

        routerSelect.addEventListener('change', loadProfiles);
        typeSelect.addEventListener('change', loadProfiles);

        paketSelect.addEventListener('input', function() {
            const selected = this.value;
            if (paketPrices[selected]) {
                hargaInput.value = paketPrices[selected];
            } else {
                const guessed = guessPrice(selected);
                if (guessed) {
                    hargaInput.value = guessed;
                }
            }
        });

        // Trigger loading if router is pre-selected
        if (routerSelect.value) {
            loadProfiles();
        }
    });

    function getMyLocation() {
        if (!navigator.geolocation) {
            alert('Browser Anda tidak mendukung fitur geolokasi.');
            return;
        }
        var btn = event.target.closest('button');
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Mengambil lokasi...';
        btn.disabled = true;

        navigator.geolocation.getCurrentPosition(function(position) {
            var myLat = position.coords.latitude;
            var myLng = position.coords.longitude;

            document.getElementById('lat').value = myLat.toFixed(8);
            document.getElementById('lng').value = myLng.toFixed(8);

            map.setView([myLat, myLng], 17);
            marker.setLatLng([myLat, myLng]);

            btn.innerHTML = '<i class="bx bx-check me-1"></i> Lokasi Ditemukan!';
            btn.classList.replace('btn-success', 'btn-primary');
            setTimeout(function() {
                btn.innerHTML = '<i class="bx bx-current-location me-1"></i> Gunakan Lokasi Saya Sekarang';
                btn.classList.replace('btn-primary', 'btn-success');
                btn.disabled = false;
            }, 3000);
        }, function(error) {
            alert('Gagal mendapatkan lokasi: ' + error.message + '\n\nPastikan izin lokasi sudah diaktifkan di browser.');
            btn.innerHTML = '<i class="bx bx-current-location me-1"></i> Gunakan Lokasi Saya Sekarang';
            btn.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000 });
    }
</script>
@endsection

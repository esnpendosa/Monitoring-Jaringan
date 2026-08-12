@extends('layouts/contentNavbarLayout')

@section('title', 'Tambah Pelanggan')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Pelanggan /</span> Tambah</h4>

<div class="card">
  <div class="card-body">
    <form action="{{ route('pelanggan.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Kode Pelanggan</label>
          <div class="input-group">
            <input type="text" name="kode_pelanggan" id="kode_pelanggan" class="form-control" placeholder="PEL001" required />
            <button class="btn btn-outline-secondary" type="button" id="btn-suggest-code" title="Ambil Kode Urut Berikutnya">
              <i class="bx bx-refresh me-1"></i> Auto
            </button>
          </div>
          <div id="code-suggestion-area" class="mt-1" style="display: none;">
            <span class="badge bg-label-success" style="cursor: pointer; font-size: 0.85rem;" id="code-suggestion-text"></span>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Nama Pelanggan</label>
          <input type="text" name="nama_pelanggan" class="form-control" required />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">No. WhatsApp</label>
          <input type="text" name="no_wa" class="form-control" placeholder="6281xxx" />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Email (Untuk Akun Login)</label>
          <input type="email" name="email" class="form-control" placeholder="pelanggan@example.com" />
          <small class="text-muted">Kosongkan jika ingin generate otomatis: (kode)@rtrwnet.com</small>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Alamat</label>
          <textarea name="alamat" class="form-control" required></textarea>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Foto Rumah</label>
          <input type="file" name="foto_rumah" class="form-control" accept="image/*" />
          <small class="text-muted">Opsional, upload foto depan rumah.</small>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Latitude</label>
          <input type="text" name="latitude" id="lat" class="form-control" value="{{ $lat ?? '' }}" required />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Longitude</label>
          <input type="text" name="longitude" id="lng" class="form-control" value="{{ $lng ?? '' }}" required />
        </div>
        <div class="col-md-12 mb-3">
            <div id="map" style="height: 300px; border-radius: 8px;"></div>
            <small class="text-muted">Klik pada peta untuk menentukan lokasi</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Usage (GB)</label>
            <input type="number" name="usage_gb" class="form-control" value="0" />
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Jumlah Device</label>
            <input type="number" name="jumlah_device" class="form-control" value="0" />
        </div>
        <hr>
        <h5>Mapping Mikrotik & Billing</h5>
        <div class="col-md-6 mb-3">
            <label class="form-label">Router</label>
            <select name="id_router" class="form-select">
                <option value="">-- Pilih Router --</option>
                @foreach($routers as $r)
                <option value="{{ $r->id_router }}">{{ $r->nama_router }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Tipe Layanan</label>
            <select name="mikrotik_type" class="form-select">
                <option value="pppoe">PPPoE</option>
                <option value="hotspot">Hotspot</option>
                <option value="static">Static/Firewall</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Paket maks</label>
            <input type="text" name="paket" id="paket_select" list="paket_list" class="form-control" value="umum" placeholder="Masukkan atau pilih paket..." required />
            <datalist id="paket_list">
                <option value="umum">umum</option>
                <option value="100rb 3mb">100rb 3mb</option>
                <option value="120rb 8mb">120rb 8mb</option>
                <option value="130rb 12mb">130rb 12mb</option>
                <option value="150rb 20mb">150rb 20mb</option>
                <option value="200rb 35mb">200rb 35mb</option>
            </datalist>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Harga Layanan (Rp)</label>
            <input type="number" name="harga_layanan" id="harga_layanan" class="form-control" placeholder="150000" required />
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Alamat IP (Static/ONT)</label>
            <input type="text" name="ip_address" class="form-control" placeholder="192.168.1.1" />
            <small class="text-muted">Diisi jika ingin monitoring IP spesifik secara manual</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Notifikasi WhatsApp Pelanggan</label>
            <select name="wa_active" class="form-select">
                <option value="1" selected>Aktif (Kirim WA)</option>
                <option value="0">Non-Aktif (Matikan WA)</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Tanggal Penagihan (Billing Date)</label>
            <select name="billing_date" class="form-select" required>
                @for($i=1; $i<=28; $i++)
                <option value="{{ $i }}">Tanggal {{ $i }}</option>
                @endfor
            </select>
            <small class="text-muted">Tagihan otomatis dibuat setiap bulan pada tanggal ini.</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Tanggal Pasang (PSB)</label>
            <input type="date" name="tanggal_pasang" class="form-control" value="{{ date('Y-m-d') }}" />
            <small class="text-muted">Tanggal pemasangan internet baru.</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label d-block">Opsi Bonus Gratis Pemasangan</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="gratis_pemasangan" id="gratis_pemasangan" value="1">
                <label class="form-check-label" for="gratis_pemasangan">Aktifkan Bonus Gratis Layanan 2 Bulan Pertama</label>
            </div>
            <small class="text-muted">Jika diaktifkan, 2 bulan pertama gratis (Jika pasang &lt; tgl 27, gratis terhitung bulan ini. Jika &gt;= tgl 27, gratis mulai bulan depan).</small>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generation & Suggestion Kode Pelanggan logic
        const kodeInput = document.getElementById('kode_pelanggan');
        const suggestBtn = document.getElementById('btn-suggest-code');
        const suggestionArea = document.getElementById('code-suggestion-area');
        const suggestionText = document.getElementById('code-suggestion-text');

        function fetchNextCode(prefix = '') {
            fetch(`/pelanggan/get-next-code?prefix=${prefix}`)
                .then(response => response.json())
                .then(data => {
                    if (data.next_code) {
                        if (!prefix) {
                            // Pre-fill on initial page load
                            kodeInput.value = data.next_code;
                        } else {
                            // Show suggestion below input
                            suggestionText.innerHTML = `Gunakan kode urut berikutnya: <strong>${data.next_code}</strong> <i class="bx bx-check-circle ms-1"></i>`;
                            suggestionArea.style.display = 'block';
                            suggestionText.onclick = function() {
                                kodeInput.value = data.next_code;
                                suggestionArea.style.display = 'none';
                            };
                        }
                    }
                })
                .catch(err => console.error('Error fetching next code:', err));
        }

        // Fetch initial next code based on last added customer
        fetchNextCode();

        // Manual refresh/suggest click
        suggestBtn.addEventListener('click', function() {
            const val = kodeInput.value.trim();
            const match = val.match(/^([a-zA-Z]+)/);
            const prefix = match ? match[1] : '';
            fetchNextCode(prefix);
        });

        // Dynamic typing check
        let typingTimer;
        kodeInput.addEventListener('input', function() {
            clearTimeout(typingTimer);
            suggestionArea.style.display = 'none';
            const val = this.value.trim();
            const match = val.match(/^([a-zA-Z]+)/);
            if (match) {
                const prefix = match[1];
                typingTimer = setTimeout(() => {
                    fetch(`/pelanggan/get-next-code?prefix=${prefix}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.next_code && data.next_code !== val) {
                                suggestionText.innerHTML = `Gunakan kode urut berikutnya: <strong>${data.next_code}</strong> <i class="bx bx-check-circle ms-1"></i>`;
                                suggestionArea.style.display = 'block';
                                suggestionText.onclick = function() {
                                    kodeInput.value = data.next_code;
                                    suggestionArea.style.display = 'none';
                                };
                            }
                        });
                }, 500);
            }
        });

        var initialLat = {{ $lat ?? -7.1207 }};
        var initialLng = {{ $lng ?? 112.5959 }};
        var map = L.map('map').setView([initialLat, initialLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        var marker;
        
        // Jika ada koordinat awal, buat marker
        if ({{ isset($lat) ? 'true' : 'false' }}) {
            marker = L.marker([initialLat, initialLng]).addTo(map);
        }

        map.on('click', function(e) {
            if(marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
            document.getElementById('lat').value = e.latlng.lat;
            document.getElementById('lng').value = e.latlng.lng;
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
    });
</script>
@endsection

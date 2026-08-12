<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrasi Pasang WiFi Baru | RT RW NET</title>
  <!-- Google Fonts: Outfit -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Boxicons -->
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <!-- Bootstrap CSS (Bootstrap 5) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Leaflet Map CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  
  <style>
    body {
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #f5f7fb 0%, #e4e9f2 100%);
      color: #2b3a4a;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px 0;
    }
    
    .register-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.8);
      overflow: hidden;
      max-width: 900px;
      width: 100%;
    }
    
    .gradient-header {
      background: linear-gradient(135deg, #3f51b5 0%, #2196f3 100%);
      color: #fff;
      padding: 30px;
      text-align: center;
    }
    
    .gradient-header h3 {
      font-weight: 700;
      margin-bottom: 5px;
    }
    
    .gradient-header p {
      font-weight: 300;
      opacity: 0.9;
      margin-bottom: 0;
    }
    
    .form-section {
      padding: 40px;
    }
    
    .form-label {
      font-weight: 500;
      font-size: 0.9rem;
      color: #4a5568;
      margin-bottom: 6px;
    }
    
    .form-control, .form-select {
      border-radius: 10px;
      padding: 12px 15px;
      border: 1px solid #cbd5e0;
      transition: all 0.3s ease;
      font-size: 0.95rem;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #2196f3;
      box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.15);
    }
    
    #map {
      height: 320px;
      border-radius: 12px;
      border: 1px solid #cbd5e0;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
    }
    
    .btn-submit {
      background: linear-gradient(135deg, #3f51b5 0%, #2196f3 100%);
      border: none;
      color: white;
      padding: 14px 28px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
    }
    
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
    }
    
    .icon-input {
      position: relative;
    }
    
    .icon-input i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #a0aec0;
      font-size: 1.2rem;
    }
    
    .icon-input .form-control {
      padding-left: 45px;
    }
    
    .map-note {
      background-color: #ebf8ff;
      border-left: 4px solid #3182ce;
      color: #2b6cb0;
      padding: 12px;
      border-radius: 0 8px 8px 0;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>

  <div class="container d-flex justify-content-center">
    <div class="register-card">
      <div class="gradient-header">
        <h3>Registrasi Pasang WiFi</h3>
        <p>Silakan isi form di bawah untuk mendaftarkan rumah Anda ke jaringan RT RW NET kami</p>
      </div>
      
      <form action="{{ route('public.register.store') }}" method="POST" enctype="multipart/form-data" class="form-section">
        @csrf
        
        <div class="row g-4">
          <!-- Nama Pelanggan -->
          <div class="col-md-6">
            <label class="form-label">Nama Lengkap</label>
            <div class="icon-input">
              <i class="bx bx-user"></i>
              <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Budi Santoso" required>
            </div>
          </div>
          
          <!-- No WhatsApp -->
          <div class="col-md-6">
            <label class="form-label">No. WhatsApp Aktif</label>
            <div class="icon-input">
              <i class="bx bxl-whatsapp"></i>
              <input type="text" name="no_wa" class="form-control" placeholder="Contoh: 628123456789" required>
            </div>
            <small class="text-muted">Masukkan format internasional tanpa tanda + (contoh: 6281xxx)</small>
          </div>
          
          <!-- Paket Layanan -->
          <div class="col-md-12">
            <label class="form-label">Pilih Paket Layanan</label>
            <select name="paket" class="form-select" required>
              <option value="" disabled selected>-- Pilih Paket Kecepatan --</option>
              @foreach($packages as $val => $lbl)
              <option value="{{ $val }}">{{ $lbl }}</option>
              @endforeach
            </select>
          </div>
          
          <!-- Foto Rumah -->
          <div class="col-md-12">
            <label class="form-label">Foto Rumah Depan (Opsional)</label>
            <div class="icon-input">
              <i class="bx bx-image-add"></i>
              <input type="file" name="foto_rumah" class="form-control" accept="image/*">
            </div>
            <small class="text-muted">Upload foto bagian depan rumah Anda untuk mempermudah identifikasi lokasi survei.</small>
          </div>

          <!-- Alamat Lengkap -->
          <div class="col-md-12">
            <label class="form-label">Alamat Rumah Lengkap</label>
            <textarea name="alamat" class="form-control" rows="3" placeholder="Nama jalan, RT/RW, Dusun, Desa, Kecamatan..." required></textarea>
          </div>
          
          <!-- Titik Koordinat -->
          <div class="col-md-12">
            <label class="form-label">Tentukan Lokasi Rumah Anda</label>
            <div class="map-note mb-3">
              <i class="bx bx-info-circle me-1"></i> Klik atau geser pin pada peta tepat di atas atap rumah Anda untuk mempermudah survei tim kami.
            </div>
            <div id="map"></div>
          </div>
          
          <!-- Lat / Lng (Hidden or Read-only) -->
          <div class="col-md-6">
            <label class="form-label">Latitude</label>
            <input type="text" name="latitude" id="lat" class="form-control" readonly required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Longitude</label>
            <input type="text" name="longitude" id="lng" class="form-control" readonly required>
          </div>
        </div>
        
        <div class="text-center mt-5">
          <button type="submit" class="btn btn-submit px-5 py-3">
            Kirim Registrasi <i class="bx bx-send ms-1"></i>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Leaflet Map JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lokasi default (Gresik/Leran/RT1)
        var defaultLat = -7.1207;
        var defaultLng = 112.5959;
        
        var map = L.map('map').setView([defaultLat, defaultLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        var marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);
        
        // Set coordinates initially
        document.getElementById('lat').value = defaultLat.toFixed(8);
        document.getElementById('lng').value = defaultLng.toFixed(8);

        // Geolocation fallback
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
                map.setView([userLat, userLng], 17);
                marker.setLatLng([userLat, userLng]);
                document.getElementById('lat').value = userLat.toFixed(8);
                document.getElementById('lng').value = userLng.toFixed(8);
            });
        }

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
    });
  </script>
</body>
</html>

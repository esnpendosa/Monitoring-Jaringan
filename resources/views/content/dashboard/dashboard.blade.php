@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Analytics')

@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('page-script')
@vite('resources/assets/js/dashboards-analytics.js')
@endsection

@section('content')
<div class="row">
  <div class="col-lg-12 mb-4 order-0">
    <div class="card">
      <div class="d-flex align-items-end row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="card-title text-primary">Selamat Datang, {{ auth()->user()->name }}! 🎓</h5>
            <p class="mb-4">Sistem Manajemen Jaringan WiFi Berbasis Web GIS (Rozitech). <br> Penelitian Skripsi oleh Muhammad As'ad Muhibbin Akbar.</p>

            <a href="{{ route('pelanggan.index') }}" class="btn btn-sm btn-outline-primary">Lihat Pelanggan</a>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-start">
          <div class="card-body pb-0 px-0 px-md-4">
            <img src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}" height="140" alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png" data-app-light-img="illustrations/man-with-laptop.png">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-user text-primary" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Total Pelanggan</span>
        <h3 class="card-title mb-2">{{ $stats['total_pelanggan'] }}</h3>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-error-circle text-danger" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Tiket High Priority</span>
        <h3 class="card-title mb-2 text-danger">{{ $stats['gangguan_high'] }}</h3>
        <small class="text-muted">Total Open: {{ $stats['total_gangguan'] }}</small>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-wrench text-warning" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Total Teknisi</span>
        <h3 class="card-title mb-2">{{ $stats['total_teknisi'] }}</h3>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-server text-info" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Router Terhubung</span>
        <h3 class="card-title mb-2">{{ $stats['total_router'] }}</h3>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-4 col-md-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-check-circle text-success" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Tagihan Lunas (Bulan Ini)</span>
        <h3 class="card-title mb-2 text-success">{{ $stats['tagihan_lunas'] }}</h3>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-time text-warning" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Tagihan Belum Bayar (Bulan Ini)</span>
        <h3 class="card-title mb-2 text-danger">{{ $stats['tagihan_unpaid'] }}</h3>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-wallet text-info" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Total Pendapatan (Bulan Ini)</span>
        <h3 class="card-title mb-2">Rp {{ number_format($stats['total_pendapatan'], 0, ',', '.') }}</h3>
        <div class="d-flex justify-content-between text-sm mt-3 border-top pt-2">
          <span class="text-success fw-semibold"><i class='bx bx-money'></i> Cash: Rp {{ number_format($stats['total_pendapatan_cash'], 0, ',', '.') }}</span>
          <span class="text-primary fw-semibold"><i class='bx bx-transfer'></i> TF: Rp {{ number_format($stats['total_pendapatan_transfer'], 0, ',', '.') }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card bg-label-success">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-check-double text-success" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Total Tagihan Lunas (Semua)</span>
        <h3 class="card-title mb-2 text-success">{{ $stats['total_tagihan_lunas'] }}</h3>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card bg-label-danger">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-history text-danger" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Total Belum Bayar (Semua)</span>
        <h3 class="card-title mb-2 text-danger">{{ $stats['total_tagihan_unpaid'] }}</h3>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card bg-label-warning">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-trending-down text-warning" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">Total Pengeluaran</span>
        <h3 class="card-title mb-2 text-warning">Rp {{ number_format($stats['total_pengeluaran'], 0, ',', '.') }}</h3>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card bg-label-primary">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <i class="icon-base bx bx-plus-circle text-primary" style="font-size: 2rem;"></i>
          </div>
        </div>
        <span class="fw-semibold d-block mb-1">PSB Pasang Baru</span>
        <h3 class="card-title mb-2 text-primary">Rp {{ number_format($stats['total_psb'], 0, ',', '.') }}</h3>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-6 col-md-6 mb-4">
    <div class="card" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: none;">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <span class="avatar-initial rounded bg-label-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
              <i class="bx bx-wrench text-primary fs-4"></i>
            </span>
          </div>
          <a href="{{ route('inventory.index') }}" class="btn btn-xs btn-outline-primary">Kelola</a>
        </div>
        <span class="fw-semibold d-block mb-1">Aset Alat & Peralatan (Inventaris)</span>
        <h3 class="card-title mb-2 text-dark">Rp {{ number_format($totalInventoryValue, 0, ',', '.') }}</h3>
        <small class="text-muted"><i class="bx bx-package"></i> {{ $totalInventoryItems }} Jenis Alat Terdaftar</small>
      </div>
    </div>
  </div>
  
  <div class="col-lg-6 col-md-6 mb-4">
    <div class="card" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: none;">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <span class="avatar-initial rounded bg-label-danger p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
              <i class="bx bx-wallet text-danger fs-4"></i>
            </span>
          </div>
          <a href="{{ route('kas-bon.index') }}" class="btn btn-xs btn-outline-danger">Kelola</a>
        </div>
        <span class="fw-semibold d-block mb-1">Kas Bon Pekerja (Belum Lunas)</span>
        <h3 class="card-title mb-2 text-danger">Rp {{ number_format($totalKasBonOutstanding, 0, ',', '.') }}</h3>
        <small class="text-muted"><i class="bx bx-user"></i> Pinjaman Aktif Teknisi</small>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0">Sebaran Pelanggan (Web GIS)</h5>
        <div>
          <span class="badge bg-success me-1">Online</span>
          <span class="badge bg-warning me-1">Offline</span>
          <span class="badge bg-danger me-1">Isolir</span>
          <span class="badge bg-primary">Perbaikan</span>
        </div>
      </div>
      <div class="card-body p-0">
        <div id="map" style="height: 400px; border-radius: 0 0 8px 8px;"></div>
      </div>
    </div>
  </div>
  
  <div class="col-md-6 col-lg-4 order-1 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0 me-2">Tiket Gangguan Terbaru</h5>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0">
          @foreach($recentTiket as $tiket)
          <li class="d-flex mb-4 pb-1">
            <div class="avatar flex-shrink-0 me-3">
              <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-error"></i></span>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <h6 class="mb-0">{{ $tiket->pelanggan->nama_pelanggan }}</h6>
                <small class="text-muted">{{ $tiket->kode_tiket }}</small>
              </div>
              <div class="user-progress text-danger">
                <small class="fw-semibold">{{ $tiket->prioritas }}</small>
              </div>
            </div>
          </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('map').setView([-7.1207, 112.5959], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var customers = @json($pelangganMap);

    customers.forEach(function(c) {
      if (!c.latitude || !c.longitude) return;

      // Warna sama persis dengan Web GIS Pelanggan
      var color = '#28a745'; // Online - Hijau
      if (c.status_gis === 'offline')   color = '#ffc107'; // Offline - Kuning
      if (c.status_gis === 'timeout')   color = '#dc3545'; // Isolir - Merah
      if (c.status_gis === 'perbaikan') color = '#007bff'; // Perbaikan - Biru

      var statusLabel = '🟢 ONLINE';
      if (c.status_gis === 'offline')   statusLabel = '🟡 OFFLINE (LOSS)';
      if (c.status_gis === 'timeout')   statusLabel = '🔴 ISOLIR';
      if (c.status_gis === 'perbaikan') statusLabel = '🔵 PERBAIKAN';

      var tagihanInfo = c.tagihan && c.tagihan.length > 0
        ? (['unpaid','belum_bayar'].includes(c.tagihan[0].status) ? '❌ BELUM BAYAR' : '✅ LUNAS')
        : 'Tidak ada';

      var marker = L.circleMarker([c.latitude, c.longitude], {
        radius: 9,
        fillColor: color,
        color: '#fff',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.9
      });

      marker.bindPopup(`
        <div style="min-width:200px;">
          <h6 class="mb-1">${c.nama_pelanggan}</h6>
          <p class="mb-1 small text-muted">${c.kode_pelanggan} | ${c.ip_address || '-'}</p>
          <p class="mb-1 small">Status: <strong>${statusLabel}</strong></p>
          <hr class="my-1">
          <p class="mb-1 small">Tagihan: ${tagihanInfo}</p>
          <p class="mb-2 small">${c.alamat}</p>
          <div class="d-flex justify-content-between align-items-center">
            <a href="https://www.google.com/maps?q=${c.latitude},${c.longitude}" target="_blank" style="font-size:10px;" class="btn btn-xs btn-outline-secondary">🗺️ Maps</a>
            <a href="/pelanggan/${c.id_pelanggan}/edit" style="padding:2px 5px;font-size:10px;color:white;" class="btn btn-xs btn-primary">Edit</a>
          </div>
        </div>
      `);

      marker.addTo(map);
    });

    // Fit map to all markers
    var bounds = customers.filter(c => c.latitude && c.longitude).map(c => [c.latitude, c.longitude]);
    if (bounds.length > 0) map.fitBounds(bounds, { padding: [30, 30] });
  });
</script>
@endsection

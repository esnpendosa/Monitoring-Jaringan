@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard Pelanggan')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}">
@endsection

@section('page-style')
<style>
    .card-id-container {
        width: 100%;
        max-width: 450px;
        margin: 0 auto;
    }
    .card-customer {
        width: 100%;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        position: relative;
        overflow: hidden;
        border: 1px solid #e1e4e8;
        padding: 0;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
    }

    .card-header-id {
        background: linear-gradient(135deg, #696cff 0%, #3f4191 100%);
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
    }

    .logo-text-id h1 {
        margin: 0;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 800;
        color: white;
    }

    .logo-text-id p {
        margin: 0;
        font-size: 8px;
        opacity: 0.8;
        letter-spacing: 0.5px;
    }

    .card-body-id {
        padding: 20px;
        display: flex;
        flex-wrap: wrap;
    }

    .info-section-id {
        flex: 1;
        min-width: 200px;
    }

    .customer-name-id {
        font-size: 18px;
        font-weight: 700;
        color: #32475c;
        margin: 0 0 5px 0;
    }

    .customer-code-id {
        font-size: 32px;
        font-weight: 800;
        color: #696cff;
        margin: 0 0 10px 0;
        line-height: 1;
    }

    .qr-section-id {
        width: 100px;
        text-align: center;
    }

    .qr-box-id {
        width: 90px;
        height: 90px;
        padding: 5px;
        border: 1.5px solid #696cff;
        border-radius: 10px;
        background: white;
        margin: 0 auto;
    }

    .qr-box-id img {
        width: 100%;
        height: 100%;
    }

    .card-footer-id {
        padding: 10px 20px;
        background: #f8f9fa;
        border-top: 1px dashed #e1e4e8;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>
@endsection

@section('content')
<div class="row">
    <!-- Welcome Card -->
    <div class="col-lg-8 mb-4 order-0">
        <div class="card">
            <div class="d-flex align-items-end row">
                <div class="col-sm-7">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Selamat Datang, {{ $pelanggan->nama_pelanggan }}! 🎉</h5>
                        <p class="mb-4">Terima kasih telah berlangganan layanan internet Rozitech. Status layanan Anda saat ini adalah: 
                            <span class="badge bg-label-{{ $pelanggan->is_active ? 'success' : 'danger' }}">
                                {{ $pelanggan->is_active ? 'Aktif' : 'Isolir/Nonaktif' }}
                            </span>
                        </p>
                        <a href="{{ route('pelanggan.my-connection') }}" class="btn btn-sm btn-outline-primary">Cek Koneksi Saya</a>
                    </div>
                </div>
                <div class="col-sm-5 text-center text-sm-start">
                    <div class="card-body pb-0 px-0 px-md-4">
                        <img src="{{asset('assets/img/illustrations/man-with-laptop-light.png')}}" height="140" alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png" data-app-light-img="illustrations/man-with-laptop-light.png">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="col-lg-4 col-md-4 order-1">
        <div class="row">
            <div class="col-lg-6 col-md-12 col-6 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="card-title d-flex align-items-start justify-content-center">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-receipt"></i></span>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Tagihan Belum Bayar</span>
                        <h3 class="card-title mb-2">{{ $stats['tagihan_unpaid'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-6 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="card-title d-flex align-items-start justify-content-center">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-support"></i></span>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Tiket Gangguan Open</span>
                        <h3 class="card-title mb-2">{{ $stats['tiket_open'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- ID Card Section -->
    <div class="col-md-5 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Kartu Digital Pelanggan</h5>
                <a href="{{ route('pelanggan.card', $pelanggan->id_pelanggan) }}" target="_blank" class="btn btn-xs btn-primary"><i class="bx bx-printer me-1"></i> Cetak</a>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="card-id-container">
                    <div class="card-customer">
                        <div class="card-header-id">
                            <div class="d-flex align-items-center">
                                <img src="https://blogger.googleusercontent.com/img/a/AVvXsEhTp3lveqnLYbNRRyeGc_F24FMRpwEuzi9WgEZesnrR0kyUYhfiMwr1ifbLNqIesohcACtM7h713FsLKmE6k38n_-enxgK5pvkL0C9iLA4fbg8YVGXjyWZraY-26Xrf8X6-J0LrURVByT--R0-zq8XASTMh5u8svjvPRTy4dHoVYX-tRiDKnaATKI0PuJE" style="height: 30px; margin-right: 10px;" alt="Logo">
                                <div class="logo-text-id">
                                    <h1>Rozitech</h1>
                                    <p>Multimedia Indonesia</p>
                                </div>
                            </div>

                        </div>
                        <div class="card-body-id">
                            <div class="info-section-id">
                                <h3 class="customer-name-id">{{ $pelanggan->nama_pelanggan }}</h3>
                                <h2 class="customer-code-id">{{ $pelanggan->kode_pelanggan }}</h2>
                                <p class="mb-1 text-muted" style="font-size: 11px;"><i class="bx bx-map me-1"></i> {{ Str::limit($pelanggan->alamat, 50) }}</p>
                                <p class="mb-0 text-muted" style="font-size: 11px;"><i class="bx bx-phone me-1"></i> {{ $pelanggan->no_wa ?? '-' }}</p>
                            </div>
                            <div class="qr-section-id">
                                <div class="qr-box-id">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ url('/billing?search=' . $pelanggan->kode_pelanggan) }}" alt="QR">
                                </div>
                                <small class="text-primary fw-bold" style="font-size: 8px;">SCAN BAYAR</small>
                            </div>
                        </div>
                        <div class="card-footer-id">
                            <svg id="barcode-{{ $pelanggan->kode_pelanggan }}" style="height: 25px; max-width: 120px; opacity: 0.8;"></svg>
                            <small class="text-muted" style="font-size: 9px;">rozitech.co.id</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bills -->
    <div class="col-md-7 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Tagihan Terbaru</h5>
                <a href="{{ route('billing.index') }}" class="btn btn-sm btn-link">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-borderless">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTagihan as $tagihan)
                        <tr>
                            <td>
                                <span class="fw-bold">{{ date('F', mktime(0, 0, 0, $tagihan->bulan, 10)) }} {{ $tagihan->tahun }}</span>
                            </td>
                            <td>Rp {{ number_format($tagihan->jumlah) }}</td>
                            <td>
                                @if($tagihan->status == 'paid')
                                    <span class="badge bg-label-success">Lunas</span>
                                @elseif($tagihan->bukti_bayar)
                                    <span class="badge bg-label-info">Verifikasi</span>
                                @else
                                    <span class="badge bg-label-warning">Belum Bayar</span>
                                @endif
                            </td>
                            <td>
                                @if($tagihan->status == 'paid')
                                    <a href="{{ route('billing.receipt.pdf', $tagihan->id_tagihan) }}" class="btn btn-xs btn-outline-info">Nota</a>
                                @else
                                    <a href="{{ route('billing.index') }}" class="btn btn-xs btn-primary">Bayar</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">Belum ada data tagihan.</td>
                        </tr>
                        @endforelse
                    </tbody>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        try {
            JsBarcode("#barcode-{{ $pelanggan->kode_pelanggan }}", "{{ $pelanggan->kode_pelanggan }}", {
                format: "CODE128",
                lineColor: "#000",
                width: 1.5,
                height: 30,
                displayValue: false,
                margin: 0
            });
        } catch(e) {
            console.error("Gagal membuat barcode:", e);
        }
    });
</script>
@endsection

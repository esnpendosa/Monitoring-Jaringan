@extends('layouts/contentNavbarLayout')

@section('title', 'Profil Tidak Ditemukan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center py-5">
            <div class="card shadow-sm border-0">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <span class="badge bg-label-warning p-3 rounded-circle">
                            <i class="bx bx-user-x fs-1"></i>
                        </span>
                    </div>
                    <h3 class="fw-bold text-dark">Profil Pelanggan Tidak Ditemukan</h3>
                    <p class="text-muted px-3">
                        Akun Anda belum terasosiasi dengan data pelanggan WiFi aktif di sistem kami. Harap hubungi Admin untuk menghubungkan akun Anda agar dapat mengelola koneksi dan mengajukan upgrade paket.
                    </p>
                    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">
                        <i class="bx bx-home-alt me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

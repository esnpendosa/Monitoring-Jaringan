@extends('layouts.app')

@section('title', 'Tambah Pengajuan SK')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center mb-4">
        <h4 class="fw-bold mb-0">SK <a href="{{ route('kepegawaian.sk') }}" class="text-dark text-decoration-none"><i class="fa-solid fa-rotate ms-1 small text-muted"></i></a></h4>
    </div>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-transparent p-0 mb-0 small">
            <li class="breadcrumb-item"><i class="fa-solid fa-bookmark me-2 shadow-sm"></i>Sk</li>
            <li class="breadcrumb-item active" aria-current="page">Pengajuan / Tambah</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5">
            <form action="{{ route('kepegawaian.sk.store') }}" method="POST">
                @csrf
                <div class="row mb-4 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small">Nama Pegawai</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" class="form-control border bg-light-subtle rounded-1 py-2 px-3 small" value="{{ $user->name }}" disabled>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small">Kode Pegawai</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" class="form-control border bg-light-subtle rounded-1 py-2 px-3 small" value="{{ $biodata->kode_pegawai ?? '-' }}" disabled>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small mt-2">Catatan</label>
                    </div>
                    <div class="col-md-9">
                        <textarea name="catatan" class="form-control border rounded-1 py-2 px-3 small" rows="6" placeholder="Catatan" required></textarea>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3"></div>
                    <div class="col-md-9">
                        <div class="mt-2">
                            <h6 class="fw-bold text-dark mb-2 small text-uppercase">Info</h6>
                            <p class="text-danger small mb-0 italic" style="font-size: 11px;">*Setelah mengajukan SK, akan dilakukan verifikasi oleh yayasan sebelum SK bisa dicetak. Status pengajuan bisa dilihat di tabel bagian validasi</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-success rounded-1 px-4 py-2 fw-semibold d-inline-flex align-items-center shadow-sm">
                            <i class="fa-solid fa-save me-2"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

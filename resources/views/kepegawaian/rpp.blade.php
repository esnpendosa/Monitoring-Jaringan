@extends('layouts.app')

@section('title', 'Rencana Pelaksanaan Pembelajaran')

@section('content')
<div class="row g-4">
    <!-- Premium Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 24px;">
            <div class="bg-pmu p-4 p-md-5 text-white position-relative">
                <div class="position-relative z-index-1">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-white text-pmu rounded-pill px-3 py-1 fw-bold small me-3 text-uppercase">Administrasi</span>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 small opacity-75 fw-bold">
                                <li class="breadcrumb-item"><a href="#" class="text-white text-decoration-none">Kepegawaian</a></li>
                                <li class="breadcrumb-item active text-white" aria-current="page">Perangkat RPP</li>
                            </ol>
                        </nav>
                    </div>
                    <h2 class="fw-bold mb-1 text-uppercase ls-1"><i class="fa-solid fa-book-open-reader me-2"></i> PERANGKAT RPP</h2>
                    <p class="opacity-75 mb-0 fw-medium">Pengelolaan administrasi guru dan rencana pelaksanaan pembelajaran.</p>
                </div>
                <i class="fa-solid fa-graduation-cap position-absolute end-0 top-0 m-4 fa-6x opacity-10"></i>
            </div>
        </div>
    </div>

    <div class="col-12 pb-5">

    <!-- Filter & Add Button Row -->
    <div class="card border-0 shadow-sm rounded-0 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-auto">
                    <a href="{{ route('kepegawaian.rpp.create') }}" class="btn btn-success d-flex align-items-center fw-bold rounded-0 px-4" style="background-color: #0d4a2b; border-color: #0d4a2b;">
                        <i class="fa-solid fa-plus-circle me-2 small"></i> Tambah Perangkat
                    </a>
                </div>
                <div class="col-md-auto ms-auto d-flex align-items-center gap-3">
                    <div class="d-inline-block text-start" style="width: 250px;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 py-2 small text-muted">Cari:</span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none pb-2" placeholder="Ketik di sini...">
                            <span class="input-group-text bg-white border-start-0 py-2"><i class="fa-solid fa-search opacity-50 small"></i></span>
                        </div>
                    </div>
                    <div class="d-inline-block text-start">
                        <select class="form-select border rounded-0 shadow-none" style="width: 80px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-0 border-top">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 border-top-0">
                    <thead class="bg-transparent border-0">
                        <tr class="border-bottom">
                            <th class="ps-4 border-0 text-dark small py-3" style="width: 100px;">Aksi <i class="fa-solid fa-angle-down ms-1 opacity-50"></i></th>
                            <th class="border-0 text-dark small py-3">Tahun Akademik <i class="fa-solid fa-arrows-up-down ms-1 opacity-50" style="font-size: 10px;"></i></th>
                            <th class="border-0 text-dark small py-3">Unit <i class="fa-solid fa-arrows-up-down ms-1 opacity-50" style="font-size: 10px;"></i></th>
                            <th class="border-0 text-dark small py-3">Kelas <i class="fa-solid fa-arrows-up-down ms-1 opacity-50" style="font-size: 10px;"></i></th>
                            <th class="border-0 text-dark small py-3">Mata Pelajaran <i class="fa-solid fa-arrows-up-down ms-1 opacity-50" style="font-size: 10px;"></i></th>
                            <th class="border-0 text-dark small py-3">Judul <i class="fa-solid fa-arrows-up-down ms-1 opacity-50" style="font-size: 10px;"></i></th>
                            <th class="pe-4 border-0 text-dark small py-3">Dokumen <i class="fa-solid fa-arrows-up-down ms-1 opacity-50" style="font-size: 10px;"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rppList as $rpp)
                        <tr class="border-bottom">
                            <td class="ps-4">
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle rounded-1 px-3 fw-bold d-flex align-items-center gap-2" style="background-color: #3b5998; border-color: #3b5998; font-size: 0.7rem;" data-bs-toggle="dropdown">
                                        Aksi
                                    </button>
                                    <ul class="dropdown-menu border shadow-sm rounded-0 p-1" style="font-size: 0.75rem;">
                                        <li><a class="dropdown-item py-2" href="{{ asset('storage/' . $rpp->dokumen) }}" target="_blank"><i class="fa-solid fa-file-pdf me-2 opacity-50"></i> Lihat Dokumen</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('kepegawaian.rpp.destroy', $rpp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item py-2 text-danger"><i class="fa-solid fa-trash me-2 opacity-50"></i> Hapus</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td class="small text-dark fw-bold">{{ $rpp->tahun_akademik }}</td>
                            <td class="small text-muted">{{ $rpp->unit }}</td>
                            <td class="small text-muted">{{ $rpp->kelas }}</td>
                            <td class="small text-muted">{{ $rpp->mata_pelajaran }}</td>
                            <td class="small text-muted">{{ $rpp->judul }}</td>
                            <td class="pe-4">
                                <a href="{{ asset('storage/' . $rpp->dokumen) }}" target="_blank" class="badge bg-success-subtle text-success py-2 px-3 rounded-pill text-decoration-none border border-success border-opacity-25" style="font-size: 0.65rem;">
                                    <i class="fa-solid fa-file-pdf me-2"></i> LIHAT FILE
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted small">
                                Tidak ada data
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Menampilkan {{ $rppList->firstItem() ?? 0 }} sampai {{ $rppList->lastItem() ?? 0 }} dari {{ $rppList->total() }} data</small>
                    {{ $rppList->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

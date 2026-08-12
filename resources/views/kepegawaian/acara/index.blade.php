@extends('layouts.app')

@section('title', 'Manajemen Acara')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 24px;">
            <div class="bg-pmu p-4 p-md-5 text-white position-relative">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-white text-pmu rounded-pill px-3 py-1 fw-bold small me-3">KEPEGAWAIAN</span>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small opacity-75 fw-bold">
                            <li class="breadcrumb-item"><a href="#" class="text-white text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Manajemen Acara</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                    <div>
                        <h2 class="fw-bold mb-0 text-uppercase">DAFTAR ACARA & KEGIATAN</h2>
                        <p class="mb-0 opacity-75">Kelola acara khusus dan absensi via QR Code yang tersinkronisasi.</p>
                    </div>
                    @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                    <div>
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold shadow d-flex align-items-center gap-2 text-pmu" data-bs-toggle="modal" data-bs-target="#modalTambahAcara" style="background: #ffffff !important; border: 2px solid rgba(255,255,255,0.3);">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span>TAMBAH ACARA</span>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-bottom border-4 border-pmu">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50">
                        <tr class="text-uppercase text-muted fw-bold ls-1" style="font-size: 0.75rem;">
                            <th class="ps-4 py-3">Nama Acara</th>
                            <th class="py-3 text-center">Tanggal</th>
                            <th class="py-3 text-center">Waktu</th>
                            <th class="py-3 text-center">Lokasi</th>
                            @if(!Auth::user()->isYayasan())
                            <th class="py-3 text-center">Status Kehadiran</th>
                            @endif
                            @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                            <th class="pe-4 py-3 text-end">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($acaras as $acara)
                        @php
                            $attendance = $acara->absensiAcaras->first(); // Only first because we filtered by current user in controller
                        @endphp
                        <tr class="border-bottom text-dark">
                            <td class="ps-4 py-3">
                                <div class="fw-bold text-pmu">{{ $acara->nama }}</div>
                            </td>
                            <td class="text-center small">
                                <div class="fw-bold">{{ \Carbon\Carbon::parse($acara->tanggal)->translatedFormat('d F Y') }}</div>
                            </td>
                            <td class="text-center small">
                                <span class="badge bg-light text-dark rounded-pill border">
                                    {{ substr($acara->jam_mulai, 0, 5) }} - {{ substr($acara->jam_selesai, 0, 5) }}
                                </span>
                            </td>
                            <td class="text-center small text-muted">
                                {{ $acara->lokasi ?? '-' }}
                            </td>
                            @if(!Auth::user()->isYayasan())
                            <td class="text-center">
                                @if($attendance)
                                    <div class="alert alert-success py-1 px-3 mb-0 small fw-bold d-inline-block rounded-pill">
                                        <i class="fa-solid fa-check-circle me-1"></i> Hadir pada {{ \Carbon\Carbon::parse($attendance->waktu_scan)->format('d/m/Y H:i') }}
                                    </div>
                                @else
                                    <div class="alert alert-danger py-1 px-3 mb-0 small fw-bold d-inline-block rounded-pill">
                                        <i class="fa-solid fa-times-circle me-1"></i> Tidak hadir
                                    </div>
                                @endif
                            </td>
                            @endif
                            @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('kepegawaian.acara.show', $acara->id) }}" class="btn btn-sm btn-light text-pmu rounded-pill px-3 fw-bold">
                                        <i class="fa-solid fa-eye me-1"></i> DETAIL
                                    </a>
                                    <form action="{{ route('kepegawaian.acara.destroy', $acara->id) }}" method="POST" onsubmit="return confirm('Hapus acara ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light text-danger rounded-pill px-2">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->isAdminUnit() ? 6 : 5 }}" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-calendar-xmark fs-2 mb-3 d-block opacity-25"></i>
                                <p class="mb-0 fw-bold">Belum ada acara yang terdaftar.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($acaras->hasPages())
            <div class="p-4 bg-light border-top">
                {{ $acaras->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tambah Acara -->
<div class="modal fade" id="modalTambahAcara" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('kepegawaian.acara.store') }}" method="POST" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <h5 class="modal-title fw-bold">TAMBAH ACARA BARU</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">NAMA ACARA</label>
                        <input type="text" name="nama" class="form-control rounded-3 border-light bg-light" placeholder="Contoh: Rapat Pleno Yayasan" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">TANGGAL</label>
                        <input type="date" name="tanggal" class="form-control rounded-3 border-light bg-light" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">JAM MULAI</label>
                            <input type="time" name="jam_mulai" class="form-control rounded-3 border-light bg-light" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">JAM SELESAI</label>
                            <input type="time" name="jam_selesai" class="form-control rounded-3 border-light bg-light" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">LOKASI (OPSIONAL)</label>
                        <input type="text" name="lokasi" class="form-control rounded-3 border-light bg-light" placeholder="Contoh: Aula Lt. 2">
                    </div>
                </div>
                <div class="modal-footer p-4 border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-pmu rounded-pill px-4 fw-bold shadow-sm">SIMPAN ACARA</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

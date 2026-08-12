@extends('layouts.app')

@section('title', 'Rekapitulasi Absensi')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header border-0 bg-transparent px-4 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-calendar-check text-success me-2"></i> 
                    Riwayat Kehadiran Bulan Ini
                </h5>
                @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-success btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-file-excel me-2"></i> Export Excel
                    </button>
                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-file-pdf me-2"></i> Cetak PDF
                    </button>
                </div>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Tanggal</th>
                                @if(!Auth::user()->isPegawai())
                                <th>Nama Pegawai</th>
                                @endif
                                <th class="text-center">Jam Masuk</th>
                                <th class="text-center">Jam Pulang</th>
                                <th class="text-center">Status</th>
                                <th class="text-end px-4">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($absensis as $absen)
                            <tr class="border-bottom">
                                <td class="px-4 fw-semibold text-muted">
                                    {{ \Carbon\Carbon::parse($absen->tgl)->format('d M Y') }}
                                </td>
                                @if(!Auth::user()->isPegawai())
                                <td>
                                    <div class="fw-bold">{{ $absen->user->name }}</div>
                                    <small class="text-muted">{{ $absen->user->unit }}</small>
                                </td>
                                @endif
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3 py-2 rounded-3">
                                        {{ $absen->jam_masuk ?? '--:--' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3 py-2 rounded-3">
                                        {{ $absen->jam_pulang ?? '--:--' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($absen->status == 'Hadir')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">
                                            <i class="fa-solid fa-check-circle me-1"></i> Hadir
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 rounded-pill">
                                            <i class="fa-solid fa-clock me-1"></i> {{ $absen->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end px-4 italic small text-muted">
                                    {{ $absen->keterangan ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ Auth::user()->isPegawai() ? 5 : 6 }}" class="text-center py-5">
                                    <div class="text-muted opacity-25 mb-3">
                                        <i class="fa-solid fa-calendar-xmark fa-3x"></i>
                                    </div>
                                    <h6 class="text-muted">Tidak ada data absensi yang ditemukan.</h6>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer border-0 bg-transparent p-4">
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-center mb-0">
                        {{-- Laravel Pagination links can go here if paginated --}}
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
@endsection

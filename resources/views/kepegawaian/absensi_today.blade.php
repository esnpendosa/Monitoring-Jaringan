@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring Absensi Real-time')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius: 16px;">
            <div class="position-absolute end-0 bottom-0 opacity-10" style="font-size: 15rem; transform: translate(10%, 20%); line-height: 1;">
                <i class="bx bx-broadcast"></i>
            </div>
            <div class="card-body p-4 p-md-5">
                <h4 class="card-title text-white mb-2 fw-bold"><i class="bx bx-broadcast me-2"></i> MONITORING ABSENSI REAL-TIME</h4>
                <p class="mb-0 text-white-50">Log aktivitas sidik jari pegawai hari ini yang masuk secara real-time ke server.</p>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center" style="border-radius: 12px;">
    <i class="bx bx-check-circle me-2 fs-4"></i> {{ session('success') }}
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-5" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <div>
                    <h5 class="fw-bold text-dark mb-0"><i class="bx bx-time text-primary me-2"></i> LOG ABSENSI HARI INI</h5>
                    <small class="text-muted fw-semibold">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('absensi.today') }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bx bx-refresh me-1"></i> Refresh Log
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light border-bottom">
                            <tr class="text-uppercase small fw-bold text-dark">
                                <th class="ps-4 py-3">Pegawai</th>
                                <th class="py-3">PIN / Role</th>
                                <th class="text-center py-3">Jam Masuk</th>
                                <th class="text-center py-3">Jam Pulang</th>
                                <th class="text-center py-3">Status</th>
                                <th class="text-center py-3">Lokasi</th>
                                @if(Auth::user()->id_role == 1)
                                <th class="pe-4 text-end py-3">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($absensis as $abs)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm border border-2 border-white" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                            {{ substr($abs->user->name ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0">{{ $abs->user->name ?? 'N/A' }}</div>
                                            <small class="text-muted" style="font-size: 0.75rem;">{{ $abs->user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="fw-semibold text-dark small">ID PIN: {{ $abs->pin ?? '-' }}</div>
                                    <span class="badge bg-label-indigo small mt-1">{{ $abs->user->role->name ?? 'Pegawai' }}</span>
                                </td>
                                <td class="text-center py-3">
                                    <div class="fw-bold text-success fs-5">
                                        {{ $abs->jam_masuk ?? '--:--:--' }}
                                    </div>
                                </td>
                                <td class="text-center py-3">
                                    <div class="fw-bold text-danger fs-5">
                                        {{ $abs->jam_pulang ?? '--:--:--' }}
                                    </div>
                                </td>
                                <td class="text-center py-3">
                                    @php
                                        $statusClass = match($abs->status_kehadiran) {
                                            'Hadir' => 'success',
                                            'Terlambat' => 'warning',
                                            'Pulang Lebih Awal' => 'info',
                                            'Terlambat & Pulang Awal' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }} px-3 py-2 rounded-pill small fw-bold">{{ strtoupper($abs->status_kehadiran) }}</span>
                                </td>
                                <td class="text-center py-3">
                                    <span class="text-muted small fw-medium"><i class="bx bx-map-pin me-1 text-primary"></i>{{ $abs->lokasi ?? 'Solutions X105' }}</span>
                                </td>
                                @if(Auth::user()->id_role == 1)
                                <td class="pe-4 text-end py-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deleteAbs{{ $abs->id }}" title="Hapus Log">
                                        <i class="bx bx-trash"></i> Hapus
                                    </button>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-5 text-center">
                                    <div class="opacity-50 mb-3">
                                        <i class="bx bx-broadcast fs-1 text-muted"></i>
                                    </div>
                                    <h6 class="text-muted fw-bold mb-1">Belum ada pegawai yang scan jari hari ini.</h6>
                                    <p class="text-muted small">Mesin sidik jari akan otomatis mengirim data saat pegawai melakukan scan.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for deletion -->
@if(Auth::user()->id_role == 1)
@foreach($absensis as $abs)
<div class="modal fade" id="deleteAbs{{ $abs->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body p-4 text-center">
                <div class="bg-label-danger p-3 rounded-circle d-inline-block mb-3">
                    <i class="bx bx-trash text-danger fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Log Kehadiran?</h5>
                <p class="text-muted small mb-4">Log absensi milik *{{ $abs->user->name ?? '' }}* pada tanggal ini akan dihapus permanen. Lanjutkan?</p>
                
                <form action="{{ route('absensi.destroy', $abs->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-danger rounded-pill fw-bold py-2 shadow-sm">Ya, Hapus Sekarang</button>
                        <button type="button" class="btn btn-light rounded-pill fw-bold py-2 border" data-bs-dismiss="modal">Batalkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach
@endif

<style>
.bg-label-indigo {
    background-color: #e0e7ff !important;
    color: #4f46e5 !important;
}
.bg-label-danger {
    background-color: #fee2e2 !important;
    color: #ef4444 !important;
}
</style>
@endsection

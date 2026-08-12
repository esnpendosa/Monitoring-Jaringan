@extends('layouts.app')

@section('title', 'Permohonan SK')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 24px;">
            <div class="card-body p-0">
                <div class="bg-pmu p-4 p-md-5 text-white position-relative">
                    <div class="position-relative z-index-1">
                        <h3 class="fw-bold mb-1"><i class="fa-solid fa-file-signature me-2"></i> LAYANAN PERMOHONAN SK</h3>
                        <p class="opacity-75 mb-0">Pengajuan dan verifikasi surat keputusan pegawai secara digital.</p>
                    </div>
                    <i class="fa-solid fa-stamp position-absolute end-0 top-0 m-4 fa-6x opacity-10"></i>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="col-12">
        <div class="alert alert-success border-0 shadow-sm rounded-4 p-4 mb-0 d-flex align-items-center">
            <i class="fa-solid fa-circle-check fs-4 me-3"></i>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif

    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> RIWAYAT PENGAJUAN</h6>
                    <span class="badge bg-light text-muted border px-3 py-2 rounded-pill small fw-normal">{{ $permohonans->total() }} Data</span>
                </div>
                <div class="d-flex gap-2">
                    @if(Auth::user()->isPegawai())
                    <a href="{{ route('kepegawaian.sk.create') }}" class="btn btn-pmu px-4 py-2 rounded-pill fw-bold shadow-sm">
                        <i class="fa-solid fa-plus me-2"></i> AJUKAN SK BARU
                    </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-uppercase small fw-bold">
                                <th class="ps-4 py-3">PEGAWAI</th>
                                <th class="py-3">TANGGAL PENGAJUAN</th>
                                <th class="py-3">CATATAN/KEPERLUAN</th>
                                <th class="text-center py-3">STATUS</th>
                                <th class="pe-4 text-end py-3">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($permohonans as $sk)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 40px; height: 40px;">
                                            {{ substr($sk->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0">{{ $sk->user->name }}</div>
                                            <small class="text-muted" style="font-size: 0.70rem;">Unit: {{ $sk->user->unit }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="fw-bold text-dark mb-0">{{ $sk->created_at->translatedFormat('d F Y') }}</div>
                                    <small class="text-muted">{{ $sk->created_at->format('H:i') }} WIB</small>
                                </td>
                                <td class="py-3">
                                    <div class="text-dark small" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $sk->catatan }}">
                                        {{ $sk->catatan }}
                                    </div>
                                </td>
                                <td class="text-center py-3">
                                    @if($sk->status == 'Pending')
                                        <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill small">MENUNGGU</span>
                                    @elseif($sk->status == 'Disetujui')
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill small">DISETUJUI</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill small">DITOLAK</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end py-3">
                                    <div class="d-flex justify-content-end gap-2 text-start">
                                        <button class="btn btn-light btn-sm rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#detailSkModal{{ $sk->id }}" title="Detail Pengajuan" style="width: 35px; height: 35px;">
                                            <i class="fa-solid fa-circle-info text-info"></i>
                                        </button>
                                        @if(Auth::user()->isAdminUnit() || Auth::user()->isYayasan())
                                            @if($sk->status == 'Pending')
                                            <!-- Approve -->
                                            <form action="{{ route('kepegawaian.sk.update-status', $sk->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="Disetujui">
                                                <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm transition-hover" title="Setujui" style="width: 35px; height: 35px;">
                                                    <i class="fa-solid fa-check text-success"></i>
                                                </button>
                                            </form>
                                            <!-- Reject -->
                                            <form action="{{ route('kepegawaian.sk.update-status', $sk->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="Ditolak">
                                                <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm transition-hover" title="Tolak" style="width: 35px; height: 35px;">
                                                    <i class="fa-solid fa-times text-danger"></i>
                                                </button>
                                            </form>
                                            @else
                                            <div class="btn btn-light btn-sm rounded-circle border-0 shadow-none opacity-50" title="Sudah Diproses" style="width: 35px; height: 35px;">
                                                <i class="fa-solid fa-lock text-muted"></i>
                                            </div>
                                            @endif
                                        @endif

                                        @if(Auth::id() == $sk->user_id || Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                                        <form action="{{ route('kepegawaian.sk.destroy', $sk->id) }}" method="POST" onsubmit="return confirm('Hapus pengajuan ini secara permanen?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Hapus" style="width: 35px; height: 35px;">
                                                <i class="fa-solid fa-trash-can text-danger"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>

                                    <!-- Modal Detail SK -->
                                    <div class="modal text-start" id="detailSkModal{{ $sk->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                                                <div class="modal-header bg-light border-0 p-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 45px; height: 45px;">
                                                            {{ substr($sk->user->name, 0, 1) }}
                                                        </div>
                                                        <div>
                                                            <h5 class="modal-title fw-bold mb-0 text-uppercase">DETAIL PENGAJUAN SK</h5>
                                                            <small class="text-muted">{{ $sk->user->name }}</small>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-4">
                                                        <div class="col-12">
                                                            <label class="small text-muted fw-bold d-block mb-1 text-uppercase">Nama Pegawai</label>
                                                            <div class="fw-bold text-dark">{{ $sk->user->name }}</div>
                                                            <small class="text-muted">Unit: {{ $sk->user->unit }}</small>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="small text-muted fw-bold d-block mb-1 text-uppercase">Waktu Pengajuan</label>
                                                            <div class="fw-bold text-dark">{{ $sk->created_at->translatedFormat('d F Y') }} - {{ $sk->created_at->format('H:i') }} WIB</div>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="small text-muted fw-bold d-block mb-1 text-uppercase">Catatan / Keperluan Pengajuan</label>
                                                            <div class="p-3 bg-light rounded-4 border-dashed border-2 text-dark" style="min-height: 120px; white-space: pre-wrap;">{{ $sk->catatan }}</div>
                                                        </div>
                                                        <div class="col-12 text-center">
                                                            <div class="p-2 rounded-pill d-inline-block px-4 {{ $sk->status == 'Disetujui' ? 'bg-success bg-opacity-10 text-success border-success border' : ($sk->status == 'Ditolak' ? 'bg-danger bg-opacity-10 text-danger border-danger border' : 'bg-warning bg-opacity-10 text-warning border-warning border') }}">
                                                                <span class="fw-bold">STATUS: {{ strtoupper($sk->status == 'Pending' ? 'PROSES' : $sk->status) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 p-4 pt-0">
                                                    <button type="button" class="btn btn-pmu w-100 rounded-pill py-2 fw-bold" data-bs-dismiss="modal">TUTUP DETAIL</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center">
                                    <div class="opacity-25 mb-3">
                                        <i class="fa-solid fa-file-invoice fa-5x"></i>
                                    </div>
                                    <h6 class="text-muted fw-bold">Belum ada riwayat pengajuan SK.</h6>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($permohonans->hasPages())
            <div class="card-footer bg-white border-0 py-4 px-4">
                {{ $permohonans->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

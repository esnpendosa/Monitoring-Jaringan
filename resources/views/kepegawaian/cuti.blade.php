@extends('layouts.app')

@section('title', 'Pengajuan Cuti Pegawai')

@section('content')
<div class="container-fluid px-0">
    <!-- Premium Header -->
    <div class="bg-pmu p-4 p-md-5 text-white mb-4">
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-white text-pmu rounded-pill px-3 py-1 fw-bold small me-3">ADMINISTRASI</span>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small opacity-75 fw-bold">
                    <li class="breadcrumb-item"><a href="#" class="text-white text-decoration-none">Kepegawaian</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Pengajuan Cuti</li>
                </ol>
            </nav>
        </div>
        <h2 class="fw-bold mb-0">PENGAJUAN & RIWAYAT CUTI</h2>
        <p class="mb-0 opacity-75">Kelola permohonan cuti pegawai dan tracking persetujuan unit/yayasan.</p>
    </div>

    <div class="px-3 px-md-5 pb-5">
        <!-- Quick Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-pmu h-100 transition-all hover-translate-y">
                    <div class="d-flex align-items-center">
                        <div class="bg-pmu bg-opacity-10 p-2 p-md-3 rounded-3 me-2 me-md-3 text-pmu">
                            <i class="fa-solid fa-calendar-day fs-4"></i>
                        </div>
                        <div>
                            <div class="text-dark small fw-bold text-uppercase" style="font-size: 0.65rem;">Total Pengajuan</div>
                            <div class="h4 fw-bold mb-0 text-dark">{{ $cutis->total() }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning h-100 transition-all hover-translate-y">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="fa-solid fa-clock text-warning fs-4"></i>
                        </div>
                        <div>
                            @php
                                $menungguUnitCount = \App\Models\Cuti::where('status_unit', 'Pending');
                                if(Auth::user()->isPegawai()) { 
                                    $menungguUnitCount->where('user_id', Auth::id()); 
                                } elseif(Auth::user()->isAdminUnit()) {
                                    $menungguUnitCount->where('unit', Auth::user()->unit);
                                }
                            @endphp
                            <div class="h4 fw-bold mb-0 text-dark">{{ $menungguUnitCount->count() }} Data</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger h-100 transition-all hover-translate-y">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="fa-solid fa-building-flag text-danger fs-4"></i>
                        </div>
                        <div>
                            @php
                                $menungguYayasanCount = \App\Models\Cuti::where('status_yayasan', 'Pending');
                                if(Auth::user()->isPegawai()) { 
                                    $menungguYayasanCount->where('user_id', Auth::id()); 
                                } elseif(Auth::user()->isAdminUnit()) {
                                    $menungguYayasanCount->where('unit', Auth::user()->unit);
                                }
                            @endphp
                            <div class="h4 fw-bold mb-0 text-dark">{{ $menungguYayasanCount->count() }} Data</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Add Button Row -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 transition-all">
            <div class="card-body p-4 bg-white">
                <form action="" method="GET" class="row g-3 align-items-center">
                    <div class="col-12 col-md-auto">
                        <select name="status" class="form-select rounded-pill border bg-light shadow-none fw-bold small py-2 px-4 transition-all" style="min-width: 180px;">
                            <option value="">SEMUA STATUS</option>
                            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>PENDING</option>
                            <option value="Disetujui" {{ request('status') == 'Disetujui' ? 'selected' : '' }}>DISETUJUI</option>
                            <option value="Ditolak" {{ request('status') == 'Ditolak' ? 'selected' : '' }}>DITOLAK</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-pmu fw-bold rounded-pill px-4 shadow-sm py-2 d-flex align-items-center gap-2 hover-translate-y transition-all" style="background: var(--pmu-gradient) !important;">
                            <i class="fa-solid fa-filter"></i> FILTER
                        </button>
                    </div>

                    @if(Auth::user()->isPegawai())
                    <div class="col-12 col-md-auto">
                        <button type="button" class="btn btn-pmu fw-bold rounded-pill px-4 shadow-sm py-2 d-flex align-items-center gap-2 hover-translate-y transition-all" data-bs-toggle="modal" data-bs-target="#tambahCutiModal" style="background: var(--pmu-gradient) !important;">
                            <i class="fa-solid fa-circle-plus fs-5"></i> TAMBAH PENGAJUAN
                        </button>
                    </div>
                    @endif

                    <div class="col-12 col-md-auto ms-auto">
                        <div class="input-group rounded-pill overflow-hidden border bg-light px-3 py-1 transition-all focus-within-shadow">
                            <span class="bg-transparent border-0 d-flex align-items-center">
                                <i class="fa-solid fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-0 bg-transparent py-2 shadow-none small" placeholder="Cari Pegawai atau Alasan..." value="{{ request('search') }}">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="border-bottom text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">
                                <th class="ps-4 py-3 text-muted fw-bold" style="width: 50px;">No.</th>
                                <th class="py-3 text-muted fw-bold">Pegawai</th>
                                <th class="py-3 text-muted fw-bold">Unit / Alasan</th>
                                <th class="py-3 text-muted fw-bold text-center">Periode Cuti</th>
                                <th class="py-3 text-muted fw-bold text-center" colspan="2" style="border-left: 1px solid #eee;">Validasi</th>
                                <th class="py-3 text-muted fw-bold text-center">Status</th>
                                <th class="pe-4 py-3 text-end fw-bold">Aksi</th>
                            </tr>
                            <tr class="border-bottom" style="background-color: #fafafa;">
                                <th class="p-0 border-0" colspan="4"></th>
                                <th class="border-0 text-muted text-center py-1" style="font-size: 0.65rem; border-left: 1px solid #eee;">Unit</th>
                                <th class="border-0 text-muted text-center py-1" style="font-size: 0.65rem; border-left: 1px solid #eee;">Yayasan</th>
                                <th class="p-0 border-0 text-center" colspan="2"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($cutis as $index => $cuti)
                        <tr class="border-bottom">
                            <td class="ps-4">
                                <span class="text-muted fw-bold" style="font-size: 0.7rem;">{{ $cutis->firstItem() + $index }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-2 me-md-3 shadow-sm border border-2 border-white" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                        {{ substr($cuti->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <div class="pe-3">
                                        <div class="small fw-bold text-uppercase mb-0 text-dark">{{ $cuti->user->name ?? '?' }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">PIN: {{ $cuti->user->pin_fingerspot ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-bold text-dark">{{ $cuti->unit }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.7rem; max-width: 180px;">{{ $cuti->alasan }}</div>
                            </td>
                            <td class="text-center">
                                <div class="badge bg-light text-dark border-0 rounded-pill px-3 py-1 mb-1 shadow-none" style="font-size: 0.65rem; background-color: #f0f2f5 !important;">
                                    {{ \Carbon\Carbon::parse($cuti->tgl_mulai)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($cuti->tgl_selesai)->translatedFormat('d M Y') }}
                                </div>
                                <div class="text-muted small fw-bold" style="font-size: 0.6rem;">{{ \Carbon\Carbon::parse($cuti->tgl_mulai)->diffInDays(\Carbon\Carbon::parse($cuti->tgl_selesai)) + 1 }} HARI</div>
                            </td>
                            <td class="text-center" style="border-left: 1px solid #eee;">
                                @php
                                    $unitStatus = match($cuti->status_unit) {
                                        'Disetujui' => ['success', 'circle-check'],
                                        'Ditolak' => ['danger', 'circle-xmark'],
                                        default => ['warning', 'clock-rotate-left']
                                    };
                                @endphp
                                <i class="fa-solid fa-{{ $unitStatus[1] }} text-{{ $unitStatus[0] }} fs-5" title="{{ $cuti->status_unit }}"></i>
                            </td>
                            <td class="text-center" style="border-left: 1px solid #eee;">
                                @php
                                    $yayasanStatus = match($cuti->status_yayasan) {
                                        'Disetujui' => ['success', 'circle-check'],
                                        'Ditolak' => ['danger', 'circle-xmark'],
                                        default => ['warning', 'clock-rotate-left']
                                    };
                                @endphp
                                <i class="fa-solid fa-{{ $yayasanStatus[1] }} text-{{ $yayasanStatus[0] }} fs-5" title="{{ $cuti->status_yayasan }}"></i>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusBadge = match($cuti->status_akhir) {
                                        'Disetujui' => ['success', 'SELESAI'],
                                        'Ditolak' => ['danger', 'DITOLAK'],
                                        default => ['warning', 'PROSES']
                                    };
                                @endphp
                                <span class="badge badge-soft-{{ $statusBadge[0] }} rounded-pill px-3 py-2 fw-bold" style="font-size: 0.65rem;">{{ $statusBadge[1] }}</span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end">
                                    <div class="btn-group-premium shadow-sm">
                                        <a href="#" class="btn-action" data-bs-toggle="modal" data-bs-target="#detailCutiModal{{ $cuti->id }}" title="Lihat Detail">
                                            <i class="fa-solid fa-file-invoice text-pmu"></i>
                                        </a>
                                        
                                        @if($cuti->status_akhir == 'Pending' && (Auth::user()->isYayasan() || Auth::user()->isAdminUnit()))
                                        <a href="#" class="btn-action" data-bs-toggle="modal" data-bs-target="#valCutiModal{{ $cuti->id }}" title="Validasi Cuti">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                        </a>
                                        @endif
                                        
                                        @if($cuti->status_akhir == 'Pending' && $cuti->user_id == Auth::id())
                                        <form action="{{ route('kepegawaian.cuti.destroy', $cuti->id) }}" method="POST" onsubmit="return confirm('Batalkan pengajuan ini?')" class="d-inline-flex">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-action border-0 bg-transparent" title="Batalkan">
                                                <i class="fa-solid fa-trash-can text-danger"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>

                                <!-- Modal Validasi Cuti -->
                                @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                                <div class="modal text-start" id="valCutiModal{{ $cuti->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                                            <div class="modal-header bg-light border-0 p-4">
                                                <h5 class="modal-title fw-bold mb-0">VALIDASI PENGAJUAN</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 text-center">
                                                <div class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                                    {{ substr($cuti->user->name, 0, 1) }}
                                                </div>
                                                <h6 class="fw-bold mb-1">{{ $cuti->user->name }}</h6>
                                                <p class="text-muted small mb-4">Unit: {{ $cuti->unit }} <br> Alasan: {{ $cuti->alasan }}</p>
                                                
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <form action="{{ route('kepegawaian.cuti.update-status', $cuti->id) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="status" value="Disetujui">
                                                            <button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-bold">SETUJUI</button>
                                                        </form>
                                                    </div>
                                                    <div class="col-6">
                                                        <form action="{{ route('kepegawaian.cuti.update-status', $cuti->id) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="status" value="Ditolak">
                                                            <button type="submit" class="btn btn-danger w-100 rounded-pill py-2 fw-bold">TOLAK</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                        </tr>

                                    <!-- Modal Detail Cuti -->
                                    <div class="modal text-start" id="detailCutiModal{{ $cuti->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered shadow-none">
                                            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                                                <div class="modal-header bg-light border-0 p-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 45px; height: 45px;">
                                                            {{ substr($cuti->user->name, 0, 1) }}
                                                        </div>
                                                        <div>
                                                            <h5 class="modal-title fw-bold mb-0">DETAIL PENGAJUAN</h5>
                                                            <small class="text-muted">{{ $cuti->user->name }}</small>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-4">
                                                        <div class="col-6">
                                                            <label class="small text-muted fw-bold d-block mb-1 text-uppercase">Jenis Alasan</label>
                                                            <div class="fw-bold text-dark">{{ $cuti->alasan }}</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="small text-muted fw-bold d-block mb-1 text-uppercase">Unit Kerja</label>
                                                            <div class="fw-bold text-dark">{{ $cuti->unit }}</div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="bg-light p-3 rounded-4 border">
                                                                <div class="row text-center">
                                                                    <div class="col-5">
                                                                        <label class="small text-muted fw-bold mb-1 d-block">MULAI</label>
                                                                        <div class="fw-bold text-pmu">{{ $cuti->tgl_mulai->translatedFormat('d F Y') }}</div>
                                                                    </div>
                                                                    <div class="col-2 d-flex align-items-center justify-content-center">
                                                                        <i class="fa-solid fa-arrow-right text-muted opacity-50"></i>
                                                                    </div>
                                                                    <div class="col-5">
                                                                        <label class="small text-muted fw-bold mb-1 d-block">SAMPAI</label>
                                                                        <div class="fw-bold text-pmu">{{ $cuti->tgl_selesai->translatedFormat('d F Y') }}</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="small text-muted fw-bold d-block mb-1 text-uppercase">Dokumen Pendukung</label>
                                                            @if($cuti->dokumen_pendukung)
                                                                <a href="{{ route('kepegawaian.dokumen.view', ['path' => $cuti->dokumen_pendukung]) }}" target="_blank" class="btn btn-sm btn-info rounded-pill px-4 fw-bold shadow-none" style="font-size: 0.7rem;">
                                                                    <i class="fa-solid fa-file-invoice me-2"></i> LIHAT LAMPIRAN
                                                                </a>
                                                            @else
                                                                <span class="text-muted small fst-italic">Tidak ada dokumen lampiran.</span>
                                                            @endif
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="small text-muted fw-bold d-block mb-1 text-uppercase">Catatan Tambahan</label>
                                                            <div class="p-3 bg-light rounded-4 border-dashed border-2 text-dark" style="min-height: 80px;">
                                                                {{ $cuti->catatan ?? 'Tidak ada catatan tambahan.' }}
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="small text-muted fw-bold d-block mb-2 text-uppercase">Tracking Persetujuan</label>
                                                            <div class="d-flex gap-3">
                                                                <div class="flex-fill p-2 rounded-3 text-center border {{ $cuti->status_unit == 'Disetujui' ? 'bg-success bg-opacity-10 text-success border-success' : ($cuti->status_unit == 'Ditolak' ? 'bg-danger bg-opacity-10 text-danger border-danger' : 'bg-warning bg-opacity-10 text-warning border-warning') }}">
                                                                    <div class="small fw-bold">UNIT</div>
                                                                    <div style="font-size: 0.65rem;">{{ strtoupper($cuti->status_unit) }}</div>
                                                                </div>
                                                                <div class="flex-fill p-2 rounded-3 text-center border {{ $cuti->status_yayasan == 'Disetujui' ? 'bg-success bg-opacity-10 text-success border-success' : ($cuti->status_yayasan == 'Ditolak' ? 'bg-danger bg-opacity-10 text-danger border-danger' : 'bg-warning bg-opacity-10 text-warning border-warning') }}">
                                                                    <div class="small fw-bold">YAYASAN</div>
                                                                    <div style="font-size: 0.65rem;">{{ strtoupper($cuti->status_yayasan) }}</div>
                                                                </div>
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
                                <td colspan="6" class="py-5 text-center">
                                    <div class="opacity-25 mb-3">
                                        <i class="fa-solid fa-calendar-xmark fa-5x"></i>
                                    </div>
                                    <h6 class="text-muted fw-bold">Belum ada riwayat pengajuan cuti.</h6>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($cutis->hasPages())
            <div class="card-footer bg-white border-0 py-4 px-4">
                {{ $cutis->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tambah Cuti (Premium Version) -->
<div class="modal" id="tambahCutiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered shadow-none">
        <form action="{{ route('kepegawaian.cuti.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white border-0 p-4">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">PENGAJUAN CUTI BARU</h5>
                        <p class="small opacity-75 mb-0">Isi data permohonan dengan benar.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">UNIT KERJA</label>
                        <select name="unit" class="form-select border-2 rounded-3" required>
                            <option value="">-- Pilih Unit --</option>
                            @foreach($global_units as $un)
                                <option value="{{ $un->nama }}" {{ Auth::user()->unit == $un->nama ? 'selected' : '' }}>{{ $un->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">ALASAN CUTI</label>
                        <select name="alasan" class="form-select border-2 rounded-3" required>
                            <option value="">-- Pilih Jenis Cuti --</option>
                            <option value="Di Luar Tanggungan">Di Luar Tanggungan</option>
                            <option value="Haji">Haji</option>
                            <option value="Ijin Belajar">Ijin Belajar</option>
                            <option value="Melahirkan">Melahirkan</option>
                            <option value="Musibah">Musibah</option>
                            <option value="Nikah">Nikah</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Tugas">Tugas</option>
                            <option value="Umrah">Umrah</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">DARI TANGGAL</label>
                            <input type="date" name="tgl_mulai" class="form-control border-2 rounded-3" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">SAMPAI TANGGAL</label>
                            <input type="date" name="tgl_selesai" class="form-control border-2 rounded-3" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">DOKUMEN PENDUKUNG (Opsional)</label>
                        <input type="file" name="dokumen" class="form-control border-2 rounded-3">
                        <small class="text-muted" style="font-size: 0.65rem;">*Sertakan surat dokter jika alasan sakit (PDF/JPG)</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted">CATATAN TAMBAHAN</label>
                        <textarea name="catatan" class="form-control border-2 rounded-3" rows="3" placeholder="Info tambahan bagi Yayasan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-pmu rounded-pill px-5 fw-bold shadow">KIRIM PENGAJUAN</button>
                </div>
            </div>
        </form>
    </div>
</div>
    </div>
</div>
@endsection

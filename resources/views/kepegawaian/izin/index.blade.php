@extends('layouts.app')

@section('title', 'Permohonan Izin Pegawai')

@section('content')
<style>
    /* Custom Premium Green Pagination Styling */
    .pagination {
        margin-bottom: 0;
        gap: 6px;
    }
    .page-item .page-link {
        border-radius: 50% !important;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1a4d2e;
        border: 1px solid #e9ecef;
        background-color: #fff;
        font-weight: 700;
        font-size: 0.85rem;
        transition: all 0.25s ease;
        box-shadow: none;
    }
    .page-item.active .page-link {
        background: var(--pmu-gradient, #1a4d2e) !important;
        border-color: #1a4d2e !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(26, 77, 46, 0.2) !important;
    }
    .page-item:first-child .page-link, .page-item:last-child .page-link {
        border-radius: 20px !important;
        width: auto;
        padding-left: 16px;
        padding-right: 16px;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .page-item.disabled .page-link {
        color: #adb5bd;
        background-color: #f8f9fa;
        border-color: #e9ecef;
    }
    .page-link:hover:not(.disabled) {
        background-color: #1a4d2e;
        color: #fff !important;
        border-color: #1a4d2e;
        transform: translateY(-2px);
    }
</style>
<div class="container-fluid px-0">
    <!-- Premium Header -->
    <div class="bg-pmu p-4 p-md-5 text-white mb-4">
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-white text-pmu rounded-pill px-3 py-1 fw-bold small me-3">ADMINISTRASI</span>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small opacity-75 fw-bold">
                    <li class="breadcrumb-item"><a href="#" class="text-white text-decoration-none">Kepegawaian</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Permohonan Izin</li>
                </ol>
            </nav>
        </div>
        <h2 class="fw-bold mb-0">PERMOHONAN & STATUS IZIN</h2>
        <p class="mb-0 opacity-75">Kelola data permohonan izin sakit, dinas luar, dan izin penting lainnya.</p>
    </div>

    <div class="px-3 px-md-5 pb-5">
        <!-- Quick Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-bottom border-4 border-success h-100 transition-all hover-translate-y">
                    <div class="d-flex align-items-center gap-4">
                        <div class="bg-success bg-opacity-10 p-3 rounded-4 text-success shadow-none">
                            <i class="fa-solid fa-file-invoice fs-3"></i>
                        </div>
                        <div>
                            <div class="text-dark fw-bold text-uppercase ls-1 opacity-50 mb-1" style="font-size: 0.7rem;">Total Pengajuan</div>
                            <div class="h2 fw-bold mb-0 text-dark">{{ $izins->total() }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-bottom border-4 border-warning h-100 transition-all hover-translate-y">
                    <div class="d-flex align-items-center gap-4">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-4 text-warning shadow-none">
                            <i class="fa-solid fa-hourglass-half fs-3"></i>
                        </div>
                        <div>
                            @php
                                $menungguIzinCount = \App\Models\Izin::where('status', 'Pending');
                                if(Auth::user()->isPegawai()) {
                                    $menungguIzinCount->where('user_id', Auth::id());
                                } elseif(Auth::user()->isAdminUnit()) {
                                    $menungguIzinCount->whereHas('user', function($q) {
                                        $q->where('unit', Auth::user()->unit);
                                    });
                                }
                            @endphp
                            <div class="h2 fw-bold mb-0 text-dark">{{ $menungguIzinCount->count() }} <small class="fs-6 fw-normal opacity-50">Data</small></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-bottom border-4 border-pmu h-100 transition-all hover-translate-y">
                    <div class="d-flex align-items-center gap-4">
                        <div class="bg-pmu bg-opacity-10 p-3 rounded-4 text-pmu shadow-none">
                            <i class="fa-solid fa-circle-check fs-3"></i>
                        </div>
                        <div>
                            @php
                                $setujuIzinCount = \App\Models\Izin::where('status', 'Disetujui');
                                if(Auth::user()->isPegawai()) {
                                    $setujuIzinCount->where('user_id', Auth::id());
                                } elseif(Auth::user()->isAdminUnit()) {
                                    $setujuIzinCount->whereHas('user', function($q) {
                                        $q->where('unit', Auth::user()->unit);
                                    });
                                }
                            @endphp
                            <div class="h2 fw-bold mb-0 text-dark">{{ $setujuIzinCount->count() }} <small class="fs-6 fw-normal opacity-50">Data</small></div>
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

                    <div class="col-12 col-md-auto">
                        <button type="button" class="btn btn-pmu fw-bold rounded-pill px-4 shadow-sm py-2 d-flex align-items-center gap-2 hover-translate-y transition-all" data-bs-toggle="modal" data-bs-target="#tambahIzinModal" style="background: var(--pmu-gradient) !important;">
                            <i class="fa-solid fa-circle-plus fs-5"></i> BUAT PENGAJUAN
                        </button>
                    </div>

                    <div class="col-12 col-md-auto ms-auto">
                        <div class="input-group rounded-pill overflow-hidden border bg-light px-3 py-1 transition-all focus-within-shadow">
                            <span class="bg-transparent border-0 d-flex align-items-center">
                                <i class="fa-solid fa-search text-muted small"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-0 bg-transparent py-2 shadow-none small fw-bold" placeholder="Cari Pegawai..." value="{{ request('search') }}">
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
                        <thead class="bg-light bg-opacity-50 border-bottom">
                            <tr class="text-uppercase text-dark fw-bold ls-1" style="font-size: 0.75rem;">
                                <th class="ps-4 py-3" style="width: 50px;">No.</th>
                                <th class="py-3">Pegawai</th>
                                <th class="py-3">Jenis & Alasan</th>
                                <th class="py-3 text-center">Periode Izin</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="pe-4 py-3 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($izins as $index => $izin)
                        <tr class="border-bottom">
                            <td class="ps-4">
                                <span class="text-muted fw-bold" style="font-size: 0.7rem;">{{ ($izins->firstItem() ?? 1) + $index }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-none border border-2 border-white" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                        {{ substr($izin->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="small fw-bold text-uppercase mb-0 text-dark">{{ $izin->user->name ?? '?' }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">PIN: {{ $izin->user->pin_fingerspot ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="badge bg-pmu text-white rounded-pill px-3 py-1 mb-1 fw-bold" style="font-size: 0.65rem;">{{ $izin->jenis_izin }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.7rem; max-width: 150px;">{{ $izin->alasan }}</div>
                            </td>
                            <td class="text-center">
                                <span class="small fw-bold text-dark">{{ $izin->tgl_mulai->translatedFormat('d M Y') }}</span>
                                <div class="text-muted" style="font-size: 0.6rem;">s/d {{ $izin->tgl_selesai ? $izin->tgl_selesai->translatedFormat('d M Y') : '-' }}</div>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusBadge = match($izin->status) {
                                        'Disetujui' => ['soft-success', 'DISETUJUI'],
                                        'Ditolak' => ['soft-danger', 'DITOLAK'],
                                        default => ['soft-warning', 'PENDING']
                                    };
                                @endphp
                                <span class="badge badge-{{ $statusBadge[0] }} rounded-pill px-3 py-2 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">{{ $statusBadge[1] }}</span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end">
                                    <div class="btn-group-premium shadow-sm">
                                        <button type="button" class="btn-action border-0 bg-transparent" onclick='showDetailIzin("{{ $izin->id }}", "{{ $izin->user->name }}", "{{ $izin->alasan }}")' title="Lihat Detail">
                                            <i class="fa-solid fa-eye text-pmu"></i>
                                        </button>
                                        
                                        @if($izin->lampiran)
                                        <a class="btn-action" href="{{ route('kepegawaian.dokumen.view', ['path' => $izin->lampiran]) }}" target="_blank" title="Lampiran">
                                            <i class="fa-solid fa-paperclip text-info"></i>
                                        </a>
                                        @endif

                                        @if((Auth::user()->isYayasan() || Auth::user()->isAdminUnit()) && $izin->status == 'Pending')
                                        <button type="button" class="btn-action border-0 bg-transparent" onclick='approveIzin("{{ $izin->id }}", "{{ $izin->user->name }}")' title="Validasi">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                        </button>
                                        @endif

                                        @if($izin->user_id == Auth::id() && $izin->status == 'Pending')
                                        <form action="{{ route('kepegawaian.izin.destroy', $izin->id) }}" method="POST" onsubmit="return confirm('Batalkan pengajuan ini?')" class="d-inline-flex">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-action border-0 bg-transparent" title="Hapus">
                                                <i class="fa-solid fa-trash-can text-danger"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fa-solid fa-envelope-open text-muted opacity-25 fa-3x mb-3"></i>
                                <p class="text-muted small fw-bold mb-0">Belum ada data permohonan izin.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($izins->hasPages())
            <div class="card-footer bg-white border-0 py-4 px-4 border-top">
                {{ $izins->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
<!-- Modal Tambah Izin -->
<div class="modal fade" id="tambahIzinModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('kepegawaian.izin.store') }}" method="POST" enctype="multipart/form-data" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow-lg mt-5" style="border-radius: 20px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <h5 class="modal-title fw-bold text-uppercase ls-1">BUAT PENGAJUAN IZIN</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-md-5">
                    @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                    <div class="mb-4">
                        <div class="form-check form-switch bg-light p-3 rounded-4 border">
                            <input class="form-check-input ms-0 me-3" type="checkbox" name="is_masal" id="is_masal" value="1">
                            <label class="form-check-label fw-bold small text-dark" for="is_masal">
                                @if(Auth::user()->isYayasan())
                                LIBUR MASAL (Terapkan ke Semua Pegawai)
                                @else
                                LIBUR MASAL (Terapkan ke Semua Pegawai Unit {{ Auth::user()->unit }})
                                @endif
                            </label>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            @if(Auth::user()->isYayasan())
                            Centang jika ingin meliburkan seluruh pegawai (misal: Hari Raya/Libur Nasional).
                            @else
                            Centang jika ingin meliburkan seluruh pegawai di unit {{ Auth::user()->unit }} (misal: Libur Khusus Unit).
                            @endif
                        </small>
                    </div>
                    @endif

                    @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                    <div class="mb-4" id="select_pegawai_container">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Pilih Pegawai</label>
                        <select name="user_id" class="form-select rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                            <option value="">-- PILIH PEGAWAI --</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ strtoupper($u->name) }} (PIN: {{ $u->pin_fingerspot ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Jenis Izin</label>
                        <select name="jenis_izin" id="jenis_izin" class="form-select rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                            <option value="Sakit">SAKIT</option>
                            <option value="Izin">IZIN PENTING</option>
                            @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                            <option value="Libur">LIBUR (HARI BESAR/UJIAN/DLL)</option>
                            @endif
                        </select>
                        <div class="mt-3 bg-light p-3 rounded-4 border">
                            <h6 class="fw-bold small text-dark mb-2 text-uppercase ls-1"><i class="fa-solid fa-circle-info me-2 text-primary"></i> Instruksi Pengajuan:</h6>
                            <ul class="mb-0 small text-muted ps-3">
                                <li>Pilih <b>SAKIT</b> untuk izin medis/sakit.</li>
                                <li>Pilih <b>IZIN PENTING</b> untuk keperluan pribadi mendesak.</li>
                                @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                                <li>Pilih <b>LIBUR</b> untuk hari yang diliburkan sistem (seperti liburan semester/pasca ujian).</li>
                                <li>Gunakan <b>LIBUR MASAL</b> (Switch di atas) jika ingin meliburkan seluruh pegawai sekaligus.</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Tanggal Sampai</label>
                            <input type="date" name="tgl_selesai" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Alasan / Keterangan</label>
                        <textarea name="alasan" class="form-control rounded-4 border p-4 shadow-none" rows="3" placeholder="Sebutkan alasan izin atau dinas luar Anda secara jelas..." required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Lampiran (Jika Ada)</label>
                        <div class="input-group">
                            <input type="file" name="lampiran" class="form-control rounded-pill border p-3 shadow-none">
                        </div>
                        <small class="text-muted mt-2 d-block">Upload bukti seperti Surat Dokter atau Surat Tugas.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 p-md-5 pt-0">
                    <button type="submit" class="btn btn-pmu rounded-pill px-5 fw-bold w-100 shadow py-3">KIRIM PENGAJUAN SEKARANG</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Izin -->
<div class="modal fade" id="modalDetailIzin" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-pmu text-white p-4 border-0">
                <h5 class="modal-title fw-bold text-uppercase ls-1">DETAIL PERMOHONAN</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-2">
                    <div class="col-4 text-muted small">Nama Pegawai</div>
                    <div class="col-8 fw-bold small text-dark" id="det_nama">: -</div>
                </div>
                <div class="row mb-4">
                    <div class="col-4 text-muted small">Alasan Izin</div>
                    <div class="col-8 small text-dark" id="det_alasan" style="white-space: pre-line;">: -</div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 p-md-5 pt-0">
                <button type="button" class="btn btn-pmu rounded-pill px-5 fw-bold w-100 shadow py-3" data-bs-dismiss="modal">TUTUP DETAIL</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Approval -->
@if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
<div class="modal fade" id="modalApproval" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" id="formApproval" method="POST" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow-lg mt-5" style="border-radius: 20px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <h5 class="modal-title fw-bold text-uppercase ls-1">PROSES PERMOHONAN</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-md-5">
                    <div class="mb-4 text-center">
                        <div class="bg-light p-3 rounded-4 border mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1 d-block">Pegawai Terkait</label>
                            <input type="text" id="izin_user_name" class="form-control border-0 bg-transparent text-center fs-5 fw-bold text-dark p-0 shadow-none" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Keputusan Validasi</label>
                        <select name="status" class="form-select rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                            <option value="Disetujui">SETUJUI PERMOHONAN</option>
                            <option value="Ditolak">TOLAK PERMOHONAN</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Catatan / Respon Admin</label>
                        <textarea name="keterangan_admin" class="form-control rounded-4 border p-4 shadow-none" rows="3" placeholder="Berikan catatan singkat atau alasan persetujuan/penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 p-md-5 pt-0">
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold w-100 shadow py-3">SIMPAN & VALIDASI DATA</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isMasal = document.getElementById('is_masal');
        const container = document.getElementById('select_pegawai_container');
        const select = container ? container.querySelector('select') : null;

        if (isMasal) {
            isMasal.addEventListener('change', function() {
                if (this.checked) {
                    container.style.display = 'none';
                    if (select) select.removeAttribute('required');
                    document.getElementById('jenis_izin').value = 'Izin';
                } else {
                    container.style.display = 'block';
                    if (select) select.setAttribute('required', 'required');
                }
            });
        }
    });

    function showDetailIzin(id, name, alasan) {
        document.getElementById('det_nama').innerText = ': ' + name;
        document.getElementById('det_alasan').innerText = ': ' + alasan;
        new bootstrap.Modal(document.getElementById('modalDetailIzin')).show();
    }

    function approveIzin(id, name) {
        document.getElementById('formApproval').action = `/izin/${id}/status`;
        document.getElementById('izin_user_name').value = name;
        new bootstrap.Modal(document.getElementById('modalApproval')).show();
    }
</script>

    </div>
</div>
@endsection

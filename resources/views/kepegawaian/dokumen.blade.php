@extends('layouts.app')

@section('title', 'Arsip Dokumen Digital')

@section('content')
<style>
    .upload-zone:hover { background-color: rgba(26,77,46, 0.08) !important; transform: scale(1.02); }
    .table-data td { padding: 1rem 1.25rem !important; border-bottom: 1px solid #f1f5f9; }
    .doc-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .ls-1 { letter-spacing: 0.5px; }
</style>

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
                                <li class="breadcrumb-item active text-white" aria-current="page">Arsip Digital</li>
                            </ol>
                        </nav>
                    </div>
                    <h2 class="fw-bold mb-1 text-uppercase ls-1"><i class="fa-solid fa-folder-tree me-2"></i> ARSIP DOKUMEN DIGITAL</h2>
                    <p class="opacity-75 mb-0 fw-medium">Digitalisasi & penyimpanan berkas kepegawaian secara instan and aman.</p>
                </div>
                <i class="fa-solid fa-file-shield position-absolute end-0 top-0 m-4 fa-6x opacity-10"></i>
            </div>
        </div>
    </div>

    <div class="col-12 pb-5">
        @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center animate__animated animate__fadeIn">
            <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
            <span class="fw-bold text-dark">{{ session('success') }}</span>
        </div>
        @endif

        <div class="row g-4">
            <!-- Form Upload (Hanya untuk Pegawai) -->
            @if(Auth::user()->isPegawai())
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 position-sticky" style="top: 110px;">
                    <div class="card-header border-0 bg-transparent px-4 pt-4 pb-0">
                        <h6 class="fw-bold text-dark mb-0 text-uppercase ls-1"><i class="fa-solid fa-cloud-arrow-up me-2 text-pmu"></i> UNGGAH BERKAS</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('kepegawaian.dokumen.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase mb-2">KATEGORI DOKUMEN</label>
                                <select name="tipe" class="form-select rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                                    <option value="">-- PILIH KATEGORI --</option>
                                    <option value="KTP">KTP (Identitas Diri)</option>
                                    <option value="KK">Kartu Keluarga (KK)</option>
                                    <option value="Ijazah">Ijazah Terakhir</option>
                                    <option value="Sertifikat">Sertifikat / Pelatihan</option>
                                    <option value="SK">SK Mengajar / Tugas</option>
                                    <option value="Foto">Foto Profil Formal</option>
                                    <option value="Lainnya">Dokumen Lainnya</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase mb-2">FILE DOKUMEN (MAX 3MB)</label>
                                <div class="upload-zone p-4 bg-light rounded-4 text-center border-dashed border-2 position-relative transition-all cursor-pointer border-secondary border-opacity-25">
                                    <i class="fa-solid fa-file-pdf fa-3x text-muted mb-3"></i>
                                    <p class="small text-muted mb-0 fw-bold">Klik untuk memilih file</p>
                                    <input type="file" name="file" class="form-control opacity-0 position-absolute top-0 start-0 w-100 h-100 cursor-pointer" required onchange="if(this.files.length) { this.parentElement.querySelector('p').innerText = this.files[0].name; this.parentElement.querySelector('p').className = 'fw-bold text-pmu small'; }">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-pmu w-100 py-3 rounded-pill fw-bold shadow-sm">
                                <i class="fa-solid fa-upload me-2"></i> ARSIPKAN SEKARANG
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Daftar Dokumen -->
            <div class="col-lg-{{ Auth::user()->isPegawai() ? '8' : '12' }}">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                    <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <h6 class="fw-bold text-dark mb-0 text-uppercase ls-1"><i class="fa-solid fa-list me-2 text-pmu"></i> DAFTAR BERKAS TERARSIP</h6>
                        
                        @if(!Auth::user()->isPegawai())
                        <form action="{{ route('kepegawaian.dokumen') }}" method="GET" class="d-flex gap-2 w-100 w-md-auto">
                            <div class="input-group input-group-sm rounded-pill overflow-hidden border bg-light px-3 py-1 shadow-none" style="max-width: 300px;">
                                <span class="bg-transparent border-0 d-flex align-items-center">
                                    <i class="fa-solid fa-search text-muted"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-0 bg-transparent py-2 shadow-none small" placeholder="Cari Pegawai..." value="{{ request('search') }}">
                            </div>
                            <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold small">CARI</button>
                        </form>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-data" id="dokumenTable">
                                <thead class="bg-light bg-opacity-50 border-bottom">
                                    <tr class="text-uppercase small fw-bold text-dark ls-1">
                                        @if(!Auth::user()->isPegawai())
                                        <th class="ps-4 py-3">PEMILIK</th>
                                        @endif
                                        <th class="{{ Auth::user()->isPegawai() ? 'ps-4' : '' }} py-3">JENIS BERKAS</th>
                                        <th class="text-center py-3">STATUS</th>
                                        <th class="pe-4 text-end py-3">OPSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dokumens as $dok)
                                    <tr>
                                        @if(!Auth::user()->isPegawai())
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark small text-uppercase mb-0">{{ $dok->user->name }}</div>
                                            <div class="text-muted" style="font-size: 0.65rem;">Unit: {{ strtoupper($dok->user->unit) }}</div>
                                        </td>
                                        @endif
                                        <td class="{{ Auth::user()->isPegawai() ? 'ps-4' : '' }}">
                                            <div class="d-flex align-items-center">
                                                <div class="doc-icon {{ str_contains($dok->file_path, '.pdf') ? 'bg-danger bg-opacity-10 text-danger' : 'bg-primary bg-opacity-10 text-primary' }} me-3 shadow-none">
                                                    <i class="fa-solid {{ str_contains($dok->file_path, '.pdf') ? 'fa-file-pdf' : 'fa-file-image' }}"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark mb-0 small text-uppercase">{{ $dok->tipe }}</div>
                                                    <div class="text-muted" style="font-size: 0.6rem;"><i class="fa-solid fa-calendar-alt me-1"></i> {{ $dok->created_at->format('d M Y, H:i') }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 small fw-bold ls-1 d-inline-flex align-items-center">
                                                <i class="fa-solid fa-check-circle me-1"></i> ARSIP VALID
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <div class="btn-group shadow-sm rounded-pill overflow-hidden border bg-white">
                                                <a href="{{ route('kepegawaian.dokumen.view', ['path' => $dok->file_path]) }}" target="_blank" class="btn btn-white btn-sm px-3 py-2 border-0" title="Lihat Berkas">
                                                    <i class="fa-solid fa-eye text-pmu"></i>
                                                </a>
                                                <form action="{{ route('kepegawaian.dokumen.destroy', $dok->id) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-white btn-sm px-3 py-2 border-0 border-start" onclick="return confirm('Hapus permanen berkas ini?')" title="Hapus Permanen">
                                                        <i class="fa-solid fa-trash text-danger"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="py-5 text-center">
                                            <i class="fa-solid fa-folder-open text-muted opacity-25 fa-4x mb-3"></i>
                                            <p class="text-muted small fw-bold mb-0">Belum ada berkas digital yang terupload.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($dokumens->hasPages())
                    <div class="card-footer bg-white border-0 py-4 px-4 d-flex justify-content-center">
                        {{ $dokumens->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

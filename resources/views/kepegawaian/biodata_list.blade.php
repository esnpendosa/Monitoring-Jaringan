@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Premium Header -->
    <div class="bg-pmu p-4 p-md-5 text-white mb-4">
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-white text-pmu rounded-pill px-3 py-1 fw-bold small me-3">PORTAL PEGAWAI</span>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small opacity-75 fw-bold">
                    <li class="breadcrumb-item"><a href="#" class="text-white text-decoration-none">Kepegawaian</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Direktori Pegawai</li>
                </ol>
            </nav>
        </div>
        <h2 class="fw-bold mb-0">DIREKTORI & DATABASE PEGAWAI</h2>
        <p class="mb-0 opacity-75">Kelola informasi akun, biodata, dan hak akses seluruh staf PMU Bungah.</p>
    </div>

    <div class="px-3 px-md-5 pb-5">
        <!-- Quick Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-pmu h-100">
                    <div class="d-flex align-items-center">
                        <div class="bg-pmu bg-opacity-10 p-2 p-md-3 rounded-3 me-2 me-md-3 text-pmu">
                            <i class="fa-solid fa-users fs-4"></i>
                        </div>
                        <div>
                            <div class="text-dark small fw-bold text-uppercase" style="font-size: 0.65rem;">Total Pegawai</div>
                            <div class="h4 fw-bold mb-0 text-dark">{{ $users->total() }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning h-100">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-2 p-md-3 rounded-3 me-2 me-md-3 text-dark">
                            <i class="fa-solid fa-user-shield fs-4"></i>
                        </div>
                        <div>
                            <div class="text-dark small fw-bold text-uppercase" style="font-size: 0.65rem;">Admin Unit</div>
                            <div class="h4 fw-bold mb-0 text-dark">{{ \App\Models\User::where('role', 'admin_unit')->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger h-100">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 p-2 p-md-3 rounded-3 me-2 me-md-3 text-danger">
                            <i class="fa-solid fa-building-columns fs-4"></i>
                        </div>
                        <div>
                            <div class="text-dark small fw-bold text-uppercase" style="font-size: 0.65rem;">Yayasan / Pusat</div>
                            <div class="h4 fw-bold mb-0 text-dark">{{ \App\Models\User::where('role', 'yayasan')->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Row -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-body p-4 bg-white">
                <div class="row align-items-center g-3">
                    <div class="col-md-auto d-flex gap-2 flex-wrap">
                        <button class="btn btn-pmu d-flex align-items-center fw-bold rounded-pill px-4 shadow-sm py-2" data-bs-toggle="modal" data-bs-target="#addPegawaiModal">
                            <i class="fa-solid fa-user-plus me-2"></i> TAMBAH PEGAWAI
                        </button>
                        <a href="{{ route('kepegawaian.pegawai.export') }}" class="btn btn-success d-flex align-items-center fw-bold rounded-pill px-4 shadow-sm py-2">
                            <i class="fa-solid fa-file-excel me-2"></i> EXPORT EXCEL
                        </a>
                        <button class="btn btn-info text-white d-flex align-items-center fw-bold rounded-pill px-4 shadow-sm py-2" data-bs-toggle="modal" data-bs-target="#importPegawaiModal">
                            <i class="fa-solid fa-file-import me-2"></i> IMPORT DATA
                        </button>
                    </div>
                    <div class="col-md-auto ms-auto d-flex align-items-center gap-3 w-100 w-md-auto flex-wrap">
                        <form action="" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                            @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                            <div class="input-group input-group-sm rounded-pill border overflow-hidden bg-light" style="width: 220px;">
                                <span class="input-group-text bg-transparent border-0 pe-0"><i class="fa-solid fa-building text-muted"></i></span>
                                <select name="unit" class="form-select border-0 bg-transparent shadow-none small fw-bold py-2" onchange="this.form.submit()">
                                    <option value="">Semua Unit Kerja</option>
                                    @foreach($units as $un)
                                        <option value="{{ $un->nama }}" {{ request('unit') == $un->nama ? 'selected' : '' }}>{{ $un->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div class="input-group input-group-merge rounded-pill overflow-hidden border shadow-none" style="max-width: 300px;">
                                <span class="input-group-text bg-light border-0 px-3">
                                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-0 bg-light py-2 shadow-none" placeholder="Cari pegawai..." id="pegawaiSearch">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="border-bottom text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">
                                <th class="ps-4 py-3 text-muted fw-bold">Pegawai</th>
                                <th class="py-3 text-muted fw-bold">Unit Kerja</th>
                                <th class="py-3 text-muted fw-bold">Hak Akses</th>
                                <th class="py-3 text-muted fw-bold text-center">Kelengkapan Profil</th>
                                <th class="pe-4 py-3 text-end fw-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="pegawaiTable">
                            @forelse($users as $u)
                            <tr class="border-bottom">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                            {{ substr($u->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="small fw-bold text-dark text-uppercase mb-0">{{ $u->name }}</div>
                                            <div class="text-muted" style="font-size: 0.65rem;">PIN: {{ $u->pin_fingerspot ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="badge-unit">{{ $u->unit ?? 'UMUM' }}</span>
                                </td>
                                <td>
                                    @php
                                        $roleBadge = match($u->role) {
                                            'yayasan' => ['danger', 'YAYASAN'],
                                            'admin_unit' => ['warning', 'ADMIN UNIT'],
                                            default => ['success', 'PEGAWAI']
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $roleBadge[0] }} bg-opacity-10 text-{{ $roleBadge[0] }} rounded-pill px-3 py-2 fw-bold" style="font-size: 0.6rem;">{{ $roleBadge[1] }}</span>
                                </td>
                                <td>
                                    @php
                                        $filled = 0;
                                        if($u->biodata) {
                                            if($u->biodata->nik) $filled++;
                                            if($u->biodata->tempat_lahir) $filled++;
                                            if($u->biodata->alamat) $filled++;
                                            if($u->biodata->no_wa) $filled++;
                                        }
                                        $percent = ($filled / 4) * 100;
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress shadow-none flex-grow-1" style="height: 6px; width: 80px; border-radius: 10px;">
                                            <div class="progress-bar bg-{{ $percent == 100 ? 'success' : 'warning' }}" style="width: {{ $percent }}%;"></div>
                                        </div>
                                        <small class="fw-bold text-muted" style="font-size: 9px;">{{ $percent }}%</small>
                                    </div>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle shadow-none border" data-bs-toggle="dropdown" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-ellipsis-vertical text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2" style="font-size: 0.8rem; min-width: 180px;">
                                            <li><a class="dropdown-item rounded-3 py-2" href="{{ route('kepegawaian.biodata', ['user_id' => $u->id]) }}"><i class="fa-solid fa-id-card me-2 text-pmu opacity-75"></i> Lihat Profil</a></li>
                                            <li><a class="dropdown-item rounded-3 py-2" href="{{ route('kepegawaian.absensi', ['user_id' => $u->id]) }}"><i class="fa-solid fa-clock-rotate-left me-2 text-success opacity-75"></i> Laporan Absensi</a></li>
                                            <li><hr class="dropdown-divider opacity-50"></li>
                                            <li><a class="dropdown-item rounded-3 py-2" href="#" data-bs-toggle="modal" data-bs-target="#editPegawai{{ $u->id }}"><i class="fa-solid fa-pen-to-square me-2 text-warning opacity-75"></i> Edit Akun</a></li>
                                            <li>
                                                <form action="{{ route('kepegawaian.pegawai.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Hapus pegawai ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item rounded-3 py-2 text-danger"><i class="fa-solid fa-trash-can me-2 opacity-75"></i> Hapus</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                                    <!-- Modal Edit Pegawai -->
                                    <div class="modal text-start" id="editPegawai{{ $u->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered modal-xl shadow-lg">
                                            <form action="{{ route('kepegawaian.pegawai.update', $u->id) }}" method="POST" class="w-100">
                                                @csrf @method('PUT')
                                                <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                                                    <div class="modal-header bg-pmu text-white p-4 border-0">
                                                        <h5 class="modal-title fw-bold text-uppercase ls-1">EDIT AKUN: {{ $u->name }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4 p-md-5">
                                                        <div class="row g-4">
                                                            <div class="col-md-6">
                                                                <label class="form-label text-dark fw-bold small text-uppercase ls-1">Nama Lengkap</label>
                                                                <input type="text" name="name" class="form-control rounded-3 py-3 shadow-none" value="{{ $u->name }}" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-dark fw-bold small text-uppercase ls-1">Email / Username</label>
                                                                <input type="email" name="email" class="form-control rounded-3 py-3 shadow-none" value="{{ $u->email }}" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-dark fw-bold small text-uppercase ls-1">Unit Kerja</label>
                                                                <select name="unit" class="form-select rounded-3 py-3 shadow-none" required>
                                                                    @foreach($global_units as $un)
                                                                    <option value="{{ $un->nama }}" {{ $u->unit == $un->nama ? 'selected' : '' }}>{{ $un->nama }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-dark fw-bold small text-uppercase ls-1">Hak Akses</label>
                                                                <select name="role" class="form-select rounded-3 py-3 shadow-none" required>
                                                                    <option value="pegawai" {{ $u->role == 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                                                                    <option value="admin_unit" {{ $u->role == 'admin_unit' ? 'selected' : '' }}>Admin Unit</option>
                                                                    <option value="yayasan" {{ $u->role == 'yayasan' ? 'selected' : '' }}>Yayasan</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-dark fw-bold small text-uppercase ls-1">PIN / ID Fingerprint</label>
                                                                <input type="text" name="pin_fingerspot" class="form-control rounded-3 py-3 shadow-none bg-light" value="{{ $u->pin_fingerspot }}" readonly>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-dark fw-bold small text-uppercase ls-1">Kode Pegawai</label>
                                                                <input type="text" class="form-control rounded-3 py-3 shadow-none bg-light" value="{{ $u->biodata->kode_pegawai ?? '-' }}" readonly>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-dark fw-bold small text-uppercase ls-1">Password Baru</label>
                                                                <input type="password" name="password" class="form-control rounded-3 py-3 shadow-none" placeholder="Isi untuk ganti">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 p-4 p-md-5 pt-0">
                                                        <button type="submit" class="btn btn-pmu w-100 rounded-pill py-3 fw-bold shadow">SIMPAN PERUBAHAN DATA</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center text-muted small fw-bold">Tidak ada data pegawai.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($users->hasPages())
            <div class="card-footer bg-white border-0 py-4 px-4 border-top">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tambah Pegawai -->
<div class="modal fade" id="addPegawaiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl shadow-lg">
        <form action="{{ route('kepegawaian.pegawai.store') }}" method="POST" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <div>
                        <h5 class="modal-title fw-bold text-uppercase ls-1"><i class="fa-solid fa-user-plus me-2"></i> TAMBAH AKUN PEGAWAI</h5>
                        <p class="mb-0 small opacity-75">Akun baru akan otomatis mendapatkan link notifikasi WA.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-md-5">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small text-uppercase ls-1">NAMA LENGKAP</label>
                            <input type="text" name="name" class="form-control rounded-3 py-3" placeholder="Nama tanpa gelar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small text-uppercase ls-1">EMAIL (USERNAME LOGIN)</label>
                            <input type="email" name="email" class="form-control rounded-3 py-3" placeholder="contoh@gmail.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small text-uppercase ls-1">UNIT KERJA</label>
                             <select name="unit" class="form-select rounded-3 py-3" required>
                                 <option value="">-- PILIH UNIT KERJA --</option>
                                 @foreach($global_units as $un)
                                     <option value="{{ $un->nama }}">{{ $un->nama }}</option>
                                 @endforeach
                             </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small text-uppercase ls-1">HAK AKSES</label>
                            <select name="role" class="form-select rounded-3 py-3" required>
                                <option value="pegawai">Pegawai</option>
                                <option value="admin_unit">Admin Unit</option>
                                <option value="yayasan">Yayasan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small text-uppercase ls-1">NOMOR WHATSAPP</label>
                            <input type="text" name="no_wa" class="form-control rounded-3 py-3" placeholder="08XXXXXXXXXX" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold small text-uppercase ls-1">PASSWORD AWAL</label>
                            <div class="input-group">
                                <input type="text" name="password" id="passInput" class="form-control rounded-3 rounded-end-0 py-3 border-end-0" value="{{ Str::random(8) }}" required>
                                <button class="btn btn-outline-secondary rounded-3 rounded-start-0 border-start-0 px-3" type="button" onclick="document.getElementById('passInput').value = Math.random().toString(36).slice(-8)">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-4 p-md-5 pt-0 border-0">
                    <button type="submit" class="btn btn-pmu rounded-pill py-3 fw-bold shadow w-100">BUAT AKUN & GENERATE WA</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('pegawaiSearch')?.addEventListener('keyup', function() {
        let value = this.value.toLowerCase();
        let rows = document.querySelectorAll('#pegawaiTable tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
        });
    });
</script>
<style>
    @media (min-width: 992px) {
        .modal-xl { max-width: 1100px !important; }
        .modal-content { min-width: 800px; }
    }
</style>
    </div>
</div>

<!-- Modal Import Pegawai -->
<div class="modal fade" id="importPegawaiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('kepegawaian.pegawai.import') }}" method="POST" enctype="multipart/form-data" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-info text-white p-4 border-0">
                    <h5 class="modal-title fw-bold text-uppercase ls-1">IMPORT DATA PEGAWAI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-md-5">
                    <div class="alert alert-warning border-0 rounded-4 small mb-4">
                        <h6 class="fw-bold mb-2"><i class="fa-solid fa-circle-info me-2"></i> Ketentuan Import:</h6>
                        <ul class="mb-0 ps-3">
                            <li>Format file harus <b>.xlsx</b> atau <b>.xls</b></li>
                            <li>Pastikan heading/kolom sesuai dengan template</li>
                            <li>Email bersifat unik (jika data sudah ada, akan otomatis diupdate)</li>
                            <li>Password default adalah username jika dikosongkan</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Pilih File Excel</label>
                        <input type="file" name="file" class="form-control rounded-pill border p-3 shadow-none" required>
                    </div>

                    <div class="text-center bg-light p-4 rounded-4 border border-dashed">
                        <p class="small text-muted mb-3">Belum punya formatnya? Download template resmi di sini:</p>
                        <a href="{{ route('kepegawaian.pegawai.template') }}" class="btn btn-outline-dark rounded-pill px-4 fw-bold small">
                            <i class="fa-solid fa-download me-2"></i> DOWNLOAD TEMPLATE XLSX
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 p-md-5 pt-0">
                    <button type="submit" class="btn btn-info text-white rounded-pill px-5 fw-bold w-100 shadow py-3">MULAI IMPORT DATA SEKARANG</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts/contentNavbarLayout')

@section('title', 'Laporan Kehadiran Pegawai')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius: 16px;">
            <div class="position-absolute end-0 bottom-0 opacity-10" style="font-size: 15rem; transform: translate(10%, 20%); line-height: 1;">
                <i class="bx bx-chart"></i>
            </div>
            <div class="card-body p-4 p-md-5">
                <h4 class="card-title text-white mb-2 fw-bold"><i class="bx bx-chart me-2"></i> REKAPITULASI & LAPORAN ABSENSI</h4>
                <p class="mb-3 text-white-50">Kelola riwayat kehadiran karyawan, cetak rekapitulasi bulanan, import CSV, dan buat koreksi manual.</p>
                <div class="d-inline-flex align-items-center bg-white text-primary fw-bold px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.85rem;">
                    <i class="bx bx-calendar me-2"></i> Periode: {{ $periodeLabel ?? '-' }}
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center" style="border-radius: 12px;">
    <i class="bx bx-check-circle me-2 fs-4"></i> {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center" style="border-radius: 12px;">
    <i class="bx bx-error-circle me-2 fs-4"></i> {{ session('error') }}
</div>
@endif

<!-- FILTER CARD -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
    <div class="card-body p-4">
        <form action="{{ route('absensi.index') }}" method="GET" id="formFilter">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="row g-3">
                <!-- Baris 1: Pegawai + Bulan/Tahun -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">PEGAWAI / KARYAWAN</label>
                    <select name="user_id" class="form-select" style="border-radius: 8px;">
                        @if(Auth::user()->id_role != 4)
                        <option value="all" {{ $targetUserId === 'all' ? 'selected' : '' }}>👥 SEMUA PEGAWAI (Kolektif)</option>
                        @endif
                        @foreach($allUsers as $u)
                        <option value="{{ $u->id }}" {{ $targetUserId == $u->id ? 'selected' : '' }}>👤 {{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">BULAN</label>
                    <select name="month" class="form-select" style="border-radius: 8px;">
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (!($useCustomDate??false) && $month==$m) ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">TAHUN</label>
                    <select name="year" class="form-select" style="border-radius: 8px;">
                        @for($y = date('Y') - 3; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ (!($useCustomDate??false) && $year==$y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="p-3 rounded-3 w-100" style="background:#f8f9ff; border: 2px dashed #c7d2fe;">
                        <div class="fw-bold small text-primary mb-2"><i class="bx bx-calendar-range me-1"></i> ATAU FILTER RENTANG TANGGAL TERTENTU</div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <div>
                                <div class="small text-muted mb-1">Dari</div>
                                <input type="date" name="start_date" id="start_date"
                                    class="form-control form-control-sm"
                                    style="border-radius: 8px; min-width:140px;"
                                    value="{{ ($useCustomDate ?? false) ? $startDate->toDateString() : '' }}">
                            </div>
                            <div class="text-muted fw-bold mt-3">—</div>
                            <div>
                                <div class="small text-muted mb-1">Sampai</div>
                                <input type="date" name="end_date" id="end_date"
                                    class="form-control form-control-sm"
                                    style="border-radius: 8px; min-width:140px;"
                                    value="{{ ($useCustomDate ?? false) ? $endDate->toDateString() : '' }}">
                            </div>
                        </div>
                        <small class="text-muted mt-1 d-block">Jika diisi, rentang ini akan digunakan (bulan/tahun diabaikan).</small>
                    </div>
                </div>

                <!-- Baris 2: Tombol -->
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <a href="{{ route('absensi.index') }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                        <i class="bx bx-reset me-1"></i> Reset
                    </a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        <i class="bx bx-filter-alt me-1"></i> Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ACTIONS BAR -->
@if(Auth::user()->id_role == 1)
<div class="d-flex flex-wrap gap-2 mb-4">
    <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalManual">
        <i class="bx bx-plus-circle me-1"></i> Input Absen Manual
    </button>
    <button class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#modalBatch">
        <i class="bx bx-group me-1"></i> Pilih Karyawan Tanggal Tertentu
    </button>
    <button class="btn btn-outline-success rounded-pill px-4 fw-bold bg-white" data-bs-toggle="modal" data-bs-target="#modalImport">
        <i class="bx bx-upload me-1"></i> Import CSV (Backup)
    </button>
    <a href="{{ route('absensi.export', ['user_id' => $targetUserId, 'month' => $month, 'year' => $year, 'start_date' => ($useCustomDate ?? false) ? $startDate->toDateString() : '', 'end_date' => ($useCustomDate ?? false) ? $endDate->toDateString() : '']) }}" class="btn btn-outline-indigo rounded-pill px-4 fw-bold bg-white">
        <i class="bx bx-download me-1"></i> Ekspor CSV
    </a>
    <a href="{{ route('absensi.pdf', ['user_id' => $targetUserId, 'month' => $month, 'year' => $year]) }}" class="btn btn-outline-danger rounded-pill px-4 fw-bold bg-white">
        <i class="bx bxs-file-pdf me-1"></i> Cetak PDF
    </a>
    <form action="{{ route('absensi.send-rekap') }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <button type="submit" class="btn btn-outline-primary rounded-pill px-4 fw-bold bg-white" onclick="return confirm('Kirim rekap absensi ke nomor WhatsApp target?')">
            <i class="bx bxl-whatsapp me-1 text-success"></i> Kirim Rekap WA
        </button>
    </form>
</div>
@endif

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius: 16px; border-left: 4px solid #22c55e !important;">
            <div class="d-flex align-items-center">
                <div class="p-3 me-3 rounded-3" style="background-color: #dcfce7; color: #15803d;">
                    <i class="bx bx-calendar-check fs-2"></i>
                </div>
                <div>
                    <h6 class="text-muted small fw-bold mb-1">TOTAL HADIR</h6>
                    <h3 class="fw-bold mb-0 text-success">{{ $counts['hadir'] }} <small class="fs-6">Hari</small></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius: 16px; border-left: 4px solid #0ea5e9 !important;">
            <div class="d-flex align-items-center">
                <div class="p-3 me-3 rounded-3" style="background-color: #e0f2fe; color: #0369a1;">
                    <i class="bx bx-home-heart fs-2"></i>
                </div>
                <div>
                    <h6 class="text-muted small fw-bold mb-1">WFH</h6>
                    <h3 class="fw-bold mb-0 text-info">{{ $counts['wfh'] ?? 0 }} <small class="fs-6">Hari</small></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius: 16px; border-left: 4px solid #f59e0b !important;">
            <div class="d-flex align-items-center">
                <div class="p-3 me-3 rounded-3" style="background-color: #fef3c7; color: #b45309;">
                    <i class="bx bx-paper-plane fs-2"></i>
                </div>
                <div>
                    <h6 class="text-muted small fw-bold mb-1">IZIN / SAKIT</h6>
                    <h3 class="fw-bold mb-0 text-warning">{{ $counts['izin_sakit'] ?? 0 }} <small class="fs-6">Hari</small></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius: 16px; border-left: 4px solid #ef4444 !important;">
            <div class="d-flex align-items-center">
                <div class="p-3 me-3 rounded-3" style="background-color: #fee2e2; color: #991b1b;">
                    <i class="bx bx-calendar-x fs-2"></i>
                </div>
                <div>
                    <h6 class="text-muted small fw-bold mb-1">TOTAL ALPHA</h6>
                    <h3 class="fw-bold mb-0 text-danger">{{ $counts['alpha'] }} <small class="fs-6">Hari</small></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MAIN DATA TABLE -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bx bx-table text-primary me-2"></i>
                    {{ $tab === 'bulanan' ? 'REKAPITULASI PRESENSI BULANAN' : 'RIWAYAT PRESENSI HARIAN' }}
                </h5>
                <div class="btn-group bg-light rounded-pill p-1" role="group">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'bulanan']) }}" class="btn btn-sm rounded-pill px-4 fw-bold {{ $tab === 'bulanan' ? 'btn-primary' : 'btn-transparent text-muted' }}">Bulanan</a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'harian']) }}" class="btn btn-sm rounded-pill px-4 fw-bold {{ $tab === 'harian' ? 'btn-primary' : 'btn-transparent text-muted' }}">Harian</a>
                </div>
            </div>

            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    @if($tab === 'bulanan')
                        @if($targetUserId === 'all')
                            <!-- Collective Monthly -->
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light border-bottom">
                                    <tr class="text-uppercase small fw-bold text-dark">
                                        <th class="ps-4 py-3">Nama Pegawai</th>
                                        <th class="py-3">PIN</th>
                                        <th class="text-center py-3">Hadir</th>
                                        <th class="text-center py-3">WFH</th>
                                        <th class="text-center py-3">Izin/Sakit</th>
                                        <th class="text-center py-3">Alpha</th>
                                        <th class="text-center py-3">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm rounded-circle text-white" style="width: 38px; height: 38px; background-color: #6366f1; flex-shrink: 0;">
                                                    {{ substr($row['user']->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $row['user']->name }}</div>
                                                    <small class="text-muted">{{ $row['work_days'] ?? '-' }} hari wajib kerja</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 fw-semibold text-muted">{{ $row['user']->pin_fingerspot ?? '-' }}</td>
                                        <td class="text-center py-3 fw-bold text-success">{{ $row['hadir'] }}</td>
                                        <td class="text-center py-3">
                                            @if(($row['wfh'] ?? 0) > 0)
                                                <span class="badge rounded-pill fw-bold px-3" style="background:#0ea5e9;">{{ $row['wfh'] }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center py-3">
                                            @if(($row['izin_sakit'] ?? 0) > 0)
                                                <span class="badge bg-warning text-dark rounded-pill fw-bold px-3">{{ $row['izin_sakit'] }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center py-3 fw-bold text-danger">{{ $row['alpha'] }}</td>
                                        <td class="py-3">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <div class="progress w-50" style="height: 8px; border-radius: 4px;">
                                                    <div class="progress-bar {{ $row['persentase'] >= 80 ? 'bg-success' : ($row['persentase'] >= 60 ? 'bg-warning' : 'bg-danger') }}" role="progressbar" style="width: {{ $row['persentase'] }}%"></div>
                                                </div>
                                                <span class="fw-bold text-dark small">{{ $row['persentase'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="py-5 text-center text-muted">Belum ada data kehadiran.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @else
                            <!-- Individual Monthly -->
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light border-bottom">
                                    <tr class="text-uppercase small fw-bold text-dark">
                                        <th class="ps-4 py-3">Bulan</th>
                                        <th class="text-center py-3">Hadir</th>
                                        <th class="text-center py-3">WFH</th>
                                        <th class="text-center py-3">Izin/Sakit</th>
                                        <th class="text-center py-3">Alpha</th>
                                        <th class="text-center py-3">Hari Kerja</th>
                                        <th class="text-center py-3">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bulananData as $row)
                                    <tr>
                                        <td class="ps-4 py-3 fw-bold text-dark">{{ $row['bulan'] }}</td>
                                        <td class="text-center py-3 fw-bold text-success">{{ $row['hadir'] }}</td>
                                        <td class="text-center py-3">
                                            @if(($row['wfh'] ?? 0) > 0)
                                                <span class="badge rounded-pill fw-bold px-3" style="background:#0ea5e9;">{{ $row['wfh'] }}</span>
                                            @else <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center py-3">
                                            @if(($row['izin_sakit'] ?? 0) > 0)
                                                <span class="badge bg-warning text-dark rounded-pill fw-bold px-3">{{ $row['izin_sakit'] }}</span>
                                            @else <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center py-3 fw-bold text-danger">{{ $row['alpha'] }}</td>
                                        <td class="text-center py-3 fw-semibold text-muted">{{ $row['total'] }}</td>
                                        <td class="py-3">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <div class="progress w-50" style="height: 8px; border-radius: 4px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ $row['persentase'] }}%"></div>
                                                </div>
                                                <span class="fw-bold text-dark small">{{ $row['persentase'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="py-5 text-center text-muted">Belum ada data bulanan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @endif
                    @else
                        @if($targetUserId === 'all')
                            <!-- Collective Daily -->
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light border-bottom">
                                    <tr class="text-uppercase small fw-bold text-dark">
                                        <th class="ps-4 py-3">Nama Pegawai</th>
                                        <th class="py-3">Tanggal</th>
                                        <th class="text-center py-3">Masuk</th>
                                        <th class="text-center py-3">Pulang</th>
                                        <th class="text-center py-3">Status</th>
                                        <th class="text-center py-3">Lokasi</th>
                                        @if(Auth::user()->id_role == 1)
                                        <th class="pe-4 text-end py-3">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark">{{ $row->user->name ?? 'N/A' }}</div>
                                            <small class="text-muted">PIN: {{ $row->pin ?? '-' }}</small>
                                        </td>
                                        <td class="py-3 fw-semibold text-muted">{{ $row->tgl->translatedFormat('d F Y') }}</td>
                                        <td class="text-center py-3 fw-bold text-success fs-6">{{ $row->jam_masuk ?? '--:--' }}</td>
                                        <td class="text-center py-3 fw-bold text-danger fs-6">{{ $row->jam_pulang ?? '--:--' }}</td>
                                        <td class="text-center py-3">
                                            @php
                                                $sc = match($row->status_kehadiran) {
                                                    'Hadir' => 'success',
                                                    'Terlambat' => 'warning',
                                                    'Pulang Lebih Awal' => 'info',
                                                    'Terlambat & Pulang Awal' => 'danger',
                                                    'WFH' => 'wfh-badge',
                                                    'Izin' => 'secondary',
                                                    'Sakit' => 'purple-badge',
                                                    'Cuti' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge absensi-badge-{{ $sc }} px-3 py-2 rounded-pill small fw-bold">{{ strtoupper($row->status_kehadiran) }}</span>
                                        </td>
                                        <td class="text-center py-3 text-muted small fw-medium"><i class="bx bx-map-pin me-1"></i>{{ $row->lokasi ?? '-' }}</td>
                                        @if(Auth::user()->id_role == 1)
                                        <td class="pe-4 text-end py-3">
                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deleteAbs{{ $row->id }}">Hapus</button>
                                        </td>
                                        @endif
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="py-5 text-center text-muted">Belum ada log kehadiran.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="card-footer bg-transparent border-0 px-4 py-3">
                                {{ $reportData->appends(request()->query())->links() }}
                            </div>
                        @else
                            <!-- Individual Daily -->
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light border-bottom">
                                    <tr class="text-uppercase small fw-bold text-dark">
                                        <th class="ps-4 py-3">Hari & Tanggal</th>
                                        <th class="text-center py-3">Jam Masuk</th>
                                        <th class="text-center py-3">Jam Pulang</th>
                                        <th class="text-center py-3">Status</th>
                                        <th class="text-center py-3">Lokasi / Keterangan</th>
                                        @if(Auth::user()->id_role == 1)
                                        <th class="pe-4 text-end py-3">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($harianData as $row)
                                    <tr class="{{ in_array($row['status'], ['Alpha']) ? 'table-danger bg-opacity-25' : '' }}">
                                        <td class="ps-4 py-3 fw-bold text-dark">
                                            {{ \Carbon\Carbon::parse($row['tanggal'])->translatedFormat('l, d F Y') }}
                                            @if($row['date_override'] ?? null)
                                                @php
                                                    $overrideBadge = match($row['date_override']) {
                                                        'wajib_masuk' => ['text' => 'Penugasan Khusus', 'class' => 'danger'],
                                                        'wfh' => ['text' => 'WFH Terjadwal', 'class' => 'info'],
                                                        'libur_khusus' => ['text' => 'Libur Khusus', 'class' => 'secondary'],
                                                        default => null,
                                                    };
                                                @endphp
                                                @if($overrideBadge)
                                                <br><span class="badge bg-{{ $overrideBadge['class'] }} small mt-1">📌 {{ $overrideBadge['text'] }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center py-3 fw-bold text-success fs-6">{{ $row['jam_masuk'] ?? '--:--' }}</td>
                                        <td class="text-center py-3 fw-bold text-danger fs-6">{{ $row['jam_pulang'] ?? '--:--' }}</td>
                                        <td class="text-center py-3">
                                            @php
                                                $sc = match($row['status']) {
                                                    'Hadir' => 'success',
                                                    'Terlambat' => 'warning',
                                                    'Pulang Lebih Awal' => 'info',
                                                    'Terlambat & Pulang Awal' => 'danger',
                                                    'WFH' => 'wfh-badge',
                                                    'Alpha' => 'danger',
                                                    'Izin', 'Cuti' => 'secondary',
                                                    'Sakit' => 'purple-badge',
                                                    'Libur Weekend', 'Libur Jadwal', 'Libur' => 'light text-muted border',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge absensi-badge-{{ $sc }} px-3 py-2 rounded-pill small fw-bold">{{ strtoupper($row['status']) }}</span>
                                        </td>
                                        <td class="text-center py-3">
                                            <div class="text-muted small fw-medium"><i class="bx bx-map-pin me-1 text-primary"></i>{{ $row['lokasi'] }}</div>
                                            @if($row['keterangan'])
                                            <small class="text-muted">({{ $row['keterangan'] }})</small>
                                            @endif
                                        </td>
                                        @if(Auth::user()->id_role == 1)
                                        <td class="pe-4 text-end py-3">
                                            @if($row['absensi_id'])
                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deleteAbs{{ $row['absensi_id'] }}">Hapus</button>
                                            @else
                                            <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="py-5 text-center text-muted">Belum ada data harian.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODALS - ADMIN ONLY -->
<!-- ============================================================ -->
@if(Auth::user()->id_role == 1)

<!-- Modal: Input Absensi Manual (1 karyawan) -->
<div class="modal fade" id="modalManual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bx bx-plus-circle me-1 text-primary"></i> INPUT ABSENSI MANUAL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('absensi.store-manual') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Pilih Pegawai</label>
                        <select name="user_id" class="form-select" required style="border-radius: 8px;">
                            @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Tanggal Absen</label>
                        <input type="date" name="tgl" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius: 8px;">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Jam Masuk</label>
                            <input type="time" name="jam_masuk" class="form-control" style="border-radius: 8px;">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Jam Pulang</label>
                            <input type="time" name="jam_pulang" class="form-control" style="border-radius: 8px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Status Kehadiran</label>
                        <select name="status_kehadiran" class="form-select" required style="border-radius: 8px;">
                            <option value="Hadir">✅ Hadir</option>
                            <option value="Terlambat">⏰ Terlambat</option>
                            <option value="Pulang Lebih Awal">🏃 Pulang Lebih Awal</option>
                            <option value="WFH">🏠 WFH (Work From Home)</option>
                            <option value="Sakit">🤒 Sakit</option>
                            <option value="Izin">📝 Izin</option>
                            <option value="Cuti">🌴 Cuti</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..." style="border-radius: 8px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Batalkan</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Pilih Karyawan Tanggal Tertentu (Batch / Multi-select) -->
<div class="modal fade" id="modalBatch" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold text-white"><i class="bx bx-group me-2"></i> PILIH KARYAWAN YANG MASUK DI TANGGAL TERTENTU</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Tab Pilihan Mode -->
                <ul class="nav nav-tabs px-4 pt-3 border-0" id="batchModeTabs">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tabBatchAbsensi">
                            <i class="bx bx-check-circle me-1"></i> Input Absensi
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tabBatchJadwal">
                            <i class="bx bx-calendar-alt me-1"></i> Set Jadwal Wajib
                        </button>
                    </li>
                </ul>

                <div class="tab-content px-4 pb-4 pt-3">
                    <!-- TAB 1: Input Absensi Langsung -->
                    <div class="tab-pane fade show active" id="tabBatchAbsensi">
                        <p class="text-muted small mb-3">
                            <i class="bx bx-info-circle text-primary me-1"></i>
                            Pilih karyawan dan tanggal, lalu tentukan status kehadiran mereka secara massal.
                        </p>
                        <form action="{{ route('absensi.store-manual-batch') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted">Tanggal</label>
                                    <input type="date" name="tgl" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius: 8px;">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted">Status Kehadiran</label>
                                    <select name="status_kehadiran" class="form-select" required style="border-radius: 8px;">
                                        <option value="Hadir">✅ Hadir</option>
                                        <option value="WFH">🏠 WFH</option>
                                        <option value="Terlambat">⏰ Terlambat</option>
                                        <option value="Sakit">🤒 Sakit</option>
                                        <option value="Izin">📝 Izin</option>
                                        <option value="Cuti">🌴 Cuti</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted">Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control" placeholder="Opsional..." style="border-radius: 8px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Jam Masuk (opsional)</label>
                                    <input type="time" name="jam_masuk" class="form-control" style="border-radius: 8px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Jam Pulang (opsional)</label>
                                    <input type="time" name="jam_pulang" class="form-control" style="border-radius: 8px;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-muted">PILIH KARYAWAN</label>
                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="selectAllBatch('batchUserCheck')">Pilih Semua</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="deselectAllBatch('batchUserCheck')">Hapus Pilihan</button>
                                    </div>
                                    <div class="border rounded-3 p-3" style="max-height: 250px; overflow-y: auto; background: #f8fafc;">
                                        <div class="row g-2">
                                            @foreach($allUsers as $u)
                                            <div class="col-md-6">
                                                <div class="form-check p-2 rounded-2" style="background: white; border: 1px solid #e2e8f0;">
                                                    <input class="form-check-input batchUserCheck" type="checkbox" name="user_ids[]" value="{{ $u->id }}" id="batch_user_{{ $u->id }}">
                                                    <label class="form-check-label small fw-semibold w-100" for="batch_user_{{ $u->id }}" style="cursor: pointer;">
                                                        {{ $u->name }}
                                                        <span class="text-muted">[{{ $u->pin_fingerspot ?? '-' }}]</span>
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-light rounded-pill px-4 border fw-semibold" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-warning rounded-pill px-5 fw-bold shadow-sm text-dark">
                                    <i class="bx bx-save me-1"></i> Simpan Absensi
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 2: Set Jadwal Wajib Tanggal Tertentu -->
                    <div class="tab-pane fade" id="tabBatchJadwal">
                        <p class="text-muted small mb-3">
                            <i class="bx bx-info-circle text-warning me-1"></i>
                            Tetapkan kewajiban karyawan di tanggal tertentu. Ini akan mempengaruhi perhitungan Alpha.
                        </p>
                        <form action="{{ route('absensi.date-schedule.store') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold small text-muted">Tanggal</label>
                                    <input type="date" name="tgl" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius: 8px;">
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label fw-bold small text-muted">Jenis Penugasan</label>
                                    <select name="status_wajib" class="form-select" required style="border-radius: 8px;">
                                        <option value="wajib_masuk">🔴 Wajib Masuk (misal: piket, hari libur)</option>
                                        <option value="wfh">🏠 WFH Terjadwal</option>
                                        <option value="libur_khusus">⚪ Libur Khusus (override hari kerja)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-muted">Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Piket Hari Raya / Dinas Luar Kota..." style="border-radius: 8px;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-muted">PILIH KARYAWAN</label>
                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="selectAllBatch('jadwalUserCheck')">Pilih Semua</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="deselectAllBatch('jadwalUserCheck')">Hapus Pilihan</button>
                                    </div>
                                    <div class="border rounded-3 p-3" style="max-height: 250px; overflow-y: auto; background: #f8fafc;">
                                        <div class="row g-2">
                                            @foreach($allUsers as $u)
                                            <div class="col-md-6">
                                                <div class="form-check p-2 rounded-2" style="background: white; border: 1px solid #e2e8f0;">
                                                    <input class="form-check-input jadwalUserCheck" type="checkbox" name="user_ids[]" value="{{ $u->id }}" id="jadwal_user_{{ $u->id }}">
                                                    <label class="form-check-label small fw-semibold w-100" for="jadwal_user_{{ $u->id }}" style="cursor: pointer;">
                                                        {{ $u->name }}
                                                        <span class="text-muted">[{{ $u->pin_fingerspot ?? '-' }}]</span>
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-light rounded-pill px-4 border fw-semibold" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                    <i class="bx bx-calendar-check me-1"></i> Simpan Penugasan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Import CSV -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bx bx-upload me-1 text-success"></i> IMPORT CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('absensi.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small">Format CSV wajib: <code>PIN_FINGERSPOT, TANGGAL, JAM_MASUK, JAM_PULANG</code></p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Pilih Berkas CSV</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required style="border-radius: 8px;">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm text-white">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modals -->
@if($tab === 'harian')
    @if($targetUserId === 'all')
        @foreach($reportData as $row)
        <div class="modal fade" id="deleteAbs{{ $row->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                    <div class="modal-body p-4 text-center">
                        <i class="bx bx-trash text-danger fs-1 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark mb-2">Hapus Log Absensi?</h5>
                        <p class="text-muted small mb-4">{{ $row->user->name ?? '' }} - {{ $row->tgl->format('d M Y') }}</p>
                        <form action="{{ route('absensi.destroy', $row->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <div class="d-flex flex-column gap-2">
                                <button type="submit" class="btn btn-danger rounded-pill fw-bold py-2">Ya, Hapus</button>
                                <button type="button" class="btn btn-light rounded-pill fw-bold py-2 border" data-bs-dismiss="modal">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    @else
        @foreach($harianData as $row)
            @if($row['absensi_id'])
            <div class="modal fade" id="deleteAbs{{ $row['absensi_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                        <div class="modal-body p-4 text-center">
                            <i class="bx bx-trash text-danger fs-1 mb-3 d-block"></i>
                            <h5 class="fw-bold text-dark mb-2">Hapus Log Absensi?</h5>
                            <p class="text-muted small mb-4">{{ \Carbon\Carbon::parse($row['tanggal'])->format('d M Y') }}</p>
                            <form action="{{ route('absensi.destroy', $row['absensi_id']) }}" method="POST">
                                @csrf @method('DELETE')
                                <div class="d-flex flex-column gap-2">
                                    <button type="submit" class="btn btn-danger rounded-pill fw-bold py-2">Ya, Hapus</button>
                                    <button type="button" class="btn btn-light rounded-pill fw-bold py-2 border" data-bs-dismiss="modal">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    @endif
@endif

@endif

<style>
/* Badge custom WFH & Sakit */
.absensi-badge-success { background-color: #22c55e; color: white; }
.absensi-badge-warning { background-color: #f59e0b; color: #1a1a1a; }
.absensi-badge-info    { background-color: #0ea5e9; color: white; }
.absensi-badge-danger  { background-color: #ef4444; color: white; }
.absensi-badge-secondary { background-color: #64748b; color: white; }
.absensi-badge-wfh-badge { background: linear-gradient(135deg, #06b6d4, #0ea5e9); color: white; }
.absensi-badge-purple-badge { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
.absensi-badge-light\ text-muted\ border { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

.btn-outline-indigo {
    color: #4f46e5 !important;
    border-color: #818cf8 !important;
}
.btn-outline-indigo:hover {
    background-color: #4f46e5 !important;
    color: #ffffff !important;
}

/* Filter custom date toggle animation */
#filterBulanan, #filterCustom { transition: all 0.2s ease; }
</style>

@push('scripts')
<script>
// ── Filter Mode Toggle ──
function switchToCustom() {
    document.getElementById('filterBulanan').style.display = 'none';
    document.getElementById('filterCustom').style.display  = 'flex';
    document.getElementById('filter_mode').value = 'custom';
    // Set default dates jika belum diisi
    const sd = document.getElementById('start_date');
    const ed = document.getElementById('end_date');
    if (!sd.value) sd.value = new Date().toISOString().slice(0,8) + '01'; // awal bulan ini
    if (!ed.value) ed.value = new Date().toISOString().slice(0,10);       // hari ini
}

function switchToBulanan() {
    document.getElementById('filterCustom').style.display  = 'none';
    document.getElementById('filterBulanan').style.display = 'flex';
    document.getElementById('filter_mode').value = 'bulanan';
}

function selectAllBatch(cls) {
    document.querySelectorAll('.' + cls).forEach(cb => cb.checked = true);
}
function deselectAllBatch(cls) {
    document.querySelectorAll('.' + cls).forEach(cb => cb.checked = false);
}
</script>
@endpush

@endsection

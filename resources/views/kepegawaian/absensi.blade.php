@extends('layouts.app')

@section('title', 'Laporan Kehadiran')

@section('content')
<style>
    @media print {
        #sidebar, .topbar, .btn, .card-header form, .card-footer, .breadcrumb, #app > nav, button, .no-print {
            display: none !important;
        }
        #content { margin-left: 0 !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: none !important; }
        .print-only { display: block !important; }
        table { width: 100% !important; border-collapse: collapse !important; }
        table th, table td { border: 1px solid #000 !important; padding: 6px !important; font-size: 9pt !important; }
    }
    .print-only { display: none; }
    .table-data td { padding: 0.5rem 0.75rem !important; font-size: 0.8rem !important; }
    .table-data th { padding: 0.75rem 0.75rem !important; font-size: 0.75rem !important; }
    .stat-card { border-radius: 20px; transition: transform 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }
</style>

<div class="row g-4">
    <!-- Premium Header -->
    <div class="col-12 no-print">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 24px;">
            <div class="card-body p-0">
                <div class="bg-pmu p-4 p-md-5 text-white position-relative">
                    <div class="position-relative z-index-1">
                        <h3 class="fw-bold mb-1"><i class="fa-solid fa-chart-line me-2"></i> LAPORAN KEHADIRAN @if(!Auth::user()->isYayasan() && !Auth::user()->isAdminUnit()) SAYA @endif</h3>
                        <p class="opacity-75 mb-0 text-uppercase small ls-1">Rekapitulasi log harian sistem SIAP Digital.</p>
                    </div>
                    <i class="fa-solid fa-clipboard-check position-absolute end-0 top-0 m-4 fa-6x opacity-10"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="col-12 col-md-4 no-print">
        <div class="card border-0 shadow-sm stat-card bg-success text-white">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
                    <i class="fa-solid fa-calendar-check fs-4"></i>
                </div>
                <div>
                    <h6 class="mb-0 opacity-75 small fw-bold">TOTAL HADIR</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['kehadiran'] }} <small class="fs-6 opacity-75">Hari</small></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 no-print">
        <div class="card border-0 shadow-sm stat-card bg-warning text-dark">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-dark bg-opacity-10 rounded-circle p-3 me-3">
                    <i class="fa-solid fa-clock fs-4"></i>
                </div>
                <div>
                    <h6 class="mb-0 opacity-75 small fw-bold">TOTAL TERLAMBAT</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['terlambat'] }} <small class="fs-6 opacity-75">Hari</small></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 no-print">
        <div class="card border-0 shadow-sm stat-card bg-danger text-white">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
                    <i class="fa-solid fa-calendar-xmark fs-4"></i>
                </div>
                <div>
                    <h6 class="mb-0 opacity-75 small fw-bold">ALPHA</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['alpha'] }} <small class="fs-6 opacity-75">Hari</small></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <form action="{{ route('kepegawaian.absensi') }}" method="GET" class="d-flex flex-wrap gap-2 no-print flex-grow-1">
                    <div class="d-flex gap-2 flex-grow-1 flex-sm-grow-0">
                        <select name="month" class="form-select border-2 rounded-pill px-4 py-2 flex-grow-1" style="width: auto; min-width: 140px;">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endforeach
                        </select>
                        <select name="year" class="form-select border-2 rounded-pill px-4 py-2" style="width: auto;">
                            @foreach(range(date('Y')-2, date('Y')+1) as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                    <select name="unit" class="form-select border-2 rounded-pill px-4 py-2 flex-grow-1 flex-sm-grow-0" style="width: auto; min-width: 160px;">
                        <option value="">Semua Unit</option>
                        @foreach($global_units as $un)
                            <option value="{{ $un->nama }}" {{ request('unit') == $un->nama ? 'selected' : '' }}>{{ $un->nama }}</option>
                        @endforeach
                    </select>
                    @endif

                    <button type="submit" class="btn btn-pmu rounded-pill px-4 fw-bold shadow-sm flex-grow-1 flex-sm-grow-0">
                        <i class="fa-solid fa-filter me-2 shadow-sm"></i> TAMPILKAN
                    </button>
                </form>

                @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                <div class="d-flex gap-2 no-print">
                    <button onclick="exportToXLSX()" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-file-excel me-2"></i> UNDUH XLSX
                    </button>
                    <button onclick="window.print()" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-print me-2"></i> CETAK PDF
                    </button>
                </div>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-data" id="absensiTable">
                        <thead class="bg-light">
                            <tr class="text-uppercase fw-bold border-bottom">
                                <th class="ps-4 py-3">PEGAWAI</th>
                                <th class="py-3">UNIT</th>
                                <th class="py-3 text-center">TANGGAL</th>
                                <th class="text-center py-3">MASUK</th>
                                <th class="text-center py-3">PULANG</th>
                                <th class="text-center py-3">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($absensis as $abs)
                            <tr>
                                <td class="ps-4 py-2">
                                    <div class="fw-bold text-dark text-uppercase small">{{ $abs->user->name }}</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">PIN: {{ $abs->pin }}</div>
                                </td>
                                <td class="py-2 small fw-bold text-muted">{{ $abs->user->unit }}</td>
                                <td class="py-2 text-center">
                                    <div class="fw-bold text-dark small">{{ \Carbon\Carbon::parse($abs->tgl)->format('d/m/y') }}</div>
                                    <small class="text-muted text-uppercase" style="font-size: 0.55rem;">{{ \Carbon\Carbon::parse($abs->tgl)->translatedFormat('D') }}</small>
                                </td>
                                <td class="text-center py-2 fw-bold text-success">{{ $abs->jam_masuk ?? '--:--' }}</td>
                                <td class="text-center py-2 fw-bold text-danger">{{ $abs->jam_pulang ?? '--:--' }}</td>
                                <td class="text-center py-2">
                                    @if($abs->status_kehadiran == 'Hadir')
                                        <span class="text-success small fw-bold">HADIR</span>
                                    @elseif($abs->status_kehadiran == 'Terlambat')
                                        <span class="text-warning small fw-bold">TELAT</span>
                                    @else
                                        <span class="text-danger small fw-bold">ALPHA</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-5 text-center text-muted fw-bold">Belum ada rekaman absensi untuk periode ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($absensis->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4 no-print text-start">
                {{ $absensis->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    function exportToXLSX() {
        const table = document.getElementById('absensiTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Laporan Absensi");
        
        const date = new Date();
        const unitName = "{{ request('unit') ?: 'Pribadi' }}".replace(/\s+/g, '_');
        const fileName = `Laporan_Absensi_PMU_${unitName}_${date.getFullYear()}-${date.getMonth() + 1}.xlsx`;
        XLSX.writeFile(wb, fileName);
    }
</script>
@endsection

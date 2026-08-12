@extends('layouts.app')

@section('title', 'Detail Acara')

@section('content')
<style>
    @media print {
        #sidebar, .topbar, .btn, .card-header form, .card-footer, .breadcrumb, #app > nav, button, .no-print, .card-header select {
            display: none !important;
        }
        #content { margin-left: 0 !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: none !important; }
        .print-only { display: block !important; }
        table { width: 100% !important; border-collapse: collapse !important; }
        table th, table td { border: 1px solid #000 !important; padding: 6px !important; font-size: 9pt !important; }
        .card-body { padding: 0 !important; }
        .row > div { width: 100% !important; }
    }
    .print-only { display: none; }
</style>

{{-- Header for Print --}}
<div class="print-only text-center mb-4">
    <h3 class="fw-bold mb-1">DAFTAR KEHADIRAN ACARA</h3>
    <h4 class="mb-3 text-uppercase">{{ $acara->nama }}</h4>
    <p class="mb-0">Tanggal: {{ \Carbon\Carbon::parse($acara->tanggal)->translatedFormat('d F Y') }}</p>
    <p>Waktu: {{ substr($acara->jam_mulai, 0, 5) }} - {{ substr($acara->jam_selesai, 0, 5) }}</p>
    <hr style="border: 2px solid #000;">
</div>

<div class="row g-4">
    <div class="col-md-4">
        @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
        <!-- QR Code Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 no-print border-bottom border-4 border-pmu">
            <div class="card-body p-4 text-center">
                <h5 class="fw-bold text-pmu mb-4">QR CODE ABSENSI</h5>
                <div class="d-flex justify-content-center mb-4 bg-white p-3 rounded-4 border shadow-sm">
                    <div id="qrcode"></div>
                </div>
                <div class="bg-light p-3 rounded-3 mb-4">
                    <div class="small fw-bold text-muted mb-1 text-uppercase">Scan Code</div>
                    <div class="h5 fw-bold mb-0 text-dark">{{ $acara->qr_code }}</div>
                </div>
                <div class="d-grid gap-2">
                    <button onclick="printQRCode()" class="btn btn-outline-pmu rounded-pill fw-bold">
                        <i class="fa-solid fa-print me-2"></i> CETAK QR CODE
                    </button>
                    <a href="{{ route('kepegawaian.acara.scan-page', $acara->qr_code) }}" target="_blank" class="btn btn-pmu rounded-pill fw-bold shadow-sm">
                        <i class="fa-solid fa-expand me-2"></i> MODE SCANNER
                    </a>
                </div>
            </div>
        </div>
        @else
        <!-- Scanner Button for Pegawai -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-pmu text-white no-print">
            <div class="card-body p-4 text-center">
                <i class="fa-solid fa-qrcode fs-1 mb-3"></i>
                <h5 class="fw-bold mb-3">ABSEN VIA QR CODE</h5>
                <p class="small opacity-75 mb-4">Klik tombol di bawah untuk membuka kamera dan scan QR Code yang disediakan panitia.</p>
                <div class="d-grid">
                    <a href="{{ route('kepegawaian.acara.scanner-camera') }}" class="btn btn-white text-pmu rounded-pill fw-bold shadow-sm">
                        <i class="fa-solid fa-camera me-2"></i> BUKA KAMERA SCANNER
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Event Details -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden no-print border-bottom border-4 border-pmu">
            <div class="card-body p-4">
                <h6 class="fw-bold text-muted mb-3 text-uppercase">Informasi Acara</h6>
                <div class="mb-3">
                    <div class="small text-muted">Nama Acara</div>
                    <div class="fw-bold text-dark">{{ $acara->nama }}</div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Tanggal</div>
                    <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($acara->tanggal)->translatedFormat('d F Y') }}</div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Waktu</div>
                    <div class="fw-bold text-dark">{{ substr($acara->jam_mulai, 0, 5) }} - {{ substr($acara->jam_selesai, 0, 5) }}</div>
                </div>
                <div>
                    <div class="small text-muted">Lokasi</div>
                    <div class="fw-bold text-dark">{{ $acara->lokasi ?? 'Tidak ditentukan' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-bottom border-4 border-pmu">
            <div class="card-header bg-white p-4 border-0">
                {{-- Header: Judul + Stats + Aksi --}}
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold text-pmu mb-1">DAFTAR KEHADIRAN</h5>
                        <div class="d-flex gap-3 align-items-center flex-wrap">
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold">
                                <i class="fa-solid fa-check-circle me-1"></i>
                                Hadir: {{ $totalHadir }}
                            </span>
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill fw-bold">
                                <i class="fa-solid fa-times-circle me-1"></i>
                                Tidak Hadir: {{ $allPegawai->count() - $totalHadir }}
                            </span>
                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill fw-bold">
                                Total: {{ $allPegawai->count() }}
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                        <div class="dropdown">
                            <button class="btn btn-pmu btn-sm rounded-pill px-3 fw-bold dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-download me-1"></i> UNDUH REKAP
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2" style="min-width: 200px;">
                                <li><div class="dropdown-header small text-uppercase fw-bold text-muted">Pilih Unit</div></li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 fw-bold" href="{{ route('kepegawaian.acara.rekap-unit', $acara->id) }}">
                                        <i class="fa-solid fa-users me-2 text-pmu"></i> Semua Unit
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider opacity-25"></li>
                                @foreach($units as $unit)
                                <li>
                                    <a class="dropdown-item rounded-3 py-2" href="{{ route('kepegawaian.acara.rekap-unit', $acara->id) }}?unit={{ urlencode($unit->nama) }}">
                                        <i class="fa-solid fa-building me-2 text-muted"></i> {{ $unit->nama }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <button onclick="window.print()" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
                            <i class="fa-solid fa-file-pdf me-1"></i> CETAK PDF
                        </button>
                        <button onclick="window.location.reload()" class="btn btn-sm btn-light text-pmu rounded-pill px-3">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> REFRESH
                        </button>
                    </div>
                </div>

                {{-- Filter Per Unit Dropdown --}}
                <div class="mt-3 no-print">
                    <form action="{{ route('kepegawaian.acara.show', $acara->id) }}" method="GET" id="filterForm" class="d-flex gap-2 align-items-center">
                        <select name="unit" class="form-select form-select-sm rounded-pill px-3 border-2 fw-bold" style="width: auto; min-width: 200px;" onchange="this.form.submit()">
                            <option value="">Semua Unit</option>
                            @foreach($units as $unit)
                            <option value="{{ $unit->nama }}" {{ $unitFilter === $unit->nama ? 'selected' : '' }}>{{ $unit->nama }}</option>
                            @endforeach
                        </select>
                        @if($unitFilter)
                        <a href="{{ route('kepegawaian.acara.show', $acara->id) }}" class="btn btn-sm btn-light rounded-pill px-3 text-muted">
                            <i class="fa-solid fa-times me-1"></i> Reset
                        </a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted fw-bold small">
                            <th class="ps-4 py-3">Nama Pegawai</th>
                            <th class="py-3">Unit</th>
                            <th class="py-3 text-center">Status Kehadiran</th>
                            <th class="py-3 text-center">Waktu Scan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allPegawai as $pegawai)
                        @php $absen = $hadirsMap->get($pegawai->id); @endphp
                        <tr class="border-bottom text-dark">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle overflow-hidden flex-shrink-0" style="width:34px;height:34px;">
                                        <img src="{{ $pegawai->getPhoto() }}" class="w-100 h-100 object-fit-cover" alt="">
                                    </div>
                                    <div>
                                        <div class="fw-bold small text-uppercase">{{ $pegawai->name }}</div>
                                        <div class="text-muted" style="font-size:0.65rem;">PIN: {{ $pegawai->pin_fingerspot ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border rounded-pill px-2" style="font-size:0.7rem;">{{ $pegawai->unit ?? '-' }}</span>
                            </td>
                            <td class="py-3 text-center">
                                @if($absen)
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold" style="font-size:0.75rem;">
                                        <i class="fa-solid fa-check-circle me-1"></i> HADIR
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2 fw-bold" style="font-size:0.75rem;">
                                        <i class="fa-solid fa-times-circle me-1"></i> TIDAK HADIR
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-center">
                                @if($absen)
                                    <span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-bold">
                                        {{ \Carbon\Carbon::parse($absen->waktu_scan)->format('H:i:s') }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-users-slash fs-2 mb-3 d-block opacity-25"></i>
                                <p class="mb-0 fw-bold">Tidak ada pegawai ditemukan.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Container (Hidden) -->
<div id="printArea" style="display: none;">
    <div style="text-align: center; font-family: sans-serif; padding: 40px;">
        <h1 style="color: #0d4a2b; margin-bottom: 5px;">ABSENSI QR CODE</h1>
        <h2 style="margin-top: 0; color: #333;">{{ $acara->nama }}</h2>
        <div id="qrcode-print" style="margin: 40px auto; display: inline-block;"></div>
        <p style="font-size: 1.2rem; font-weight: bold; color: #555;">TANGGAL: {{ \Carbon\Carbon::parse($acara->tanggal)->translatedFormat('d F Y') }}</p>
        <p style="font-size: 1rem; color: #777;">SILAKAN SCAN MENGGUNAKAN SMARTPHONE ANDA</p>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    var qrcode = new QRCode(document.getElementById("qrcode"), {
        text: "{{ route('kepegawaian.acara.scan-page', $acara->qr_code) }}",
        width: 200,
        height: 200,
        colorDark : "#0d4a2b",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    var qrcodePrint = new QRCode(document.getElementById("qrcode-print"), {
        text: "{{ route('kepegawaian.acara.scan-page', $acara->qr_code) }}",
        width: 400,
        height: 400,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    function printQRCode() {
        var printContents = document.getElementById('printArea').innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload();
    }
</script>
@endpush

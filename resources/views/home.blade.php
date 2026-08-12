@extends('layouts.app')

@section('title', 'Ringkasan Dashboard')

@section('content')
<div class="row g-4">
    <!-- Welcome Banner -->
    <div class="col-12">
        <div class="card overflow-hidden border-0 bg-pmu text-white h-100 shadow-lg" style="border-radius: 24px;">
            <div class="card-body p-4 p-md-5 position-relative">
                <div class="position-relative z-index-1">
                    <h2 class="fw-bold mb-2">Selamat Datang, {{ Auth::user()->name }}!</h2>
                    <p class="mb-4 opacity-75 lead small-on-mobile">Sistem Informasi Administrasi Pegawai (SIAP) Digital PMU Bungah.</p>
                    <div class="d-flex flex-wrap gap-2 gap-md-3">
                        <a href="{{ route('kepegawaian.biodata') }}" class="btn btn-light rounded-pill px-4 text-pmu fw-bold shadow-sm flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-user-edit me-2"></i> Profil
                        </a>
                        @if(Auth::user()->isPegawai())
                        <a href="{{ route('kepegawaian.absensi.today') }}" class="btn btn-outline-light rounded-pill px-4 fw-bold flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-fingerprint me-2"></i> Finger
                        </a>
                        @endif
                    </div>
                </div>
                <div class="position-absolute end-0 top-0 h-100 me-5 d-none d-lg-flex align-items-center opacity-25">
                    <i class="fa-solid fa-graduation-cap fa-10x"></i>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->role == 'yayasan' || Auth::user()->isAdminUnit())
    <!-- Admin Quick Stats -->
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100 border-bottom border-4 border-pmu hover-translate-y transition-all">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-pmu-soft text-pmu rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-users fa-lg"></i>
                    </div>
                    <span class="badge bg-pmu-soft text-pmu">Total</span>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['total_pegawai'] ?? 0 }}</h3>
                <p class="text-muted small mb-0">Total Pegawai Aktif</p>
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100 border-bottom border-4 border-success hover-translate-y transition-all">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-calendar-check fa-lg"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success">Menunggu</span>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['cuti_pending'] ?? 0 }}</h3>
                <p class="text-muted small mb-0">Pengajuan Cuti</p>
                <i class="fa-solid fa-calendar-alt"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100 border-bottom border-4 border-warning hover-translate-y transition-all">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-file-signature fa-lg"></i>
                    </div>
                    <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['sk_pending'] ?? 0 }}</h3>
                <p class="text-muted small mb-0">Permohonan SK</p>
                <i class="fa-solid fa-file-contract"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100 border-bottom border-4 border-danger hover-translate-y transition-all">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-folder-open fa-lg"></i>
                    </div>
                    <span class="badge bg-danger-subtle text-danger">Menunggu</span>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['dokumen_pending'] ?? 0 }}</h3>
                <p class="text-muted small mb-0">Validasi Dokumen</p>
                <i class="fa-solid fa-file-pdf"></i>
            </div>
        </div>
    </div>
    @else
    <!-- Pegawai Quick Stats -->
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100 border-bottom border-4 border-pmu hover-translate-y transition-all">
            <div class="card-body p-4 text-center">
                <div class="bg-pmu-soft text-pmu rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-fingerprint fa-lg"></i>
                </div>
                <h4 class="fw-bold mb-1">{{ $stats['my_absensi'] ?? 0 }}</h4>
                <p class="text-muted small mb-0">Total Kehadiran</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100 border-bottom border-4 border-success hover-translate-y transition-all">
            <div class="card-body p-4 text-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-calendar-day fa-lg"></i>
                </div>
                <h4 class="fw-bold mb-1">{{ $stats['my_cuti'] ?? 0 }}</h4>
                <p class="text-muted small mb-0">Pengajuan Cuti</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100 border-bottom border-4 border-info hover-translate-y transition-all">
            <div class="card-body p-4 text-center">
                <div class="bg-info bg-opacity-10 text-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-file-contract fa-lg"></i>
                </div>
                <h4 class="fw-bold mb-1">{{ $stats['my_sk'] ?? 0 }}</h4>
                <p class="text-muted small mb-0">Pengajuan SK</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm h-100 border-bottom border-4 border-warning hover-translate-y transition-all">
            <div class="card-body p-4 text-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-folder-closed fa-lg"></i>
                </div>
                <h4 class="fw-bold mb-1">{{ $stats['my_dokumen'] ?? 0 }}</h4>
                <p class="text-muted small mb-0">Dokumen Terunggah</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Events Today -->
    <div class="col-12">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold text-dark"><i class="fa-solid fa-calendar-day text-pmu me-2"></i> Acara Hari Ini</h6>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Absensi Kegiatan & Event</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-pmu btn-sm rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                        <i class="fa-solid fa-qrcode me-1"></i> SCAN QR CODE
                    </button>
                    <a href="{{ route('kepegawaian.acara.index') }}" class="btn btn-outline-pmu btn-sm rounded-pill px-3 fw-bold">
                        RIWAYAT
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">
                                <th class="ps-4 py-3">Nama Acara</th>
                                <th class="py-3 text-center">Waktu</th>
                                <th class="py-3 text-center">Lokasi</th>
                                @if(!Auth::user()->isYayasan())
                                <th class="pe-4 py-3 text-end">Status Saya</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayEvents ?? [] as $event)
                            @php
                                $isHadir = Auth::user()->absensis()->whereHas('acara', fn($q) => $q->where('id', $event->id))->exists();
                                // Wait, the relation is through AbsensiAcara
                                $attendance = \App\Models\AbsensiAcara::where('acara_id', $event->id)->where('user_id', Auth::user()->id)->first();
                            @endphp
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $event->nama }}</div>
                                </td>
                                <td class="text-center py-3">
                                    <span class="badge bg-light text-dark rounded-pill">{{ $event->jam_mulai }} - {{ $event->jam_selesai }}</span>
                                </td>
                                <td class="text-center py-3">
                                    <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i> {{ $event->lokasi ?? '-' }}</small>
                                </td>
                                @if(!Auth::user()->isYayasan())
                                <td class="pe-4 text-end py-3">
                                    @if($attendance)
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3">
                                            <i class="fa-solid fa-check-circle me-1"></i> HADIR ({{ \Carbon\Carbon::parse($attendance->waktu_scan)->format('H:i') }})
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3">
                                            <i class="fa-solid fa-times-circle me-1"></i> BELUM HADIR
                                        </span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ Auth::user()->isYayasan() ? 3 : 4 }}" class="text-center py-5">
                                    <div class="text-muted opacity-50">
                                        <i class="fa-solid fa-calendar-xmark fa-2x mb-2"></i>
                                        <p class="small mb-0">Tidak ada agenda acara hari ini</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Completion -->
    <div class="col-md-5 col-lg-4">
        <div class="card h-100 border-0 shadow-sm text-center" style="border-radius: 20px;">

            <div class="card-body p-4 d-flex flex-column justify-content-center">
                <h6 class="text-muted fw-bold text-uppercase small mb-4" style="letter-spacing: 1px;">Kelengkapan Profil Saya</h6>
                
                <div class="position-relative mx-auto mb-4" style="width: 150px; height: 150px;">
                   <svg viewBox="0 0 36 36" class="circular-chart pmu-green">
                      <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#f1f5f9" stroke-width="3" />
                      <path class="circle" stroke-dasharray="{{ $progress }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#1a4d2e" stroke-width="3" stroke-linecap="round" style="transition: stroke-dasharray 1s ease 0s;" />
                      <text x="18" y="20.35" class="percentage" style="font-family: 'Inter'; font-weight: 800; font-size: 8px; text-anchor: middle; fill: #1a4d2e;">{{ $progress }}%</text>
                   </svg>
                </div>

                <p class="text-muted small mb-0 px-2 mt-2">Lengkapi data diri Anda untuk mendukung validasi data kepegawaian yang akurat.</p>
            </div>
        </div>
    </div>

    <!-- Attendance Today -->
    <div class="col-md-7 col-lg-8">
        <div class="card h-100 border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold text-dark">Aktivitas Fingerprint Hari Ini</h6>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Log Real-time</small>
                </div>
                <span class="badge bg-light text-muted">{{ date('d F Y') }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="ps-4">Nama Pegawai</th>
                                <th class="text-center">Masuk</th>
                                <th class="text-center">Pulang</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($absensiToday ?? [] as $absen)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle me-3 shadow-sm border border-white" style="width: 32px; height: 32px; overflow: hidden;">
                                            <img src="{{ $absen->user->getPhoto() }}" class="w-100 h-100 object-fit-cover" alt="Avatar">
                                        </div>
                                        <div class="fw-bold small">{{ $absen->user->name }}</div>
                                    </div>
                                </td>
                                <td class="text-center py-3">
                                    <span class="fw-bold text-success small">{{ $absen->jam_masuk ?? '--:--' }}</span>
                                </td>
                                <td class="text-center py-3">
                                    <span class="fw-bold text-danger small">{{ $absen->jam_pulang ?? '--:--' }}</span>
                                </td>
                                <td class="text-center py-3">
                                    @php
                                        $statusRaw = $absen->status_kehadiran;
                                        $status = empty($statusRaw) ? 'Hadir' : $statusRaw;
                                        $isHadir = trim(strtolower($status)) == 'hadir';
                                    @endphp
                                    <span class="badge {{ $isHadir ? 'bg-pmu text-white' : 'bg-warning text-dark' }} rounded-pill shadow-sm" style="font-size: 0.7rem; padding: 5px 15px !important;">
                                        {{ strtoupper($status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted opacity-50">
                                        <i class="fa-solid fa-clock-rotate-left fa-3x mb-3"></i>
                                        <p class="small mb-0">Belum ada aktivitas hari ini</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @if(!Auth::user()->isYayasan() && !Auth::user()->isAdminUnit())
    <div class="col-12 no-print">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-pmu p-2 rounded-3 text-white">
                        <i class="fa-solid fa-calendar-check fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark text-uppercase ls-1">Jadwal Kerja & Kewajiban Menit</h6>
                        <small class="text-muted small">Ketentuan beban kerja mingguan Anda</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="small fw-bold text-dark text-uppercase" style="font-size: 0.7rem;">
                                <th class="ps-4 py-3">Hari</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="py-3 text-center">Kewajiban</th>
                                <th class="pe-4 py-3 text-end">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($days as $index => $day)
                            @php $sched = $userSchedules->get($index); @endphp
                            <tr>
                                <td class="ps-4 py-3 fw-bold text-dark small">{{ $day }}</td>
                                <td class="text-center">
                                    @if($sched && $sched->minutes > 0)
                                    <span class="badge bg-pmu text-white py-1 rounded-pill px-3" style="font-size: 0.65rem;">MASUK</span>
                                    @else
                                    <span class="badge bg-light text-muted rounded-pill border px-3" style="font-size: 0.65rem;">LIBUR</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-dark small">{{ $sched ? $sched->minutes : 0 }} Menit</td>
                                <td class="pe-4 text-end small text-muted">
                                    {{ ($sched && $sched->minutes > 0) ? 'Wajib hadir di unit kerja' : 'Bebas tugas / Libur' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal QR Scanner -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header bg-pmu text-white p-4 border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-qrcode me-2"></i> SCAN QR CODE ABSENSI
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeScanner"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Area Kamera -->
                <div id="reader" style="width: 100%; border-radius: 0 0 24px 24px; overflow: hidden;"></div>
            </div>
            <div class="modal-footer p-4 border-0 justify-content-center">
                <p class="small text-muted mb-0">
                    <i class="fa-solid fa-info-circle me-1"></i> Arahkan kamera ke QR Code Acara yang tersedia.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrCode;
    
    // Saat modal ditampilkan, jalankan kamera
    document.getElementById('qrScannerModal').addEventListener('shown.bs.modal', function () {
        html5QrCode = new Html5Qrcode("reader");
        const config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0 
        };
        
        html5QrCode.start(
            { facingMode: "environment" }, 
            config, 
            (decodedText) => {
                // Jika QR Code Terdeteksi
                html5QrCode.stop().then(() => {
                    // Beri notifikasi loading singkat
                    document.getElementById('reader').innerHTML = '<div class="p-5 text-center"><i class="fa-solid fa-sync fa-spin fa-2x text-pmu"></i><p class="mt-2 fw-bold">Memproses absensi...</p></div>';
                    
                    // Arahkan ke URL scan (bisa berupa URL lengkap atau kode)
                    if (decodedText.includes('/kepegawaian/acara/scan/')) {
                        window.location.href = decodedText;
                    } else if (decodedText.startsWith('ACARA-')) {
                        window.location.href = "{{ url('/kepegawaian/acara/scan') }}/" + decodedText;
                    } else {
                        alert("QR Code tidak valid untuk sistem ini.");
                        location.reload();
                    }
                });
            }
        ).catch((err) => {
            console.error(err);
            alert("Gagal mengakses kamera. Pastikan izin kamera telah diberikan.");
        });
    });

    // Saat modal ditutup, matikan kamera
    document.getElementById('closeScanner').addEventListener('click', function() {
        if (html5QrCode) {
            html5QrCode.stop().catch(err => console.error("Gagal menghentikan kamera:", err));
        }
    });

    // Otomatis munculkan modal Ganti Password jika wajib
    @if(Auth::user()->must_change_password)
    document.addEventListener('DOMContentLoaded', function() {
        var modalChangePwd = new bootstrap.Modal(document.getElementById('changePasswordModal'), {
            backdrop: 'static',
            keyboard: false
        });
        modalChangePwd.show();
    });
    @endif
</script>
@endpush
@endsection

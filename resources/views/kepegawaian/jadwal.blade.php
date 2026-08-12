@extends('layouts.app')

@section('title', 'Jadwal Kerja & Kewajiban Menit')

@section('content')
<div class="row g-4">
    <!-- Premium Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 24px;">
            <div class="bg-pmu p-4 p-md-5 text-white position-relative">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-white text-pmu rounded-pill px-3 py-1 fw-bold small me-3">PORTAL PEGAWAI</span>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small opacity-75 fw-bold">
                            <li class="breadcrumb-item"><a href="#" class="text-white text-decoration-none">Kepegawaian</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Jadwal & Kewajiban Kerja</li>
                        </ol>
                    </nav>
                </div>
                <h2 class="fw-bold mb-0">JADWAL & KEWAJIBAN KERJA</h2>
                <p class="mb-0 opacity-75">Tentukan kewajiban menit harian untuk perhitungan otomatis absensi dan performa.</p>
            </div>
        </div>
    </div>

    <div class="col-12 pb-5">

    <!-- Selection for Admin/Yayasan -->
    @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4">
            <form action="{{ route('kepegawaian.jadwal.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 py-2 small text-muted text-uppercase fw-bold ps-3"><i class="fa-solid fa-search me-2"></i></span>
                        <select name="user_id" class="form-select select2-user border-0 bg-light shadow-none fw-bold" onchange="this.form.submit()">
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" {{ $targetUser->id == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->unit }}) - {{ strtoupper($u->role) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-pmu d-flex align-items-center fw-bold rounded-pill px-4 py-2 shadow-sm">
                        <i class="fa-solid fa-sync me-2 small"></i> REFRESH DATA
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="row g-4">
        <!-- Profile Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 border-top border-4 border-pmu h-100">
                <div class="card-body p-4 text-center">
                    <div class="avatar-wrapper mx-auto mb-4" style="width: 100px; height: 100px;">
                        <img src="{{ $targetUser->getPhoto() }}" class="rounded-circle w-100 h-100 object-fit-cover shadow-sm border border-4 border-white">
                    </div>
                    <h5 class="fw-bold mb-1 text-dark">{{ $targetUser->name }}</h5>
                    <p class="text-muted small mb-4">{{ $targetUser->unit }} • {{ $targetUser->role }}</p>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3 bg-light border">
                                <h4 class="fw-bold text-dark mb-0 fs-5">{{ $userSchedules->sum('minutes') }}</h4>
                                <div class="text-muted fw-bold" style="font-size: 0.6rem;">MENIT/MINGGU</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white rounded-3 border">
                                <h4 class="fw-bold text-dark mb-0 fs-5">{{ round($userSchedules->sum('minutes') / 60, 1) }}</h4>
                                <div class="text-muted fw-bold" style="font-size: 0.6rem;">JAM/MINGGU</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Table & Edit -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 border-top">
                <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0 text-uppercase ls-1">
                        <i class="fa-solid fa-calendar-check me-2 text-pmu"></i> PENGATURAN JADWAL
                    </h6>
                </div>
                <div class="card-body p-0">
                    <form action="{{ route('kepegawaian.jadwal.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr class="small fw-bold text-dark text-uppercase bg-light" style="font-size: 0.7rem;">
                                        <th class="ps-4 py-3" style="width: 150px;">HARI</th>
                                        <th class="py-3 text-center">JAM MASUK</th>
                                        <th class="py-3 text-center">JAM PULANG</th>
                                        <th class="py-3 text-center">KEWAJIBAN (MENIT)</th>
                                        <th class="pe-4 py-3 text-end">STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($days as $index => $day)
                                    @php $sched = $userSchedules->get($index); @endphp
                                    <tr class="border-bottom">
                                        <td class="ps-4 py-3 fw-bold text-dark small">{{ strtoupper($day) }}</td>
                                        <td class="py-3 text-center">
                                            <input type="time" name="jam_masuk[{{ $index }}]" 
                                                   class="form-control form-control-sm text-center fw-bold rounded-pill mx-auto border-pmu" 
                                                   style="width: 120px;"
                                                   value="{{ $sched && $sched->jam_masuk ? \Carbon\Carbon::parse($sched->jam_masuk)->format('H:i') : ($index == 0 ? '' : '07:30') }}">
                                        </td>
                                        <td class="py-3 text-center">
                                            <input type="time" name="jam_pulang[{{ $index }}]" 
                                                   class="form-control form-control-sm text-center fw-bold rounded-pill mx-auto border-pmu" 
                                                   style="width: 120px;"
                                                   value="{{ $sched && $sched->jam_pulang ? \Carbon\Carbon::parse($sched->jam_pulang)->format('H:i') : ($index == 0 ? '' : '12:30') }}">
                                        </td>
                                        <td class="py-3 text-center">
                                            <div class="input-group input-group-sm rounded-pill overflow-hidden mx-auto" style="width: 140px; border: 1px solid #ddd;">
                                                <input type="number" name="schedule[{{ $index }}]" 
                                                       class="form-control text-center fw-bold border-0 shadow-none bg-white" 
                                                       value="{{ $sched ? $sched->minutes : ($index == 0 ? 0 : 300) }}" 
                                                       min="0" step="1">
                                                <span class="input-group-text bg-white border-0 small fw-bold text-muted">MIN</span>
                                            </div>
                                        </td>
                                        <td class="pe-4 py-3 text-end">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" 
                                                       {{ (($sched && $sched->minutes > 0) || (!$sched && $index != 0)) ? 'checked' : '' }}
                                                       onchange="toggleDay(this, {{ $index }})">
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 bg-light border-top text-end">
                            <button type="submit" class="btn btn-pmu d-inline-flex align-items-center gap-2 fw-bold rounded-pill px-4 py-2 shadow-sm">
                                <i class="fa-solid fa-save"></i> SIMPAN PERUBAHAN
                            </button>
                        </div>
                    </form>
                </div>
            </div> <!-- End col-lg-8 -->
        </div> <!-- End Inner row -->

        <!-- Reference Guide -->
        <div class="row g-4 mt-2">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light">
                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="fa-solid fa-circle-question text-pmu me-2"></i> Cara Mengisi?
                    </h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2">Isi angka <b>0</b> untuk hari libur.</li>
                        <li class="mb-2">Gunakan kelipatan 60 untuk memudahkan hitungan jam (mis: 300 = 5 jam).</li>
                        <li>Pastikan total menit dalam seminggu sudah sesuai dengan kebijakan unit.</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light">
                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="fa-solid fa-clock text-warning me-2"></i> Estimasi Standar
                    </h6>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>4 Jam Kerja</span>
                        <span class="fw-bold">240 Menit</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>5 Jam Kerja</span>
                        <span class="fw-bold">300 Menit</span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>6 Jam Kerja</span>
                        <span class="fw-bold">360 Menit</span>
                    </div>
                </div>
            </div>
        </div> <!-- End Reference row -->
    </div> <!-- End Main Content col-12 -->
</div> <!-- End Main Row -->

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 48px;
        border-radius: 0 12px 12px 0;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 46px;
    }
    .select2-dropdown {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        padding: 5px;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-user').select2({
            placeholder: 'Masukkan nama pegawai...',
            width: 'resolve'
        });

        $('.select2-user').on('change', function() {
            $(this).closest('form').submit();
        });
    });

    function updateHourDisplay(input, index) {
        const minutes = parseInt(input.value) || 0;
        const hours = (minutes / 60).toFixed(1);
        document.getElementById(`hour-display-${index}`).innerText = `${hours} Jam`;
        
        // Auto check/uncheck status switch based on minutes
        const switchInput = input.closest('tr').querySelector('.form-check-input');
        if (switchInput) {
            if (minutes > 0) {
                switchInput.checked = true;
            } else {
                switchInput.checked = false;
            }
        }
    }

    function toggleDay(checkbox, index) {
        const input = document.getElementsByName(`schedule[${index}]`)[0];
        if (!checkbox.checked) {
            input.dataset.oldValue = input.value;
            input.value = 0;
        } else {
            input.value = input.dataset.oldValue || 300;
        }
    }
</script>
@endpush

<style>
    .transition-hover:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2) !important;
    }
    .form-switch .form-check-input { width: 3em; height: 1.5em; cursor: pointer; }
    .form-switch .form-check-input:checked { background-color: #22c55e; border-color: #22c55e; }
</style>
@endsection

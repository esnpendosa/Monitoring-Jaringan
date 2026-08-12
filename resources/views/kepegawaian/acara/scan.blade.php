@extends('layouts.app')

@section('title', 'Konfirmasi Kehadiran')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mt-5">
            <div class="bg-pmu p-5 text-white text-center">
                <div class="bg-white bg-opacity-20 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-calendar-check fs-1"></i>
                </div>
                <h3 class="fw-bold mb-1">KONFIRMASI KEHADIRAN</h3>
                <p class="mb-0 opacity-75">Silakan konfirmasi kehadiran Anda untuk acara di bawah ini.</p>
            </div>
            <div class="card-body p-4 p-lg-5 text-center">
                <div class="mb-4">
                    <h4 class="fw-bold text-pmu mb-1">{{ $acara->nama }}</h4>
                    <div class="text-muted small">
                        <i class="fa-solid fa-clock me-1"></i> 
                        {{ \Carbon\Carbon::parse($acara->tanggal)->translatedFormat('l, d F Y') }} | 
                        {{ substr($acara->jam_mulai, 0, 5) }} - {{ substr($acara->jam_selesai, 0, 5) }}
                    </div>
                </div>

                <div class="bg-light p-4 rounded-4 mb-4 text-start border border-dashed border-2">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 40px; height: 40px;">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark text-uppercase small">{{ Auth::user()->name }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">ID: {{ Auth::user()->pin_fingerspot ?? Auth::user()->id }}</div>
                        </div>
                    </div>
                    <div class="small text-muted mb-0">Unit Kerja: <b>{{ Auth::user()->unit }}</b></div>
                </div>

                <div class="d-grid gap-3">
                    <button id="btnAbsen" class="btn btn-pmu btn-lg rounded-pill fw-bold shadow-sm py-3 transition-all">
                        <i class="fa-solid fa-check-circle me-2"></i> ABSEN SEKARANG
                    </button>
                    <a href="{{ route('home') }}" class="btn btn-link text-muted text-decoration-none small fw-bold">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="modalSuccess" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-5 text-center">
                <div class="text-success mb-4">
                    <i class="fa-solid fa-circle-check" style="font-size: 5rem;"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">BERHASIL!</h3>
                <p class="text-muted mb-4" id="successMessage">Kehadiran Anda telah berhasil dicatat dan disinkronkan ke sistem.</p>
                <div class="d-grid">
                    <a href="{{ route('home') }}" class="btn btn-pmu rounded-pill fw-bold py-2">KE DASHBOARD</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('btnAbsen').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> MEMPROSES...';

        fetch("{{ route('kepegawaian.acara.do-scan') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                qr_code: "{{ $acara->qr_code }}",
                user_id: "{{ Auth::id() }}"
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('successMessage').innerText = data.message;
                new bootstrap.Modal(document.getElementById('modalSuccess')).show();
            } else {
                alert(data.message || 'Terjadi kesalahan.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> ABSEN SEKARANG';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal menghubungi server.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> ABSEN SEKARANG';
        });
    });
</script>
@endpush

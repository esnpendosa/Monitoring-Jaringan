@extends('layouts.app')

@push('styles')
<style>
    #content { margin-left: 0 !important; padding: 0 !important; }
    #sidebar { display: none !important; }
    .topbar { display: none !important; }
    .auth-card {
        border-radius: 30px;
        overflow: hidden;
        border: none;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    .auth-bg {
        background: linear-gradient(rgba(26, 77, 46, 0.82), rgba(18, 54, 32, 0.88)), url('https://blogger.googleusercontent.com/img/a/AVvXsEgyu4i5Lx5GitgyPsnJ3dzMk04qhjRdwI-g6Mor7eN30rgkLyWr5B9KhAK2GcAYqppF2eYqU155wlwX4TwJodb7Hon-7jFviSLCXOM5Yy6LpekJlXdx3WVQMwDuyk0eclEtIEB2Oi2k6PmECFGKO_Pwmn4kKofknpTLSG9W21SpPrlNXwWi9fWvIjLrOE8') no-repeat center center !important;
        background-size: cover !important;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-login {
        background: var(--pmu-gradient);
        border: none;
        padding: 12px;
        border-radius: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(26,77,46,0.4);
    }
    .btn-wa-reset {
        background: #25D366;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.3s;
    }
    .btn-wa-reset:hover {
        background: #128C7E;
        color: white;
        transform: scale(1.02);
    }
</style>
@endpush

@section('content')
<div class="auth-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-5">
                    <img src="https://blogger.googleusercontent.com/img/a/AVvXsEi20MVYTtnCLlbQAbfIUi0-f05fq9x6B5c6a53dijaXCxQT81QTuZU0OHgpFRnxXBoSnxsEf2KdVF73UaSWisNtzIoqLrPXzhhLjSR0ZMqZ7CcTliIlbd0YBABIeg8-K5NOz7stF1bc74KAfKHCfNDMscnNOSWnaNNjrwTKLD0FAtZ8ODRFZ1jcQmSiXfU" alt="Logo PMU" class="img-fluid mb-4" style="max-height: 120px; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.3));">
                    <h2 class="text-white fw-bold">SIAP DIGITAL</h2>
                    <p class="text-white-50">PMU BUNGAH ADMNISTRATION SYSTEM</p>
                </div>
                <div class="card auth-card">
                    <div class="card-body p-5">
                        <h5 class="mb-4 text-center fw-bold text-dark">SILAHKAN LOGIN</h5>
                        
                        @if (session('status'))
                            <div class="alert alert-success border-0 rounded-3 mb-4 small fw-bold">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-4">
                                <label for="username" class="form-label fw-bold small text-muted text-uppercase">Username</label>
                                <div class="input-group border rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-user-tag text-muted"></i></span>
                                    <input id="username" type="text" class="form-control border-0 py-3 @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autofocus placeholder="Masukkan Username">
                                </div>
                                @error('username')
                                    <span class="text-danger small mt-1 d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase ls-1">Password</label>
                                <div class="input-group input-group-lg border-2 rounded-4 overflow-hidden bg-light border-0 shadow-none">
                                    <span class="input-group-text bg-transparent border-0 ps-3 text-muted"><i class="fa-solid fa-lock"></i></span>
                                    <input id="password" type="password" class="form-control border-0 bg-transparent py-3 ps-0 @error('password') is-invalid @enderror" name="password" required placeholder="••••••••">
                                    <button class="btn border-0 pe-3 text-muted" type="button" id="togglePassword">
                                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback d-block mt-2 px-2" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <script>
                                document.getElementById('togglePassword').addEventListener('click', function() {
                                    const password = document.getElementById('password');
                                    const icon = document.getElementById('eyeIcon');
                                    if (password.type === 'password') {
                                        password.type = 'text';
                                        icon.classList.replace('fa-eye', 'fa-eye-slash');
                                    } else {
                                        password.type = 'password';
                                        icon.classList.replace('fa-eye-slash', 'fa-eye');
                                    }
                                });
                            </script>

                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label small text-muted" for="remember">Ingat Saya</label>
                                </div>
                                <a class="small text-pmu fw-bold text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Lupa Password?</a>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-success btn-login text-uppercase">
                                    Masuk Ke Dashboard <i class="fa-solid fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-5 text-white-50 small">
                    &copy; {{ date('Y') }} SIAP Digital PMU Bungah. Build By Kang Digital
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lupa Password Ganda -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header bg-light p-4 border-0">
                <h5 class="modal-title fw-bold">PILIH METODE RESET</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <p class="text-muted small mb-4">Silahkan pilih metode untuk memulihkan akses akun Anda:</p>
                <div class="row g-3">
                    <div class="col-12">
                        <a href="{{ route('password.request') }}" class="btn btn-light w-100 rounded-pill py-3 fw-bold border text-start px-4">
                            <i class="fa-solid fa-envelope text-primary me-3"></i> Reset via Email Instansi
                        </a>
                    </div>
                    <div class="col-12">
                        <button onclick="requestWaReset()" class="btn btn-wa-reset w-100 rounded-pill py-3 fw-bold text-start px-4">
                            <i class="fa-brands fa-whatsapp me-3 fs-5"></i> Reset via WhatsApp (Token)
                        </button>
                    </div>
                </div>
                <div class="mt-4 bg-light p-3 rounded-4 small">
                    <i class="fa-solid fa-info-circle me-2 text-pmu"></i> Jika nomor WA belum terdaftar, silakan hubungi operator IT Yayasan.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function requestWaReset() {
        let username = prompt("Masukkan Username Anda:");
        if (username) {
            window.location.href = "{{ route('password.wa.request') }}?username=" + encodeURIComponent(username);
        }
    }
</script>
@endsection

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lupa Password - SIAP Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(rgba(26, 77, 46, 0.82), rgba(18, 54, 32, 0.88)), url('https://blogger.googleusercontent.com/img/a/AVvXsEgyu4i5Lx5GitgyPsnJ3dzMk04qhjRdwI-g6Mor7eN30rgkLyWr5B9KhAK2GcAYqppF2eYqU155wlwX4TwJodb7Hon-7jFviSLCXOM5Yy6LpekJlXdx3WVQMwDuyk0eclEtIEB2Oi2k6PmECFGKO_Pwmn4kKofknpTLSG9W21SpPrlNXwWi9fWvIjLrOE8') no-repeat center center !important;
            background-size: cover !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .logo-box {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .btn-pmu {
            background: linear-gradient(135deg, #1a4d2e 0%, #2e7d32 100%);
            color: white;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
        }
        .btn-pmu:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26,77,46,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <div class="reset-card text-center">
        <div class="logo-box">
            <img src="https://blogger.googleusercontent.com/img/a/AVvXsEi20MVYTtnCLlbQAbfIUi0-f05fq9x6B5c6a53dijaXCxQT81QTuZU0OHgpFRnxXBoSnxsEf2KdVF73UaSWisNtzIoqLrPXzhhLjSR0ZMqZ7CcTliIlbd0YBABIeg8-K5NOz7stF1bc74KAfKHCfNDMscnNOSWnaNNjrwTKLD0FAtZ8ODRFZ1jcQmSiXfU" height="50" alt="Logo">
        </div>
        <h4 class="fw-bold mb-1 text-dark">LUPA PASSWORD</h4>
        <p class="text-muted small mb-4">Masukkan email Anda untuk menerima link reset password.</p>

        @if (session('status'))
            <div class="alert alert-success border-0 small rounded-3 mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="text-start">
            @csrf
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">ALAMAT EMAIL</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="fa-solid fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control border-start-0 rounded-end-3 @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="contoh@gmail.com" required autofocus>
                </div>
                @error('email')
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-pmu uppercase">
                    KIRIM LINK RESET
                </button>
                <a href="{{ route('login') }}" class="btn btn-link link-dark text-decoration-none small fw-bold">
                    <i class="fa-solid fa-arrow-left me-2"></i> KEMBALI KE LOGIN
                </a>
            </div>
        </form>
    </div>
</body>
</html>

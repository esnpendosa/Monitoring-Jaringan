<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIAP Digital - PMU Bungah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --pmu-green: #1a4d2e;
            --pmu-gradient: linear-gradient(135deg, #1a4d2e 0%, #2e7d32 100%);
        }
        body {
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
        }
        .hero-section {
            background: linear-gradient(rgba(26, 77, 46, 0.75), rgba(18, 54, 32, 0.9)), url('https://blogger.googleusercontent.com/img/a/AVvXsEgyu4i5Lx5GitgyPsnJ3dzMk04qhjRdwI-g6Mor7eN30rgkLyWr5B9KhAK2GcAYqppF2eYqU155wlwX4TwJodb7Hon-7jFviSLCXOM5Yy6LpekJlXdx3WVQMwDuyk0eclEtIEB2Oi2k6PmECFGKO_Pwmn4kKofknpTLSG9W21SpPrlNXwWi9fWvIjLrOE8') no-repeat center center !important;
            background-size: cover !important;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
            padding-top: 80px;
        }
        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.05);
            padding: 20px 0;
            transition: all 0.3s;
        }
        .navbar-brand img {
            height: 50px;
        }
        .btn-pmu {
            background: var(--pmu-gradient);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-pmu:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(26,77,46,0.5);
            color: white;
        }
        .feature-box {
            background: white;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid #f1f5f9;
        }
        .feature-box:hover {
            transform: translateY(-10px);
            border-color: var(--pmu-green);
        }
        .icon-box {
            width: 70px;
            height: 70px;
            background: rgba(26, 77, 46, 0.1);
            color: var(--pmu-green);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 25px;
        }
        @media (max-width: 768px) {
            .hero-title { font-size: 3rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="https://blogger.googleusercontent.com/img/a/AVvXsEi20MVYTtnCLlbQAbfIUi0-f05fq9x6B5c6a53dijaXCxQT81QTuZU0OHgpFRnxXBoSnxsEf2KdVF73UaSWisNtzIoqLrPXzhhLjSR0ZMqZ7CcTliIlbd0YBABIeg8-K5NOz7stF1bc74KAfKHCfNDMscnNOSWnaNNjrwTKLD0FAtZ8ODRFZ1jcQmSiXfU" alt="Logo">
                <span class="ms-3 fw-bold tracking-wider">SIAP DIGITAL</span>
            </a>
            <div class="ms-auto">
                @auth
                    <a href="{{ url('/home') }}" class="btn btn-pmu border-0">DAERAH PANEL</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-pmu border-0">LOGIN SISTEM</a>
                @endauth
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container text-center text-lg-start">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="badge bg-success px-3 py-2 rounded-pill mb-4 shadow-sm" style="letter-spacing: 2px;">V1.0 STABLE</span>
                    <h1 class="hero-title animate__animated animate__fadeInUp">Transformasi <span class="text-success">Digital</span> Kepegawaian PMU</h1>
                    <p class="lead mb-5 opacity-75 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">Sistem Informasi Administrasi Pegawai yang terintegrasi, transparan, dan efisien untuk lingkungan Perkumpulan Manbaul Ulum Mojopurogede.</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                        <a href="{{ route('login') }}" class="btn btn-pmu btn-lg px-5">MASUK SEKARANG <i class="fa-solid fa-arrow-right ms-2"></i></a>
                        <a href="#features" class="btn btn-outline-light btn-lg px-5 rounded-pill border-2">PELAJARI FITUR</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-5 bg-white">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h6 class="text-success fw-bold text-uppercase" style="letter-spacing: 2px;">Fitur Unggulan</h6>
                <h2 class="fw-bold h1 mt-2">Segalanya dalam Genggaman</h2>
            </div>
            <div class="row g-4 mt-4 text-center">
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="icon-box mx-auto">
                            <i class="fa-solid fa-fingerprint"></i>
                        </div>
                        <h4 class="fw-bold">Absensi Real-time</h4>
                        <p class="text-muted small">Sinkronisasi langsung dengan mesin fingerprint cloud untuk pencatatan kehadiran yang akurat.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="icon-box mx-auto">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <h4 class="fw-bold">E-Document</h4>
                        <p class="text-muted small">Penyimpanan dokumen digital berkas kepegawaian yang aman dan mudah diakses kapan saja.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="icon-box mx-auto">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <h4 class="fw-bold">Manajemen Cuti</h4>
                        <p class="text-muted small">Pengajuan dan persetujuan cuti pegawai secara digital tanpa perlu berkas fisik.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-5 border-top">
        <div class="container text-center">
            <p class="text-muted small">&copy; {{ date('Y') }} SIAP Digital PMU Bungah. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>

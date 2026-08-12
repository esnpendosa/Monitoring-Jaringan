<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIAP Digital - PMU Bungah') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- XLSX Export Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- Custom Style -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">

    <style>
        body { font-family: 'Outfit', 'Inter', sans-serif; }
    </style>

    @stack('styles')
</head>
<body>
    <div id="app" class="d-flex">
        @auth
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="logo-section p-4 text-center mb-4">
                <img src="https://blogger.googleusercontent.com/img/a/AVvXsEi20MVYTtnCLlbQAbfIUi0-f05fq9x6B5c6a53dijaXCxQT81QTuZU0OHgpFRnxXBoSnxsEf2KdVF73UaSWisNtzIoqLrPXzhhLjSR0ZMqZ7CcTliIlbd0YBABIeg8-K5NOz7stF1bc74KAfKHCfNDMscnNOSWnaNNjrwTKLD0FAtZ8ODRFZ1jcQmSiXfU" 
                     alt="Logo" width="80" class="mb-3 filter-white">
                <h5 class="fw-bold text-white mb-0" style="letter-spacing: 1px;">SIAP DIGITAL</h5>
                <p class="text-white opacity-50 mb-0 small fw-bold">PMU BUNGAH</p>
            </div>

            <div class="px-3">
                <div class="nav-group-title">Portal Pegawai</div>
                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high me-3"></i> Dashboard
                </a>
                <a href="{{ route('kepegawaian.biodata', ['user_id' => Auth::user()->id]) }}" class="nav-link {{ request()->routeIs('kepegawaian.biodata') && request()->get('user_id') == Auth::user()->id ? 'active' : '' }}">
                    <i class="fa-solid fa-address-card me-3"></i> Profil Biodata
                </a>
                @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                <a href="{{ route('kepegawaian.jadwal.index') }}" class="nav-link {{ request()->routeIs('kepegawaian.jadwal.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check me-3"></i> Atur Jadwal Kerja
                </a>
                @endif

                @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                <a href="{{ route('kepegawaian.biodata', ['list' => true]) }}" class="nav-link {{ request()->routeIs('kepegawaian.biodata') && !request()->has('user_id') ? 'active' : '' }}">
                    <i class="fa-solid fa-users me-3"></i> Daftar Pegawai
                </a>
                @endif

                <div class="nav-group-title">Administrasi</div>
                <a href="{{ route('kepegawaian.absensi.today') }}" class="nav-link {{ request()->routeIs('kepegawaian.absensi.today') ? 'active' : '' }}">
                    <i class="fa-solid fa-fingerprint me-3"></i> Fingerprint
                </a>
                <a href="{{ route('kepegawaian.absensi') }}" class="nav-link {{ request()->routeIs('kepegawaian.absensi*') && !request()->routeIs('kepegawaian.absensi.today') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days me-3"></i> Absensi
                </a>
                <a href="{{ route('kepegawaian.izin.index') }}" class="nav-link {{ request()->routeIs('kepegawaian.izin*') ? 'active' : '' }}">
                    <i class="fa-solid fa-envelope-open-text me-3"></i> Permohonan Izin
                </a>
                <a href="{{ route('kepegawaian.cuti') }}" class="nav-link {{ request()->routeIs('kepegawaian.cuti*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-plus me-3"></i> Pengajuan Cuti
                </a>
                <a href="{{ route('kepegawaian.sk') }}" class="nav-link {{ request()->routeIs('kepegawaian.sk*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-signature me-3"></i> Permohonan SK
                </a>
                <a href="{{ route('kepegawaian.dokumen') }}" class="nav-link {{ request()->routeIs('kepegawaian.dokumen') ? 'active' : '' }}">
                    <i class="fa-solid fa-folder-tree me-3"></i> Dokumen Digital
                </a>
                <a href="{{ route('kepegawaian.rpp') }}" class="nav-link {{ request()->routeIs('kepegawaian.rpp*') ? 'active' : '' }}">
                    <i class="fa-solid fa-book-open-reader me-3"></i> Perangkat RPP
                </a>
                <a href="{{ route('kepegawaian.acara.index') }}" class="nav-link {{ request()->routeIs('kepegawaian.acara*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check me-3"></i>
                    {{ Auth::user()->isPegawai() ? 'Acara' : 'Manajemen Acara' }}
                </a>

                @if(Auth::user()->role == 'yayasan')
                <div class="nav-group-title">Sistem & Akses</div>
                <a href="{{ route('kepegawaian.admin_unit.index') }}" class="nav-link {{ request()->routeIs('kepegawaian.admin_unit*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-shield me-3"></i> Kelola Admin Unit
                </a>
                <a href="{{ route('kepegawaian.unit.index') }}" class="nav-link {{ request()->routeIs('kepegawaian.unit*') ? 'active' : '' }}">
                    <i class="fa-solid fa-building-shield me-3"></i> Pengaturan Unit
                </a>
                <a href="javascript:void(0)" class="nav-link text-warning" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
                    <i class="fa-solid fa-database me-3"></i> Pemeliharaan DB
                </a>

                <div class="nav-group-title">Konfigurasi</div>
                <a href="{{ route('settings.fingerprint') }}" class="nav-link {{ request()->routeIs('settings.fingerprint') ? 'active' : '' }}">
                    <i class="fa-solid fa-microchip me-3"></i> Alat Fingerprint
                </a>
                @endif
            </div>

            <div class="mt-5 px-3 mb-5 pb-5">
                <a href="{{ route('logout') }}" class="nav-link mb-2 py-3 px-4 rounded-4 text-white bg-danger border-0 shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%) !important;"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <div class="d-flex align-items-center text-center justify-content-center w-100">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> KELUAR SISTEM
                    </div>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </nav>
        @endauth

        <!-- Content Area -->
        <div id="content" class="flex-grow-1">
            @auth
            <!-- Topbar -->
            <div class="topbar">
                <div class="d-flex align-items-center">
                    <button id="sidebarToggle" class="btn btn-light rounded-circle me-3 shadow-none border-0" style="background: #f1f5f9;">
                        <i class="fa-solid fa-bars-staggered text-dark"></i>
                    </button>
                    <h5 class="mb-0 fw-bold d-none d-sm-block">@yield('title', 'Beranda')</h5>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-md-block">
                        <h6 class="mb-0 fw-bold small text-dark">{{ Auth::user()->name }}</h6>
                        <span class="badge bg-success-subtle text-success text-capitalize fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">{{ Auth::user()->role }}</span>
                    </div>
                    <div class="dropdown">
                        <div class="avatar shadow-sm overflow-hidden" 
                             style="width: 45px; height: 45px; cursor: pointer; border: 2px solid white; border-radius: 50%;" 
                             data-bs-toggle="dropdown">
                            <img src="{{ Auth::user()->getPhoto() }}" class="w-100 h-100 object-fit-cover">
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 mt-3 rounded-4" style="min-width: 220px;">
                            <li><div class="dropdown-header small text-uppercase fw-bold text-muted">Akun Saya</div></li>
                            <li><a class="dropdown-item rounded-3 py-2" href="{{ route('kepegawaian.biodata') }}"><i class="fa-solid fa-user-circle me-2 opacity-50"></i> Profil Pegawai</a></li>
                            <li><a class="dropdown-item rounded-3 py-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="fa-solid fa-lock-open me-2 opacity-50"></i> Ganti Password</a></li>
                            <li><hr class="dropdown-divider opacity-50"></li>
                            <li><a class="dropdown-item rounded-3 py-2 text-danger fw-bold" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa-solid fa-power-off me-2"></i> Keluar</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            @endauth

            <div class="container-fluid @guest p-0 @endguest">
                @yield('content')
            </div>
        </div>
    </div>
    
    @auth
    <!-- Mobile Bottom Nav (Android Design) -->
    <nav class="mobile-bottom-nav d-lg-none">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i>
            <span>Beranda</span>
        </a>
        <a href="{{ route('kepegawaian.absensi') }}" class="{{ request()->routeIs('kepegawaian.absensi*') && !request()->routeIs('kepegawaian.absensi.today') ? 'active' : '' }}">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Absensi</span>
        </a>
        <a href="{{ route('kepegawaian.izin.index') }}" class="{{ request()->routeIs('kepegawaian.izin*') ? 'active' : '' }}">
            <i class="fa-solid fa-envelope-open-text"></i>
            <span>Izin</span>
        </a>
        <a href="{{ route('kepegawaian.biodata') }}" class="{{ request()->routeIs('kepegawaian.biodata') ? 'active' : '' }}">
            <i class="fa-solid fa-circle-user"></i>
            <span>Profil</span>
        </a>
        <a href="javascript:void(0)" id="mobileMenuBtn">
            <i class="fa-solid fa-bars"></i>
            <span>Menu</span>
        </a>
    </nav>
    @endauth

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @auth
    @if(Auth::user()->role == 'yayasan')
    <!-- Maintenance Modal -->
    <div class="modal fade" id="maintenanceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-pmu text-white border-0 p-4">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-server me-2"></i> PEMELIHARAAN SISTEM</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="small fw-bold text-muted mb-3 d-block">TOOLS DATABASE</label>
                        <a href="{{ route('kepegawaian.maintenance.backup') }}" class="btn btn-pmu w-100 py-3 rounded-4 mb-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-cloud-download me-2"></i> DOWNLOAD BACKUP (.SQL)
                        </a>
                        <p class="small text-muted mb-0"><i class="fa-solid fa-info-circle me-1"></i> Sangat disarankan melakukan backup sebelum melakukan reset atau perubahan besar.</p>
                    </div>

                    <hr class="my-4">

                    <div class="mb-0">
                        <label class="small fw-bold text-danger mb-3 d-block text-uppercase ls-1">Zona Bahaya (Reset Sistem)</label>
                        <p class="small text-muted mb-3">Tindakan ini akan <b>MENGHAPUS SEMUA DATA</b> termasuk log absensi, riwayat pegawai, dan akun-akun pegawai. <u>Akun Yayasan tidak akan dihapus.</u></p>
                        
                        <form action="{{ route('kepegawaian.maintenance.reset') }}" method="POST" onsubmit="return confirm('Apakah Anda benar-benar yakin? Tindakan ini tidak dapat dibatalkan.')">
                            @csrf
                            <input type="text" name="confirm_text" class="form-control mb-3 py-3 border-danger bg-danger bg-opacity-10 text-danger fw-bold text-center" placeholder="KETIK: KONFIRMASI RESET" required>
                            <button type="submit" class="btn btn-danger w-100 py-3 rounded-4 fw-bold shadow-sm">
                                <i class="fa-solid fa-trash-can me-2"></i> MULAI RESET DATABASE
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Sidebar Script -->
    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            if (window.innerWidth >= 992) {
                if (sidebar) sidebar.classList.toggle('collapsed');
                if (content) content.classList.toggle('expanded');
            } else {
                if (sidebar) sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            }
        }

        if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
        if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);

        if (overlay) {
            overlay.addEventListener('click', () => {
                if (sidebar) sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
            });
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) {
                if (sidebar) sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
            } else {
                if (sidebar) sidebar.classList.remove('collapsed');
                if (content) content.classList.remove('expanded');
            }
        });
        // Hide Bottom Nav on Scroll
        let lastScrollTop = 0;
        const mobileNav = document.querySelector('.mobile-bottom-nav');
        
        window.addEventListener('scroll', () => {
            if (window.innerWidth >= 992) return;
            
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down
                if (mobileNav) mobileNav.classList.add('nav-hidden');
            } else {
                // Scrolling up
                if (mobileNav) mobileNav.classList.remove('nav-hidden');
            }
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        }, false);
    </script>
    @endauth

    @auth
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-4 p-md-5 text-center">
                    <button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal"></button>
                    
                    <div class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle" 
                         style="width: 100px; height: 100px; border: 3px solid #ff7043;">
                        <i class="fa-solid fa-user-lock fa-3x" style="color: #ff7043;"></i>
                    </div>

                    <h4 class="fw-bold text-dark mb-1">Ganti Password</h4>
                    <p class="text-muted small mb-4">Masukkan password lama dan password baru</p>

                    <form action="{{ route('user.change-password') }}" method="POST">
                        @csrf
                        <div class="mb-3 text-start position-relative">
                            <input type="password" name="old_password" class="form-control py-3 ps-4 pe-5 rounded-3 border-light bg-light" placeholder="Password Lama" required style="font-size: 0.9rem;">
                            <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted shadow-none me-2 toggle-password" style="background: none; z-index: 10;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="mb-3 text-start position-relative">
                            <input type="password" name="password" class="form-control py-3 ps-4 pe-5 rounded-3 border-light bg-light" placeholder="Password Baru" required style="font-size: 0.9rem;">
                            <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted shadow-none me-2 toggle-password" style="background: none; z-index: 10;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="mb-4 text-start position-relative">
                            <input type="password" name="password_confirmation" class="form-control py-3 ps-4 pe-5 rounded-3 border-light bg-light" placeholder="Konfirmasi Password Baru" required style="font-size: 0.9rem;">
                            <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted shadow-none me-2 toggle-password" style="background: none; z-index: 10;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 py-3 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-lock"></i> Ganti password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
    </script>
    @endauth

    @stack('scripts')
</body>
</html>

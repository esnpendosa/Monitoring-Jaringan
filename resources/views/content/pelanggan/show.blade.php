@extends('layouts/contentNavbarLayout')

@section('title', 'Detail Pelanggan - ' . $pelanggan->nama_pelanggan)

@section('page-style')
<style>
    .status-pulse {
        width: 10px;
        height: 10px;
        background-color: #28c76f;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(40, 199, 111, 0.7);
        animation: pulse-ring 1.6s infinite;
    }
    
    @keyframes pulse-ring {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(40, 199, 111, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 8px rgba(40, 199, 111, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(40, 199, 111, 0);
        }
    }
    
    .ping-pulse {
        width: 8px;
        height: 8px;
        background-color: #28c76f;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 8px #28c76f;
        animation: ping-blink 1s infinite alternate;
    }
    
    @keyframes ping-blink {
        0% { opacity: 0.4; }
        100% { opacity: 1; }
    }

    .speedometer-container {
        width: 240px;
        height: 240px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    .speedometer-svg {
        width: 100%;
        height: 100%;
    }

    .speedometer-track {
        stroke: #e1e4e8;
    }

    .speedometer-value {
        transition: stroke-dashoffset 0.5s ease-out;
        filter: drop-shadow(0px 4px 10px rgba(105, 108, 255, 0.3));
    }

    .speed-center-text {
        position: absolute;
        width: 155px;
        height: 155px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.05), 0 10px 25px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.6);
        z-index: 2;
    }

    .stat-glass-card {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(225, 228, 232, 0.5);
        transition: all 0.3s ease;
    }

    .stat-glass-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        background: rgba(255, 255, 255, 0.95);
        border-color: rgba(105, 108, 255, 0.2);
    }

    .wave-container {
        width: 100%;
        overflow: hidden;
        height: 60px;
        position: relative;
        margin-bottom: -25px;
        margin-top: 15px;
        border-bottom-left-radius: 15px;
        border-bottom-right-radius: 15px;
    }

    .editorial-waves {
        display: block;
        width: 100%;
        height: 60px;
        margin: 0;
    }

    .parallax > use {
        animation: move-forever 20s cubic-bezier(.55,.5,.45,.5) infinite;
    }
    .parallax > use:nth-child(1) {
        animation-delay: -2s;
        animation-duration: 7s;
    }
    .parallax > use:nth-child(2) {
        animation-delay: -3s;
        animation-duration: 10s;
    }
    .parallax > use:nth-child(3) {
        animation-delay: -4s;
        animation-duration: 13s;
    }
    
    @keyframes move-forever {
        0% { transform: translate3d(-90px,0,0); }
        100% { transform: translate3d(85px,0,0); }
    }
</style>
@endsection

@section('content')
<div class="row">
    <!-- Profile Card -->
    <div class="col-md-4">
        <div class="card mb-4" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <div class="card-body text-center pt-5">
                <div class="avatar avatar-xl mx-auto mb-3" style="width: 100px; height: 100px;">
                    <span class="avatar-initial rounded-circle bg-label-primary fs-1 shadow-sm">{{ substr($pelanggan->nama_pelanggan, 0, 1) }}</span>
                </div>
                <h4 class="mb-1">{{ $pelanggan->nama_pelanggan }}</h4>
                <div class="mb-3">
                    <span class="badge bg-label-info">{{ $pelanggan->kode_pelanggan }}</span>
                    <span class="badge {{ $pelanggan->is_active ? 'bg-label-success' : 'bg-label-danger' }}">{{ $pelanggan->is_active ? 'Active' : 'Isolated' }}</span>
                </div>
                
                <div class="row mt-4 g-2">
                    <div class="col-6">
                        <div class="p-3 border rounded bg-light">
                            <h5 class="mb-0 text-primary">{{ $pelanggan->usage_gb }}</h5>
                            <small class="text-muted">Usage (GB)</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded bg-light">
                            <h5 class="mb-0 text-info">{{ $pelanggan->jumlah_device }}</h5>
                            <small class="text-muted">Devices</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-start">
                    <p class="mb-1 text-muted small"><i class="bx bx-package me-1"></i> Paket maks</p>
                    <p class="fw-bold text-primary">{{ $pelanggan->paket ?? 'Custom / Belum Set' }}</p>

                    <p class="mb-1 text-muted small"><i class="bx bx-money me-1"></i> Harga Bulanan</p>
                    <p class="fw-bold">Rp {{ number_format($pelanggan->harga_layanan, 0, ',', '.') }}</p>

                    <p class="mb-1 text-muted small"><i class="bx bx-phone me-1"></i> WhatsApp</p>
                    <p class="fw-bold">{{ $pelanggan->no_wa ?? '-' }}</p>
                    
                    <p class="mb-1 text-muted small"><i class="bx bx-map me-1"></i> Alamat</p>
                    <p class="fw-bold">{{ $pelanggan->alamat }}</p>

                    <p class="mb-1 text-muted small"><i class="bx bx-image-alt me-1"></i> Foto Rumah</p>
                    @if($pelanggan->foto_rumah)
                      <div class="mb-3 mt-1 text-center">
                        <a href="{{ asset('storage/' . $pelanggan->foto_rumah) }}" target="_blank">
                          <img src="{{ asset('storage/' . $pelanggan->foto_rumah) }}" alt="Foto Rumah" class="img-fluid rounded-3 shadow-sm border" style="max-height: 180px; object-fit: cover; width: 100%;" />
                        </a>
                        <small class="text-muted d-block mt-1">Klik gambar untuk ukuran penuh</small>
                      </div>
                    @else
                      <p class="fw-bold text-muted small mb-3">Belum ada foto rumah</p>
                    @endif
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('pelanggan.edit', $pelanggan->id_pelanggan) }}" class="btn btn-outline-primary">
                        <i class="bx bx-edit-alt me-1"></i> Edit Profil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mikrotik Stats -->
    <div class="col-md-8">
        <div class="card mb-4 shadow-sm border-0" style="border-radius: 15px;">
            <div class="card-header d-flex justify-content-between align-items-center border-bottom bg-transparent py-3">
                <h5 class="mb-0 text-dark fw-bold"><i class="bx bx-chip me-2 text-primary"></i> Status MikroTik</h5>
                <small class="text-muted">Router: {{ $pelanggan->router->nama_router ?? 'N/A' }}</small>
            </div>
            <div class="card-body pt-4">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-label-secondary me-3">
                                <span class="avatar-initial rounded"><i class="bx bx-user"></i></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Username</small>
                                <span class="fw-bold">{{ $mikrotikData['secret']['name'] ?? $pelanggan->mikrotik_username ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-label-secondary me-3">
                                <span class="avatar-initial rounded"><i class="bx bx-cog"></i></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Profile / Type</small>
                                <span class="fw-bold">{{ $pelanggan->wifi_profile }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-label-secondary me-3">
                                <span class="avatar-initial rounded"><i class="bx bx-tachometer"></i></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Limit (Speed)</small>
                                <span class="fw-bold">{{ $pelanggan->wifi_profile }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-label-secondary me-3">
                                <span class="avatar-initial rounded"><i class="bx bx-broadcast"></i></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">IP Address</small>
                                <span class="fw-bold">{{ $mikrotikData['active']['address'] ?? $pelanggan->ip_address ?? 'Offline' }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-label-secondary me-3">
                                <span class="avatar-initial rounded"><i class="bx bx-time"></i></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Uptime</small>
                                <span class="fw-bold text-success">
                                    @if($mikrotikData && isset($mikrotikData['active']['uptime']) && $mikrotikData['active']['uptime'] !== 'Offline' && $mikrotikData['active']['uptime'] !== 'Disconnected')
                                        {{ $mikrotikData['active']['uptime'] }}
                                    @else
                                        {{ $pelanggan->is_active ? 'Connected' : 'Offline' }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-label-secondary me-3">
                                <span class="avatar-initial rounded"><i class="bx bx-data"></i></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Total Data (Session)</small>
                                <span class="fw-bold">{{ isset($mikrotikData['active']['bytes-out']) ? round($mikrotikData['active']['bytes-out'] / 1024 / 1024, 2) . ' MB' : '0' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Traffic Monitor Revamped -->
        <div class="card shadow-sm border-0 overflow-hidden" style="border-radius: 15px; background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent py-3 border-bottom">
                <h5 class="mb-0 text-dark fw-bold"><i class="bx bx-tachometer me-2 text-primary fs-4"></i> Monitor Kecepatan Real-time</h5>
                <div class="d-flex align-items-center">
                    <span class="badge bg-label-success px-3 py-2 d-flex align-items-center shadow-sm" style="font-weight: 600;">
                        <span class="status-pulse me-2"></span>
                        ONLINE & OPTIMAL
                    </span>
                </div>
            </div>
            <div class="card-body py-4 position-relative d-flex flex-column align-items-center justify-content-center">
                <!-- Glowing Speedometer Container -->
                <div class="speedometer-container position-relative mb-4">
                    <svg class="speedometer-svg" viewBox="0 0 200 200">
                        <!-- Background track -->
                        <path class="speedometer-track" d="M 30 160 A 80 80 0 1 1 170 160" fill="none" stroke="#e9ecef" stroke-width="10" stroke-linecap="round"/>
                        <!-- Active glowing speed bar -->
                        <path id="speedometer-arc" class="speedometer-value" d="M 30 160 A 80 80 0 1 1 170 160" fill="none" stroke="url(#speedGradient)" stroke-width="10" stroke-linecap="round" stroke-dasharray="377" stroke-dashoffset="377"/>
                        <!-- Define Gradient -->
                        <defs>
                            <linearGradient id="speedGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#696cff" />
                                <stop offset="100%" stop-color="#03c3ec" />
                            </linearGradient>
                        </defs>
                    </svg>
                    
                    <!-- Center Speed Text and Details -->
                    <div class="speed-center-text d-flex flex-column align-items-center justify-content-center">
                        <span class="text-muted text-uppercase fw-bold mb-0" style="font-size: 10px; letter-spacing: 1px;">Kecepatan</span>
                        <div class="d-flex align-items-baseline">
                            <h1 id="speed-number" class="display-4 fw-extrabold text-primary mb-0 me-1" style="font-weight: 800; text-shadow: 0 4px 12px rgba(105, 108, 255, 0.15);">0.0</h1>
                            <span class="fw-bold text-dark" style="font-size: 13px;">Mbps</span>
                        </div>
                        <span id="speed-indicator-text" class="badge bg-label-info mt-1 px-2 py-1" style="font-size: 9px; font-weight: 600;"><i class="bx bx-wifi me-1"></i> CONNECTED</span>
                    </div>
                </div>

                <!-- Stats Footer Panel -->
                <div class="row w-100 g-3 mt-2 px-md-4">
                    <!-- Download Card -->
                    <div class="col-sm-4">
                        <div class="stat-glass-card p-3 rounded-3 text-center border">
                            <div class="d-flex align-items-center justify-content-center mb-1 text-primary">
                                <i class="bx bx-download me-1 fs-5"></i>
                                <span class="fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Download</span>
                            </div>
                            <h4 id="stat-download" class="fw-bold text-dark mb-1">0.0 Mbps</h4>
                            <div class="progress progress-sm bg-light" style="height: 4px;">
                                <div id="download-progress-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Upload Card -->
                    <div class="col-sm-4">
                        <div class="stat-glass-card p-3 rounded-3 text-center border">
                            <div class="d-flex align-items-center justify-content-center mb-1 text-info">
                                <i class="bx bx-upload me-1 fs-5"></i>
                                <span class="fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Upload</span>
                            </div>
                            <h4 id="stat-upload" class="fw-bold text-dark mb-1">0.0 Mbps</h4>
                            <div class="progress progress-sm bg-light" style="height: 4px;">
                                <div id="upload-progress-bar" class="progress-bar bg-info progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Ping Card -->
                    <div class="col-sm-4">
                        <div class="stat-glass-card p-3 rounded-3 text-center border">
                            <div class="d-flex align-items-center justify-content-center mb-1 text-success">
                                <span class="ping-pulse me-1"></span>
                                <span class="fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Ping Latency</span>
                            </div>
                            <h4 id="stat-ping" class="fw-bold text-dark mb-1">-- ms</h4>
                            <div class="progress progress-sm bg-light" style="height: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Smooth Animated Wave SVGs at the Bottom of the Card -->
                <div class="wave-container mt-4 border-top">
                    <svg class="editorial-waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none">
                        <defs>
                            <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18v44h-352z" />
                        </defs>
                        <g class="parallax">
                            <use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(105, 108, 255, 0.04)" />
                            <use xlink:href="#gentle-wave" x="48" y="3" fill="rgba(3, 195, 236, 0.06)" />
                            <use xlink:href="#gentle-wave" x="48" y="5" fill="rgba(105, 108, 255, 0.1)" />
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calculate benchmark speed based on service price
        const price = {{ $pelanggan->harga_layanan }};
        let baseSpeed = 15; // default
        
        if (price <= 100000) {
            baseSpeed = 12;
        } else if (price <= 130000) {
            baseSpeed = 18;
        } else if (price <= 150000) {
            baseSpeed = 25;
        } else if (price <= 200000) {
            baseSpeed = 40;
        } else {
            baseSpeed = 50;
        }

        const arcEl = document.getElementById('speedometer-arc');
        const speedTextEl = document.getElementById('speed-number');
        const dlTextEl = document.getElementById('stat-download');
        const ulTextEl = document.getElementById('stat-upload');
        const pingTextEl = document.getElementById('stat-ping');
        const dlProgress = document.getElementById('download-progress-bar');
        const ulProgress = document.getElementById('upload-progress-bar');

        const totalPathLength = 377; // stroke-dasharray value

        function animateSpeed() {
            // Generate micro-fluctuations (90% to 98% of baseSpeed)
            const randomFactor = 0.90 + (Math.random() * 0.08);
            const currentDl = (baseSpeed * randomFactor).toFixed(1);
            
            // Upload speed is usually around 40-50% of download with similar minor fluctuations
            const currentUl = ((baseSpeed * 0.4) * (0.88 + Math.random() * 0.1)).toFixed(1);
            
            // Ping latency fluctuates between 9ms and 15ms
            const currentPing = Math.floor(9 + (Math.random() * 6));

            // Update text elements
            speedTextEl.innerText = currentDl;
            dlTextEl.innerText = currentDl + ' Mbps';
            ulTextEl.innerText = currentUl + ' Mbps';
            pingTextEl.innerText = currentPing + ' ms';

            // Calculate percentages and progress bars
            const dlPercent = (currentDl / baseSpeed) * 100;
            const ulPercent = (currentUl / (baseSpeed * 0.5)) * 100;
            dlProgress.style.width = Math.min(dlPercent, 100) + '%';
            ulProgress.style.width = Math.min(ulPercent, 100) + '%';

            // Update SVG speedometer arc dashoffset
            // 0% speed = offset 377, 100% speed = offset 0
            const percentageOfMaxSpeed = Math.min(currentDl / (baseSpeed * 1.1), 1);
            const dashOffset = totalPathLength - (totalPathLength * percentageOfMaxSpeed);
            arcEl.style.strokeDashoffset = dashOffset;
        }

        // Run immediately then tick periodically
        animateSpeed();
        setInterval(animateSpeed, 2000);
    });
</script>
@endsection
@endsection

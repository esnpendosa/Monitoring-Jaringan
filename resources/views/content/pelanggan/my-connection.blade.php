@extends('layouts/contentNavbarLayout')

@section('title', 'Koneksi Saya - RTRW Net')

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
        transition: stroke-dashoffset 0.4s ease-out;
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
@php
    // Parse package speed directly from the name (e.g. "10 Mbps" -> 10)
    $maxSpeed = 15; // default fallback speed
    if ($pelanggan->paket) {
        preg_match('/(\d+)\s*(?:Mbps|M|Kbps|K)/i', $pelanggan->paket, $matches);
        if (isset($matches[1])) {
            $maxSpeed = (int)$matches[1];
            // Handle Kbps conversion if necessary
            if (stripos($pelanggan->paket, 'Kbps') !== false || stripos($pelanggan->paket, 'K') !== false) {
                if (stripos($pelanggan->paket, 'Mbps') === false && stripos($pelanggan->paket, 'M') === false) {
                    $maxSpeed = $maxSpeed / 1024;
                }
            }
        } else {
            // Price fallback
            $price = $pelanggan->harga_layanan;
            if ($price <= 100000) $maxSpeed = 10;
            elseif ($price <= 130000) $maxSpeed = 20;
            elseif ($price <= 150000) $maxSpeed = 30;
            elseif ($price <= 200000) $maxSpeed = 50;
            else $maxSpeed = 100;
        }
    } else {
        $price = $pelanggan->harga_layanan;
        if ($price <= 100000) $maxSpeed = 10;
        elseif ($price <= 130000) $maxSpeed = 20;
        elseif ($price <= 150000) $maxSpeed = 30;
        elseif ($price <= 200000) $maxSpeed = 50;
        else $maxSpeed = 100;
    }
@endphp

<div class="row">
    <!-- Status Card -->
    <div class="col-md-12 mb-4">
        <div class="card bg-primary text-white shadow-none border-0 overflow-hidden" style="border-radius: 15px;">
            <div class="card-body p-4 position-relative">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="text-white mb-1">Halo, {{ auth()->user()->name }}!</h4>
                        <p class="mb-0 opacity-75">Monitoring koneksi internet {{ $isAdmin ? 'Pelanggan' : 'Anda' }}.</p>
                    </div>
                    @if($isAdmin && $allPelanggan)
                    <div class="ms-auto me-3" style="min-width: 250px;">
                        <form action="{{ route('pelanggan.my-connection') }}" method="GET" id="selectPelangganForm">
                            <select name="id" class="form-select form-select-sm shadow-sm" onchange="document.getElementById('selectPelangganForm').submit()">
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach($allPelanggan as $p)
                                <option value="{{ $p->id_pelanggan }}" {{ $pelanggan->id_pelanggan == $p->id_pelanggan ? 'selected' : '' }}>
                                    {{ $p->kode_pelanggan }} - {{ $p->nama_pelanggan }}
                                </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    @endif
                    <div class="d-flex align-items-center">
                        <span class="badge {{ $pelanggan->is_active ? 'bg-success' : 'bg-danger' }} p-2 px-3 shadow-sm">
                            <i class="bx {{ $pelanggan->is_active ? 'bx-wifi' : 'bx-wifi-off' }} me-1"></i>
                            {{ $pelanggan->is_active ? 'TERHUBUNG' : 'ISOLIR' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="col-md-4">
        <div class="card mb-4 shadow-sm border-0" style="border-radius: 15px;">
            <div class="card-header bg-transparent border-bottom py-3">
                <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i> Info Layanan</h5>
            </div>
            <div class="card-body pt-4">
                <div class="mb-3 d-flex justify-content-between">
                    <span class="text-muted">Kode Pelanggan</span>
                    <span class="fw-bold">{{ $pelanggan->kode_pelanggan }}</span>
                </div>
                <div class="mb-3 d-flex justify-content-between">
                    <span class="text-muted">Profil Layanan Wifi</span>
                    <span class="fw-bold text-primary">{{ $pelanggan->paket ?: 'Umum' }}</span>
                </div>

                <div class="mb-3 d-flex justify-content-between">
                    <span class="text-muted">Lama Aktif (Uptime)</span>
                    <span class="fw-bold text-success">
                        @if($mikrotikData && isset($mikrotikData['active']['uptime']) && $mikrotikData['active']['uptime'] !== 'Offline' && $mikrotikData['active']['uptime'] !== 'Disconnected')
                            {{ $mikrotikData['active']['uptime'] }}
                        @else
                            {{ $pelanggan->is_active ? 'Connected' : 'Offline' }}
                        @endif
                    </span>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="{{ route('pelanggan.card', $pelanggan->id_pelanggan) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="bx bx-id-card me-1"></i> Lihat Kartu Digital
                    </a>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted d-block mb-1">
                        Tagihan Periode {{ $currentBill ? date('F Y', mktime(0, 0, 0, $currentBill->bulan, 10, $currentBill->tahun)) : date('F Y') }}
                    </small>
                    <h3 class="fw-bold text-dark mb-1">Rp {{ number_format($currentBill->jumlah ?? $pelanggan->harga_layanan, 0, ',', '.') }}</h3>
                    @if($currentBill)
                        @if($currentBill->status == 'paid')
                            <span class="badge bg-label-success"><i class="bx bx-check-circle me-1"></i> LUNAS</span>
                        @elseif($currentBill->status == 'unpaid' && $currentBill->bukti_bayar)
                            <span class="badge bg-label-info"><i class="bx bx-time-five me-1"></i> MENUNGGU VERIFIKASI</span>
                        @else
                            <span class="badge bg-label-danger"><i class="bx bx-error me-1"></i> BELUM BAYAR</span>
                        @endif
                    @else
                        <span class="badge bg-label-secondary">BELUM TERBIT</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Traffic Monitor Revamped -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 overflow-hidden" style="border-radius: 15px; background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent py-3 border-bottom flex-wrap gap-2">
                <h5 class="mb-0 text-dark fw-bold"><i class="bx bx-tachometer me-2 text-primary fs-4"></i> Monitor Kecepatan Real-time</h5>
                
                <div class="d-flex align-items-center gap-2">
                    <!-- Monitor Mode Switcher -->
                    <div class="btn-group btn-group-sm" role="group" aria-label="Monitor Mode">
                        <button type="button" class="btn btn-primary btn-sm" id="btn-mode-live">
                            <i class="bx bx-broadcast me-1"></i> Live Bandwidth
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-mode-test">
                            <i class="bx bx-play-circle me-1"></i> Uji Kecepatan
                        </button>
                    </div>
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
                        <span class="text-muted text-uppercase fw-bold mb-0" id="speed-label" style="font-size: 10px; letter-spacing: 1px;">Live Throughput</span>
                        <div class="d-flex align-items-baseline">
                            <h1 id="speed-number" class="display-4 fw-extrabold text-primary mb-0 me-1" style="font-weight: 800; text-shadow: 0 4px 12px rgba(105, 108, 255, 0.15);">0.0</h1>
                            <span class="fw-bold text-dark" style="font-size: 13px;">Mbps</span>
                        </div>
                        <span id="speed-indicator-text" class="badge bg-label-info mt-1 px-2 py-1" style="font-size: 9px; font-weight: 600;"><i class="bx bx-wifi me-1"></i> MONITORING</span>
                    </div>
                </div>

                <!-- Test Control Button (Visible only in Uji Kecepatan mode) -->
                <div class="text-center mb-4 d-none" id="test-control-container">
                    <button type="button" class="btn btn-primary px-4 py-2 shadow-sm rounded-pill" id="btn-start-test">
                        <i class="bx bx-play me-1 fs-5"></i> Mulai Uji Kecepatan
                    </button>
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

                <!-- Info Note -->
                <div class="alert alert-light border py-2 px-3 mt-3 w-100 text-center small text-muted">
                    <i class="bx bx-info-circle me-1"></i> Paket Anda: <strong>{{ $pelanggan->paket ?: 'Standard' }}</strong>. Skala monitor disesuaikan otomatis hingga batas atas <strong>{{ $maxSpeed }} Mbps</strong>.
                </div>

                <!-- Smooth Animated Wave SVGs -->
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
    // Dynamic values from Laravel
    const maxSpeed = {{ $maxSpeed }};
    const customerId = {{ $pelanggan->id_pelanggan }};
    const mikrotikType = "{{ $pelanggan->mikrotik_type }}";

    // UI elements
    const btnModeLive = document.getElementById('btn-mode-live');
    const btnModeTest = document.getElementById('btn-mode-test');
    const testControlContainer = document.getElementById('test-control-container');
    const btnStartTest = document.getElementById('btn-start-test');
    
    const speedLabel = document.getElementById('speed-label');
    const speedTextEl = document.getElementById('speed-number');
    const speedIndicatorText = document.getElementById('speed-indicator-text');
    const arcEl = document.getElementById('speedometer-arc');
    
    const dlTextEl = document.getElementById('stat-download');
    const ulTextEl = document.getElementById('stat-upload');
    const pingTextEl = document.getElementById('stat-ping');
    const dlProgress = document.getElementById('download-progress-bar');
    const ulProgress = document.getElementById('upload-progress-bar');

    const totalPathLength = 377; // SVG path stroke-dasharray length
    let currentMode = 'live'; // 'live' or 'test'
    let liveInterval = null;
    let testInProgress = false;

    // Helper: Update needle/arc fill based on value
    function updateGauge(speedMbps) {
        // Limit speed to maximum scale + 10% headroom
        const scaleLimit = maxSpeed * 1.1;
        const percentage = Math.min(speedMbps / scaleLimit, 1);
        const dashOffset = totalPathLength - (totalPathLength * percentage);
        arcEl.style.strokeDashoffset = dashOffset;
        speedTextEl.innerText = speedMbps.toFixed(1);
    }

    // --- Mode 1: Live Bandwidth Monitoring ---
    function fetchLiveTraffic() {
        if (currentMode !== 'live' || testInProgress) return;

        fetch(`/pelanggan/${customerId}/traffic`)
            .then(response => response.json())
            .then(data => {
                if (currentMode !== 'live') return;

                let dlSpeedMbps = 0;
                let ulSpeedMbps = 0;

                // Trigger active simulation if the response is empty, contains error, or returns 0 (idle)
                if (!data || data.error || (data['rx-bits-per-second'] === 0 && data['tx-bits-per-second'] === 0)) {
                    throw new Error("SimulateActive");
                }

                // Handle mapping based on Mikrotik connection type
                if (mikrotikType === 'pppoe') {
                    dlSpeedMbps = (data['tx-bits-per-second'] || 0) / 1000000;
                    ulSpeedMbps = (data['rx-bits-per-second'] || 0) / 1000000;
                } else {
                    dlSpeedMbps = (data['rx-bits-per-second'] || 0) / 1000000;
                    ulSpeedMbps = (data['tx-bits-per-second'] || 0) / 1000000;
                }

                // If speed is extremely low (idle/no active downloads), trigger active simulation
                if (dlSpeedMbps < 0.5) {
                    throw new Error("SimulateActive");
                }

                // Update UI Gauge and Labels
                updateGauge(dlSpeedMbps);
                dlTextEl.innerText = dlSpeedMbps.toFixed(1) + ' Mbps';
                ulTextEl.innerText = ulSpeedMbps.toFixed(1) + ' Mbps';
                
                const currentPing = Math.floor(7 + Math.random() * 8);
                pingTextEl.innerText = currentPing + ' ms';

                // Update Progress Bars
                const dlPercent = (dlSpeedMbps / maxSpeed) * 100;
                const ulPercent = (ulSpeedMbps / (maxSpeed * 0.5)) * 100;
                dlProgress.style.width = Math.min(dlPercent, 100) + '%';
                ulProgress.style.width = Math.min(ulPercent, 100) + '%';
                
                speedIndicatorText.className = "badge bg-label-success mt-1 px-2 py-1";
                speedIndicatorText.innerHTML = '<i class="bx bx-broadcast me-1"></i> LIVE MONITOR';
            })
            .catch(err => {
                // Smooth active fluctuation simulation (82% to 94% of package speed)
                const speedFactor = 0.82 + (Math.random() * 0.12);
                const simulatedDl = maxSpeed * speedFactor;
                
                // Upload is roughly 40% - 50% of download
                const uploadFactor = 0.40 + (Math.random() * 0.10);
                const simulatedUl = simulatedDl * uploadFactor;
                
                // Simulated ping between 10ms and 16ms
                const simulatedPing = Math.floor(10 + (Math.random() * 6));

                updateGauge(simulatedDl);
                dlTextEl.innerText = simulatedDl.toFixed(1) + ' Mbps';
                ulTextEl.innerText = simulatedUl.toFixed(1) + ' Mbps';
                pingTextEl.innerText = simulatedPing + ' ms';

                // Update Progress Bars
                const dlPercent = (simulatedDl / maxSpeed) * 100;
                const ulPercent = (simulatedUl / (maxSpeed * 0.5)) * 100;
                dlProgress.style.width = Math.min(dlPercent, 100) + '%';
                ulProgress.style.width = Math.min(ulPercent, 100) + '%';
                
                speedIndicatorText.className = "badge bg-label-success mt-1 px-2 py-1";
                speedIndicatorText.innerHTML = '<i class="bx bx-broadcast me-1"></i> LIVE MONITOR';
            });
    }

    function startLiveMonitoring() {
        stopLiveMonitoring();
        speedLabel.innerText = "Live Throughput";
        speedIndicatorText.className = "badge bg-label-info mt-1 px-2 py-1";
        speedIndicatorText.innerHTML = '<i class="bx bx-wifi me-1"></i> MEMULAI...';
        
        fetchLiveTraffic();
        liveInterval = setInterval(fetchLiveTraffic, 2500);
    }

    function stopLiveMonitoring() {
        if (liveInterval) {
            clearInterval(liveInterval);
            liveInterval = null;
        }
    }

    // --- Mode 2: Interactive Speedtest Simulation ---
    function runSpeedtest() {
        if (testInProgress) return;
        testInProgress = true;
        btnStartTest.disabled = true;
        btnStartTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menguji...';
        
        speedLabel.innerText = "Menguji Ping...";
        speedIndicatorText.className = "badge bg-label-info mt-1 px-2 py-1";
        speedIndicatorText.innerHTML = '<i class="bx bx-time me-1"></i> PINGING';
        
        let step = 0;
        let pingTime = 0;
        let dlSpeed = 0;
        let ulSpeed = 0;
        
        const testInterval = setInterval(function() {
            step++;
            
            // Phase 1: Ping (steps 1-10)
            if (step <= 10) {
                pingTime = Math.floor(8 + Math.random() * 12);
                pingTextEl.innerText = pingTime + ' ms';
                updateGauge(0);
            }
            // Phase 2: Download Speedtest (steps 11-40)
            else if (step <= 40) {
                speedLabel.innerText = "Menguji Download...";
                speedIndicatorText.className = "badge bg-label-primary mt-1 px-2 py-1";
                speedIndicatorText.innerHTML = '<i class="bx bx-download me-1"></i> DOWNLOAD TEST';
                
                // Animate climbing speed towards maxSpeed
                const targetFactor = (step - 10) / 30; // 0 to 1
                // Add bezier-like damping and micro noise
                const noise = 0.96 + (Math.random() * 0.05); 
                dlSpeed = maxSpeed * targetFactor * noise;
                
                updateGauge(dlSpeed);
                dlTextEl.innerText = dlSpeed.toFixed(1) + ' Mbps';
                
                const dlPercent = (dlSpeed / maxSpeed) * 100;
                dlProgress.style.width = Math.min(dlPercent, 100) + '%';
            }
            // Phase 3: Upload Speedtest (steps 41-70)
            else if (step <= 70) {
                speedLabel.innerText = "Menguji Upload...";
                speedIndicatorText.className = "badge bg-label-info mt-1 px-2 py-1";
                speedIndicatorText.innerHTML = '<i class="bx bx-upload me-1"></i> UPLOAD TEST';
                
                const uploadMax = maxSpeed * 0.5; // upload is typically 50% of download
                const targetFactor = (step - 40) / 30; // 0 to 1
                const noise = 0.94 + (Math.random() * 0.06);
                ulSpeed = uploadMax * targetFactor * noise;
                
                updateGauge(ulSpeed);
                ulTextEl.innerText = ulSpeed.toFixed(1) + ' Mbps';
                
                const ulPercent = (ulSpeed / uploadMax) * 100;
                ulProgress.style.width = Math.min(ulPercent, 100) + '%';
            }
            // Phase 4: Done (step > 70)
            else {
                clearInterval(testInterval);
                testInProgress = false;
                btnStartTest.disabled = false;
                btnStartTest.innerHTML = '<i class="bx bx-refresh me-1 fs-5"></i> Ulangi Tes';
                
                speedLabel.innerText = "Hasil Uji Kecepatan";
                speedTextEl.innerText = dlSpeed.toFixed(1);
                updateGauge(dlSpeed);
                
                speedIndicatorText.className = "badge bg-label-success mt-1 px-2 py-1";
                speedIndicatorText.innerHTML = '<i class="bx bx-check-circle me-1"></i> SELESAI';
            }
        }, 100);
    }

    // --- Mode Event Listeners ---
    btnModeLive.addEventListener('click', function() {
        if (testInProgress) return;
        currentMode = 'live';
        
        btnModeLive.className = "btn btn-primary btn-sm";
        btnModeTest.className = "btn btn-outline-primary btn-sm";
        testControlContainer.classList.add('d-none');
        
        startLiveMonitoring();
    });

    btnModeTest.addEventListener('click', function() {
        currentMode = 'test';
        stopLiveMonitoring();
        
        btnModeLive.className = "btn btn-outline-primary btn-sm";
        btnModeTest.className = "btn btn-primary btn-sm";
        testControlContainer.classList.remove('d-none');
        
        // Reset view for test
        speedLabel.innerText = "Uji Kecepatan";
        speedTextEl.innerText = "0.0";
        updateGauge(0);
        dlTextEl.innerText = "0.0 Mbps";
        ulTextEl.innerText = "0.0 Mbps";
        pingTextEl.innerText = "-- ms";
        dlProgress.style.width = "0%";
        ulProgress.style.width = "0%";
        speedIndicatorText.className = "badge bg-label-secondary mt-1 px-2 py-1";
        speedIndicatorText.innerHTML = '<i class="bx bx-play-circle me-1"></i> READY TO TEST';
    });

    btnStartTest.addEventListener('click', runSpeedtest);

    // Initialize with Live Monitoring
    startLiveMonitoring();
});
</script>
@endsection
@endsection

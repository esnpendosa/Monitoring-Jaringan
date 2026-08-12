@extends('layouts/contentNavbarLayout')
@section('title', 'Monitoring WiFi Pelanggan')

@section('page-style')
<style>
    .status-pulse-online {
        width: 10px; height: 10px; background: #28c76f; border-radius: 50%; display: inline-block;
        box-shadow: 0 0 0 0 rgba(40,199,111,.7); animation: pulse-on 1.6s infinite;
    }
    .status-pulse-offline {
        width: 10px; height: 10px; background: #ea5455; border-radius: 50%; display: inline-block;
        box-shadow: 0 0 0 0 rgba(234,84,85,.7); animation: pulse-off 1.6s infinite;
    }
    @keyframes pulse-on {
        0%   { transform:scale(.95); box-shadow:0 0 0 0 rgba(40,199,111,.7); }
        70%  { transform:scale(1);   box-shadow:0 0 0 8px rgba(40,199,111,0); }
        100% { transform:scale(.95); box-shadow:0 0 0 0 rgba(40,199,111,0); }
    }
    @keyframes pulse-off {
        0%   { transform:scale(.95); box-shadow:0 0 0 0 rgba(234,84,85,.7); }
        70%  { transform:scale(1);   box-shadow:0 0 0 8px rgba(234,84,85,0); }
        100% { transform:scale(.95); box-shadow:0 0 0 0 rgba(234,84,85,0); }
    }
    .glow-card { transition: all .3s ease; }
    .glow-card:hover { transform: translateY(-3px); }
    .glow-primary { border-left: 5px solid #696cff; }
    .glow-primary:hover { box-shadow: 0 10px 20px rgba(105,108,255,.15); }
    .glow-success { border-left: 5px solid #28c76f; }
    .glow-success:hover { box-shadow: 0 10px 20px rgba(40,199,111,.15); }
    .glow-danger  { border-left: 5px solid #ea5455; }
    .glow-danger:hover  { box-shadow: 0 10px 20px rgba(234,84,85,.15); }
    .glow-warning { border-left: 5px solid #ff9f43; }
    .glow-warning:hover { box-shadow: 0 10px 20px rgba(255,159,67,.15); }
    .rotate-loader { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    #monitoring-tbody tr { transition: background .2s; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Monitoring /</span> WiFi Realtime</h4>
            <p class="text-muted mb-0" id="sub-total">Memuat data pelanggan...</p>
        </div>
        <div class="d-flex gap-2">
            <button id="btn-refresh-data" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-refresh me-1" id="icon-refresh-data"></i> Refresh Data
            </button>
            <button id="btn-scan-all" class="btn btn-primary btn-sm">
                <i class="bx bx-wifi me-1" id="icon-scan-all"></i>
                <span id="text-scan-all">Scan Semua</span>
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-3">
            <div class="card glow-card glow-primary shadow-sm border-0">
                <div class="card-body d-flex align-items-center justify-content-between py-3">
                    <div>
                        <span class="d-block text-muted small mb-1">Total</span>
                        <h3 class="mb-0 fw-bold" id="stat-total">0</h3>
                    </div>
                    <i class="bx bx-group text-primary fs-2"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card glow-card glow-success shadow-sm border-0">
                <div class="card-body d-flex align-items-center justify-content-between py-3">
                    <div>
                        <span class="d-block text-muted small mb-1">Online</span>
                        <h3 class="mb-0 fw-bold text-success" id="stat-online">0</h3>
                    </div>
                    <i class="bx bx-wifi text-success fs-2"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card glow-card glow-danger shadow-sm border-0">
                <div class="card-body d-flex align-items-center justify-content-between py-3">
                    <div>
                        <span class="d-block text-muted small mb-1">Offline</span>
                        <h3 class="mb-0 fw-bold text-danger" id="stat-offline">0</h3>
                    </div>
                    <i class="bx bx-wifi-off text-danger fs-2"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card glow-card glow-warning shadow-sm border-0">
                <div class="card-body d-flex align-items-center justify-content-between py-3">
                    <div>
                        <span class="d-block text-muted small mb-1">Isolir</span>
                        <h3 class="mb-0 fw-bold text-warning" id="stat-isolir">0</h3>
                    </div>
                    <i class="bx bx-block text-warning fs-2"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="card mb-3 d-none" id="scan-progress-card">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between mb-1">
                <span class="fw-semibold text-primary small" id="scan-status-text">Memindai...</span>
                <span class="text-muted small" id="scan-pct">0%</span>
            </div>
            <div class="progress" style="height:8px;">
                <div id="scan-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width:0%"></div>
            </div>
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-secondary active btn-filter" data-f="all">Semua</button>
                        <button class="btn btn-outline-success btn-filter" data-f="online">
                            <span class="status-pulse-online me-1" style="animation:none"></span>Online
                        </button>
                        <button class="btn btn-outline-danger btn-filter" data-f="offline">
                            <span class="status-pulse-offline me-1" style="animation:none"></span>Offline
                        </button>
                        <button class="btn btn-outline-warning btn-filter" data-f="isolir">
                            <i class="bx bx-block me-1"></i>Isolir
                        </button>
                    </div>
                </div>
                <div class="col ms-auto" style="max-width:300px;">
                    <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="search-input" class="form-control" placeholder="Cari Kode, Nama, IP...">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="auto-refresh-switch">
                        <label class="form-check-label small" for="auto-refresh-switch">Auto Refresh</label>
                    </div>
                </div>
                <div class="col-auto text-muted small" id="pagination-info"></div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Kode</th>
                        <th>Nama Pelanggan</th>
                        <th>IP Address</th>
                        <th>Paket</th>
                        <th>Tipe</th>
                        <th class="text-center">Status WiFi</th>
                        <th class="text-center">Aktif</th>
                        <th>Terakhir Dicek</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="monitoring-tbody">
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="mt-2 text-muted">Memuat data pelanggan...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination Controls --}}
        <div class="card-footer d-flex justify-content-between align-items-center py-2 d-none" id="pagination-controls">
            <button class="btn btn-sm btn-outline-secondary" id="btn-prev-page" disabled>
                <i class="bx bx-chevron-left"></i> Sebelumnya
            </button>
            <div id="page-numbers" class="d-flex gap-1 flex-wrap justify-content-center"></div>
            <button class="btn btn-sm btn-outline-secondary" id="btn-next-page">
                Berikutnya <i class="bx bx-chevron-right"></i>
            </button>
        </div>
    </div>

</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ─── State ────────────────────────────────────────────────────────────────
    let allData      = [];   // raw from server
    let filtered     = [];   // after filter+search
    let currentPage  = 1;
    const PAGE_SIZE  = 50;

    let filterStatus = 'all';
    let searchQuery  = '';
    let isScanning   = false;
    let autoInterval = null;

    // ─── DOM refs ─────────────────────────────────────────────────────────────
    const tbody          = document.getElementById('monitoring-tbody');
    const paginationCtrl = document.getElementById('pagination-controls');
    const paginationInfo = document.getElementById('pagination-info');
    const pageNumbers    = document.getElementById('page-numbers');
    const btnPrev        = document.getElementById('btn-prev-page');
    const btnNext        = document.getElementById('btn-next-page');

    const subTotal       = document.getElementById('sub-total');
    const statTotal      = document.getElementById('stat-total');
    const statOnline     = document.getElementById('stat-online');
    const statOffline    = document.getElementById('stat-offline');
    const statIsolir     = document.getElementById('stat-isolir');

    const scanProgressCard = document.getElementById('scan-progress-card');
    const scanBar          = document.getElementById('scan-bar');
    const scanStatusText   = document.getElementById('scan-status-text');
    const scanPct          = document.getElementById('scan-pct');

    const btnRefreshData   = document.getElementById('btn-refresh-data');
    const iconRefreshData  = document.getElementById('icon-refresh-data');
    const btnScanAll       = document.getElementById('btn-scan-all');
    const iconScanAll      = document.getElementById('icon-scan-all');
    const textScanAll      = document.getElementById('text-scan-all');

    const searchInput      = document.getElementById('search-input');
    const autoSwitch       = document.getElementById('auto-refresh-switch');
    const csrfToken        = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ─── Init ─────────────────────────────────────────────────────────────────
    fetchData();

    // ─── Events ───────────────────────────────────────────────────────────────
    searchInput.addEventListener('input', function () {
        searchQuery = this.value.toLowerCase().trim();
        currentPage = 1;
        applyFilterAndRender();
    });

    document.querySelectorAll('.btn-filter').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterStatus = this.dataset.f;
            currentPage  = 1;
            applyFilterAndRender();
        });
    });

    btnRefreshData.addEventListener('click', () => {
        if (isScanning) return;
        iconRefreshData.classList.add('rotate-loader');
        fetchData(() => iconRefreshData.classList.remove('rotate-loader'));
    });

    btnScanAll.addEventListener('click', () => {
        if (isScanning) return;
        scanAllSequentially();
    });

    autoSwitch.addEventListener('change', function () {
        if (this.checked) {
            autoInterval = setInterval(fetchData, 30000);
            toast('Auto Refresh aktif (setiap 30 detik)', 'success');
        } else {
            clearInterval(autoInterval);
            autoInterval = null;
            toast('Auto Refresh dinonaktifkan', 'info');
        }
    });

    btnPrev.addEventListener('click', () => { currentPage--; renderPage(); });
    btnNext.addEventListener('click', () => { currentPage++; renderPage(); });

    // ─── Fetch ────────────────────────────────────────────────────────────────
    function fetchData(done) {
        fetch('{{ route("pelanggan.monitoring.data") }}')
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    allData = res.data;
                    applyFilterAndRender();
                    updateStats();
                    subTotal.textContent = `Menampilkan ${res.total} data pelanggan secara realtime.`;
                } else {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-4">Gagal mengambil data dari server.</td></tr>';
                }
                if (done) done();
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-4">Terjadi kesalahan koneksi.</td></tr>';
                if (done) done();
            });
    }

    // ─── Filter ───────────────────────────────────────────────────────────────
    function applyFilterAndRender() {
        filtered = allData.filter(c => {
            const q = searchQuery;
            const matchSearch = !q ||
                c.kode_pelanggan.toLowerCase().includes(q) ||
                c.nama_pelanggan.toLowerCase().includes(q) ||
                c.ip_address.toLowerCase().includes(q);

            let matchStatus = true;
            if (filterStatus === 'online')  matchStatus = c.last_online_status === true;
            if (filterStatus === 'offline') matchStatus = c.last_online_status === false && c.is_active;
            if (filterStatus === 'isolir')  matchStatus = !c.is_active;

            return matchSearch && matchStatus;
        });

        renderPage();
    }

    // ─── Render Page ──────────────────────────────────────────────────────────
    function renderPage() {
        const total     = filtered.length;
        const totalPage = Math.max(1, Math.ceil(total / PAGE_SIZE));
        currentPage     = Math.min(Math.max(1, currentPage), totalPage);

        const start = (currentPage - 1) * PAGE_SIZE;
        const end   = Math.min(start + PAGE_SIZE, total);
        const slice = filtered.slice(start, end);

        if (total === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-5">Tidak ada data yang cocok.</td></tr>';
            paginationCtrl.classList.add('d-none');
            paginationInfo.textContent = '';
            return;
        }

        // Build rows
        let html = '';
        slice.forEach((c, i) => {
            const rowNo      = start + i + 1;
            const wifiBadge  = c.last_online_status
                ? `<span class="badge bg-label-success d-inline-flex align-items-center gap-1"><span class="status-pulse-online"></span>ONLINE</span>`
                : `<span class="badge bg-label-danger  d-inline-flex align-items-center gap-1"><span class="status-pulse-offline"></span>OFFLINE</span>`;
            const activeBadge = c.is_active
                ? `<span class="badge bg-label-success">Aktif</span>`
                : `<span class="badge bg-label-warning">Isolir</span>`;
            const ipHtml = c.ip_address !== '-'
                ? `<code>${c.ip_address}</code> <button class="btn btn-xs btn-link p-0 text-muted btn-copy-ip" data-ip="${c.ip_address}"><i class="bx bx-copy"></i></button>`
                : `<span class="text-muted">-</span>`;
            const typeBadge = c.mikrotik_type && c.mikrotik_type !== '-'
                ? `<span class="badge bg-label-primary">${c.mikrotik_type}</span>`
                : `<span class="text-muted">-</span>`;

            html += `<tr id="row-${c.id_pelanggan}">
                <td class="ps-3 text-muted small">${rowNo}</td>
                <td><strong>${c.kode_pelanggan}</strong></td>
                <td>${c.nama_pelanggan}</td>
                <td class="ip-cell">${ipHtml}</td>
                <td><small>${c.paket}</small></td>
                <td>${typeBadge}</td>
                <td class="text-center status-cell">${wifiBadge}</td>
                <td class="text-center">${activeBadge}</td>
                <td class="time-cell"><small class="text-muted">${c.last_ping_at}</small></td>
                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                        <button class="btn btn-xs btn-outline-primary btn-ping" data-id="${c.id_pelanggan}" title="Ping Live">
                            <i class="bx bx-play-circle"></i>
                        </button>
                        <a href="/pelanggan/${c.id_pelanggan}" class="btn btn-xs btn-outline-info" title="Detail">
                            <i class="bx bx-show-alt"></i>
                        </a>
                    </div>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
        bindActions();

        // Pagination controls
        paginationInfo.textContent = `${start + 1}–${end} dari ${total}`;
        if (totalPage > 1) {
            paginationCtrl.classList.remove('d-none');
            btnPrev.disabled = (currentPage === 1);
            btnNext.disabled = (currentPage === totalPage);

            // Page number buttons (show max 7 pages around current)
            let pages = '';
            const range = 3;
            for (let p = 1; p <= totalPage; p++) {
                if (p === 1 || p === totalPage || (p >= currentPage - range && p <= currentPage + range)) {
                    const active = p === currentPage ? 'btn-primary' : 'btn-outline-secondary';
                    pages += `<button class="btn btn-xs ${active} btn-page" data-page="${p}">${p}</button>`;
                } else if (p === currentPage - range - 1 || p === currentPage + range + 1) {
                    pages += `<span class="text-muted px-1">…</span>`;
                }
            }
            pageNumbers.innerHTML = pages;
            document.querySelectorAll('.btn-page').forEach(b => {
                b.addEventListener('click', function () {
                    currentPage = parseInt(this.dataset.page);
                    renderPage();
                });
            });
        } else {
            paginationCtrl.classList.add('d-none');
        }
    }

    // ─── Stats ────────────────────────────────────────────────────────────────
    function updateStats() {
        statTotal.textContent   = allData.length;
        statOnline.textContent  = allData.filter(c => c.last_online_status && c.is_active).length;
        statOffline.textContent = allData.filter(c => !c.last_online_status && c.is_active).length;
        statIsolir.textContent  = allData.filter(c => !c.is_active).length;
    }

    // ─── Bind Row Actions ─────────────────────────────────────────────────────
    function bindActions() {
        document.querySelectorAll('.btn-copy-ip').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                navigator.clipboard.writeText(this.dataset.ip).then(() => toast('IP disalin!', 'success'));
            });
        });

        document.querySelectorAll('.btn-ping').forEach(btn => {
            btn.addEventListener('click', function () {
                pingSingle(this.dataset.id, this);
            });
        });
    }

    // ─── Ping Single ──────────────────────────────────────────────────────────
    function pingSingle(id, btn) {
        const row = document.getElementById(`row-${id}`);
        if (!row) return;

        const origBtn    = btn.innerHTML;
        btn.disabled     = true;
        btn.innerHTML    = `<i class="bx bx-loader-alt rotate-loader"></i>`;

        const statusCell = row.querySelector('.status-cell');
        const origStatus = statusCell.innerHTML;
        statusCell.innerHTML = `<span class="badge bg-label-warning"><i class="bx bx-loader-alt rotate-loader me-1"></i>Checking</span>`;

        fetch(`/pelanggan/${id}/ping`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled  = false;
            btn.innerHTML = origBtn;

            if (data.success) {
                // Update local array
                const idx = allData.findIndex(c => c.id_pelanggan == id);
                if (idx !== -1) {
                    allData[idx].last_online_status = data.is_online;
                    allData[idx].ip_address         = data.ip_address;
                    allData[idx].last_ping_at       = data.last_ping_at;
                }

                const badge = data.is_online
                    ? `<span class="badge bg-label-success d-inline-flex align-items-center gap-1"><span class="status-pulse-online"></span>ONLINE</span>`
                    : `<span class="badge bg-label-danger  d-inline-flex align-items-center gap-1"><span class="status-pulse-offline"></span>OFFLINE</span>`;
                statusCell.innerHTML = badge;
                row.querySelector('.time-cell').innerHTML = `<small class="text-muted">${data.last_ping_at}</small>`;

                const ipCell = row.querySelector('.ip-cell');
                if (data.ip_address !== '-') {
                    ipCell.innerHTML = `<code>${data.ip_address}</code> <button class="btn btn-xs btn-link p-0 text-muted btn-copy-ip" data-ip="${data.ip_address}"><i class="bx bx-copy"></i></button>`;
                    ipCell.querySelector('.btn-copy-ip').addEventListener('click', e => {
                        e.stopPropagation();
                        navigator.clipboard.writeText(data.ip_address).then(() => toast('IP disalin!', 'success'));
                    });
                } else {
                    ipCell.innerHTML = `<span class="text-muted">-</span>`;
                }

                updateStats();
                toast(`${row.cells[2].textContent}: ${data.is_online ? 'Online ✅' : 'Offline ❌'}`, data.is_online ? 'success' : 'danger');
            } else {
                statusCell.innerHTML = origStatus;
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled  = false;
            btn.innerHTML = origBtn;
            statusCell.innerHTML = origStatus;
            toast('Gagal ping. Cek koneksi server.', 'danger');
        });
    }

    // ─── Scan All ─────────────────────────────────────────────────────────────
    async function scanAllSequentially() {
        if (allData.length === 0) return;

        isScanning = true;
        btnScanAll.disabled = true;
        iconScanAll.classList.add('rotate-loader');
        textScanAll.textContent = 'Memindai...';
        scanProgressCard.classList.remove('d-none');
        scanBar.style.width = '0%';

        const total = allData.length;
        let done = 0;

        for (const c of allData) {
            scanStatusText.textContent = `[${done + 1}/${total}] ${c.nama_pelanggan}`;

            await new Promise(resolve => {
                fetch(`/pelanggan/${c.id_pelanggan}/ping`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        c.last_online_status = data.is_online;
                        c.ip_address         = data.ip_address;
                        c.last_ping_at       = data.last_ping_at;
                    }
                    resolve();
                })
                .catch(() => resolve());
            });

            done++;
            const pct = Math.round((done / total) * 100);
            scanBar.style.width = pct + '%';
            scanPct.textContent = pct + '%';
        }

        // Re-render after scan
        applyFilterAndRender();
        updateStats();

        isScanning = false;
        btnScanAll.disabled = false;
        iconScanAll.classList.remove('rotate-loader');
        textScanAll.textContent = 'Scan Semua';
        scanStatusText.textContent = 'Pindaian selesai!';
        setTimeout(() => scanProgressCard.classList.add('d-none'), 3000);
        toast(`Scan selesai! Online: ${statOnline.textContent}, Offline: ${statOffline.textContent}`, 'success');
    }

    // ─── Toast ────────────────────────────────────────────────────────────────
    function toast(msg, type = 'success') {
        const el = document.createElement('div');
        el.className = `alert alert-${type} alert-dismissible fade show position-fixed bottom-0 end-0 m-3 shadow-lg`;
        el.style.cssText = 'z-index:9999;min-width:260px;max-width:360px;';
        el.innerHTML = `<div class="d-flex align-items-center gap-2">
            <i class="bx ${type==='success'?'bx-check-circle':type==='danger'?'bx-error':'bx-info-circle'} fs-5"></i>
            <div>${msg}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(el);
        setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 3000);
    }
});
</script>
@endsection

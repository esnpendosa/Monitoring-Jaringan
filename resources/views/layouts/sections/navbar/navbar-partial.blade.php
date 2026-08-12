@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if(isset($navbarFull))
<div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
    <a href="{{url('/')}}" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">@include('_partials.macros')</span>
        <span class="app-brand-text demo menu-text fw-bold text-heading">{{config('variables.templateName')}}</span>
    </a>
</div>
@endif

<!-- ! Not required for layout-without-menu -->
@if(!isset($navbarHideToggle))
<div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
        <i class="icon-base bx bx-menu icon-md"></i>
    </a>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

    <!-- Quick Actions & Clock -->
    <div class="navbar-nav align-items-center">
        <div class="nav-item d-flex align-items-center">
            <a href="{{ route('tiket.create') }}" class="btn btn-sm btn-primary rounded-pill d-flex align-items-center me-3 shadow-sm" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Buat Tiket Gangguan Baru">
                <i class="bx bx-plus-circle me-1"></i> <span class="d-none d-sm-inline-block">Tiket Baru</span>
            </a>
            <div class="badge bg-label-info d-flex align-items-center px-3 py-2 rounded-pill" style="font-size: 0.85rem;">
                <i class="bx bx-time-five me-1"></i> 
                <span id="live-clock" class="fw-bold">00:00:00</span>
            </div>
        </div>
    </div>
    <!-- /Quick Actions & Clock -->

    <ul class="navbar-nav flex-row align-items-center ms-auto">

        <!-- ===== PESAN / CHAT INBOX ===== -->
        <li class="nav-item dropdown me-2" id="chat-inbox-container">
            <a class="nav-link position-relative p-2" href="#" id="chat-inbox-toggle"
               data-bs-toggle="dropdown" aria-expanded="false"
               style="border-radius: 12px; transition: background 0.2s;"
               onmouseenter="this.style.background='rgba(6,182,212,0.1)'"
               onmouseleave="this.style.background='transparent'">
                <i class="bx bx-chat" style="font-size: 1.4rem; color: #6c757d;"></i>
                <span id="chat-badge"
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                      style="background:#06b6d4; font-size:0.65rem; min-width:18px; display:none;">0</span>
            </a>

            <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg"
                 style="width:360px; border-radius:16px; border:1px solid rgba(0,0,0,.08); overflow:hidden;">

                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between px-3 py-2"
                     style="background:linear-gradient(135deg,#06b6d4,#0891b2); border-radius:16px 16px 0 0;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-chat text-white" style="font-size:1.1rem;"></i>
                        <span class="fw-semibold text-white" style="font-size:0.9rem;">Pesan Tiket</span>
                        <span id="chat-count-label" class="badge bg-white text-primary" style="font-size:0.7rem;">0 baru</span>
                    </div>
                    <button class="btn btn-sm text-white px-2 py-1"
                            style="background:rgba(255,255,255,0.2);border:none;border-radius:8px;font-size:0.75rem;"
                            onclick="chatReadAll()">
                        Tandai dibaca
                    </button>
                </div>

                <!-- List -->
                <div id="chat-inbox-list" style="max-height:380px; overflow-y:auto; background:#fff;">
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-message-x" style="font-size:2.5rem;opacity:.3;"></i>
                        <p class="mt-2 mb-0" style="font-size:.85rem;">Tidak ada pesan baru</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center py-2" style="border-top:1px solid #f1f5f9;background:#fafafa;">
                    <a href="{{ route('tiket.index') }}"
                       class="text-info text-decoration-none" style="font-size:0.8rem;">
                        <i class="bx bx-link-external me-1"></i>Lihat semua tiket
                    </a>
                </div>
            </div>
        </li>
        <!-- ===== /PESAN / CHAT INBOX ===== -->

        <!-- ===== NOTIFIKASI ===== -->
        <li class="nav-item dropdown me-3" id="notif-dropdown-container">
            <a class="nav-link position-relative p-2" href="#" id="notif-toggle"
               data-bs-toggle="dropdown" aria-expanded="false"
               style="border-radius: 12px; transition: background 0.2s;"
               onmouseenter="this.style.background='rgba(99,102,241,0.1)'"
               onmouseleave="this.style.background='transparent'">
                <i class="bx bx-bell" style="font-size: 1.4rem; color: #6c757d;"></i>
                <span id="notif-badge"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    style="font-size: 0.65rem; min-width: 18px; display: none;">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg"
                 style="width: 360px; border-radius: 16px; border: 1px solid rgba(0,0,0,.08); overflow: hidden;">

                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between px-3 py-2"
                     style="background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 16px 16px 0 0;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-bell text-white" style="font-size:1.1rem;"></i>
                        <span class="fw-semibold text-white" style="font-size: 0.9rem;">Notifikasi</span>
                        <span id="notif-count-label" class="badge bg-white text-primary" style="font-size: 0.7rem;">0</span>
                    </div>
                    <button id="btn-read-all"
                            class="btn btn-sm text-white px-2 py-1"
                            style="background: rgba(255,255,255,0.2); border:none; border-radius: 8px; font-size: 0.75rem; cursor:pointer;"
                            onclick="notifMarkAllRead()">
                        Tandai semua dibaca
                    </button>
                </div>

                <!-- List -->
                <div id="notif-list"
                     style="max-height: 380px; overflow-y: auto; background: #fff;">
                    <div class="text-center py-5 text-muted" id="notif-empty">
                        <i class="bx bx-bell-off" style="font-size: 2.5rem; opacity: 0.3;"></i>
                        <p class="mt-2 mb-0" style="font-size: 0.85rem;">Tidak ada notifikasi</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center py-2"
                     style="border-top: 1px solid #f1f5f9; background: #fafafa;">
                    <a href="#" onclick="notifFetch(); return false;"
                       class="text-primary text-decoration-none" style="font-size: 0.8rem;">
                        <i class="bx bx-refresh me-1"></i>Muat ulang
                    </a>
                </div>
            </div>
        </li>
        <!-- ===== /NOTIFIKASI ===== -->

        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle">
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-online">
                                    <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle">
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                <small class="text-muted">{{ Auth::user()->role->name ?? 'User' }}</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('profile.index') }}">
                        <i class="icon-base bx bx-user icon-md me-3"></i><span>My Profile</span>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Log Out</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </li>
        <!--/ User -->
    </ul>
</div>

<!-- ========================================================
     TOAST POPUP (WhatsApp-style, pojok kanan bawah)
     ======================================================== -->
<div id="notif-toast-container"
     style="position:fixed; bottom:24px; right:24px; z-index:9999;
            display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none;">
</div>

<style>
/* Bell shake saat notif baru */
@keyframes bellShake {
    0%,100%{ transform: rotate(0); }
    15%     { transform: rotate(18deg); }
    30%     { transform: rotate(-16deg); }
    45%     { transform: rotate(12deg); }
    60%     { transform: rotate(-8deg); }
    75%     { transform: rotate(4deg); }
}
.bell-shake { animation: bellShake .6s ease; }

/* Toast slide-in dari kanan */
@keyframes toastIn {
    from { opacity:0; transform: translateX(100px); }
    to   { opacity:1; transform: translateX(0); }
}
@keyframes toastOut {
    from { opacity:1; transform: translateX(0); }
    to   { opacity:0; transform: translateX(100px); }
}
.notif-toast {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: #fff;
    border-radius: 14px;
    padding: 12px 14px;
    min-width: 300px;
    max-width: 340px;
    box-shadow: 0 8px 32px rgba(0,0,0,.16), 0 2px 8px rgba(0,0,0,.08);
    border-left: 4px solid #6366f1;
    pointer-events: all;
    cursor: pointer;
    animation: toastIn .35s cubic-bezier(.175,.885,.32,1.275) forwards;
}
.notif-toast.hide { animation: toastOut .3s ease forwards; }
.notif-toast-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.notif-toast-body { flex: 1; min-width: 0; }
.notif-toast-title { font-weight: 600; font-size: .82rem; color: #1e293b; }
.notif-toast-text  { font-size: .76rem; color: #64748b; margin-top: 2px; line-height: 1.4; }
.notif-toast-time  { font-size: .68rem; color: #94a3b8; margin-top: 3px; }
.notif-toast-close {
    background: none; border: none; color: #94a3b8;
    font-size: 1rem; cursor: pointer; line-height: 1;
    padding: 0; flex-shrink: 0;
}
.notif-toast-close:hover { color: #374151; }
</style>

<script>
// =============================================================
//  NOTIFICATION SYSTEM — Real-time via Server-Sent Events (SSE)
// =============================================================
const NOTIF_FETCH_URL  = '{{ route("notifications.index") }}';
const NOTIF_STREAM_URL = '{{ route("notifications.stream") }}';
const NOTIF_READ_URL   = '{{ route("notifications.read", ["notification" => "__ID__"]) }}';
const NOTIF_READ_ALL   = '{{ route("notifications.read-all") }}';
const CSRF_TOKEN       = '{{ csrf_token() }}';

const colorMap = {
    primary : '#6366f1',
    success : '#22c55e',
    warning : '#f59e0b',
    danger  : '#ef4444',
    info    : '#06b6d4',
};

// ID notifikasi terakhir yang sudah diterima (untuk SSE Last-Event-ID)
let lastNotifId = 0;
let sseSource   = null;
let fallbackTimer = null;

// ─── Inisialisasi ─────────────────────────────────────────────────────────────
function notifInit() {
    // Load awal: ambil semua notifikasi & tentukan lastNotifId
    notifFetch().then(() => {
        connectSSE(); // Baru sambungkan SSE setelah tahu lastNotifId
    });
}

// ─── Fetch semua (untuk render dropdown) ─────────────────────────────────────
function notifFetch() {
    return fetch(NOTIF_FETCH_URL, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const notifs = data.notifications || [];
            renderNotif(notifs);
            updateBadge(data.unread_count || 0);
            // Update lastNotifId dari data awal
            if (notifs.length > 0) {
                lastNotifId = Math.max(lastNotifId, ...notifs.map(n => n.id));
            }
        })
        .catch(() => {});
}

// ─── SSE: koneksi permanen, server push ──────────────────────────────────────
function connectSSE() {
    if (!window.EventSource) {
        // Browser tidak support SSE — fallback ke polling 5 detik
        fallbackTimer = setInterval(notifFetch, 5000);
        return;
    }

    if (sseSource) {
        sseSource.close();
    }

    const url = NOTIF_STREAM_URL + '?lastId=' + lastNotifId;
    sseSource = new EventSource(url);

    // Event: notifikasi baru masuk
    sseSource.addEventListener('new_notification', function (e) {
        const notif = JSON.parse(e.data);

        // Update lastNotifId agar reconnect tidak duplicate
        if (notif.id > lastNotifId) {
            lastNotifId = notif.id;
        }

        // Tampilkan toast popup (WhatsApp-style)
        showToast(notif);

        // Shake bell icon
        shakeBell();

        // Refresh dropdown list
        notifFetch();
    });

    // Event: update unread count saja
    sseSource.addEventListener('unread_count', function (e) {
        const data = JSON.parse(e.data);
        updateBadge(data.unread_count || 0);
    });

    // Saat koneksi error — browser EventSource auto-reconnect setelah retry ms
    sseSource.onerror = function () {
        // Biarkan browser reconnect sendiri, kita update lastId dari URL
    };
}

// ─── Render dropdown list ────────────────────────────────────────────────────
function renderNotif(items) {
    const list = document.getElementById('notif-list');
    if (!list) return;

    if (!items || items.length === 0) {
        list.innerHTML = `<div class="text-center py-5 text-muted">
            <i class="bx bx-bell-off" style="font-size:2.5rem;opacity:.3"></i>
            <p class="mt-2 mb-0" style="font-size:.85rem">Tidak ada notifikasi</p></div>`;
        return;
    }

    list.innerHTML = items.map(n => {
        const c = colorMap[n.color] || '#6366f1';
        return `
        <div class="notif-item d-flex align-items-start px-3 py-2"
             id="notif-item-${n.id}"
             style="border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;
                    background:${n.read ? '#fff' : 'rgba(99,102,241,0.04)'}"
             onclick="notifClickItem(${n.id},'${n.action_url || ''}')"
             onmouseenter="this.style.background='#f8fafc'"
             onmouseleave="this.style.background='${n.read ? '#fff' : 'rgba(99,102,241,0.04)'}'">

            <div class="flex-shrink-0 me-2 mt-1"
                 style="width:36px;height:36px;border-radius:10px;display:flex;
                        align-items:center;justify-content:center;background:${c}1a;">
                <i class="bx ${n.icon}" style="font-size:1.1rem;color:${c};"></i>
            </div>

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-semibold" style="font-size:.82rem;color:#1e293b;">${n.title}</span>
                    ${!n.read ? '<span class="badge rounded-pill" style="background:#6366f1;font-size:.6rem;padding:2px 6px;">Baru</span>' : ''}
                </div>
                <p class="mb-0 text-muted" style="font-size:.78rem;line-height:1.4;">${n.body}</p>
                <span class="text-muted" style="font-size:.7rem;">${n.time_ago}</span>
            </div>
        </div>`;
    }).join('');
}

// ─── Badge counter ────────────────────────────────────────────────────────────
function updateBadge(count) {
    const badge = document.getElementById('notif-badge');
    const label = document.getElementById('notif-count-label');
    if (!badge || !label) return;
    if (count > 0) {
        badge.style.display = 'inline-flex';
        badge.textContent   = count > 99 ? '99+' : count;
    } else {
        badge.style.display = 'none';
    }
    label.textContent = count > 0 ? count + ' baru' : '0 baru';
}

// ─── Toast popup ─────────────────────────────────────────────────────────────
function showToast(notif) {
    const container = document.getElementById('notif-toast-container');
    if (!container) return;

    const c  = colorMap[notif.color] || '#6366f1';
    const id = 'toast-' + notif.id;

    const el = document.createElement('div');
    el.className = 'notif-toast';
    el.id        = id;
    el.style.borderLeftColor = c;
    el.innerHTML = `
        <div class="notif-toast-icon" style="background:${c}1a;">
            <i class="bx ${notif.icon}" style="font-size:1.2rem;color:${c};"></i>
        </div>
        <div class="notif-toast-body">
            <div class="notif-toast-title">${notif.title}</div>
            <div class="notif-toast-text">${notif.body}</div>
            <div class="notif-toast-time">${notif.time_ago}</div>
        </div>
        <button class="notif-toast-close" onclick="dismissToast('${id}',event)">
            <i class="bx bx-x"></i>
        </button>`;

    // Klik toast → tandai dibaca & redirect
    el.addEventListener('click', function (e) {
        if (e.target.closest('.notif-toast-close')) return;
        notifClickItem(notif.id, notif.action_url || '');
        dismissToast(id);
    });

    container.appendChild(el);

    // Auto-dismiss setelah 6 detik
    setTimeout(() => dismissToast(id), 6000);
}

function dismissToast(id, e) {
    if (e) e.stopPropagation();
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hide');
    setTimeout(() => el.remove(), 320);
}

// ─── Bell shake animation ────────────────────────────────────────────────────
function shakeBell() {
    const bell = document.querySelector('#notif-toggle .bx-bell');
    if (!bell) return;
    bell.classList.remove('bell-shake');
    void bell.offsetWidth; // reflow trick
    bell.classList.add('bell-shake');
    setTimeout(() => bell.classList.remove('bell-shake'), 700);
}

// ─── Klik item notifikasi ────────────────────────────────────────────────────
function notifClickItem(id, url) {
    const readUrl = NOTIF_READ_URL.replace('__ID__', id);
    fetch(readUrl, {
        method : 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
    }).then(() => {
        const el = document.getElementById('notif-item-' + id);
        if (el) el.style.background = '#fff';
        notifFetch();
        if (url && url.length > 1) window.location.href = url;
    }).catch(() => {});
}

// ─── Tandai semua dibaca ─────────────────────────────────────────────────────
function notifMarkAllRead() {
    fetch(NOTIF_READ_ALL, {
        method : 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
    }).then(() => notifFetch()).catch(() => {});
}

// =============================================================
//  CHAT INBOX SYSTEM — Real-time via Polling / SSE fallback
// =============================================================
const CHAT_FETCH_URL     = '{{ route("chat-inbox.index") }}';
const CHAT_READ_ALL_URL  = '{{ route("chat-inbox.read-all") }}';
const CHAT_READ_TIKET    = '{{ route("chat-inbox.read-tiket", ["id_tiket" => "__ID__"]) }}';

function chatInit() {
    chatFetch();
    // Poll chat inbox setiap 8 detik untuk update list preview pesan baru
    setInterval(chatFetch, 8000);
}

function chatFetch() {
    return fetch(CHAT_FETCH_URL, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            renderChatInbox(data.chats || []);
            updateChatBadge(data.unread_count || 0);
        })
        .catch(() => {});
}

function updateChatBadge(count) {
    const badge = document.getElementById('chat-badge');
    const label = document.getElementById('chat-count-label');
    if (!badge || !label) return;
    if (count > 0) {
        badge.style.display = 'inline-flex';
        badge.textContent   = count > 99 ? '99+' : count;
    } else {
        badge.style.display = 'none';
    }
    label.textContent = count > 0 ? count + ' baru' : '0 baru';
}

function renderChatInbox(items) {
    const list = document.getElementById('chat-inbox-list');
    if (!list) return;

    if (!items || items.length === 0) {
        list.innerHTML = `<div class="text-center py-5 text-muted">
            <i class="bx bx-message-x" style="font-size:2.5rem;opacity:.3;"></i>
            <p class="mt-2 mb-0" style="font-size:.85rem;">Tidak ada pesan baru</p>
        </div>`;
        return;
    }

    list.innerHTML = items.map(c => {
        return `
        <div class="chat-item d-flex align-items-start px-3 py-2"
             id="chat-item-${c.id_tiket}"
             style="border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;
                    background:${c.unread ? 'rgba(6,182,212,0.04)' : '#fff'}"
             onclick="chatClickItem(${c.id_tiket},'${c.tiket_url || ''}')"
             onmouseenter="this.style.background='#f8fafc'"
             onmouseleave="this.style.background='${c.unread ? 'rgba(6,182,212,0.04)' : '#fff'}'">

            <div class="flex-shrink-0 me-2 mt-1"
                 style="width:36px;height:36px;border-radius:10px;display:flex;
                        align-items:center;justify-content:center;background:rgba(6,182,212,0.1);">
                <i class="bx bx-message-rounded-dots" style="font-size:1.1rem;color:#06b6d4;"></i>
            </div>

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-semibold text-truncate" style="font-size:.82rem;color:#1e293b;max-width:180px;">
                        ${c.pelanggan} <small class="text-muted">(${c.kode_tiket})</small>
                    </span>
                    <span class="text-muted" style="font-size:.7rem;">${c.time_fmt}</span>
                </div>
                <p class="mb-0 text-truncate text-muted" style="font-size:.78rem;line-height:1.4;max-width:240px;">
                    <strong>${c.sender_name}:</strong> ${c.preview}
                </p>
            </div>
            ${c.unread ? '<span class="badge rounded-pill bg-info ms-1" style="font-size:.55rem;padding:3px 6px;">Baru</span>' : ''}
        </div>`;
    }).join('');
}

function chatClickItem(idTiket, redirectUrl) {
    const readUrl = CHAT_READ_TIKET.replace('__ID__', idTiket);
    fetch(readUrl, {
        method : 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
    }).then(() => {
        chatFetch();
        if (redirectUrl) window.location.href = redirectUrl;
    }).catch(() => {});
}

function chatReadAll() {
    fetch(CHAT_READ_ALL_URL, {
        method : 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
    }).then(() => chatFetch()).catch(() => {});
}

// ─── Jalankan ────────────────────────────────────────────────────────────────
notifInit();
chatInit();

// ─── Live Clock ──────────────────────────────────────────────────────────────
function updateClock() {
    const now  = new Date();
    const el   = document.getElementById('live-clock');
    if (el) {
        el.textContent = String(now.getHours()).padStart(2,'0') + ':' +
                         String(now.getMinutes()).padStart(2,'0') + ':' +
                         String(now.getSeconds()).padStart(2,'0');
    }
}
setInterval(updateClock, 1000);
updateClock();
</script>
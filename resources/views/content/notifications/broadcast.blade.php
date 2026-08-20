@extends('layouts/contentNavbarLayout')

@section('title', 'Custom Push Notification Broadcast')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-broadcast text-primary me-2"></i> Broadcast Push Notifikasi Kustom
            </h4>
            <p class="text-muted mb-0">Kirim notifikasi kustom langsung ke bilah notifikasi HP Android, PWA App, & Web Browser pengguna.</p>
        </div>
        <div>
            <button onclick="installPwaApp()" class="btn btn-outline-success rounded-pill shadow-sm">
                <i class="bx bx-mobile-alt me-1"></i> Install App PWA (Android/PC)
            </button>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
    <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
    <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <!-- Form Custom Broadcast -->
    <div class="col-md-5 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <i class="bx bx-send me-2 fs-5"></i>
                <h5 class="card-title text-white mb-0">Buat Notifikasi Kustom Baru</h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('notifications.broadcast.send') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul Notifikasi <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: ⚠️ Pengumuman Pemeliharaan Jaringan" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Isi Pesan Notifikasi <span class="text-danger">*</span></label>
                        <textarea name="body" class="form-control" rows="3" placeholder="Tuliskan isi notifikasi kustom yang akan muncul di HP/Browser pengguna..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Penerima <span class="text-danger">*</span></label>
                        <select name="target" class="form-select" required>
                            <option value="all">🌐 Semua Pengguna (Broadcast Publik)</option>
                            <option value="Admin">🛡️ Khusus Admin Sistem ({{ $adminCount }} user)</option>
                            <option value="Teknisi">🔧 Khusus Tim Teknisi ({{ $teknisiCount }} user)</option>
                            <option value="Pelanggan">👤 Khusus Pelanggan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe & Warna Alert</label>
                        <select name="color" class="form-select">
                            <option value="primary">🟣 Primary (Informasi Umum)</option>
                            <option value="success">🟢 Success (Pembayaran / Pengumuman Positif)</option>
                            <option value="warning">🟡 Warning (Peringatan / Maintenance)</option>
                            <option value="danger">🔴 Danger (Gangguan / Kritis)</option>
                            <option value="info">🔵 Info (Pemberitahuan)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Link Tautan Aksi (Opsional)</label>
                        <input type="url" name="action_url" class="form-control" value="{{ url('/') }}" placeholder="https://example.com/target-page">
                        <small class="text-muted">Link yang akan dibuka ketika pengguna mengklik notifikasi.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                        <i class="bx bx-paper-plane me-1"></i> Kirim Push Notifikasi Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat Push Notification -->
    <div class="col-md-7 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h5 class="card-title text-dark mb-0">
                    <i class="bx bx-history me-1 text-primary"></i> Riwayat Push Notifikasi Terkirim
                </h5>
                <span class="badge bg-label-primary">Total User: {{ $totalUsers }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tipe</th>
                            <th>Judul & Isi Notifikasi</th>
                            <th>Waktu Kirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $n)
                        <tr>
                            <td style="width: 100px;">
                                <span class="badge bg-label-{{ $n->color ?? 'primary' }} text-capitalize">
                                    <i class="bx {{ $n->icon ?? 'bx-bell' }} me-1"></i> {{ $n->type }}
                                </span>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ $n->title }}</strong>
                                <small class="text-muted">{{ Str::limit($n->body, 80) }}</small>
                                @if($n->action_url)
                                <div><a href="{{ $n->action_url }}" target="_blank" class="small text-primary text-decoration-none">🔗 Link Tautan</a></div>
                                @endif
                            </td>
                            <td class="small text-muted" style="white-space: nowrap;">
                                {{ $n->created_at ? $n->created_at->diffForHumans() : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">Belum ada riwayat push notifikasi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($history->hasPages())
            <div class="card-footer bg-white">
                {{ $history->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

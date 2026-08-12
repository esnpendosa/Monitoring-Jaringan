@extends('layouts/contentNavbarLayout')

@section('title', 'Tutorial')

@section('content')
<div class="d-flex justify-content-between align-items-center py-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bx bx-book-open text-primary me-2"></i>Tutorial</h4>
        <p class="text-muted mb-0 small">Panduan lengkap penggunaan perangkat internet Anda</p>
    </div>
    @if($isAdmin)
    <a href="{{ route('tutorial.admin.index') }}" class="btn btn-primary btn-sm">
        <i class="bx bx-cog me-1"></i> Kelola Tutorial
    </a>
    @endif
</div>

{{-- Filter & Search --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('tutorial.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="bx bx-search"></i></span>
                    <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0" placeholder="Cari tutorial...">
                </div>
            </div>
            <div class="col-md-4">
                <select name="kategori" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ $kategori == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bx bx-filter-alt me-1"></i>Filter</button>
                    <a href="{{ route('tutorial.index') }}" class="btn btn-outline-secondary"><i class="bx bx-reset"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tutorial Grid --}}
@if($tutorials->isEmpty())
<div class="text-center py-5">
    <div class="mb-3">
        <i class="bx bx-book-bookmark" style="font-size: 5rem; color: #d1d5db;"></i>
    </div>
    <h5 class="text-muted">Belum ada tutorial tersedia</h5>
    <p class="text-muted small">
        {{ $search || $kategori ? 'Coba ubah filter pencarian.' : 'Admin belum menambahkan tutorial.' }}
    </p>
    @if($search || $kategori)
        <a href="{{ route('tutorial.index') }}" class="btn btn-outline-primary btn-sm">Tampilkan Semua</a>
    @endif
</div>
@else
<div class="row g-4">
    @foreach($tutorials as $tutorial)
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('tutorial.show', $tutorial->slug) }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm tutorial-card">
                {{-- Thumbnail --}}
                <div class="tutorial-thumb-wrap">
                    @if($tutorial->thumbnail)
                        <img src="{{ url('storage/' . $tutorial->thumbnail) }}" alt="{{ $tutorial->judul }}" class="tutorial-thumb">
                    @else
                        <div class="tutorial-thumb-placeholder">
                            <i class="bx bx-book-open"></i>
                        </div>
                    @endif
                    <span class="tutorial-badge">{{ $tutorial->kategori }}</span>
                </div>

                <div class="card-body">
                    <h6 class="fw-bold mb-2 text-dark">{{ $tutorial->judul }}</h6>
                    @if($tutorial->ringkasan)
                        <p class="text-muted small mb-0" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">
                            {{ $tutorial->ringkasan }}
                        </p>
                    @endif
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted"><i class="bx bx-calendar me-1"></i>{{ $tutorial->created_at->format('d M Y') }}</small>
                        <span class="text-primary small fw-semibold">Baca <i class="bx bx-chevron-right"></i></span>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endif

<style>
.tutorial-card {
    border-radius: 16px !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}
.tutorial-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12) !important;
}
.tutorial-thumb-wrap {
    position: relative;
    height: 180px;
    overflow: hidden;
    background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%);
}
.tutorial-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.tutorial-card:hover .tutorial-thumb {
    transform: scale(1.05);
}
.tutorial-thumb-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}
.tutorial-thumb-placeholder i {
    font-size: 4rem;
    color: rgba(255,255,255,0.7);
}
.tutorial-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(255,255,255,0.95);
    color: #6366f1;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 99px;
    backdrop-filter: blur(4px);
    letter-spacing: 0.3px;
}
</style>
@endsection

@extends('layouts/contentNavbarLayout')

@section('title', $tutorial->judul . ' - Tutorial')

@section('content')
<div class="row">
    {{-- Artikel Utama --}}
    <div class="col-lg-8">
        <div class="mb-4">
            <a href="{{ route('tutorial.index') }}" class="text-muted text-decoration-none small">
                <i class="bx bx-arrow-back me-1"></i>Kembali ke Daftar Tutorial
            </a>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius:20px;overflow:hidden;">
            {{-- Thumbnail --}}
            @if($tutorial->thumbnail)
            <img src="{{ url('storage/' . $tutorial->thumbnail) }}" alt="{{ $tutorial->judul }}"
                 style="width:100%;height:300px;object-fit:cover;">
            @endif

            <div class="card-body p-4 p-md-5">
                {{-- Meta --}}
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);font-size:0.78rem;padding:5px 14px;border-radius:99px;">
                        {{ $tutorial->kategori }}
                    </span>
                    @if(!$tutorial->is_published && $isAdmin)
                    <span class="badge bg-warning text-dark" style="border-radius:99px;padding:5px 14px;font-size:0.78rem;">
                        <i class="bx bx-hide me-1"></i>Draft
                    </span>
                    @endif
                </div>

                <h1 class="fw-bold mb-2" style="font-size:1.8rem;line-height:1.3;">{{ $tutorial->judul }}</h1>

                <div class="d-flex align-items-center gap-3 text-muted small mb-4 pb-4 border-bottom">
                    <span><i class="bx bx-calendar me-1"></i>{{ $tutorial->created_at->format('d F Y') }}</span>
                    @if($tutorial->author)
                    <span><i class="bx bx-user me-1"></i>{{ $tutorial->author->name }}</span>
                    @endif
                    @if($isAdmin)
                    <a href="{{ route('tutorial.edit', $tutorial->id) }}" class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="bx bx-edit me-1"></i>Edit Tutorial
                    </a>
                    @endif
                </div>

                {{-- Konten HTML dari TinyMCE --}}
                <div class="tutorial-content">
                    {!! $tutorial->konten !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar: Tutorial Terkait --}}
    <div class="col-lg-4 mt-4 mt-lg-0">
        {{-- Navigasi Kembali --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bx bx-link-alt text-primary me-2"></i>Tutorial {{ $tutorial->kategori }} Lainnya</h6>
                @forelse($related as $item)
                <a href="{{ route('tutorial.show', $item->slug) }}" class="d-flex gap-3 align-items-start mb-3 text-decoration-none related-item">
                    <div class="related-thumb flex-shrink-0">
                        @if($item->thumbnail)
                            <img src="{{ url('storage/' . $item->thumbnail) }}" alt="">
                        @else
                            <div class="related-thumb-placeholder"><i class="bx bx-book-open"></i></div>
                        @endif
                    </div>
                    <div>
                        <p class="fw-semibold mb-1 text-dark small" style="line-height:1.4">{{ $item->judul }}</p>
                        <small class="text-muted">{{ $item->created_at->format('d M Y') }}</small>
                    </div>
                </a>
                @empty
                <p class="text-muted small mb-0">Tidak ada tutorial terkait lainnya.</p>
                @endforelse

                <a href="{{ route('tutorial.index', ['kategori' => $tutorial->kategori]) }}" class="btn btn-outline-primary btn-sm w-100 mt-2">
                    Lihat Semua Kategori {{ $tutorial->kategori }}
                </a>
            </div>
        </div>

        {{-- Semua Kategori --}}
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bx bx-category text-primary me-2"></i>Kategori</h6>
                @foreach(\App\Models\Tutorial::kategoriList() as $kat)
                <a href="{{ route('tutorial.index', ['kategori' => $kat]) }}"
                   class="badge mb-2 me-1 text-decoration-none {{ $tutorial->kategori == $kat ? 'bg-primary' : 'bg-label-secondary text-dark' }}"
                   style="font-size:0.82rem;padding:7px 14px;border-radius:99px;">
                    {{ $kat }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
/* ── Konten TinyMCE ── */
.tutorial-content {
    font-size: 1rem;
    line-height: 1.85;
    color: #374151;
}
.tutorial-content h1, .tutorial-content h2, .tutorial-content h3,
.tutorial-content h4, .tutorial-content h5, .tutorial-content h6 {
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    color: #111827;
}
.tutorial-content h2 { font-size: 1.4rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e5e7eb; }
.tutorial-content h3 { font-size: 1.2rem; }
.tutorial-content p  { margin-bottom: 1rem; }
.tutorial-content ul, .tutorial-content ol {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}
.tutorial-content li { margin-bottom: 0.4rem; }
.tutorial-content img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    margin: 1rem 0;
    box-shadow: 0 4px 16px rgba(0,0,0,0.10);
}
.tutorial-content blockquote {
    border-left: 4px solid #6366f1;
    background: #f5f3ff;
    padding: 1rem 1.25rem;
    border-radius: 0 12px 12px 0;
    margin: 1.5rem 0;
    color: #4b5563;
    font-style: italic;
}
.tutorial-content pre, .tutorial-content code {
    background: #1e293b;
    color: #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    padding: 0.2em 0.5em;
}
.tutorial-content pre {
    padding: 1.25rem;
    overflow-x: auto;
    margin: 1rem 0;
}
.tutorial-content pre code {
    background: none;
    padding: 0;
}
.tutorial-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    font-size: 0.9rem;
}
.tutorial-content table th,
.tutorial-content table td {
    border: 1px solid #e5e7eb;
    padding: 0.75rem 1rem;
    text-align: left;
}
.tutorial-content table th {
    background: #f9fafb;
    font-weight: 600;
}
.tutorial-content table tr:hover td { background: #f9fafb; }

/* ── Sidebar Related ── */
.related-item { transition: opacity .2s; }
.related-item:hover { opacity: .75; }
.related-thumb {
    width: 64px;
    height: 48px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.related-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.related-thumb-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
</style>
@endsection

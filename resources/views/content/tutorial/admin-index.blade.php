@extends('layouts/contentNavbarLayout')

@section('title', 'Kelola Tutorial Modem & WiFi')

@section('content')
<div class="d-flex justify-content-between align-items-center py-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bx bx-cog text-primary me-2"></i>Kelola Tutorial</h4>
        <p class="text-muted mb-0 small">Buat dan edit panduan modem, router, dan WiFi secara dinamis</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('tutorial.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bx bx-show me-1"></i> Lihat Halaman Publik
        </a>
        <a href="{{ route('tutorial.create') }}" class="btn btn-primary btn-sm">
            <i class="bx bx-plus me-1"></i> Tambah Tutorial Baru
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="card border-0 shadow-sm" style="border-radius: 16px;">
    <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th width="80">Thumbnail</th>
                    <th>Judul Tutorial</th>
                    <th>Kategori</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th>Tanggal Dibuat</th>
                    <th width="150" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse($tutorials as $tutorial)
                <tr>
                    <td>
                        @if($tutorial->thumbnail)
                            <img src="{{ url('storage/' . $tutorial->thumbnail) }}" alt="" class="rounded" style="width: 60px; height: 45px; object-fit: cover;">
                        @else
                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 45px;">
                                <i class="bx bx-book-open text-muted"></i>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-bold text-dark">{{ $tutorial->judul }}</div>
                        <small class="text-muted" style="font-size: 0.75rem;">Slug: {{ $tutorial->slug }}</small>
                    </td>
                    <td>
                        <span class="badge bg-label-info">{{ $tutorial->kategori }}</span>
                    </td>
                    <td>{{ $tutorial->urutan }}</td>
                    <td>
                        <form action="{{ route('tutorial.toggle-publish', $tutorial->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="border-0 bg-transparent p-0" title="Klik untuk ubah status">
                                @if($tutorial->is_published)
                                    <span class="badge bg-label-success"><i class="bx bx-show-alt me-1"></i>Published</span>
                                @else
                                    <span class="badge bg-label-warning"><i class="bx bx-hide me-1"></i>Draft</span>
                                @endif
                            </button>
                        </form>
                    </td>
                    <td>
                        <small>{{ $tutorial->created_at->format('d M Y H:i') }}</small>
                        @if($tutorial->author)
                            <br><small class="text-muted" style="font-size: 0.75rem;">Oleh: {{ $tutorial->author->name }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('tutorial.show', $tutorial->slug) }}" class="btn btn-sm btn-outline-info" target="_blank" title="Preview Halaman">
                                <i class="bx bx-link-external"></i>
                            </a>
                            <a href="{{ route('tutorial.edit', $tutorial->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Tutorial">
                                <i class="bx bx-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="{{ $tutorial->id }}" data-judul="{{ $tutorial->judul }}" title="Hapus">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                        <form id="delete-form-{{ $tutorial->id }}" action="{{ route('tutorial.destroy', $tutorial->id) }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Belum ada tutorial yang ditambahkan. <br>
                        <a href="{{ route('tutorial.create') }}" class="btn btn-sm btn-primary mt-2">Buat Tutorial Pertama</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const judul = this.dataset.judul;
                
                Swal.fire({
                    title: 'Hapus Tutorial?',
                    text: `Apakah Anda yakin ingin menghapus tutorial "${judul}"? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff3e1d',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            });
        });
    });
</script>
@endsection
@endsection

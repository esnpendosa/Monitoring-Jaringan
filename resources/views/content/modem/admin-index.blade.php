@extends('layouts/contentNavbarLayout')

@section('title', 'Kelola Modem')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0"><i class="bx bx-chip text-primary me-2"></i>Kelola Modem</h5>
        <small class="text-muted">Manajemen katalog perangkat modem</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('modem.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bx bx-show me-1"></i>Lihat Publik
        </a>
        <a href="{{ route('modem.create') }}" class="btn btn-primary btn-sm">
            <i class="bx bx-plus me-1"></i>Tambah Modem
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th width="60">Gambar</th>
                    <th>Nama / Model</th>
                    <th>Merek</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($modems as $modem)
                <tr>
                    <td>
                        @if($modem->image_path_front)
                            <img src="{{ asset('storage/' . $modem->image_path_front) }}"
                                 style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e8eaed;">
                        @else
                            <div style="width:48px;height:48px;background:#f0f1ff;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <i class="bx bx-chip text-primary" style="font-size:22px;opacity:0.5;"></i>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $modem->nama }}</div>
                        <small class="text-muted">{{ $modem->model }}</small>
                    </td>
                    <td>{{ $modem->merek }}</td>
                    <td>
                        @if($modem->ip_address)
                            <a href="{{ str_starts_with($modem->ip_address, 'http') ? $modem->ip_address : 'http://' . $modem->ip_address }}" 
                               target="_blank" class="badge bg-label-info">
                                <i class="bx bx-link-external me-1"></i>{{ $modem->ip_address }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-label-{{ $modem->is_active ? 'success' : 'secondary' }}">
                            {{ $modem->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('modem.show', $modem) }}" class="btn btn-xs btn-outline-info me-1">
                            <i class="bx bx-show"></i>
                        </a>
                        <a href="{{ route('modem.edit', $modem) }}" class="btn btn-xs btn-outline-primary me-1">
                            <i class="bx bx-edit"></i>
                        </a>
                        <form action="{{ route('modem.destroy', $modem) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Hapus modem {{ $modem->nama }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-outline-danger"><i class="bx bx-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bx bx-chip d-block mb-2" style="font-size:2rem;opacity:0.3;"></i>
                        Belum ada data modem. <a href="{{ route('modem.create') }}">Tambah sekarang</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($modems->hasPages())
    <div class="card-footer">{{ $modems->links() }}</div>
    @endif
</div>
@endsection

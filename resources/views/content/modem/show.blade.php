@extends('layouts/contentNavbarLayout')

@section('title', $modem->nama . ' - Katalog Modem')

@section('page-style')
<style>
    .modem-detail-img {
        width: 100%;
        max-height: 320px;
        object-fit: contain;
        border-radius: 12px;
        background: linear-gradient(135deg, #f0f1ff, #e8e9ff);
        padding: 1rem;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    .modem-detail-img:hover {
        transform: scale(1.03);
        box-shadow: 0 8px 20px rgba(105, 108, 255, 0.15);
    }
    .modem-img-placeholder-lg {
        height: 280px;
        background: linear-gradient(135deg, #f0f1ff, #e8e9ff);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 6rem;
        color: #696cff;
        opacity: 0.3;
    }
    .spec-table td:first-child {
        font-weight: 600;
        color: #32475c;
        width: 40%;
    }
    .spec-table td {
        padding: 8px 12px;
        border-bottom: 1px solid #f1f1f1;
        font-size: 13px;
    }
    .spec-table tr:last-child td { border-bottom: none; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12 mb-3">
        <a href="{{ route('modem.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bx bx-arrow-back me-1"></i>Kembali ke Katalog
        </a>
    </div>

    <div class="col-md-5 mb-4">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-6 text-center">
                        <span class="badge bg-label-secondary mb-2 w-100">Tampak Depan</span>
                        @if($modem->image_path_front)
                            <a href="{{ asset('storage/' . $modem->image_path_front) }}" target="_blank" title="Klik untuk memperbesar">
                                <img src="{{ asset('storage/' . $modem->image_path_front) }}" alt="{{ $modem->nama }} Depan" class="modem-detail-img" style="max-height: 200px;">
                            </a>
                        @else
                            <div class="modem-img-placeholder-lg" style="height: 160px; font-size: 3rem;"><i class="bx bx-chip"></i></div>
                        @endif
                    </div>
                    <div class="col-6 text-center">
                        <span class="badge bg-label-secondary mb-2 w-100">Tampak Belakang</span>
                        @if($modem->image_path_back)
                            <a href="{{ asset('storage/' . $modem->image_path_back) }}" target="_blank" title="Klik untuk memperbesar">
                                <img src="{{ asset('storage/' . $modem->image_path_back) }}" alt="{{ $modem->nama }} Belakang" class="modem-detail-img" style="max-height: 200px;">
                            </a>
                        @else
                            <div class="modem-img-placeholder-lg" style="height: 160px; font-size: 3rem;"><i class="bx bx-chip"></i></div>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
                    @if($modem->merek)
                        <span class="badge bg-label-primary">{{ $modem->merek }}</span>
                    @endif
                    @if($modem->ip_address)
                        <a href="{{ str_starts_with($modem->ip_address, 'http') ? $modem->ip_address : 'http://' . $modem->ip_address }}" 
                           target="_blank" class="badge bg-label-info">
                            <i class="bx bx-link-external me-1"></i>IP: {{ $modem->ip_address }}
                        </a>
                    @endif
                    <span class="badge bg-label-{{ $modem->is_active ? 'success' : 'secondary' }}">
                        {{ $modem->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body p-4">
                <h4 class="fw-bold text-dark mb-1">{{ $modem->nama }}</h4>
                <p class="text-muted small mb-3">Model: <strong>{{ $modem->model }}</strong></p>

                @if($modem->ip_address)
                <div class="mb-3">
                    <h6 class="fw-semibold text-dark mb-1">Akses IP Modem</h6>
                    <a href="{{ str_starts_with($modem->ip_address, 'http') ? $modem->ip_address : 'http://' . $modem->ip_address }}" 
                       target="_blank" class="btn btn-primary btn-sm px-3">
                        <i class="bx bx-link-external me-1"></i>Buka {{ $modem->ip_address }}
                    </a>
                </div>
                @endif

                @if($modem->deskripsi)
                <div class="mb-4">
                    <h6 class="fw-semibold text-dark mb-2">Deskripsi</h6>
                    <p class="text-muted" style="font-size:14px; line-height:1.7;">{{ $modem->deskripsi }}</p>
                </div>
                @endif

                @if($modem->spesifikasi)
                <div>
                    <h6 class="fw-semibold text-dark mb-2">Spesifikasi</h6>
                    <div class="bg-light rounded-3 p-3" style="font-size:13px; white-space:pre-wrap; line-height:1.7;">{{ $modem->spesifikasi }}</div>
                </div>
                @endif

                @can('pelanggan_manage')
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('modem.edit', $modem) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <form action="{{ route('modem.destroy', $modem) }}" method="POST"
                          onsubmit="return confirm('Hapus modem ini?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="bx bx-trash me-1"></i>Hapus
                        </button>
                    </form>
                </div>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

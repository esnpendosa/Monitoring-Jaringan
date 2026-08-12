@extends('layouts/contentNavbarLayout')

@section('title', 'Katalog Modem')

@section('page-style')
<style>
    .modem-hero {
        background: linear-gradient(135deg, #696cff 0%, #3f4191 100%);
        border-radius: 16px;
        padding: 2rem 2.5rem;
        color: white;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .modem-hero::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }
    .modem-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; right: 60px;
        width: 250px; height: 250px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }

    .modem-card {
        border: 1px solid #e8eaed;
        border-radius: 14px;
        overflow: hidden;
        transition: all 0.3s ease;
        background: #fff;
        height: 100%;
    }
    .modem-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(105,108,255,0.15);
        border-color: rgba(105,108,255,0.3);
    }
    .modem-img-wrap {
        height: 180px;
        background: linear-gradient(135deg, #f0f1ff 0%, #e8e9ff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }
    .modem-name a {
        color: #32475c;
        text-decoration: none;
        transition: color 0.2s;
    }
    .modem-name a:hover {
        color: #696cff;
    }
    .modem-ip-link {
        font-size: 13px;
        color: #8592a3;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 6px;
    }
    .modem-ip-link:hover {
        color: #696cff;
    }
    .modem-img-link {
        display: block;
        width: 100%;
        height: 100%;
    }
    .modem-img-wrap {
        height: 180px;
        background: linear-gradient(135deg, #f0f1ff 0%, #e8e9ff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }
    .modem-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
        will-change: transform;
    }
    .modem-card:hover .modem-img-wrap img {
        transform: scale(1.05);
    }
    .modem-img-placeholder {
        font-size: 4rem;
        opacity: 0.3;
    }
    .modem-card-body {
        padding: 1rem 1.2rem 1.2rem;
    }
    .modem-brand {
        font-size: 11px;
        font-weight: 600;
        color: #696cff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .modem-name {
        font-size: 15px;
        font-weight: 700;
        color: #32475c;
        margin: 4px 0 8px;
        line-height: 1.3;
    }
    .modem-desc {
        font-size: 12px;
        color: #8592a3;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .btn-detail {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 600;
        color: #696cff;
        text-decoration: none;
        margin-top: 10px;
        transition: gap 0.2s;
    }
    .btn-detail:hover { gap: 8px; color: #5f61e6; }

    .filter-bar {
        background: #fff;
        border: 1px solid #e8eaed;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #8592a3;
    }
    .empty-state i { font-size: 4rem; opacity: 0.3; }
</style>
@endsection

@section('content')
{{-- Hero --}}
<div class="modem-hero">
    <div class="position-relative" style="z-index:1;">
        <h4 class="text-white mb-1 fw-bold"><i class="bx bx-chip me-2"></i>Katalog Modem</h4>
        <p class="mb-0 opacity-75">Daftar perangkat modem yang digunakan dalam jaringan Rozitech.</p>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('modem.index') }}" class="row g-2 align-items-end">
        <div class="col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="bx bx-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 ps-0"
                    placeholder="Cari nama, merek, model..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-3">
            <select name="merek" class="form-select form-select-sm">
                <option value="">Semua Merek</option>
                @foreach($mereks as $merek)
                    <option value="{{ $merek }}" {{ request('merek') == $merek ? 'selected' : '' }}>
                        {{ $merek }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bx bx-filter-alt me-1"></i>Filter</button>
            <a href="{{ route('modem.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-x"></i></a>
        </div>
        @can('pelanggan_manage')
        <div class="col-md-1 text-end">
            <a href="{{ route('modem.admin.index') }}" class="btn btn-outline-primary btn-sm w-100">
                <i class="bx bx-cog"></i>
            </a>
        </div>
        @endcan
    </form>
</div>

{{-- Grid Modem --}}
@if($modems->isEmpty())
<div class="empty-state">
    <i class="bx bx-chip d-block mb-3"></i>
    <h6>Belum ada data modem</h6>
    <p class="small">@can('pelanggan_manage')<a href="{{ route('modem.admin.index') }}">Tambah modem pertama</a>@else Silakan hubungi admin.@endcan</p>
</div>
@else
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
    @foreach($modems as $modem)
    @php
        $ipUrl = $modem->ip_address 
            ? (str_starts_with($modem->ip_address, 'http') ? $modem->ip_address : 'http://' . $modem->ip_address)
            : null;
        $detailUrl = route('modem.show', $modem);
    @endphp
    <div class="col">
        <div class="modem-card">
            <div class="modem-img-wrap">
                <a href="{{ $detailUrl }}" class="modem-img-link">
                    @if($modem->image_path_front)
                        <img src="{{ asset('storage/' . $modem->image_path_front) }}"
                             alt="{{ $modem->nama }}"
                             loading="lazy"
                             decoding="async"
                             width="360" height="180">
                    @else
                        <i class="bx bx-chip modem-img-placeholder"></i>
                    @endif
                </a>
            </div>
            <div class="modem-card-body">
                <div class="modem-brand">{{ $modem->merek }}</div>
                <div class="modem-name">
                    <a href="{{ $detailUrl }}">
                        {{ $modem->nama }}
                    </a>
                </div>
                
                @if($modem->ip_address)
                    <div>
                        <a href="{{ $ipUrl }}" target="_blank" class="modem-ip-link">
                            <i class="bx bx-link-external"></i> {{ $modem->ip_address }}
                        </a>
                    </div>
                @endif

                @if($modem->deskripsi)
                    <div class="modem-desc mb-2">{{ $modem->deskripsi }}</div>
                @endif
                
                <a href="{{ $detailUrl }}" class="btn-detail">
                    Lihat Detail <i class="bx bx-right-arrow-alt"></i>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $modems->links() }}
</div>
@endif
@endsection

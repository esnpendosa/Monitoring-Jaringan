@extends('layouts/contentNavbarLayout')

@section('title', 'Rekap Pembayaran Pelanggan')

@section('page-style')
<style>
    .excel-table {
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.9rem;
    }
    .excel-table th {
        background-color: #f1f3f4 !important;
        color: #3c4043 !important;
        font-weight: 700 !important;
        border: 1px solid #cbd5e1 !important;
        text-transform: none !important;
        letter-spacing: 0px !important;
    }
    .excel-table td {
        border: 1px solid #e2e8f0 !important;
        padding: 8px 12px !important;
        vertical-align: middle;
    }
    .excel-table tbody tr:hover {
        background-color: #f8fafd !important;
    }
    .excel-tfoot td {
        background-color: #f8fafc !important;
        font-weight: bold;
        color: #1e293b;
        border-top: 2px solid #64748b !important;
        border-bottom: 2px double #64748b !important;
    }
    .excel-badge-paid {
        background-color: #e6f4ea;
        color: #137333;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.75rem;
    }
    .excel-badge-unpaid {
        background-color: #fce8e6;
        color: #c5221f;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.75rem;
    }
    .excel-badge-pending {
        background-color: #fef7e0;
        color: #b06000;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.75rem;
    }
    .card-excel-header {
        background-color: #107c41; /* Excel green */
        color: white;
    }
    .card-excel-header h5 {
        color: white !important;
    }
    .number-col {
        font-family: 'Consolas', 'Courier New', Courier, monospace;
        text-align: right;
    }
</style>
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Laporan /</span> Rekap Pembayaran Excel View</h4>

<!-- Filter Sheet -->
<div class="card mb-4 border border-success border-opacity-25">
    <div class="card-header card-excel-header py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold"><i class="bx bx-filter me-2"></i> Filter Sheet & Pencarian</h5>
        <span class="badge bg-white text-success fw-bold">Excel Mode</span>
    </div>
    <div class="card-body pt-4">
        <form action="{{ route('laporan.rekap-pembayaran') }}" method="GET">
            <div class="row g-3">
                <!-- Search Pelanggan -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Cari Pelanggan</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Nama / Kode Pelanggan" value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Periode Bulan -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Bulan</label>
                    <select name="month" class="form-select">
                        <option value="">-- Semua Bulan --</option>
                        @for($i=1; $i<=12; $i++)
                            <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Periode Tahun -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Tahun</label>
                    <select name="year" class="form-select">
                        <option value="">-- Semua Tahun --</option>
                        @for($i=date('Y'); $i>=2023; $i--)
                            <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Status Pembayaran -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">-- Semua Status --</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas (PAID)</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Bayar (UNPAID)</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu Verifikasi (PENDING)</option>
                    </select>
                </div>

                <!-- Metode Pembayaran -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Metode Pembayaran</label>
                    <select name="metode_pembayaran" class="form-select">
                        <option value="">-- Semua Metode --</option>
                        <option value="Cash" {{ request('metode_pembayaran') == 'Cash' ? 'selected' : '' }}>Cash (Tunai)</option>
                        @foreach($available_methods as $method)
                            @if($method != 'Cash')
                                <option value="{{ $method }}" {{ request('metode_pembayaran') == $method ? 'selected' : '' }}>{{ $method }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Range Tanggal Bayar -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Dari Tanggal (Bayar/Update)</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>

                <!-- Submit / Actions -->
                <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                    <a href="{{ route('laporan.rekap-pembayaran') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-refresh me-1"></i> Reset Filter
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-filter-alt me-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('laporan.rekap-pembayaran.export-excel', request()->all()) }}" class="btn btn-primary">
                        <i class="bx bx-file me-1"></i> Export Excel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards (Totals) -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-start border-primary border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Hasil Filter (Semua)</h6>
                        <h3 class="fw-bold mb-0 text-primary">Rp {{ number_format($total_pembayaran, 0, ',', '.') }}</h3>
                        <small class="text-muted">
                            Lunas: <span class="fw-bold text-success">Rp {{ number_format($total_lunas, 0, ',', '.') }}</span> | 
                            Piutang: <span class="fw-bold text-warning">Rp {{ number_format($total_piutang, 0, ',', '.') }}</span>
                        </small>
                    </div>
                    <div class="avatar bg-label-primary rounded p-2">
                        <i class="bx bx-money text-primary fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-success border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Tunai (Cash)</h6>
                        <h3 class="fw-bold mb-0 text-success">Rp {{ number_format($total_cash, 0, ',', '.') }}</h3>
                        <small class="text-muted">
                            Lunas (Sudah Bayar): <span class="fw-bold text-success">Rp {{ number_format($total_cash_lunas, 0, ',', '.') }}</span>
                        </small>
                    </div>
                    <div class="avatar bg-label-success rounded p-2">
                        <i class="bx bx-hand-holding-dollar text-success fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-info border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Transfer / Non-Cash</h6>
                        <h3 class="fw-bold mb-0 text-info">Rp {{ number_format($total_transfer, 0, ',', '.') }}</h3>
                        <small class="text-muted">
                            Lunas (Sudah Bayar): <span class="fw-bold text-info">Rp {{ number_format($total_transfer_lunas, 0, ',', '.') }}</span>
                        </small>
                    </div>
                    <div class="avatar bg-label-info rounded p-2">
                        <i class="bx bx-credit-card text-info fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Excel Data Sheet -->
<div class="card border border-success border-opacity-25">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom py-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-spreadsheet me-2 text-success"></i> Lembar Kerja Rekap Pembayaran</h5>
        <small class="text-muted">Total Data: <strong>{{ $tagihan->count() }}</strong> baris</small>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-bordered excel-table table-sm">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th class="text-end">E</th>
                    <th>F</th>
                    <th>G</th>
                    <th class="text-center">H</th>
                </tr>
                <tr>
                    <th class="text-center">No</th>
                    <th>Kode / ID Pelanggan</th>
                    <th>Nama Pelanggan</th>
                    <th>Periode Tagihan</th>
                    <th class="text-end">Jumlah Pembayaran</th>
                    <th>Metode Pembayaran</th>
                    <th>Tanggal Update</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse($tagihan as $t)
                <tr>
                    <td class="text-center fw-bold text-muted">{{ $loop->iteration }}</td>
                    <td class="fw-bold text-dark">{{ $t->pelanggan->kode_pelanggan }}</td>
                    <td>{{ $t->pelanggan->nama_pelanggan }}</td>
                    <td>{{ date('F', mktime(0, 0, 0, $t->bulan, 10)) }} {{ $t->tahun }}</td>
                    <td class="number-col text-dark font-monospace fw-semibold">
                        Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                        @if(isset($t->original_jumlah) && $t->original_jumlah > 0 && $t->jumlah == 0)
                            <br><small class="text-info fw-normal" style="font-size: 0.72rem;">(Dibayar di awal Rp {{ number_format($t->original_jumlah, 0, ',', '.') }} pada {{ $t->paid_at->format('M Y') }})</small>
                        @endif
                    </td>
                    <td>
                        @if($t->metode_pembayaran)
                            <span class="badge bg-label-secondary"><i class="bx bx-credit-card-front me-1"></i> {{ $t->metode_pembayaran }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $t->paid_at ? date('Y-m-d H:i', strtotime($t->paid_at)) : $t->updated_at->format('Y-m-d H:i') }}</td>
                    <td class="text-center">
                        @if($t->status == 'paid')
                            <span class="excel-badge-paid">LUNAS</span>
                        @elseif($t->status == 'pending' || ($t->status == 'unpaid' && $t->bukti_bayar))
                            <span class="excel-badge-pending">PENDING</span>
                        @else
                            <span class="excel-badge-unpaid">BELUM BAYAR</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Data rekap tidak ditemukan. Silakan sesuaikan filter pencarian Anda.</td>
                </tr>
                @endforelse
            </tbody>
            @if($tagihan->isNotEmpty())
            <tfoot class="excel-tfoot">
                <tr>
                    <td colspan="4" class="text-end fw-bold">TOTAL KESELURUHAN (SEMUA):</td>
                    <td class="number-col font-monospace fw-bold text-dark fs-6" style="border-left: 1px solid #cbd5e1 !important; border-right: 1px solid #cbd5e1 !important;">
                        Rp {{ number_format($total_pembayaran, 0, ',', '.') }}
                    </td>
                    <td colspan="3" class="bg-light"></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end fw-bold text-success">TOTAL TUNAI (CASH) LUNAS:</td>
                    <td class="number-col font-monospace fw-bold text-success fs-6" style="border-left: 1px solid #cbd5e1 !important; border-right: 1px solid #cbd5e1 !important;">
                        Rp {{ number_format($total_cash_lunas, 0, ',', '.') }}
                    </td>
                    <td colspan="3" class="bg-light"></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end fw-bold text-info">TOTAL TRANSFER / NON-CASH LUNAS:</td>
                    <td class="number-col font-monospace fw-bold text-info fs-6" style="border-left: 1px solid #cbd5e1 !important; border-right: 1px solid #cbd5e1 !important;">
                        Rp {{ number_format($total_transfer_lunas, 0, ',', '.') }}
                    </td>
                    <td colspan="3" class="bg-light"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

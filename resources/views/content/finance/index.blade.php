@extends('layouts/contentNavbarLayout')

@section('title', 'Manajemen Keuangan & Arus Kas (Role Finance)')

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-calculator text-primary me-2"></i> Portal Manajemen Keuangan (Finance)
            </h4>
            <p class="text-muted mb-0">Pusat kendali arus kas, pencatatan transaksi masuk/keluar, tagihan pelanggan, dan laporan laba rugi NMS.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('billing.index') }}" class="btn btn-outline-primary rounded-pill shadow-sm">
                <i class="bx bx-receipt me-1"></i> Tagihan Pelanggan
            </a>
            <a href="{{ route('kas-bon.index') }}" class="btn btn-outline-warning rounded-pill shadow-sm">
                <i class="bx bx-money me-1"></i> Kas Bon Pekerja
            </a>
            <a href="{{ route('settings.payment') }}" class="btn btn-outline-secondary rounded-pill shadow-sm">
                <i class="bx bx-cog me-1"></i> Gateway Pembayaran
            </a>
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

<!-- Summary Cards Keuangan -->
<div class="row mb-4">
    <!-- Card Saldo Kas Bersih -->
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm bg-primary text-white h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold text-white-50">SALDO KAS BERSIH (NET)</span>
                    <div class="p-2 rounded bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center" style="width:42px; height:42px;">
                        <i class="bx bx-wallet fs-4"></i>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-1">Rp {{ number_format($netBalance, 0, ',', '.') }}</h2>
                <small class="text-white-50">Total akumulasi penerimaan dikurangi pengeluaran</small>
            </div>
        </div>
    </div>

    <!-- Card Pemasukan Bulan Ini -->
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm bg-success text-white h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold text-white-50">PEMASUKAN BULAN INI</span>
                    <div class="p-2 rounded bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center" style="width:42px; height:42px;">
                        <i class="bx bx-trending-up fs-4"></i>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-1">Rp {{ number_format($monthIncome, 0, ',', '.') }}</h2>
                <small class="text-white-50">Tagihan Lunas + PSB Bulan {{ sprintf('%02d', $bulan) }}/{{ $tahun }}</small>
            </div>
        </div>
    </div>

    <!-- Card Pengeluaran Bulan Ini -->
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm bg-danger text-white h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold text-white-50">PENGELUARAN BULAN INI</span>
                    <div class="p-2 rounded bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center" style="width:42px; height:42px;">
                        <i class="bx bx-trending-down fs-4"></i>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-1">Rp {{ number_format($monthExpense, 0, ',', '.') }}</h2>
                <small class="text-white-50">Pengeluaran Kas + Kas Bon Bulan {{ sprintf('%02d', $bulan) }}/{{ $tahun }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Form Input Transaksi Kas -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center border-bottom">
                <i class="bx bx-plus-circle text-primary me-2 fs-5"></i>
                <h5 class="card-title text-dark mb-0">Catat Transaksi Kas Baru</h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('finance.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jenis Transaksi Kas <span class="text-danger">*</span></label>
                        <select name="tipe" class="form-select" required>
                            <option value="pengeluaran">Pengeluaran Kas (Beli Perangkat/Operasional)</option>
                            <option value="psb">Pemasukan Pasang Baru (PSB)</option>
                            <option value="pemasukan">Pemasukan Kas Lainnya</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori Transaksi <span class="text-danger">*</span></label>
                        <input type="text" name="kategori" class="form-control" placeholder="Contoh: Kabel Optik, Gaji, Konsumsi, Listrik" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nominal Jumlah (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="jumlah" class="form-control" placeholder="0" min="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Keterangan / Deskripsi</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Rincian keterangan transaksi..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Upload Struk / Nota (Opsional)</label>
                        <input type="file" name="nota" class="form-control" accept="image/*">
                        <small class="text-muted">Format gambar JPG/PNG maks 5MB.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                        <i class="bx bx-save me-1"></i> Simpan Transaksi Kas
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Transaksi Arus Kas -->
    <div class="col-md-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
                <h5 class="card-title text-dark mb-0">
                    <i class="bx bx-list-check me-1 text-primary"></i> Daftar Transaksi Arus Kas
                </h5>

                <!-- Filter Form -->
                <form action="{{ route('finance.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                        @endfor
                    </select>

                    <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <select name="tipe" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Tipe</option>
                        <option value="pengeluaran" {{ request('tipe') == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                        <option value="psb" {{ request('tipe') == 'psb' ? 'selected' : '' }}>Pemasukan / PSB</option>
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe & Kategori</th>
                            <th>Keterangan</th>
                            <th>Jumlah Nominal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                        <tr>
                            <td class="small text-muted" style="white-space: nowrap;">
                                {{ \Carbon\Carbon::parse($t->tanggal)->format('d/m/Y') }}
                            </td>
                            <td>
                                @if($t->tipe === 'pengeluaran')
                                <span class="badge bg-label-danger me-1">Pengeluaran</span>
                                @else
                                <span class="badge bg-label-success me-1">Pemasukan</span>
                                @endif
                                <strong class="text-dark">{{ $t->kategori }}</strong>
                            </td>
                            <td>
                                <span class="small text-muted">{{ Str::limit($t->keterangan, 60) ?: '-' }}</span>
                            </td>
                            <td>
                                <strong class="{{ $t->tipe === 'pengeluaran' ? 'text-danger' : 'text-success' }}">
                                    {{ $t->tipe === 'pengeluaran' ? '-' : '+' }} Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                                </strong>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('finance.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Hapus Transaksi">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi kas keuangan dicatat untuk periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
            <div class="card-footer bg-white">
                {{ $transactions->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

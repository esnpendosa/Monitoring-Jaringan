@extends('layouts/contentNavbarLayout')

@section('title', 'Manajemen Keuangan & PSB')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Keuangan /</span> Catatan Keuangan & PSB</h4>
        <button type="button" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
            <i class="bx bx-plus me-1"></i> Tambah Transaksi
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Financial Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-label-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold">Pengeluaran (Bulan Ini)</span>
                        <i class="bx bx-trending-down text-warning fs-3"></i>
                    </div>
                    <h4 class="mb-1 text-warning">Rp {{ number_format($stats['bulan_pengeluaran'], 0, ',', '.') }}</h4>
                    <small class="text-muted">Bulan: {{ Carbon\Carbon::createFromDate(null, $bulan, 1)->translatedFormat('F') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-label-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold">PSB Pasang Baru (Bulan Ini)</span>
                        <i class="bx bx-plus-circle text-primary fs-3"></i>
                    </div>
                    <h4 class="mb-1 text-primary">Rp {{ number_format($stats['bulan_psb'], 0, ',', '.') }}</h4>
                    <small class="text-muted">Bulan: {{ Carbon\Carbon::createFromDate(null, $bulan, 1)->translatedFormat('F') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-label-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold">Total Pengeluaran (Semua)</span>
                        <i class="bx bx-wallet text-danger fs-3"></i>
                    </div>
                    <h4 class="mb-1 text-danger">Rp {{ number_format($stats['total_pengeluaran'], 0, ',', '.') }}</h4>
                    <small class="text-muted">Seluruh periode</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-label-success">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold">Total PSB (Semua)</span>
                        <i class="bx bx-trending-up text-success fs-3"></i>
                    </div>
                    <h4 class="mb-1 text-success">Rp {{ number_format($stats['total_psb'], 0, ',', '.') }}</h4>
                    <small class="text-muted">Seluruh periode</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Table Card -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-3">Filter Transaksi</h5>
            <form action="{{ route('keuangan.index') }}" method="GET">
                <div class="row gap-3 gap-md-0">
                    <div class="col-md-3 col-12">
                        <select name="tipe" class="form-select text-capitalize">
                            <option value="">Semua Tipe</option>
                            <option value="pengeluaran" {{ request('tipe') === 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                            <option value="psb" {{ request('tipe') === 'psb' ? 'selected' : '' }}>PSB Pasang Baru</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <select name="bulan" class="form-select">
                            <option value="">Semua Bulan</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                    {{ Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <select name="tahun" class="form-select">
                            @for($y = date('Y') - 3; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <input type="text" name="search" class="form-control" placeholder="Cari keterangan..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 col-12">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bx bx-filter-alt"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th>Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($transactions as $t)
                    <tr>
                        <td><strong>{{ $t->tanggal->format('d M Y') }}</strong></td>
                        <td>
                            @if($t->tipe === 'pengeluaran')
                                <span class="badge bg-label-warning">Pengeluaran</span>
                            @else
                                <span class="badge bg-label-primary">PSB Pasang Baru</span>
                            @endif
                        </td>
                        <td><span class="badge bg-label-secondary text-dark">{{ $t->kategori }}</span></td>
                        <td><span class="text-wrap" style="max-width: 300px; display: block;">{{ $t->keterangan ?: '-' }}</span></td>
                        <td>
                            <strong class="{{ $t->tipe === 'pengeluaran' ? 'text-danger' : 'text-success' }}">
                                {{ $t->tipe === 'pengeluaran' ? '-' : '+' }} Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                            </strong>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-xs btn-outline-primary me-2 edit-btn" 
                                    data-id="{{ $t->id }}"
                                    data-tipe="{{ $t->tipe }}"
                                    data-kategori="{{ $t->kategori }}"
                                    data-jumlah="{{ $t->jumlah }}"
                                    data-keterangan="{{ $t->keterangan }}"
                                    data-tanggal="{{ $t->tanggal->format('Y-m-d') }}"
                                    data-bs-toggle="modal" data-bs-target="#editTransactionModal">
                                    <i class="bx bx-edit-alt"></i>
                                </button>
                                <form action="{{ route('keuangan.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan transaksi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data transaksi keuangan tercatat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer px-3 py-2 border-top">
            {{ $transactions->links() }}
        </div>
    </div>
</div>

<!-- Tambah Transaksi Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('keuangan.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Transaksi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipe Transaksi</label>
                            <select name="tipe" id="add_tipe" class="form-select" required>
                                <option value="pengeluaran">Pengeluaran</option>
                                <option value="psb">PSB Pasang Baru</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" id="add_kategori" class="form-select" required>
                            <option value="Gaji Pegawai">Gaji Pegawai</option>
                            <option value="Pembelian Alat">Pembelian Alat</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                        <input type="text" id="add_kategori_custom" class="form-control mt-2 d-none" placeholder="Tulis kategori kustom...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah (Rupiah)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="jumlah" class="form-control" placeholder="100000" min="0" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Keterangan / Deskripsi</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Detail pengeluaran atau pembayaran pemasangan baru..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transaksi Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="editForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Transaksi Keuangan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipe Transaksi</label>
                            <select name="tipe" id="edit_tipe" class="form-select" required>
                                <option value="pengeluaran">Pengeluaran</option>
                                <option value="psb">PSB Pasang Baru</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" id="edit_kategori" class="form-select" required>
                            <option value="Gaji Pegawai">Gaji Pegawai</option>
                            <option value="Pembelian Alat">Pembelian Alat</option>
                            <option value="PSB Pasang Baru">PSB Pasang Baru</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah (Rupiah)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="jumlah" id="edit_jumlah" class="form-control" min="0" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Keterangan / Deskripsi</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addTipe = document.getElementById('add_tipe');
    const addKategori = document.getElementById('add_kategori');

    // Dynamic categories based on transaction type for Add modal
    addTipe.addEventListener('change', function() {
        addKategori.innerHTML = '';
        if (this.value === 'pengeluaran') {
            addKategori.innerHTML = `
                <option value="Gaji Pegawai">Gaji Pegawai</option>
                <option value="Pembelian Alat">Pembelian Alat</option>
                <option value="Lain-lain">Lain-lain</option>
            `;
        } else {
            addKategori.innerHTML = `
                <option value="PSB Pasang Baru">PSB Pasang Baru</option>
                <option value="Lain-lain">Lain-lain</option>
            `;
        }
    });

    const editTipe = document.getElementById('edit_tipe');
    const editKategori = document.getElementById('edit_kategori');

    // Dynamic categories based on transaction type for Edit modal
    editTipe.addEventListener('change', function() {
        const val = this.value;
        const currentKategori = editKategori.value;
        editKategori.innerHTML = '';
        if (val === 'pengeluaran') {
            editKategori.innerHTML = `
                <option value="Gaji Pegawai">Gaji Pegawai</option>
                <option value="Pembelian Alat">Pembelian Alat</option>
                <option value="Lain-lain">Lain-lain</option>
            `;
        } else {
            editKategori.innerHTML = `
                <option value="PSB Pasang Baru">PSB Pasang Baru</option>
                <option value="Lain-lain">Lain-lain</option>
            `;
        }
    });

    // Populate values in Edit modal
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const tipe = this.getAttribute('data-tipe');
            const kategori = this.getAttribute('data-kategori');
            const jumlah = this.getAttribute('data-jumlah');
            const keterangan = this.getAttribute('data-keterangan');
            const tanggal = this.getAttribute('data-tanggal');

            document.getElementById('editForm').action = `/keuangan/${id}`;
            editTipe.value = tipe;
            
            // Trigger change event to populate correct options
            editTipe.dispatchEvent(new Event('change'));

            // Find or append custom category if not in dropdown list
            let optExists = false;
            for (let i = 0; i < editKategori.options.length; i++) {
                if (editKategori.options[i].value === kategori) {
                    optExists = true;
                    break;
                }
            }
            if (!optExists) {
                const opt = document.createElement('option');
                opt.value = kategori;
                opt.text = kategori;
                editKategori.appendChild(opt);
            }
            
            editKategori.value = kategori;
            document.getElementById('edit_jumlah').value = Math.round(jumlah);
            document.getElementById('edit_keterangan').value = keterangan;
            document.getElementById('edit_tanggal').value = tanggal;
        });
    });
});
</script>
@endsection

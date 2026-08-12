@extends('layouts/contentNavbarLayout')

@section('title', 'Manajemen Upgrade Paket')

@section('page-style')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Keuangan /</span> Manajemen Upgrade Paket</h4>

<div class="row">
    <!-- Left Column: Upgrade Requests Table -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="bx bx-list-ol text-primary me-2"></i>Daftar Pengajuan Upgrade</h5>
                
                <form action="{{ route('upgrade-paket.index') }}" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari pelanggan..." value="{{ request('search') }}">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Status --</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Batal</option>
                    </select>
                    @if(request('search') || request('status'))
                        <a href="{{ route('upgrade-paket.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-x"></i></a>
                    @endif
                </form>
            </div>
            
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>Paket Lama ➔ Baru</th>
                            <th>Biaya Baru</th>
                            <th>Tagihan / Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($upgrades as $up)
                            <tr>
                                <td>
                                    <strong>{{ $up->pelanggan->nama_pelanggan }}</strong><br>
                                    <small class="text-muted">{{ $up->pelanggan->kode_pelanggan }}</small>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $up->paket_lama }}</span>
                                    <i class="bx bx-right-arrow-alt mx-1 text-primary"></i>
                                    <strong class="text-primary">{{ $up->paket_baru }}</strong>
                                </td>
                                <td>
                                    Rp {{ number_format($up->harga_baru, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if($up->status == 'completed')
                                        <span class="badge bg-label-success">Selesai</span>
                                        @if($up->tagihan)
                                            <br><small class="text-muted">Lunas via {{ $up->tagihan->metode_pembayaran ?? 'System' }}</small>
                                        @endif
                                    @elseif($up->status == 'pending')
                                        <span class="badge bg-label-warning">Pending</span>
                                        @if($up->tagihan)
                                            @if($up->tagihan->bukti_bayar)
                                                <br><span class="badge bg-label-info mt-1">Bukti Transfer Ada</span>
                                            @else
                                                <br><small class="text-muted">Menunggu Pembayaran</small>
                                            @endif
                                        @endif
                                    @elseif($up->status == 'cancelled')
                                        <span class="badge bg-label-danger">Batal</span>
                                    @else
                                        <span class="badge bg-label-info">{{ $up->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-inline-flex gap-1">
                                        @if($up->status == 'pending')
                                            @if($up->tagihan)
                                                @if($up->tagihan->bukti_bayar)
                                                    <a href="{{ route('billing.index', ['search' => $up->pelanggan->kode_pelanggan]) }}" class="btn btn-xs btn-success" title="Verifikasi Pembayaran & Selesaikan Upgrade">
                                                        <i class="bx bx-check me-1"></i> Verifikasi TF
                                                    </a>
                                                @else
                                                    <form action="{{ route('billing.pay-cash', $up->tagihan->id_tagihan) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-xs btn-outline-success" title="Bayar Tunai & Selesaikan Upgrade">
                                                            <i class="bx bx-money me-1"></i> Cash
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                            
                                            <form action="{{ route('upgrade-paket.cancel', $up->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengajuan upgrade ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Batalkan Pengajuan">
                                                    <i class="bx bx-x"></i> Batal
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Tidak ada aksi</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Tidak ada pengajuan upgrade yang ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer py-3">
                {{ $upgrades->links() }}
            </div>
        </div>
    </div>

    <!-- Right Column: Direct Upgrade Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="bx bx-rocket text-success me-2"></i>Upgrade Langsung (Admin)</h5>
            </div>
            <div class="card-body pt-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('upgrade-paket.admin-upgrade') }}" method="POST" id="formAdminUpgrade">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Pilih Pelanggan</label>
                        <select name="id_pelanggan" id="id_pelanggan" class="form-select border-primary" required>
                            <option value="">-- Cari & Pilih Pelanggan --</option>
                            @foreach($allPelanggan as $p)
                                <option value="{{ $p->id_pelanggan }}" data-paket="{{ $p->paket ?: 'None' }}" data-harga="{{ $p->harga_layanan ?: 0 }}">
                                    [{{ $p->kode_pelanggan }}] {{ $p->nama_pelanggan }} ({{ $p->paket ?: 'Umum' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Current Plan details card (rendered dynamically on select) -->
                    <div class="bg-light p-3 rounded mb-3 border d-none" id="adminCurrentPlanCard">
                        <span class="small text-muted d-block">Paket Saat Ini:</span>
                        <strong class="text-dark d-block" id="adminCurrentPlanText">None</strong>
                        <span class="small text-muted d-block mt-1">Biaya:</span>
                        <span class="text-dark" id="adminCurrentPriceText">Rp 0</span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-dark">Pilih Paket WiFi Baru</label>
                        <select name="paket_baru" id="paket_baru" class="form-select border-primary" required>
                            <option value="">-- Pilih Paket Baru --</option>
                            @foreach($packages as $name => $price)
                                <option value="{{ $name }}">
                                    {{ $name }} - Rp {{ number_format($price, 0, ',', '.') }}/bulan
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-warning py-2 mb-4 small">
                        <i class="bx bx-info-circle me-1"></i> Tindakan ini akan <strong>langsung mengubah</strong> paket pelanggan di database, menyinkronkan profil kecepatan baru ke router MikroTik, dan membuat tagihan LUNAS untuk periode bulan ini.
                    </div>

                    <button type="submit" class="btn btn-success w-100 shadow-sm">
                        <i class="bx bx-check-double me-1"></i> Proses Upgrade Langsung
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize TomSelect for searchable customer dropdown
    const selectPelanggan = new TomSelect('#id_pelanggan', {
        create: false,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });

    const selectPelangganElement = document.getElementById('id_pelanggan');
    const adminCurrentPlanCard = document.getElementById('adminCurrentPlanCard');
    const adminCurrentPlanText = document.getElementById('adminCurrentPlanText');
    const adminCurrentPriceText = document.getElementById('adminCurrentPriceText');

    if (selectPelangganElement) {
        selectPelangganElement.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const currentPaket = selectedOption.getAttribute('data-paket');
            const currentHarga = selectedOption.getAttribute('data-harga');

            if (currentPaket) {
                adminCurrentPlanText.textContent = currentPaket;
                adminCurrentPriceText.textContent = 'Rp ' + parseInt(currentHarga).toLocaleString('id-ID');
                adminCurrentPlanCard.classList.remove('d-none');
            } else {
                adminCurrentPlanCard.classList.add('d-none');
            }
        });
    }
});
</script>
@endsection

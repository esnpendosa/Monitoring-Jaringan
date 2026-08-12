@extends('layouts/contentNavbarLayout')

@section('title', 'Upgrade Paket WiFi')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Koneksi /</span> Upgrade Paket</h4>

<div class="row">
    <!-- Left Column: Current Package & Upgrade Form -->
    <div class="col-md-7 mb-4">
        <!-- Current Package Info -->
        <div class="card bg-label-primary border-0 shadow-sm mb-4">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <span class="badge bg-primary mb-2">Paket Aktif Anda</span>
                    <h3 class="fw-bold text-primary mb-1">{{ $pelanggan->paket ?: 'Umum' }}</h3>
                    <p class="mb-0 text-muted">
                        Biaya Bulanan: <strong class="text-dark">Rp {{ number_format($pelanggan->harga_layanan ?: 100000, 0, ',', '.') }}</strong>
                    </p>
                </div>
                <div class="d-none d-sm-block text-primary">
                    <i class="bx bx-wifi fs-1" style="font-size: 4.5rem !important; opacity: 0.8;"></i>
                </div>
            </div>
        </div>

        @if($pendingUpgrade)
            <!-- Pending Request Alert -->
            <div class="card border-warning border shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="avatar bg-label-warning p-2 rounded me-3">
                            <i class="bx bx-time-five text-warning fs-3"></i>
                        </span>
                        <div>
                            <h5 class="mb-0 text-warning fw-bold">Upgrade Sedang Diproses</h5>
                            <small class="text-muted">Diajukan pada {{ $pendingUpgrade->created_at->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                    
                    <p class="text-dark">
                        Anda telah mengajukan upgrade dari paket <strong>{{ $pendingUpgrade->paket_lama }}</strong> ke <strong>{{ $pendingUpgrade->paket_baru }}</strong> dengan biaya <strong>Rp {{ number_format($pendingUpgrade->harga_baru, 0, ',', '.') }}</strong>.
                    </p>
                    
                    @if($pendingUpgrade->tagihan)
                        <div class="bg-light p-3 rounded mb-3 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="small text-muted d-block">Nominal Tagihan Upgrade</span>
                                    <strong class="text-dark">Rp {{ number_format($pendingUpgrade->tagihan->jumlah, 0, ',', '.') }}</strong>
                                </div>
                                <span class="badge bg-label-warning">Belum Terbayar</span>
                            </div>
                            
                            @if($pendingUpgrade->tagihan->bukti_bayar)
                                <div class="mt-2 text-info small">
                                    <i class="bx bx-check-circle me-1"></i> Bukti pembayaran telah diunggah. Menunggu konfirmasi verifikasi admin.
                                </div>
                            @endif
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="{{ route('billing.index') }}" class="btn btn-primary btn-sm">
                                <i class="bx bx-credit-card me-1"></i> Bayar Tagihan / Upload Bukti
                            </a>
                            <form action="{{ route('upgrade-paket.cancel', $pendingUpgrade->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengajuan upgrade ini?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bx bx-x me-1"></i> Batalkan Pengajuan
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-danger py-2 small">
                            Galat: Tagihan untuk upgrade ini tidak ditemukan. Silakan hubungi admin.
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Upgrade Form -->
            <div class="card shadow-sm border-0">
                <div class="card-header border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bx bx-rocket text-primary me-2"></i>Ajukan Upgrade Paket</h5>
                </div>
                <div class="card-body pt-4">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('upgrade-paket.request') }}" method="POST" id="formRequestUpgrade">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label text-dark fw-semibold">Pilih Paket WiFi Baru</label>
                            <select name="paket_baru" id="paket_baru" class="form-select form-select-lg border-primary" required>
                                <option value="">-- Pilih Paket Upgrade --</option>
                                @foreach($packages as $name => $price)
                                    @if($name !== $pelanggan->paket)
                                        <option value="{{ $name }}" data-price="{{ $price }}">
                                            {{ $name }} - Rp {{ number_format($price, 0, ',', '.') }}/bulan
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Hanya paket dengan kecepatan dan harga yang berbeda yang tersedia.</small>
                        </div>

                        <!-- Price summary placeholder card -->
                        <div class="bg-light p-3 rounded mb-4 border d-none" id="priceSummaryCard">
                            <h6 class="fw-bold mb-2">Rincian Upgrade</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Biaya Paket Baru:</span>
                                <span class="fw-semibold text-dark" id="summaryNewPrice">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Biaya Paket Lama:</span>
                                <span class="fw-semibold text-muted" id="summaryOldPrice">Rp {{ number_format($pelanggan->harga_layanan ?: 100000, 0, ',', '.') }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="alert alert-info py-2 mb-0 small">
                                <i class="bx bx-info-circle me-1"></i> Setelah mengajukan, tagihan upgrade akan diterbitkan. Upgrade akan aktif secara otomatis di router dan sistem setelah pembayaran lunas diverifikasi.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                            <i class="bx bx-check-circle me-1"></i> Ajukan Upgrade & Terbitkan Tagihan
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- Right Column: Flow Info & History -->
    <div class="col-md-5 mb-4">
        <!-- Upgrade Flow Guide -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="bx bx-git-commit text-success me-2"></i>Alur Upgrade Paket</h5>
            </div>
            <div class="card-body pt-4">
                <div class="d-flex mb-3">
                    <span class="badge bg-label-primary p-2 rounded me-3 h-100 align-self-start">1</span>
                    <div>
                        <h6 class="fw-bold mb-1">Pilih & Ajukan</h6>
                        <small class="text-muted">Pilih paket baru yang Anda inginkan dari formulir pengajuan.</small>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <span class="badge bg-label-primary p-2 rounded me-3 h-100 align-self-start">2</span>
                    <div>
                        <h6 class="fw-bold mb-1">Bayar Tagihan</h6>
                        <small class="text-muted">Tagihan upgrade paket akan otomatis diterbitkan. Lakukan transfer bank/E-Wallet atau pembayaran otomatis.</small>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <span class="badge bg-label-primary p-2 rounded me-3 h-100 align-self-start">3</span>
                    <div>
                        <h6 class="fw-bold mb-1">Verifikasi & Otomatis Aktif</h6>
                        <small class="text-muted">Setelah pembayaran lunas, sistem dan router MikroTik akan otomatis memperbarui profil kecepatan WiFi Anda tanpa downtime.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="bx bx-history text-muted me-2"></i>Riwayat Upgrade</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($upgrades as $up)
                            <tr>
                                <td>
                                    <strong>{{ $up->paket_baru }}</strong><br>
                                    <small class="text-muted">Dari: {{ $up->paket_lama }}</small>
                                </td>
                                <td>
                                    {{ $up->created_at->format('d/m/Y') }}
                                </td>
                                <td>
                                    @if($up->status == 'completed')
                                        <span class="badge bg-label-success">Selesai</span>
                                    @elseif($up->status == 'pending')
                                        <span class="badge bg-label-warning">Pending</span>
                                    @elseif($up->status == 'cancelled')
                                        <span class="badge bg-label-danger">Batal</span>
                                    @else
                                        <span class="badge bg-label-info">{{ $up->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-3 text-muted">Belum ada riwayat upgrade.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectPaket = document.getElementById('paket_baru');
    const priceSummaryCard = document.getElementById('priceSummaryCard');
    const summaryNewPrice = document.getElementById('summaryNewPrice');

    if (selectPaket) {
        selectPaket.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            
            if (price) {
                summaryNewPrice.textContent = 'Rp ' + parseInt(price).toLocaleString('id-ID');
                priceSummaryCard.classList.remove('d-none');
            } else {
                priceSummaryCard.classList.add('d-none');
            }
        });
    }
});
</script>
@endsection

@extends('layouts/contentNavbarLayout')

@section('title', 'Manajemen Tagihan')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Keuangan /</span> Daftar Tagihan</h4>

@php
    $gwEnabled = \App\Models\Setting::get('payment_gateway_enabled', '1') == '1';
    $manualEnabled = \App\Models\Setting::get('manual_payment_enabled', '1') == '1';
    $bankInfo = \App\Models\Setting::get('manual_bank_info', '');
    
    // Summary Stats
    $user = auth()->user();
    $roleName = $user->role ? $user->role->name : 'Pelanggan';
    $isPelanggan = ($roleName === 'Pelanggan' || $user->id_role == 4);
    
    if ($isPelanggan) {
        $totalTagihan = \App\Models\Tagihan::whereHas('pelanggan', fn($q) => $q->where('id_user', $user->id))->count();
        $totalPaid = \App\Models\Tagihan::where('status', 'paid')->whereHas('pelanggan', fn($q) => $q->where('id_user', $user->id))->count();
        $totalUnpaid = \App\Models\Tagihan::where('status', 'unpaid')->whereHas('pelanggan', fn($q) => $q->where('id_user', $user->id))->count();
        
        $totalCash = \App\Models\Tagihan::where('status', 'paid')->where('metode_pembayaran', 'Cash')->whereHas('pelanggan', fn($q) => $q->where('id_user', $user->id))->sum('jumlah');
        $totalTf = \App\Models\Tagihan::where('status', 'paid')->where('metode_pembayaran', '!=', 'Cash')->whereHas('pelanggan', fn($q) => $q->where('id_user', $user->id))->sum('jumlah');
        
        $countCash = \App\Models\Tagihan::where('status', 'paid')->where('metode_pembayaran', 'Cash')->whereHas('pelanggan', fn($q) => $q->where('id_user', $user->id))->count();
        $countTf = \App\Models\Tagihan::where('status', 'paid')->where('metode_pembayaran', '!=', 'Cash')->whereHas('pelanggan', fn($q) => $q->where('id_user', $user->id))->count();
    } else {
        $totalTagihan = \App\Models\Tagihan::count();
        $totalPaid = \App\Models\Tagihan::where('status', 'paid')->count();
        $totalUnpaid = \App\Models\Tagihan::where('status', 'unpaid')->count();
        
        $totalCash = \App\Models\Tagihan::where('status', 'paid')->where('metode_pembayaran', 'Cash')->sum('jumlah');
        $totalTf = \App\Models\Tagihan::where('status', 'paid')->where('metode_pembayaran', '!=', 'Cash')->sum('jumlah');
        
        $countCash = \App\Models\Tagihan::where('status', 'paid')->where('metode_pembayaran', 'Cash')->count();
        $countTf = \App\Models\Tagihan::where('status', 'paid')->where('metode_pembayaran', '!=', 'Cash')->count();
    }
@endphp

@if(!$isPelanggan)
<div class="row mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-border-shadow-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-list-ul"></i></span>
                    </div>
                    <h4 class="ms-1 mb-0">{{ $totalTagihan }}</h4>
                </div>
                <p class="mb-1">Total Semua Tagihan</p>
                <p class="mb-0">
                    <small class="text-muted">Akumulasi seluruh periode</small>
                </p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-border-shadow-success h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-success"><i class="bx bx-check-circle"></i></span>
                    </div>
                    <h4 class="ms-1 mb-0">{{ $totalPaid }}</h4>
                </div>
                <p class="mb-1">Tagihan Lunas</p>
                <p class="mb-0">
                    <small class="text-success fw-semibold">{{ round(($totalPaid / max($totalTagihan, 1)) * 100) }}%</small>
                    <small class="text-muted"> dari total tagihan</small>
                </p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-border-shadow-warning h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-time-five"></i></span>
                    </div>
                    <h4 class="ms-1 mb-0">{{ $totalUnpaid }}</h4>
                </div>
                <p class="mb-1">Belum Terbayar</p>
                <p class="mb-0">
                    <small class="text-danger fw-semibold">{{ $totalUnpaid }}</small>
                    <small class="text-muted"> perlu ditindaklanjuti</small>
                </p>
            </div>
        </div>
    </div>
</div>
@endif

@if(!$isPelanggan)
<div class="row mb-4">
    <div class="col-sm-6 col-lg-6 mb-3 mb-sm-0">
        <div class="card card-border-shadow-success h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-success"><i class="bx bx-money text-success" style="font-size: 1.5rem;"></i></span>
                    </div>
                    <h4 class="ms-1 mb-0 text-success fw-bold">Rp {{ number_format($totalCash, 0, ',', '.') }}</h4>
                </div>
                <p class="mb-1 fw-semibold text-dark">Total Pembayaran Cash</p>
                <p class="mb-0">
                    <small class="text-muted">Diterima dari <strong>{{ $countCash }}</strong> tagihan tunai lunas</small>
                </p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-6">
        <div class="card card-border-shadow-info h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                    <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-info"><i class="bx bx-credit-card-front text-info" style="font-size: 1.5rem;"></i></span>
                    </div>
                    <h4 class="ms-1 mb-0 text-info fw-bold">Rp {{ number_format($totalTf, 0, ',', '.') }}</h4>
                </div>
                <p class="mb-1 fw-semibold text-dark">Total Pembayaran Transfer / Gateway</p>
                <p class="mb-0">
                    <small class="text-muted">Diterima dari <strong>{{ $countTf }}</strong> tagihan non-cash lunas</small>
                </p>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-header border-bottom p-0">
        <div class="nav-align-top">
            <ul class="nav nav-tabs nav-fill" role="tablist">
                <li class="nav-item">
                    <a href="{{ route('billing.index') }}" class="nav-link {{ !request('status') ? 'active' : '' }}">
                        <i class="tf-icons bx bx-list-ul me-1"></i> Semua Tagihan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('billing.index', ['status' => 'paid']) }}" class="nav-link {{ request('status') == 'paid' ? 'active' : '' }}">
                        <i class="tf-icons bx bx-check-circle me-1"></i> Sudah Bayar (Lunas)
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('billing.index', ['status' => 'unpaid']) }}" class="nav-link {{ request('status') == 'unpaid' ? 'active' : '' }}">
                        <i class="tf-icons bx bx-time-five me-1"></i> Belum Bayar
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="card-body pt-4">
        <div class="d-flex flex-column gap-3 mb-4">
            <!-- Row 1: Title and Action Buttons -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Daftar Tagihan Pelanggan</h5>
                @if(auth()->user()->id_role == 1)
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnKosongkanSemua">
                        <i class="bx bx-trash me-1"></i> Kosongkan Semua
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahTagihan">
                        <i class="bx bx-plus me-1"></i> Input Tagihan Manual
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGenerateTagihan">
                        <i class="bx bx-plus-circle me-1"></i> Buat Tagihan Baru
                    </button>
                </div>
                @endif
            </div>

            <!-- Row 2: Filters and Search Input -->
            @if(!$isPelanggan)
            <div>
                <form action="{{ route('billing.index') }}" method="GET" class="row g-2 align-items-center" id="searchBillingForm">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif

                    <div class="col-12 col-md-3">
                        <select name="filter_khusus" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Filter Khusus --</option>
                            <option value="unpaid_3_months" {{ request('filter_khusus') == 'unpaid_3_months' ? 'selected' : '' }}>Belum Bayar >= 3 Bulan</option>
                            <option value="unpaid_2_months" {{ request('filter_khusus') == 'unpaid_2_months' ? 'selected' : '' }}>Belum Bayar >= 2 Bulan</option>
                            <option value="paid_3_months" {{ request('filter_khusus') == 'paid_3_months' ? 'selected' : '' }}>Sudah Bayar >= 3 Bulan Sekaligus</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select name="filter_bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Bulan --</option>
                            @for($i=1; $i<=12; $i++)
                            <option value="{{ $i }}" {{ request('filter_bulan') == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select name="filter_tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Tahun --</option>
                            @for($y=now()->year; $y>=2020; $y--)
                            <option value="{{ $y }}" {{ request('filter_tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-12 col-md-5">
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, kode, IP, alamat..." value="{{ request('search') }}">
                            @if(request('search') || request('filter_khusus') || request('filter_bulan') || request('filter_tahun'))
                            <a href="{{ route('billing.index', ['status' => request('status')]) }}" class="input-group-text bg-transparent border-start-0 text-muted" title="Bersihkan Pencarian">
                                <i class="bx bx-x"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                    <button type="submit" style="display: none;"></button>
                </form>
            </div>
            @endif
        </div>

    <!-- Modal Generate Tagihan -->
    @if(auth()->user()->id_role == 1)
    <div class="modal fade" id="modalGenerateTagihan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('billing.sync') }}" method="GET" id="formGenerateTagihan">
                    <div class="modal-header">
                        <h5 class="modal-title">Generate Tagihan Bulanan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            Pilih periode dan metode pembuatan tagihan di bawah ini.
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Bulan Periode</label>
                                <select name="bulan" class="form-select">
                                    @for($i=1; $i<=12; $i++)
                                    <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tahun Periode</label>
                                <input type="number" name="tahun" class="form-control" value="{{ now()->year }}" required>
                            </div>
                        </div>
                        
                        <div class="mb-4 mt-3">
                            <label class="form-label d-block">Pilih Metode Pembuatan:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="mode" id="modeRange" value="range" checked onchange="toggleMode()">
                                <label class="form-check-label" for="modeRange">Berdasarkan Tanggal</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="mode" id="modeUser" value="user" onchange="toggleMode()">
                                <label class="form-check-label" for="modeUser">Per Pelanggan</label>
                            </div>
                        </div>

                        <div id="sectionRange">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Dari Tanggal (Billing)</label>
                                    <select name="date_start" class="form-select">
                                        @for($i=1; $i<=28; $i++)
                                        <option value="{{ $i }}">Tanggal {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Sampai Tanggal (Billing)</label>
                                    <select name="date_end" class="form-select">
                                        @for($i=1; $i<=28; $i++)
                                        <option value="{{ $i }}" {{ $i == 28 ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Contoh: Pilih 1 - 10 untuk memproses pelanggan yang jatuh tempo di awal bulan.</small>
                        </div>

                        <div id="sectionUser" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Pilih Pelanggan</label>
                                <select name="id_pelanggan" id="id_pelanggan" class="form-select">
                                    <option value="">-- Pilih Pelanggan --</option>
                                    @foreach($allPelanggan as $p)
                                    <option value="{{ $p->id_pelanggan }}">[{{ $p->kode_pelanggan }}] {{ $p->nama_pelanggan }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal & Jam Terbit (Opsional)</label>
                                <input type="datetime-local" name="created_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                                <small class="text-muted">Jika dikosongkan, akan menggunakan waktu sekarang.</small>
                            </div>
                        </div>
                        <!-- Safety check box to prevent accidental generation of bills -->
                        <div class="mb-3 form-check bg-label-warning p-3 rounded ms-3 me-3 border border-warning shadow-sm">
                            <input type="checkbox" class="form-check-input ms-0" id="confirmGenerateCheck" onchange="toggleGenerateButton()">
                            <label class="form-check-label ms-2 text-dark fw-semibold" for="confirmGenerateCheck">
                                Saya yakin & setuju ingin memproses pembuatan tagihan baru ini.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="submitGenerateBtn" class="btn btn-primary" disabled>
                            <i class="bx bx-check-circle me-1"></i> Proses & Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Tagihan Manual -->
    @if(auth()->user()->id_role == 1)
    <div class="modal fade" id="modalTambahTagihan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('billing.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Input Tagihan Manual (Riwayat/Baru)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pelanggan</label>
                            <select name="id_pelanggan" id="tambah_id_pelanggan" class="form-select" required onchange="updateDefaultJumlah()">
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach($allPelanggan as $p)
                                <option value="{{ $p->id_pelanggan }}" data-harga="{{ $p->harga_layanan }}">[{{ $p->kode_pelanggan }}] {{ $p->nama_pelanggan }} - Rp{{ number_format($p->harga_layanan, 0, ',', '.') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Bulan</label>
                                <select name="bulan" class="form-select" required>
                                    @for($i=1; $i<=12; $i++)
                                    <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tahun</label>
                                <input type="number" name="tahun" class="form-control" value="{{ now()->year }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Tagihan (Rp)</label>
                            <input type="number" name="jumlah" id="tambah_jumlah" class="form-control" required placeholder="Contoh: 150000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Pembayaran</label>
                            <select name="status" id="tambah_status" class="form-select" required onchange="toggleTambahPaidSection()">
                                <option value="unpaid">Belum Bayar</option>
                                <option value="paid">Sudah Bayar (Lunas)</option>
                                <option value="pending">Pending / Menunggu Verifikasi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="bayar_di_awal" id="tambah_bayar_di_awal" value="1">
                                <label class="form-check-label" for="tambah_bayar_di_awal">Bayar di Awal (Bulan Selanjutnya)</label>
                            </div>
                            <small class="text-muted">Centang jika ini adalah pembayaran di awal untuk bulan selanjutnya agar tidak dihitung sebagai pemasukan bulan tersebut.</small>
                        </div>
                        <div id="tambahPaidSection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <select name="metode_pembayaran" class="form-select">
                                    @foreach(explode(',', \App\Models\Setting::get('manual_payment_methods', 'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay')) as $method)
                                        <option value="{{ trim($method) }}">{{ trim($method) }}</option>
                                    @endforeach
                                    <option value="Midtrans">Midtrans (Otomatis)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Waktu Pembayaran (Paid At)</label>
                                <input type="datetime-local" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Admin</label>
                            <textarea name="catatan_admin" class="form-control" rows="2" placeholder="Contoh: Pembayaran bulan lalu manual via cash"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Tagihan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script>
        function toggleMode() {
            const mode = document.querySelector('input[name="mode"]:checked').value;
            if(mode === 'range') {
                document.getElementById('sectionRange').style.display = 'block';
                document.getElementById('sectionUser').style.display = 'none';
            } else {
                document.getElementById('sectionRange').style.display = 'none';
                document.getElementById('sectionUser').style.display = 'block';
            }
        }
    </script>
    @endif
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>Bulan/Tahun</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse($tagihan as $t)
                <tr>
                    <td>
                        <strong>{{ $t->pelanggan->nama_pelanggan }}</strong><br>
                        <small class="text-muted">{{ $t->pelanggan->kode_pelanggan }}</small>
                    </td>
                    <td>{{ date('F', mktime(0, 0, 0, $t->bulan, 10)) }} {{ $t->tahun }}</td>
                    <td>
                        Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                        @if(auth()->user()->id_role == 1 && $t->status != 'paid')
                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modalAmount{{ $t->id_tagihan }}" class="ms-1 text-warning">
                            <i class="bx bx-edit-alt"></i>
                        </a>
                        @endif
                    </td>
                    <td>
                        @if($t->status == 'paid')
                            <span class="badge bg-label-success">Lunas</span>
                            @if($t->bayar_di_awal)
                                <span class="badge bg-label-info ms-1">Bayar di Awal</span>
                            @endif
                            <br><small class="text-muted">{{ $t->paid_at }} via {{ $t->metode_pembayaran ?? 'System' }}</small>
                            @if($t->bukti_bayar && file_exists(storage_path('app/public/' . $t->bukti_bayar)))
                                <br><a href="{{ asset('storage/' . $t->bukti_bayar) }}" target="_blank" class="small text-info"><i class='bx bx-image-alt'></i> Lihat Bukti TF</a>
                                @php
                                    $canEditProof = auth()->user()->id_role == 1 || auth()->user()->id_role == 2 || 
                                                    ($t->pelanggan && $t->pelanggan->id_user == auth()->id());
                                @endphp
                                @if($canEditProof)
                                    <a href="{{ route('billing.edit-bukti-bayar', $t->id_tagihan) }}" class="small text-warning ms-2">
                                        <i class='bx bx-edit'></i> Edit Bukti
                                    </a>
                                @endif
                            @endif
                        @elseif($t->status == 'pending' || ($t->status == 'unpaid' && $t->bukti_bayar))
                            <span class="badge bg-label-info">Menunggu Verifikasi</span>
                            @if($t->bukti_bayar && file_exists(storage_path('app/public/' . $t->bukti_bayar)))
                                <br><a href="{{ asset('storage/' . $t->bukti_bayar) }}" target="_blank" class="small"><i class='bx bx-image'></i> Lihat Bukti</a>
                                @php
                                    $canEditProof = auth()->user()->id_role == 1 || auth()->user()->id_role == 2 || 
                                                    ($t->pelanggan && $t->pelanggan->id_user == auth()->id());
                                @endphp
                                @if($canEditProof)
                                    <a href="{{ route('billing.edit-bukti-bayar', $t->id_tagihan) }}" class="small text-warning ms-2">
                                        <i class='bx bx-edit'></i> Edit Bukti
                                    </a>
                                @endif
                            @endif
                        @elseif($t->status == 'unpaid')
                            <span class="badge bg-label-warning">Belum Bayar</span>
                        @else
                            <span class="badge bg-label-danger">Dibatalkan</span>
                        @endif
                    </td>
                    <td>
                        @if($t->status !== 'paid' && auth()->user()->id_role == 1)
                        <form action="{{ route('billing.pay-cash', $t->id_tagihan) }}" method="POST" style="display:inline-block;" class="me-1">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Terima Pembayaran Tunai (Cash)">
                                <i class="bx bx-money me-1"></i> Bayar Cash
                            </button>
                        </form>
                        @endif

                        @if($t->status == 'unpaid' && !$t->bukti_bayar)
                            <div class="btn-group">
                                @if($gwEnabled)
                                <button class="btn btn-sm btn-primary pay-button" data-id="{{ $t->id_tagihan }}">
                                    <i class="bx bx-credit-card me-1"></i> Bayar Otomatis
                                </button>
                                @endif
                                
                                @if($manualEnabled)
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalManual{{ $t->id_tagihan }}">
                                    <i class="bx bx-upload me-1"></i> Manual
                                </button>
                                @endif
                            </div>
                        @elseif($t->status == 'pending' || ($t->status == 'unpaid' && $t->bukti_bayar))
                            @if(auth()->user()->id_role == 1)
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalVerify{{ $t->id_tagihan }}">
                                <i class="bx bx-check me-1"></i> Verifikasi
                            </button>
                            @else
                            <button class="btn btn-sm btn-secondary" disabled>
                                <i class="bx bx-time-five me-1"></i> Diproses
                            </button>
                            @endif
                        @else
                            <div class="d-inline-flex gap-1">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-success" disabled>
                                        <i class="bx bx-check-circle me-1"></i> Selesai
                                    </button>
                                    <a href="{{ route('billing.receipt.pdf', $t->id_tagihan) }}" class="btn btn-sm btn-info" title="Download Nota PDF">
                                        <i class="bx bx-file"></i> Nota PDF
                                    </a>
                                </div>
                                @if(auth()->user()->id_role == 1 && $t->pelanggan->no_wa)
                                <form action="{{ route('billing.send-receipt-wa', $t->id_tagihan) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="Kirim Ulang Kwitansi via WhatsApp">
                                        <i class="bx bxl-whatsapp me-1"></i> Kirim WA
                                    </button>
                                </form>
                                @endif
                            </div>
                        @endif

                        @if(auth()->user()->id_role == 1)
                        <div class="d-inline-flex gap-1 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $t->id_tagihan }}">
                                <i class="bx bx-edit"></i>
                            </button>
                            <a href="{{ route('billing.destroy-direct', $t->id_tagihan) }}" class="btn btn-sm btn-outline-danger">
                                <i class="bx bx-trash" style="pointer-events: none;"></i>
                            </a>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">Belum ada data tagihan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Menampilkan {{ $tagihan->firstItem() ?? 0 }} - {{ $tagihan->lastItem() ?? 0 }} dari {{ $tagihan->total() }} tagihan
        </div>
        <div>
            {{ $tagihan->links() }}
        </div>
    </div>
</div>

<!-- Modals rendered outside of table to prevent click blocking overlay bugs -->
@foreach($tagihan as $t)
    <!-- Modal Manual Payment -->
    <div class="modal fade" id="modalManual{{ $t->id_tagihan }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('billing.confirm', $t->id_tagihan) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Pembayaran Manual</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Info Rekening:</strong><br>
                            {!! nl2br(e($bankInfo)) !!}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Metode</label>
                            @php
                                $methods = explode(',', \App\Models\Setting::get('manual_payment_methods', 'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay'));
                            @endphp
                            <select name="metode_pembayaran" class="form-select" required>
                                @foreach($methods as $method)
                                <option value="{{ trim($method) }}">{{ trim($method) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Bukti Bayar (Jika TF/QRIS)</label>
                            <div class="drop-zone" id="dropZone{{ $t->id_tagihan }}"
                                 style="border: 2px dashed #adb5bd; border-radius: 8px; padding: 24px; text-align: center; cursor: pointer; transition: background .2s, border-color .2s; background: #f8f9fa;">
                                <div class="drop-zone__prompt" id="dropPrompt{{ $t->id_tagihan }}">
                                    <i class='bx bx-cloud-upload' style="font-size:2rem; color:#6c757d;"></i>
                                    <div class="mt-1 text-muted small">Drag &amp; drop gambar di sini, atau <span class="text-primary fw-semibold">pilih file</span></div>
                                    <div class="text-muted" style="font-size:0.75rem;">JPG, PNG, GIF, PDF — maks 3MB</div>
                                </div>
                                <div class="drop-zone__preview d-none" id="dropPreview{{ $t->id_tagihan }}">
                                    <img id="dropImg{{ $t->id_tagihan }}" src="" alt="preview" style="max-height:140px; max-width:100%; border-radius:6px; object-fit:contain;">
                                    <div class="mt-1 small text-muted" id="dropName{{ $t->id_tagihan }}"></div>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-1 drop-zone__clear" data-id="{{ $t->id_tagihan }}">Hapus</button>
                                </div>
                                <input type="file" name="bukti_bayar" id="buktiFile{{ $t->id_tagihan }}"
                                       class="d-none" accept="image/jpeg,image/jpg,image/png,image/gif,application/pdf">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Kirim Bukti</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Verify (Admin) -->
    @if(auth()->user()->id_role == 1)
    <div class="modal fade" id="modalVerify{{ $t->id_tagihan }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('billing.verify', $t->id_tagihan) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Verifikasi Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($t->bukti_bayar && file_exists(storage_path('app/public/' . $t->bukti_bayar)))
                        <div class="mb-3 text-center">
                            <img src="{{ asset('storage/' . $t->bukti_bayar) }}" class="img-fluid rounded border" style="max-height: 400px">
                        </div>
                        @else
                        <div class="mb-3 text-center py-4 bg-light rounded border text-muted">
                            <i class="bx bx-image-alt" style="font-size: 3rem;"></i>
                            <div class="small mt-1">Bukti Transfer Fisik Tidak Tersedia (Hanya Catatan Sistem)</div>
                        </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-select" required>
                                @foreach(explode(',', \App\Models\Setting::get('manual_payment_methods', 'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay')) as $method)
                                    <option value="{{ trim($method) }}" {{ $t->metode_pembayaran == trim($method) ? 'selected' : '' }}>{{ trim($method) }}</option>
                                @endforeach
                                <option value="Midtrans" {{ $t->metode_pembayaran == 'Midtrans' ? 'selected' : '' }}>Midtrans (Otomatis)</option>
                                @if($t->metode_pembayaran && !in_array(trim($t->metode_pembayaran), array_map('trim', explode(',', \App\Models\Setting::get('manual_payment_methods', 'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay')))) && $t->metode_pembayaran != 'Midtrans')
                                    <option value="{{ $t->metode_pembayaran }}" selected>{{ $t->metode_pembayaran }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waktu Pembayaran (Sesuai Struk)</label>
                            <input type="datetime-local" name="paid_at" class="form-control" value="{{ $t->updated_at->format('Y-m-d\TH:i') }}" required>
                            <small class="text-muted">Default: Waktu pelanggan kirim bukti.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Admin</label>
                            <textarea name="catatan_admin" class="form-control" placeholder="Optional"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Verifikasi & Aktifkan WiFi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Modal Edit Amount (Admin) -->
    @if(auth()->user()->id_role == 1)
    <div class="modal fade" id="modalAmount{{ $t->id_tagihan }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('billing.amount.update', $t->id_tagihan) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Jumlah Tagihan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pelanggan</label>
                            <input type="text" class="form-control" value="{{ $t->pelanggan->nama_pelanggan }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Tagihan (IDR)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="jumlah" class="form-control" value="{{ $t->jumlah }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Update Jumlah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Edit (Admin) -->
    @if(auth()->user()->id_role == 1)
    <div class="modal fade" id="modalEdit{{ $t->id_tagihan }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('billing.update', $t->id_tagihan) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Detail Tagihan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Bulan</label>
                                <select name="bulan" class="form-select">
                                    @for($i=1; $i<=12; $i++)
                                    <option value="{{ $i }}" {{ $t->bulan == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tahun</label>
                                <input type="number" name="tahun" class="form-control" value="{{ $t->tahun }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Jumlah Tagihan (Rp)</label>
                                <input type="number" name="jumlah" class="form-control" value="{{ $t->jumlah }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tanggal Tagihan (Dibuat)</label>
                                <input type="datetime-local" name="created_at" class="form-control" value="{{ date('Y-m-d\TH:i', strtotime($t->created_at)) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" id="status{{ $t->id_tagihan }}" onchange="togglePaidAt({{ $t->id_tagihan }})">
                                    <option value="unpaid" {{ $t->status == 'unpaid' ? 'selected' : '' }}>Belum Bayar</option>
                                    <option value="paid" {{ $t->status == 'paid' ? 'selected' : '' }}>Lunas</option>
                                    <option value="pending" {{ $t->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="cancelled" {{ $t->status == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                                </select>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="bayar_di_awal" id="edit_bayar_di_awal{{ $t->id_tagihan }}" value="1" {{ $t->bayar_di_awal ? 'checked' : '' }}>
                                    <label class="form-check-label" for="edit_bayar_di_awal{{ $t->id_tagihan }}">Bayar di Awal (Bulan Selanjutnya)</label>
                                </div>
                                <small class="text-muted">Centang jika ini adalah pembayaran di awal untuk bulan selanjutnya agar tidak dihitung sebagai pemasukan bulan tersebut.</small>
                            </div>
                            <div class="col-12" id="paidAtSection{{ $t->id_tagihan }}" style="{{ $t->status == 'paid' ? '' : 'display:none' }}">
                                <label class="form-label">Waktu Pembayaran (Paid At)</label>
                                <input type="datetime-local" name="paid_at" class="form-control" value="{{ $t->paid_at ? date('Y-m-d\TH:i', strtotime($t->paid_at)) : '' }}">
                            </div>
                            <div class="col-12" id="metodePembayaranSection{{ $t->id_tagihan }}" style="{{ $t->status == 'paid' ? '' : 'display:none' }}">
                                <label class="form-label">Metode Pembayaran</label>
                                <select name="metode_pembayaran" class="form-select">
                                    <option value="">-- Pilih Metode --</option>
                                    @foreach(explode(',', \App\Models\Setting::get('manual_payment_methods', 'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay')) as $method)
                                        <option value="{{ trim($method) }}" {{ $t->metode_pembayaran == trim($method) ? 'selected' : '' }}>{{ trim($method) }}</option>
                                    @endforeach
                                    <option value="Midtrans" {{ $t->metode_pembayaran == 'Midtrans' ? 'selected' : '' }}>Midtrans (Otomatis)</option>
                                    @if($t->metode_pembayaran && !in_array(trim($t->metode_pembayaran), array_map('trim', explode(',', \App\Models\Setting::get('manual_payment_methods', 'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay')))) && $t->metode_pembayaran != 'Midtrans')
                                        <option value="{{ $t->metode_pembayaran }}" selected>{{ $t->metode_pembayaran }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

@endsection

@section('page-style')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="{{ \App\Models\Setting::get('midtrans_is_production', '0') == '1' ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ \App\Models\Setting::get('midtrans_client_key', config('services.midtrans.client_key')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Kosongkan Semua confirmation dialog
        const btnKosongkan = document.getElementById('btnKosongkanSemua');
        if (btnKosongkan) {
            btnKosongkan.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Kosongkan Semua Tagihan?',
                    text: "Seluruh data tagihan akan dihapus secara permanen dari database. Tindakan ini tidak dapat dibatalkan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff3e1d',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'Ya, Kosongkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('billing.delete-all-direct') }}";
                    }
                });
            });
        }
        if (document.getElementById('id_pelanggan')) {
            new TomSelect("#id_pelanggan", {
                create: false,
                placeholder: "-- Pilih Pelanggan --",
                dropdownParent: '#modalGenerateTagihan'
            });
        }

        if (document.getElementById('tambah_id_pelanggan')) {
            new TomSelect("#tambah_id_pelanggan", {
                create: false,
                placeholder: "-- Pilih Pelanggan --",
                dropdownParent: '#modalTambahTagihan'
            });
        }

        // Intercept Enter key inside search input to submit search form instead of generating bills
        const searchInput = document.querySelector('input[name="search"]');
        const searchForm = document.getElementById('searchBillingForm');
        if (searchInput && searchForm) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchForm.submit();
                }
            });
        }

        // Prevent Enter key inside billing generate form from triggering submit
        const formGen = document.getElementById('formGenerateTagihan');
        if (formGen) {
            formGen.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const tagName = e.target.tagName.toLowerCase();
                    if (tagName !== 'textarea' && e.target.type !== 'submit' && e.target.type !== 'button') {
                        e.preventDefault();
                        return false;
                    }
                }
            });

            formGen.addEventListener('submit', function(e) {
                const check = document.getElementById('confirmGenerateCheck');
                if (!check || !check.checked) {
                    e.preventDefault();
                    alert('Silakan centang kotak konfirmasi terlebih dahulu untuk memproses!');
                    return false;
                }
            });
        }
    });

    document.querySelectorAll('.pay-button').forEach(button => {
        button.addEventListener('click', function() {
            const tagihanId = this.getAttribute('data-id');
            
            fetch(`/billing/${tagihanId}/pay`)
                .then(response => response.json())
                .then(data => {
                    if (data.token) {
                        snap.pay(data.token, {
                            onSuccess: function(result) { location.reload(); },
                            onPending: function(result) { location.reload(); },
                            onError: function(result) { alert("Pembayaran gagal!"); }
                        });
                    } else if (data.error) {
                        alert("Error: " + data.error);
                    }
                });
        });
    });

    function togglePaidAt(id) {
        const status = document.getElementById('status' + id).value;
        const section = document.getElementById('paidAtSection' + id);
        const methodSection = document.getElementById('metodePembayaranSection' + id);
        if(status === 'paid') {
            if(section) section.style.display = 'block';
            if(methodSection) methodSection.style.display = 'block';
        } else {
            if(section) section.style.display = 'none';
            if(methodSection) methodSection.style.display = 'none';
        }
    }

    function toggleGenerateButton() {
        const check = document.getElementById('confirmGenerateCheck');
        const btn = document.getElementById('submitGenerateBtn');
        if (check && btn) {
            btn.disabled = !check.checked;
        }
    }

    function updateDefaultJumlah() {
        const select = document.getElementById('tambah_id_pelanggan');
        const selectedOption = select.options[select.selectedIndex];
        const harga = selectedOption.getAttribute('data-harga');
        const jumlahInput = document.getElementById('tambah_jumlah');
        if (harga) {
            jumlahInput.value = harga;
        } else {
            jumlahInput.value = '';
        }
    }

    function toggleTambahPaidSection() {
        const status = document.getElementById('tambah_status').value;
        const section = document.getElementById('tambahPaidSection');
        if (status === 'paid') {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    }

    // ── Drag & Drop Bukti Bayar ───────────────────────────────────────────────
    document.querySelectorAll('.drop-zone').forEach(function(zone) {
        const id      = zone.id.replace('dropZone', '');
        const input   = document.getElementById('buktiFile' + id);
        const prompt  = document.getElementById('dropPrompt' + id);
        const preview = document.getElementById('dropPreview' + id);
        const img     = document.getElementById('dropImg' + id);
        const name    = document.getElementById('dropName' + id);

        // Click zone → trigger file input
        zone.addEventListener('click', function(e) {
            if (!e.target.classList.contains('drop-zone__clear')) input.click();
        });

        // Drag visual feedback
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            zone.style.background = '#e8f4ff';
            zone.style.borderColor = '#0d6efd';
        });
        zone.addEventListener('dragleave', function() {
            zone.style.background = '#f8f9fa';
            zone.style.borderColor = '#adb5bd';
        });

        // Drop file
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.style.background = '#f8f9fa';
            zone.style.borderColor = '#adb5bd';
            const file = e.dataTransfer.files[0];
            if (file) setFile(file);
        });

        // Normal file input change
        input.addEventListener('change', function() {
            if (input.files[0]) setFile(input.files[0]);
        });

        // Clear button
        zone.addEventListener('click', function(e) {
            if (e.target.classList.contains('drop-zone__clear')) {
                input.value = '';
                prompt.classList.remove('d-none');
                preview.classList.add('d-none');
                img.src = '';
            }
        });

        function setFile(file) {
            // Validate size 3MB
            if (file.size > 3 * 1024 * 1024) {
                alert('Ukuran file maksimal 3MB');
                return;
            }
            // Transfer to real input via DataTransfer
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;

            name.textContent = file.name;
            prompt.classList.add('d-none');
            preview.classList.remove('d-none');

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) { img.src = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                // PDF — show icon instead
                img.src = '';
                img.alt = '';
                img.style.display = 'none';
                const pdfIcon = preview.querySelector('.pdf-icon');
                if (!pdfIcon) {
                    const div = document.createElement('div');
                    div.className = 'pdf-icon my-2';
                    div.innerHTML = "<i class='bx bxs-file-pdf' style='font-size:3rem;color:#dc3545;'></i>";
                    preview.insertBefore(div, name);
                }
            }
        }
    });
</script>
@endsection

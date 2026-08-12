@extends('layouts/contentNavbarLayout')

@section('title', 'Detail Alat - ' . $inventory->nama_alat)

@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card mb-4 overflow-hidden" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                <h5 class="mb-0 text-white"><i class="bx bx-wrench me-2"></i> Informasi Alat</h5>
                <span class="badge bg-white text-primary text-uppercase">{{ $inventory->status }}</span>
            </div>
            <div class="card-body pt-4">
                <div class="text-center mb-4">
                    @if($inventory->gambar_alat)
                    <img src="{{ asset('storage/' . $inventory->gambar_alat) }}" alt="alat" class="img-fluid rounded mb-3 shadow-sm" style="max-height: 150px; width: 100%; object-fit: cover;">
                    @else
                    <div class="avatar avatar-xl mx-auto bg-label-primary mb-3" style="width: 80px; height: 80px;">
                        <span class="avatar-initial rounded-circle fs-2"><i class="bx bx-package"></i></span>
                    </div>
                    @endif
                    <h4 class="mb-0">{{ $inventory->nama_alat }}</h4>
                    <span class="text-muted">{{ $inventory->kategori }} (Stok: {{ $inventory->stok }})</span>
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Tanggal Beli</span>
                        <span class="fw-bold">
                            @if($inventory->tanggal_beli)
                            {{ \Carbon\Carbon::parse($inventory->tanggal_beli)->format('d/m/Y') }}
                            @else
                            -
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Harga Satuan</span>
                        <span class="fw-bold text-success">
                            @if($inventory->harga_beli)
                            Rp {{ number_format($inventory->harga_beli / ($inventory->stok ?: 1), 0, ',', '.') }}
                            @else
                            -
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Total Harga Beli</span>
                        <span class="fw-bold text-success">
                            @if($inventory->harga_beli)
                            Rp {{ number_format($inventory->harga_beli, 0, ',', '.') }}
                            @else
                            -
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Merk</span>
                        <span class="fw-bold">{{ $inventory->merk ?? '-' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Serial Number</span>
                        <span class="fw-bold text-primary">{{ $inventory->serial_number ?? '-' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Kondisi</span>
                        @php
                            $kondisiClass = $inventory->kondisi == 'baik' ? 'bg-label-success' : ($inventory->kondisi == 'rusak' ? 'bg-label-danger' : 'bg-label-warning');
                        @endphp
                        <span class="badge {{ $kondisiClass }}">{{ ucfirst($inventory->kondisi) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Pemegang Saat Ini</span>
                        @if($inventory->id_teknisi)
                            <span class="fw-bold text-info"><i class="bx bx-user me-1"></i> {{ $inventory->technician->nama_teknisi }}</span>
                        @elseif($inventory->id_user)
                            <span class="fw-bold text-info"><i class="bx bx-user me-1"></i> {{ $inventory->user->name }}</span>
                        @else
                            <span class="text-muted italic">Tersedia di Gudang</span>
                        @endif
                    </li>
                </ul>

                @if($inventory->id_user && $inventory->user)
                <div class="mt-4 p-3 border rounded bg-light">
                    <h6 class="mb-2 small text-uppercase text-muted">Profil Pemegang</h6>
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <span class="avatar-initial rounded-circle bg-primary">{{ substr($inventory->user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $inventory->user->name }}</h6>
                            <small class="text-muted">{{ $inventory->user->role->name ?? 'User' }}</small>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-4">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#assignModal">
                        <i class="bx bx-transfer-alt me-1"></i> Ganti Alokasi / Pemegang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm" style="border-radius: 15px; border: none;">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="bx bx-history me-2"></i> Riwayat Alat</h5>
            </div>
            <div class="card-body">
                <ul class="timeline timeline-dashed mt-3">
                    @foreach($inventory->logs as $log)
                    <li class="timeline-item timeline-item-transparent">
                        <span class="timeline-point timeline-point-primary"></span>
                        <div class="timeline-event">
                            <div class="timeline-header mb-1">
                                <h6 class="mb-0 text-uppercase">{{ $log->aksi }}</h6>
                                <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-2">{{ $log->keterangan }}</p>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-xs me-2">
                                    <span class="avatar-initial rounded-circle bg-label-secondary"><i class="bx bx-user"></i></span>
                                </div>
                                <small>Oleh: {{ $log->executor->name ?? 'System' }}</small>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('inventory.assign', $inventory->id_inventory) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Alokasikan {{ $inventory->nama_alat }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Teknisi</label>
                        <select name="id_teknisi" class="form-select">
                            <option value="">-- Tidak Ada (Gudang) --</option>
                            @foreach($technicians as $tech)
                            <option value="{{ $tech->id_teknisi }}" {{ $inventory->id_teknisi == $tech->id_teknisi ? 'selected' : '' }}>{{ $tech->nama_teknisi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="text-center my-2 text-muted">ATAU</div>
                    <div class="mb-3">
                        <label class="form-label">Pilih User Internal</label>
                        <select name="id_user" class="form-select">
                            <option value="">-- Tidak Ada --</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $inventory->id_user == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
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
@endsection

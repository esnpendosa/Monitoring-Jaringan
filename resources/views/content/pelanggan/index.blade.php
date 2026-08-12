@extends('layouts/contentNavbarLayout')

@section('title', 'Data Pelanggan')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Data /</span> Pelanggan</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Daftar Pelanggan</h5>
    <div class="d-flex align-items-center">
      <form action="{{ route('pelanggan.index') }}" method="GET" class="me-2">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari pelanggan..." value="{{ request('search') }}">
        </div>
      </form>
      <a href="{{ route('pelanggan.export') }}" class="btn btn-outline-success btn-sm me-1">
        <i class="bx bx-download me-1"></i> Export
      </a>
      <button type="button" class="btn btn-outline-info btn-sm me-1" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="bx bx-upload me-1"></i> Import
      </button>
      <a href="{{ route('pelanggan.card-massal', ['search' => request('search')]) }}" class="btn btn-outline-info btn-sm me-1" target="_blank">
        <i class="bx bx-id-card me-1"></i> Cetak Kartu
      </a>
      <div class="dropdown me-1">
        <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="bx bxl-whatsapp me-1"></i> WA Massal
        </button>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
          <form action="{{ route('pelanggan.toggle-all-wa') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENGAKTIFKAN notifikasi WhatsApp untuk semua pelanggan?')">
            @csrf
            <input type="hidden" name="status" value="1">
            <button type="submit" class="dropdown-item text-success">
              <i class="bx bx-check-circle me-1"></i> Aktifkan Semua WA
            </button>
          </form>
          <form action="{{ route('pelanggan.toggle-all-wa') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENONAKTIFKAN notifikasi WhatsApp untuk semua pelanggan?')">
            @csrf
            <input type="hidden" name="status" value="0">
            <button type="submit" class="dropdown-item text-danger">
              <i class="bx bx-x-circle me-1"></i> Nonaktifkan Semua WA
            </button>
          </form>
        </div>
      </div>
      <a href="{{ route('pelanggan.create') }}" class="btn btn-primary btn-sm">
        <i class="bx bx-plus me-1"></i> Tambah
      </a>
    </div>
  </div>
  <div class="table-responsive text-nowrap">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama</th>
          <th>Alamat</th>
          <th>Foto Rumah</th>
          <th>Usage (GB)</th>
          <th>Devices</th>
          <th>Paket maks</th>
          <th>Status</th>
          <th>WA</th>
          <th>Prioritas</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach($pelanggan as $p)
        <tr>
          <td><strong>{{ $p->kode_pelanggan }}</strong></td>
          <td>{{ $p->nama_pelanggan }}</td>
          <td>{{ Str::limit($p->alamat, 30) }}</td>
          <td>
            @if($p->foto_rumah)
              <a href="{{ asset('storage/' . $p->foto_rumah) }}" target="_blank" class="btn btn-xs btn-outline-info" title="Lihat Foto Rumah">
                <i class="bx bx-image-alt"></i>
              </a>
            @else
              <span class="text-muted" style="font-size: 0.75rem;">-</span>
            @endif
          </td>
          <td>{{ $p->usage_gb }} GB</td>
          <td>{{ $p->jumlah_device }}</td>
          <td><span class="badge bg-label-primary">{{ $p->paket ?? '-' }}</span></td>
          <td>
            <form action="{{ route('pelanggan.toggle-status', $p->id_pelanggan) }}" method="POST" class="d-inline">
              @csrf
              <div class="form-check form-switch d-inline-block">
                <input class="form-check-input" type="checkbox" role="switch" onchange="this.form.submit()" style="cursor: pointer; width: 2.5em; height: 1.25em;" {{ $p->is_active ? 'checked' : '' }} title="{{ $p->is_active ? 'Klik untuk Nonaktifkan' : 'Klik untuk Aktifkan' }}">
              </div>
            </form>
          </td>
          <td>
            <form action="{{ route('pelanggan.toggle-wa', $p->id_pelanggan) }}" method="POST" class="d-inline">
              @csrf
              <div class="form-check form-switch d-inline-block">
                <input class="form-check-input" type="checkbox" role="switch" onchange="this.form.submit()" style="cursor: pointer; width: 2.5em; height: 1.25em; {{ $p->wa_active ? 'background-color: #25d366; border-color: #25d366;' : '' }}" {{ $p->wa_active ? 'checked' : '' }} title="{{ $p->wa_active ? 'Klik untuk Matikan WA' : 'Klik untuk Aktifkan WA' }}">
              </div>
            </form>
          </td>
          <td>
            @php
              $badge = ($p->prioritas_label == 'High' || $p->prioritas_label == 'Medium') ? 'bg-label-danger' : 'bg-label-success';
            @endphp
            <span class="badge {{ $badge }}">{{ $p->prioritas_label ?? 'Low' }}</span>
          </td>
          <td>
            <div class="d-inline-flex gap-1 align-items-center">
              <a href="{{ route('pelanggan.show', $p->id_pelanggan) }}" class="btn btn-xs btn-outline-info" title="Detail / Statistik">
                <i class="bx bx-show-alt"></i>
              </a>
              <a href="{{ route('pelanggan.edit', $p->id_pelanggan) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                <i class="bx bx-edit-alt"></i>
              </a>
              <!-- Trigger Button for Bootstrap Modal -->
              <button type="button" class="btn btn-xs btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $p->id_pelanggan }}" title="Hapus Pelanggan">
                <i class="bx bx-trash"></i>
              </button>

              <!-- Premium Center-aligned Delete Modal -->
              <div class="modal fade" id="deleteModal{{ $p->id_pelanggan }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Konfirmasi</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="white-space: normal;">
                      Apakah Anda yakin ingin menghapus pelanggan <strong>{{ $p->nama_pelanggan }}</strong> beserta seluruh data tagihannya?
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                      <a href="{{ route('pelanggan.destroy-direct', $p->id_pelanggan) }}" class="btn btn-danger">Hapus</a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="dropdown d-inline-block">
                <button type="button" class="btn btn-xs btn-outline-secondary dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                <div class="dropdown-menu">
                  <a href="{{ route('pelanggan.card', $p->id_pelanggan) }}" class="dropdown-item" target="_blank">
                      <i class="bx bx-id-card me-1"></i> Cetak Kartu
                  </a>
                  <form action="{{ route('pelanggan.toggle-wa', $p->id_pelanggan) }}" method="POST" class="d-inline">
                      @csrf
                      <button type="submit" class="dropdown-item">
                          <i class="bx bxl-whatsapp me-1 text-success"></i> {{ $p->wa_active ? 'Matikan WA' : 'Aktifkan WA' }}
                      </button>
                  </form>
                  <form action="{{ route('pelanggan.toggle-status', $p->id_pelanggan) }}" method="POST" class="d-inline">
                      @csrf
                      <button type="submit" class="dropdown-item">
                          <i class="bx {{ $p->is_active ? 'bx-power-off' : 'bx-play' }} me-1"></i> {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                      </button>
                  </form>
                </div>
              </div>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Data Pelanggan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('pelanggan.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="row">
            <div class="col mb-3">
              <label class="form-label">File Excel (.xlsx, .xls)</label>
              <input type="file" name="file" class="form-control" required>
            </div>
          </div>
          <div class="alert alert-info py-2 small mb-0">
            <strong>Info:</strong> Gunakan format header yang sesuai (ID, Kode, Nama, WA, User, Tipe, Alamat, Lat, Lng, Harga, Billing, Status). <br>
            <a href="{{ route('pelanggan.export') }}" class="text-primary fw-bold">Download Template (Export data sekarang)</a>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary">Mulai Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

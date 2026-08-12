@extends('layouts/contentNavbarLayout')

@section('title', 'Kas Bon Pekerja')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Operasional /</span> Kas Bon Pekerja</h4>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-4">
    <div class="card shadow-sm border-0 bg-white">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small d-block mb-1">Total Kas Bon Belum Lunas</span>
            <div class="d-flex align-items-center">
              <h4 class="mb-0 me-2 fw-bold text-danger">Rp {{ number_format($totalBelumLunas, 0, ',', '.') }}</h4>
            </div>
            <small class="text-muted">Total pinjaman aktif pekerja</small>
          </div>
          <span class="badge bg-label-danger p-2 rounded">
            <i class="bx bx-wallet text-danger style-icon"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-sm-6 col-xl-4">
    <div class="card shadow-sm border-0 bg-white">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small d-block mb-1">Total Kas Bon Lunas</span>
            <div class="d-flex align-items-center">
              <h4 class="mb-0 me-2 fw-bold text-success">Rp {{ number_format($totalLunas, 0, ',', '.') }}</h4>
            </div>
            <small class="text-muted">Pinjaman terkumpul / selesai</small>
          </div>
          <span class="badge bg-label-success p-2 rounded">
            <i class="bx bx-check-circle text-success style-icon"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-12 col-xl-4">
    <div class="card shadow-sm border-0 bg-white">
      <div class="card-body p-3">
        <h6 class="fw-bold text-dark mb-2">Ringkasan per Pekerja (Belum Lunas)</h6>
        <div class="row g-2" style="max-height: 80px; overflow-y: auto;">
          @forelse($groupedUnpaid as $worker => $sum)
          <div class="col-6">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-1">
              <span class="small text-truncate me-1" style="max-width: 80px;">{{ $worker }}</span>
              <span class="small fw-semibold text-danger">Rp {{ number_format($sum, 0, ',', '.') }}</span>
            </div>
          </div>
          @empty
          <div class="col-12 text-center text-muted small py-2">Tidak ada pinjaman aktif</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Table and Search Card -->
<div class="card shadow-sm border-0 bg-white">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-transparent border-bottom py-3 gap-3">
    <div class="d-flex align-items-center">
      <div class="avatar me-2">
        <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-money text-primary" style="font-size: 1.5rem;"></i></span>
      </div>
      <div>
        <h5 class="mb-0 fw-bold text-dark">Data Kas Bon Pekerja</h5>
        <small class="text-muted">Manajemen pinjaman tunai / kas bon karyawan & teknisi</small>
      </div>
    </div>
    <div>
      <button class="btn btn-primary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addKasBonModal">
        <i class="bx bx-plus"></i> Tambah Kas Bon
      </button>
    </div>
  </div>

  <div class="card-body py-3">
    <!-- Filter form -->
    <form action="{{ route('kas-bon.index') }}" method="GET" class="row g-3 mb-4">
      <div class="col-md-5">
        <input type="text" name="search" class="form-control" placeholder="Cari nama pekerja atau keterangan..." value="{{ request('search') }}">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">-- Semua Status --</option>
          <option value="belum_lunas" {{ request('status') === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
          <option value="lunas" {{ request('status') === 'lunas' ? 'selected' : '' }}>Lunas</option>
          <option value="dibatalkan" {{ request('status') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
        </select>
      </div>
      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-outline-primary"><i class="bx bx-filter-alt me-1"></i> Filter</button>
        @if(request()->anyFilled(['search', 'status']))
        <a href="{{ route('kas-bon.index') }}" class="btn btn-outline-secondary">Reset</a>
        @endif
      </div>
    </form>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover align-middle">
        <thead>
          <tr class="table-light">
            <th>Pekerja</th>
            <th>Tanggal</th>
            <th>Jumlah</th>
            <th>Keterangan</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($kasBons as $kb)
          <tr>
            <td class="fw-semibold text-dark">{{ $kb->worker_name }}</td>
            <td>{{ \Carbon\Carbon::parse($kb->tanggal)->translatedFormat('d M Y') }}</td>
            <td class="fw-bold text-dark">Rp {{ number_format($kb->jumlah, 0, ',', '.') }}</td>
            <td>
              <span class="text-wrap d-block" style="max-width: 250px;"><small class="text-secondary">{{ $kb->keterangan ?: '-' }}</small></span>
            </td>
            <td>
              @if($kb->status === 'belum_lunas')
              <span class="badge bg-label-danger">Belum Lunas</span>
              @elseif($kb->status === 'lunas')
              <span class="badge bg-label-success">Lunas</span>
              @else
              <span class="badge bg-label-secondary">Dibatalkan</span>
              @endif
            </td>
             <td>
              <div class="d-flex align-items-center gap-1">
                @if($kb->status === 'belum_lunas')
                <form action="{{ route('kas-bon.pay', $kb->id_kas_bon) }}" method="POST" class="d-inline">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-icon btn-sm btn-success" title="Tandai Lunas">
                    <i class="bx bx-check"></i>
                  </button>
                </form>
                @endif
                <button type="button" class="btn btn-icon btn-sm btn-warning" title="Edit" 
                        data-bs-toggle="modal" data-bs-target="#editKasBonModal{{ $kb->id_kas_bon }}">
                  <i class="bx bx-edit-alt"></i>
                </button>
                
                <!-- Trigger Button for Bootstrap Modal -->
                <button type="button" class="btn btn-icon btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $kb->id_kas_bon }}" title="Hapus">
                  <i class="bx bx-trash"></i>
                </button>

                <!-- Premium Center-aligned Delete Modal -->
                <div class="modal fade" id="deleteModal{{ $kb->id_kas_bon }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body" style="white-space: normal;">
                        Apakah Anda yakin ingin menghapus catatan kas bon dari <strong>{{ $kb->worker_name }}</strong> sebesar <strong>Rp {{ number_format($kb->jumlah, 0, ',', '.') }}</strong>?
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <a href="{{ route('kas-bon.destroy', $kb->id_kas_bon) }}" class="btn btn-danger">Hapus</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editKasBonModal{{ $kb->id_kas_bon }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title fw-bold">Edit Kas Bon</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('kas-bon.update', $kb->id_kas_bon) }}" method="POST">
                  @csrf
                  @method('PUT')
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Pilih Pekerja (Dari Database)</label>
                      <select name="selected_pekerja" class="form-select edit-worker-select">
                        <option value="">-- Pekerja Eksternal / Manual --</option>
                        @foreach($teknisiUsers as $tu)
                          @if($tu->teknisi)
                            <option value="teknisi:{{ $tu->teknisi->id_teknisi }}" {{ $kb->id_teknisi == $tu->teknisi->id_teknisi ? 'selected' : '' }}>
                              {{ $tu->name }}
                            </option>
                          @else
                            <option value="user:{{ $tu->name }}" {{ ($kb->id_teknisi === null && $kb->nama_pekerja === $tu->name) ? 'selected' : '' }}>
                              {{ $tu->name }}
                            </option>
                          @endif
                        @endforeach
                      </select>
                    </div>
                    <div class="mb-3 edit-manual-name-div">
                      <label class="form-label">Nama Pekerja Manual</label>
                      <input type="text" name="nama_pekerja" class="form-control" value="{{ $kb->nama_pekerja }}" placeholder="Masukkan nama jika tidak ada di list">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Jumlah Kas Bon (Rp)</label>
                      <input type="number" name="jumlah" class="form-control" value="{{ $kb->jumlah }}" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tanggal</label>
                      <input type="date" name="tanggal" class="form-control" value="{{ $kb->tanggal }}" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Keterangan / Catatan</label>
                      <textarea name="keterangan" class="form-control" rows="3">{{ $kb->keterangan }}</textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Status</label>
                      <select name="status" class="form-select">
                        <option value="belum_lunas" {{ $kb->status === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                        <option value="lunas" {{ $kb->status === 'lunas' ? 'selected' : '' }}>Lunas</option>
                        <option value="dibatalkan" {{ $kb->status === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
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
          @empty
          <tr>
            <td colspan="6" class="text-center py-4 text-muted">Tidak ada catatan kas bon ditemukan.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
      {{ $kasBons->links() }}
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addKasBonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Tambah Kas Bon</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('kas-bon.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pilih Pekerja (Dari Database)</label>
            <select name="selected_pekerja" id="add_selected_pekerja" class="form-select">
              <option value="">-- Pekerja Eksternal / Manual --</option>
              @foreach($teknisiUsers as $tu)
                @if($tu->teknisi)
                  <option value="teknisi:{{ $tu->teknisi->id_teknisi }}">{{ $tu->name }}</option>
                @else
                  <option value="user:{{ $tu->name }}">{{ $tu->name }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="mb-3" id="add_manual_name_div">
            <label class="form-label">Nama Pekerja Manual</label>
            <input type="text" name="nama_pekerja" class="form-control" placeholder="Masukkan nama jika tidak ada di list">
          </div>
          <div class="mb-3">
            <label class="form-label">Jumlah Kas Bon (Rp)</label>
            <input type="number" name="jumlah" class="form-control" placeholder="Contoh: 100000" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Keterangan / Catatan</label>
            <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Bon bensin / makan siang"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="belum_lunas" selected>Belum Lunas</option>
              <option value="lunas">Lunas</option>
              <option value="dibatalkan">Dibatalkan</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide manual worker name field dynamically based on select in ADD modal
    const addPekerjaSelect = document.getElementById('add_selected_pekerja');
    const addManualDiv = document.getElementById('add_manual_name_div');

    if (addPekerjaSelect && addManualDiv) {
        addPekerjaSelect.addEventListener('change', function() {
            if (this.value !== '') {
                addManualDiv.style.display = 'none';
            } else {
                addManualDiv.style.display = 'block';
            }
        });
    }

    // Edit modal dynamic inputs
    const editSelects = document.querySelectorAll('.edit-worker-select');
    editSelects.forEach(select => {
        const modal = select.closest('.modal-content');
        const manualDiv = modal.querySelector('.edit-manual-name-div');
        
        select.addEventListener('change', function() {
            if (this.value !== '') {
                manualDiv.style.display = 'none';
            } else {
                manualDiv.style.display = 'block';
            }
        });

        // Trigger on load
        if (select.value !== '') {
            manualDiv.style.display = 'none';
        }
    });
});
</script>
@endsection

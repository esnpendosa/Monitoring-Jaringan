@extends('layouts.app')

@section('title', 'Tambah Pengajuan Cuti')

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-white p-3 rounded-4 shadow-sm">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted"><i class="fa-solid fa-house"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('kepegawaian.cuti') }}" class="text-decoration-none text-muted">Cuti</a></li>
            <li class="breadcrumb-item text-muted">Cuti Pegawai</li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Tambah</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5">
            <form action="{{ route('kepegawaian.cuti.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Nama Pegawai</label>
                        <input type="text" class="form-control border-0 bg-light rounded-2 px-3" value="{{ $user->name }}" disabled>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Kode Pegawai</label>
                        <input type="text" class="form-control border-0 bg-light rounded-2 px-3" value="{{ $biodata->kode_pegawai ?? '-' }}" disabled>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-danger">Unit *</label>
                        <select name="unit" class="form-select border-0 bg-light rounded-2 px-3" required>
                            <option value="">-- Pilih Unit --</option>
                            <option value="Yayasan">Yayasan</option>
                            <option value="SD">SD</option>
                            <option value="SMP">SMP</option>
                            <option value="SMA">SMA</option>
                            <option value="Kantin">Kantin</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-danger">Alasan Cuti *</label>
                        <select name="alasan" class="form-select border-0 bg-light rounded-2 px-3" required>
                            <option value="">-- Pilih Alasan Cuti --</option>
                            <option value="Cuti Tahunan">Cuti Tahunan</option>
                            <option value="Cuti Sakit">Cuti Sakit</option>
                            <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                            <option value="Cuti Menikah">Cuti Menikah</option>
                            <option value="Urusan Keluarga">Urusan Keluarga</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-danger">Tanggal Mulai & Selesai *</label>
                        <div class="input-group">
                            <input type="date" name="tgl_mulai" class="form-control border-0 bg-light rounded-start-2 px-3" required>
                            <span class="input-group-text border-0 bg-light px-2"><i class="fa-solid fa-calendar-alt"></i></span>
                            <input type="date" name="tgl_selesai" class="form-control border-0 bg-light rounded-end-2 px-3" required>
                            <span class="input-group-text border-0 bg-light px-2"><i class="fa-solid fa-calendar-alt"></i></span>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-danger">Dokumen Pendukung *</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="file" name="dokumen" class="form-control border-0 bg-light rounded-2 px-3">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="ket_dokumen" class="form-control border-0 bg-light rounded-2 px-3" placeholder="Keterangan Dokumen">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Catatan</label>
                        <textarea name="catatan" class="form-control border-0 bg-light rounded-3 px-3" rows="4" placeholder="Catatan Tambahan"></textarea>
                    </div>

                    <div class="col-md-12 mt-4">
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-danger">
                            <h6 class="fw-bold text-dark mb-1 small">Info</h6>
                            <p class="text-danger small mb-0 font-italic">* Pengajuan cuti akan sah jika sudah disetujui yayasan, status pengajuan bisa dilihat di tabel bagian validasi</p>
                        </div>
                    </div>

                    <div class="col-md-12 text-end mt-4">
                        <button type="submit" class="btn btn-success rounded-2 px-5 py-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-save me-2"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

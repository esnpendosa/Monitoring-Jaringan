@extends('layouts.app')

@section('title', 'Tambah RPP')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center mb-4">
        <h4 class="fw-bold mb-0">RENCANA PELAKSANAAN PEMBELAJARAN <a href="{{ route('kepegawaian.rpp') }}" class="text-dark text-decoration-none"><i class="fa-solid fa-rotate ms-1 small text-muted"></i></a></h4>
    </div>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-transparent p-0 mb-0 small text-muted">
            <li class="breadcrumb-item"><i class="fa-solid fa-bookmark me-2 opacity-50"></i>Rencana Pelaksanaan Pembelajaran</li>
            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5">
            <form action="{{ route('kepegawaian.rpp.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row mb-4 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small">Tanggal <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-9">
                        <div class="input-group">
                            <input type="date" name="tanggal" class="form-control border rounded-3 py-2 px-3 small @error('tanggal') is-invalid @enderror" value="{{ date('Y-m-d') }}" required>
                        </div>
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small">Unit <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-9">
                        <select name="unit" class="form-select border rounded-3 py-2 px-3 small @error('unit') is-invalid @enderror" required>
                            <option value="">-- Pilih Unit --</option>
                            <option value="MA Ma'arif NU Assa'adah">MA Ma'arif NU Assa'adah</option>
                            <option value="MTs Ma'arif NU Assa'adah">MTs Ma'arif NU Assa'adah</option>
                            <option value="MI Ma'arif NU Assa'adah">MI Ma'arif NU Assa'adah</option>
                        </select>
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small">Kelas <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-9">
                        <select name="kelas" class="form-select border rounded-3 py-2 px-3 small @error('kelas') is-invalid @enderror" required>
                            <option value="">-- Pilih Kelas --</option>
                            <option value="X-A">X-A</option>
                            <option value="X-B">X-B</option>
                            <option value="XI-A">XI-A</option>
                            <option value="XI-B">XI-B</option>
                            <option value="XII-A">XII-A</option>
                            <option value="XII-B">XII-B</option>
                        </select>
                        @error('kelas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small">Mata Pelajaran <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-9">
                        <select name="mata_pelajaran" class="form-select border rounded-3 py-2 px-3 small @error('mata_pelajaran') is-invalid @enderror" required>
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <option value="Al Qur'an Hadis">Al Qur'an Hadis</option>
                            <option value="Akidah Akhlak">Akidah Akhlak</option>
                            <option value="Fikih">Fikih</option>
                            <option value="Sejarah Kebudayaan Islam">Sejarah Kebudayaan Islam</option>
                            <option value="Bahasa Arab">Bahasa Arab</option>
                            <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                            <option value="Matematika">Matematika</option>
                        </select>
                        @error('mata_pelajaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small">Judul <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" name="judul" class="form-control border rounded-3 py-2 px-3 small @error('judul') is-invalid @enderror" placeholder="Ketik Judul RPP" value="{{ old('judul') }}" required>
                        @error('judul') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small mt-2">Deskripsi <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-9">
                        <textarea name="deskripsi" class="form-control border rounded-3 py-2 px-3 small @error('deskripsi') is-invalid @enderror" rows="5" placeholder="Deskripsi materi pembelajaran" required>{{ old('deskripsi') }}</textarea>
                        @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-0 text-muted small">Dokumen <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-9">
                        <input type="file" name="dokumen" class="form-control border rounded-3 py-2 px-3 small @error('dokumen') is-invalid @enderror" required>
                        <small class="text-muted mt-1 d-block" style="font-size: 10px;">Format: PDF, DOC, DOCX, JPG, PNG (Max 5MB)</small>
                        @error('dokumen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-pmu rounded-pill px-5 py-3 fw-bold d-inline-flex align-items-center shadow-lg transition-hover">
                            <i class="fa-solid fa-save me-2 small"></i> Simpan RPP
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

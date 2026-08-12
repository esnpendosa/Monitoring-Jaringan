@extends('layouts/contentNavbarLayout')

@section('title', 'Tambah Modem')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-header bg-transparent border-bottom py-3">
                <h5 class="mb-0"><i class="bx bx-plus-circle text-primary me-2"></i>Tambah Modem Baru</h5>
                <small class="text-muted">Isi data lengkap perangkat modem</small>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('modem.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Merek <span class="text-danger">*</span></label>
                            <input type="text" name="merek" class="form-control @error('merek') is-invalid @enderror"
                                   value="{{ old('merek') }}" placeholder="e.g. Huawei, ZTE, TP-Link">
                            @error('merek')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Model <span class="text-danger">*</span></label>
                            <input type="text" name="model" class="form-control @error('model') is-invalid @enderror"
                                   value="{{ old('model') }}" placeholder="e.g. HG8245H5, F609">
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                   value="{{ old('nama') }}" placeholder="e.g. Huawei HG8245H5 ONT">
                            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">IP Address</label>
                            <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror"
                                   value="{{ old('ip_address') }}" placeholder="e.g. 192.168.1.1">
                            @error('ip_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3"
                                      placeholder="Deskripsi singkat modem...">{{ old('deskripsi') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Spesifikasi Teknis</label>
                            <textarea name="spesifikasi" class="form-control" rows="5"
                                      placeholder="Processor: ...\nRAM: ...\nPort: ...">{{ old('spesifikasi') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Foto Depan</label>
                            <input type="file" name="image_front" class="form-control @error('image_front') is-invalid @enderror"
                                   accept="image/*" id="imageFrontInput">
                            @error('image_front')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="imageFrontPreview" class="mt-2" style="display:none;">
                                <img id="previewFrontImg" style="max-height:150px;border-radius:10px;border:1px solid #e8eaed;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Foto Belakang</label>
                            <input type="file" name="image_back" class="form-control @error('image_back') is-invalid @enderror"
                                   accept="image/*" id="imageBackInput">
                            @error('image_back')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="imageBackPreview" class="mt-2" style="display:none;">
                                <img id="previewBackImg" style="max-height:150px;border-radius:10px;border:1px solid #e8eaed;">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">Tampilkan di katalog</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Simpan Modem
                        </button>
                        <a href="{{ route('modem.admin.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
function setupImagePreview(inputId, previewDivId, imgId) {
    document.getElementById(inputId).addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById(imgId).src = ev.target.result;
            document.getElementById(previewDivId).style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
}
setupImagePreview('imageFrontInput', 'imageFrontPreview', 'previewFrontImg');
setupImagePreview('imageBackInput', 'imageBackPreview', 'previewBackImg');
</script>
@endsection

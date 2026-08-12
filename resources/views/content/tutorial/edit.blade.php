@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Tutorial - ' . $tutorial->judul)

@section('content')
<div class="mb-4">
    <a href="{{ route('tutorial.admin.index') }}" class="text-muted text-decoration-none small">
        <i class="bx bx-arrow-back me-1"></i>Kembali ke Kelola Tutorial
    </a>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 16px;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Edit Tutorial</h5>
        <small class="text-muted float-end">Materi Tutorial Modem Dinamis</small>
    </div>
    <div class="card-body">
        <form action="{{ route('tutorial.update', $tutorial->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                {{-- Kiri: Form Utama --}}
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label" for="judul">Judul Tutorial <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="judul" class="form-control @error('judul') is-invalid @enderror" 
                               value="{{ old('judul', $tutorial->judul) }}" placeholder="Contoh: Cara Reset dan Setting Ulang Modem ZTE F609" required>
                        @error('judul')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="ringkasan">Deskripsi Ringkas / Ringkasan</label>
                        <textarea name="ringkasan" id="ringkasan" rows="3" class="form-control @error('ringkasan') is-invalid @enderror" 
                                  placeholder="Ringkasan singkat isi tutorial..." max="500">{{ old('ringkasan', $tutorial->ringkasan) }}</textarea>
                        @error('ringkasan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="editor-content">Konten Lengkap <span class="text-danger">*</span></label>
                        <textarea name="konten" id="editor-content" class="d-none">{{ old('konten', $tutorial->konten) }}</textarea>
                        @error('konten')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Kanan: Sidebar Form --}}
                <div class="col-lg-4">
                    <div class="p-3 bg-light rounded" style="border: 1px solid #e5e7eb;">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Pengaturan Publikasi</h6>
                        
                        <div class="mb-3">
                            <label class="form-label" for="kategori">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" id="kategori" class="form-select @error('kategori') is-invalid @enderror" required>
                                @foreach($kategoriList as $kat)
                                    <option value="{{ $kat }}" {{ old('kategori', $tutorial->kategori) == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                                @endforeach
                            </select>
                            @error('kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="urutan">Urutan Tampil</label>
                            <input type="number" name="urutan" id="urutan" class="form-control @error('urutan') is-invalid @enderror" 
                                   value="{{ old('urutan', $tutorial->urutan) }}" placeholder="Urutan angka">
                            <small class="text-muted" style="font-size: 0.75rem;">Semakin kecil nilai urutan, semakin atas posisinya.</small>
                            @error('urutan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="thumbnail">Gambar Cover / Thumbnail</label>
                            <input type="file" name="thumbnail" id="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/*">
                            <small class="text-muted" style="font-size: 0.75rem;">Maksimal 2MB (Format: JPG, PNG, WEBP).</small>
                            @error('thumbnail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            
                            {{-- Preview Cover Saat Ini / Preview Baru --}}
                            <div class="mt-2 text-center" id="preview-wrapper">
                                <label class="d-block text-muted small text-start mb-1">Preview Gambar:</label>
                                @if($tutorial->thumbnail)
                                    <img src="{{ url('storage/' . $tutorial->thumbnail) }}" id="image-preview" class="img-thumbnail" style="max-height: 150px; object-fit: cover;">
                                @else
                                    <img src="" id="image-preview" class="img-thumbnail d-none" style="max-height: 150px; object-fit: cover;">
                                @endif
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block">Status Publikasi</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1" 
                                       {{ old('is_published', $tutorial->is_published) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_published">Publikasikan langsung</label>
                            </div>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bx bx-save me-1"></i> Perbarui Tutorial
                        </button>
                        <a href="{{ route('tutorial.admin.index') }}" class="btn btn-outline-secondary w-100">Batal</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Script TinyMCE Integration --}}
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Thumbnail Preview
        const thumbnailInput = document.getElementById('thumbnail');
        const imagePreview = document.getElementById('image-preview');

        thumbnailInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        });

        // Initialize TinyMCE Rich Text Editor
        tinymce.init({
            selector: '#editor-content',
            height: 450,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image media table | removeformat code fullscreen help',
            branding: false,
            promotion: false,
            content_style: 'body { font-family: Outfit, sans-serif; font-size: 14px; color: #374151; } img { max-width: 100%; height: auto; border-radius: 8px; }',
            images_upload_url: '{{ route("tutorial.upload-image") }}',
            images_upload_credentials: true,
            automatic_uploads: true,
            
            // Custom Upload Handler for dynamic images
            images_upload_handler: function (blobInfo, progress) {
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.withCredentials = false;
                    xhr.open('POST', '{{ route("tutorial.upload-image") }}');
                    
                    // Laravel CSRF Token
                    xhr.setRequestHeader("X-CSRF-Token", '{{ csrf_token() }}');

                    xhr.upload.onprogress = (e) => {
                        progress(e.loaded / e.total * 100);
                    };

                    xhr.onload = () => {
                        if (xhr.status === 403) {
                            reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                            return;
                        }
                        if (xhr.status < 200 || xhr.status >= 300) {
                            reject('HTTP Error: ' + xhr.status);
                            return;
                        }
                        const json = JSON.parse(xhr.responseText);
                        if (!json || typeof json.location != 'string') {
                            reject('Invalid JSON: ' + xhr.responseText);
                            return;
                        }
                        resolve(json.location);
                    };

                    xhr.onerror = () => {
                        reject('Gagal mengupload gambar karena masalah jaringan atau izin.');
                    };

                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());

                    xhr.send(formData);
                });
            }
        });
    });
</script>
@endsection
@endsection

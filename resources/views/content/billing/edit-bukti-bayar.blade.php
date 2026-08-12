@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Bukti Pembayaran')

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Keuangan / Tagihan /</span> Edit Bukti Pembayaran
</h4>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Bukti Pembayaran</h5>
                <a href="{{ route('billing.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <!-- Success Message -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong><i class="bx bx-check-circle me-2"></i>Berhasil!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <!-- Error Messages -->
                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="bx bx-error-circle me-2"></i>Terjadi kesalahan!</strong>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <!-- Billing Information -->
                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading mb-2"><i class="bx bx-info-circle me-1"></i> Informasi Tagihan</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Pelanggan:</strong> {{ $tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum' }}<br>
                            <strong>Kode:</strong> {{ $tagihan->pelanggan ? $tagihan->pelanggan->kode_pelanggan : '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Periode:</strong> {{ date('F', mktime(0, 0, 0, $tagihan->bulan, 10)) }} {{ $tagihan->tahun }}<br>
                            <strong>Jumlah:</strong> Rp {{ number_format($tagihan->jumlah, 0, ',', '.') }}<br>
                            <strong>Status:</strong> 
                            @if($tagihan->status == 'paid')
                                <span class="badge bg-success">Lunas</span>
                            @elseif($tagihan->status == 'pending')
                                <span class="badge bg-info">Pending</span>
                            @else
                                <span class="badge bg-warning">Belum Bayar</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Current Payment Proof -->
                @if($tagihan->bukti_bayar && file_exists(storage_path('app/public/' . $tagihan->bukti_bayar)))
                <div class="mb-4">
                    <h6>Bukti Pembayaran Saat Ini:</h6>
                    <div class="text-center border rounded p-3">
                        @if(Str::endsWith($tagihan->bukti_bayar, '.pdf'))
                        <a href="{{ asset('storage/' . $tagihan->bukti_bayar) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="bx bx-file-blank me-1"></i> Lihat PDF
                        </a>
                        @else
                        <img src="{{ asset('storage/' . $tagihan->bukti_bayar) }}" alt="Bukti Pembayaran" class="img-fluid" style="max-height: 300px; border-radius: 8px;">
                        @endif
                    </div>
                    <small class="text-muted d-block mt-2">File: {{ basename($tagihan->bukti_bayar) }}</small>
                </div>
                @else
                <div class="alert alert-warning mb-4">
                    <i class="bx bx-info-circle me-1"></i> Belum ada bukti pembayaran yang diunggah untuk tagihan ini.
                </div>
                @endif

                <!-- Edit Form -->
                <form action="{{ route('billing.update-bukti-bayar', $tagihan->id_tagihan) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="bukti_bayar" class="form-label fw-semibold">
                            <i class="bx bx-upload me-1"></i> Upload New Payment Proof
                        </label>
                        <div class="drop-zone" id="dropZoneEdit"
                             style="border: 2px dashed #adb5bd; border-radius: 8px; padding: 24px; text-align: center; cursor: pointer; transition: background .2s, border-color .2s; background: #f8f9fa;">
                            <div class="drop-zone__prompt" id="dropPromptEdit">
                                <i class='bx bx-cloud-upload' style="font-size:2.5rem; color:#6c757d;"></i>
                                <div class="mt-1 text-muted">Drag &amp; drop gambar di sini, atau <span class="text-primary fw-semibold">pilih file</span></div>
                                <div class="text-muted small">JPG, PNG, GIF, PDF — maks 3MB</div>
                            </div>
                            <div class="drop-zone__preview d-none" id="dropPreviewEdit">
                                <img id="dropImgEdit" src="" alt="preview" style="max-height:160px; max-width:100%; border-radius:6px; object-fit:contain;">
                                <div class="mt-1 small text-muted" id="dropNameEdit"></div>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-1" id="dropClearEdit">Hapus</button>
                            </div>
                            <input type="file" class="d-none @error('bukti_bayar') is-invalid @enderror"
                                   id="bukti_bayar" name="bukti_bayar"
                                   accept="image/jpeg,image/jpg,image/png,image/gif,application/pdf" required>
                        </div>
                        <div class="form-text mt-1">
                            <i class="bx bx-info-circle me-1"></i> Formats: JPG, PNG, GIF, PDF. Max size: 3MB
                        </div>
                        @error('bukti_bayar')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="metode_pembayaran" class="form-label">Metode Pembayaran (Opsional)</label>
                        <select name="metode_pembayaran" id="metode_pembayaran" class="form-select @error('metode_pembayaran') is-invalid @enderror">
                            <option value="">-- Pilih Metode Pembayaran --</option>
                            @foreach(explode(',', \App\Models\Setting::get('manual_payment_methods', 'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay')) as $method)
                            <option value="{{ trim($method) }}" {{ old('metode_pembayaran', $tagihan->metode_pembayaran) == trim($method) ? 'selected' : '' }}>
                                {{ trim($method) }}
                            </option>
                            @endforeach
                        </select>
                        @error('metode_pembayaran')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(auth()->user()->id_role == 1 || auth()->user()->id_role == 2)
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="verify_payment" name="verify_payment" value="1">
                            <label class="form-check-label" for="verify_payment">
                                <strong>Verifikasi & Tandai Sebagai Lunas</strong>
                            </label>
                        </div>
                        <small class="text-muted">Centang jika Anda ingin langsung memverifikasi pembayaran ini sebagai lunas.</small>
                    </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ── Drag & Drop Edit Bukti Bayar ──────────────────────────────────────────────
(function() {
    const zone    = document.getElementById('dropZoneEdit');
    const input   = document.getElementById('bukti_bayar');
    const prompt  = document.getElementById('dropPromptEdit');
    const preview = document.getElementById('dropPreviewEdit');
    const img     = document.getElementById('dropImgEdit');
    const name    = document.getElementById('dropNameEdit');
    const clear   = document.getElementById('dropClearEdit');

    zone.addEventListener('click', function(e) {
        if (e.target !== clear) input.click();
    });

    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        zone.style.background = '#e8f4ff';
        zone.style.borderColor = '#0d6efd';
    });
    zone.addEventListener('dragleave', function() {
        zone.style.background = '#f8f9fa';
        zone.style.borderColor = '#adb5bd';
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.style.background = '#f8f9fa';
        zone.style.borderColor = '#adb5bd';
        const file = e.dataTransfer.files[0];
        if (file) setFile(file);
    });

    input.addEventListener('change', function() {
        if (input.files[0]) setFile(input.files[0]);
    });

    clear.addEventListener('click', function(e) {
        e.stopPropagation();
        input.value = '';
        prompt.classList.remove('d-none');
        preview.classList.add('d-none');
        img.src = '';
        img.style.display = 'block';
        const pdfIcon = preview.querySelector('.pdf-icon');
        if (pdfIcon) pdfIcon.remove();
    });

    function setFile(file) {
        if (file.size > 3 * 1024 * 1024) {
            alert('Ukuran file maksimal 3MB');
            return;
        }
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;

        name.textContent = file.name;
        prompt.classList.add('d-none');
        preview.classList.remove('d-none');

        if (file.type.startsWith('image/')) {
            img.style.display = 'block';
            const reader = new FileReader();
            reader.onload = function(e) { img.src = e.target.result; };
            reader.readAsDataURL(file);
        } else {
            img.style.display = 'none';
            const existing = preview.querySelector('.pdf-icon');
            if (!existing) {
                const div = document.createElement('div');
                div.className = 'pdf-icon my-2';
                div.innerHTML = "<i class='bx bxs-file-pdf' style='font-size:3rem;color:#dc3545;'></i>";
                preview.insertBefore(div, name);
            }
        }
    }
})();
</script>

@endsection

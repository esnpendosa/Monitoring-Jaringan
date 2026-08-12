@extends('layouts/contentNavbarLayout')

@section('title', 'Penjadwal Status WhatsApp')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">WhatsApp /</span> Penjadwal Status Otomatis</h4>

<!-- Notification Alert -->
@if(session('success'))
<div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible" role="alert">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <!-- Form Create Schedule -->
    <div class="col-md-5">
        <div class="card premium-glass mb-4 shadow-sm border-0">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-0 fw-semibold text-primary"><i class="bx bx-time-five me-2"></i> Jadwalkan Status Baru</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('whatsapp.status.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Media Upload with Live Preview -->
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Gambar Status (Opsional)</label>
                        <div class="d-flex flex-column align-items-center justify-content-center border border-dashed rounded-3 p-3 text-center preview-container" id="uploadZone">
                            <i class="bx bx-image-add text-muted fs-1 mb-2" id="placeholderIcon"></i>
                            <div class="text-muted small mb-2" id="uploadHint">Klik atau Seret Gambar ke Sini</div>
                            <input type="file" name="media" id="mediaInput" class="form-control d-none" accept="image/*">
                            <img id="imagePreview" class="img-fluid rounded-3 d-none shadow-sm" style="max-height: 180px; object-fit: cover;">
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnChooseImage">Pilih Gambar</button>
                        </div>
                    </div>

                    <!-- Text Area Content -->
                    <div class="mb-3">
                        <label for="content" class="form-label fw-medium text-dark">Isi Tulisan / Caption Status</label>
                        <textarea class="form-control focus-ring" id="content" name="content" rows="4" placeholder="Tulis caption cerita WhatsApp Anda di sini..."></textarea>
                        <div class="text-end text-muted small mt-1"><span id="charCount">0</span> karakter</div>
                    </div>

                    <!-- Date Time Picker -->
                    <div class="mb-4">
                        <label for="scheduled_at" class="form-label fw-medium text-dark">Jadwal Publish</label>
                        <input type="datetime-local" class="form-control focus-ring" id="scheduled_at" name="scheduled_at" required value="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm"><i class="bx bx-calendar-plus me-1"></i> Simpan Penjadwalan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Schedule List Table -->
    <div class="col-md-7">
        <div class="card premium-glass mb-4 shadow-sm border-0">
            <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-primary"><i class="bx bx-list-ul me-2"></i> Daftar Antrean Status</h5>
                <span class="badge bg-label-primary rounded-pill">{{ count($schedules) }} Item</span>
            </div>
            <div class="table-responsive text-nowrap mt-3">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Pratinjau</th>
                            <th>Tulisan / Caption</th>
                            <th>Waktu Kirim</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $s)
                        <tr>
                            <!-- Media Preview -->
                            <td>
                                @if($s->media)
                                <a href="{{ asset('storage/' . $s->media) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $s->media) }}" alt="Preview" class="rounded shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                                </a>
                                @else
                                <div class="bg-light rounded text-muted d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bx bx-font"></i>
                                </div>
                                @endif
                            </td>
                            <!-- Caption -->
                            <td class="text-wrap" style="max-width: 200px;">
                                <span class="small text-dark">{{ Str::limit($s->content ?: '-', 80) }}</span>
                            </td>
                            <!-- Scheduled Time -->
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold small text-dark">{{ $s->scheduled_at->format('d M Y') }}</span>
                                    <span class="text-muted small">{{ $s->scheduled_at->format('H:i') }} WIB</span>
                                </div>
                            </td>
                            <!-- Status Badge -->
                            <td>
                                @if($s->status === 'pending')
                                <span class="badge bg-label-warning rounded-pill px-3 py-2"><i class="bx bx-time-five me-1"></i> PENDING</span>
                                @elseif($s->status === 'posted')
                                <span class="badge bg-label-success rounded-pill px-3 py-2"><i class="bx bx-check-double me-1"></i> POSTED</span>
                                @else
                                <span class="badge bg-label-danger rounded-pill px-3 py-2 cursor-help" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $s->error_message }}"><i class="bx bx-error-circle me-1"></i> FAILED</span>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    @if($s->status !== 'posted')
                                    <!-- Publish Now -->
                                    <form action="{{ route('whatsapp.status.publish-now', $s->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-icon btn-sm btn-success shadow-sm" title="Upload Sekarang Seketika">
                                            <i class="bx bx-cloud-upload"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <!-- Delete -->
                                    <form action="{{ route('whatsapp.status.destroy', $s->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-icon btn-sm btn-danger btn-delete-status shadow-sm" title="Hapus">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bx bx-calendar-x fs-1 mb-2"></i>
                                    <p class="mb-0 small">Belum ada antrean status WhatsApp.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="card-footer bg-transparent border-0">
                <div class="d-flex justify-content-center">
                    {{ $schedules->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadZone = document.getElementById('uploadZone');
        const mediaInput = document.getElementById('mediaInput');
        const btnChoose = document.getElementById('btnChooseImage');
        const imagePreview = document.getElementById('imagePreview');
        const placeholderIcon = document.getElementById('placeholderIcon');
        const uploadHint = document.getElementById('uploadHint');
        const contentTextarea = document.getElementById('content');
        const charCount = document.getElementById('charCount');

        // Character counter
        contentTextarea.addEventListener('input', function() {
            charCount.innerText = this.value.length;
        });

        // Trigger input file click
        btnChoose.addEventListener('click', () => mediaInput.click());
        uploadZone.addEventListener('click', (e) => {
            if (e.target !== btnChoose) {
                mediaInput.click();
            }
        });

        // Drag and Drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('border-primary');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('border-primary');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('border-primary');
            if (e.dataTransfer.files.length) {
                mediaInput.files = e.dataTransfer.files;
                previewImage();
            }
        });

        // Handle File Select
        mediaInput.addEventListener('change', previewImage);

        function previewImage() {
            const file = mediaInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                    placeholderIcon.classList.add('d-none');
                    uploadHint.classList.add('d-none');
                    
                    // Style container
                    uploadZone.classList.add('p-1');
                    btnChoose.innerText = 'Ganti Gambar';
                }
                reader.readAsDataURL(file);
            }
        }

        // Initialize bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // SweetAlert Confirmation for Delete Status Schedule
        document.querySelectorAll('.btn-delete-status').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                
                Swal.fire({
                    title: 'Hapus Antrean Status?',
                    text: "Apakah Anda yakin ingin menghapus antrean jadwal status WhatsApp ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff3e1d',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection

<style>
    /* Premium Glassmorphism Style Cards */
    .premium-glass {
        background: rgba(255, 255, 255, 0.85) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        transition: all 0.3s ease;
    }
    .premium-glass:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.15) !important;
    }
    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
        border-color: rgba(99, 102, 241, 0.3) !important;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .border-dashed:hover {
        border-color: #6366f1 !important;
        background: rgba(99, 102, 241, 0.03) !important;
    }
    .focus-ring:focus {
        box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
        border-color: #6366f1;
    }
    .cursor-help {
        cursor: help;
    }
    .btn-icon {
        padding: 0.45rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
    }
</style>
@endsection

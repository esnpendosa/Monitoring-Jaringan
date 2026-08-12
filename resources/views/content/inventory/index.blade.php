@extends('layouts/contentNavbarLayout')

@section('title', 'Inventaris Alat - RTRW Net')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4 overflow-hidden" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div class="card-header d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #696cff 0%, #a2a4ff 100%);">
                <h5 class="mb-0 text-white"><i class="bx bx-wrench me-2"></i> Inventaris Alat & Peralatan</h5>
                <button type="button" class="btn btn-white btn-sm" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                    <i class="bx bx-plus me-1"></i> Tambah Alat
                </button>
            </div>
            <div class="card-body pt-4">
                <!-- Date Filter Form -->
                <form action="{{ route('inventory.index') }}" method="GET" class="mb-4 bg-light p-3 rounded" style="border: 1px solid #e5e7eb;">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-muted small">Tanggal Beli Mulai</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input type="date" name="tanggal_mulai" class="form-control" value="{{ request('tanggal_mulai') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-muted small">Tanggal Beli Selesai</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input type="date" name="tanggal_selesai" class="form-control" value="{{ request('tanggal_selesai') }}">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bx bx-filter-alt me-1"></i> Filter</button>
                            @if(request()->filled('tanggal_mulai') || request()->filled('tanggal_selesai'))
                                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary" title="Reset"><i class="bx bx-reset"></i></a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Tanggal Beli</th>
                                <th>Harga Satuan</th>
                                <th>Stok</th>
                                <th>Total Harga</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                                <th>Pemegang</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach($items as $item)
                            <tr>
                                <td>
                                    @if($item->gambar_alat)
                                    <a href="javascript:void(0);" onclick="showImage('{{ asset('storage/' . $item->gambar_alat) }}', '{{ $item->nama_alat }}')">
                                        <img src="{{ asset('storage/' . $item->gambar_alat) }}" alt="alat" class="rounded shadow-sm" width="50" height="50" style="object-fit: cover; border: 2px solid #fff;">
                                    </a>
                                    @else
                                    <div class="avatar avatar-sm bg-label-secondary"><span class="avatar-initial rounded"><i class="bx bx-package"></i></span></div>
                                    @endif
                                </td>
                                <td><strong>{{ $item->nama_alat }}</strong><br><small class="text-muted">{{ $item->serial_number }}</small></td>
                                <td><span class="badge bg-label-info">{{ $item->kategori }}</span></td>
                                <td>
                                    @if($item->tanggal_beli)
                                    <span class="text-muted small">{{ \Carbon\Carbon::parse($item->tanggal_beli)->format('d/m/Y') }}</span>
                                    @else
                                    <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->harga_beli)
                                    <span class="fw-semibold text-dark">Rp {{ number_format($item->harga_beli / ($item->stok ?: 1), 0, ',', '.') }}</span>
                                    @else
                                    <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td><span class="fw-bold">{{ $item->stok }}</span></td>
                                <td>
                                    @if($item->harga_beli)
                                    <span class="fw-semibold text-success">Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</span>
                                    @else
                                    <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->kondisi == 'baik')
                                    <span class="badge bg-label-success">Baik</span>
                                    @elseif($item->kondisi == 'rusak')
                                    <span class="badge bg-label-danger">Rusak</span>
                                    @else
                                    <span class="badge bg-label-warning">Perlu Perbaikan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->status == 'tersedia')
                                    <span class="badge bg-label-primary">Tersedia</span>
                                    @elseif($item->status == 'digunakan')
                                    <span class="badge bg-label-info">Digunakan</span>
                                    @else
                                    <span class="badge bg-label-secondary">{{ ucfirst($item->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->id_teknisi)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-xs me-2">
                                            <span class="avatar-initial rounded-circle bg-label-primary">{{ substr($item->technician->nama_teknisi, 0, 1) }}</span>
                                        </div>
                                        <span>{{ $item->technician->nama_teknisi }}</span>
                                    </div>
                                    @elseif($item->id_user)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-xs me-2">
                                            <span class="avatar-initial rounded-circle bg-label-secondary">{{ substr($item->user->name, 0, 1) }}</span>
                                        </div>
                                        <span>{{ $item->user->name }}</span>
                                    </div>
                                    @else
                                    <span class="text-muted italic">Gudang</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <a href="{{ route('inventory.show', $item->id_inventory) }}" class="btn btn-xs btn-outline-info" title="Detail Riwayat">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <button type="button" class="btn btn-xs btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id_inventory }}" title="Edit Alat">
                                            <i class="bx bx-edit-alt"></i>
                                        </button>
                                        <!-- Trigger Button for Bootstrap Modal -->
                                        <button type="button" class="btn btn-xs btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $item->id_inventory }}" title="Hapus">
                                            <i class="bx bx-trash"></i>
                                        </button>

                                        <div class="dropdown d-inline-block">
                                            <button type="button" class="btn btn-xs btn-outline-secondary dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#assignModal{{ $item->id_inventory }}"><i class="bx bx-transfer-alt me-1"></i> Alokasikan</a>
                                            </div>
                                        </div>

                                        <!-- Premium Center-aligned Delete Modal -->
                                        <div class="modal fade" id="deleteModal{{ $item->id_inventory }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Konfirmasi</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="white-space: normal;">
                                                        Apakah Anda yakin ingin menghapus alat <strong>{{ $item->nama_alat }}</strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <form action="{{ route('inventory.destroy', $item->id_inventory) }}" method="POST" class="d-inline">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">Hapus</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Assign Modal -->
                            <div class="modal fade" id="assignModal{{ $item->id_inventory }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form action="{{ route('inventory.assign', $item->id_inventory) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Alokasikan {{ $item->nama_alat }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Pilih Teknisi</label>
                                                    <select name="id_teknisi" class="form-select">
                                                        <option value="">-- Tidak Ada (Gudang) --</option>
                                                        @foreach($technicians as $tech)
                                                        <option value="{{ $tech->id_teknisi }}" {{ $item->id_teknisi == $tech->id_teknisi ? 'selected' : '' }}>{{ $tech->nama_teknisi }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="text-center my-2 text-muted">ATAU</div>
                                                <div class="mb-3">
                                                    <label class="form-label">Pilih User Internal</label>
                                                    <select name="id_user" class="form-select">
                                                        <option value="">-- Tidak Ada --</option>
                                                        @foreach($users as $user)
                                                        <option value="{{ $user->id }}" {{ $item->id_user == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
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

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $item->id_inventory }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('inventory.update', $item->id_inventory) }}" method="POST" enctype="multipart/form-data">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Alat: {{ $item->nama_alat }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3 text-center">
                                                    @if($item->gambar_alat)
                                                    <img src="{{ asset('storage/' . $item->gambar_alat) }}" alt="alat" class="rounded mb-2 img-current-edit" width="100">
                                                    @endif
                                                    <div class="camera-container-edit" style="display:none;" class="mb-2 position-relative">
                                                        <video width="100%" autoplay playsinline class="rounded border shadow-sm video-edit"></video>
                                                        <button type="button" class="btn btn-success btn-sm mt-1 btn-snap-edit"><i class="bx bx-camera"></i> Jepret</button>
                                                    </div>
                                                    <div class="preview-container-edit" class="mb-2" style="display:none;">
                                                        <img src="" class="rounded border shadow-sm img-preview-edit" style="max-height: 150px; width: auto;">
                                                        <button type="button" class="btn btn-outline-danger btn-xs d-block mx-auto mt-1 btn-retry-edit">Ulangi</button>
                                                    </div>
                                                    <input type="file" name="gambar_alat" class="form-control mb-2 input-file-edit" accept="image/*" capture="environment">
                                                    <input type="hidden" name="captured_image" class="input-captured-edit">
                                                    <button type="button" class="btn btn-outline-primary btn-sm w-100 btn-camera-edit"><i class="bx bx-camera me-1"></i> Gunakan Kamera Langsung</button>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Nama Alat</label>
                                                    <input type="text" name="nama_alat" class="form-control" value="{{ $item->nama_alat }}" required>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Kategori</label>
                                                        <input type="text" name="kategori" class="form-control" value="{{ $item->kategori }}">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Total Harga Beli (Rp)</label>
                                                        <input type="number" name="harga_beli" class="form-control" value="{{ $item->harga_beli }}" placeholder="e.g. 150000">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Tanggal Beli</label>
                                                        <input type="date" name="tanggal_beli" class="form-control" value="{{ $item->tanggal_beli ? \Carbon\Carbon::parse($item->tanggal_beli)->format('Y-m-d') : '' }}">
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Stok</label>
                                                        <input type="number" name="stok" class="form-control" value="{{ $item->stok }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Merk</label>
                                                        <input type="text" name="merk" class="form-control" value="{{ $item->merk }}">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Kondisi</label>
                                                    <select name="kondisi" class="form-select">
                                                        <option value="baik" {{ $item->kondisi == 'baik' ? 'selected' : '' }}>Baik</option>
                                                        <option value="rusak" {{ $item->kondisi == 'rusak' ? 'selected' : '' }}>Rusak</option>
                                                        <option value="perlu_perbaikan" {{ $item->kondisi == 'perlu_perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
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
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light border-top">
                                <td colspan="3" class="text-end fw-bold">Total Keseluruhan:</td>
                                <td>-</td>
                                <td>-</td>
                                <td class="fw-bold text-dark">{{ $items->sum('stok') }}</td>
                                <td class="fw-bold text-success" colspan="5">Rp {{ number_format($items->sum('harga_beli'), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <img id="fullImage" src="" class="img-fluid rounded shadow-lg" style="max-height: 90vh;">
                <div id="imageTitle" class="text-white mt-2 fw-bold fs-5"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('inventory.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Alat Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <label class="form-label d-block">Gambar Alat</label>
                        <div id="camera-container-add" style="display:none;" class="mb-2 position-relative">
                            <video id="video-add" width="100%" autoplay playsinline class="rounded border shadow-sm"></video>
                            <button type="button" id="btn-snap-add" class="btn btn-success btn-sm position-absolute bottom-0 start-50 translate-middle-x mb-2 shadow"><i class="bx bx-camera"></i> Jepret</button>
                        </div>
                        <div id="preview-container-add" class="mb-2" style="display:none;">
                            <img id="img-preview-add" src="" class="rounded border shadow-sm" style="max-height: 150px; width: auto;">
                            <button type="button" id="btn-retry-add" class="btn btn-outline-danger btn-xs d-block mx-auto mt-1">Ulangi</button>
                        </div>
                        <input type="file" name="gambar_alat" id="input-file-add" class="form-control mb-2" accept="image/*" capture="environment">
                        <input type="hidden" name="captured_image" id="input-captured-add">
                        <button type="button" id="btn-camera-add" class="btn btn-outline-primary btn-sm w-100"><i class="bx bx-camera me-1"></i> Gunakan Kamera Langsung</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Alat</label>
                        <input type="text" name="nama_alat" class="form-control" placeholder="Contoh: Tang Kombinasi" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="kategori" class="form-control" placeholder="e.g. Tools">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Harga Beli (Rp)</label>
                            <input type="number" name="harga_beli" class="form-control" placeholder="e.g. 150000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Beli</label>
                            <input type="date" name="tanggal_beli" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stok" class="form-control" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Merk</label>
                            <input type="text" name="merk" class="form-control" placeholder="e.g. Stanley">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Serial Number
                            <a href="javascript:void(0);" id="btn-scan-add" class="text-primary small"><i class="bx bx-qr-scan me-1"></i> Scan QR</a>
                        </label>
                        <div id="reader-add" style="display:none; width: 100%; border-radius: 10px; overflow: hidden;" class="mb-2 border"></div>
                        <input type="text" name="serial_number" id="input-sn-add" class="form-control" placeholder="Scan atau ketik S/N">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrCode = null;

    document.getElementById('btn-scan-add').addEventListener('click', function() {
        const readerDiv = document.getElementById('reader-add');
        const btn = this;

        if (readerDiv.style.display === 'none') {
            readerDiv.style.display = 'block';
            btn.innerHTML = '<i class="bx bx-x me-1"></i> Tutup Kamera';
            btn.classList.replace('text-primary', 'text-danger');

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("reader-add");
            }
            
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };

            html5QrCode.start(
                { facingMode: "environment" }, 
                config,
                (decodedText) => {
                    // Feedback visual
                    readerDiv.style.border = "5px solid #71dd37";
                    
                    // FORMAT SUPPORT: "Nama Alat|Serial" OR "Serial"
                    // Also handle common Dahua/Hikvision formats if they contain SN:
                    let sn = decodedText;
                    let name = "";

                    if (decodedText.includes('|')) {
                        const parts = decodedText.split('|');
                        name = parts[0];
                        sn = parts[1];
                    } else if (decodedText.includes('SN:')) {
                        // Dahua style maybe?
                        const match = decodedText.match(/SN:([A-Za-z0-9]+)/);
                        if (match) sn = match[1];
                    }

                    if (name) document.querySelector('input[name="nama_alat"]').value = name;
                    document.getElementById('input-sn-add').value = sn;
                    
                    // Notifikasi sukses kecil
                    const statusText = document.createElement('div');
                    statusText.className = 'alert alert-success py-1 px-2 mt-2 small';
                    statusText.innerHTML = 'Berhasil memindai: ' + sn;
                    readerDiv.after(statusText);
                    setTimeout(() => statusText.remove(), 3000);

                    // Stop after success
                    stopScanner();
                },
                (errorMessage) => {
                    // parse error, ignore
                }
            ).catch((err) => {
                console.error("Gagal start kamera", err);
                alert("Gagal mengakses kamera: " + err);
                stopScanner();
            });
        } else {
            stopScanner();
        }

        function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    readerDiv.style.display = 'none';
                    readerDiv.style.border = "none";
                    btn.innerHTML = '<i class="bx bx-qr-scan me-1"></i> Scan QR';
                    btn.classList.replace('text-danger', 'text-primary');
                });
            } else {
                readerDiv.style.display = 'none';
                btn.innerHTML = '<i class="bx bx-qr-scan me-1"></i> Scan QR';
                btn.classList.replace('text-danger', 'text-primary');
            }
        }
    });

    // CAMERA CAPTURE LOGIC FOR ADD MODAL
    const btnCameraAdd = document.getElementById('btn-camera-add');
    const cameraContainerAdd = document.getElementById('camera-container-add');
    const videoAdd = document.getElementById('video-add');
    const btnSnapAdd = document.getElementById('btn-snap-add');
    const previewContainerAdd = document.getElementById('preview-container-add');
    const imgPreviewAdd = document.getElementById('img-preview-add');
    const inputCapturedAdd = document.getElementById('input-captured-add');
    const inputFileAdd = document.getElementById('input-file-add');
    const btnRetryAdd = document.getElementById('btn-retry-add');

    let streamAdd = null;

    btnCameraAdd.addEventListener('click', async function() {
        try {
            streamAdd = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                } 
            });
            videoAdd.srcObject = streamAdd;
            cameraContainerAdd.style.display = 'block';
            btnCameraAdd.style.display = 'none';
            inputFileAdd.style.display = 'none';
        } catch (err) {
            console.error(err);
            alert("Tidak dapat mengakses kamera: " + err.message);
        }
    });

    btnSnapAdd.addEventListener('click', function() {
        const canvas = document.createElement('canvas');
        canvas.width = videoAdd.videoWidth;
        canvas.height = videoAdd.videoHeight;
        canvas.getContext('2d').drawImage(videoAdd, 0, 0);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
        
        imgPreviewAdd.src = dataUrl;
        inputCapturedAdd.value = dataUrl;
        
        cameraContainerAdd.style.display = 'none';
        previewContainerAdd.style.display = 'block';
        
        if (streamAdd) {
            streamAdd.getTracks().forEach(track => track.stop());
        }
    });

    btnRetryAdd.addEventListener('click', function() {
        previewContainerAdd.style.display = 'none';
        btnCameraAdd.style.display = 'block';
        inputFileAdd.style.display = 'block';
        btnCameraAdd.click();
    });

    // CAMERA LOGIC FOR EDIT MODALS
    document.querySelectorAll('.btn-camera-edit').forEach(btn => {
        btn.addEventListener('click', async function() {
            const modal = this.closest('.modal-body');
            const video = modal.querySelector('.video-edit');
            const container = modal.querySelector('.camera-container-edit');
            const inputFile = modal.querySelector('.input-file-edit');
            const currentImg = modal.querySelector('.img-current-edit');

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                video.dataset.streamId = stream.id; // Store for stopping
                container.style.display = 'block';
                this.style.display = 'none';
                inputFile.style.display = 'none';
                if(currentImg) currentImg.style.display = 'none';

                // Store stream locally to stop it later
                window['stream_' + stream.id] = stream;
            } catch (err) {
                alert("Gagal kamera: " + err.message);
            }
        });
    });

    document.querySelectorAll('.btn-snap-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-body');
            const video = modal.querySelector('.video-edit');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            
            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
            const preview = modal.querySelector('.img-preview-edit');
            const container = modal.querySelector('.preview-container-edit');
            const camContainer = modal.querySelector('.camera-container-edit');
            const input = modal.querySelector('.input-captured-edit');

            preview.src = dataUrl;
            input.value = dataUrl;
            camContainer.style.display = 'none';
            container.style.display = 'block';

            const stream = window['stream_' + video.dataset.streamId];
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    });

    document.querySelectorAll('.btn-retry-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-body');
            const container = modal.querySelector('.preview-container-edit');
            const camBtn = modal.querySelector('.btn-camera-edit');
            container.style.display = 'none';
            camBtn.click();
        });
    });

    function showImage(src, title) {
        document.getElementById('fullImage').src = src;
        document.getElementById('imageTitle').innerText = title;
        new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
    }
</script>
@endsection

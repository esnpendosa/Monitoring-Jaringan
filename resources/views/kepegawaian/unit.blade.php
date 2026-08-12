@extends('layouts.app')

@section('title', 'Pengaturan Unit Kerja')

@section('content')
<div class="row g-4">
    <!-- Premium Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 24px;">
            <div class="bg-pmu p-4 p-md-5 text-white position-relative">
                <h2 class="fw-bold mb-0 text-uppercase ls-1"><i class="fa-solid fa-building-shield me-2"></i> PENGATURAN UNIT KERJA</h2>
                <p class="mb-0 opacity-75 fw-medium">Kelola daftar unit kerja di lingkungan PMU Bungah secara mandiri.</p>
                <i class="fa-solid fa-hotel position-absolute end-0 top-0 m-4 fa-6x opacity-10"></i>
            </div>
        </div>
    </div>

    <div class="col-12 pb-5">
        @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center animate__animated animate__fadeIn">
            <i class="fa-solid fa-circle-check fs-4 me-3"></i>
            <span class="fw-bold">{{ session('success') }}</span>
        </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-sticky" style="top: 100px;">
                    <div class="card-header bg-white border-0 p-4">
                        <h6 class="fw-bold text-dark mb-0 text-uppercase ls-1"><i class="fa-solid fa-plus-circle me-2 text-pmu"></i> TAMBAH UNIT BARU</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <form action="{{ route('kepegawaian.unit.store') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Nama Unit Kerja</label>
                                <input type="text" name="nama" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" placeholder="Contoh: Madrasah Aliyah" required>
                                @error('nama') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Keterangan (Opsional)</label>
                                <textarea name="keterangan" class="form-control rounded-4 border p-4 shadow-none" rows="3" placeholder="Berikan deskripsi singkat unit ini..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-pmu w-100 rounded-pill py-3 fw-bold shadow-sm">
                                <i class="fa-solid fa-save me-2"></i> SIMPAN UNIT KERJA
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 p-4">
                        <h6 class="fw-bold text-dark mb-0 text-uppercase ls-1"><i class="fa-solid fa-list me-2 text-pmu"></i> DAFTAR UNIT SAAT INI</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light bg-opacity-50 border-bottom">
                                    <tr class="text-uppercase small fw-bold text-dark ls-1">
                                        <th class="ps-4 py-3">NAMA UNIT KERJA</th>
                                        <th class="py-3">KETERANGAN</th>
                                        <th class="pe-4 text-end py-3">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($units as $unit)
                                    <tr>
                                        <td class="ps-4 py-3 fw-bold text-dark text-uppercase fs-6">
                                            {{ $unit->nama }}
                                        </td>
                                        <td class="py-3 small text-muted">
                                            {{ $unit->keterangan ?: '-' }}
                                        </td>
                                        <td class="pe-4 text-end py-3">
                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-pmu me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editUnitModal" 
                                                data-id="{{ $unit->id }}" 
                                                data-nama="{{ $unit->nama }}" 
                                                data-keterangan="{{ $unit->keterangan }}">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> EDIT
                                            </button>
                                            <form action="{{ route('kepegawaian.unit.destroy', $unit->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus unit ini? Pegawai yang terdaftar di unit ini mungkin terpengaruh.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-danger">
                                                    <i class="fa-solid fa-trash-can me-1"></i> HAPUS
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="py-5 text-center">
                                            <div class="opacity-25 mb-3"><i class="fa-solid fa-building-circle-exclamation fa-4x text-muted"></i></div>
                                            <h6 class="text-muted fw-bold">Belum ada unit kerja yang didaftarkan.</h6>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Edit Unit Modal -->
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold text-uppercase ls-1" id="editUnitModalLabel"><i class="fa-solid fa-pen-to-square me-2 text-pmu"></i> EDIT UNIT KERJA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUnitForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Nama Unit Kerja</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Keterangan (Opsional)</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control rounded-4 border p-4 shadow-none" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-pmu rounded-pill px-4 fw-bold shadow-sm">SIMPAN PERUBAHAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editUnitModal = document.getElementById('editUnitModal');
    if (editUnitModal) {
        editUnitModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nama = button.getAttribute('data-nama');
            const keterangan = button.getAttribute('data-keterangan');

            const form = document.getElementById('editUnitForm');
            form.action = `/manage/units/${id}`;
            
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_keterangan').value = keterangan;
        });
    }
});
</script>
@endpush

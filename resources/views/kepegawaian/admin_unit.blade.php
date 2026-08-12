@extends('layouts.app')

@section('title', 'Kelola Admin Unit')

@section('content')
<div class="container-fluid px-0">
    <div class="bg-pmu p-4 p-md-5 text-white mb-4">
        <h2 class="fw-bold mb-0 text-uppercase ls-1"><i class="fa-solid fa-user-shield me-2"></i> KELOLA AKUN ADMIN UNIT</h2>
        <p class="mb-0 opacity-75 fw-medium">Kelola akses khusus untuk Admin di setiap Unit Kerja PMU Bungah.</p>
    </div>

    <div class="px-3 px-md-5 pb-5">
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
                        <h6 class="fw-bold text-dark mb-0 text-uppercase ls-1"><i class="fa-solid fa-user-plus me-2 text-pmu"></i> TAMBAH ADMIN UNIT</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <form action="{{ route('kepegawaian.admin_unit.store') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" placeholder="Nama Admin" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Email Login</label>
                                <input type="email" name="email" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" placeholder="email@pmub.my.id" required>
                                @error('email') <small class="text-danger mt-1 d-block small">{{ $message }}</small> @enderror
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Password Awal</label>
                                <input type="password" name="password" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" placeholder="Minimal 8 Karakter" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Penugasan Unit</label>
                                <select name="unit" class="form-select rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                                    <option value="">-- PILIH UNIT --</option>
                                    @foreach($units as $u)
                                    <option value="{{ $u->nama }}">{{ strtoupper($u->nama) }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted mt-2 d-block small">Gunakan menu <b>Pengaturan Unit</b> jika unit belum ada.</small>
                            </div>
                            <button type="submit" class="btn btn-pmu w-100 rounded-pill py-3 fw-bold shadow-sm">
                                <i class="fa-solid fa-user-check me-2"></i> BUAT AKUN ADMIN
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 p-4">
                        <h6 class="fw-bold text-dark mb-0 text-uppercase ls-1"><i class="fa-solid fa-users-gear me-2 text-pmu"></i> DAFTAR ADMIN UNIT</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light bg-opacity-50 border-bottom">
                                    <tr class="text-uppercase small fw-bold text-dark ls-1">
                                        <th class="ps-4 py-3">ADMIN</th>
                                        <th class="py-3">UNIT PENUGASAN</th>
                                        <th class="pe-4 text-end py-3">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($admins as $admin)
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-none border border-2 border-white" style="width: 40px; height: 40px; font-size: 1rem;">
                                                    {{ substr($admin->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark mb-0 text-uppercase small">{{ $admin->name }}</div>
                                                    <small class="text-muted small">{{ $admin->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <span class="badge-unit">{{ $admin->unit }}</span>
                                        </td>
                                        <td class="pe-4 text-end py-3">
                                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-pmu me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editAdminModal" 
                                                data-id="{{ $admin->id }}" 
                                                data-name="{{ $admin->name }}" 
                                                data-email="{{ $admin->email }}" 
                                                data-unit="{{ $admin->unit }}">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> EDIT
                                            </button>
                                            <form action="{{ route('kepegawaian.admin_unit.destroy', $admin->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus akses admin ini? Pengguna akan kehilangan hak akses admin unit.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-danger">
                                                    <i class="fa-solid fa-trash-can me-1"></i> HAPUS AKSES
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="py-5 text-center">
                                            <div class="opacity-25 mb-3"><i class="fa-solid fa-user-shield fa-4x text-muted"></i></div>
                                            <h6 class="text-muted fw-bold">Belum ada Admin Unit yang didaftarkan.</h6>
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
<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold text-uppercase ls-1" id="editAdminModalLabel"><i class="fa-solid fa-user-pen me-2 text-pmu"></i> EDIT ADMIN UNIT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAdminForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Nama Lengkap</label>
                        <input type="text" name="name" id="edit_name" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Email Login</label>
                        <input type="email" name="email" id="edit_email" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Penugasan Unit</label>
                        <select name="unit" id="edit_unit" class="form-select rounded-pill border py-3 px-4 shadow-none fw-bold" required>
                            <option value="">-- PILIH UNIT --</option>
                            @foreach($units as $u)
                            <option value="{{ $u->nama }}">{{ strtoupper($u->nama) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Password Baru (Kosongkan jika tidak ganti)</label>
                        <input type="password" name="password" class="form-control rounded-pill border py-3 px-4 shadow-none fw-bold" placeholder="Minimal 8 Karakter">
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
    const editAdminModal = document.getElementById('editAdminModal');
    if (editAdminModal) {
        editAdminModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const email = button.getAttribute('data-email');
            const unit = button.getAttribute('data-unit');

            const form = document.getElementById('editAdminForm');
            form.action = `/manage/admins/${id}`;
            
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_unit').value = unit;
        });
    }
});
</script>
@endpush

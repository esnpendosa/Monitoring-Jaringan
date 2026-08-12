@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Pengguna')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Pengguna /</span> Edit</h4>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ $user->name }}" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (Kosongkan jika tidak ingin mengubah)</label>
                        <input type="password" name="password" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="id_role" id="id_role" class="form-select" required onchange="togglePelangganLink()">
                            @foreach($roles as $role)
                                <option value="{{ $role->id_role }}" data-name="{{ strtolower($role->name) }}" {{ $user->id_role == $role->id_role ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PIN Sidik Jari (Fingerspot / Solution)</label>
                        <input type="text" name="pin_fingerspot" class="form-control" value="{{ $user->pin_fingerspot }}" placeholder="Contoh: 12" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. WhatsApp / HP</label>
                        <input type="text" name="no_hp" class="form-control" value="{{ $user->no_hp }}" placeholder="Contoh: 6281234567890" />
                    </div>

                    <div class="mb-3" id="pelanggan_link_group" style="display: none;">
                        <label class="form-label text-primary fw-bold">Hubungkan ke Data Pelanggan</label>
                        <select name="id_pelanggan" class="form-select border-primary">
                            <option value="">-- Pilih Data Pelanggan --</option>
                            @foreach($pelangganList as $p)
                                <option value="{{ $p->id_pelanggan }}" {{ $user->pelanggan && $user->pelanggan->id_pelanggan == $p->id_pelanggan ? 'selected' : '' }}>
                                    {{ $p->kode_pelanggan }} - {{ $p->nama_pelanggan }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pilih pelanggan agar user ini bisa melihat trafik wifi-nya sendiri.</small>
                    </div>

                    <script>
                        function togglePelangganLink() {
                            const roleSelect = document.getElementById('id_role');
                            const selectedRoleName = roleSelect.options[roleSelect.selectedIndex].getAttribute('data-name');
                            const linkGroup = document.getElementById('pelanggan_link_group');
                            
                            if (selectedRoleName === 'pelanggan') {
                                linkGroup.style.display = 'block';
                            } else {
                                linkGroup.style.display = 'none';
                            }
                        }
                        // Initialize
                        document.addEventListener('DOMContentLoaded', togglePelangganLink);
                    </script>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('users.index') }}" class="btn btn-label-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

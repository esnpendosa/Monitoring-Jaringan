@extends('layouts/contentNavbarLayout')

@section('title', 'Manajemen Chatbot')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Sistem /</span> Auto Responder Bot</h4>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Tambah Respon Baru</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('bot.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Kata Kunci (Keyword)</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Contoh: ping, halo, alamat" required>
                        <small class="text-muted">Gunakan koma untuk banyak kata (misal: halo,hai,pagi)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label Menu (Judul di List/Tombol)</label>
                        <input type="text" name="menu_label" class="form-control" placeholder="Contoh: 💰 Cek Tagihan AD20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teks Balasan (Response)</label>
                        <textarea name="response" class="form-control" rows="5" placeholder="Halo! Ada yang bisa kami bantu?" required></textarea>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_exact_match" id="exactMatch" value="1" checked>
                        <label class="form-check-label" for="exactMatch">Cocok Sama Persis (Wajib untuk Menu)</label>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_menu" id="isMenu" value="1">
                        <label class="form-check-label" for="isMenu">Jadikan Menu (List Pilihan)</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Menu (Opsional)</label>
                        <select name="parent_id" id="parent_id" class="form-select">
                            <option value="">-- Menu Utama --</option>
                            @foreach($parentMenus as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->keyword }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urutan (Sort Order)</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="group_enabled" id="groupEnabled" value="1">
                        <label class="form-check-label" for="groupEnabled">Aktif di Grup</label>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
                        <label class="form-check-label" for="isActive">Aktif</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Respon</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Daftar Respon Otomatis</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kata Kunci</th>
                            <th>Balasan</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($responses as $r)
                        <tr>
                            <td>
                                <strong>{{ $r->keyword }}</strong>
                                @if($r->menu_label)
                                <br><small class="text-primary">{{ $r->menu_label }}</small>
                                @endif
                            </td>
                            <td class="text-wrap" style="max-width: 300px;">{{ Str::limit($r->response, 50) }}</td>
                            <td>
                                @if($r->is_menu)
                                <span class="badge bg-label-primary">Menu</span>
                                @if($r->parent_id)
                                <br><small class="text-muted">Sub of: {{ $r->parent->keyword ?? '?' }}</small>
                                @endif
                                @elseif($r->is_exact_match)
                                <span class="badge bg-label-warning">Exact Match</span>
                                @else
                                <span class="badge bg-label-info">Contains</span>
                                @endif
                                @if($r->group_enabled)
                                <br><span class="badge bg-label-dark">Group OK</span>
                                @endif
                            </td>
                            <td>
                                @if($r->is_active)
                                <span class="badge bg-label-success">Aktif</span>
                                @else
                                <span class="badge bg-label-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-icon btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $r->id }}">
                                    <i class="bx bx-edit"></i>
                                </button>
                                <form action="{{ route('bot.destroy', $r->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-delete" title="Hapus Respon">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="modalEdit{{ $r->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('bot.update', $r->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Respon Bot</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Kata Kunci (Keyword)</label>
                                                <input type="text" name="keyword" class="form-control" value="{{ $r->keyword }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Label Menu</label>
                                                <input type="text" name="menu_label" class="form-control" value="{{ $r->menu_label }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Teks Balasan (Response)</label>
                                                <textarea name="response" class="form-control" rows="5" required>{{ $r->response }}</textarea>
                                            </div>
                                            <div class="mb-3 form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_exact_match" id="exact{{ $r->id }}" value="1" {{ $r->is_exact_match ? 'checked' : '' }}>
                                                <label class="form-check-label" for="exact{{ $r->id }}">Cocok Sama Persis</label>
                                            </div>
                                            <div class="mb-3 form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_menu" id="isMenu{{ $r->id }}" value="1" {{ $r->is_menu ? 'checked' : '' }}>
                                                <label class="form-check-label" for="isMenu{{ $r->id }}">Jadikan Menu</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Parent Menu</label>
                                                <select name="parent_id" class="form-select">
                                                    <option value="">-- Menu Utama --</option>
                                                    @foreach($parentMenus as $pm)
                                                    @if($pm->id != $r->id)
                                                    <option value="{{ $pm->id }}" {{ $r->parent_id == $pm->id ? 'selected' : '' }}>{{ $pm->keyword }}</option>
                                                    @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Urutan</label>
                                                <input type="number" name="sort_order" class="form-control" value="{{ $r->sort_order }}">
                                            </div>
                                            <div class="mb-3 form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="group_enabled" id="group{{ $r->id }}" value="1" {{ $r->group_enabled ? 'checked' : '' }}>
                                                <label class="form-check-label" for="group{{ $r->id }}">Aktif di Grup</label>
                                            </div>
                                            <div class="mb-3 form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="active{{ $r->id }}" value="1" {{ $r->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="active{{ $r->id }}">Aktif</label>
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
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data respon otomatis.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-style')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('parent_id')) {
            new TomSelect("#parent_id", {
                create: false,
                placeholder: "-- Menu Utama --"
            });
        }

        // SweetAlert Confirmation for Delete Bot Response
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                
                Swal.fire({
                    title: 'Hapus Respon Bot?',
                    text: "Apakah Anda yakin ingin menghapus respon otomatis untuk kata kunci ini?",
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

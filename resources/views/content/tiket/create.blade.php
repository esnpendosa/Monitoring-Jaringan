@extends('layouts/contentNavbarLayout')

@section('title', 'Buat Tiket Gangguan')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Tiket /</span> Buat Baru</h4>

<div class="card shadow-sm border-0 bg-white">
  <div class="card-body">
    <form action="{{ route('tiket.store') }}" method="POST">
      @csrf
      
      @if($isPelanggan)
        <!-- Customer View: Read-only name, auto-selected profile -->
        <div class="mb-3">
          <label class="form-label fw-bold">Nama Pelanggan</label>
          <input type="text" class="form-control bg-light border-0 fw-semibold" value="{{ $pelanggan[0]->nama_pelanggan }} ({{ $pelanggan[0]->kode_pelanggan }})" readonly style="border-radius: 8px;">
          <input type="hidden" name="id_pelanggan" value="{{ $pelanggan[0]->id_pelanggan }}">
          <input type="hidden" name="prioritas" value="Low">
        </div>
      @else
        <!-- Admin/Staff View: Full Customer Dropdown with TomSelect Search -->
        <div class="mb-3">
          <label class="form-label fw-bold">Pelanggan</label>
          <select name="id_pelanggan" id="pelanggan_select" class="form-select" required>
            <option value="">-- Pilih Pelanggan --</option>
            @foreach($pelanggan as $p)
              <option value="{{ $p->id_pelanggan }}" data-priority="{{ $p->prioritas_label }}">{{ $p->id_pelanggan }} - {{ $p->nama_pelanggan }} ({{ $p->prioritas_label }})</option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="mb-3">
        <label class="form-label fw-bold">Keluhan / Masalah</label>
        <textarea name="keluhan" class="form-control border-light-subtle" rows="4" placeholder="Jelaskan kendala koneksi internet Anda secara detail... (Contoh: Lampu LOS merah, Sinyal lemah, koneksi putus-putus)" required style="border-radius: 8px; resize: none;"></textarea>
      </div>

      @if(!$isPelanggan)
        <!-- Admin/Staff View: Priority and Technician Selectors -->
        <div class="mb-3">
          <label class="form-label fw-bold">Prioritas</label>
          <select name="prioritas" id="prioritas_select" class="form-select" required>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
          </select>
          <small class="text-muted">Prioritas otomatis terisi berdasarkan hasil klasifikasi KNN terakhir.</small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Assign Teknisi (Opsional)</label>
          <select name="id_teknisi" id="teknisi_select" class="form-select">
            <option value="">-- Pilih Teknisi --</option>
            @foreach($teknisi as $t)
              <option value="{{ $t->id_teknisi }}">{{ $t->nama_teknisi }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
          <i class="bx bx-check"></i> Buat Tiket
        </button>
        <a href="{{ route('tiket.index') }}" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

@endsection

@section('page-style')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('pelanggan_select')) {
        new TomSelect("#pelanggan_select", {
            create: false,
            placeholder: "-- Pilih Pelanggan --"
        });
    }

    if (document.getElementById('teknisi_select')) {
        new TomSelect("#teknisi_select", {
            create: false,
            placeholder: "-- Pilih Teknisi --"
        });
    }

    const pelangganSelect = document.getElementById('pelanggan_select');
    if (pelangganSelect) {
        pelangganSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption) {
                const priority = selectedOption.getAttribute('data-priority');
                if (priority && priority !== 'Unclassified') {
                    const prioritasSelect = document.getElementById('prioritas_select');
                    if (prioritasSelect) {
                        prioritasSelect.value = priority;
                    }
                }
            }
        });
    }
});
</script>
@endsection

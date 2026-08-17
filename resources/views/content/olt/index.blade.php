@extends('layouts/contentNavbarLayout')
@section('title', 'Manajemen OLT')
@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Jaringan /</span> Manajemen OLT</h4>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Daftar OLT (Optical Line Terminal)</h5>
    <a href="{{ route('olt.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Tambah OLT</a>
  </div>
  <div class="card-body p-0">
    @if(session('success'))
      <div class="alert alert-success m-3 mb-0">{{ session('success') }}</div>
    @endif
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Nama OLT</th><th>IP Address</th><th>Kapasitas PON</th><th>Lokasi</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($olts as $olt)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td><strong>{{ $olt->nama }}</strong></td>
            <td><code>{{ $olt->ip_address ?? '-' }}</code></td>
            <td>{{ $olt->kapasitas_pon }} port</td>
            <td class="text-muted small">{{ $olt->lokasi ?? '-' }}</td>
            <td>
              @php $sc = ['online'=>'success','warning'=>'warning','offline'=>'danger'][$olt->status] ?? 'secondary' @endphp
              <span class="badge bg-{{ $sc }}">{{ strtoupper($olt->status) }}</span>
            </td>
            <td>
              <a href="{{ route('olt.show', $olt) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bx bx-show"></i></a>
              <a href="{{ route('olt.edit', $olt) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
              <form action="{{ route('olt.destroy', $olt) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus OLT ini?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bx bx-trash"></i></button>
              </form>
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data OLT. <a href="{{ route('olt.create') }}">Tambah sekarang</a>.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

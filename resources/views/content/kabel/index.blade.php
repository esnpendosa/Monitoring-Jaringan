@extends('layouts/contentNavbarLayout')
@section('title', 'Manajemen Kabel FTTH')
@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Jaringan /</span> Manajemen Kabel FTTH</h4>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Daftar Kabel</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('ftth.map') }}" class="btn btn-sm btn-outline-info"><i class="bx bx-map me-1"></i>Lihat di Peta</a>
      <a href="{{ route('kabel.create') }}" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>Tambah Kabel</a>
    </div>
  </div>
  <div class="card-body p-0">
    @if(session('success'))
      <div class="alert alert-success m-3 mb-0">{{ session('success') }}</div>
    @endif
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Label / Kode</th><th>Tipe</th><th>Monitoring</th>
            <th>Core</th><th>Redaman</th><th>Status</th><th>Update By</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($kabels as $k)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td><strong>{{ $k->label }}</strong>
              @if($k->titik_putus_meter)
                <br><small class="text-danger"><i class="bx bx-error-circle"></i> Putus ±{{ $k->titik_putus_meter }}m</small>
              @endif
            </td>
            <td>
              @php $tc=['feeder'=>'primary','distribusi'=>'info','drop'=>'secondary'] @endphp
              <span class="badge bg-label-{{ $tc[$k->tipe] ?? 'secondary' }}">{{ $k->tipe_label }}</span>
            </td>
            <td>
              @if($k->monitoring_type === 'realtime')
                <span class="badge bg-label-info"><i class="bx bx-wifi me-1"></i>Realtime</span>
              @else
                <span class="badge bg-label-secondary">Manual</span>
              @endif
            </td>
            <td>{{ $k->jumlah_core }} core</td>
            <td>{{ $k->redaman_db ? $k->redaman_db.' dB' : '-' }}</td>
            <td>
              @php $sc=['online'=>'success','warning'=>'warning','offline'=>'danger'][$k->status]??'secondary' @endphp
              <span class="badge bg-{{ $sc }}">{{ strtoupper($k->status) }}</span>
            </td>
            <td class="text-muted small">{{ $k->updated_by ?? '-' }}</td>
            <td>
              <a href="{{ route('kabel.show', $k) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bx bx-show"></i></a>
              <a href="{{ route('kabel.edit', $k) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
              <form action="{{ route('kabel.destroy', $k) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kabel ini?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bx bx-trash"></i></button>
              </form>
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center text-muted py-4">Belum ada kabel. <a href="{{ route('kabel.create') }}">Tambah sekarang</a>.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

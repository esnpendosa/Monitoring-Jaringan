@extends('layouts/contentNavbarLayout')
@section('title', 'Detail OLT')
@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">OLT /</span> {{ $olt->nama }}</h4>

<div class="row g-4">
  {{-- Info Card --}}
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-header"><h6 class="mb-0">Informasi OLT</h6></div>
      <div class="card-body">
        @php $sc = ['online'=>'success','warning'=>'warning','offline'=>'danger'][$olt->status] ?? 'secondary' @endphp
        <span class="badge bg-{{ $sc }} mb-3">{{ strtoupper($olt->status) }}</span>
        <table class="table table-sm table-borderless mb-0">
          <tr><th class="text-muted" style="width:40%">IP Address</th><td><code>{{ $olt->ip_address ?? '-' }}</code></td></tr>
          <tr><th class="text-muted">SNMP</th><td>{{ $olt->snmp_community }}</td></tr>
          <tr><th class="text-muted">PON Port</th><td>{{ $olt->kapasitas_pon }}</td></tr>
          <tr><th class="text-muted">Lokasi</th><td>{{ $olt->lokasi ?? '-' }}</td></tr>
          <tr><th class="text-muted">Koordinat</th><td>{{ $olt->latitude }}, {{ $olt->longitude }}</td></tr>
          <tr><th class="text-muted">Deskripsi</th><td>{{ $olt->deskripsi ?? '-' }}</td></tr>
        </table>
        <div class="mt-3 d-flex gap-2">
          <a href="{{ route('olt.edit', $olt) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit me-1"></i>Edit</a>
          <a href="{{ route('ftth.map') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-map me-1"></i>Lihat di Peta</a>
        </div>
      </div>
    </div>
  </div>

  {{-- Hierarki ODC di bawah OLT ini --}}
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header"><h6 class="mb-0">Hierarki ODC → ODP → Pelanggan</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr><th>ODC</th><th>ODP Turunan</th><th>Pelanggan</th><th>Status</th></tr>
            </thead>
            <tbody>
              @forelse($olt->odcList as $odc)
              <tr>
                <td><strong>{{ $odc->nama }}</strong></td>
                <td>{{ $odc->children->count() }} ODP</td>
                <td>{{ $odc->children->sum(fn($o) => $o->pelanggan->count()) }} pelanggan</td>
                <td>
                  @php $s = $odc->status ?? 'online'; $c = ['online'=>'success','warning'=>'warning','offline'=>'danger'][$s]??'secondary' @endphp
                  <span class="badge bg-{{ $c }}">{{ strtoupper($s) }}</span>
                </td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-center text-muted py-3">Belum ada ODC terhubung ke OLT ini.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

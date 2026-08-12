@extends('layouts.app')

@section('title', 'Biodata Pegawai')

@section('content')
@php
    $riwayatPend = $biodata->riwayat_pendidikan ?? [];
    $children = $biodata->keluarga['anak'] ?? [];
    $isOwner = Auth::user()->id === $user->id;
@endphp

<div class="row g-4 mb-5">
    <!-- Header Profile -->
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 24px;">
            <div class="card-body p-0">
                <div class="bg-pmu p-4 p-md-5 text-white position-relative" style="min-height: 160px;">
                    <div class="position-relative z-index-1">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-white text-pmu rounded-pill px-3 py-1 fw-bold small shadow-sm">PORTAL PEGAWAI</span>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold small shadow-sm">STATUS: {{ $biodata->status_pegawai ?? 'Aktif' }}</span>
                        </div>
                        <h3 class="fw-bold mb-1">PROFIL BIODATA</h3>
                        <p class="opacity-75 mb-0 small">
                            @if($isOwner)
                                Kelola informasi data diri, keluarga, dan riwayat karir Anda secara digital.
                            @else
                                Melihat informasi data diri, keluarga, dan riwayat karir: <strong>{{ $user->name }}</strong>
                            @endif
                        </p>
                    </div>
                    <i class="fa-solid fa-address-card position-absolute end-0 top-0 m-4 fa-6x opacity-10 d-none d-lg-block"></i>
                </div>
                <div class="px-4 px-md-5 pb-4" style="margin-top: -50px;">
                    <div class="row align-items-end g-4">
                        <div class="col-auto">
                            <div class="profile-photo-wrapper bg-white p-1 rounded-circle shadow-lg position-relative" style="width: 130px; height: 130px;">
                                <img src="{{ $user->getPhoto() }}" 
                                     id="profilePreview" class="rounded-circle w-100 h-100 object-fit-cover shadow-inner" alt="Profile">
                                
                                @if($isOwner)
                                <form id="fotoForm" action="{{ route('kepegawaian.biodata.foto') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <label for="fotoInput" class="photo-edit-btn position-absolute bottom-0 end-0 bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm cursor-pointer" style="width: 35px; height: 35px; border: 3px solid white;">
                                        <i class="fa-solid fa-camera small"></i>
                                    </label>
                                    <input type="file" name="foto" id="fotoInput" class="d-none" accept="image/*" onchange="this.form.submit()">
                                </form>
                                @endif
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-2">
                                <h3 class="fw-bold text-dark mb-1">{{ $user->name }}</h3>
                                <div class="d-flex gap-3 flex-wrap align-items-center">
                                    <div class="small fw-bold text-muted"><i class="fa-solid fa-id-card-clip text-pmu me-2"></i>{{ $biodata->kode_pegawai ?? 'ID-PENDING' }}</div>
                                    <div class="small fw-bold text-muted d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-building text-pmu"></i>
                                        <span class="badge-unit" style="padding: 4px 15px; font-size: 0.7rem;">{{ $user->unit ?? 'UMUM' }}</span>
                                    </div>
                                    <div class="small fw-bold text-muted d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-user-tag text-pmu"></i>
                                        <span class="text-uppercase">{{ $user->role }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($isOwner || Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                        <div class="col-lg-auto ms-auto mb-2 text-center text-lg-end">
                            <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold transition-hover" data-bs-toggle="modal" data-bs-target="#editModal">
                                <i class="fa-solid fa-user-gear me-2"></i> EDIT BIODATA
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="col-12">
        <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center mb-0 p-4">
            <i class="fa-solid fa-circle-check fs-3 me-3 text-success"></i>
            <div>
                <h6 class="fw-bold mb-0">Update Berhasil!</h6>
                <p class="small mb-0 text-muted">{{ session('success') }}</p>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    @error('foto')
    <div class="col-12">
        <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 mb-0">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ $message }}
        </div>
    </div>
    @enderror

    @if($errors->any())
    <div class="col-12">
        <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-start mb-0 p-4 animate__animated animate__fadeIn">
            <i class="fa-solid fa-circle-exclamation fs-3 me-3 text-danger mt-1"></i>
            <div>
                <h6 class="fw-bold mb-1">Gagal Menyimpan Data!</h6>
                <ul class="mb-0 small ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    <!-- Primary Identity -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-white border-0 py-4 px-4">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                    <span class="bg-pmu text-white rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-info" style="font-size: 0.8rem;"></i>
                    </span>
                    IDENTITAS UTAMA
                </h6>
            </div>
            <div class="card-body p-4 pt-0">
                <div class="info-group mb-4">
                    <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Nomor Induk Pegawai / NIK</div>
                    <div class="fw-bold text-dark border-bottom pb-2">{{ $biodata->nik ?? '-' }} <span class="text-muted fw-normal ms-2 opacity-50 small">| NIK</span></div>
                </div>
                <div class="info-group mb-4">
                    <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Tempat, Tanggal Lahir</div>
                    <div class="fw-bold text-dark border-bottom pb-2">{{ $biodata->tempat_lahir ?? '-' }}, {{ $biodata->tgl_lahir ? $biodata->tgl_lahir->format('d F Y') : '-' }}</div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="info-group mb-4">
                            <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Jenis Kelamin</div>
                            <div class="fw-bold text-dark border-bottom pb-2">{{ $biodata->jenis_kelamin ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-group mb-4">
                            <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Agama</div>
                            <div class="fw-bold text-dark border-bottom pb-2">{{ $biodata->agama ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="info-group mb-4">
                            <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Gol. Darah</div>
                            <div class="fw-bold text-dark border-bottom pb-2 text-center bg-light rounded py-1" style="max-width: 50px;">{{ $biodata->gol_darah ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-group mb-4">
                            <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">WhatsApp</div>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $biodata->no_wa) }}" target="_blank" class="fw-bold text-success border-bottom pb-2 d-block text-decoration-none">
                                <i class="fa-brands fa-whatsapp me-1"></i> {{ $biodata->no_wa ?? '-' }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="info-group mb-4">
                            <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Tanggal Masuk</div>
                            <div class="fw-bold text-dark border-bottom pb-2 text-pmu">{{ $biodata->tgl_masuk ? $biodata->tgl_masuk->format('d/m/Y') : '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-group mb-4">
                            <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Umur</div>
                            <div class="fw-bold text-dark border-bottom pb-2">{{ $biodata->getAge() }} <span class="text-muted small fw-normal">Tahun</span></div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="info-group mb-4">
                            <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Purna Tugas</div>
                            <div class="fw-bold text-dark border-bottom pb-2">{{ $biodata->getPurnaTugas() }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-group mb-4">
                            <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Sertifikat Pendidik</div>
                            <div class="pb-2 border-bottom d-flex align-items-center justify-content-between">
                                @php 
                                    $sertif = $dokumens->where('tipe', 'Sertifikat Pendidik')->whereIn('status', ['Valid', 'Disetujui'])->first(); 
                                @endphp
                                @if($sertif)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success rounded-pill px-3">Ya</span>
                                        <a href="{{ route('kepegawaian.dokumen.view', ['path' => $sertif->file_path]) }}" target="_blank" class="btn btn-xs btn-primary rounded-pill px-2 py-0 fw-bold" style="font-size: 0.65rem;">
                                            Buka dokumen
                                        </a>
                                    </div>
                                @else
                                    <span class="badge bg-danger rounded-pill px-3 opacity-75">Tidak</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-group mb-4">
                    <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Status Pegawai</div>
                    <div class="pb-2 border-bottom d-flex align-items-center justify-content-between">
                        @if(($biodata->status_pegawai ?? 'Aktif') == 'Aktif')
                            <span class="badge bg-success rounded-pill px-4 fw-bold">AKTIF</span>
                        @else
                            <span class="badge bg-danger rounded-pill px-4 fw-bold">TIDAK AKTIF</span>
                        @endif
                        
                        @if(Auth::user()->isYayasan())
                            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editModal" class="small text-decoration-none text-muted fst-italic">
                                <i class="fa-solid fa-pen-to-square me-1"></i>Ubah Status
                            </a>
                        @endif
                    </div>
                </div>

                <div class="info-group mb-4">
                    <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Data Perbankan (Rekening)</div>
                    <div class="fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-credit-card me-2 text-muted opacity-50"></i> {{ $biodata->rekening ?? '-' }}</div>
                </div>
                <div class="info-group">
                    <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Alamat Domisili</div>
                    <div class="p-3 bg-light rounded-4 fw-bold text-dark mb-0 lh-base" style="font-size: 0.85rem;">
                        <i class="fa-solid fa-map-location-dot me-2 text-muted opacity-50"></i>{{ $biodata->alamat ?? 'Alamat belum diisi lengkap.' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <span class="bg-pmu text-white rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-folder-open" style="font-size: 0.8rem;"></i>
                            </span>
                            ARSIP DOKUMEN DIGITAL
                        </h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            @php
                                $docList = ['KTP', 'KK', 'Akte Kelahiran'];
                                
                                // Add specific Ijazah cards if found in history (S1, S2, S3)
                                $hasDegree = false;
                                foreach($riwayatPend as $rp) {
                                    if(isset($rp['jenjang']) && in_array($rp['jenjang'], ['S1', 'S2', 'S3'])) {
                                        $docList[] = 'Ijazah ' . $rp['jenjang'];
                                        $hasDegree = true;
                                    }
                                }
                                
                                // Fallback to Ijazah Terakhir if no S1/S2/S3 found
                                if(!$hasDegree) $docList[] = 'Ijazah Terakhir';
                                
                                $docList[] = 'Sertifikat Pendidik';
                                $docList = array_unique($docList);
                            @endphp
                            @foreach($docList as $docType)
                                @php 
                                    $doc = $dokumens->where('tipe', $docType)->where('status', 'Disetujui')->first(); 
                                @endphp
                                <div class="col-md-6 col-xl-3">
                                    <div class="p-3 rounded-4 border bg-light h-100 d-flex flex-column align-items-center text-center transition-hover">
                                        <div class="mb-2">
                                            @if($doc)
                                                <i class="fa-solid fa-file-circle-check text-success fa-2x mb-2"></i>
                                            @else
                                                <i class="fa-solid fa-file-circle-question text-muted opacity-25 fa-2x mb-2"></i>
                                            @endif
                                        </div>
                                        <div class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 0.7rem;">{{ $docType }}</div>
                                        @if($doc)
                                            <a href="{{ route('kepegawaian.dokumen.view', ['path' => $doc->file_path]) }}" target="_blank" class="btn btn-sm btn-pmu rounded-pill px-3 w-100 fw-bold shadow-none" style="font-size: 0.65rem;">
                                                LIHAT DATA
                                            </a>
                                        @else
                                            @if($isOwner)
                                                <button onclick="setDocType('{{ $docType }}')" data-bs-toggle="modal" data-bs-target="#uploadDocModal" class="btn btn-sm btn-outline-secondary rounded-pill px-3 w-100 fw-bold border-dashed" style="font-size: 0.65rem;">
                                                    UNGGAH
                                                </button>
                                            @else
                                                <span class="badge bg-light text-muted rounded-pill w-100">BELUM ADA</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <span class="bg-primary text-white rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-graduation-cap" style="font-size: 0.8rem;"></i>
                            </span>
                            RIWAYAT PENDIDIKAN
                        </h6>
                        @if($isOwner)
                        <button class="btn btn-sm btn-outline-success rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#editPendidikanModal">
                            <i class="fa-solid fa-edit me-1"></i> UPDATE
                        </button>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light bg-opacity-50">
                                    <tr class="small text-muted fw-bold text-uppercase">
                                        <th class="ps-4 py-3 border-0">Jenjang</th>
                                        <th class="py-3 border-0">Instansi / Sekolah</th>
                                        <th class="py-3 border-0">Jurusan</th>
                                        <th class="text-center py-3 border-0">Lulus</th>
                                        @if($isOwner) <th class="pe-4 text-center py-3 border-0">Aksi</th> @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($riwayatPend as $index => $d)
                                    <tr>
                                        <td class="ps-4 py-3"><span class="badge bg-pmu-soft px-3">{{ $d['jenjang'] ?? '-' }}</span></td>
                                        <td class="py-3 fw-bold text-dark small">{{ $d['sekolah'] ?? '--' }}</td>
                                        <td class="py-3 text-muted small">{{ $d['jurusan'] ?? '--' }}</td>
                                        <td class="text-center py-3 fw-bold small">{{ $d['tgl_lulus'] ?? '--' }}</td>
                                        @if($isOwner)
                                        <td class="pe-4 text-center py-3">
                                            <div class="d-flex justify-content-center gap-1">
                                                @php $ijz = $dokumens->where('tipe', 'Ijazah '.$d['jenjang'])->first(); @endphp
                                                @if($ijz)
                                                <a href="{{ route('kepegawaian.dokumen.view', ['path' => $ijz->file_path]) }}" target="_blank" class="btn btn-light btn-sm rounded-circle shadow-none" title="Lihat Ijazah">
                                                    <i class="fa-solid fa-eye text-success"></i>
                                                </a>
                                                @endif
                                                <button class="btn btn-light btn-sm rounded-circle shadow-none" onclick="setDocType('Ijazah {{ $d['jenjang'] }}')" data-bs-toggle="modal" data-bs-target="#uploadDocModal" title="{{ $ijz ? 'Ganti Ijazah' : 'Unggah Ijazah' }}">
                                                    <i class="fa-solid fa-file-arrow-up text-{{ $ijz ? 'success' : 'primary' }}"></i>
                                                </button>
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-muted opacity-50">
                                                <i class="fa-solid fa-graduation-cap fa-3x mb-3"></i>
                                                <p class="small mb-0">Belum ada riwayat pendidikan tercatat.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-0 py-4 px-4">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <span class="bg-danger text-white rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-heart" style="font-size: 0.8rem;"></i>
                            </span>
                            STATUS PERNIKAHAN
                        </h6>
                    </div>
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                        @php
                            $maritalStatus = $biodata->status_pernikahan ?? 'Belum Diatur';
                            $maritalColor = match($maritalStatus) {
                                'Menikah' => 'success',
                                'Belum Menikah' => 'secondary',
                                default => 'danger'
                            };
                        @endphp
                        <div class="badge bg-{{ $maritalColor }} {{ $maritalColor == 'success' ? '' : 'bg-opacity-10' }} text-{{ $maritalColor == 'success' ? 'white' : $maritalColor }} rounded-pill px-4 py-2 mb-3 align-self-center fw-bold">
                            {{ strtoupper($maritalStatus) }}
                        </div>
                        <p class="text-muted small mb-4">Tanggal Nikah: <span class="fw-bold text-dark">{{ $biodata->tgl_menikah ? $biodata->tgl_menikah->format('d M Y') : '-' }}</span></p>
                        <div class="p-3 bg-light rounded-4 border border-white">
                            <div class="display-6 fw-bold text-pmu mb-0">{{ $biodata->jumlah_anak ?? 0 }}</div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 1px;">JUMLAH ANAK</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <span class="bg-warning text-white rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-users" style="font-size: 0.8rem;"></i>
                            </span>
                            DATA KELUARGA (ANAK)
                        </h6>
                        @if($isOwner)
                        <button class="btn btn-sm btn-link text-decoration-none text-pmu fw-bold" data-bs-toggle="modal" data-bs-target="#editAnakModal">
                            <i class="fa-solid fa-plus-circle me-1"></i> UPDATE
                        </button>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <tbody>
                                    @forelse($children as $child)
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark small">{{ $child['nama'] }}</div>
                                            <div class="text-muted" style="font-size: 0.65rem;">{{ $child['ttl'] ?? '-' }}</div>
                                        </td>
                                        <td class="pe-4 text-end py-3">
                                            <span class="badge bg-light text-pmu border border-pmu border-opacity-10 small">{{ $child['gender'] == 'Laki-laki' ? 'L' : 'P' }}</span>
                                            <span class="badge bg-pmu-subtle text-pmu small fw-normal">{{ $child['status'] }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center py-5">
                                            <i class="fa-solid fa-user-group opacity-10 fa-3x mb-3"></i>
                                            <p class="text-muted small mb-0 px-4">Belum ada data keluarga tercatat.</p>
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

    <!-- Active Status Card Section -->
    <div class="col-12 mt-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center" style="font-size: 1.1rem; letter-spacing: 0.5px;">
                        <span class="bg-success text-white rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #1a4d2e !important; border-radius: 8px !important;">
                            <i class="fa-solid fa-briefcase" style="font-size: 1.1rem;"></i>
                        </span>
                        STATUS PEGAWAI
                    </h5>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center justify-content-center gap-2" style="height: 42px; border-color: #3b71ca; color: #3b71ca; font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#viewRiwayatModal">
                            <i class="fa-solid fa-clock-rotate-left"></i> RIWAYAT PEGAWAI
                        </button>
                        @if(Auth::user()->isYayasan())
                        <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm border-0 d-inline-flex align-items-center justify-content-center gap-2" style="height: 42px; background-color: #1a4d2e; font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#addRiwayatModal">
                            <i class="fa-solid fa-plus"></i> TAMBAH RIWAYAT
                        </button>
                        @endif
                    </div>
                </div>

                @php $activeRiwayats = $riwayats->where('status', 'Aktif'); @endphp
                @if($activeRiwayats->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="bg-light bg-opacity-50">
                                <tr class="small text-muted fw-bold text-nowrap">
                                    <th class="ps-4 py-3 border-0">Tahun Ajaran</th>
                                    <th class="py-3 border-0">Unit Kerja</th>
                                    <th class="py-3 border-0">Jenis Pegawai</th>
                                    <th class="py-3 border-0">Jabatan</th>
                                    <th class="py-3 border-0">Mata Pelajaran</th>
                                    <th class="py-3 border-0">Status Kepegawaian</th>
                                    <th class="py-3 border-0">Golongan</th>
                                    <th class="py-3 border-0">Satker</th>
                                    <th class="py-3 border-0 text-center">Tahun Mulai Tugas</th>
                                    <th class="py-3 border-0 text-center">Tgl Selesai SK</th>
                                    <th class="py-3 border-0 text-center">Tgl SK</th>
                                    <th class="py-3 border-0 text-center">Status</th>
                                    <th class="py-3 border-0 text-center">SK</th>
                                    @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                                        <th class="pe-4 py-3 border-0 text-center">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeRiwayats as $activeRiwayat)
                                <tr class="text-nowrap fw-bold text-dark">
                                    <td class="ps-4 py-3">{{ $activeRiwayat->thn_ajaran }}</td>
                                    <td class="py-3 text-uppercase">{{ $activeRiwayat->unit }}</td>
                                    <td class="py-3">{{ $activeRiwayat->jenis_pegawai }}</td>
                                    <td class="py-3">{{ $activeRiwayat->jabatan }}</td>
                                    <td class="py-3">{{ $activeRiwayat->mapel }}</td>
                                    <td class="py-3 text-success">{{ $activeRiwayat->status_pegawai }}</td>
                                    <td class="py-3">{{ $activeRiwayat->golongan }}</td>
                                    <td class="py-3">{{ $activeRiwayat->satmingkal }}</td>
                                    <td class="py-3 text-center">{{ $activeRiwayat->thn_mulai }}</td>
                                    <td class="py-3 text-center text-danger">{{ $activeRiwayat->tgl_selesai ? $activeRiwayat->tgl_selesai->format('d/m/Y') : '' }}</td>
                                    <td class="py-3 text-center">{{ $activeRiwayat->tgl_sk ? $activeRiwayat->tgl_sk->format('d/m/Y') : '' }}</td>
                                    <td class="py-3 text-center text-dark">Aktif</td>
                                    <td class="py-3 text-center">
                                        @if($activeRiwayat->file_sk)
                                            <a href="{{ route('kepegawaian.dokumen.view', ['path' => $activeRiwayat->file_sk]) }}" target="_blank" class="btn btn-sm btn-primary rounded-3 p-0 d-inline-flex align-items-center overflow-hidden border-0 shadow-sm transition-hover" style="height: 32px; font-size: 0.75rem; background-color: #3b71ca;">
                                                <div class="d-flex align-items-center justify-content-center px-2 h-100" style="background-color: #2b5bb3;">
                                                    <i class="fa-solid fa-print text-white"></i>
                                                </div>
                                                <div class="px-2 fw-bold text-white text-uppercase">SK</div>
                                            </a>
                                        @endif
                                    </td>
                                    @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                                        <td class="pe-4 py-3 text-center">
                                            <div class="d-flex justify-content-center align-items-center gap-1">
                                                @if(Auth::user()->isYayasan())
                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" onclick='editRiwayat({{ json_encode($activeRiwayat) }})' title="Edit SK/Riwayat Aktif">
                                                    <i class="fa-solid fa-pen-to-square" style="font-size: 0.8rem;"></i>
                                                </button>
                                                @endif
                                                <form action="{{ route('kepegawaian.riwayat.destroy', $activeRiwayat->id) }}" method="POST" onsubmit="return confirm('Hapus status aktif ini?')" class="m-0">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;">
                                                        <i class="fa-solid fa-trash-can" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-5 text-center bg-light rounded-4">
                        <div class="text-muted opacity-75 py-4">
                            <i class="fa-solid fa-briefcase fa-4x mb-3 text-secondary"></i>
                            <h5 class="fw-bold text-dark mb-1">Belum Ada Status Aktif</h5>
                            <p class="small mb-0 text-muted">Saat ini belum ada data status kepegawaian aktif. Silakan hubungi admin atau Yayasan.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Riwayat Pegawai (Selesai/Expired) -->
<div class="modal fade" id="viewRiwayatModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95% !important;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header bg-pmu text-white p-4 border-0">
                <div>
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i> RIWAYAT STATUS KEPEGAWAIAN</h5>
                    <p class="mb-0 small opacity-75">Daftar riwayat kontrak, jabatan, dan status kepegawaian masa lalu.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.75rem;">
                        <thead class="bg-light">
                            <tr class="small fw-bold text-muted text-uppercase">
                                <th class="ps-4 py-3" style="min-width: 100px;">Tahun Ajaran</th>
                                <th class="py-3" style="min-width: 120px;">Unit</th>
                                <th class="py-3" style="min-width: 100px;">Jenis Pegawai</th>
                                <th class="py-3" style="min-width: 120px;">Jabatan</th>
                                <th class="py-3" style="min-width: 120px;">Mata Pelajaran</th>
                                <th class="py-3 text-center" style="min-width: 130px;">Status Pegawai</th>
                                <th class="py-3 text-center">Golongan</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="pe-4 py-3 text-end" style="min-width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayats as $riwayat)
                            <tr class="{{ $riwayat->status == 'Aktif' ? 'bg-success bg-opacity-5 fw-semibold' : '' }}">
                                <td class="ps-4 py-3">{{ $riwayat->thn_ajaran }}</td>
                                <td class="py-3">
                                    <div class="fw-bold">{{ $riwayat->unit }}</div>
                                </td>
                                <td class="py-3 text-muted">{{ $riwayat->jenis_pegawai ?: '-' }}</td>
                                <td class="py-3 text-muted">{{ $riwayat->jabatan ?: '-' }}</td>
                                <td class="py-3 text-muted">{{ $riwayat->mapel ?: '-' }}</td>
                                <td class="py-3 text-center text-pmu">{{ $riwayat->status_pegawai ?: '-' }}</td>
                                <td class="py-3 text-center">{{ $riwayat->golongan ?: '-' }}</td>
                                <td class="py-3 text-center">
                                    <span class="badge {{ $riwayat->status == 'Aktif' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }} rounded-pill px-2" style="font-size: 0.65rem; font-weight: bold;">
                                        {{ strtoupper($riwayat->status) }}
                                    </span>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        @if($riwayat->file_sk)
                                        <a href="{{ route('kepegawaian.dokumen.view', ['path' => $riwayat->file_sk]) }}" target="_blank" class="btn btn-sm btn-primary rounded-3 p-0 d-inline-flex align-items-center overflow-hidden border-0 shadow-sm transition-hover" style="height: 30px; font-size: 0.7rem; background-color: #3b71ca;">
                                            <div class="d-flex align-items-center justify-content-center px-2 h-100" style="background-color: #2b5bb3;">
                                                <i class="fa-solid fa-print text-white"></i>
                                            </div>
                                            <div class="px-2 fw-bold text-white text-uppercase">SK</div>
                                        </a>
                                        @endif

                                        @if(Auth::user()->isYayasan())
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 30px; height: 30px;" onclick='editRiwayat({{ json_encode($riwayat) }})' title="Edit Riwayat">
                                            <i class="fa-solid fa-pen-to-square" style="font-size: 0.8rem;"></i>
                                        </button>
                                        @endif

                                        @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                                        <form action="{{ route('kepegawaian.riwayat.destroy', $riwayat->id) }}" method="POST" onsubmit="return confirm('Hapus riwayat ini?')" class="m-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 30px; height: 30px;">
                                                <i class="fa-solid fa-trash-can" style="font-size: 0.8rem;"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fa-solid fa-folder-open text-muted opacity-25 fa-2x mb-3"></i>
                                    <p class="text-muted small fw-bold mb-0">Riwayat belum tersedia.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer p-4 border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">TUTUP</button>
            </div>
        </div>
    </div>
</div>
</div>

@if($isOwner || Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
    @include('kepegawaian.partials_biodata_modals')
@endif

<script>
    function setDocType(type) {
        if(document.getElementById('modal_doc_type')) {
            document.getElementById('modal_doc_type').value = type;
            document.getElementById('modal_doc_label').innerText = type;
        }
        
        const ijazahBox = document.getElementById('ijazah-instruction-box');
        if (ijazahBox) {
            if (type.toLowerCase().includes('ijazah')) {
                ijazahBox.classList.remove('d-none');
            } else {
                ijazahBox.classList.add('d-none');
            }
        }
    }

    function editRiwayat(riwayat) {
        const form = document.getElementById('editRiwayatForm');
        form.action = `/kepegawaian/riwayat/update/${riwayat.id}`;
        
        document.getElementById('edit_riwayat_thn_ajaran').value = riwayat.thn_ajaran || '';
        document.getElementById('edit_riwayat_unit').value = riwayat.unit || '';
        document.getElementById('edit_riwayat_jenis_pegawai').value = riwayat.jenis_pegawai || 'Guru';
        document.getElementById('edit_riwayat_jabatan').value = riwayat.jabatan || '';
        document.getElementById('edit_riwayat_mapel').value = riwayat.mapel || '';
        document.getElementById('edit_riwayat_status_pegawai').value = riwayat.status_pegawai || '';
        document.getElementById('edit_riwayat_golongan').value = riwayat.golongan || '';
        document.getElementById('edit_riwayat_satmingkal').value = riwayat.satmingkal || 'Satmingkal';
        document.getElementById('edit_riwayat_thn_mulai').value = riwayat.thn_mulai || '';
        document.getElementById('edit_riwayat_status').value = riwayat.status || 'Aktif';

        if (riwayat.tgl_selesai) {
            const dateSelesai = new Date(riwayat.tgl_selesai);
            const yyyy = dateSelesai.getFullYear();
            const mm = String(dateSelesai.getMonth() + 1).padStart(2, '0');
            const dd = String(dateSelesai.getDate()).padStart(2, '0');
            document.getElementById('edit_riwayat_tgl_selesai').value = `${yyyy}-${mm}-${dd}`;
        } else {
            document.getElementById('edit_riwayat_tgl_selesai').value = '';
        }

        if (riwayat.tgl_sk) {
            const dateSk = new Date(riwayat.tgl_sk);
            const yyyy = dateSk.getFullYear();
            const mm = String(dateSk.getMonth() + 1).padStart(2, '0');
            const dd = String(dateSk.getDate()).padStart(2, '0');
            document.getElementById('edit_riwayat_tgl_sk').value = `${yyyy}-${mm}-${dd}`;
        } else {
            document.getElementById('edit_riwayat_tgl_sk').value = '';
        }

        const hintEl = document.getElementById('edit_riwayat_file_hint');
        if (riwayat.file_sk) {
            const filename = riwayat.file_sk.split('/').pop();
            hintEl.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check"></i> Sudah memiliki SK: ${filename}</span>`;
        } else {
            hintEl.innerHTML = '<span class="text-muted">Belum ada file SK terunggah</span>';
        }

        const viewModalEl = document.getElementById('viewRiwayatModal');
        if (viewModalEl) {
            const viewModal = bootstrap.Modal.getInstance(viewModalEl);
            if (viewModal) {
                viewModal.hide();
            }
        }

        const editModalEl = document.getElementById('editRiwayatModal');
        const editModal = new bootstrap.Modal(editModalEl);
        editModal.show();
    }

    @if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        @if($errors->hasAny(['thn_ajaran', 'unit', 'jenis_pegawai', 'jabatan', 'mapel', 'status_pegawai', 'golongan', 'satmingkal', 'thn_mulai', 'tgl_selesai', 'tgl_sk', 'file_sk', 'status']))
            var modalEl = document.getElementById('addRiwayatModal');
        @elseif($errors->hasAny(['pend_jenjang', 'pend_sekolah', 'pend_jurusan', 'pend_tgl_lulus']))
            var modalEl = document.getElementById('editPendidikanModal');
        @elseif($errors->hasAny(['anak_nama', 'anak_ttl', 'anak_gender', 'anak_status']))
            var modalEl = document.getElementById('editAnakModal');
        @else
            var modalEl = document.getElementById('editModal');
        @endif
        
        if (modalEl) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });
    @endif
</script>

<style>
    .info-group .fw-bold { font-size: 0.95rem; }
    .shadow-inner { box-shadow: inset 0 2px 10px rgba(0,0,0,0.1); }
    .transition-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .transition-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(26, 77, 46, 0.2) !important; }
    .btn-pmu { background: var(--pmu-gradient); }
    .btn-outline-pmu { border-color: var(--pmu-green); color: var(--pmu-green); }
    .btn-outline-pmu:hover { background: var(--pmu-gradient); color: white; }
    .max-width-300 { max-width: 400px; }
    .list-group-item { background: transparent; }
    .photo-edit-btn:hover { background-color: var(--pmu-green) !important; transform: scale(1.1); transition: all 0.2s; }
</style>
@endsection

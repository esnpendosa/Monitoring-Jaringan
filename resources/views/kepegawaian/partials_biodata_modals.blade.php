<!-- Bio Edit Modal -->
<div class="modal fade animate__animated animate__fadeIn" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('kepegawaian.biodata.store') }}" method="POST" class="w-100">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <div>
                        <h5 class="modal-title fw-bold">EDIT IDENTITAS UTAMA</h5>
                        <p class="mb-0 small opacity-75">Pastikan data yang diinput sudah sesuai dengan KTP/KK.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    @if($errors->hasAny(['username', 'email', 'password', 'pin_fingerspot', 'kode_pegawai', 'nik', 'tempat_lahir', 'tgl_lahir', 'jenis_kelamin', 'gol_darah', 'agama', 'tgl_masuk', 'status_pegawai', 'alamat', 'no_wa', 'rekening', 'status_pernikahan', 'tgl_menikah', 'jumlah_anak']))
                        <div class="alert alert-danger border-0 rounded-4 small mb-4">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            <strong>Gagal menyimpan identitas:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">USERNAME LOGIN</label>
                            <input type="text" name="username" class="form-control fw-bold text-pmu bg-light" value="{{ $user->username }}" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">EMAIL ADDRESS</label>
                            <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                        </div>
                        @if(Auth::user()->isYayasan() || Auth::user()->isAdminUnit())
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">GANTI PASSWORD</label>
                            <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ganti">
                        </div>
                        @endif
                    </div>

                    <h6 class="text-uppercase fw-bold text-pmu small mb-4 d-flex align-items-center">
                        <span class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">2</span> 
                        Data Personal & Kepegawaian
                    </h6>
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">ID/PIN FINGERPRINT X100-C</label>
                            <input type="text" name="pin_fingerspot" class="form-control {{ Auth::user()->isYayasan() ? '' : 'bg-light' }} fw-bold text-dark" value="{{ $user->pin_fingerspot ?? '' }}" {{ Auth::user()->isYayasan() ? '' : 'readonly' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">KODE PEGAWAI</label>
                            <input type="text" name="kode_pegawai" class="form-control {{ Auth::user()->isYayasan() ? '' : 'bg-light' }} fw-bold" value="{{ $biodata->kode_pegawai }}" {{ Auth::user()->isYayasan() ? '' : 'readonly' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">NIK (KTP)</label>
                            <input type="text" name="nik" class="form-control" value="{{ $biodata->nik }}" placeholder="16 Digit NIK">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">TEMPAT LAHIR</label>
                            <input type="text" name="tempat_lahir" class="form-control" value="{{ $biodata->tempat_lahir }}" placeholder="Kota Lahir">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">TANGGAL LAHIR</label>
                            <input type="date" name="tgl_lahir" class="form-control" value="{{ $biodata->tgl_lahir ? $biodata->tgl_lahir->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">JENIS KELAMIN</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="Laki-laki" {{ $biodata->jenis_kelamin == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="Perempuan" {{ $biodata->jenis_kelamin == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">GOLONGAN DARAH</label>
                            <input type="text" name="gol_darah" class="form-control" value="{{ $biodata->gol_darah }}" placeholder="A / B / AB / O">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">AGAMA</label>
                            <select name="agama" class="form-select">
                                <option value="Islam" {{ $biodata->agama == 'Islam' ? 'selected' : '' }}>Islam</option>
                                <option value="Kristen" {{ $biodata->agama == 'Kristen' ? 'selected' : '' }}>Kristen</option>
                                <option value="Katolik" {{ $biodata->agama == 'Katolik' ? 'selected' : '' }}>Katolik</option>
                                <option value="Hindu" {{ $biodata->agama == 'Hindu' ? 'selected' : '' }}>Hindu</option>
                                <option value="Budha" {{ $biodata->agama == 'Budha' ? 'selected' : '' }}>Budha</option>
                                <option value="Konghucu" {{ $biodata->agama == 'Konghucu' ? 'selected' : '' }}>Konghucu</option>
                            </select>
                        </div>
                        
                        @if(Auth::user()->isYayasan())
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">TANGGAL MASUK PMU</label>
                            <input type="date" name="tgl_masuk" class="form-control fw-bold text-success border-success bg-success bg-opacity-10" value="{{ $biodata->tgl_masuk ? $biodata->tgl_masuk->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-danger">STATUS KEAKTIFAN</label>
                            <select name="status_pegawai" class="form-select border-danger bg-danger bg-opacity-10 fw-bold">
                                <option value="Aktif" {{ ($biodata->status_pegawai ?? 'Aktif') == 'Aktif' ? 'selected' : '' }}>AKTIF</option>
                                <option value="Tidak Aktif" {{ ($biodata->status_pegawai ?? 'Aktif') == 'Tidak Aktif' ? 'selected' : '' }}>TIDAK AKTIF</option>
                            </select>
                        </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">ALAMAT DOMISILI LENGKAP</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Jalan, RT/RW, Desa, Kecamatan, Kabupaten">{{ $biodata->alamat }}</textarea>
                        </div>
                    </div>

                    <h6 class="text-uppercase fw-bold text-pmu small mb-4 d-flex align-items-center">
                        <span class="bg-pmu text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">2</span> 
                        Sosial & Perbankan
                    </h6>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">NOMOR WHATSAPP</label>
                            <input type="text" name="no_wa" class="form-control" value="{{ $biodata->no_wa }}" placeholder="08XXXXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">NOMOR REKENING GAJI</label>
                            <input type="text" name="rekening" class="form-control" value="{{ $biodata->rekening }}" placeholder="Bank - Nama - No. Rek">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">STATUS PERNIKAHAN</label>
                            <select name="status_pernikahan" class="form-select">
                                <option value="Belum Menikah" {{ $biodata->status_pernikahan == 'Belum Menikah' ? 'selected' : '' }}>Belum Menikah</option>
                                <option value="Menikah" {{ $biodata->status_pernikahan == 'Menikah' ? 'selected' : '' }}>Menikah</option>
                                <option value="Cerai" {{ $biodata->status_pernikahan == 'Cerai' ? 'selected' : '' }}>Cerai</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">TANGGAL PERNIKAHAN</label>
                            <input type="date" name="tgl_menikah" class="form-control" value="{{ $biodata->tgl_menikah ? $biodata->tgl_menikah->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">JUMLAH ANAK</label>
                            <input type="number" name="jumlah_anak" class="form-control" value="{{ $biodata->jumlah_anak ?? 0 }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-4 p-lg-5 pt-0 border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow">SIMPAN PERUBAHAN</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pendidikan (Dynamic) -->
<div class="modal fade" id="editPendidikanModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form action="{{ route('kepegawaian.biodata.store') }}" method="POST" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <div>
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-graduation-cap me-2"></i> UPDATE RIWAYAT PENDIDIKAN</h5>
                        <p class="mb-0 small opacity-75 fst-italic">Tambahkan jenjang pendidikan sesuai ijazah yang Anda miliki.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    @if($errors->hasAny(['pend_jenjang', 'pend_sekolah', 'pend_jurusan', 'pend_tgl_lulus']))
                        <div class="alert alert-danger border-0 rounded-4 small mb-4">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            <strong>Gagal menyimpan pendidikan:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="table-responsive rounded-4 border overflow-hidden mb-4">
                        <table class="table table-borderless align-middle mb-0" id="table-pendidikan">
                            <thead class="bg-light">
                                <tr class="text-muted small fw-bold">
                                    <th class="ps-4 py-3" style="width: 180px;">JENJANG</th>
                                    <th class="py-3">SEKOLAH / PERGURUAN TINGGI</th>
                                    <th class="py-3">JURUSAN</th>
                                    <th class="py-3" style="width: 120px;">THN LULUS</th>
                                    <th class="text-center py-3" style="width: 50px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="container-pendidikan">
                                @forelse($riwayatPend as $index => $d)
                                <tr class="row-pendidikan">
                                    <td class="ps-4">
                                        <select name="pend_jenjang[]" class="form-select form-select-sm fw-bold border-pmu border-opacity-25">
                                            @foreach(['SD/MI', 'SMP/MTs', 'SMA/MA', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'] as $opt)
                                                <option value="{{ $opt }}" {{ $d['jenjang'] == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="pend_sekolah[]" class="form-control form-control-sm" value="{{ $d['sekolah'] ?? '' }}" placeholder="Nama Institusi" required></td>
                                    <td><input type="text" name="pend_jurusan[]" class="form-control form-control-sm" value="{{ $d['jurusan'] ?? '' }}" placeholder="Ex: IPA / Teknik"></td>
                                    <td><input type="text" name="pend_tgl_lulus[]" class="form-control form-control-sm text-center" value="{{ $d['tgl_lulus'] ?? '' }}" placeholder="YYYY"></td>
                                    <td class="pe-4 text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle border-0 shadow-none remove-pend-row">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr class="row-pendidikan">
                                    <td class="ps-4">
                                        <select name="pend_jenjang[]" class="form-select form-select-sm fw-bold border-pmu border-opacity-25">
                                            @foreach(['SD/MI', 'SMP/MTs', 'SMA/MA', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'] as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="pend_sekolah[]" class="form-control form-control-sm" placeholder="Nama Institusi" required></td>
                                    <td><input type="text" name="pend_jurusan[]" class="form-control form-control-sm" placeholder="Ex: IPA / Teknik"></td>
                                    <td><input type="text" name="pend_tgl_lulus[]" class="form-control form-control-sm text-center" placeholder="YYYY"></td>
                                    <td class="pe-4 text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle border-0 shadow-none remove-pend-row">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold shadow-none" id="add-pend-row">
                        <i class="fa-solid fa-plus-circle me-1"></i> TAMBAH JENJANG PENDIDIKAN
                    </button>
                </div>
                <div class="modal-footer p-4 p-lg-5 pt-0 border-0 mt-3">
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow py-3 w-100">SIMPAN SEMUA RIWAYAT PENDIDIKAN</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('container-pendidikan');
    const addButton = document.getElementById('add-pend-row');

    if (addButton) {
        addButton.addEventListener('click', function() {
            const html = `
                <tr class="row-pendidikan animate__animated animate__fadeInDown">
                    <td class="ps-4">
                        <select name="pend_jenjang[]" class="form-select form-select-sm fw-bold border-pmu border-opacity-25">
                            <option value="SD/MI">SD/MI</option>
                            <option value="SMP/MTs">SMP/MTs</option>
                            <option value="SMA/MA">SMA/MA</option>
                            <option value="D1">D1</option>
                            <option value="D2">D2</option>
                            <option value="D3">D3</option>
                            <option value="S1" selected>S1</option>
                            <option value="S2">S2</option>
                            <option value="S3">S3</option>
                        </select>
                    </td>
                    <td><input type="text" name="pend_sekolah[]" class="form-control form-control-sm" placeholder="Nama Institusi" required></td>
                    <td><input type="text" name="pend_jurusan[]" class="form-control form-control-sm" placeholder="Ex: IPA / Teknik"></td>
                    <td><input type="text" name="pend_tgl_lulus[]" class="form-control form-control-sm text-center" placeholder="YYYY"></td>
                    <td class="pe-4 text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle border-0 shadow-none remove-pend-row">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `;
            const div = document.createElement('tbody');
            div.innerHTML = html;
            container.appendChild(div.firstElementChild);
        });
    }

    if (container) {
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-pend-row') || e.target.closest('.remove-pend-row')) {
                const row = e.target.closest('tr');
                if (container.querySelectorAll('tr').length > 1) {
                    row.classList.remove('animate__fadeInDown');
                    row.classList.add('animate__fadeOut');
                    setTimeout(() => row.remove(), 500);
                } else {
                    alert('Minimal harus ada 1 riwayat pendidikan.');
                }
            }
        });
    }
});
</script>

<!-- Modal Edit Anak -->
<div class="modal fade" id="editAnakModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('kepegawaian.biodata.store') }}" method="POST" class="w-100">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white p-4 border-0 text-center d-block position-relative">
                    <h5 class="modal-title fw-bold mx-auto">DATA KELUARGA (ANAK)</h5>
                    <p class="mb-0 small opacity-75">Inputkan informasi anak secara berurutan.</p>
                    <button type="button" class="btn-close btn-close-white position-absolute end-0 top-0 m-4" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    @if($errors->hasAny(['anak_nama', 'anak_ttl', 'anak_gender', 'anak_status']))
                        <div class="alert alert-danger border-0 rounded-4 small mb-4">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            <strong>Gagal menyimpan data keluarga:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="container-anak">
                        @php 
                            $cCount = count($children);
                            $loopCount = max(1, $cCount); // show at least 1 child input if empty
                        @endphp
                        @for($i=0; $i<$loopCount; $i++)
                        @php $c = $children[$i] ?? null; @endphp
                        <div class="p-4 rounded-4 bg-light mb-4 border border-white position-relative row-anak animate__animated animate__fadeIn">
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle border-0 shadow-none remove-anak-row position-absolute end-0 top-0 m-3" title="Hapus Anak" style="z-index: 10;">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                            <div class="position-absolute translate-middle-y top-0 start-0 ms-4 bg-white px-2 fw-bold text-pmu small label-anak">ANAK KE-{{ $i+1 }}</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">NAMA LENGKAP</label>
                                    <input type="text" name="anak_nama[]" class="form-control" value="{{ $c['nama'] ?? '' }}" placeholder="Input Nama" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">TEMPAT, TGL LAHIR</label>
                                    <input type="text" name="anak_ttl[]" class="form-control" value="{{ $c['ttl'] ?? '' }}" placeholder="Ex: Gresik, 18 Juli 2020">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">JENIS KELAMIN</label>
                                    <select name="anak_gender[]" class="form-select">
                                        <option value="Laki-laki" {{ ($c['gender'] ?? '') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="Perempuan" {{ ($c['gender'] ?? '') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">STATUS HUBUNGAN</label>
                                    <select name="anak_status[]" class="form-select">
                                        <option value="Kandung" {{ ($c['status'] ?? '') == 'Kandung' ? 'selected' : '' }}>Anak Kandung</option>
                                        <option value="Tiri" {{ ($c['status'] ?? '') == 'Tiri' ? 'selected' : '' }}>Anak Angkat/Tiri</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-4 fw-bold shadow-none" id="add-anak-row">
                        <i class="fa-solid fa-plus-circle me-1"></i> TAMBAH ANAK
                    </button>
                </div>
                <div class="modal-footer p-4 p-lg-5 pt-0 border-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow flex-grow-1">SIMPAN DATA KELUARGA</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const containerAnak = document.getElementById('container-anak');
    const addAnakButton = document.getElementById('add-anak-row');

    function reindexAnakLabels() {
        if (!containerAnak) return;
        const rows = containerAnak.querySelectorAll('.row-anak');
        rows.forEach((row, index) => {
            const label = row.querySelector('.label-anak');
            if (label) {
                label.innerText = `ANAK KE-${index + 1}`;
            }
        });
    }

    if (addAnakButton) {
        addAnakButton.addEventListener('click', function() {
            const index = containerAnak.querySelectorAll('.row-anak').length;
            const html = `
                <div class="p-4 rounded-4 bg-light mb-4 border border-white position-relative row-anak animate__animated animate__fadeInDown">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle border-0 shadow-none remove-anak-row position-absolute end-0 top-0 m-3" title="Hapus Anak" style="z-index: 10;">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                    <div class="position-absolute translate-middle-y top-0 start-0 ms-4 bg-white px-2 fw-bold text-pmu small label-anak">ANAK KE-${index + 1}</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NAMA LENGKAP</label>
                            <input type="text" name="anak_nama[]" class="form-control" placeholder="Input Nama" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">TEMPAT, TGL LAHIR</label>
                            <input type="text" name="anak_ttl[]" class="form-control" placeholder="Ex: Gresik, 18 Juli 2020">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">JENIS KELAMIN</label>
                            <select name="anak_gender[]" class="form-select">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan" selected>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">STATUS HUBUNGAN</label>
                            <select name="anak_status[]" class="form-select">
                                <option value="Kandung" selected>Anak Kandung</option>
                                <option value="Tiri">Anak Angkat/Tiri</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            const div = document.createElement('div');
            div.innerHTML = html;
            containerAnak.appendChild(div.firstElementChild);
            reindexAnakLabels();
        });
    }

    if (containerAnak) {
        containerAnak.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-anak-row') || e.target.closest('.remove-anak-row')) {
                const row = e.target.closest('.row-anak');
                if (containerAnak.querySelectorAll('.row-anak').length > 1) {
                    row.classList.remove('animate__fadeInDown', 'animate__fadeIn');
                    row.classList.add('animate__fadeOut');
                    setTimeout(() => {
                        row.remove();
                        reindexAnakLabels();
                    }, 500);
                } else {
                    const nameInput = row.querySelector('input[name="anak_nama[]"]');
                    const ttlInput = row.querySelector('input[name="anak_ttl[]"]');
                    if (nameInput) nameInput.value = '';
                    if (ttlInput) ttlInput.value = '';
                }
            }
        });
    }
});
</script>

<!-- Modal Tambah Riwayat Karir -->
<div class="modal fade" id="addRiwayatModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('kepegawaian.riwayat.store') }}" method="POST" enctype="multipart/form-data" class="w-100">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-briefcase me-2"></i> TAMBAH RIWAYAT STATUS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    @if($errors->hasAny(['thn_ajaran', 'unit', 'jenis_pegawai', 'jabatan', 'mapel', 'status_pegawai', 'golongan', 'satmingkal', 'thn_mulai', 'tgl_selesai', 'tgl_sk', 'file_sk', 'status']))
                        <div class="alert alert-danger border-0 rounded-4 small mb-4">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            <strong>Gagal menyimpan riwayat:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="alert alert-primary border-0 rounded-4 small mb-4">
                        <i class="fa-solid fa-circle-info me-2"></i> Tambahkan riwayat kepegawaian untuk melengkapi database portofolio pegawai.
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">TAHUN AJARAN</label>
                            @php
                                $currentYear = date('Y');
                                $currentMonth = date('n');
                                $defaultAjaran = ($currentMonth >= 7) ? $currentYear.'/'.($currentYear+1) : ($currentYear-1).'/'.$currentYear;
                            @endphp
                            <input type="text" name="thn_ajaran" class="form-control" value="{{ $defaultAjaran }}" placeholder="Ex: 2024/2025" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">UNIT KERJA</label>
                            <select name="unit" class="form-select text-uppercase" required>
                                @foreach($global_units as $unit)
                                    <option value="{{ $unit->nama }}" {{ $user->unit == $unit->nama ? 'selected' : '' }}>{{ $unit->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">JENIS PEGAWAI</label>
                            <select name="jenis_pegawai" class="form-select">
                                <option value="Guru">Guru</option>
                                <option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
                                <option value="Umum">Umum / Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">JABATAN</label>
                            <input type="text" name="jabatan" class="form-control" placeholder="Jabatan Utama" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">MATA PELAJARAN (GURU)</label>
                            <input type="text" name="mapel" class="form-control" placeholder="-">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">STATUS KEPEGAWAIAN (SK)</label>
                            <input type="text" name="status_pegawai" class="form-control" placeholder="Contoh: Pegawai Tetap / GTY / GTT">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">GOLONGAN</label>
                            <input type="text" name="golongan" class="form-control" placeholder="Ex: II/A">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">JENIS SATKER</label>
                            <select name="satmingkal" class="form-select">
                                <option value="Satmingkal">Satmingkal (Pusat)</option>
                                <option value="Non Satmingkal">Non Satmingkal (Cabang)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">TAHUN MULAI TUGAS</label>
                            <input type="number" name="thn_mulai" class="form-control" value="{{ $biodata->tgl_masuk ? $biodata->tgl_masuk->year : date('Y') }}" placeholder="YYYY">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">TGL SELESAI KONTRAK</label>
                            <input type="date" name="tgl_selesai" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">TGL SK</label>
                            <input type="date" name="tgl_sk" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">UNGGAH FILE SK (PDF/JPG)</label>
                            <input type="file" name="file_sk" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">STATUS PERIODE</label>
                            <select name="status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-4 p-lg-5 pt-0 border-0">
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow">SIMPAN RIWAYAT</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Riwayat Karir -->
<div class="modal fade" id="editRiwayatModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="editRiwayatForm" method="POST" enctype="multipart/form-data" class="w-100">
            @csrf
            @method('PUT')
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-briefcase me-2"></i> EDIT RIWAYAT STATUS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    <div class="alert alert-warning border-0 rounded-4 small mb-4">
                        <i class="fa-solid fa-circle-info me-2"></i> Perbarui data riwayat kepegawaian untuk menjaga konsistensi database portofolio pegawai.
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">TAHUN AJARAN</label>
                            <input type="text" name="thn_ajaran" id="edit_riwayat_thn_ajaran" class="form-control" placeholder="Ex: 2024/2025" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">UNIT KERJA</label>
                            <select name="unit" id="edit_riwayat_unit" class="form-select text-uppercase" required>
                                @foreach($global_units as $unit)
                                    <option value="{{ $unit->nama }}">{{ $unit->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">JENIS PEGAWAI</label>
                            <select name="jenis_pegawai" id="edit_riwayat_jenis_pegawai" class="form-select">
                                <option value="Guru">Guru</option>
                                <option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
                                <option value="Umum">Umum / Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">JABATAN</label>
                            <input type="text" name="jabatan" id="edit_riwayat_jabatan" class="form-control" placeholder="Jabatan Utama" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">MATA PELAJARAN (GURU)</label>
                            <input type="text" name="mapel" id="edit_riwayat_mapel" class="form-control" placeholder="-">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">STATUS KEPEGAWAIAN (SK)</label>
                            <input type="text" name="status_pegawai" id="edit_riwayat_status_pegawai" class="form-control" placeholder="Contoh: Pegawai Tetap / GTY / GTT">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">GOLONGAN</label>
                            <input type="text" name="golongan" id="edit_riwayat_golongan" class="form-control" placeholder="Ex: II/A">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">JENIS SATKER</label>
                            <select name="satmingkal" id="edit_riwayat_satmingkal" class="form-select">
                                <option value="Satmingkal">Satmingkal (Pusat)</option>
                                <option value="Non Satmingkal">Non Satmingkal (Cabang)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">TAHUN MULAI TUGAS</label>
                            <input type="number" name="thn_mulai" id="edit_riwayat_thn_mulai" class="form-control" placeholder="YYYY">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">TGL SELESAI KONTRAK</label>
                            <input type="date" name="tgl_selesai" id="edit_riwayat_tgl_selesai" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">TGL SK</label>
                            <input type="date" name="tgl_sk" id="edit_riwayat_tgl_sk" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">UNGGAH FILE SK BARU (BISA DIKOSONGKAN)</label>
                            <input type="file" name="file_sk" class="form-control">
                            <small class="text-muted mt-1 d-block" id="edit_riwayat_file_hint"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">STATUS PERIODE</label>
                            <select name="status" id="edit_riwayat_status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-4 p-lg-5 pt-0 border-0">
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-5 fw-bold shadow">PERBARUI RIWAYAT</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('kepegawaian.dokumen.store') }}" method="POST" enctype="multipart/form-data" class="w-100">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-pmu text-white p-4 border-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-cloud-arrow-up me-2"></i> UNGGAH BERKAS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-5">
                    <input type="hidden" name="tipe" id="modal_doc_type">
                    <div id="ijazah-instruction-box" class="alert alert-warning border-0 rounded-4 small mb-4 d-none text-start" style="background-color: #fff9e6; color: #b27b00;">
                        <i class="fa-solid fa-circle-info me-2" style="color: #d99100;"></i>
                        <strong>Petunjuk Unggah Ijazah:</strong>
                        <ul class="mb-0 mt-1 ps-3 text-dark opacity-75">
                            <li>Unggah berkas scan <strong>Ijazah Asli</strong> (berwarna) atau fotokopi yang telah <strong>dilegalisir</strong>.</li>
                            <li>Pastikan dokumen tegak (tidak miring/terbalik) lan semua teks/nilai/stempel <strong>terbaca dengan jelas</strong>.</li>
                        </ul>
                    </div>
                    <div class="mb-4 text-center">
                        <i class="fa-solid fa-file-pdf fa-4x text-pmu opacity-25 mb-3"></i>
                        <h6 class="fw-bold mb-1" id="modal_doc_label">Digitalisasi Berkas</h6>
                        <p class="text-muted small">Pastikan format file JPG, PNG, atau PDF (Max 2MB)</p>
                    </div>
                    <div class="p-4 rounded-4 border-dashed bg-light text-center">
                        <input type="file" name="file" class="form-control form-control-sm border-0 bg-transparent" required>
                    </div>
                </div>
                <div class="modal-footer p-5 pt-0 border-0">
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold w-100 shadow py-3">MULAI UNGGAH</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Modal Atur Jadwal Kerja -->
<div class="modal fade" id="editScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('kepegawaian.biodata.schedule') }}" method="POST" class="w-100">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header bg-primary text-white p-4 border-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-clock me-2"></i> PENGATURAN JADWAL KERJA</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    <div class="alert alert-info border-0 rounded-4 small mb-4">
                        <i class="fa-solid fa-circle-info me-2"></i> Atur jumlah menit kewajiban Anda setiap harinya. Masukkan <b>0</b> jika hari tersebut adalah hari libur Anda.
                    </div>
                    
                    <div class="row g-3">
                        @php
                            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                            $userSchedules = $user->schedules->keyBy('day_index');
                        @endphp
                        @foreach($days as $index => $day)
                        @php $sched = $userSchedules->get($index); @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 border rounded-4 bg-light">
                                <label class="form-label small fw-bold text-muted text-uppercase">{{ $day }}</label>
                                <div class="input-group">
                                    <input type="number" name="schedule[{{ $index }}]" 
                                           class="form-control rounded-3 border-0 shadow-sm" 
                                           placeholder="0" 
                                           value="{{ $sched ? $sched->minutes : 0 }}" required>
                                    <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">MENIT</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer p-4 p-lg-5 pt-0 border-0">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow w-100 py-3">SIMPAN JADWAL KERJA</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .border-dashed { border: 2px dashed #cbd5e1 !important; }
    .form-control, .form-select { background-color: #f8fafc; }
    .transition-hover:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
</style>

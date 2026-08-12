@extends('layouts/contentNavbarLayout')

@section('title', 'WhatsApp Manager - Multi Device')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">WhatsApp /</span> Multi Device Manager</h4>

<div class="row">
    @if($sessions === null)
    <div class="col-12">
        <div class="alert alert-danger d-flex align-items-center justify-content-between" role="alert">
            <div>
                <i class="bx bx-error-circle me-2"></i>
                Server Bot sedang <strong>OFFLINE</strong>. Silakan jalankan server terlebih dahulu agar fitur WA berfungsi.
            </div>
            <button class="btn btn-danger btn-sm" id="btnStartBot">
                <i class="bx bx-play me-1"></i> Jalankan Server Bot
            </button>
        </div>
    </div>
    @endif

    <!-- Session List -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Perangkat Terhubung</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-info btn-sm" onclick="location.reload()">
                        <i class="bx bx-sync me-1"></i> Sinkronkan Sesi
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                        <i class="bx bx-plus me-1"></i> Tambah Perangkat
                    </button>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Nomor WA</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($sessions ?? [] as $session)
                        <tr>
                            <td><strong>{{ $session['id'] }}</strong></td>
                            <td>{{ $session['user']['id'] ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $session['status'] === 'open' ? 'bg-label-success' : 'bg-label-warning' }}">
                                    {{ strtoupper($session['status']) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($session['status'] !== 'open' && isset($session['qr']))
                                    <button class="btn btn-sm btn-info" onclick="showQrModal('{{ $session['qr'] }}')" title="Scan QR">
                                        <i class="bx bx-qr-scan"></i>
                                    </button>
                                    @endif
                                    @if($session['status'] === 'open')
                                    <button class="btn btn-sm btn-warning stop-session" data-id="{{ $session['id'] }}" title="Disconnect/Logout">
                                        <i class="bx bx-log-out"></i>
                                    </button>
                                    @endif
                                    <button class="btn btn-sm btn-danger delete-session" data-id="{{ $session['id'] }}" title="Hapus Permanen">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">Belum ada perangkat terhubung. <br> <button class="btn btn-sm btn-outline-primary mt-2" onclick="location.reload()">Refresh Halaman</button></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">💡 Petunjuk</h5>
                <p class="card-text small">
                    Fitur ini memungkinkan Anda menghubungkan lebih dari satu nomor WhatsApp ke sistem secara dinamis.
                </p>
                <ul class="small ps-3">
                    <li>Gunakan <strong>Session ID</strong> yang unik (misal: "cs1", "admin").</li>
                    <li>Anda bisa menghubungkan lewat <strong>Pairing Code</strong> (Tanpa Scan QR).</li>
                    <li>Status <strong>OPEN</strong> berarti bot sudah aktif di nomor tersebut.</li>
                </ul>
                <div class="alert alert-warning py-2 small">
                    <strong>Catatan:</strong> Pesan keluar akan menggunakan session yang tersedia secara bergantian jika tidak ditentukan.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Session -->
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Perangkat Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Session ID (Bebas, Tanpa Spasi)</label>
                    <input type="text" id="newSessionId" class="form-control" placeholder="misal: kantor_pusat">
                </div>
                <div class="mb-3">
                    <label class="form-label">Metode Koneksi</label>
                    <select id="connectMethod" class="form-select">
                        <option value="qr">Scan QR Code</option>
                        <option value="pairing">Pairing Code (Input Nomor HP)</option>
                    </select>
                </div>
                <div id="pairingSection" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Nomor WhatsApp (Format 628xxx)</label>
                        <input type="text" id="pairingPhone" class="form-control" placeholder="628123456789">
                    </div>
                </div>

                <div id="resultArea" class="text-center mt-4" style="display: none;">
                    <div id="qrContainer" style="display: none;">
                        <canvas id="qrCanvas"></canvas>
                        <p class="mt-2 text-muted small">Scan QR di atas lewat WhatsApp > Perangkat Tertaut</p>
                    </div>
                    <div id="pairingContainer" style="display: none;">
                        <h2 id="pairingDisplay" class="fw-bold text-primary letter-spacing-2"></h2>
                        <p class="mt-2 text-muted small">Masukkan kode di atas di HP Anda pada menu <strong>Tautkan dengan nomor telepon saja</strong></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnGenerate">Hubungkan</button>
            </div>
        </div>
    </div>
</div>

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script>
    function showQrModal(qrData) {
        const modal = new bootstrap.Modal(document.getElementById('addSessionModal'));
        document.getElementById('newSessionId').value = "Active Session";
        document.getElementById('resultArea').style.display = 'block';
        document.getElementById('qrContainer').style.display = 'block';
        document.getElementById('pairingContainer').style.display = 'none';
        
        new QRious({
            element: document.getElementById('qrCanvas'),
            value: qrData,
            size: 200
        });
        modal.show();
    }
    document.addEventListener('DOMContentLoaded', function() {
        const btnStartBot = document.getElementById('btnStartBot');
        if (btnStartBot) {
            btnStartBot.addEventListener('click', function() {
                btnStartBot.disabled = true;
                btnStartBot.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menjalankan...';
                
                axios.post("{{ route('whatsapp.bot.start') }}")
                    .then(res => {
                        Swal.fire({
                            icon: 'info',
                            title: 'Memulai Server Bot...',
                            text: res.data.message || 'Harap tunggu sekitar 10 detik lalu segarkan halaman.',
                            timer: 5000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menjalankan Bot',
                            text: err.response?.data?.message || 'Terjadi kesalahan sistem.'
                        });
                        btnStartBot.disabled = false;
                        btnStartBot.innerHTML = '<i class="bx bx-play me-1"></i> Jalankan Server Bot';
                    });
            });
        }

        const methodSelect = document.getElementById('connectMethod');
        const pairingSection = document.getElementById('pairingSection');
        const btnGenerate = document.getElementById('btnGenerate');
        const resultArea = document.getElementById('resultArea');
        const qrCanvas = document.getElementById('qrCanvas');
        
        let pollingInterval = null;

        methodSelect.addEventListener('change', function() {
            pairingSection.style.display = this.value === 'pairing' ? 'block' : 'none';
        });

        // Function to start polling for a specific session
        function startPolling(targetId) {
            if (pollingInterval) clearInterval(pollingInterval);
            
            pollingInterval = setInterval(() => {
                axios.get("{{ route('whatsapp.index') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => {
                        const sessions = res.data.sessions || [];
                        const session = sessions.find(s => s.id === targetId);
                        
                        if (session) {
                            // Update QR if modal is open and QR is available
                            if (session.status === 'qr' && session.qr) {
                                resultArea.style.display = 'block';
                                document.getElementById('qrContainer').style.display = 'block';
                                renderQr(session.qr);
                            }
                            
                            // If connected, reload to show success
                            if (session.status === 'open') {
                                clearInterval(pollingInterval);
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil Terhubung!',
                                    text: 'Perangkat ' + targetId + ' sekarang aktif.',
                                    timer: 2000
                                }).then(() => location.reload());
                            }
                        }
                    });
            }, 3000);
        }

        function renderQr(qrData) {
            qrCanvas.innerHTML = ''; // Clear
            new QRious({
                element: qrCanvas,
                value: qrData,
                size: 240
            });
        }

        btnGenerate.addEventListener('click', function() {
            const id = document.getElementById('newSessionId').value.trim().replace(/\s+/g, '_');
            const method = methodSelect.value;
            const phone = document.getElementById('pairingPhone').value;

            if(!id) return alert('Session ID harus diisi');

            btnGenerate.disabled = true;
            btnGenerate.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghubungkan...';

            if(method === 'qr') {
                axios.post("{{ route('whatsapp.session.start') }}", { id: id })
                    .then(res => {
                        if (res.data.error) {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: res.data.error });
                            btnGenerate.disabled = false;
                            btnGenerate.innerHTML = 'Hubungkan';
                            return;
                        }
                        startPolling(id);
                        resultArea.style.display = 'block';
                        document.getElementById('qrContainer').style.display = 'block';
                        btnGenerate.innerHTML = '<i class="bx bx-sync bx-spin me-1"></i> Menunggu Scan QR...';
                    })
                    .catch(err => {
                        const msg = err.response?.data?.error || err.message || 'Gagal konek ke server bot. Pastikan bot sudah dijalankan.';
                        Swal.fire({ icon: 'error', title: 'Gagal Memulai Sesi', text: msg });
                        btnGenerate.disabled = false;
                        btnGenerate.innerHTML = 'Hubungkan';
                    });
            } else {
                if (!phone) {
                    Swal.fire({ icon: 'warning', title: 'Nomor Kosong', text: 'Masukkan nomor WhatsApp format 628xxx terlebih dahulu.' });
                    btnGenerate.disabled = false;
                    btnGenerate.innerHTML = 'Hubungkan';
                    return;
                }
                axios.post("{{ route('whatsapp.session.pairing') }}", { id: id, phone: phone })
                    .then(res => {
                        if (res.data.pairingCode) {
                            resultArea.style.display = 'block';
                            document.getElementById('pairingContainer').style.display = 'block';
                            document.getElementById('qrContainer').style.display = 'none';
                            document.getElementById('pairingDisplay').innerText = res.data.pairingCode;
                            btnGenerate.style.display = 'none';
                            startPolling(id);
                        } else {
                            const errMsg = res.data.error || 'Tidak ada pairing code diterima dari server.';
                            Swal.fire({ icon: 'error', title: 'Gagal Pairing', text: errMsg });
                            btnGenerate.disabled = false;
                            btnGenerate.innerHTML = 'Hubungkan';
                        }
                    })
                    .catch(err => {
                        const msg = err.response?.data?.error || err.message || 'Gagal konek ke server bot. Pastikan bot sudah dijalankan.';
                        Swal.fire({ icon: 'error', title: 'Gagal Pairing', text: msg });
                        btnGenerate.disabled = false;
                        btnGenerate.innerHTML = 'Hubungkan';
                    });
            }
        });

        // Disconnect / Logout Session
        document.querySelectorAll('.stop-session').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = this.dataset.id;
                Swal.fire({
                    title: 'Disconnect Perangkat?',
                    text: "Koneksi WhatsApp pada sesi '" + sessionId + "' akan terputus.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffab00',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'Ya, Putuskan!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return axios.post("{{ route('whatsapp.session.stop') }}", { id: sessionId, _token: "{{ csrf_token() }}" })
                            .then(response => {
                                if (response.data.error) {
                                    throw new Error(response.data.error);
                                }
                                return response.data;
                            })
                            .catch(error => {
                                Swal.showValidationMessage(
                                    `Gagal: ${error.response?.data?.error || error.message || 'Server Error'}`
                                );
                            });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terputus!',
                            text: 'Sesi WhatsApp berhasil diputuskan.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    }
                });
            });
        });

        // Delete Session Permanently
        document.querySelectorAll('.delete-session').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = this.dataset.id;
                Swal.fire({
                    title: 'Hapus Sesi Permanen?',
                    text: "Semua data autentikasi untuk sesi '" + sessionId + "' akan dihapus dari server.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff3e1d',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return axios.post("{{ route('whatsapp.session.stop') }}", { id: sessionId, _token: "{{ csrf_token() }}" })
                            .then(response => {
                                if (response.data.error) {
                                    throw new Error(response.data.error);
                                }
                                return response.data;
                            })
                            .catch(error => {
                                Swal.showValidationMessage(
                                    `Gagal: ${error.response?.data?.error || error.message || 'Server Error'}`
                                );
                            });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: 'Sesi WhatsApp berhasil dihapus secara permanen.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    }
                });
            });
        });

        // Global status monitor (updates table badges)
        setInterval(() => {
            if (document.getElementById('addSessionModal').classList.contains('show')) return; // Don't conflict with modal polling
            
            axios.get("{{ route('whatsapp.index') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => {
                    const sessions = res.data.sessions || [];
                    const currentOpenCount = document.querySelectorAll('.bg-label-success').length;
                    const newOpenCount = sessions.filter(s => s.status === 'open').length;
                    
                    if (currentOpenCount !== newOpenCount) {
                        location.reload(); // Refresh table if status changed
                    }
                });
        }, 5000);
    });

    function showQrModal(qrData) {
        const modal = new bootstrap.Modal(document.getElementById('addSessionModal'));
        document.getElementById('newSessionId').value = "Active_Session";
        document.getElementById('resultArea').style.display = 'block';
        document.getElementById('qrContainer').style.display = 'block';
        document.getElementById('pairingContainer').style.display = 'none';
        
        new QRious({
            element: document.getElementById('qrCanvas'),
            value: qrData,
            size: 240
        });
        modal.show();
    }
</script>
<style>
    .letter-spacing-2 { letter-spacing: 5px; }
</style>
@endsection
@endsection

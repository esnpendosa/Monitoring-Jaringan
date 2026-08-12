@extends('layouts.app')

@section('title', 'Kamera Scanner QR')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mt-4">
            <div class="bg-pmu p-4 text-white">
                <h4 class="fw-bold mb-0 text-uppercase">SCAN QR CODE ACARA</h4>
                <p class="mb-0 opacity-75 small">Arahkan kamera ke QR Code yang disediakan panitia.</p>
            </div>
            <div class="card-body p-0">
                <div id="reader" style="width: 100%;"></div>
            </div>
            <div class="card-footer bg-white p-4 border-0">
                <div id="result" class="text-muted small mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i> Menunggu scan...
                </div>
                <div class="d-grid mt-3">
                    <a href="{{ route('kepegawaian.acara.index') }}" class="btn btn-light rounded-pill fw-bold">KEMBALI</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    function onScanSuccess(decodedText, decodedResult) {
        // decodedText will be the URL encoded in the QR Code
        // If it's a URL to our system, we follow it.
        // We expect the QR code to contain the full URL like: 
        // http://127.0.0.1:8000/kepegawaian/acara/scan/ACARA-XXXXXX
        
        console.log(`Scan result: ${decodedText}`);
        document.getElementById('result').innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i> QR Terdeteksi! Mengalihkan...</span>';
        
        // Check if the scanned text is a valid URL and contains our scan path
        if (decodedText.includes('/kepegawaian/acara/scan/')) {
            window.location.href = decodedText;
        } else {
            // If it's just the code ACARA-XXXXXX, we manually construct the URL
            if (decodedText.startsWith('ACARA-')) {
                window.location.href = "{{ url('/kepegawaian/acara/scan') }}/" + decodedText;
            } else {
                alert("QR Code tidak valid untuk absensi acara ini.");
                html5QrcodeScanner.render(onScanSuccess); // Restart scanner
            }
        }
    }

    function onScanFailure(error) {
        // handle scan failure, usually better to ignore and keep scanning
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { 
            fps: 10, 
            qrbox: {width: 250, height: 250},
            rememberLastUsedCamera: true,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
        },
        /* verbose= */ false);
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
</script>
@endpush

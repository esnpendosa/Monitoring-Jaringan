<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Pelanggan Rozitech</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
        }

        .page-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .card-container {
            width: 85.6mm; /* ID-1 size */
            height: 53.98mm;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            border: 1px solid #e1e4e8;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            page-break-inside: avoid;
        }

        .card-header {
            background: linear-gradient(135deg, #696cff 0%, #3f4191 100%);
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .logo-section {
            display: flex;
            align-items: center;
        }

        .logo-icon {
            width: 24px;
            height: 24px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 800;
            font-size: 14px;
            margin-right: 8px;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .logo-text h1 {
            margin: 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
        }

        .logo-text p {
            margin: 0;
            font-size: 6px;
            opacity: 0.8;
            letter-spacing: 0.5px;
        }

        .service-badge {
            font-size: 8px;
            background: rgba(255,255,255,0.15);
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .card-body {
            padding: 12px 15px;
            display: flex;
            flex-grow: 1;
            z-index: 1;
        }

        .info-section {
            width: 65%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .customer-name {
            font-size: 14px;
            font-weight: 700;
            color: #32475c;
            margin: 0 0 2px 0;
            text-transform: capitalize;
        }

        .customer-id {
            font-size: 24px;
            font-weight: 800;
            color: #696cff;
            margin: 0 0 5px 0;
            line-height: 1;
        }

        .customer-detail {
            font-size: 7px;
            color: #697a8d;
            margin: 1px 0;
            display: flex;
            align-items: center;
        }

        .customer-detail i {
            margin-right: 4px;
        }

        .qr-section {
            width: 35%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-box {
            width: 65px;
            height: 65px;
            background: #fff;
            padding: 4px;
            border: 1.5px solid #696cff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(105, 108, 255, 0.15);
        }

        .qr-box img {
            width: 100%;
            height: 100%;
        }

        .scan-label {
            font-size: 6px;
            font-weight: 700;
            color: #696cff;
            margin-top: 4px;
            text-transform: uppercase;
            text-align: center;
        }

        .card-footer {
            height: 25px;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-top: 1px dashed #e1e4e8;
        }

        .barcode-img, .barcode-svg {
            height: 20px;
            opacity: 0.85;
            max-width: 100px;
        }

        .web-info {
            font-size: 6px;
            color: #a1acb8;
            font-weight: 600;
        }

        .accent-circle {
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: #696cff;
            opacity: 0.03;
            border-radius: 50%;
            z-index: 0;
        }

        .btn-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 30px;
            background: #696cff;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(105, 108, 255, 0.5);
            display: flex;
            align-items: center;
            transition: transform 0.2s;
            z-index: 1000;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            background: #5f61e6;
        }

        @media print {
            body { background: none; padding: 0; }
            .btn-print { display: none; }
            .page-container { gap: 10px; }
            .card-container {
                box-shadow: none;
                border: 0.5px solid #ddd;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">
        <span style="margin-right: 10px;">🖨️</span> CETAK SEMUA KARTU
    </button>

    <div class="page-container">
        @foreach($pelanggans as $p)
        <div class="card-container">
            <div class="card-header">
                <div class="logo-section">
                    <img src="https://blogger.googleusercontent.com/img/a/AVvXsEhTp3lveqnLYbNRRyeGc_F24FMRpwEuzi9WgEZesnrR0kyUYhfiMwr1ifbLNqIesohcACtM7h713FsLKmE6k38n_-enxgK5pvkL0C9iLA4fbg8YVGXjyWZraY-26Xrf8X6-J0LrURVByT--R0-zq8XASTMh5u8svjvPRTy4dHoVYX-tRiDKnaATKI0PuJE" style="height: 30px; margin-right: 10px;" alt="Rozitech Logo">
                    <div class="logo-text">
                        <h1>Rozitech</h1>
                        <p>Multimedia Indonesia</p>
                    </div>
                </div>

            </div>

            <div class="card-body">
                <div class="info-section">
                    <h3 class="customer-name">{{ Str::limit($p->nama_pelanggan, 25) }}</h3>
                    <h2 class="customer-id">{{ $p->kode_pelanggan }}</h2>
                    <div class="customer-detail">
                        <span>📍 {{ Str::limit($p->alamat, 40) }}</span>
                    </div>
                    <div class="customer-detail" style="margin-top: 2px;">
                        <span>📞 {{ $p->no_wa ?? '-' }}</span>
                    </div>
                </div>

                <div class="qr-section">
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ url('/billing?search=' . $p->kode_pelanggan) }}" alt="QR">
                    </div>
                    <div class="scan-label">Scan Bayar</div>
                </div>
            </div>

            <div class="card-footer">
                <svg class="barcode-svg" id="barcode-{{ $p->kode_pelanggan }}"></svg>
                <div class="web-info">rozitech.co.id</div>
            </div>

            <div class="accent-circle"></div>
        </div>
        @endforeach
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @foreach($pelanggans as $p)
            try {
                JsBarcode("#barcode-{{ $p->kode_pelanggan }}", "{{ $p->kode_pelanggan }}", {
                    format: "CODE128",
                    lineColor: "#000",
                    width: 1.2,
                    height: 25,
                    displayValue: false,
                    margin: 0
                });
            } catch(e) {
                console.error("Gagal membuat barcode untuk {{ $p->kode_pelanggan }}:", e);
            }
            @endforeach
        });
    </script>
</body>
</html>

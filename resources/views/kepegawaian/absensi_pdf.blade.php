<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi Kehadiran Pegawai</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            margin-bottom: 25px;
            border-bottom: 3px double #1e3a8a;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 1px;
        }
        .company-sub {
            font-size: 12px;
            color: #555;
            margin-top: 2px;
        }
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
            color: #111827;
        }
        .subtitle {
            text-align: center;
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 25px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
            font-size: 11px;
        }
        .report-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 10px 8px;
            border: 1px solid #1e3a8a;
            text-transform: uppercase;
        }
        .report-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: middle;
        }
        .report-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-success {
            color: #16a34a;
        }
        .text-danger {
            color: #dc2626;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            width: 50%;
            vertical-align: top;
        }
        .signature-space {
            height: 70px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 70%; border: none; vertical-align: middle;">
                    <div class="company-name">RMI</div>
                    <div class="company-sub">Rozitech Multimedia Indonesia</div>
                </td>
                <td style="width: 30%; border: none; text-align: right; font-size: 10px; color: #4b5563; vertical-align: middle; line-height: 1.4;">
                    CS: 0856-0411-8932<br>
                    IG: @rozitechgresik<br>
                    www.rozitech.co.id
                </td>
            </tr>
        </table>
    </div>

    <div class="title">Laporan Rekapitulasi Kehadiran Pegawai</div>
    <div class="subtitle">Periode: {{ $periodeLabel }}</div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="text-align: left; width: 35%;">Nama Pegawai</th>
                <th style="width: 15%;">Total Kehadiran</th>
                <th style="width: 15%;">Jam Telat</th>
                <th style="width: 15%;">Alpha</th>
                <th style="width: 15%;">Total Jam Kerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $row['user']->name }}</td>
                <td class="text-center font-bold text-success">{{ $row['hadir'] }} Hari</td>
                <td class="text-center font-bold text-danger">{{ $row['jam_telat'] }} Jam</td>
                <td class="text-center font-bold text-danger">{{ $row['alpha'] }} Hari</td>
                <td class="text-center font-bold" style="color: #1e3a8a;">{{ $row['total_jam_kerja'] }} Jam</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px; color: #6b7280;">Tidak ada data kehadiran untuk periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td></td>
                <td class="text-center">
                    Gresik, {{ $reportDate }}<br>
                    <strong>Rozitech Multimedia Indonesia</strong>
                    <div class="signature-space"></div>
                    <strong><u>Administrasi Kepegawaian</u></strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Laporan ini digenerate secara otomatis oleh Sistem RMI pada {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>

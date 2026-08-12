<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lisensi Tidak Valid - SIAP Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .license-card { max-width: 500px; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); background: #fff; text-align: center; }
        .icon { font-size: 60px; color: #dc3545; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="license-card">
        <div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h2 class="fw-bold mb-3">Domain Tidak Berlisensi</h2>
        <p class="text-muted">Aplikasi <strong>SIAP Digital - PMU Bungah</strong> tidak memiliki lisensi untuk digunakan pada domain ini.</p>
        <div class="alert alert-danger py-2 small">
            Domain saat ini: <code>{{ $current }}</code><br>
            Domain berlisensi: <code>{{ $licensed }}</code>
        </div>
        <p class="small text-muted mb-4">Silakan hubungi pengembang untuk aktivasi lisensi domain Anda.</p>
        <a href="mailto:admin@pmu.id" class="btn btn-outline-danger px-4">Hubungi Administrator</a>
    </div>
</body>
</html>

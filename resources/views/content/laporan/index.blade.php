@extends('layouts/contentNavbarLayout')

@section('title', 'Laporan & Statistik')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Sistem /</span> Laporan</h4>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Rekap Pembayaran</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">Excel View</h4>
                        </div>
                        <p class="mb-0 mt-2">Rekap data pembayaran tunai & transfer ala Excel sheet.</p>
                        <a href="{{ route('laporan.rekap-pembayaran') }}" class="btn btn-sm btn-primary mt-3">Buka Rekap</a>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-wallet bx-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Statistik Operasional</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">Tiket Gangguan</h4>
                        </div>
                        <p class="mb-0 mt-2">Grafik laporan gangguan bulanan.</p>
                        <a href="#ticketChart" class="btn btn-sm btn-secondary mt-3">Lihat Grafik</a>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="bx bx-error-circle bx-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title m-0">Statistik Tiket Gangguan {{ date('Y') }}</h5>
            </div>
            <div class="card-body">
                <canvas id="ticketChart" style="height: 350px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('ticketChart').getContext('2d');
        var monthlyData = @json($monthlyTickets);
        
        var labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var ticketCounts = new Array(12).fill(0);
        
        monthlyData.forEach(function(item) {
            ticketCounts[item.month - 1] = item.count;
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Tiket',
                    data: ticketCounts,
                    backgroundColor: '#696cff',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
</script>
@endsection

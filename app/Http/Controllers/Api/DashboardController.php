<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Display dashboard summary metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // 1. Total Pelanggan Aktif
        $totalPelangganAktif = Pelanggan::where('is_active', true)->count();

        // 2. Pendapatan Bulan Ini (Lunas)
        $pendapatanBulanIni = Tagihan::where('status', 'paid')
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->sum('jumlah');

        // 3. Tagihan Jatuh Tempo (Belum Lunas Bulan Ini)
        $tagihanJatuhTempo = Tagihan::where('status', 'unpaid')
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->count();

        // 4. Pelanggan Baru Bulan Ini
        $pelangganBaruBulanIni = Pelanggan::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $summary = [
            'total_pelanggan_aktif' => $totalPelangganAktif,
            'pendapatan_bulan_ini' => (int) $pendapatanBulanIni,
            'tagihan_jatuh_tempo_bulan_ini' => $tagihanJatuhTempo,
            'pelanggan_baru_bulan_ini' => $pelangganBaruBulanIni,
        ];

        return $this->successResponse($summary, 'Berhasil mengambil ringkasan dashboard');
    }
}

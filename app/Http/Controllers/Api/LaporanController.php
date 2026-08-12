<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Http\Resources\TagihanResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    use ApiResponse;

    /**
     * Get payment recap report.
     */
    public function rekapPembayaran(Request $request): JsonResponse
    {
        $query = Tagihan::with('pelanggan');

        // Search by Pelanggan Name, Code, or Payment Method
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('pelanggan', function ($sub) use ($search) {
                    $sub->where('nama_pelanggan', 'like', "%{$search}%")
                        ->orWhere('kode_pelanggan', 'like', "%{$search}%");
                })->orWhere('metode_pembayaran', 'like', "%{$search}%");
            });
        }

        // Filter by Month and Year
        if ($request->filled('month') && $request->filled('year')) {
            $m = $request->input('month');
            $y = $request->input('year');
            $query->where(function($q) use ($m, $y) {
                $q->where(function($sub) use ($m, $y) {
                    $sub->where('bulan', $m)
                        ->where('tahun', $y);
                })->orWhere(function($sub) use ($m, $y) {
                    $sub->where('bayar_di_awal', true)
                        ->whereMonth('paid_at', $m)
                        ->whereYear('paid_at', $y);
                });
            });
        } else {
            if ($request->filled('month')) {
                $query->where('bulan', $request->input('month'));
            }
            if ($request->filled('year')) {
                $query->where('tahun', $request->input('year'));
            }
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by Metode Pembayaran
        if ($request->filled('metode_pembayaran')) {
            $query->where('metode_pembayaran', $request->input('metode_pembayaran'));
        }

        // Filter by Date Range (updated_at)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('updated_at', [$request->input('start_date') . ' 00:00:00', $request->input('end_date') . ' 23:59:59']);
        }

        $tagihan = $query->orderBy('updated_at', 'desc')->get();

        // Adjust amount to 0 for bills paid in advance in a different month
        if ($request->filled('month') && $request->filled('year')) {
            $m = (int) $request->input('month');
            $y = (int) $request->input('year');
            foreach ($tagihan as $t) {
                if ($t->bayar_di_awal && $t->paid_at) {
                    $paidMonth = (int) $t->paid_at->format('n');
                    $paidYear = (int) $t->paid_at->format('Y');
                    if ($paidMonth !== $m || $paidYear !== $y) {
                        $t->original_jumlah = $t->jumlah;
                        $t->jumlah = 0;
                    }
                }
            }
        }

        // Calculate Totals
        $total_pembayaran = $tagihan->sum('jumlah');
        $total_lunas = $tagihan->where('status', 'paid')->sum('jumlah');
        $total_piutang = $tagihan->where('status', '!=', 'paid')->sum('jumlah');
        
        $total_cash = $tagihan->where('metode_pembayaran', 'Cash')->sum('jumlah');
        $total_transfer = $tagihan->where('metode_pembayaran', '!=', 'Cash')->whereNotNull('metode_pembayaran')->sum('jumlah');
        
        $total_cash_lunas = $tagihan->where('status', 'paid')->where('metode_pembayaran', 'Cash')->sum('jumlah');
        $total_transfer_lunas = $tagihan->where('status', 'paid')->where('metode_pembayaran', '!=', 'Cash')->whereNotNull('metode_pembayaran')->sum('jumlah');

        // Get unique methods for filter options
        $available_methods = Tagihan::whereNotNull('metode_pembayaran')
            ->where('metode_pembayaran', '!=', '')
            ->distinct()
            ->pluck('metode_pembayaran');

        $data = [
            'total_pembayaran' => (int) $total_pembayaran,
            'total_lunas' => (int) $total_lunas,
            'total_piutang' => (int) $total_piutang,
            'total_cash' => (int) $total_cash,
            'total_transfer' => (int) $total_transfer,
            'total_cash_lunas' => (int) $total_cash_lunas,
            'total_transfer_lunas' => (int) $total_transfer_lunas,
            'available_methods' => $available_methods,
            'tagihan' => TagihanResource::collection($tagihan)
        ];

        return $this->successResponse($data, 'Berhasil mengambil rekap pembayaran');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Models\TiketGangguan;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function index()
    {
        $monthlyTickets = TiketGangguan::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->get();
            
        return view('content.laporan.index', compact('monthlyTickets'));
    }

    public function rekapPembayaran(Request $request)
    {
        $query = Tagihan::with('pelanggan');

        // Search by Pelanggan Name, Code, or Payment Method
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('pelanggan', function ($sub) use ($search) {
                    $sub->where('nama_pelanggan', 'like', "%{$search}%")
                        ->orWhere('kode_pelanggan', 'like', "%{$search}%");
                })->orWhere('metode_pembayaran', 'like', "%{$search}%");
            });
        }

        // Filter by Month and Year
        if ($request->month && $request->year) {
            $m = $request->month;
            $y = $request->year;
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
            if ($request->month) {
                $query->where('bulan', $request->month);
            }
            if ($request->year) {
                $query->where('tahun', $request->year);
            }
        }

        // Filter by Status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by Metode Pembayaran
        if ($request->metode_pembayaran) {
            $query->where('metode_pembayaran', $request->metode_pembayaran);
        }

        // Filter by Date Range (updated_at)
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('updated_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $tagihan = $query->orderBy('updated_at', 'desc')->get();

        // Adjust amount to 0 for bills paid in advance in a different month
        if ($request->month && $request->year) {
            $m = (int) $request->month;
            $y = (int) $request->year;
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

        return view('content.laporan.rekap_pembayaran', compact(
            'tagihan', 
            'total_pembayaran', 
            'total_lunas',
            'total_piutang',
            'total_cash', 
            'total_transfer',
            'total_cash_lunas',
            'total_transfer_lunas',
            'available_methods'
        ));
    }

    public function exportExcel(Request $request)
    {
        $query = Tagihan::with('pelanggan');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('pelanggan', function ($sub) use ($search) {
                    $sub->where('nama_pelanggan', 'like', "%{$search}%")
                        ->orWhere('kode_pelanggan', 'like', "%{$search}%");
                })->orWhere('metode_pembayaran', 'like', "%{$search}%");
            });
        }
        if ($request->month && $request->year) {
            $m = $request->month;
            $y = $request->year;
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
            if ($request->month) $query->where('bulan', $request->month);
            if ($request->year) $query->where('tahun', $request->year);
        }
        if ($request->status) $query->where('status', $request->status);
        if ($request->metode_pembayaran) $query->where('metode_pembayaran', $request->metode_pembayaran);
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('updated_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $tagihan = $query->orderBy('updated_at', 'desc')->get();

        // Adjust amount to 0 for bills paid in advance in a different month
        if ($request->month && $request->year) {
            $m = (int) $request->month;
            $y = (int) $request->year;
            foreach ($tagihan as $t) {
                if ($t->bayar_di_awal && $t->paid_at) {
                    $paidMonth = (int) $t->paid_at->format('n');
                    $paidYear = (int) $t->paid_at->format('Y');
                    if ($paidMonth !== $m || $paidYear !== $y) {
                        $t->jumlah = 0;
                    }
                }
            }
        }

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=rekap-pembayaran-" . date('Ymd-His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['No', 'ID Pelanggan', 'Nama Pelanggan', 'Periode', 'Jumlah Pembayaran', 'Metode Pembayaran', 'Tanggal Bayar', 'Status'];

        $callback = function() use($tagihan, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // Add UTF-8 BOM
            fputcsv($file, $columns);

            $i = 1;
            foreach ($tagihan as $t) {
                $monthName = date('F', mktime(0, 0, 0, $t->bulan, 10));
                fputcsv($file, [
                    $i++,
                    $t->pelanggan->kode_pelanggan,
                    $t->pelanggan->nama_pelanggan,
                    $monthName . ' ' . $t->tahun,
                    $t->jumlah,
                    $t->metode_pembayaran ?: '-',
                    $t->paid_at ? date('Y-m-d H:i:s', strtotime($t->paid_at)) : '-',
                    strtoupper($t->status)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

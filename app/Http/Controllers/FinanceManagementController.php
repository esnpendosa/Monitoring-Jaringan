<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use App\Models\Tagihan;
use App\Models\KasBon;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceManagementController extends Controller
{
    /**
     * Dashboard Manajemen Keuangan Khusus Role Finance
     */
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', date('m'));
        $tahun = (int) $request->get('tahun', date('Y'));

        $query = Keuangan::query();

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $tahun);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('keterangan', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('tanggal', 'desc')->paginate(20);

        // Calculate Totals & Net Cashflow
        $totalPaidBills = Tagihan::where('status', 'paid')->sum('jumlah');
        $totalPsbIncome = Keuangan::where('tipe', 'psb')->sum('jumlah');
        $totalIncome    = $totalPaidBills + $totalPsbIncome;

        $totalDirectExpense = Keuangan::where('tipe', 'pengeluaran')->sum('jumlah');
        $totalKasBonApproved = KasBon::where('status', 'disetujui')->sum('jumlah');
        $totalExpense = $totalDirectExpense + $totalKasBonApproved;

        $netBalance = $totalIncome - $totalExpense;

        // Monthly Stats
        $monthPaidBills = Tagihan::where('status', 'paid')
            ->whereMonth('paid_at', $bulan)
            ->whereYear('paid_at', $tahun)
            ->sum('jumlah');

        $monthPsbIncome = Keuangan::where('tipe', 'psb')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah');

        $monthIncome = $monthPaidBills + $monthPsbIncome;

        $monthDirectExpense = Keuangan::where('tipe', 'pengeluaran')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah');

        $monthKasBon = KasBon::where('status', 'disetujui')
            ->whereMonth('created_at', $bulan)
            ->whereYear('created_at', $tahun)
            ->sum('jumlah');

        $monthExpense = $monthDirectExpense + $monthKasBon;
        $monthNet = $monthIncome - $monthExpense;

        // Category breakdowns for chart/analytics
        $categories = Keuangan::select('kategori', DB::raw('SUM(jumlah) as total'))
            ->groupBy('kategori')
            ->get();

        return view('content.finance.index', compact(
            'transactions',
            'bulan',
            'tahun',
            'totalIncome',
            'totalExpense',
            'netBalance',
            'monthIncome',
            'monthExpense',
            'monthNet',
            'categories'
        ));
    }

    /**
     * Catat Transaksi Kas Masuk / Keluar Baru
     */
    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'tipe'       => 'required|in:pengeluaran,psb,pemasukan',
            'kategori'   => 'required|string|max:255',
            'jumlah'     => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'tanggal'    => 'required|date',
            'nota'       => 'nullable|image|max:5120',
        ]);

        $notaPath = null;
        if ($request->hasFile('nota')) {
            $file = $request->file('nota');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $targetDir = storage_path('app/public/nota_keuangan');
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $filename);
            $notaPath = 'nota_keuangan/' . $filename;
        }

        // Map tipe 'pemasukan' to 'psb' or store directly
        $dbTipe = ($validated['tipe'] === 'pemasukan') ? 'psb' : $validated['tipe'];

        Keuangan::create([
            'tipe'       => $dbTipe,
            'kategori'   => $validated['kategori'],
            'jumlah'     => $validated['jumlah'],
            'keterangan' => $validated['keterangan'] . ($notaPath ? " (Nota: storage/{$notaPath})" : ''),
            'tanggal'    => $validated['tanggal'],
        ]);

        return redirect()->route('finance.index')->with('success', 'Transaksi kas keuangan berhasil dicatat!');
    }

    /**
     * Hapus Transaksi Kas
     */
    public function destroyTransaction($id)
    {
        $transaction = Keuangan::findOrFail($id);
        $transaction->delete();

        return redirect()->route('finance.index')->with('success', 'Transaksi keuangan berhasil dihapus!');
    }
}

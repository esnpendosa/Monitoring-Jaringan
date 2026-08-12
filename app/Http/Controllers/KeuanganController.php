<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KeuanganController extends Controller
{
    public function index(Request $request)
    {
        $query = Keuangan::query();

        // Filters
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        } else {
            $query->whereYear('tanggal', date('Y'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('keterangan', 'like', '%' . $request->search . '%')
                  ->orWhere('kategori', 'like', '%' . $request->search . '%');
            });
        }

        $bulan = (int) $request->get('bulan', date('m'));
        $tahun = (int) $request->get('tahun', date('Y'));

        $transactions = $query->orderBy('tanggal', 'desc')->paginate(50);

        // Stats
        $stats = [
            'total_pengeluaran' => Keuangan::where('tipe', 'pengeluaran')->sum('jumlah'),
            'total_psb' => Keuangan::where('tipe', 'psb')->sum('jumlah'),
            'bulan_pengeluaran' => Keuangan::where('tipe', 'pengeluaran')->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->sum('jumlah'),
            'bulan_psb' => Keuangan::where('tipe', 'psb')->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->sum('jumlah'),
        ];

        // Grouped by Category for charts/analytics
        $expenseCategories = Keuangan::where('tipe', 'pengeluaran')
            ->selectRaw('kategori, SUM(jumlah) as total')
            ->groupBy('kategori')
            ->get();

        return view('keuangan.index', compact('transactions', 'stats', 'expenseCategories', 'bulan', 'tahun'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipe' => 'required|in:pengeluaran,psb',
            'kategori' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'tanggal' => 'required|date',
        ]);

        Keuangan::create($validated);

        return redirect()->back()->with('success', 'Transaksi keuangan berhasil dicatat.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tipe' => 'required|in:pengeluaran,psb',
            'kategori' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'tanggal' => 'required|date',
        ]);

        $keuangan = Keuangan::findOrFail($id);
        $keuangan->update($validated);

        return redirect()->back()->with('success', 'Transaksi keuangan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $keuangan = Keuangan::findOrFail($id);
        $keuangan->delete();

        return redirect()->back()->with('success', 'Transaksi keuangan berhasil dihapus.');
    }
}

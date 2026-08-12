<?php

namespace App\Http\Controllers;

use App\Models\KasBon;
use App\Models\Teknisi;
use Illuminate\Http\Request;

class KasBonController extends Controller
{
    public function index(Request $request)
    {
        $query = KasBon::with('teknisi');

        // Filter search/name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama_pekerja', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('teknisi', function($qt) use ($search) {
                      $qt->where('nama_teknisi', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $kasBons = $query->latest('tanggal')->paginate(15);
        
        // Fetch all Users with role 'Teknisi' along with their Teknisi profile if it exists
        $teknisiUsers = \App\Models\User::whereHas('role', function($q) {
            $q->where('name', 'Teknisi');
        })->with('teknisi')->get();

        // Statistics
        $totalBelumLunas = KasBon::where('status', 'belum_lunas')->sum('jumlah');
        $totalLunas = KasBon::where('status', 'lunas')->sum('jumlah');
        
        // Grouped unpaid summary by worker
        $groupedUnpaid = KasBon::where('status', 'belum_lunas')
            ->with('teknisi')
            ->get()
            ->groupBy(function($item) {
                return $item->worker_name;
            })->map(function($items) {
                return $items->sum('jumlah');
            });

        return view('content.kas-bon.index', compact(
            'kasBons',
            'teknisiUsers',
            'totalBelumLunas',
            'totalLunas',
            'groupedUnpaid'
        ));
    }

    public function store(Request $request)
    {
        if ($request->filled('selected_pekerja')) {
            $parts = explode(':', $request->input('selected_pekerja'), 2);
            if ($parts[0] === 'teknisi') {
                $request->merge([
                    'id_teknisi' => $parts[1],
                    'nama_pekerja' => null
                ]);
            } else {
                $request->merge([
                    'id_teknisi' => null,
                    'nama_pekerja' => $parts[1]
                ]);
            }
        }

        $validated = $request->validate([
            'id_teknisi' => 'nullable|exists:teknisi,id_teknisi',
            'nama_pekerja' => 'nullable|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:belum_lunas,lunas,dibatalkan',
        ]);

        if (empty($validated['id_teknisi']) && empty($validated['nama_pekerja'])) {
            return back()->withErrors(['id_teknisi' => 'Pilih salah satu pekerja atau masukkan nama pekerja manual.'])->withInput();
        }

        $kasBon = KasBon::create($validated);

        \App\Helpers\ActivityLogger::log('Mencatat Kas Bon Baru untuk ' . $kasBon->worker_name . ' sebesar Rp ' . number_format($kasBon->jumlah, 0, ',', '.'), 'kas_bon');

        return redirect()->route('kas-bon.index')->with('success', 'Kas Bon baru berhasil dicatat.');
    }

    public function update(Request $request, $id)
    {
        $kasBon = KasBon::findOrFail($id);

        if ($request->filled('selected_pekerja')) {
            $parts = explode(':', $request->input('selected_pekerja'), 2);
            if ($parts[0] === 'teknisi') {
                $request->merge([
                    'id_teknisi' => $parts[1],
                    'nama_pekerja' => null
                ]);
            } else {
                $request->merge([
                    'id_teknisi' => null,
                    'nama_pekerja' => $parts[1]
                ]);
            }
        }

        $validated = $request->validate([
            'id_teknisi' => 'nullable|exists:teknisi,id_teknisi',
            'nama_pekerja' => 'nullable|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:belum_lunas,lunas,dibatalkan',
        ]);

        if (empty($validated['id_teknisi']) && empty($validated['nama_pekerja'])) {
            return back()->withErrors(['id_teknisi' => 'Pilih salah satu pekerja atau masukkan nama pekerja manual.'])->withInput();
        }

        // Keep model clean
        if (!empty($validated['id_teknisi'])) {
            $validated['nama_pekerja'] = null;
        }

        $kasBon->update($validated);

        \App\Helpers\ActivityLogger::log('Mengubah catatan Kas Bon #' . $kasBon->id_kas_bon . ' untuk ' . $kasBon->worker_name, 'kas_bon');

        return redirect()->route('kas-bon.index')->with('success', 'Catatan Kas Bon berhasil diubah.');
    }

    public function pay($id)
    {
        $kasBon = KasBon::findOrFail($id);
        $kasBon->update(['status' => 'lunas']);

        \App\Helpers\ActivityLogger::log('Melunasi Kas Bon #' . $kasBon->id_kas_bon . ' untuk ' . $kasBon->worker_name . ' sebesar Rp ' . number_format($kasBon->jumlah, 0, ',', '.'), 'kas_bon');

        return redirect()->route('kas-bon.index')->with('success', 'Kas Bon telah berhasil dilunasi.');
    }

    public function destroy($id)
    {
        $kasBon = KasBon::findOrFail($id);
        
        \App\Helpers\ActivityLogger::log('Menghapus catatan Kas Bon #' . $kasBon->id_kas_bon . ' untuk ' . $kasBon->worker_name, 'kas_bon');

        $kasBon->delete();

        return redirect()->route('kas-bon.index')->with('success', 'Catatan Kas Bon berhasil dihapus.');
    }
}

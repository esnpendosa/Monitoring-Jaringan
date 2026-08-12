<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rpp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RppController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role == 'admin' || $user->role == 'yayasan') {
            $rppList = Rpp::with('user')->orderBy('tanggal', 'desc')->paginate(10);
        } else {
            $rppList = Rpp::where('user_id', $user->id)->orderBy('tanggal', 'desc')->paginate(10);
        }
        
        return view('kepegawaian.rpp', compact('rppList'));
    }

    public function create()
    {
        $user = Auth::user();
        return view('kepegawaian.rpp_tambah', compact('user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'unit' => 'required|string',
            'kelas' => 'required|string',
            'mata_pelajaran' => 'required|string',
            'judul' => 'required|string',
            'deskripsi' => 'required|string',
            'dokumen' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('dokumen')->store('dokumen_rpp', 'public');

        // Determine Tahun Akademik based on tanggal
        $year = date('Y', strtotime($request->tanggal));
        $month = date('n', strtotime($request->tanggal));
        if ($month >= 7) {
            $tahunAkademik = $year . '/' . ($year + 1);
        } else {
            $tahunAkademik = ($year - 1) . '/' . $year;
        }

        Rpp::create([
            'user_id' => Auth::id(),
            'tanggal' => $request->tanggal,
            'tahun_akademik' => $tahunAkademik,
            'unit' => $request->unit,
            'kelas' => $request->kelas,
            'mata_pelajaran' => $request->mata_pelajaran,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'dokumen' => $path,
        ]);

        return redirect()->route('kepegawaian.rpp')->with('success', 'RPP berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $rpp = Rpp::where('user_id', Auth::id())->findOrFail($id);
        Storage::disk('public')->delete($rpp->dokumen);
        $rpp->delete();

        return redirect()->back()->with('success', 'RPP berhasil dihapus.');
    }
}

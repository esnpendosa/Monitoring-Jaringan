<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SkPermohonan;
use Illuminate\Support\Facades\Auth;

class SkPermohonanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role == 'admin' || $user->role == 'yayasan') {
            $permohonans = SkPermohonan::with('user')->orderBy('created_at', 'desc')->paginate(10);
        } else {
            $permohonans = SkPermohonan::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate(10);
        }
        
        return view('kepegawaian.sk', compact('permohonans'));
    }

    public function create()
    {
        $user = Auth::user();
        $biodata = $user->biodata;
        return view('kepegawaian.sk_tambah', compact('user', 'biodata'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'catatan' => 'required|string',
        ]);

        SkPermohonan::create([
            'user_id' => Auth::id(),
            'catatan' => $request->catatan,
            'status' => 'Pending',
        ]);

        return redirect()->route('kepegawaian.sk')->with('success', 'Pengajuan SK berhasil dikirim.');
    }

    public function updateStatus(Request $request, $id)
    {
        $permohonan = SkPermohonan::findOrFail($id);
        $permohonan->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Status permohonan SK berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $permohonan = SkPermohonan::findOrFail($id);
        
        // Ensure user owns it or is admin/yayasan
        if (Auth::id() !== $permohonan->user_id && !Auth::user()->role == 'admin' && !Auth::user()->role == 'yayasan') {
            abort(403);
        }

        $permohonan->delete();

        return redirect()->back()->with('success', 'Data pengajuan SK berhasil dihapus.');
    }
}

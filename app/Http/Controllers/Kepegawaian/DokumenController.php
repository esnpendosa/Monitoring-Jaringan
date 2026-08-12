<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dokumen;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class DokumenController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search');

        $query = Dokumen::with('user');

        if ($user->isAdminUnit() || $user->isYayasan()) {
            // Admin can search across all users
            if ($search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('tipe', 'like', "%{$search}%");
            }
            // Order by User Name A-Z
            $dokumens = $query->join('users', 'dokumens.user_id', '=', 'users.id')
                ->select('dokumens.*')
                ->orderBy('users.name', 'asc')
                ->paginate(20);
        } else {
            // Pegawai only sees their own
            $dokumens = $user->dokumens()->latest()->paginate(10);
        }
        
        return view('kepegawaian.dokumen', compact('dokumens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipe' => 'required|string',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:3072', // Limit 3MB
        ]);

        $user = Auth::user();
        
        // Delete any existing documents of the same type for this user to prevent duplicates and save disk space
        $existingDocs = Dokumen::where('user_id', $user->id)->where('tipe', $request->tipe)->get();
        foreach ($existingDocs as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $file = $request->file('file');
        
        // Professional File Naming: DOK_TIPEDOKUMEN_NAMAPEGAWAI_TIMESTAMP.EXT
        $cleanName = str_replace(' ', '_', $user->name);
        $fileName = 'DOK_' . strtoupper($request->tipe) . '_' . strtoupper($cleanName) . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Store in public/arsip_digital
        $path = $file->storeAs('arsip_digital', $fileName, 'public');

        Dokumen::create([
            'user_id' => $user->id,
            'tipe' => $request->tipe,
            'file_path' => $path,
            'status' => 'Disetujui',
        ]);

        return redirect()->back()->with('success', 'Dokumen berhasil diarsipkan secara digital dan otomatis telah divalidasi oleh sistem.');
    }

    public function validateDokumen(Request $request, $id)
    {
        if (!Auth::user()->isAdminUnit() && !Auth::user()->isYayasan()) {
            abort(403);
        }

        $dokumen = Dokumen::findOrFail($id);
        $dokumen->update([
            'status' => $request->status,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->back()->with('success', 'Validasi dokumen berhasil disimpan.');
    }

    public function destroy($id)
    {
        $dokumen = Dokumen::findOrFail($id);
        
        // Ensure user owns it or is admin/yayasan
        if (Auth::id() !== $dokumen->user_id && !Auth::user()->isAdminUnit() && !Auth::user()->isYayasan()) {
            abort(403);
        }

        // Delete actual file
        Storage::disk('public')->delete($dokumen->file_path);
        $dokumen->delete();

        return redirect()->back()->with('success', 'Dokumen berhasil dihapus selamanya.');
    }

    /**
     * Serve files from storage securely to bypass symbolic link issues (403 Forbidden).
     */
    public function viewFile(Request $request)
    {
        $path = $request->query('path');
        
        if (!$path) {
            abort(404);
        }

        // Basic security: only allow access to files inside storage/app/public
        // The path should be relative to the 'public' disk
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $fullPath = Storage::disk('public')->path($path);
        
        // Return file response
        return response()->file($fullPath);
    }
}

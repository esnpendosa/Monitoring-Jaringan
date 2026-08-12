<?php

namespace App\Http\Controllers;

use App\Models\Tutorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TutorialController extends Controller
{
    // ── Helpers ─────────────────────────────────────────────────────────────────
    private function isAdmin()
    {
        $role = auth()->user()->role?->name ?? '';
        return in_array($role, ['Admin', 'Manajer']);
    }

    // ── PUBLIC: Daftar Tutorial (Semua role login) ───────────────────────────
    public function index(Request $request)
    {
        $kategori = $request->get('kategori');
        $search   = $request->get('search');

        $query = Tutorial::published()->ordered();

        if ($kategori) {
            $query->where('kategori', $kategori);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('ringkasan', 'like', "%{$search}%");
            });
        }

        $tutorials  = $query->get();
        $kategoriList = Tutorial::kategoriList();
        $isAdmin    = $this->isAdmin();

        return view('content.tutorial.index', compact('tutorials', 'kategoriList', 'kategori', 'search', 'isAdmin'));
    }

    // ── PUBLIC: Baca Detail Tutorial ────────────────────────────────────────
    public function show(Tutorial $tutorial)
    {
        if (!$tutorial->is_published && !$this->isAdmin()) {
            abort(404);
        }

        $related = Tutorial::published()
            ->where('kategori', $tutorial->kategori)
            ->where('id', '!=', $tutorial->id)
            ->ordered()
            ->take(4)
            ->get();

        $isAdmin = $this->isAdmin();

        return view('content.tutorial.show', compact('tutorial', 'related', 'isAdmin'));
    }

    // ── ADMIN: Kelola Tutorial ───────────────────────────────────────────────
    public function adminIndex()
    {
        if (!$this->isAdmin()) abort(403);

        $tutorials    = Tutorial::with('author')->ordered()->get();
        $kategoriList = Tutorial::kategoriList();
        return view('content.tutorial.admin-index', compact('tutorials', 'kategoriList'));
    }

    public function create()
    {
        if (!$this->isAdmin()) abort(403);

        $kategoriList = Tutorial::kategoriList();
        return view('content.tutorial.create', compact('kategoriList'));
    }

    public function store(Request $request)
    {
        if (!$this->isAdmin()) abort(403);

        $request->validate([
            'judul'       => 'required|string|max:255',
            'kategori'    => 'required|string',
            'ringkasan'   => 'nullable|string|max:500',
            'konten'      => 'required|string',
            'urutan'      => 'nullable|integer',
            'is_published'=> 'nullable|boolean',
            'thumbnail'   => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $file     = $request->file('thumbnail');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $dir      = storage_path('app/public/tutorials');
            if (!file_exists($dir)) mkdir($dir, 0775, true);
            $file->move($dir, $filename);
            $thumbnailPath = 'tutorials/' . $filename;
        }

        Tutorial::create([
            'judul'        => $request->judul,
            'kategori'     => $request->kategori,
            'ringkasan'    => $request->ringkasan,
            'konten'       => $request->konten,
            'urutan'       => $request->urutan ?? 0,
            'is_published' => $request->boolean('is_published'),
            'thumbnail'    => $thumbnailPath,
            'created_by'   => auth()->id(),
        ]);

        return redirect()->route('tutorial.admin.index')->with('success', 'Tutorial berhasil dibuat!');
    }

    public function edit(Tutorial $tutorial)
    {
        if (!$this->isAdmin()) abort(403);

        $kategoriList = Tutorial::kategoriList();
        return view('content.tutorial.edit', compact('tutorial', 'kategoriList'));
    }

    public function update(Request $request, Tutorial $tutorial)
    {
        if (!$this->isAdmin()) abort(403);

        $request->validate([
            'judul'        => 'required|string|max:255',
            'kategori'     => 'required|string',
            'ringkasan'    => 'nullable|string|max:500',
            'konten'       => 'required|string',
            'urutan'       => 'nullable|integer',
            'is_published' => 'nullable|boolean',
            'thumbnail'    => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $thumbnailPath = $tutorial->thumbnail;
        if ($request->hasFile('thumbnail')) {
            // Hapus thumbnail lama
            if ($thumbnailPath) {
                $oldPath = storage_path('app/public/' . $thumbnailPath);
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $file     = $request->file('thumbnail');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $dir      = storage_path('app/public/tutorials');
            if (!file_exists($dir)) mkdir($dir, 0775, true);
            $file->move($dir, $filename);
            $thumbnailPath = 'tutorials/' . $filename;
        }

        $tutorial->update([
            'judul'        => $request->judul,
            'kategori'     => $request->kategori,
            'ringkasan'    => $request->ringkasan,
            'konten'       => $request->konten,
            'urutan'       => $request->urutan ?? 0,
            'is_published' => $request->boolean('is_published'),
            'thumbnail'    => $thumbnailPath,
        ]);

        return redirect()->route('tutorial.admin.index')->with('success', 'Tutorial berhasil diperbarui!');
    }

    public function destroy(Tutorial $tutorial)
    {
        if (!$this->isAdmin()) abort(403);

        if ($tutorial->thumbnail) {
            $oldPath = storage_path('app/public/' . $tutorial->thumbnail);
            if (file_exists($oldPath)) @unlink($oldPath);
        }

        $tutorial->delete();
        return redirect()->route('tutorial.admin.index')->with('success', 'Tutorial berhasil dihapus!');
    }

    public function togglePublish(Tutorial $tutorial)
    {
        if (!$this->isAdmin()) abort(403);

        $tutorial->update(['is_published' => !$tutorial->is_published]);
        $status = $tutorial->is_published ? 'dipublikasikan' : 'disembunyikan';
        return back()->with('success', "Tutorial berhasil {$status}.");
    }

    // ── AJAX: Upload gambar inline dari TinyMCE ──────────────────────────────
    public function uploadImage(Request $request)
    {
        if (!$this->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $request->validate(['file' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:5120']);

        $file     = $request->file('file');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $dir      = storage_path('app/public/tutorials/images');
        if (!file_exists($dir)) mkdir($dir, 0775, true);
        $file->move($dir, $filename);

        $url = url('storage/tutorials/images/' . $filename);
        return response()->json(['location' => $url]); // Format respons TinyMCE
    }
}

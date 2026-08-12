<?php

namespace App\Http\Controllers;

use App\Models\Modem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ModemController extends Controller
{
    /**
     * Halaman publik / pelanggan - daftar modem
     */
    public function index(Request $request)
    {
        $query = Modem::select(['id', 'nama', 'merek', 'model', 'ip_address', 'image_path_front', 'image_path_back', 'deskripsi', 'is_active'])
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('merek', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('merek')) {
            $query->where('merek', $request->merek);
        }

        $modems = $query->orderBy('merek')->orderBy('model')->paginate(12)->withQueryString();

        // Cache daftar merek selama 5 menit agar tidak query ulang setiap request
        $mereks = Cache::remember('modem_mereks', 300, function () {
            return Modem::where('is_active', true)
                ->distinct()
                ->orderBy('merek')
                ->pluck('merek');
        });

        return view('content.modem.index', compact('modems', 'mereks'));
    }

    /**
     * Detail satu modem
     */
    public function show(Modem $modem)
    {
        abort_unless($modem->is_active || auth()->user()?->hasPermission('pelanggan_manage'), 404);
        return view('content.modem.show', compact('modem'));
    }

    /* ==================== Admin Only ==================== */

    public function adminIndex(Request $request)
    {
        $modems = Modem::orderByDesc('created_at')->paginate(20);
        return view('content.modem.admin-index', compact('modems'));
    }

    public function create()
    {
        return view('content.modem.create');
    }

    public function store(Request $request)
    {

        $request->validate([
            'nama'        => 'required|string|max:100',
            'merek'       => 'required|string|max:50',
            'model'       => 'required|string|max:50',
            'ip_address'  => 'nullable|string|max:100',
            'deskripsi'   => 'nullable|string',
            'spesifikasi' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // Validasi file gambar tanpa fileinfo (cek ekstensi manual)
        foreach (['image_front', 'image_back'] as $field) {
            if ($request->hasFile($field)) {
                $ext = strtolower($request->file($field)->getClientOriginalExtension());
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    return back()->withErrors([$field => 'Gambar harus berformat jpg, jpeg, png, atau webp.'])->withInput();
                }
                if ($request->file($field)->getSize() > 2048 * 1024) {
                    return back()->withErrors([$field => 'Ukuran gambar maksimal 2MB.'])->withInput();
                }
            }
        }

        $validated = $request->only(['nama', 'merek', 'model', 'ip_address', 'deskripsi', 'spesifikasi', 'is_active']);
        $validated['is_active'] = $request->boolean('is_active');

        foreach (['image_front' => 'image_path_front', 'image_back' => 'image_path_back'] as $inputKey => $dbColumn) {
            if ($request->hasFile($inputKey)) {
                $file     = $request->file($inputKey);
                $filename = uniqid('modem_') . '.' . strtolower($file->getClientOriginalExtension());
                $destPath = storage_path('app/public/modems');
                if (!file_exists($destPath)) {
                    mkdir($destPath, 0777, true);
                }
                $file->move($destPath, $filename);
                $validated[$dbColumn] = 'modems/' . $filename;
            }
        }

        unset($validated['image_front'], $validated['image_back']);
        Modem::create($validated);
        Cache::forget('modem_mereks');

        return redirect()->route('modem.admin.index')->with('success', 'Modem berhasil ditambahkan.');
    }

    public function edit(Modem $modem)
    {
        return view('content.modem.edit', compact('modem'));
    }

    public function update(Request $request, Modem $modem)
    {

        $request->validate([
            'nama'        => 'required|string|max:100',
            'merek'       => 'required|string|max:50',
            'model'       => 'required|string|max:50',
            'ip_address'  => 'nullable|string|max:100',
            'deskripsi'   => 'nullable|string',
            'spesifikasi' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // Validasi file gambar tanpa fileinfo (cek ekstensi manual)
        foreach (['image_front', 'image_back'] as $field) {
            if ($request->hasFile($field)) {
                $ext = strtolower($request->file($field)->getClientOriginalExtension());
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    return back()->withErrors([$field => 'Gambar harus berformat jpg, jpeg, png, atau webp.'])->withInput();
                }
                if ($request->file($field)->getSize() > 2048 * 1024) {
                    return back()->withErrors([$field => 'Ukuran gambar maksimal 2MB.'])->withInput();
                }
            }
        }

        $validated = $request->only(['nama', 'merek', 'model', 'ip_address', 'deskripsi', 'spesifikasi', 'is_active']);
        $validated['is_active'] = $request->boolean('is_active');

        foreach (['image_front' => 'image_path_front', 'image_back' => 'image_path_back'] as $inputKey => $dbColumn) {
            if ($request->hasFile($inputKey)) {
                if ($modem->$dbColumn) {
                    $oldPath = storage_path('app/public/' . $modem->$dbColumn);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $file     = $request->file($inputKey);
                $filename = uniqid('modem_') . '.' . strtolower($file->getClientOriginalExtension());
                $destPath = storage_path('app/public/modems');
                if (!file_exists($destPath)) {
                    mkdir($destPath, 0777, true);
                }
                $file->move($destPath, $filename);
                $validated[$dbColumn] = 'modems/' . $filename;
            }
        }

        unset($validated['image_front'], $validated['image_back']);
        $modem->update($validated);
        Cache::forget('modem_mereks');

        return redirect()->route('modem.admin.index')->with('success', 'Modem berhasil diperbarui.');
    }

    public function destroy(Modem $modem)
    {
        foreach (['image_path_front', 'image_path_back'] as $dbColumn) {
            if ($modem->$dbColumn) {
                $oldPath = storage_path('app/public/' . $modem->$dbColumn);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
        }

        $modem->delete();
        Cache::forget('modem_mereks');

        return redirect()->route('modem.admin.index')->with('success', 'Modem berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\OdcOdp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OdcOdpController extends Controller
{
    public function index()
    {
        $data = OdcOdp::with('parent')->latest()->get();
        return view('content.odc_odp.index', compact('data'));
    }

    public function create()
    {
        $odcs = OdcOdp::where('tipe', 'ODC')->get();
        return view('content.odc_odp.create', compact('odcs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:ODC,ODP',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:odc_odp,id'
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return back()->withErrors(['foto' => 'Foto harus berupa gambar (jpg, jpeg, png, gif, webp).'])->withInput();
            }
            if ($file->getSize() > 2048 * 1024) {
                return back()->withErrors(['foto' => 'Foto tidak boleh lebih dari 2MB.'])->withInput();
            }
        }

        $data = $request->all();

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/odc_odp');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $file->move($destinationPath, $filename);
            $data['foto'] = '/storage/odc_odp/' . $filename;
        }

        OdcOdp::create($data);

        return redirect()->route('odc-odp.index')->with('success', 'Titik Jaringan berhasil ditambahkan.');
    }

    public function edit(OdcOdp $odcOdp)
    {
        $odcs = OdcOdp::where('tipe', 'ODC')->get();
        return view('content.odc_odp.edit', compact('odcOdp', 'odcs'));
    }

    public function update(Request $request, OdcOdp $odcOdp)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:ODC,ODP',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:odc_odp,id'
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return back()->withErrors(['foto' => 'Foto harus berupa gambar (jpg, jpeg, png, gif, webp).'])->withInput();
            }
            if ($file->getSize() > 2048 * 1024) {
                return back()->withErrors(['foto' => 'Foto tidak boleh lebih dari 2MB.'])->withInput();
            }
        }

        $data = $request->all();

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($odcOdp->foto) {
                $oldPath = storage_path('app/public/' . str_replace('/storage/', '', $odcOdp->foto));
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            $file = $request->file('foto');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/odc_odp');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $file->move($destinationPath, $filename);
            $data['foto'] = '/storage/odc_odp/' . $filename;
        }

        $odcOdp->update($data);

        return redirect()->route('odc-odp.index')->with('success', 'Titik Jaringan berhasil diperbarui.');
    }

    public function destroy(OdcOdp $odcOdp)
    {
        if ($odcOdp->foto) {
            $oldPath = storage_path('app/public/' . str_replace('/storage/', '', $odcOdp->foto));
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        $odcOdp->delete();

        return redirect()->route('odc-odp.index')->with('success', 'Titik Jaringan berhasil dihapus.');
    }
}

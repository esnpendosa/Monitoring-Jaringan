<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryLog;
use App\Models\Teknisi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with(['technician', 'user']);

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_beli', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_beli', '<=', $request->tanggal_selesai);
        }

        $items = $query->get();
        $technicians = Teknisi::all();
        
        // Filter users: Admin (1), Manager (2?), Teknisi (3?) 
        // Let's assume names for better reliability if IDs differ
        $users = User::whereHas('role', function($q) {
            $q->whereIn('name', ['Admin', 'Manager', 'Teknisi', 'Manajer']);
        })->get();

        return view('content.inventory.index', compact('items', 'technicians', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_alat' => 'required',
            'kategori' => 'nullable',
            'merk' => 'nullable',
            'serial_number' => 'nullable|unique:inventory_items,serial_number',
            'stok' => 'nullable|integer|min:0',
            'harga_beli' => 'nullable|numeric|min:0',
            'tanggal_beli' => 'nullable|date',
        ]);

        if ($request->hasFile('gambar_alat')) {
            $file = $request->file('gambar_alat');
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return back()->withErrors(['gambar_alat' => 'Gambar alat harus berupa gambar (jpg, jpeg, png, gif, webp).'])->withInput();
            }
            if ($file->getSize() > 2048 * 1024) {
                return back()->withErrors(['gambar_alat' => 'Gambar alat tidak boleh lebih dari 2MB.'])->withInput();
            }
        }

        $data = $request->all();

        if ($request->hasFile('gambar_alat')) {
            $file = $request->file('gambar_alat');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/inventory');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $file->move($destinationPath, $filename);
            $data['gambar_alat'] = 'inventory/' . $filename;
        } elseif ($request->captured_image) {
            // Handle base64 image from camera
            $imageData = $request->captured_image;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $filename = uniqid() . '.png';
            $destinationPath = storage_path('app/public/inventory');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            file_put_contents($destinationPath . '/' . $filename, base64_decode($imageData));
            $data['gambar_alat'] = 'inventory/' . $filename;
        }

        $item = InventoryItem::create($data);

        InventoryLog::create([
            'id_inventory' => $item->id_inventory,
            'aksi' => 'tambah',
            'id_user_executor' => Auth::id() ?? 1,
            'keterangan' => 'Alat baru ditambahkan ke sistem dengan stok ' . ($item->stok ?? 1)
        ]);

        return redirect()->route('inventory.index')->with('success', 'Alat berhasil ditambahkan');
    }

    public function update(Request $request, InventoryItem $inventory)
    {
        $request->validate([
            'nama_alat' => 'required',
            'kategori' => 'nullable',
            'merk' => 'nullable',
            'stok' => 'nullable|integer|min:0',
            'kondisi' => 'required',
            'harga_beli' => 'nullable|numeric|min:0',
            'tanggal_beli' => 'nullable|date',
        ]);

        if ($request->hasFile('gambar_alat')) {
            $file = $request->file('gambar_alat');
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return back()->withErrors(['gambar_alat' => 'Gambar alat harus berupa gambar (jpg, jpeg, png, gif, webp).'])->withInput();
            }
            if ($file->getSize() > 2048 * 1024) {
                return back()->withErrors(['gambar_alat' => 'Gambar alat tidak boleh lebih dari 2MB.'])->withInput();
            }
        }

        $data = $request->all();

        if ($request->hasFile('gambar_alat')) {
            // Delete old image if exists
            if ($inventory->gambar_alat) {
                $oldPath = storage_path('app/public/' . $inventory->gambar_alat);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $file = $request->file('gambar_alat');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/inventory');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $file->move($destinationPath, $filename);
            $data['gambar_alat'] = 'inventory/' . $filename;
        } elseif ($request->captured_image) {
            // Delete old image
            if ($inventory->gambar_alat) {
                $oldPath = storage_path('app/public/' . $inventory->gambar_alat);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $imageData = $request->captured_image;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $filename = uniqid() . '.png';
            $destinationPath = storage_path('app/public/inventory');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            file_put_contents($destinationPath . '/' . $filename, base64_decode($imageData));
            $data['gambar_alat'] = 'inventory/' . $filename;
        }

        $inventory->update($data);

        InventoryLog::create([
            'id_inventory' => $inventory->id_inventory,
            'aksi' => 'update',
            'id_user_executor' => Auth::id() ?? 1,
            'keterangan' => 'Data alat diperbarui'
        ]);

        return redirect()->route('inventory.index')->with('success', 'Data alat berhasil diperbarui');
    }

    public function assign(Request $request, InventoryItem $inventory)
    {
        $request->validate([
            'id_teknisi' => 'nullable|exists:teknisi,id_teknisi',
            'id_user' => 'nullable|exists:users,id',
        ]);

        $inventory->update([
            'id_teknisi' => $request->id_teknisi,
            'id_user' => $request->id_user,
            'status' => ($request->id_teknisi || $request->id_user) ? 'digunakan' : 'tersedia'
        ]);

        $penerima = $request->id_teknisi ? 'Teknisi: ' . Teknisi::find($request->id_teknisi)->nama_teknisi : ($request->id_user ? 'User: ' . User::find($request->id_user)->name : 'Dikembalikan ke gudang');

        InventoryLog::create([
            'id_inventory' => $inventory->id_inventory,
            'aksi' => 'assign',
            'id_user_executor' => Auth::id() ?? 1,
            'keterangan' => 'Alat dialokasikan ke ' . $penerima
        ]);

        return redirect()->route('inventory.index')->with('success', 'Alokasi alat berhasil diperbarui');
    }

    public function destroy(InventoryItem $inventory)
    {
        $inventory->delete();
        return redirect()->route('inventory.index')->with('success', 'Alat berhasil dihapus');
    }

    public function show(InventoryItem $inventory)
    {
        $inventory->load(['technician', 'user.role', 'logs.executor']);
        $technicians = Teknisi::all();
        $users = User::whereHas('role', function($q) {
            $q->whereIn('name', ['Admin', 'Manager', 'Teknisi', 'Manajer']);
        })->get();
        return view('content.inventory.show', compact('inventory', 'technicians', 'users'));
    }
}

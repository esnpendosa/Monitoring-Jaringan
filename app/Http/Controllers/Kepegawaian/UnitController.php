<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::orderBy('nama', 'asc')->get();
        return view('kepegawaian.unit', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|unique:units,nama',
            'keterangan' => 'nullable|string',
        ]);

        Unit::create($request->all());

        return redirect()->back()->with('success', 'Unit baru berhasil ditambahkan.');
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'nama' => 'required|string|unique:units,nama,' . $unit->id,
            'keterangan' => 'nullable|string',
        ]);

        $unit->update($request->all());

        return redirect()->back()->with('success', 'Data unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return redirect()->back()->with('success', 'Unit berhasil dihapus.');
    }
}

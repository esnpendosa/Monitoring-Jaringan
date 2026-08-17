<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use Illuminate\Http\Request;

class OltController extends Controller
{
    public function index()
    {
        $olts = Olt::withCount(['odcList'])->latest()->get();
        return view('content.olt.index', compact('olts'));
    }

    public function create()
    {
        return view('content.olt.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'           => 'required|string|max:255',
            'ip_address'     => 'nullable|ip',
            'snmp_community' => 'nullable|string|max:100',
            'kapasitas_pon'  => 'nullable|integer|min:1',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'lokasi'         => 'nullable|string|max:500',
            'deskripsi'      => 'nullable|string',
        ]);

        Olt::create($request->all());

        return redirect()->route('olt.index')->with('success', 'OLT berhasil ditambahkan.');
    }

    public function edit(Olt $olt)
    {
        return view('content.olt.edit', compact('olt'));
    }

    public function update(Request $request, Olt $olt)
    {
        $request->validate([
            'nama'           => 'required|string|max:255',
            'ip_address'     => 'nullable|ip',
            'snmp_community' => 'nullable|string|max:100',
            'kapasitas_pon'  => 'nullable|integer|min:1',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'lokasi'         => 'nullable|string|max:500',
            'deskripsi'      => 'nullable|string',
        ]);

        $olt->update($request->all());

        return redirect()->route('olt.index')->with('success', 'OLT berhasil diperbarui.');
    }

    public function show(Olt $olt)
    {
        $olt->load(['odcList.children.pelanggan', 'kabels']);
        return view('content.olt.show', compact('olt'));
    }

    public function destroy(Olt $olt)
    {
        $olt->delete();
        return redirect()->route('olt.index')->with('success', 'OLT berhasil dihapus.');
    }
}

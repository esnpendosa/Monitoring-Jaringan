<?php

namespace App\Http\Controllers;

use App\Models\Kabel;
use App\Models\Olt;
use App\Models\OdcOdp;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KabelController extends Controller
{
    public function index()
    {
        $kabels = Kabel::latest()->get();
        return view('content.kabel.index', compact('kabels'));
    }

    public function create()
    {
        $olts      = Olt::all();
        $odcs      = OdcOdp::where('tipe', 'ODC')->get();
        $odps      = OdcOdp::where('tipe', 'ODP')->get();
        $pelanggan = Pelanggan::select('id_pelanggan', 'nama_pelanggan', 'kode_pelanggan')->get();
        return view('content.kabel.create', compact('olts', 'odcs', 'odps', 'pelanggan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label'           => 'required|string|max:255',
            'tipe'            => 'required|in:feeder,distribusi,drop',
            'monitoring_type' => 'required|in:realtime,manual',
            'from_type'       => 'required|in:olt,odc,odp',
            'from_id'         => 'required|integer',
            'to_type'         => 'required|in:odc,odp,pelanggan',
            'to_id'           => 'required|integer',
            'geometry'        => 'required|json',
            'jumlah_core'     => 'nullable|integer|min:1',
            'catatan'         => 'nullable|string',
        ]);

        $data = $request->except('_token');
        $data['geometry']   = json_decode($request->geometry, true);
        $data['updated_by'] = Auth::user()->name ?? 'system';

        Kabel::create($data);

        return redirect()->route('kabel.index')->with('success', 'Kabel berhasil ditambahkan.');
    }

    public function edit(Kabel $kabel)
    {
        $olts      = Olt::all();
        $odcs      = OdcOdp::where('tipe', 'ODC')->get();
        $odps      = OdcOdp::where('tipe', 'ODP')->get();
        $pelanggan = Pelanggan::select('id_pelanggan', 'nama_pelanggan', 'kode_pelanggan')->get();
        return view('content.kabel.edit', compact('kabel', 'olts', 'odcs', 'odps', 'pelanggan'));
    }

    public function update(Request $request, Kabel $kabel)
    {
        $request->validate([
            'label'           => 'required|string|max:255',
            'tipe'            => 'required|in:feeder,distribusi,drop',
            'monitoring_type' => 'required|in:realtime,manual',
            'jumlah_core'     => 'nullable|integer|min:1',
            'catatan'         => 'nullable|string',
        ]);

        $data = $request->only(['label', 'tipe', 'monitoring_type', 'jumlah_core', 'catatan', 'status']);
        $data['updated_by'] = Auth::user()->name ?? 'system';

        if ($request->filled('geometry')) {
            $data['geometry'] = json_decode($request->geometry, true);
        }

        $kabel->update($data);

        return redirect()->route('kabel.index')->with('success', 'Kabel berhasil diperbarui.');
    }

    public function show(Kabel $kabel)
    {
        $kabel->load(['rftsReadings' => fn($q) => $q->latest('waktu_baca')->limit(10)]);
        return view('content.kabel.show', compact('kabel'));
    }

    public function destroy(Kabel $kabel)
    {
        $kabel->delete();
        return redirect()->route('kabel.index')->with('success', 'Kabel berhasil dihapus.');
    }

    /**
     * Update status kabel secara manual (oleh teknisi)
     */
    public function updateStatus(Request $request, Kabel $kabel)
    {
        $request->validate([
            'status'           => 'required|in:online,warning,offline',
            'catatan'          => 'nullable|string',
            'titik_putus_meter' => 'nullable|numeric',
        ]);

        $kabel->update([
            'status'            => $request->status,
            'catatan'           => $request->catatan ?? $kabel->catatan,
            'titik_putus_meter' => $request->titik_putus_meter,
            'updated_by'        => Auth::user()->name ?? 'teknisi',
        ]);

        return response()->json(['success' => true, 'status' => $kabel->status]);
    }

    /**
     * API endpoint — kembalikan semua kabel dalam format GeoJSON
     * untuk dikonsumsi Leaflet.js di peta
     */
    public function geojson()
    {
        $kabels = Kabel::all();

        $features = $kabels->map(function (Kabel $k) {
            // geometry: [[lat,lng],...] → GeoJSON perlu [lng,lat]
            $coordinates = array_map(fn($point) => [$point[1], $point[0]], $k->geometry ?? []);

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type'        => 'LineString',
                    'coordinates' => $coordinates,
                ],
                'properties' => [
                    'id'               => $k->id,
                    'label'            => $k->label,
                    'tipe'             => $k->tipe,
                    'tipe_label'       => $k->tipe_label,
                    'monitoring_type'  => $k->monitoring_type,
                    'jumlah_core'      => $k->jumlah_core,
                    'status'           => $k->status ?? 'online',
                    'color'            => $k->polyline_color,
                    'redaman_db'       => $k->redaman_db,
                    'titik_putus_meter' => $k->titik_putus_meter,
                    'catatan'          => $k->catatan,
                    'updated_by'       => $k->updated_by,
                    'updated_at'       => $k->updated_at?->format('d/m/Y H:i'),
                ],
            ];
        });

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}

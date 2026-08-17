<?php

namespace App\Http\Controllers;

use App\Models\FtthItem;
use App\Models\Kabel;
use App\Models\Olt;
use App\Models\OdcOdp;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FtthMapController extends Controller
{
    /** Peta topologi lama (tetap ada) */
    public function index()
    {
        $olts      = Olt::all();
        $odcs      = OdcOdp::where('tipe', 'ODC')->with(['olt', 'children'])->get();
        $odps      = OdcOdp::where('tipe', 'ODP')->with(['parent', 'pelanggan'])->get();
        $pelanggan = Pelanggan::whereNotNull('latitude')->whereNotNull('longitude')->get();
        $kabelCount = Kabel::count();
        return view('content.ftth.map', compact('olts', 'odcs', 'odps', 'pelanggan', 'kabelCount'));
    }

    /** Dashboard FTTH Unified — halaman baru satu layar */
    public function dashboard()
    {
        return view('content.ftth.dashboard');
    }

    // ═══════════════════════════════════════════════
    //  API ENDPOINTS (JSON) — dikonsumsi JS
    // ═══════════════════════════════════════════════

    /** Ambil semua node + kabel sekaligus */
    public function apiNodes()
    {
        $olts = Olt::all()->map(fn($o) => [
            'type' => 'olt', 'id' => $o->id,
            'nama' => $o->nama, 'lat' => (float)$o->latitude, 'lng' => (float)$o->longitude,
            'status' => $o->status ?? 'online', 'ip_address' => $o->ip_address,
            'kapasitas_pon' => $o->kapasitas_pon, 'lokasi' => $o->lokasi,
        ]);

        $odcOdps = OdcOdp::with(['olt','parent'])->get()->map(fn($o) => [
            'type' => strtolower($o->tipe), 'id' => $o->id,
            'nama' => $o->nama, 'lat' => (float)$o->latitude, 'lng' => (float)$o->longitude,
            'status' => $o->status ?? 'online',
            'tipe' => $o->tipe, 'parent_id' => $o->parent_id, 'olt_id' => $o->olt_id,
            'kapasitas_core' => $o->kapasitas_core, 'kapasitas_port' => $o->kapasitas_port,
            'deskripsi' => $o->deskripsi,
        ]);

        $pelanggan = Pelanggan::whereNotNull('latitude')->whereNotNull('longitude')
            ->with('odp')
            ->get()->map(fn($p) => [
                'type'             => 'pelanggan',
                'id'               => $p->id_pelanggan,
                'nama'             => $p->nama_pelanggan,
                'lat'              => (float)$p->latitude,
                'lng'              => (float)$p->longitude,
                'status'           => ($p->last_online_status == 1 || $p->last_online_status === 'online' || $p->last_online_status === null) ? 'online' : 'offline',
                'kode'             => $p->kode_pelanggan,
                'ip_address'       => $p->ip_address,
                'serial_ont'       => $p->serial_ont,
                'onu_rx_power'     => $p->onu_rx_power,
                'onu_rx_baseline'  => $p->baseline_rx_power ?: -19.5,
                'last_inform_at'   => $p->last_inform_at ? $p->last_inform_at->toIso8601String() : now()->toIso8601String(),
                'odp_id'           => $p->odp_id,
                'alamat'           => $p->alamat,
            ]);

        $kabels = Kabel::all()->map(fn($k) => [
            'id' => $k->id, 'label' => $k->label,
            'tipe' => $k->tipe, 'tipe_label' => $k->tipe_label,
            'monitoring_type' => $k->monitoring_type,
            'from_type' => $k->from_type, 'from_id' => $k->from_id,
            'to_type' => $k->to_type, 'to_id' => $k->to_id,
            'geometry' => $k->geometry,
            'jumlah_core' => $k->jumlah_core, 'status' => $k->status ?? 'online',
            'color' => $k->polyline_color,
            'redaman_db' => $k->redaman_db, 'titik_putus_meter' => $k->titik_putus_meter,
            'catatan' => $k->catatan, 'updated_by' => $k->updated_by,
            'updated_at' => $k->updated_at?->format('d/m/Y H:i'),
        ]);

        // FTTH Items baru (Tiang Tumpu, Tiang ODP, Tiang ODC, Joint Closure, HTB, AP, Server)
        $ftthItems = FtthItem::withCoords()->get()->map(fn($i) => [
            'type'           => 'ftth_item',
            'id'             => $i->id,
            'kategori'       => $i->kategori,
            'kategori_label' => $i->kategori_label,
            'kategori_emoji' => $i->kategori_emoji,
            'kategori_color' => $i->kategori_color,
            'nama'           => $i->nama,
            'kode'           => $i->kode,
            'lat'            => (float)$i->latitude,
            'lng'            => (float)$i->longitude,
            'status'         => $i->status ?? 'online',
            'status_color'   => $i->status_color,
            'merk'           => $i->merk,
            'model'          => $i->model,
            'serial_number'  => $i->serial_number,
            'ip_address'     => $i->ip_address,
            'tanggal_pasang' => $i->tanggal_pasang?->format('d/m/Y'),
            'deskripsi'      => $i->deskripsi,
            'tinggi_tiang'   => $i->tinggi_tiang,
            'material_tiang' => $i->material_tiang,
            'kapasitas_core' => $i->kapasitas_core,
            'kapasitas_port' => $i->kapasitas_port,
            'frekuensi_ghz'  => $i->frekuensi_ghz,
            'daya_watt'      => $i->daya_watt,
            'gain_dbi'       => $i->gain_dbi,
            'olt_id'         => $i->olt_id,
            'updated_by'     => $i->updated_by,
            'updated_at'     => $i->updated_at?->format('d/m/Y H:i'),
        ]);

        return response()->json([
            'olts'       => $olts,
            'odcOdps'    => $odcOdps,
            'pelanggan'  => $pelanggan,
            'kabels'     => $kabels,
            'ftthItems'  => $ftthItems,
            'kabels'    => $kabels,
        ]);
    }

    /** Simpan OLT baru */
    public function storeOlt(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'latitude' => 'required|numeric', 'longitude' => 'required|numeric',
            'ip_address' => 'nullable|string', 'snmp_community' => 'nullable|string',
            'kapasitas_pon' => 'nullable|integer', 'lokasi' => 'nullable|string',
        ]);
        $olt = Olt::create($data);
        return response()->json(['success' => true, 'node' => [
            'type' => 'olt', 'id' => $olt->id, 'nama' => $olt->nama,
            'lat' => (float)$olt->latitude, 'lng' => (float)$olt->longitude,
            'status' => $olt->status, 'ip_address' => $olt->ip_address,
            'kapasitas_pon' => $olt->kapasitas_pon,
        ]]);
    }

    /** Simpan node ODC/ODP/ONT baru */
    public function storeNode(Request $request)
    {
        $tipe = strtoupper($request->input('tipe', ''));
        if ($tipe === 'ONT' || $tipe === 'PELANGGAN' || $request->input('type') === 'ont') {
            $data = $request->validate([
                'nama'              => 'required|string|max:255',
                'latitude'          => 'required|numeric',
                'longitude'         => 'required|numeric',
                'odp_id'            => 'nullable|integer',
                'serial_ont'        => 'nullable|string',
                'ip_address'        => 'nullable|string',
                'no_wa'             => 'nullable|string',
                'mikrotik_username' => 'nullable|string',
                'alamat'            => 'nullable|string',
            ]);

            $pelanggan = Pelanggan::create([
                'nama_pelanggan'     => $data['nama'],
                'kode_pelanggan'     => 'ONT-' . strtoupper(substr(uniqid(), -5)),
                'alamat'             => $data['alamat'] ?? 'Lokasi Terdaftar FTTH Map',
                'latitude'           => $data['latitude'],
                'longitude'          => $data['longitude'],
                'odp_id'             => $data['odp_id'] ?? null,
                'serial_ont'         => $data['serial_ont'] ?? '48575443A3F1A89D',
                'ip_address'         => $data['ip_address'] ?? '192.168.88.253',
                'no_wa'              => $data['no_wa'] ?? null,
                'mikrotik_username'  => $data['mikrotik_username'] ?? null,
                'last_online_status' => 1,
                'is_active'          => true,
            ]);

            return response()->json(['success' => true, 'node' => [
                'type' => 'pelanggan',
                'id'   => $pelanggan->id_pelanggan,
                'nama' => $pelanggan->nama_pelanggan,
                'kode' => $pelanggan->kode_pelanggan,
                'lat'  => (float)$pelanggan->latitude,
                'lng'  => (float)$pelanggan->longitude,
                'status' => 'online',
            ]]);
        }

        $data = $request->validate([
            'tipe' => 'required|in:ODC,ODP', 'nama' => 'required|string|max:255',
            'latitude' => 'required|numeric', 'longitude' => 'required|numeric',
            'olt_id' => 'nullable|integer', 'parent_id' => 'nullable|integer',
            'kapasitas_core' => 'nullable|integer', 'kapasitas_port' => 'nullable|integer',
            'deskripsi' => 'nullable|string',
        ]);

        // Validate foreign key existence to prevent constraint violation
        if (!empty($data['parent_id']) && !OdcOdp::where('id', $data['parent_id'])->exists()) {
            $data['parent_id'] = null;
        }
        if (!empty($data['olt_id']) && !Olt::where('id', $data['olt_id'])->exists()) {
            $data['olt_id'] = null;
        }

        $node = OdcOdp::create($data);
        return response()->json(['success' => true, 'node' => [
            'type' => strtolower($node->tipe), 'id' => $node->id,
            'nama' => $node->nama, 'lat' => (float)$node->latitude, 'lng' => (float)$node->longitude,
            'status' => 'online', 'tipe' => $node->tipe,
            'parent_id' => $node->parent_id, 'olt_id' => $node->olt_id,
        ]]);
    }

    /** Update node OLT, ODC/ODP, atau ONT/Pelanggan */
    public function updateNode(Request $request, $id)
    {
        $type = $request->input('type', 'odc');
        if ($type === 'olt') {
            $node = Olt::findOrFail($id);
            $node->update($request->only(['nama','ip_address','snmp_community','kapasitas_pon','lokasi','status','latitude','longitude']));
        } elseif ($type === 'ont' || $type === 'pelanggan') {
            $node = Pelanggan::findOrFail($id);
            $update = [];
            if ($request->has('nama')) $update['nama_pelanggan'] = $request->input('nama');
            if ($request->has('latitude')) $update['latitude'] = $request->input('latitude');
            if ($request->has('longitude')) $update['longitude'] = $request->input('longitude');
            if ($request->has('ip_address')) $update['ip_address'] = $request->input('ip_address');
            if ($request->has('serial_ont')) $update['serial_ont'] = $request->input('serial_ont');
            if ($request->has('no_wa')) $update['no_wa'] = $request->input('no_wa');
            if ($request->has('odp_id')) $update['odp_id'] = $request->input('odp_id');
            if ($request->has('deskripsi')) $update['alamat'] = $request->input('deskripsi');
            if ($request->has('catatan')) $update['alamat'] = $request->input('catatan');
            if ($request->has('status')) {
                $st = $request->input('status');
                $update['last_online_status'] = ($st === 'online' || $st === '1' || $st === 1) ? 1 : 0;
            }
            $node->update($update);
        } else {
            $node = OdcOdp::findOrFail($id);
            $node->update($request->only(['nama','status','kapasitas_core','kapasitas_port','deskripsi','latitude','longitude','olt_id','parent_id']));
        }
        return response()->json(['success' => true]);
    }

    /** Hapus node */
    public function deleteNode(Request $request, $type, $id)
    {
        if ($type === 'olt') Olt::findOrFail($id)->delete();
        elseif ($type === 'ont' || $type === 'pelanggan') Pelanggan::findOrFail($id)->delete();
        else OdcOdp::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    /** Simpan kabel baru via API */
    public function storeKabel(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string', 'tipe' => 'required|in:feeder,distribusi,drop',
            'monitoring_type' => 'required|in:realtime,manual',
            'from_type' => 'required|string', 'from_id' => 'required|integer',
            'to_type' => 'required|string', 'to_id' => 'required|integer',
            'geometry' => 'required|array', 'jumlah_core' => 'nullable|integer',
            'catatan' => 'nullable|string',
        ]);
        $data['updated_by'] = Auth::user()->name ?? 'system';
        $kabel = Kabel::create($data);
        return response()->json(['success' => true, 'kabel' => [
            'id' => $kabel->id, 'label' => $kabel->label,
            'tipe' => $kabel->tipe, 'status' => $kabel->status,
            'color' => $kabel->polyline_color, 'geometry' => $kabel->geometry,
            'monitoring_type' => $kabel->monitoring_type,
            'jumlah_core' => $kabel->jumlah_core, 'catatan' => $kabel->catatan,
        ]]);
    }

    /** Update kabel via API */
    public function updateKabel(Request $request, Kabel $kabel)
    {
        $kabel->update(array_merge(
            $request->only(['label','tipe','monitoring_type','jumlah_core','status','catatan','titik_putus_meter']),
            ['updated_by' => Auth::user()->name ?? 'system']
        ));
        return response()->json(['success' => true, 'color' => $kabel->polyline_color]);
    }

    /** Hapus kabel via API */
    public function deleteKabel(Kabel $kabel)
    {
        $kabel->delete();
        return response()->json(['success' => true]);
    }
}

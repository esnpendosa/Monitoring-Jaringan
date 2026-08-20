<?php

namespace App\Http\Controllers;

use App\Models\FtthItem;
use App\Models\Olt;
use App\Models\OdcOdp;
use App\Models\Kabel;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FtthItemController extends Controller
{
    // ═══════════════════════════════════════════
    //  INDEX (list semua item by kategori)
    // ═══════════════════════════════════════════
    public function index(Request $request)
    {
        $kategori = $request->input('kategori');
        $query = FtthItem::query()->latest();

        if ($kategori && array_key_exists($kategori, FtthItem::KATEGORI_LABELS)) {
            $query->where('kategori', $kategori);
        }

        $items    = $query->paginate(20)->withQueryString();
        $kategori = $kategori ?? 'all';

        return view('content.ftth.items', compact('items', 'kategori'));
    }

    // ═══════════════════════════════════════════
    //  STORE
    // ═══════════════════════════════════════════
    public function store(Request $request)
    {
        if ($request->filled('id')) {
            $existingItem = FtthItem::find($request->id);
            if ($existingItem) {
                return $this->update($request, $existingItem);
            }
        }

        $data = $request->validate([
            'kategori'       => 'required|in:' . implode(',', array_keys(FtthItem::KATEGORI_LABELS)),
            'nama'           => 'required|string|max:255',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'status'         => 'nullable|in:online,warning,offline',
            'merk'           => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'serial_number'  => 'nullable|string|max:100',
            'ip_address'     => 'nullable|string|max:45',
            'tanggal_pasang' => 'nullable|date',
            'deskripsi'      => 'nullable|string',
            'tinggi_tiang'   => 'nullable|numeric',
            'material_tiang' => 'nullable|in:beton,besi,kayu,galvanis',
            'kapasitas_core' => 'nullable|integer',
            'kapasitas_port' => 'nullable|integer',
            'snmp_community' => 'nullable|string|max:100',
            'frekuensi_ghz'  => 'nullable|numeric',
            'daya_watt'      => 'nullable|numeric',
            'gain_dbi'       => 'nullable|numeric',
            'parent_id'      => 'nullable|integer',
            'olt_id'         => 'nullable|integer|exists:olts,id',
        ]);

        $data['kode']       = FtthItem::generateKode($data['kategori']);
        $data['status']     = $data['status'] ?? 'online';
        $data['updated_by'] = Auth::user()->name ?? 'system';

        if ($request->filled('parent_id')) {
            $data['parent_type'] = 'ftth_item';
        }

        $item = FtthItem::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'item' => $this->itemToMap($item)]);
        }

        return redirect()->back()->with('success', "{$item->kategori_label} '{$item->nama}' berhasil ditambahkan.");
    }

    // ═══════════════════════════════════════════
    //  UPDATE
    // ═══════════════════════════════════════════
    public function update(Request $request, FtthItem $ftthItem)
    {
        $data = $request->validate([
            'kategori'       => 'sometimes|required|in:' . implode(',', array_keys(FtthItem::KATEGORI_LABELS)),
            'nama'           => 'sometimes|required|string|max:255',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'status'         => 'nullable|in:online,warning,offline',
            'merk'           => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'serial_number'  => 'nullable|string|max:100',
            'ip_address'     => 'nullable|string|max:45',
            'tanggal_pasang' => 'nullable|date',
            'deskripsi'      => 'nullable|string',
            'tinggi_tiang'   => 'nullable|numeric',
            'material_tiang' => 'nullable|in:beton,besi,kayu,galvanis',
            'kapasitas_core' => 'nullable|integer',
            'kapasitas_port' => 'nullable|integer',
            'frekuensi_ghz'  => 'nullable|numeric',
            'daya_watt'      => 'nullable|numeric',
            'gain_dbi'       => 'nullable|numeric',
            'olt_id'         => 'nullable|integer|exists:olts,id',
        ]);

        $data['updated_by'] = Auth::user()->name ?? 'system';
        $ftthItem->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'item' => $this->itemToMap($ftthItem->fresh())]);
        }

        return redirect()->back()->with('success', 'Item berhasil diperbarui.');
    }

    // ═══════════════════════════════════════════
    //  DESTROY
    // ═══════════════════════════════════════════
    public function destroy(FtthItem $ftthItem)
    {
        $ftthItem->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Item berhasil dihapus.');
    }

    // ═══════════════════════════════════════════
    //  API: Semua FTTH Items untuk peta
    // ═══════════════════════════════════════════
    public function apiItems(Request $request)
    {
        $items = FtthItem::withCoords()->get()->map(fn($i) => $this->itemToMap($i));
        return response()->json($items);
    }

    // ═══════════════════════════════════════════
    //  API: Auto-generate Tiang Tumpu
    //  Otomatis buat tiang tumpu sepanjang jalur kabel
    // ═══════════════════════════════════════════
    public function autoGenerateTiang(Request $request)
    {
        $request->validate([
            'kabel_id'    => 'required|integer|exists:kabels,id',
            'jarak_meter' => 'required|numeric|min:10|max:500',
        ]);

        $kabel   = Kabel::findOrFail($request->kabel_id);
        $geometry = $kabel->geometry ?? [];

        if (count($geometry) < 2) {
            return response()->json(['success' => false, 'message' => 'Kabel perlu minimal 2 titik koordinat.'], 422);
        }

        $jarakMeter = (float) $request->jarak_meter;
        $generated  = [];
        $created    = 0;

        // Iterasi tiap segmen, letakkan tiang setiap $jarakMeter meter
        for ($i = 0; $i < count($geometry) - 1; $i++) {
            $from = $geometry[$i];    // [lat, lng]
            $to   = $geometry[$i + 1];

            $segmentDist = $this->haversineMeters($from[0], $from[1], $to[0], $to[1]);
            $numPoles    = max(0, (int) floor($segmentDist / $jarakMeter) - 1);

            for ($j = 1; $j <= $numPoles; $j++) {
                $fraction = ($j * $jarakMeter) / $segmentDist;
                $lat      = $from[0] + ($to[0] - $from[0]) * $fraction;
                $lng      = $from[1] + ($to[1] - $from[1]) * $fraction;

                $item = FtthItem::create([
                    'kategori'   => 'tiang_tumpu',
                    'nama'       => 'Tiang Tumpu ' . FtthItem::generateKode('tiang_tumpu'),
                    'kode'       => FtthItem::generateKode('tiang_tumpu'),
                    'latitude'   => round($lat, 8),
                    'longitude'  => round($lng, 8),
                    'status'     => 'online',
                    'updated_by' => Auth::user()->name ?? 'system',
                ]);

                $generated[] = $this->itemToMap($item);
                $created++;
            }
        }

        return response()->json([
            'success'   => true,
            'created'   => $created,
            'message'   => "{$created} tiang tumpu berhasil digenerate.",
            'items'     => $generated,
        ]);
    }

    // ═══════════════════════════════════════════
    //  EXPORT KMZ (Google Earth)
    // ═══════════════════════════════════════════
    public function exportKmz(Request $request)
    {
        // Ambil semua data untuk KMZ
        $olts      = Olt::all();
        $odcOdps   = OdcOdp::all();
        $kabels    = Kabel::all();
        $pelanggan = Pelanggan::whereNotNull('latitude')->whereNotNull('longitude')->get();
        $items     = FtthItem::withCoords()->get();

        $kml = $this->buildKml($olts, $odcOdps, $kabels, $pelanggan, $items);

        // KMZ = ZIP containing doc.kml
        $tmpDir  = sys_get_temp_dir();
        $kmlFile = $tmpDir . '/doc.kml';
        $kmzFile = $tmpDir . '/ftth_network_' . date('Ymd_His') . '.kmz';

        file_put_contents($kmlFile, $kml);

        $zip = new \ZipArchive();
        if ($zip->open($kmzFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['error' => 'Gagal membuat file KMZ'], 500);
        }
        $zip->addFile($kmlFile, 'doc.kml');
        $zip->close();

        return response()->download($kmzFile, 'FTTH_Network_' . date('Ymd') . '.kmz', [
            'Content-Type' => 'application/vnd.google-earth.kmz',
        ])->deleteFileAfterSend(true);
    }

    // ═══════════════════════════════════════════
    //  IMPORT KMZ
    // ═══════════════════════════════════════════
    public function importKmz(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:kmz,kml,zip|max:10240',
        ]);

        $file     = $request->file('file');
        $ext      = strtolower($file->getClientOriginalExtension());
        $tmpPath  = $file->getRealPath();
        $kmlContent = null;

        if ($ext === 'kmz' || $ext === 'zip') {
            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                return response()->json(['success' => false, 'message' => 'File KMZ tidak valid.'], 422);
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with(strtolower($name), '.kml')) {
                    $kmlContent = $zip->getFromIndex($i);
                    break;
                }
            }
            $zip->close();
        } else {
            $kmlContent = file_get_contents($tmpPath);
        }

        if (!$kmlContent) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan konten KML.'], 422);
        }

        $imported = $this->parseKmlAndImport($kmlContent);

        return response()->json([
            'success'  => true,
            'imported' => $imported,
            'message'  => "Berhasil import {$imported} item dari KMZ.",
        ]);
    }

    // ═══════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════
    private function itemToMap(FtthItem $item): array
    {
        return [
            'id'              => $item->id,
            'type'            => 'ftth_item',
            'kategori'        => $item->kategori,
            'kategori_label'  => $item->kategori_label,
            'kategori_emoji'  => $item->kategori_emoji,
            'kategori_color'  => $item->kategori_color,
            'nama'            => $item->nama,
            'kode'            => $item->kode,
            'lat'             => (float) ($item->latitude ?? 0),
            'lng'             => (float) ($item->longitude ?? 0),
            'status'          => $item->status ?? 'online',
            'status_color'    => $item->status_color,
            'merk'            => $item->merk,
            'model'           => $item->model,
            'serial_number'   => $item->serial_number,
            'ip_address'      => $item->ip_address,
            'tanggal_pasang'  => $item->tanggal_pasang?->format('d/m/Y'),
            'deskripsi'       => $item->deskripsi,
            'tinggi_tiang'    => $item->tinggi_tiang,
            'material_tiang'  => $item->material_tiang,
            'kapasitas_core'  => $item->kapasitas_core,
            'kapasitas_port'  => $item->kapasitas_port,
            'frekuensi_ghz'   => $item->frekuensi_ghz,
            'daya_watt'       => $item->daya_watt,
            'gain_dbi'        => $item->gain_dbi,
            'olt_id'          => $item->olt_id,
            'updated_by'      => $item->updated_by,
            'updated_at'      => $item->updated_at?->format('d/m/Y H:i'),
        ];
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000; // bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function buildKml($olts, $odcOdps, $kabels, $pelanggan, $items): string
    {
        $placemarks = '';

        // OLT
        foreach ($olts as $o) {
            if (!$o->latitude || !$o->longitude) continue;
            $placemarks .= $this->kmlPoint($o->nama, "OLT | {$o->ip_address} | Status: {$o->status}", $o->longitude, $o->latitude, 'OLT');
        }

        // ODC / ODP
        foreach ($odcOdps as $o) {
            if (!$o->latitude || !$o->longitude) continue;
            $placemarks .= $this->kmlPoint($o->nama, "{$o->tipe} | Status: " . ($o->status ?? 'online'), $o->longitude, $o->latitude, $o->tipe);
        }

        // Pelanggan
        foreach ($pelanggan as $p) {
            $placemarks .= $this->kmlPoint(
                $p->nama_pelanggan,
                "Pelanggan | {$p->kode_pelanggan} | IP: {$p->ip_address}",
                $p->longitude, $p->latitude, 'Pelanggan'
            );
        }

        // FTTH Items
        foreach ($items as $i) {
            $placemarks .= $this->kmlPoint($i->nama, "{$i->kategori_label} | {$i->kode} | Status: {$i->status}", $i->longitude, $i->latitude, $i->kategori_label);
        }

        // Kabel (LineString)
        foreach ($kabels as $k) {
            if (empty($k->geometry) || count($k->geometry) < 2) continue;
            $coords = implode(' ', array_map(fn($pt) => "{$pt[1]},{$pt[0]},0", $k->geometry));
            $color  = ltrim($k->polyline_color, '#');
            $placemarks .= "
            <Placemark>
              <name>{$k->label}</name>
              <description>{$k->tipe_label} | Cores: {$k->jumlah_core} | Status: {$k->status}</description>
              <Style>
                <LineStyle>
                  <color>ff" . substr($color, 4, 2) . substr($color, 2, 2) . substr($color, 0, 2) . "</color>
                  <width>" . match($k->tipe) { 'feeder' => 4, 'distribusi' => 3, default => 2 } . "</width>
                </LineStyle>
              </Style>
              <LineString><coordinates>{$coords}</coordinates></LineString>
            </Placemark>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>FTTH Network — ' . date('d/m/Y') . '</name>
    <description>Export dari Rozitech FTTH NMS</description>
    ' . $placemarks . '
  </Document>
</kml>';
    }

    private function kmlPoint(string $name, string $desc, $lng, $lat, string $folder): string
    {
        $name = htmlspecialchars($name, ENT_XML1);
        $desc = htmlspecialchars($desc, ENT_XML1);
        return "
        <Placemark>
          <name>{$name}</name>
          <description>{$desc}</description>
          <Point><coordinates>{$lng},{$lat},0</coordinates></Point>
        </Placemark>";
    }

    private function parseKmlAndImport(string $kml): int
    {
        $imported = 0;
        $xml = simplexml_load_string($kml);
        if (!$xml) return 0;

        $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
        $placemarks = $xml->xpath('//kml:Placemark') ?: $xml->xpath('//Placemark') ?: [];

        foreach ($placemarks as $pm) {
            $name   = (string) ($pm->name ?? '');
            $coords = (string) ($pm->Point->coordinates ?? '');
            if (!$coords) continue;

            $parts = explode(',', trim($coords));
            if (count($parts) < 2) continue;

            $lng = (float) $parts[0];
            $lat = (float) $parts[1];
            if ($lat == 0 && $lng == 0) continue;

            // Tebak kategori berdasarkan nama
            $desc     = strtolower((string) ($pm->description ?? '') . ' ' . $name);
            $kategori = 'tiang_tumpu'; // default
            if (str_contains($desc, 'olt'))           $kategori = 'server_router';
            elseif (str_contains($desc, 'odc'))       $kategori = 'tiang_odc';
            elseif (str_contains($desc, 'odp'))       $kategori = 'tiang_odp';
            elseif (str_contains($desc, 'joint') || str_contains($desc, 'closure')) $kategori = 'joint_closure';
            elseif (str_contains($desc, 'htb'))       $kategori = 'htb';
            elseif (str_contains($desc, 'ap') || str_contains($desc, 'access point')) $kategori = 'access_point';
            elseif (str_contains($desc, 'server') || str_contains($desc, 'router'))   $kategori = 'server_router';

            FtthItem::create([
                'kategori'   => $kategori,
                'nama'       => $name ?: "Import #{$imported}",
                'kode'       => FtthItem::generateKode($kategori),
                'latitude'   => $lat,
                'longitude'  => $lng,
                'status'     => 'online',
                'updated_by' => Auth::user()->name ?? 'import',
            ]);

            $imported++;
        }

        return $imported;
    }
}

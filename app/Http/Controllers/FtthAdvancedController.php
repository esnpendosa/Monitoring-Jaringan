<?php

namespace App\Http\Controllers;

use App\Models\Kabel;
use App\Models\OdcOdp;
use App\Models\Olt;
use App\Models\Pelanggan;
use App\Models\FtthItem;
use App\Services\AcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FtthAdvancedController extends Controller
{
    // ═══════════════════════════════════════════════════════
    //  1. KALKULATOR REDAMAN
    // ═══════════════════════════════════════════════════════

    /**
     * Hitung estimasi redaman OLT → ONU berdasarkan jalur kabel
     * POST /ftth/api/redaman-calc
     */
    public function kalkulatorRedaman(Request $request)
    {
        $request->validate([
            'panjang_kabel_m'   => 'required|numeric|min:0',
            'jumlah_splitter'   => 'required|integer|min:0',
            'rasio_splitter'    => 'required|in:2,4,8,16,32,64',
            'jumlah_konektor'   => 'nullable|integer|min:0',
            'jumlah_splice'     => 'nullable|integer|min:0',
            'tx_power_dbm'      => 'nullable|numeric',
        ]);

        $panjangKm     = $request->panjang_kabel_m / 1000;
        $atenuasiFiber = 0.35; // dB/km untuk SMF G.652D
        $splitterLoss  = [2=>3.5, 4=>7, 8=>10.5, 16=>14, 32=>17.5, 64=>21][$request->rasio_splitter];
        $konektorLoss  = ($request->jumlah_konektor ?? 2) * 0.3;
        $spliceLoss    = ($request->jumlah_splice   ?? 2) * 0.1;

        $totalLoss = round(
            ($panjangKm * $atenuasiFiber) +
            ($request->jumlah_splitter * $splitterLoss) +
            $konektorLoss +
            $spliceLoss,
            2
        );

        $txPower   = $request->tx_power_dbm ?? 2.0; // dBm OLT default +2
        $rxEstimasi = round($txPower - $totalLoss, 2);

        $status = 'ok';
        if ($rxEstimasi < -27)       $status = 'critical';
        elseif ($rxEstimasi < -24)   $status = 'warning';

        $breakdown = [
            'fiber'     => round($panjangKm * $atenuasiFiber, 2),
            'splitter'  => round($request->jumlah_splitter * $splitterLoss, 2),
            'konektor'  => round($konektorLoss, 2),
            'splice'    => round($spliceLoss, 2),
        ];

        return response()->json([
            'success'       => true,
            'total_loss_db' => $totalLoss,
            'rx_estimasi'   => $rxEstimasi,
            'tx_power'      => $txPower,
            'status'        => $status,
            'breakdown'     => $breakdown,
            'rekomendasi'   => $this->rekomendasiRedaman($rxEstimasi),
        ]);
    }

    /**
     * Hitung redaman otomatis berdasarkan pelanggan (ambil jalur kabel dari ODP→OLT)
     * GET /ftth/api/redaman-calc/{pelanggan}
     */
    public function kalkulatorRedamanPelanggan(Pelanggan $pelanggan)
    {
        $odp   = $pelanggan->odp;
        $kabel = null;
        $panjangTotal = 0;
        $splitterCount = 0;
        $konektorCount = 2;
        $spliceCount   = 2;

        // Ambil kabel drop (ODP→Pelanggan)
        $kabelDrop = Kabel::where('to_type', 'pelanggan')
            ->where('to_id', $pelanggan->id_pelanggan)->first();

        if ($kabelDrop && $kabelDrop->geometry) {
            $panjangTotal += $this->hitungPanjangKabel($kabelDrop->geometry);
            $splitterCount += 1; // splitter di ODP
        }

        // Ambil kabel distribusi (ODC→ODP)
        if ($odp) {
            $kabelDistrib = Kabel::where('to_type', 'odp')
                ->where('to_id', $odp->id)->first();
            if ($kabelDistrib && $kabelDistrib->geometry) {
                $panjangTotal += $this->hitungPanjangKabel($kabelDistrib->geometry);
                $splitterCount += 1;
            }

            // Ambil kabel feeder (OLT→ODC) jika ODC punya parent
            $odc = $odp->parent;
            if ($odc) {
                $kabelFeeder = Kabel::where('to_type', 'odc')
                    ->where('to_id', $odc->id)->first();
                if ($kabelFeeder && $kabelFeeder->geometry) {
                    $panjangTotal += $this->hitungPanjangKabel($kabelFeeder->geometry);
                    $konektorCount += 2;
                    $spliceCount   += 4;
                }
            }
        }

        // Jika tidak ada data kabel, estimasi dari RX power actual
        if ($panjangTotal == 0 && $pelanggan->onu_rx_power) {
            return response()->json([
                'success'       => true,
                'estimated'     => false,
                'rx_actual'     => $pelanggan->onu_rx_power,
                'rx_baseline'   => $pelanggan->baseline_rx_power,
                'degradasi_db'  => $pelanggan->baseline_rx_power
                    ? round($pelanggan->baseline_rx_power - $pelanggan->onu_rx_power, 2)
                    : null,
                'message'       => 'Menggunakan data RX aktual dari GenieACS.',
            ]);
        }

        // Hitung dari jalur kabel
        $rasio = $splitterCount >= 3 ? 64 : ($splitterCount == 2 ? 16 : 8);
        $request = new Request([
            'panjang_kabel_m' => $panjangTotal,
            'jumlah_splitter' => max(1, $splitterCount - 1),
            'rasio_splitter'  => $rasio,
            'jumlah_konektor' => $konektorCount,
            'jumlah_splice'   => $spliceCount,
        ]);
        $request->merge(['tx_power_dbm' => 2.0]);

        $result = $this->kalkulatorRedaman($request);
        $data   = json_decode($result->getContent(), true);
        $data['panjang_total_m'] = round($panjangTotal, 0);
        $data['rx_actual']       = $pelanggan->onu_rx_power;
        $data['rx_baseline']     = $pelanggan->baseline_rx_power;
        $data['degradasi_db']    = $pelanggan->baseline_rx_power && $pelanggan->onu_rx_power
            ? round($pelanggan->baseline_rx_power - $pelanggan->onu_rx_power, 2)
            : null;

        return response()->json($data);
    }

    // ═══════════════════════════════════════════════════════
    //  2. REMOTE WIFI via MAPS
    // ═══════════════════════════════════════════════════════

    /**
     * Ambil info WiFi saat ini dari GenieACS
     * GET /ftth/api/wifi-info/{pelanggan}
     */
    public function getWifiInfo(Pelanggan $pelanggan)
    {
        if (!$pelanggan->serial_ont) {
            return response()->json(['error' => 'Pelanggan tidak memiliki serial ONT.'], 404);
        }

        $acs  = app(AcsService::class);
        $info = $acs->getWifiInfo($pelanggan->serial_ont);

        // Format uptime
        $uptimeStr = '-';
        if (!empty($info['uptime_sec'])) {
            $sec = (int) $info['uptime_sec'];
            $h   = floor($sec / 3600);
            $m   = floor(($sec % 3600) / 60);
            $uptimeStr = "{$h}j {$m}m";
        }

        return response()->json([
            'ssid'         => $info['ssid'] ?? '-',
            'rx_power'     => $info['rx_power'] ?? $pelanggan->onu_rx_power,
            'uptime'       => $uptimeStr,
            'last_inform'  => $info['last_inform'] ?? null,
            'nama'         => $pelanggan->nama_pelanggan,
            'ip_address'   => $pelanggan->ip_address,
            'serial_ont'   => $pelanggan->serial_ont,
        ]);
    }

    /**
     * Ubah SSID/Password WiFi pelanggan
     * POST /ftth/api/set-wifi/{pelanggan}
     */
    public function setWifi(Request $request, Pelanggan $pelanggan)
    {
        $request->validate([
            'ssid'     => 'nullable|string|min:2|max:32',
            'password' => 'nullable|string|min:8|max:63',
        ]);

        if (!$pelanggan->serial_ont) {
            return response()->json(['success' => false, 'message' => 'Serial ONT tidak terdaftar.'], 422);
        }
        if (!$request->filled('ssid') && !$request->filled('password')) {
            return response()->json(['success' => false, 'message' => 'SSID atau password harus diisi.'], 422);
        }

        $acs = app(AcsService::class);
        $ok  = $acs->setWifi($pelanggan->serial_ont, $request->ssid, $request->password);
        // Auto trigger reboot so physical ONT modem updates Wi-Fi over the air immediately
        @$acs->reboot($pelanggan->serial_ont);

        if ($ok) {
            Log::info("WiFi changed for pelanggan #{$pelanggan->id_pelanggan} by " . (Auth::user()->name ?? 'system'));
            return response()->json(['success' => true, 'message' => 'Perintah ganti WiFi berhasil dikirim ke ONT.']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal kirim perintah ke GenieACS. Cek koneksi ACS.'], 500);
    }

    /**
     * Reboot ONT pelanggan
     * POST /ftth/api/reboot-ont/{pelanggan}
     */
    public function rebootOnt(Pelanggan $pelanggan)
    {
        if (!$pelanggan->serial_ont) {
            return response()->json(['success' => false, 'message' => 'Serial ONT tidak terdaftar.'], 422);
        }

        $acs = app(AcsService::class);
        $ok  = $acs->reboot($pelanggan->serial_ont);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Perintah reboot dikirim ke ONT.' : 'Gagal reboot ONT.',
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  GENIEACS FULL APP MANAGEMENT ENDPOINTS
    // ═══════════════════════════════════════════════════════

    /**
     * Test koneksi GenieACS NBI Server
     * GET /ftth/api/acs/test
     */
    public function testAcsConnection(Request $request)
    {
        $acs = app(AcsService::class);
        if ($request->filled('url')) {
            $acs->setConfig($request->url, $request->input('user'), $request->input('pass'));
        }
        return response()->json($acs->testConnection());
    }

    /**
     * Simpan pengaturan GenieACS NBI Server secara dinamis ke database settings
     * POST /ftth/api/acs/config
     */
    public function saveAcsSettings(Request $request)
    {
        $request->validate([
            'url' => 'required|string',
        ]);

        $url  = rtrim(trim($request->url), '/');
        $user = trim($request->user ?? '');
        $pass = trim($request->pass ?? '');

        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['key' => 'genieacs_url'],
            ['value' => $url, 'group' => 'genieacs', 'updated_at' => now()]
        );
        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['key' => 'genieacs_user'],
            ['value' => $user, 'group' => 'genieacs', 'updated_at' => now()]
        );
        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['key' => 'genieacs_pass'],
            ['value' => $pass, 'group' => 'genieacs', 'updated_at' => now()]
        );

        $acs = app(AcsService::class);
        $acs->setConfig($url, $user, $pass);
        $testResult = $acs->testConnection();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan GenieACS NBI berhasil disimpan secara dinamis!',
            'test'    => $testResult,
        ]);
    }

    /**
     * Ambil daftar seluruh devices di GenieACS
     * GET /ftth/api/acs/devices
     */
    public function getAcsDevices(Request $request)
    {
        $acs = app(AcsService::class);
        $res = $acs->getAllDevices();
        return response()->json($res);
    }

    /**
     * Ambil detail lengkap 1 device di GenieACS
     * GET /ftth/api/acs/devices/{serialId}
     */
    public function getAcsDeviceDetail(string $serialId)
    {
        $acs    = app(AcsService::class);
        $detail = $acs->getDeviceDetails($serialId);

        if (!$detail) {
            return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS.'], 404);
        }

        return response()->json(['success' => true, 'device' => $detail]);
    }

    /**
     * Ubah WiFi SSID & Password via GenieACS TR-069
     * POST /ftth/api/acs/devices/{serialId}/wifi
     */
    public function setAcsWifi(Request $request, string $serialId)
    {
        $request->validate([
            'ssid' => 'required|string',
        ]);

        $acs = app(AcsService::class);
        $ok  = $acs->setWifi($serialId, $request->ssid, $request->password);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'SSID & Password Wi-Fi berhasil disimpan via TR-069!' : 'Gagal ganti Wi-Fi.',
        ]);
    }

    /**
     * Reboot Device via GenieACS TR-069
     * POST /ftth/api/acs/devices/{serialId}/reboot
     */
    public function rebootAcsDevice(string $serialId)
    {
        $acs = app(AcsService::class);
        $ok  = $acs->reboot($serialId);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Perintah Reboot berhasil dikirim ke perangkat!' : 'Gagal reboot.',
        ]);
    }

    /**
     * Ubah PPPoE Username & Password via GenieACS
     * POST /ftth/api/acs/devices/{serialId}/pppoe
     */
    public function setAcsPppoe(Request $request, string $serialId)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $acs = app(AcsService::class);
        $ok  = $acs->setPppoe($serialId, $request->username, $request->password);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'PPPoE WAN credentials berhasil diupdate.' : 'Gagal update PPPoE.',
        ]);
    }

    /**
     * Factory Reset Device via GenieACS
     * POST /ftth/api/acs/devices/{serialId}/factory-reset
     */
    public function factoryResetAcsDevice(string $serialId)
    {
        $acs = app(AcsService::class);
        $ok  = $acs->factoryReset($serialId);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Perintah Factory Reset dikirim ke ONT.' : 'Gagal Factory Reset.',
        ]);
    }

    /**
     * Summon / Refresh Object Device via GenieACS
     * POST /ftth/api/acs/devices/{serialId}/refresh
     */
    public function refreshAcsDevice(string $serialId)
    {
        $acs = app(AcsService::class);
        $ok  = $acs->refreshObject($serialId);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Perintah Refresh Object dikirim ke ONT.' : 'Gagal refresh device.',
        ]);
    }

    /**
     * Hapus Device dari GenieACS NBI
     * DELETE /ftth/api/acs/devices/{serialId}
     */
    public function deleteAcsDevice(string $serialId)
    {
        $acs = app(AcsService::class);
        $ok  = $acs->deleteDevice($serialId);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Device berhasil dihapus dari GenieACS.' : 'Gagal hapus device.',
        ]);
    }

    /**
     * Ambil daftar Presets GenieACS
     * GET /ftth/api/acs/presets
     */
    public function getAcsPresets()
    {
        $acs = app(AcsService::class);
        return response()->json(['success' => true, 'presets' => $acs->getPresets()]);
    }

    /**
     * Ambil daftar Provisions GenieACS
     * GET /ftth/api/acs/provisions
     */
    public function getAcsProvisions()
    {
        $acs = app(AcsService::class);
        return response()->json(['success' => true, 'provisions' => $acs->getProvisions()]);
    }

    /**
     * Ambil daftar Faults Log GenieACS
     * GET /ftth/api/acs/faults
     */
    public function getAcsFaults()
    {
        $acs = app(AcsService::class);
        return response()->json(['success' => true, 'faults' => $acs->getFaults()]);
    }

    /**
     * Ambil daftar Files (Firmware) GenieACS
     * GET /ftth/api/acs/files
     */
    public function getAcsFiles()
    {
        $acs = app(AcsService::class);
        return response()->json(['success' => true, 'files' => $acs->getFiles()]);
    }

    /**
     * Upload File Firmware/Config ke GenieACS FS
     * POST /ftth/api/acs/files
     */
    public function uploadAcsFile(Request $request)
    {
        $request->validate([
            'file'      => 'required|file',
            'file_type' => 'required|string',
            'version'   => 'nullable|string',
        ]);

        $uploaded = $request->file('file');
        $filename = $uploaded->getClientOriginalName();
        $fileType = $request->input('file_type');
        $version  = $request->input('version', '1.0.0');
        $content  = file_get_contents($uploaded->getRealPath());

        $acs = app(AcsService::class);
        $ok = $acs->uploadFile($filename, $fileType, $version, $content);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? "File {$filename} berhasil diunggah ke GenieACS FS!" : "Gagal mengunggah file.",
        ]);
    }

    /**
     * Hapus File Firmware/Config dari GenieACS FS
     * DELETE /ftth/api/acs/files/{filename}
     */
    public function deleteAcsFile($filename)
    {
        $acs = app(AcsService::class);
        $ok = $acs->deleteFile($filename);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? "File {$filename} berhasil dihapus dari GenieACS FS." : "Gagal menghapus file.",
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  3. IN-MAPS PING TERMINAL
    // ═══════════════════════════════════════════════════════

    /**
     * Ping IP address dan kembalikan hasilnya
     * POST /ftth/api/ping
     */
    public function ping(Request $request)
    {
        $request->validate([
            'ip'    => 'required|ip',
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        $ip    = $request->ip;
        $count = min((int) ($request->count ?? 4), 10);

        // Jalankan ping (platform-aware)
        $cmd     = PHP_OS_FAMILY === 'Windows'
            ? "ping -n {$count} " . escapeshellarg($ip) . " 2>&1"
            : "ping -c {$count} -W 2 " . escapeshellarg($ip) . " 2>&1";

        $output  = [];
        $retCode = 0;
        exec($cmd, $output, $retCode);

        $rawOutput  = implode("\n", $output);
        $isReachable = $retCode === 0;

        // Parse avg RTT
        $avgRtt = null;
        if (preg_match('/Average = (\d+)ms/i', $rawOutput, $m)) {
            $avgRtt = (float) $m[1]; // Windows
        } elseif (preg_match('/min\/avg\/max.*?= [\d.]+\/([\d.]+)\//', $rawOutput, $m)) {
            $avgRtt = (float) $m[1]; // Linux
        }

        // Log ke database
        \DB::table('ping_logs')->insert([
            'ip_address'   => $ip,
            'is_reachable' => $isReachable,
            'avg_rtt_ms'   => $avgRtt,
            'raw_output'   => $rawOutput,
            'user_id'      => Auth::id(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'success'     => true,
            'ip'          => $ip,
            'reachable'   => $isReachable,
            'avg_rtt_ms'  => $avgRtt,
            'output_lines'=> $output,
            'raw'         => $rawOutput,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  4. DATA TABLE — Export Excel/CSV
    // ═══════════════════════════════════════════════════════

    /**
     * Ambil semua data dalam format tabel
     * GET /ftth/api/data-table
     */
    public function dataTable(Request $request)
    {
        $q = Pelanggan::with(['odp.parent', 'router'])
            ->select([
                'id_pelanggan', 'kode_pelanggan', 'nama_pelanggan',
                'ip_address', 'latitude', 'longitude',
                'last_online_status', 'onu_rx_power', 'baseline_rx_power',
                'serial_ont', 'odp_id', 'id_router', 'paket',
                'last_inform_at', 'is_active',
            ]);

        if ($request->filled('status')) {
            $q->where('last_online_status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function($qb) use ($s) {
                $qb->where('nama_pelanggan', 'like', "%{$s}%")
                   ->orWhere('kode_pelanggan', 'like', "%{$s}%")
                   ->orWhere('ip_address', 'like', "%{$s}%");
            });
        }

        $rows = $q->get()->map(fn($p) => [
            'id'            => $p->id_pelanggan,
            'kode'          => $p->kode_pelanggan,
            'nama'          => $p->nama_pelanggan,
            'ip'            => $p->ip_address ?? '-',
            'lat'           => $p->latitude,
            'lng'           => $p->longitude,
            'status'        => $p->last_online_status ?? 'unknown',
            'rx_power'      => $p->onu_rx_power,
            'baseline_rx'   => $p->baseline_rx_power,
            'degradasi'     => ($p->baseline_rx_power && $p->onu_rx_power)
                ? round($p->baseline_rx_power - $p->onu_rx_power, 2)
                : null,
            'serial_ont'    => $p->serial_ont ?? '-',
            'odp'           => $p->odp?->nama ?? '-',
            'odc'           => $p->odp?->parent?->nama ?? '-',
            'paket'         => $p->paket ?? '-',
            'last_inform'   => $p->last_inform_at,
            'is_active'     => $p->is_active,
        ]);

        return response()->json(['data' => $rows, 'total' => $rows->count()]);
    }

    /**
     * Export CSV
     * GET /ftth/export-csv
     */
    public function exportCsv()
    {
        $rows = Pelanggan::with(['odp.parent'])->get();

        $filename = 'FTTH_Data_' . date('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kode', 'Nama', 'IP', 'Latitude', 'Longitude', 'ODP', 'ODC', 'Status', 'RX Power (dBm)', 'Baseline RX (dBm)', 'Degradasi (dB)', 'Serial ONT', 'Paket', 'Last Inform']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->kode_pelanggan,
                    $p->nama_pelanggan,
                    $p->ip_address ?? '-',
                    $p->latitude,
                    $p->longitude,
                    $p->odp?->nama ?? '-',
                    $p->odp?->parent?->nama ?? '-',
                    $p->last_online_status ?? '-',
                    $p->onu_rx_power,
                    $p->baseline_rx_power,
                    ($p->baseline_rx_power && $p->onu_rx_power) ? round($p->baseline_rx_power - $p->onu_rx_power, 2) : '',
                    $p->serial_ont ?? '-',
                    $p->paket ?? '-',
                    $p->last_inform_at,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ═══════════════════════════════════════════════════════
    //  5. BASELINE REDAMAN
    // ═══════════════════════════════════════════════════════

    /**
     * Set baseline RX power untuk pelanggan
     * POST /ftth/api/set-baseline/{pelanggan}
     */
    public function setBaseline(Request $request, Pelanggan $pelanggan)
    {
        $rxPower = $request->rx_power ?? $pelanggan->onu_rx_power;

        if (!$rxPower) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data RX power.'], 422);
        }

        $pelanggan->update([
            'baseline_rx_power' => $rxPower,
            'baseline_set_at'   => now(),
        ]);

        return response()->json([
            'success'   => true,
            'baseline'  => $rxPower,
            'message'   => "Baseline RX {$rxPower} dBm berhasil disimpan.",
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  6. QUICK DUPLICATE DEVICE
    // ═══════════════════════════════════════════════════════

    /**
     * Duplikasi pelanggan — reset IP, kode baru, geser koordinat
     * POST /ftth/api/duplicate-pelanggan/{pelanggan}
     */
    public function duplicatePelanggan(Pelanggan $pelanggan)
    {
        // Generate kode baru
        $lastKode = Pelanggan::orderByDesc('id_pelanggan')->value('kode_pelanggan') ?? '00000';
        $newKode  = str_pad((int) $lastKode + 1, 5, '0', STR_PAD_LEFT);

        // Geser koordinat 0.0001 derajat (~11m)
        $newLat = $pelanggan->latitude   ? (float) $pelanggan->latitude   + 0.0001 : null;
        $newLng = $pelanggan->longitude  ? (float) $pelanggan->longitude  + 0.0001 : null;

        $newPelanggan = $pelanggan->replicate();
        $newPelanggan->kode_pelanggan    = $newKode;
        $newPelanggan->ip_address        = null;      // wajib diisi manual
        $newPelanggan->serial_ont        = null;      // reset ONT
        $newPelanggan->onu_rx_power      = null;
        $newPelanggan->baseline_rx_power = null;
        $newPelanggan->baseline_set_at   = null;
        $newPelanggan->last_online_status = 'offline';
        $newPelanggan->last_inform_at    = null;
        $newPelanggan->latitude          = $newLat;
        $newPelanggan->longitude         = $newLng;
        $newPelanggan->nama_pelanggan    = $pelanggan->nama_pelanggan . ' (Copy)';
        $newPelanggan->save();

        return response()->json([
            'success'   => true,
            'new_id'    => $newPelanggan->id_pelanggan,
            'new_kode'  => $newKode,
            'message'   => "Pelanggan berhasil diduplikat. Harap lengkapi IP address dan Serial ONT.",
            'pelanggan' => [
                'id'    => $newPelanggan->id_pelanggan,
                'nama'  => $newPelanggan->nama_pelanggan,
                'kode'  => $newKode,
                'lat'   => $newLat,
                'lng'   => $newLng,
                'ip'    => null,
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  7. TELEGRAM SETTINGS & ALERT
    // ═══════════════════════════════════════════════════════

    /**
     * GET/POST settings Telegram
     * GET  /ftth/settings/telegram
     * POST /ftth/settings/telegram
     */
    public function telegramSettings(Request $request)
    {
        $setting = DB::table('telegram_settings')->first();

        if ($request->isMethod('POST')) {
            $request->validate([
                'bot_token' => 'nullable|string',
                'chat_id'   => 'nullable|string',
            ]);

            $data = [
                'bot_token'                  => $request->bot_token,
                'chat_id'                    => $request->chat_id,
                'enabled'                    => $request->boolean('enabled'),
                'notify_onu_offline'         => $request->boolean('notify_onu_offline'),
                'notify_odp_full'            => $request->boolean('notify_odp_full'),
                'notify_kabel_offline'       => $request->boolean('notify_kabel_offline'),
                'offline_threshold_minutes'  => (int) ($request->offline_threshold_minutes ?? 5),
                'updated_at'                 => now(),
            ];

            if ($setting) {
                DB::table('telegram_settings')->where('id', $setting->id)->update($data);
            } else {
                $data['created_at'] = now();
                DB::table('telegram_settings')->insert($data);
            }

            return response()->json(['success' => true, 'message' => 'Pengaturan Telegram disimpan.']);
        }

        return response()->json($setting ?: (object)[]);
    }

    /**
     * Test kirim pesan Telegram
     * POST /ftth/settings/telegram/test
     */
    public function telegramTest(Request $request)
    {
        $setting = DB::table('telegram_settings')->first();
        if (!$setting?->bot_token || !$setting?->chat_id) {
            return response()->json(['success' => false, 'message' => 'Token/Chat ID belum diset.'], 422);
        }

        $ok = $this->sendTelegram($setting->bot_token, $setting->chat_id,
            "✅ *Test Notifikasi FTTH NMS*\nRozitech FTTH bisa mengirim notifikasi Telegram!");

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Pesan test berhasil dikirim.' : 'Gagal kirim. Cek token/chat_id.',
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  8. AUTO-SYNC STATUS
    // ═══════════════════════════════════════════════════════

    /**
     * Jalankan sync manual (dipanggil dari tombol di dashboard)
     * POST /ftth/api/sync-now
     */
    public function syncNow(Request $request)
    {
        $startTime = microtime(true);
        $results   = [];

        // Sync GenieACS
        try {
            $acs    = app(AcsService::class);
            $result = $acs->syncAllDevices();
            $results['acs'] = $result;
        } catch (\Exception $e) {
            $results['acs'] = ['error' => $e->getMessage()];
        }

        // Cek ONU offline dan kirim notifikasi jika perlu
        $this->checkAndNotifyOffline();

        $elapsed = round(microtime(true) - $startTime, 2);

        return response()->json([
            'success'  => true,
            'elapsed'  => $elapsed,
            'results'  => $results,
            'synced_at'=> now()->format('H:i:s'),
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════

    private function rekomendasiRedaman(float $rxDbm): string
    {
        if ($rxDbm >= -20)     return '✅ Sangat Baik — sinyal optimal';
        if ($rxDbm >= -24)     return '✅ Baik — dalam batas normal';
        if ($rxDbm >= -27)     return '⚠️ Cukup — mulai perlu perhatian';
        if ($rxDbm >= -30)     return '⚠️ Lemah — perlu pengecekan konektor/kabel';
        return '🔴 Kritis — kemungkinan kabel putus atau splitter rusak';
    }

    private function hitungPanjangKabel(array $geometry): float
    {
        $total = 0;
        for ($i = 0; $i < count($geometry) - 1; $i++) {
            $total += $this->haversineMeters(
                $geometry[$i][0],   $geometry[$i][1],
                $geometry[$i+1][0], $geometry[$i+1][1]
            );
        }
        return $total;
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }

    public function sendTelegram(string $token, string $chatId, string $message): bool
    {
        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                ['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'Markdown']
            );
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram send error: " . $e->getMessage());
            return false;
        }
    }

    private function checkAndNotifyOffline(): void
    {
        $setting = DB::table('telegram_settings')->first();
        if (!$setting || !$setting->enabled) return;

        $threshold = $setting->offline_threshold_minutes ?? 5;

        // ONU offline
        if ($setting->notify_onu_offline) {
            $offline = Pelanggan::where('last_online_status', 'offline')
                ->where('last_inform_at', '<', now()->subMinutes($threshold))
                ->whereNotNull('serial_ont')
                ->count();

            if ($offline > 0) {
                $this->sendTelegram(
                    $setting->bot_token, $setting->chat_id,
                    "🔴 *FTTH Alert: {$offline} ONU Offline*\nLebih dari {$threshold} menit tidak ada respon.\nCek dashboard FTTH segera."
                );
            }
        }

        // ODP penuh
        if ($setting->notify_odp_full) {
            $odps = OdcOdp::where('tipe', 'ODP')
                ->withCount('pelanggan')
                ->get()
                ->filter(fn($o) => $o->kapasitas_port && $o->pelanggan_count >= $o->kapasitas_port);

            foreach ($odps as $odp) {
                $this->sendTelegram(
                    $setting->bot_token, $setting->chat_id,
                    "🟡 *FTTH Alert: ODP Penuh*\n{$odp->nama} sudah penuh ({$odp->pelanggan_count}/{$odp->kapasitas_port} port)."
                );
            }
        }

        // Kabel offline
        if ($setting->notify_kabel_offline) {
            $kabels = Kabel::where('status', 'offline')->get();
            foreach ($kabels as $k) {
                $this->sendTelegram(
                    $setting->bot_token, $setting->chat_id,
                    "🔴 *FTTH Alert: Kabel Putus*\n{$k->label} ({$k->tipe_label}) statusnya OFFLINE."
                );
            }
        }
    }
}

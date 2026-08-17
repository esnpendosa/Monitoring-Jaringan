<?php

namespace App\Services;

use App\Models\Kabel;
use App\Models\OdcOdp;
use App\Models\Olt;
use App\Models\Pelanggan;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FtthMonitoringService
 *
 * Implementasi logika deteksi gangguan kabel jaringan FTTH.
 *
 * Alur deteksi:
 * 1. Polling status ONU (diset dari SNMP atau manual via last_online_status)
 * 2. Jika 1 pelanggan offline → gangguan individu (notif Info ke dashboard)
 * 3. Jika mayoritas/seluruh pelanggan dalam 1 ODP offline → kabel distribusi ODP menjadi 'offline'
 * 4. Jika seluruh ODP di bawah 1 ODC offline → kabel feeder OLT→ODC menjadi 'offline'
 * 5. OLT sendiri di-update statusnya berdasarkan kondisi ODC di bawahnya
 */
class FtthMonitoringService
{
    /**
     * Jalankan seluruh pipeline deteksi gangguan jaringan FTTH.
     * Dipanggil oleh Console Command PollFtthStatus.
     */
    public function runFullPoll(): array
    {
        $log = [];

        // Step 1 — Proses setiap ODP: cek status pelanggan di bawahnya
        $odps = OdcOdp::where('tipe', 'ODP')
            ->with(['pelanggan', 'kabelsIn'])
            ->get();

        foreach ($odps as $odp) {
            $result = $this->evaluateOdp($odp);
            $log[]  = "[ODP #{$odp->id} {$odp->nama}] → {$result}";
        }

        // Step 2 — Proses setiap ODC: cek status ODP di bawahnya
        $odcs = OdcOdp::where('tipe', 'ODC')
            ->with(['children', 'kabelsIn'])
            ->get();

        foreach ($odcs as $odc) {
            $result = $this->evaluateOdc($odc);
            $log[]  = "[ODC #{$odc->id} {$odc->nama}] → {$result}";
        }

        // Step 3 — Update status OLT
        $olts = Olt::with(['odcList'])->get();
        foreach ($olts as $olt) {
            $result = $this->evaluateOlt($olt);
            $log[]  = "[OLT #{$olt->id} {$olt->nama}] → {$result}";
        }

        return $log;
    }

    /**
     * Evaluasi kondisi satu ODP berdasarkan status pelanggan di bawahnya.
     * Update status kabel distribusi masuk ke ODP tersebut.
     */
    public function evaluateOdp(OdcOdp $odp): string
    {
        $pelangganList = $odp->pelanggan;

        if ($pelangganList->isEmpty()) {
            return 'Tidak ada pelanggan terhubung';
        }

        $total   = $pelangganList->count();
        $offline = $pelangganList->where('last_online_status', 'offline')->count();
        $ratio   = $offline / $total;

        // Tentukan status baru ODP
        if ($ratio === 0.0) {
            $newStatus = 'online';
        } elseif ($ratio < 1.0) {
            $newStatus = 'warning'; // sebagian offline
        } else {
            $newStatus = 'offline'; // semua offline
        }

        // Update status ODP
        if ($odp->status !== $newStatus) {
            $odp->update(['status' => $newStatus]);
            Log::info("FTTH: ODP #{$odp->id} status changed to {$newStatus}");
        }

        // Update kabel distribusi yang masuk ke ODP ini
        $kabels = Kabel::where('to_type', 'odp')->where('to_id', $odp->id)->get();
        foreach ($kabels as $kabel) {
            if ($kabel->monitoring_type === 'manual' && $kabel->status !== $newStatus) {
                $kabel->update([
                    'status'     => $newStatus,
                    'updated_by' => 'system:auto-inference',
                ]);
                $this->createAlert($odp, $newStatus, $offline, $total);
            }
        }

        return "{$offline}/{$total} offline → ODP={$newStatus}";
    }

    /**
     * Evaluasi kondisi ODC berdasarkan status ODP-ODP di bawahnya.
     * Update kabel feeder yang mengarah ke ODC ini.
     */
    public function evaluateOdc(OdcOdp $odc): string
    {
        $odps    = OdcOdp::where('tipe', 'ODP')->where('parent_id', $odc->id)->get();

        if ($odps->isEmpty()) {
            return 'Tidak ada ODP terhubung';
        }

        $total   = $odps->count();
        $offline = $odps->where('status', 'offline')->count();
        $ratio   = $offline / $total;

        if ($ratio === 0.0) {
            $newStatus = 'online';
        } elseif ($ratio < 1.0) {
            $newStatus = 'warning';
        } else {
            $newStatus = 'offline'; // seluruh ODP offline → kabel feeder putus
        }

        if ($odc->status !== $newStatus) {
            $odc->update(['status' => $newStatus]);
        }

        // Update kabel feeder yang masuk ke ODC ini (manual inference)
        $kabels = Kabel::where('to_type', 'odc')->where('to_id', $odc->id)->get();
        foreach ($kabels as $kabel) {
            if ($kabel->monitoring_type === 'manual' && $kabel->status !== $newStatus) {
                $kabel->update([
                    'status'     => $newStatus,
                    'updated_by' => 'system:auto-inference',
                ]);
                if ($newStatus === 'offline') {
                    $this->createAlert($odc, 'offline', $offline, $total, level: 'critical');
                }
            }
        }

        return "{$offline}/{$total} ODP offline → ODC={$newStatus}";
    }

    /**
     * Update status OLT berdasarkan kondisi ODC di bawahnya.
     */
    public function evaluateOlt(Olt $olt): string
    {
        $odcs    = OdcOdp::where('tipe', 'ODC')->where('olt_id', $olt->id)->get();

        if ($odcs->isEmpty()) {
            return 'Tidak ada ODC terhubung';
        }

        $hasOffline = $odcs->where('status', 'offline')->isNotEmpty();
        $allOffline = $odcs->every(fn($o) => $o->status === 'offline');

        $newStatus = match (true) {
            $allOffline  => 'offline',
            $hasOffline  => 'warning',
            default      => 'online',
        };

        if ($olt->status !== $newStatus) {
            $olt->update(['status' => $newStatus]);
        }

        return "OLT={$newStatus}";
    }

    /**
     * Update status kabel berdasarkan data RFTS (realtime sensor).
     * Dipanggil dari RftsService setelah menerima data terbaru.
     */
    public function applyRftsReading(Kabel $kabel, string $rftsStatus, float $redaman, ?float $jarakPutus = null): void
    {
        $newStatus = match ($rftsStatus) {
            'break'            => 'offline',
            'attenuation_high' => 'warning',
            default            => 'online',
        };

        $kabel->update([
            'status'            => $newStatus,
            'redaman_db'        => $redaman,
            'titik_putus_meter' => $jarakPutus,
            'updated_by'        => 'system:rfts',
        ]);

        Log::info("FTTH RFTS: Kabel #{$kabel->id} '{$kabel->label}' → {$newStatus} (redaman={$redaman}dB, jarak={$jarakPutus}m)");
    }

    /**
     * Buat notifikasi in-app
     */
    private function createAlert(OdcOdp $node, string $status, int $offlineCount, int $total, string $level = 'warning'): void
    {
        try {
            $pesan = match ($level) {
                'critical' => "🔴 CRITICAL: Seluruh {$total} ODP di {$node->nama} offline! Kabel feeder kemungkinan putus.",
                'warning'  => "⚠️ WARNING: {$offlineCount}/{$total} pelanggan di {$node->nama} offline. Cek kabel distribusi.",
                default    => "ℹ️ INFO: {$offlineCount} pelanggan offline di {$node->nama}.",
            };

            Notification::create([
                'type'    => 'ftth_alert',
                'title'   => strtoupper($level) . ': Gangguan Jaringan',
                'message' => $pesan,
                'data'    => json_encode([
                    'node_id'   => $node->id,
                    'node_tipe' => $node->tipe,
                    'node_nama' => $node->nama,
                    'status'    => $status,
                    'level'     => $level,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('FTTH Alert creation failed: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pelanggan;
use App\Services\MikrotikService;
use App\Services\WhatsappClient;
use Illuminate\Support\Facades\Log;

class MonitorPelanggan extends Command
{
    protected $signature = 'monitor:pelanggan';
    protected $description = 'Monitor pelanggan online status and send notifications';

    public function handle(MikrotikService $mikrotik)
    {
        $cacheKey = 'monitor_pelanggan_last_run';
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $this->info('MonitorPelanggan has already run in the last 2 minutes. Exiting to prevent duplication.');
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 120);

        $pelanggans = Pelanggan::where('is_active', true)->whereNotNull('id_router')->get();
        $adminNum = env('WHATSAPP_ADMIN_NUMBER');
        $waClient = new WhatsappClient();

        foreach ($pelanggans as $p) {
            $router = $p->router;
            if (!$router) continue;

            $mUser = $p->mikrotik_username;
            $currentIp = null;

            // 1. Coba cari IP Aktif dari Mikrotik (jika ada username)
            if ($mUser) {
                $currentIp = $mikrotik->getPelangganActiveIp($router, $mUser, $p->mikrotik_type);
                if ($currentIp === 'ROUTER_OFFLINE') {
                    $this->warn("Skipping monitoring check for customer {$p->nama_pelanggan} because the router {$router->nama_router} connection is offline/failed.");
                    continue;
                }
            }

            // 2. Jika tidak ketemu di Mikrotik, tapi ada IP Manual, coba ping IP Manual tersebut
            if (!$currentIp && $p->ip_address) {
                $host = $p->ip_address;
                $pingCommand = (PHP_OS_FAMILY === 'Windows') ? "ping -n 1 -w 1000 $host" : "ping -c 1 -W 1 $host";
                exec($pingCommand, $output, $resultCode);
                if ($resultCode === 0) {
                    $currentIp = $host; // Anggap online jika ping berhasil
                }
            }

            $isOnline = $currentIp ? true : false;
            
            // If status changed from Online to Offline
            if ($p->last_online_status && !$isOnline) {
                $msg = "⚠️ *LAPORAN GANGGUAN OTOMATIS*\n";
                $msg .= "--------------------------\n";
                $msg .= "Pelanggan: " . $p->nama_pelanggan . " (" . $p->kode_pelanggan . ")\n";
                $msg .= "Status: *OFFLINE / DISCONNECTED*\n";
                $msg .= "Waktu: " . now()->format('H:i:s d/m/Y') . "\n";
                $msg .= "--------------------------\n";
                $msg .= "Mohon cek koneksi atau hubungi pelanggan.";

                // Send Telegram Alert to Admin Group via TelegramService
                try {
                    $telegramService = new \App\Services\TelegramService();
                    if ($telegramService->isEnabled()) {
                        $tgMsg = "🔴 <b>WATCHDOG DETEKSI GANGGUAN OFFLINE</b>\n";
                        $tgMsg .= "--------------------------------------\n";
                        $tgMsg .= "Pelanggan: <b>" . htmlspecialchars($p->nama_pelanggan) . "</b> (" . $p->kode_pelanggan . ")\n";
                        $tgMsg .= "Status   : 🔴 <b>OFFLINE / DISCONNECTED</b>\n";
                        $tgMsg .= "Waktu    : " . now()->format('H:i:s d/m/Y') . "\n";
                        $tgMsg .= "--------------------------------------\n";
                        $tgMsg .= "<i>Sistem otomatis memantau koneksi & membuat tiket penanganan.</i>";
                        $telegramService->sendAdminAlert($tgMsg);
                    }
                } catch (\Exception $e) {
                    Log::error("Watchdog Telegram Alert Error: " . $e->getMessage());
                }

                // Auto-create Watchdog Ticket if no open ticket exists
                try {
                    $hasOpenTicket = \App\Models\TiketGangguan::where('id_pelanggan', $p->id_pelanggan)
                        ->where('status', 'Open')
                        ->exists();

                    if (!$hasOpenTicket) {
                        \App\Models\TiketGangguan::create([
                            'kode_tiket'   => 'TKT-WATCHDOG-' . date('YmdHis') . '-' . $p->id_pelanggan,
                            'id_pelanggan' => $p->id_pelanggan,
                            'prioritas'    => 'Tinggi',
                            'keluhan'      => 'Sistem Watchdog Otomatis: ONT Offline / Disconnected terdeteksi',
                            'status'       => 'Open',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Watchdog Ticket Auto-Create Error: " . $e->getMessage());
                }

                Log::info("Offline status detected for customer: " . $p->nama_pelanggan);
            } 
            // If status changed from Offline to Online
            elseif (!$p->last_online_status && $isOnline) {
                $msg = "✅ *KONEKSI PULIH*\n";
                $msg .= "--------------------------\n";
                $msg .= "Pelanggan: " . $p->nama_pelanggan . " (" . $p->kode_pelanggan . ")\n";
                $msg .= "Status: *ONLINE*\n";
                $msg .= "IP Baru: " . $currentIp . "\n";
                $msg .= "Waktu: " . now()->format('H:i:s d/m/Y');

                try {
                    $telegramService = new \App\Services\TelegramService();
                    if ($telegramService->isEnabled()) {
                        $tgRecover = "🟢 <b>WATCHDOG KONEKSI PULIH</b>\n";
                        $tgRecover .= "Pelanggan: <b>" . htmlspecialchars($p->nama_pelanggan) . "</b> (" . $p->kode_pelanggan . ")\n";
                        $tgRecover .= "Status   : 🟢 <b>ONLINE</b>\n";
                        $tgRecover .= "IP       : " . $currentIp . "\n";
                        $tgRecover .= "Waktu    : " . now()->format('H:i:s d/m/Y');
                        $telegramService->sendAdminAlert($tgRecover);
                    }
                } catch (\Exception $e) {
                    Log::error("Watchdog Recovery Telegram Error: " . $e->getMessage());
                }
            }

            // Update database
            $p->update([
                'ip_address' => $currentIp ?: $p->ip_address,
                'last_online_status' => $isOnline,
                'last_ping_at' => now()
            ]);
        }

        $this->info('Monitoring completed at ' . now());
    }
}

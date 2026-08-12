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

                // Notify Admin (Disabled by request)
                // $targetJid = str_contains($adminNum, '@') ? $adminNum : $adminNum . "@s.whatsapp.net";
                // $waClient->sendMessage($targetJid, ['text' => $msg]);
                
                // JANGAN kirim notifikasi offline ke pelanggan agar tidak risih
                /*
                if ($p->no_wa) {
                    $waClient->sendMessage($p->no_wa, ['text' => "Halo Kak " . $p->nama_pelanggan . ", sistem kami mendeteksi koneksi internet Anda terputus. Tim kami sedang mengecek kendala ini. Mohon tunggu sebentar nggih."]);
                }
                */

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

                // Notify Admin (Disabled by request)
                // $targetJid = str_contains($adminNum, '@') ? $adminNum : $adminNum . "@s.whatsapp.net";
                // $waClient->sendMessage($targetJid, ['text' => $msg]);
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

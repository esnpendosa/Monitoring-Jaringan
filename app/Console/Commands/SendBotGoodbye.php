<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsappTraining;
use App\Services\WhatsappClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendBotGoodbye extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:goodbye';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pesan penutup (Goodbye) setelah 5 menit tidak ada aktivitas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(0);
        $minutes = 5;
        $threshold = time() - ($minutes * 60);

        // Cari JID yang pesan terakhirnya sudah lebih dari 5 menit yang lalu
        // Tapi masih dalam kurun waktu 1 jam terakhir (agar tidak kirim ke chat sangat lama)
        $oneHourAgo = time() - 3600;

        $recentInteractions = WhatsappTraining::select('remote_jid', DB::raw('MAX(timestamp) as last_seen'))
            ->groupBy('remote_jid')
            ->having('last_seen', '<=', $threshold)
            ->having('last_seen', '>=', $oneHourAgo)
            ->get();

        if ($recentInteractions->isEmpty()) {
            $this->info("Tidak ada sesi yang perlu ditutup.");
            return;
        }

        $client = new WhatsappClient();
        $goodbyeKeyword = "Terima kasih sudah menghubungi R-CARE";

        foreach ($recentInteractions as $interaction) {
            $jid = $interaction->remote_jid;
            
            // Cek apakah pesan paling terakhir benar-benar pesan penutup atau pesan dari user
            $lastMsg = WhatsappTraining::where('remote_jid', $jid)
                ->orderBy('timestamp', 'desc')
                ->first();
            
            // Jika pesan terakhir sudah pesan penutup, jangan kirim lagi
            if ($lastMsg && str_contains($lastMsg->message, $goodbyeKeyword)) {
                continue;
            }

            // Jika pesan terakhir dari sistem tapi bukan penutup, atau dari user
            $msg = "Terima kasih sudah menghubungi R-CARE. Jika ada yang bisa dibantu kembali, silahkan ketik “Hi”. \n\nR-CARE dengan senang hati akan membantu.\nSampai jumpa 😊";
            
            $this->info("Mengirim goodbye ke: $jid");
            
            if ($client->sendMessage($jid, $msg)) {
                // Simpan ke log agar tidak dobel
                WhatsappTraining::create([
                    'remote_jid' => $jid,
                    'message' => $msg,
                    'is_from_me' => true,
                    'timestamp' => time()
                ]);
                
                // Jeda 3-5 detik
                sleep(rand(3, 5));
            }
        }

        $this->info("Proses selesai.");
    }
}

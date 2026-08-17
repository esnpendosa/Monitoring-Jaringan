<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AcsService;

class StartFtthSuite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ftth:start {--port=8000 : Port untuk web app Laravel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan seluruh ekosistem FTTH Manager + GenieACS TR-069 sekaligus dalam 1 kali jalan';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("🚀 Memulai Seluruh Ekosistem FTTH Manager & GenieACS TR-069...");

        // 1. Cek & Jalankan MongoDB Service jika di Windows
        if (str_contains(PHP_OS, 'WIN')) {
            $this->info("🍃 Memeriksa Layanan MongoDB...");
            exec('net start MongoDB >nul 2>&1');
        }

        // 2. Jalankan Layanan GenieACS (CWMP 7547, NBI 7557, FS 7567, UI 3000)
        $localPath = base_path('genieacs');
        $geniePath = file_exists("{$localPath}\\bin\\genieacs-nbi") ? $localPath : 'C:\\laragon\\www\\genieacs-app-main';

        if (file_exists("{$geniePath}\\bin\\genieacs-cwmp")) {
            $this->info("📡 Memulai Layanan GenieACS TR-069 (Ports 7547, 7557, 7567, 3000)...");
            pclose(popen("start \"GenieACS-CWMP\" /min node \"{$geniePath}\\bin\\genieacs-cwmp\"", "r"));
            pclose(popen("start \"GenieACS-NBI\" /min node \"{$geniePath}\\bin\\genieacs-nbi\"", "r"));
            pclose(popen("start \"GenieACS-FS\" /min node \"{$geniePath}\\bin\\genieacs-fs\"", "r"));
            pclose(popen("start \"GenieACS-UI\" /min node \"{$geniePath}\\bin\\genieacs-ui\"", "r"));
        }

        // 3. Verifikasi Koneksi NBI
        $acs = app(AcsService::class);
        $test = $acs->testConnection();
        if ($test['online']) {
            $this->info("✅ GenieACS NBI Terhubung ke {$test['url']}");
        } else {
            $this->warn("⚠️ GenieACS sedang booting di latar belakang...");
        }

        // 4. Jalankan Laravel Web Server
        $port = $this->option('port');
        $this->info("🌐 Membuka Dashboard FTTH Manager di http://127.0.0.1:{$port}/ftth ...");

        if (str_contains(PHP_OS, 'WIN')) {
            pclose(popen("start http://127.0.0.1:{$port}/ftth", "r"));
        }

        $this->call('serve', ['--host' => '127.0.0.1', '--port' => $port]);

        return 0;
    }
}

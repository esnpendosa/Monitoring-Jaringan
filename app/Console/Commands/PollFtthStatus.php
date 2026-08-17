<?php

namespace App\Console\Commands;

use App\Services\AcsService;
use App\Services\FtthMonitoringService;
use App\Services\RftsService;
use Illuminate\Console\Command;

class PollFtthStatus extends Command
{
    protected $signature   = 'ftth:poll-status
                                {--skip-acs  : Lewati sync GenieACS ACS}
                                {--skip-rfts : Lewati polling RFTS sensor}
                                {--skip-infer: Lewati inferensi status kabel dari ONU}';

    protected $description = 'Poll status jaringan FTTH: sync ACS/TR-069, polling RFTS OTDR, dan inferensi gangguan kabel';

    public function __construct(
        protected AcsService $acsService,
        protected RftsService $rftsService,
        protected FtthMonitoringService $monitoringService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('=== FTTH Network Status Poll ===');
        $this->info('Waktu: ' . now()->format('d/m/Y H:i:s'));
        $this->newLine();

        // Step 1 — Sync GenieACS (TR-069) → update last_online_status pelanggan
        if (!$this->option('skip-acs')) {
            $this->info('📡 Step 1: Sync GenieACS TR-069...');
            $result = $this->acsService->syncAllDevices();
            if (isset($result['error'])) {
                $this->warn("  ⚠️  ACS error: {$result['error']}");
            } else {
                $this->info("  ✅ Synced {$result['synced']} pelanggan dari {$result['total_devices']} device.");
            }
        }

        // Step 2 — Poll RFTS sensor (untuk kabel realtime)
        if (!$this->option('skip-rfts')) {
            $this->info('📟 Step 2: Polling RFTS OTDR sensors...');
            $results = $this->rftsService->pollAllRealtime();
            if (isset($results['error'])) {
                $this->warn("  ⚠️  RFTS: {$results['error']}");
            } else {
                $this->info('  ✅ ' . count($results) . ' kabel realtime diperbarui.');
                foreach ($results as $label => $r) {
                    $status = $r['status'] ?? $r['error'] ?? '?';
                    $this->line("     [{$label}] → {$status}");
                }
            }
        }

        // Step 3 — Inferensi status kabel manual dari agregasi ONU
        if (!$this->option('skip-infer')) {
            $this->info('🔍 Step 3: Inferensi gangguan kabel dari status ONU...');
            $logs = $this->monitoringService->runFullPoll();
            foreach ($logs as $log) {
                $this->line("  {$log}");
            }
            $this->info('  ✅ Inferensi selesai.');
        }

        $this->newLine();
        $this->info('=== Poll selesai ===');
        return Command::SUCCESS;
    }
}

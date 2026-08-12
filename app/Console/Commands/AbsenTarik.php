<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FingerprintService;

class AbsenTarik extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absen:tarik';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tarik data log absensi dari mesin Fingerspot';

    /**
     * Execute the console command.
     */
    public function handle(FingerprintService $fingerprintService)
    {
        $this->info('Memulai sinkronisasi data Fingerspot...');
        $result = $fingerprintService->pullData();
        $count = $result['total_synced'];
        $this->info("Sinkronisasi selesai. {$count} data diproses.");
    }
}

<?php

namespace App\Services;

use App\Models\Kabel;
use App\Models\RftsReading;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RftsService
 *
 * Konsumsi data dari alat RFTS/OTDR vendor (EXFO, VeEX, Fiberizon, dll.)
 * via REST API. Data yang ditarik: status kabel, redaman (dB), dan
 * jarak titik putus dalam meter dari titik awal kabel.
 *
 * Konfigurasi di .env:
 *   RFTS_API_URL=http://rfts-server/api
 *   RFTS_API_KEY=your-api-key
 */
class RftsService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected FtthMonitoringService $monitoringService;

    public function __construct(FtthMonitoringService $monitoringService)
    {
        $this->apiUrl            = rtrim(config('services.rfts.url', env('RFTS_API_URL', '')), '/');
        $this->apiKey            = config('services.rfts.key', env('RFTS_API_KEY', ''));
        $this->monitoringService = $monitoringService;
    }

    /**
     * Poll semua kabel dengan monitoring_type = 'realtime'
     * Ambil data terbaru dari API RFTS dan simpan ke rfts_readings.
     */
    public function pollAllRealtime(): array
    {
        if (empty($this->apiUrl)) {
            Log::warning('RFTS: RFTS_API_URL belum dikonfigurasi di .env');
            return ['error' => 'RFTS_API_URL not configured'];
        }

        $kabels = Kabel::where('monitoring_type', 'realtime')->get();
        $results = [];

        foreach ($kabels as $kabel) {
            try {
                $result  = $this->fetchKabelStatus($kabel);
                $results[$kabel->label] = $result;
            } catch (\Exception $e) {
                Log::error("RFTS: Gagal ambil data kabel #{$kabel->id}: " . $e->getMessage());
                $results[$kabel->label] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Ambil status satu kabel dari RFTS API.
     * Asumsikan endpoint: GET /api/cables/{rfts_port_id}
     */
    protected function fetchKabelStatus(Kabel $kabel): array
    {
        $response = Http::timeout(10)
            ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
            ->get("{$this->apiUrl}/cables/{$kabel->id}");

        if (!$response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}: {$response->body()}");
        }

        $data = $response->json();

        // Simpan reading ke database
        $reading = RftsReading::create([
            'kabel_id'          => $kabel->id,
            'status'            => $data['status'] ?? 'ok',       // 'ok'|'break'|'attenuation_high'
            'redaman'           => $data['attenuation_db'] ?? null,
            'jarak_putus_meter' => $data['break_distance_m'] ?? null,
            'waktu_baca'        => now(),
        ]);

        // Terapkan perubahan status ke kabel (dan update warna peta)
        $this->monitoringService->applyRftsReading(
            $kabel,
            $reading->status,
            (float) ($reading->redaman ?? 0),
            $reading->jarak_putus_meter
        );

        return [
            'status'  => $reading->status,
            'redaman' => $reading->redaman,
            'jarak'   => $reading->jarak_putus_meter,
        ];
    }
}

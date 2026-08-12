<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class FonnteService
{
    protected $token;

    public function __construct()
    {
        // Get token from settings table
        $this->token = Setting::get('fonnte_token');
    }

    /**
     * Send WhatsApp message via Fonnte API with human-like delay intervals.
     */
    public function sendMessage($target, $message, $delay = '3-5')
    {
        if (!$this->token) {
            Log::error("Fonnte Error: API Token not configured.");
            return ['status' => false, 'message' => 'API Token not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // Default to Indonesia
                'delay' => (string) $delay,
            ]);

            $result = $response->json();

            // Local delay to stagger sequential API calls and mimic natural human intervals
            if ($delay) {
                if (str_contains($delay, '-')) {
                    $parts = explode('-', $delay);
                    $sleepSec = rand((int)$parts[0], (int)$parts[1]);
                } else {
                    $sleepSec = (int)$delay;
                }
                if ($sleepSec > 0) {
                    sleep($sleepSec);
                }
            }

            if ($response->successful() && ($result['status'] ?? false)) {
                return ['status' => true, 'message' => 'Message sent successfully'];
            }

            Log::error("Fonnte API Error:", $result);
            return ['status' => false, 'message' => $result['reason'] ?? 'Unknown error'];

        } catch (\Exception $e) {
            Log::error("Fonnte Connection Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}

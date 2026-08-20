<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TelegramBotController;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Poll updates once and exit}';
    protected $description = 'Long polling service for Telegram Bot updates (Localhost & Server mode)';

    public function handle(TelegramBotController $botController)
    {
        $setting = DB::table('telegram_settings')->first();
        if (!$setting || empty($setting->bot_token)) {
            $this->error('Bot Token belum disetting di Telegram Bot Manager!');
            return 1;
        }

        $token = $setting->bot_token;
        $this->info("🚀 Starting Telegram Bot Polling (Token: ... " . substr($token, -6) . ")...");
        $this->info("Clearing active webhooks to allow long polling...");
        
        // Remove any conflicting webhook so getUpdates works
        try {
            Http::post("https://api.telegram.org/bot{$token}/deleteWebhook", ['drop_pending_updates' => false]);
        } catch (\Exception $e) {}

        $this->info("Tekan Ctrl+C untuk menghentikan.");

        $offset = cache()->get('telegram_last_offset', 0);
        $runOnce = $this->option('once');

        while (true) {
            try {
                $response = Http::timeout(25)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 20
                ]);

                if ($response->successful()) {
                    $results = $response->json()['result'] ?? [];

                    foreach ($results as $update) {
                        $offset = $update['update_id'] + 1;
                        cache()->put('telegram_last_offset', $offset, 86400);

                        $message = $update['message'] ?? $update['edited_message'] ?? null;
                        if ($message && isset($message['chat']['id'])) {
                            $chatId    = $message['chat']['id'];
                            $text      = trim($message['text'] ?? '');
                            $firstName = $message['from']['first_name'] ?? 'Pelanggan';
                            $username  = $message['from']['username'] ?? '';

                            $this->info("[" . now()->format('H:i:s') . "] Pesan dari {$firstName} ({$chatId}): {$text}");

                            $botController->processIncomingMessage($chatId, $text, $firstName, $username);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Telegram Polling Error: " . $e->getMessage());
                sleep(1);
            }

            if ($runOnce) break;
            usleep(300000); // 0.3s pause
        }

        return 0;
    }
}

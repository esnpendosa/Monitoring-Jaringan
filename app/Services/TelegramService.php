<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TelegramService
{
    protected function getBotToken()
    {
        $setting = DB::table('telegram_settings')->first();
        return $setting?->bot_token ?? config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
    }

    public function getAdminChatId()
    {
        $setting = DB::table('telegram_settings')->first();
        return $setting?->chat_id ?? config('services.telegram.chat_id', env('TELEGRAM_CHAT_ID'));
    }

    public function isEnabled()
    {
        $setting = DB::table('telegram_settings')->first();
        if ($setting) {
            return (bool) $setting->enabled;
        }
        return !empty(env('TELEGRAM_BOT_TOKEN'));
    }

    /**
     * Send message via Telegram Bot API
     */
    public function sendMessage($chatId, string $message, string $parseMode = 'HTML')
    {
        $token = $this->getBotToken();
        if (empty($token) || empty($chatId)) {
            Log::warning("TelegramService: Missing bot_token or chat_id.");
            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $chatId,
                'text'                     => $message,
                'parse_mode'               => $parseMode,
                'disable_web_page_preview' => true,
            ]);

            if (!$response->successful()) {
                Log::error("TelegramService Error ({$response->status()}): " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("TelegramService Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send alert to Admin Telegram Group / Admin Chat
     */
    public function sendAdminAlert(string $message)
    {
        $chatId = $this->getAdminChatId();
        if (!empty($chatId)) {
            return $this->sendMessage($chatId, $message);
        }
        return false;
    }

    /**
     * Set Telegram Webhook URL
     */
    public function setWebhook(string $url)
    {
        $token = $this->getBotToken();
        if (empty($token)) return false;

        try {
            $res = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $url
            ]);
            return $res->json();
        } catch (\Exception $e) {
            Log::error("Telegram setWebhook error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete Telegram Webhook to enable Long Polling / getUpdates
     */
    public function deleteWebhook(bool $dropPendingUpdates = false)
    {
        $token = $this->getBotToken();
        if (empty($token)) return false;

        try {
            $res = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook", [
                'drop_pending_updates' => $dropPendingUpdates
            ]);
            return $res->json();
        } catch (\Exception $e) {
            Log::error("Telegram deleteWebhook error: " . $e->getMessage());
            return false;
        }
    }
}

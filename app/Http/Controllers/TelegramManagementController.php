<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TelegramService;

class TelegramManagementController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function index()
    {
        if (auth()->user()->id_role != 1 && !auth()->user()->hasPermission('user_manage')) {
            abort(403);
        }

        $setting = DB::table('telegram_settings')->first();
        if (!$setting) {
            $id = DB::table('telegram_settings')->insertGetId([
                'bot_token' => '',
                'chat_id' => '',
                'enabled' => false,
                'notify_onu_offline' => true,
                'notify_odp_full' => true,
                'notify_kabel_offline' => true,
                'offline_threshold_minutes' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $setting = DB::table('telegram_settings')->where('id', $id)->first();
        }

        $webhookUrl = url('/api/telegram/webhook');

        return view('content.telegram.index', compact('setting', 'webhookUrl'));
    }

    public function update(Request $request)
    {
        if (auth()->user()->id_role != 1 && !auth()->user()->hasPermission('user_manage')) {
            abort(403);
        }

        $setting = DB::table('telegram_settings')->first();

        $data = [
            'bot_token' => trim($request->bot_token),
            'chat_id' => trim($request->chat_id),
            'enabled' => $request->has('enabled'),
            'notify_onu_offline' => $request->has('notify_onu_offline'),
            'notify_odp_full' => $request->has('notify_odp_full'),
            'notify_kabel_offline' => $request->has('notify_kabel_offline'),
            'offline_threshold_minutes' => (int) $request->offline_threshold_minutes ?: 5,
            'updated_at' => now(),
        ];

        if ($setting) {
            DB::table('telegram_settings')->where('id', $setting->id)->update($data);
        } else {
            DB::table('telegram_settings')->insert($data);
        }

        return back()->with('success', 'Pengaturan Bot Telegram Berhasil Diperbarui!');
    }

    public function setWebhook(Request $request)
    {
        $webhookUrl = url('/api/telegram/webhook');
        $result = $this->telegramService->setWebhook($webhookUrl);

        if ($result && ($result['ok'] ?? false)) {
            return back()->with('success', 'Webhook Bot Telegram Berhasil Dihubungkan ke ' . $webhookUrl . '!');
        }

        $errMsg = $result['description'] ?? 'Gagal menghubungkan webhook. Pastikan Bot Token valid dan domain terjangkau HTTPS / Public URL.';
        return back()->with('error', $errMsg);
    }

    public function testMessage(Request $request)
    {
        $setting = DB::table('telegram_settings')->first();
        if (!$setting || empty($setting->bot_token) || empty($setting->chat_id)) {
            return back()->with('error', 'Bot Token atau Admin Chat ID belum diisi!');
        }

        $msg = "⚡ <b>TES KONEKSI BOT TELEGRAM DINAMIS</b>\n";
        $msg .= "--------------------------------------\n";
        $msg .= "Status : 🟢 <b>BERHASIL TERHUBUNG</b>\n";
        $msg .= "Server : " . config('app.name') . "\n";
        $msg .= "Waktu  : " . now()->format('H:i:s d/m/Y') . "\n";
        $msg .= "--------------------------------------\n";
        $msg .= "Bot Telegram dinamis Anda siap digunakan untuk Watchdog, Pembayaran, & Ticketing!";

        $success = $this->telegramService->sendMessage($setting->chat_id, $msg);

        if ($success) {
            return back()->with('success', 'Pesan Tes Berhasil Dikirim ke Chat ID ' . $setting->chat_id . '!');
        }

        return back()->with('error', 'Gagal mengirim pesan tes. Cek Bot Token dan Chat ID.');
    }
}

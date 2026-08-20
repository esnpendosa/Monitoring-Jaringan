<?php

namespace App\Http\Controllers;

use App\Services\WhatsappClient;
use App\Models\Setting;
use Illuminate\Http\Request;

class WhatsappManagementController extends Controller
{
    protected $waClient;

    public function __construct(WhatsappClient $waClient)
    {
        $this->waClient = $waClient;
    }

    public function index(Request $request)
    {
        $sessions = $this->waClient->getSessions();
        $isServerOnline = is_array($sessions) && !isset($sessions['error']);

        $botUrl    = Setting::get('whatsapp_bot_url', 'http://127.0.0.1:3000');
        $botPort   = Setting::get('whatsapp_bot_port', '3000');
        $botSecret = Setting::get('whatsapp_bot_secret', 'rozitech-bot-secret-2024');

        if ($request->ajax()) {
            return response()->json([
                'sessions'         => $sessions,
                'is_server_online' => $isServerOnline,
                'bot_url'          => $botUrl,
                'bot_secret'       => $botSecret,
            ]);
        }

        return view('content.whatsapp.index', compact('sessions', 'isServerOnline', 'botUrl', 'botPort', 'botSecret'));
    }

    public function saveConfig(Request $request)
    {
        $request->validate([
            'bot_url'    => 'required|string',
            'bot_secret' => 'required|string',
        ]);

        $url = rtrim($request->bot_url, '/');
        Setting::set('whatsapp_bot_url', $url, 'whatsapp');
        Setting::set('whatsapp_bot_secret', $request->bot_secret, 'whatsapp');

        // Extract port if present
        $parsed = parse_url($url);
        if (isset($parsed['port'])) {
            Setting::set('whatsapp_bot_port', (string) $parsed['port'], 'whatsapp');
        }

        // Test new connection immediately
        $newClient = new WhatsappClient();
        $sessions  = $newClient->getSessions();
        $isOnline  = is_array($sessions) && !isset($sessions['error']);

        return response()->json([
            'success'   => true,
            'is_online' => $isOnline,
            'message'   => $isOnline ? 'Koneksi ke server Bot WhatsApp berhasil & AKTIF!' : 'Pengaturan tersimpan. Namun server bot saat ini belum merespons.',
        ]);
    }

    public function testConnection()
    {
        $sessions = $this->waClient->getSessions();
        $isOnline = is_array($sessions) && !isset($sessions['error']);

        return response()->json([
            'success'   => $isOnline,
            'is_online' => $isOnline,
            'sessions'  => $sessions,
            'message'   => $isOnline ? 'Server Bot WA Terhubung & Berjalan Normal.' : 'Server Bot WA OFFLINE / Tidak Merespons.',
        ]);
    }

    public function start(Request $request)
    {
        $id = $request->id ?: 'main';
        $result = $this->waClient->startSession($id);
        
        return response()->json($result);
    }

    public function pairing(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'phone' => 'required'
        ]);

        $result = $this->waClient->getPairingCode($request->id, $request->phone);
        return response()->json($result);
    }

    public function stop(Request $request)
    {
        $result = $this->waClient->stopSession($request->id);
        return response()->json($result);
    }

    public function startBotProcess()
    {
        $botPath = base_path('whatsapp-bot');
        $command = "cmd /c \"taskkill /F /IM node.exe /T & cd /d $botPath & start /B node index.js > bot.log 2>&1\"";
        
        pclose(popen($command, "r"));
        
        return response()->json(['success' => true, 'message' => 'Sistem sedang menjalankan ulang server bot. Silakan tunggu 5–10 detik, status akan ter-update otomatis.']);
    }
}

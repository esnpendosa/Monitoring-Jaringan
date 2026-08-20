<?php

namespace App\Http\Controllers;

use App\Services\WhatsappClient;
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
        if ($request->ajax()) {
            return response()->json(['sessions' => $sessions]);
        }
        return view('content.whatsapp.index', compact('sessions'));
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
        // Check if bot is already running
        $sessions = $this->waClient->getSessions();
        if (!empty($sessions) || (isset($sessions['error']) && str_contains($sessions['error'], 'connect'))) {
            // If we can't connect, try to start
        } else {
             return response()->json(['success' => false, 'message' => 'Bot sudah berjalan.']);
        }

        $botPath = base_path('whatsapp-bot');
        // Force kill any existing node processes to prevent stale/ghost sessions. Use single ampersand so failure does not block the start command.
        $command = "cmd /c \"taskkill /F /IM node.exe /T & cd /d $botPath & start /B node index.js > bot.log 2>&1\"";
        
        pclose(popen($command, "r"));
        
        return response()->json(['success' => true, 'message' => 'Sistem sedang membersihkan proses lama dan menjalankan ulang bot. Silakan tunggu 10 detik lalu refresh.']);
    }
}

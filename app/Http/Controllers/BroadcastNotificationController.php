<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BroadcastNotificationController extends Controller
{
    /**
     * Tampilkan Halaman Form Custom Broadcast Push Notifikasi
     */
    public function index()
    {
        // Ambil riwayat broadcast notifikasi terbaru
        $history = Notification::orderBy('created_at', 'desc')
            ->paginate(15);

        $totalUsers = User::count();
        $adminCount = User::whereHas('role', fn($q) => $q->where('name', 'Admin'))->count();
        $teknisiCount = User::whereHas('role', fn($q) => $q->where('name', 'Teknisi'))->count();

        return view('content.notifications.broadcast', compact('history', 'totalUsers', 'adminCount', 'teknisiCount'));
    }

    /**
     * Proses Kirim Custom Push Notification Broadcast
     */
    public function send(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'body'       => 'required|string',
            'target'     => 'required|string|in:all,Admin,Teknisi,Pelanggan',
            'color'      => 'required|string|in:primary,success,warning,danger,info',
            'action_url' => 'nullable|url',
        ]);

        $title     = trim($request->title);
        $body      = trim($request->body);
        $target    = $request->target;
        $color     = $request->color;
        $actionUrl = $request->action_url ?: route('dashboard');

        $iconMap = [
            'primary' => 'bx-bell',
            'success' => 'bx-check-circle',
            'warning' => 'bx-error',
            'danger'  => 'bx-error-circle',
            'info'    => 'bx-info-circle',
        ];
        $icon = $iconMap[$color] ?? 'bx-bell';

        $options = [
            'icon'       => $icon,
            'color'      => $color,
            'action_url' => $actionUrl,
        ];

        try {
            if ($target === 'all') {
                NotificationHelper::sendToAll('broadcast', $title, $body, $options);
                $msg = "Push Notifikasi Kustom berhasil dikirim ke SELAMAT SEMUA Pengguna & Perangkat PWA!";
            } else {
                NotificationHelper::sendToRole($target, 'broadcast', "[{$target}] {$title}", $body, $options);
                $msg = "Push Notifikasi Kustom berhasil dikirim ke grup role '{$target}'!";
            }

            return redirect()->route('notifications.broadcast.index')->with('success', $msg);
        } catch (\Exception $e) {
            Log::error('Broadcast notification error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengirim push notifikasi: ' . $e->getMessage());
        }
    }
}

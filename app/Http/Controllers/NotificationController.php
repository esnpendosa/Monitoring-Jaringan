<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    /**
     * GET /notifications
     * Ambil notifikasi untuk user yang login (max 30 terbaru)
     */
    public function index()
    {
        $userId = auth()->id();

        $notifications = Notification::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->map(fn($n) => $this->format($n));

        $unreadCount = Notification::forUser($userId)->unread()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * GET /notifications/stream
     * Server-Sent Events (SSE): koneksi tetap terbuka, server PUSH langsung
     * ke browser saat notif baru masuk — tanpa perlu polling dari client.
     *
     * Browser auto-reconnect setiap kali koneksi terputus (built-in EventSource).
     */
    public function stream(Request $request): StreamedResponse
    {
        $userId = auth()->id();
        // Client kirim lastId (ID notif terakhir yang sudah diterima) via query param
        // sehingga setelah reconnect tidak ada notif yang terlewat
        $lastId = (int) $request->query('lastId', 0);

        $response = new StreamedResponse(function () use ($userId, $lastId) {
            // Lepas lock session agar request lain dari user yang sama tidak terblokir (session locking)
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            // Matikan output buffering agar data langsung terkirim ke browser
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Instruksi retry ke browser: tunggu 3 detik sebelum reconnect
            echo "retry: 3000\n\n";
            flush();

            $currentLastId = $lastId;
            $iteration     = 0;
            // Maksimal ~5 menit (150 × 2 detik), lalu tutup koneksi
            // Browser akan reconnect otomatis via EventSource
            $maxIterations = 150;

            while ($iteration < $maxIterations) {
                // Cek apakah koneksi client masih hidup
                if (connection_aborted()) {
                    break;
                }

                // Cari notifikasi baru sejak $currentLastId
                $newNotifs = Notification::forUser($userId)
                    ->where('id', '>', $currentLastId)
                    ->orderBy('id', 'asc')
                    ->get();

                if ($newNotifs->isNotEmpty()) {
                    foreach ($newNotifs as $n) {
                        $data = json_encode($this->format($n));
                        // Format SSE standar: id (untuk Last-Event-ID), event name, data JSON
                        echo "id: {$n->id}\n";
                        echo "event: new_notification\n";
                        echo "data: {$data}\n\n";
                        $currentLastId = $n->id;
                    }

                    // Kirim juga unread count terbaru sebagai event terpisah
                    $count = Notification::forUser($userId)->unread()->count();
                    echo "event: unread_count\n";
                    echo "data: {\"unread_count\":{$count}}\n\n";

                    flush();
                } else {
                    // Heartbeat comment — mencegah koneksi di-drop proxy/firewall/Nginx
                    echo ": heartbeat\n\n";
                    flush();
                }

                $iteration++;
                sleep(2); // Cek database tiap 2 detik
            }

            // Tutup koneksi → browser EventSource akan auto-reconnect
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('X-Accel-Buffering', 'no'); // Wajib untuk Nginx agar tidak buffer
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    /**
     * POST /notifications/{id}/read
     * Tandai satu notifikasi sudah dibaca
     */
    public function read(Notification $notification)
    {
        $userId = auth()->id();

        if ($notification->user_id !== null && $notification->user_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * POST /notifications/read-all
     * Tandai SEMUA notifikasi milik user sebagai sudah dibaca
     */
    public function readAll()
    {
        $userId = auth()->id();

        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Notification::whereNull('user_id')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * GET /notifications/count
     * Fallback polling ringan (dipakai jika SSE tidak tersedia / browser lama)
     */
    public function count()
    {
        $userId = auth()->id();
        $count  = Notification::forUser($userId)->unread()->count();
        return response()->json(['unread_count' => $count]);
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function format(Notification $n): array
    {
        return [
            'id'         => $n->id,
            'type'       => $n->type,
            'title'      => $n->title,
            'body'       => $n->body,
            'icon'       => $n->icon,
            'color'      => $n->color,
            'action_url' => $n->action_url,
            'read'       => $n->isRead(),
            'time_ago'   => $n->created_at->diffForHumans(),
            'created_at' => $n->created_at->format('d/m/Y H:i'),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TiketChat;
use App\Models\TiketGangguan;
use App\Models\Pelanggan;
use App\Models\Teknisi;
use Illuminate\Http\Request;

class ChatInboxController extends Controller
{
    /**
     * GET /chat-inbox
     * Ambil preview pesan terbaru dari tiket yang relevan untuk user ini
     * — dikelompokkan per tiket, urut dari pesan terbaru
     */
    public function index()
    {
        $user   = auth()->user();
        $userId = $user->id;
        $role   = $user->role->name ?? 'Pelanggan';

        // Tentukan tiket yang boleh dilihat user ini
        $ticketQuery = TiketGangguan::query();

        if (in_array($role, ['Admin', 'Manajer'])) {
            // Admin/Manajer: semua tiket
        } elseif ($role === 'Teknisi') {
            $teknisi = Teknisi::where('id_user', $userId)->first();
            $ticketQuery->where('id_teknisi', $teknisi?->id_teknisi ?? 0);
        } else {
            // Pelanggan: tiket milik sendiri
            $pelanggan = Pelanggan::where('id_user', $userId)->first();
            $ticketQuery->where('id_pelanggan', $pelanggan?->id_pelanggan ?? 0);
        }

        $tiketIds = $ticketQuery->pluck('id_tiket');

        // Ambil 1 pesan terbaru per tiket, hanya tiket yang punya pesan
        // dan pesan bukan dari user sendiri (supaya inbox = pesan dari orang lain)
        $chats = TiketChat::with(['ticket.pelanggan', 'user'])
            ->whereIn('id_tiket', $tiketIds)
            ->where('id_user', '!=', $userId)   // pesan dari orang lain
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('id_tiket')  // 1 pesan terbaru per tiket
            ->take(20)
            ->values();

        // Hitung total unread (pesan dari orang lain yang belum dibaca oleh user ini)
        $unreadCount = TiketChat::whereIn('id_tiket', $tiketIds)
            ->where('id_user', '!=', $userId)
            ->whereNull('read_at')
            ->count();

        $formatted = $chats->map(fn($c) => $this->format($c, $userId));

        return response()->json([
            'chats'        => $formatted,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * POST /chat-inbox/read-tiket/{id_tiket}
     * Tandai semua pesan di tiket tertentu sebagai sudah dibaca
     */
    public function readTiket(int $idTiket)
    {
        $userId = auth()->id();

        TiketChat::where('id_tiket', $idTiket)
            ->where('id_user', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * POST /chat-inbox/read-all
     * Tandai semua pesan sebagai dibaca
     */
    public function readAll()
    {
        $user   = auth()->user();
        $userId = $user->id;
        $role   = $user->role->name ?? 'Pelanggan';

        $ticketQuery = TiketGangguan::query();
        if (in_array($role, ['Admin', 'Manajer'])) {
            // semua
        } elseif ($role === 'Teknisi') {
            $teknisi = Teknisi::where('id_user', $userId)->first();
            $ticketQuery->where('id_teknisi', $teknisi?->id_teknisi ?? 0);
        } else {
            $pelanggan = Pelanggan::where('id_user', $userId)->first();
            $ticketQuery->where('id_pelanggan', $pelanggan?->id_pelanggan ?? 0);
        }

        $tiketIds = $ticketQuery->pluck('id_tiket');

        TiketChat::whereIn('id_tiket', $tiketIds)
            ->where('id_user', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function format(TiketChat $c, int $userId): array
    {
        $ticket  = $c->ticket;
        $sender  = $c->user;
        $preview = $c->message
            ? (strlen($c->message) > 60 ? substr($c->message, 0, 60) . '…' : $c->message)
            : '📷 Gambar';

        return [
            'id'            => $c->id,
            'id_tiket'      => $c->id_tiket,
            'kode_tiket'    => $ticket?->kode_tiket ?? '#',
            'pelanggan'     => $ticket?->pelanggan?->nama_pelanggan ?? 'Pelanggan',
            'sender_name'   => $sender?->name ?? 'Unknown',
            'preview'       => $preview,
            'has_image'     => !empty($c->image_path),
            'unread'        => is_null($c->read_at),
            'time_ago'      => $c->created_at->diffForHumans(),
            'time_fmt'      => $c->created_at->format('H:i'),
            'tiket_url'     => route('tiket.index'),
        ];
    }
}

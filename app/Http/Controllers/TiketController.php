<?php

namespace App\Http\Controllers;

use App\Models\TiketGangguan;
use App\Models\Pelanggan;
use App\Models\Teknisi;
use App\Models\User;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class TiketController extends Controller
{
    private function getUserContext()
    {
        $user = auth()->user();
        $roleName = $user->role ? $user->role->name : 'Pelanggan';

        $isAdmin = in_array($roleName, ['Admin', 'Manajer']);
        $isTeknisi = $roleName === 'Teknisi';
        $isPelanggan = $roleName === 'Pelanggan';

        $pelanggan = null;
        $teknisi = null;

        if ($isPelanggan) {
            $pelanggan = Pelanggan::where('id_user', $user->id)->first();
            
            // Self-healing customer creation:
            // "pelanggan bisa buat tiket otomatis terbuat ya pelanggan nya kalo buat tiket"
            if (!$pelanggan) {
                // Generate a custom customer code starting with REG (Registration)
                $customerCode = 'REG-' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 4));
                $pelanggan = Pelanggan::create([
                    'id_user' => $user->id,
                    'kode_pelanggan' => $customerCode,
                    'nama_pelanggan' => $user->name,
                    'no_wa' => $user->no_wa ?? $user->no_hp ?? '',
                    'alamat' => 'Alamat belum diisi (Otomatis dibuat saat buat tiket)',
                    'prioritas_label' => 'Low',
                    'is_active' => true
                ]);
            }
        } elseif ($isTeknisi) {
            $teknisi = Teknisi::where('id_user', $user->id)->first();
        }

        return [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'isTeknisi' => $isTeknisi,
            'isPelanggan' => $isPelanggan,
            'profilePelanggan' => $pelanggan,
            'profileTeknisi' => $teknisi,
        ];
    }

    public function index()
    {
        $ctx = $this->getUserContext();
        
        $allTeknisi = [];
        if ($ctx['isAdmin']) {
            $tiket = TiketGangguan::with(['pelanggan', 'teknisi'])->latest()->get();
            $allTeknisi = Teknisi::where('is_active', true)->get();
        } elseif ($ctx['isTeknisi']) {
            $teknisiId = $ctx['profileTeknisi'] ? $ctx['profileTeknisi']->id_teknisi : 0;
            $tiket = TiketGangguan::with(['pelanggan', 'teknisi'])
                ->where('id_teknisi', $teknisiId)
                ->latest()
                ->get();
        } else { // Pelanggan
            $pelangganId = $ctx['profilePelanggan'] ? $ctx['profilePelanggan']->id_pelanggan : 0;
            $tiket = TiketGangguan::with(['pelanggan', 'teknisi'])
                ->where('id_pelanggan', $pelangganId)
                ->latest()
                ->get();
        }

        return view('content.tiket.index', array_merge(compact('tiket', 'allTeknisi'), $ctx));
    }

    public function create()
    {
        $ctx = $this->getUserContext();
        
        if ($ctx['isPelanggan']) {
            // Customers only see themselves pre-selected
            $pelanggan = [$ctx['profilePelanggan']];
            $teknisi = [];
        } else {
            $pelanggan = Pelanggan::all();
            $teknisi = Teknisi::where('is_active', true)->get();
        }

        return view('content.tiket.create', array_merge(compact('pelanggan', 'teknisi'), $ctx));
    }

    public function store(Request $request)
    {
        $ctx = $this->getUserContext();
        
        if ($ctx['isPelanggan']) {
            $id_pelanggan = $ctx['profilePelanggan']->id_pelanggan;
            $prioritas = 'Low'; // Default priority for new customer tickets
            $id_teknisi = null;
        } else {
            $request->validate([
                'id_pelanggan' => 'required',
                'prioritas' => 'required',
            ]);
            $id_pelanggan = $request->id_pelanggan;
            $prioritas = $request->prioritas;
            $id_teknisi = $request->id_teknisi;
        }

        $request->validate([
            'keluhan' => 'required',
        ]);

        $tiket = TiketGangguan::create([
            'kode_tiket' => 'TKT-' . date('YmdHis'),
            'id_pelanggan' => $id_pelanggan,
            'prioritas' => $prioritas,
            'keluhan' => $request->keluhan,
            'id_teknisi' => $id_teknisi,
            'status' => 'Open'
        ]);

        // ── Kirim notifikasi ke semua Admin & Manajer ──────────────────────────
        $namaPelanggan = optional(Pelanggan::find($id_pelanggan))->nama_pelanggan ?? 'Pelanggan';
        NotificationHelper::sendToRole('Admin', 'tiket_baru', 'Tiket Gangguan Baru',
            "Tiket #{$tiket->kode_tiket} dari {$namaPelanggan}: {$request->keluhan}",
            ['icon' => 'bx-error-circle', 'color' => 'danger', 'action_url' => route('tiket.index')]
        );
        NotificationHelper::sendToRole('Manajer', 'tiket_baru', 'Tiket Gangguan Baru',
            "Tiket #{$tiket->kode_tiket} dari {$namaPelanggan}: {$request->keluhan}",
            ['icon' => 'bx-error-circle', 'color' => 'danger', 'action_url' => route('tiket.index')]
        );
        // ── Notifikasi ke Teknisi yang di-assign (jika ada) ───────────────────
        if ($id_teknisi) {
            $teknisiUser = Teknisi::find($id_teknisi);
            if ($teknisiUser && $teknisiUser->id_user) {
                NotificationHelper::send($teknisiUser->id_user, 'tiket_baru', 'Tiket Baru Ditugaskan',
                    "Anda mendapat tiket #{$tiket->kode_tiket} dari {$namaPelanggan}.",
                    ['icon' => 'bx-error-circle', 'color' => 'warning', 'action_url' => route('tiket.index')]
                );
            }
        }

        return redirect()->route('tiket.index')->with('success', 'Tiket berhasil dibuat');
    }

    public function updateStatus(Request $request, TiketGangguan $tiket)
    {
        $ctx = $this->getUserContext();
        
        // Authorization check: Customer cannot change ticket status
        if ($ctx['isPelanggan']) {
            return back()->with('error', 'Pelanggan tidak dapat mengubah status tiket.');
        }

        if ($ctx['isTeknisi'] && $tiket->id_teknisi !== $ctx['profileTeknisi']->id_teknisi) {
            return back()->with('error', 'Anda tidak ditugaskan untuk tiket ini.');
        }

        $tiket->update([
            'status' => $request->status,
            'closed_at' => $request->status == 'Closed' ? now() : $tiket->closed_at
        ]);
        return back()->with('success', 'Status tiket berhasil diupdate');
    }

    public function assignTeknisi(Request $request, TiketGangguan $tiket)
    {
        $ctx = $this->getUserContext();
        if (!$ctx['isAdmin']) {
            return back()->with('error', 'Hanya admin yang dapat meng-assign teknisi.');
        }

        $request->validate([
            'id_teknisi' => 'nullable|exists:teknisi,id_teknisi'
        ]);

        $tiket->update([
            'id_teknisi' => $request->id_teknisi
        ]);

        return back()->with('success', 'Teknisi berhasil ditugaskan.');
    }

    public function destroy(TiketGangguan $tiket)
    {
        $ctx = $this->getUserContext();
        if (!$ctx['isAdmin']) {
            return back()->with('error', 'Hanya admin yang dapat menghapus tiket.');
        }
        $tiket->delete();
        return redirect()->route('tiket.index')->with('success', 'Tiket berhasil dihapus');
    }

    public function getChats(TiketGangguan $tiket)
    {
        $ctx = $this->getUserContext();

        // Authorization check
        if ($ctx['isPelanggan'] && $tiket->id_pelanggan !== $ctx['profilePelanggan']->id_pelanggan) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        if ($ctx['isTeknisi'] && $tiket->id_teknisi !== $ctx['profileTeknisi']->id_teknisi) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $chats = \App\Models\TiketChat::with('user.role')
            ->where('id_tiket', $tiket->id_tiket)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'user_id' => $chat->id_user,
                    'user_name' => $chat->user->name,
                    'role' => $chat->user->role ? $chat->user->role->name : 'Staff',
                    'message' => $chat->message,
                    'image_url' => $chat->image_path ? asset('storage/' . $chat->image_path) : null,
                    'time' => $chat->created_at->format('H:i'),
                    'is_me' => $chat->id_user === auth()->id()
                ];
            });

        return response()->json($chats);
    }

    public function sendChat(Request $request, TiketGangguan $tiket)
    {
        $ctx = $this->getUserContext();

        // Authorization check
        if ($ctx['isPelanggan'] && $tiket->id_pelanggan !== $ctx['profilePelanggan']->id_pelanggan) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        if ($ctx['isTeknisi'] && $tiket->id_teknisi !== $ctx['profileTeknisi']->id_teknisi) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        if (!$request->message && !$request->hasFile('image')) {
            return response()->json(['error' => 'Pesan atau gambar harus diisi.'], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // Manual validation: Size (Max 5MB)
            if ($file->getSize() > 5 * 1024 * 1024) {
                return response()->json(['error' => 'Ukuran gambar tidak boleh lebih dari 5MB.'], 422);
            }
            
            // Manual validation: File Extension
            $ext = strtolower($file->getClientOriginalExtension());
            $allowed = ['jpeg', 'png', 'jpg', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) {
                return response()->json(['error' => 'Format gambar harus jpeg, png, jpg, gif, atau webp.'], 422);
            }

            $fileName = time() . '_' . uniqid() . '.' . $ext;
            
            // Create target directory if it does not exist
            $targetDir = storage_path('app/public/chats');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0775, true);
            }
            
            // Move file using native moving which does not trigger MIME-type guessing
            $file->move($targetDir, $fileName);
            $imagePath = 'chats/' . $fileName;
        }

        $chat = \App\Models\TiketChat::create([
            'id_tiket'   => $tiket->id_tiket,
            'id_user'    => auth()->id(),
            'message'    => $request->message ?? '',
            'image_path' => $imagePath
        ]);

        $chat->load('user.role');

        // ── Kirim notifikasi in-app ke pihak lain di tiket ini ────────────────
        try {
            $senderName = $chat->user->name;
            $preview    = $request->message
                ? (strlen($request->message) > 50 ? substr($request->message, 0, 50) . '…' : $request->message)
                : '📷 Gambar';
            $notifBody  = "{$senderName}: {$preview}";
            $notifOpts  = [
                'icon'       => 'bx-chat',
                'color'      => 'info',
                'action_url' => route('tiket.index'),
            ];

            if ($ctx['isPelanggan']) {
                // Pelanggan kirim pesan → Admin & Manajer dikasih tahu
                NotificationHelper::sendToRole('Admin', 'chat_baru',
                    "💬 Pesan Tiket #{$tiket->kode_tiket}", $notifBody, $notifOpts);
                NotificationHelper::sendToRole('Manajer', 'chat_baru',
                    "💬 Pesan Tiket #{$tiket->kode_tiket}", $notifBody, $notifOpts);
                // Teknisi yang di-assign
                if ($tiket->id_teknisi) {
                    $tek = Teknisi::find($tiket->id_teknisi);
                    if ($tek?->id_user) {
                        NotificationHelper::send($tek->id_user, 'chat_baru',
                            "💬 Pesan Tiket #{$tiket->kode_tiket}", $notifBody, $notifOpts);
                    }
                }
            } elseif ($ctx['isTeknisi']) {
                // Teknisi kirim pesan → Admin tahu
                NotificationHelper::sendToRole('Admin', 'chat_baru',
                    "💬 Pesan Tiket #{$tiket->kode_tiket}", $notifBody, $notifOpts);
                // Pelanggan tahu
                if ($tiket->pelanggan?->id_user) {
                    NotificationHelper::send($tiket->pelanggan->id_user, 'chat_baru',
                        "💬 Pesan Tiket #{$tiket->kode_tiket}", $notifBody, $notifOpts);
                }
            } else {
                // Admin/Manajer kirim → Teknisi & Pelanggan tahu
                if ($tiket->id_teknisi) {
                    $tek = Teknisi::find($tiket->id_teknisi);
                    if ($tek?->id_user) {
                        NotificationHelper::send($tek->id_user, 'chat_baru',
                            "💬 Pesan Tiket #{$tiket->kode_tiket}", $notifBody, $notifOpts);
                    }
                }
                if ($tiket->pelanggan?->id_user) {
                    NotificationHelper::send($tiket->pelanggan->id_user, 'chat_baru',
                        "💬 Pesan Tiket #{$tiket->kode_tiket}", $notifBody, $notifOpts);
                }
            }
        } catch (\Exception $e) {
            // Jangan sampai notif gagal merusak pengiriman pesan
            \Illuminate\Support\Facades\Log::warning('Chat notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'chat'   => [
                'id'        => $chat->id,
                'user_id'   => $chat->id_user,
                'user_name' => $chat->user->name,
                'role'      => $chat->user->role ? $chat->user->role->name : 'Staff',
                'message'   => $chat->message,
                'image_url' => $chat->image_path ? asset('storage/' . $chat->image_path) : null,
                'time'      => $chat->created_at->format('H:i'),
                'is_me'     => true
            ]
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\PackageUpgrade;
use App\Models\Setting;
use App\Models\Router;
use App\Services\MikrotikService;
use App\Services\WhatsappClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UpgradePaketController extends Controller
{
    protected $mikrotikService;

    public function __construct(MikrotikService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $roleName = $user->role ? $user->role->name : 'Pelanggan';
        $isPelanggan = ($roleName === 'Pelanggan' || $user->id_role == 4);

        $packages = $this->getPackages();

        if ($isPelanggan) {
            $pelanggan = Pelanggan::where('id_user', $user->id)->first();
            if (!$pelanggan) {
                return view('content.upgrade-paket.no-profile');
            }

            $upgrades = PackageUpgrade::where('id_pelanggan', $pelanggan->id_pelanggan)
                ->with('tagihan')
                ->latest()
                ->get();

            $pendingUpgrade = PackageUpgrade::where('id_pelanggan', $pelanggan->id_pelanggan)
                ->where('status', 'pending')
                ->first();

            return view('content.upgrade-paket.index', compact('pelanggan', 'upgrades', 'pendingUpgrade', 'packages'));
        }

        // Admin view
        $query = PackageUpgrade::with('pelanggan.user', 'tagihan');
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('pelanggan', function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('kode_pelanggan', 'like', "%{$search}%");
            });
        }

        $upgrades = $query->latest()->paginate(20);
        $allPelanggan = Pelanggan::where('is_active', true)->orderBy('nama_pelanggan')->get();

        return view('content.upgrade-paket.admin-index', compact('upgrades', 'allPelanggan', 'packages'));
    }

    public function requestUpgrade(Request $request)
    {
        $user = Auth::user();
        $pelanggan = Pelanggan::where('id_user', $user->id)->firstOrFail();

        // Check if there is already a pending upgrade
        $existing = PackageUpgrade::where('id_pelanggan', $pelanggan->id_pelanggan)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('error', 'Anda sudah memiliki pengajuan upgrade paket yang sedang diproses.');
        }

        $request->validate([
            'paket_baru' => 'required|string',
        ]);

        $packages = $this->getPackages();
        $paketBaru = $request->paket_baru;

        if (!array_key_exists($paketBaru, $packages)) {
            return back()->with('error', 'Paket yang dipilih tidak valid.');
        }

        $hargaBaru = $packages[$paketBaru];

        if ($pelanggan->paket === $paketBaru) {
            return back()->with('error', 'Paket baru harus berbeda dengan paket Anda saat ini.');
        }

        // Create separate Tagihan for upgrade
        $tagihan = Tagihan::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'bulan' => now()->month,
            'tahun' => now()->year,
            'jumlah' => $hargaBaru,
            'status' => 'unpaid',
            'catatan_admin' => "Upgrade Paket: " . ($pelanggan->paket ?: 'None') . " ke " . $paketBaru,
        ]);

        // Create PackageUpgrade request
        $upgrade = PackageUpgrade::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_tagihan' => $tagihan->id_tagihan,
            'paket_lama' => $pelanggan->paket ?: 'None',
            'harga_lama' => $pelanggan->harga_layanan ?: 0,
            'paket_baru' => $paketBaru,
            'harga_baru' => $hargaBaru,
            'status' => 'pending',
        ]);

        // Activity Log
        try {
            \App\Helpers\ActivityLogger::log(
                "Pelanggan {$pelanggan->nama_pelanggan} mengajukan upgrade paket ke {$paketBaru}",
                'tagihan'
            );
        } catch (\Exception $e) {
            Log::error("Failed to log activity: " . $e->getMessage());
        }

        // WhatsApp Notification
        try {
            $waClient = new WhatsappClient();

            // To customer
            if ($pelanggan->no_wa && $pelanggan->wa_active && Setting::get('wa_billing_notification_enabled', '1') == '1') {
                $custMsg = "🔔 *PENGAJUAN UPGRADE PAKET WIFI*\n\n";
                $custMsg .= "Halo *" . $pelanggan->nama_pelanggan . "*,\n";
                $custMsg .= "Pengajuan upgrade paket Anda telah berhasil dibuat:\n";
                $custMsg .= "• Paket Saat Ini: *" . ($pelanggan->paket ?: 'None') . "*\n";
                $custMsg .= "• Paket Baru: *" . $paketBaru . "*\n";
                $custMsg .= "• Biaya Upgrade: *Rp " . number_format($hargaBaru, 0, ',', '.') . "*\n\n";
                $custMsg .= "Silakan lakukan pembayaran dan unggah bukti transfer pada menu *Daftar Tagihan* untuk memproses upgrade nggih.";
                $waClient->sendMessage($pelanggan->no_wa, ['text' => $custMsg], true);
            }

            // To admin
            $adminNum = Setting::get('wa_admin_number'); 
            if (empty($adminNum)) {
                $adminNum = env('WHATSAPP_ADMIN_NUMBER');
            }
            if ($adminNum) {
                $adminMsg = "🔔 *PENGAJUAN UPGRADE PAKET BARU*\n\n";
                $adminMsg .= "Pelanggan: *" . $pelanggan->nama_pelanggan . "* (" . $pelanggan->kode_pelanggan . ")\n";
                $adminMsg .= "Upgrade: *" . ($pelanggan->paket ?: 'None') . "* ➔ *" . $paketBaru . "*\n";
                $adminMsg .= "Total Tagihan: *Rp " . number_format($hargaBaru, 0, ',', '.') . "*\n";
                $adminMsg .= "Status: *Menunggu Pembayaran*\n";
                $waClient->sendMessage($adminNum, ['text' => $adminMsg], true);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp notification on upgrade request: " . $e->getMessage());
        }

        return redirect()->route('upgrade-paket.index')->with('success', 'Pengajuan upgrade paket berhasil dibuat. Silakan lakukan pembayaran tagihan upgrade untuk mengaktifkan paket.');
    }

    public function adminUpgrade(Request $request)
    {
        if (!in_array(Auth::user()->id_role, [1, 2])) {
            abort(403);
        }

        $request->validate([
            'id_pelanggan' => 'required|exists:pelanggan,id_pelanggan',
            'paket_baru' => 'required|string',
        ]);

        $pelanggan = Pelanggan::findOrFail($request->id_pelanggan);
        $packages = $this->getPackages();
        $paketBaru = $request->paket_baru;

        if (!array_key_exists($paketBaru, $packages)) {
            return back()->with('error', 'Paket yang dipilih tidak valid.');
        }

        $hargaBaru = $packages[$paketBaru];
        $paketLama = $pelanggan->paket ?: 'None';
        $hargaLama = $pelanggan->harga_layanan ?: 0;

        // Create Paid Tagihan
        $tagihan = Tagihan::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'bulan' => now()->month,
            'tahun' => now()->year,
            'jumlah' => $hargaBaru,
            'status' => 'paid',
            'metode_pembayaran' => 'Direct Admin',
            'paid_at' => now(),
            'catatan_admin' => "Upgrade Paket Langsung oleh Admin: {$paketLama} ke {$paketBaru}",
        ]);

        // Create PackageUpgrade record
        PackageUpgrade::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_tagihan' => $tagihan->id_tagihan,
            'paket_lama' => $paketLama,
            'harga_lama' => $hargaLama,
            'paket_baru' => $paketBaru,
            'harga_baru' => $hargaBaru,
            'status' => 'completed',
        ]);

        // Update Pelanggan record
        $pelanggan->update([
            'paket' => $paketBaru,
            'harga_layanan' => $hargaBaru,
            'is_active' => true,
        ]);

        // Sync to Mikrotik
        if ($pelanggan->id_router) {
            try {
                $username = $pelanggan->mikrotik_username ?: $pelanggan->kode_pelanggan;
                $this->mikrotikService->setSecretStatus(
                    $pelanggan->router,
                    $username,
                    $pelanggan->mikrotik_type,
                    false,
                    $pelanggan->ip_address,
                    $pelanggan->paket
                );
            } catch (\Exception $e) {
                Log::error("Failed to sync upgraded package to Mikrotik: " . $e->getMessage());
            }
        }

        // WhatsApp notification
        try {
            $waClient = new WhatsappClient();
            if ($pelanggan->no_wa && $pelanggan->wa_active && Setting::get('wa_billing_notification_enabled', '1') == '1') {
                $custMsg = "🎉 *UPGRADE PAKET WIFI BERHASIL*\n\n";
                $custMsg .= "Halo *" . $pelanggan->nama_pelanggan . "*,\n";
                $custMsg .= "Paket WiFi Anda telah di-upgrade langsung oleh Admin:\n";
                $custMsg .= "• Paket Saat Ini: *" . $paketBaru . "*\n";
                $custMsg .= "• Biaya Layanan: *Rp " . number_format($hargaBaru, 0, ',', '.') . "/bulan*\n\n";
                $custMsg .= "Terima kasih nggih!";
                $waClient->sendMessage($pelanggan->no_wa, ['text' => $custMsg], true);
            }
        } catch (\Exception $e) {
            Log::error("WhatsApp Notification Direct Upgrade Error: " . $e->getMessage());
        }

        // Activity Log
        try {
            \App\Helpers\ActivityLogger::log(
                "Admin mengupgrade langsung paket pelanggan {$pelanggan->nama_pelanggan} ke {$paketBaru}",
                'tagihan'
            );
        } catch (\Exception $e) {
            Log::error("Failed to log activity: " . $e->getMessage());
        }

        return back()->with('success', 'Paket pelanggan berhasil di-upgrade langsung.');
    }

    public function cancelUpgrade(PackageUpgrade $upgrade)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->id_role, [1, 2]);
        $isOwner = $upgrade->pelanggan && $upgrade->pelanggan->id_user == $user->id;

        if (!$isAdmin && !$isOwner) {
            abort(403);
        }

        if ($upgrade->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan pending yang dapat dibatalkan.');
        }

        $upgrade->update(['status' => 'cancelled']);

        if ($upgrade->tagihan && $upgrade->tagihan->status !== 'paid') {
            $upgrade->tagihan->update(['status' => 'cancelled']);
        }

        // Activity Log
        try {
            $canceller = $isAdmin ? 'Admin' : 'Pelanggan';
            \App\Helpers\ActivityLogger::log(
                "{$canceller} membatalkan pengajuan upgrade paket pelanggan " . ($upgrade->pelanggan ? $upgrade->pelanggan->nama_pelanggan : 'Umum'),
                'tagihan'
            );
        } catch (\Exception $e) {
            Log::error("Failed to log activity: " . $e->getMessage());
        }

        return back()->with('success', 'Pengajuan upgrade paket berhasil dibatalkan.');
    }

    private function getPackages()
    {
        $packages = [
            'umum' => 100000,
            '100rb 3mb' => 100000,
            '120rb 8mb' => 120000,
            '130rb 12mb' => 130000,
            '150rb 20mb' => 150000,
            '200rb 35mb' => 200000,
        ];

        try {
            $dbPakets = Pelanggan::whereNotNull('paket')
                ->where('paket', '!=', '')
                ->distinct()
                ->pluck('paket')
                ->toArray();
            foreach ($dbPakets as $paket) {
                if (!array_key_exists($paket, $packages)) {
                    $packages[$paket] = $this->guessPrice($paket);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch pakets from database: " . $e->getMessage());
        }

        try {
            $routers = Router::all();
            foreach ($routers as $router) {
                $pppoeProfiles = $this->mikrotikService->getProfiles($router, 'pppoe');
                if (is_array($pppoeProfiles)) {
                    foreach ($pppoeProfiles as $p) {
                        if (isset($p['name'])) {
                            $name = $p['name'];
                            if (!array_key_exists($name, $packages) && !in_array($name, ['default', 'default-encryption'])) {
                                $packages[$name] = $this->guessPrice($name);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch profiles from Mikrotik: " . $e->getMessage());
        }

        return $packages;
    }

    private function guessPrice($profileName)
    {
        $lower = strtolower($profileName);
        if ($lower === 'umum') return 100000;
        if (str_contains($lower, '100')) return 100000;
        if (str_contains($lower, '120')) return 120000;
        if (str_contains($lower, '130')) return 130000;
        if (str_contains($lower, '150')) return 150000;
        if (str_contains($lower, '200')) return 200000;
        if (str_contains($lower, '3mb') || str_contains($lower, '3m')) return 100000;
        if (str_contains($lower, '8mb') || str_contains($lower, '8m')) return 120000;
        if (str_contains($lower, '12mb') || str_contains($lower, '12m')) return 130000;
        if (str_contains($lower, '20mb') || str_contains($lower, '20m')) return 150000;
        if (str_contains($lower, '35mb') || str_contains($lower, '35m')) return 200000;
        return 150000;
    }
}

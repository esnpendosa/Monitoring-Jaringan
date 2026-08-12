<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\User;
use App\Services\WhatsappClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PublicRegistrationController extends Controller
{
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
        return 150000; // default fallback price
    }

    public function showForm()
    {
        $packages = [
            'umum' => 'umum (Rp 100.000)',
            '100rb 3mb' => '100rb 3mb (Rp 100.000)',
            '120rb 8mb' => '120rb 8mb (Rp 120.000)',
            '130rb 12mb' => '130rb 12mb (Rp 130.000)',
            '150rb 20mb' => '150rb 20mb (Rp 150.000)',
            '200rb 35mb' => '200rb 35mb (Rp 200.000)',
        ];

        // 1. Ambil dari paket yang sudah digunakan di database
        try {
            $dbPakets = Pelanggan::whereNotNull('paket')
                ->where('paket', '!=', '')
                ->distinct()
                ->pluck('paket')
                ->toArray();
            foreach ($dbPakets as $paket) {
                if (!array_key_exists($paket, $packages)) {
                    $price = $this->guessPrice($paket);
                    $packages[$paket] = "$paket (Rp " . number_format($price, 0, ',', '.') . ")";
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch pakets from database: " . $e->getMessage());
        }

        // 2. Ambil profil dinamis dari Router MikroTik yang aktif
        try {
            $routers = \App\Models\Router::all();
            $mikrotikService = app(\App\Services\MikrotikService::class);
            foreach ($routers as $router) {
                // Get PPPoE Profiles
                $pppoeProfiles = $mikrotikService->getProfiles($router, 'pppoe');
                if (is_array($pppoeProfiles)) {
                    foreach ($pppoeProfiles as $p) {
                        if (isset($p['name'])) {
                            $name = $p['name'];
                            if (!array_key_exists($name, $packages) && !in_array($name, ['default', 'default-encryption'])) {
                                $price = $this->guessPrice($name);
                                $packages[$name] = "$name (Rp " . number_format($price, 0, ',', '.') . ")";
                            }
                        }
                    }
                }
                
                // Get Hotspot Profiles
                $hotspotProfiles = $mikrotikService->getProfiles($router, 'hotspot');
                if (is_array($hotspotProfiles)) {
                    foreach ($hotspotProfiles as $p) {
                        if (isset($p['name'])) {
                            $name = $p['name'];
                            if (!array_key_exists($name, $packages) && !in_array($name, ['default'])) {
                                $price = $this->guessPrice($name);
                                $packages[$name] = "$name (Rp " . number_format($price, 0, ',', '.') . ")";
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch profiles from Mikrotik: " . $e->getMessage());
        }

        return view('content.public.register', compact('packages'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'no_wa' => 'required|string|max:20',
            'alamat' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'paket' => 'required|string',
            'foto_rumah' => 'nullable|file|max:5120',
        ]);

        // Generate unique REG code
        $code = 'REG' . rand(10000, 99999);
        while (Pelanggan::where('kode_pelanggan', $code)->exists()) {
            $code = 'REG' . rand(10000, 99999);
        }

        $harga = $this->guessPrice($validated['paket']);

        $fotoPath = null;
        if ($request->hasFile('foto_rumah')) {
            $file = $request->file('foto_rumah');
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedExtensions = ['jpeg', 'png', 'jpg', 'gif'];
            if (in_array($extension, $allowedExtensions)) {
                $filename = time() . '_' . uniqid() . '.' . $extension;
                $targetDir = storage_path('app/public/foto_rumah');
                if (!file_exists($targetDir)) {
                    @mkdir($targetDir, 0755, true);
                    @chmod($targetDir, 0755);
                }
                $file->move($targetDir, $filename);
                @chmod($targetDir . '/' . $filename, 0644);
                $fotoPath = 'foto_rumah/' . $filename;
            }
        }

        // Create Pelanggan
        $pelanggan = Pelanggan::create([
            'kode_pelanggan' => $code,
            'nama_pelanggan' => $validated['nama_pelanggan'],
            'no_wa' => $validated['no_wa'],
            'alamat' => $validated['alamat'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'paket' => $validated['paket'],
            'harga_layanan' => $harga,
            'mikrotik_type' => 'pppoe',
            'billing_date' => 1,
            'is_active' => 0, // Pending / Non-aktif
            'wa_active' => 1,
            'foto_rumah' => $fotoPath,
        ]);

        // Create disabled User
        $user = User::create([
            'name' => $pelanggan->nama_pelanggan,
            'email' => strtolower($code) . '@rtrwnet.com',
            'username_email' => strtolower($code),
            'password' => Hash::make('12345678'),
            'id_role' => 4, // Role Pelanggan
            'is_active' => false
        ]);

        $pelanggan->update(['id_user' => $user->id]);

        // Notify Admin on WhatsApp
        try {
            $whatsapp = app(WhatsappClient::class);
            $adminNum = env('WHATSAPP_ADMIN_NUMBER');
            if ($adminNum) {
                $targetJid = str_contains($adminNum, '@') ? $adminNum : $adminNum . "@s.whatsapp.net";
                
                $msg = "🔔 *REGISTRASI WIFI BARU*\n";
                $msg .= "--------------------------\n";
                $msg .= "Kode: " . $code . "\n";
                $msg .= "Nama: " . $pelanggan->nama_pelanggan . "\n";
                $msg .= "No. WA: " . $pelanggan->no_wa . "\n";
                $msg .= "Alamat: " . $pelanggan->alamat . "\n";
                $msg .= "Paket: " . $pelanggan->paket . "\n";
                $msg .= "Lokasi: https://www.google.com/maps?q=" . $pelanggan->latitude . "," . $pelanggan->longitude . "\n";
                $msg .= "--------------------------\n";
                $msg .= "Mohon segera lakukan survei lokasi!";

                $whatsapp->sendMessage($targetJid, ['text' => $msg]);
            }
        } catch (\Exception $e) {
            Log::error("Public registration WA notification failed: " . $e->getMessage());
        }

        return redirect()->route('public.register.success', ['code' => $code]);
    }

    public function success(Request $request)
    {
        $code = $request->query('code');
        $pelanggan = Pelanggan::where('kode_pelanggan', $code)->firstOrFail();

        // Get admin phone number (cleaned for direct WhatsApp chat API)
        $adminNum = env('WHATSAPP_ADMIN_NUMBER');
        if (str_contains($adminNum, '@')) {
            $adminNum = explode('@', $adminNum)[0];
        }
        $adminNum = preg_replace('/[^0-9]/', '', $adminNum);

        // Pre-filled message for customer to chat admin
        $text = urlencode("Halo Admin, saya baru saja melakukan registrasi pasang WiFi dengan kode: {$code}. Mohon segera diproses nggih.");
        $waUrl = "https://wa.me/{$adminNum}?text={$text}";

        return view('content.public.success', compact('pelanggan', 'waUrl'));
    }
}

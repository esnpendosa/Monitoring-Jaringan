<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnablePaidServices extends Command
{
    protected $signature = 'billing:enable-paid {--force : Bypass cache checks}';
    protected $description = 'Enable WiFi services for customers who have paid their bill';

    public function handle(\App\Services\MikrotikService $mikrotikService)
    {
        set_time_limit(0);
        $isForce = $this->option('force');

        if (!$isForce) {
            $cacheKey = 'enable_paid_services_last_run';
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                $this->info('EnablePaidServices has already run in the last 2 minutes. Exiting to prevent duplication.');
                return;
            }
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, 120);
        }

        $this->info('Memeriksa tagihan yang sudah dibayar untuk mengaktifkan layanan...');

        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Cari pelanggan yang terisolir tapi sudah tidak punya tunggakan lagi (bulan ini atau sebelumnya)
        // Dan pastikan mereka memang memiliki tagihan aktif bulan ini
        $paidPelanggan = \App\Models\Pelanggan::where('is_isolated', true)
            ->whereHas('tagihan', function ($query) use ($currentMonth, $currentYear) {
                $query->where('tahun', $currentYear)
                      ->where('bulan', $currentMonth);
            })
            ->whereDoesntHave('tagihan', function ($query) use ($currentMonth, $currentYear) {
                $query->where('status', 'unpaid')
                      ->where(function($q) use ($currentMonth, $currentYear) {
                          $q->where('tahun', '<', $currentYear)
                            ->orWhere(function($sq) use ($currentMonth, $currentYear) {
                                $sq->where('tahun', $currentYear)
                                   ->where('bulan', '<=', $currentMonth);
                            });
                      });
            })->get();

        if ($paidPelanggan->isEmpty()) {
            $this->info('Tidak ada pelanggan yang perlu diaktifkan.');
            return;
        }

        $waClient = new \App\Services\WhatsappClient();

        foreach ($paidPelanggan as $p) {
            $username = $p->mikrotik_username ?: $p->kode_pelanggan;

            // Selalu tandai aktif dan hilangkan status terisolir di DB terlebih dahulu
            $p->update(['is_active' => true, 'is_isolated' => false]);

            if ($p->router && $username) {
                $this->info("Sinkronisasi MikroTik untuk: {$p->nama_pelanggan} ({$username})");

                try {
                    $success = $mikrotikService->setSecretStatus($p->router, $username, $p->mikrotik_type, false, $p->ip_address);

                    if ($success) {
                        $this->info("  ✓ Berhasil disinkronkan ke MikroTik.");

                        // Kirim notifikasi WA
                        if ($p->no_wa) {
                            try {
                                $monthName = date('F', mktime(0, 0, 0, $currentMonth, 10));
                                $message = "✅ *INTERNET AKTIF KEMBALI*\n\n";
                                $message .= "Halo *{$p->nama_pelanggan}*,\n";
                                $message .= "Pembayaran tagihan bulan *{$monthName} {$currentYear}* Anda telah dikonfirmasi.\n\n";
                                $message .= "Internet Anda sekarang sudah *aktif kembali*. 🎉\n\n";
                                $message .= "Terima kasih telah berlangganan layanan kami!";
                                $waClient->sendMessage($p->no_wa, ['text' => $message], true);
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Gagal kirim notif aktivasi: ' . $e->getMessage());
                            }
                        }
                    } else {
                        $this->warn("  ⚠ MikroTik sync gagal untuk {$p->nama_pelanggan}. Status DB sudah aktif, router perlu sinkronisasi manual.");
                        \Illuminate\Support\Facades\Log::warning("EnablePaidServices: MikroTik sync failed for {$p->kode_pelanggan} ({$username}). DB marked active, router not updated.");
                    }
                } catch (\Exception $e) {
                    $this->error("  ✗ Exception MikroTik untuk {$p->nama_pelanggan}: " . $e->getMessage());
                    \Illuminate\Support\Facades\Log::error("EnablePaidServices: Exception for {$p->kode_pelanggan}: " . $e->getMessage());
                }
            } else {
                $this->info("Diaktifkan di DB (tanpa router): {$p->nama_pelanggan}");
            }
        }

        $this->info('Proses aktivasi selesai. Total: ' . $paidPelanggan->count() . ' pelanggan.');
    }
}

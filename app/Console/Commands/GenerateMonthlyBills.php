<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateMonthlyBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly bills for all active customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(0);
        $enabled = \App\Models\Setting::get('billing_auto_generate_enabled', '1');
        if ($enabled != '1') {
            $this->info('Auto bill generation is disabled in settings.');
            return;
        }

        $genDate = (int) \App\Models\Setting::get('billing_generate_date', '1');
        
        if (now()->day != $genDate) {
            $this->info("Today is " . now()->day . ". Bill generation is scheduled for day {$genDate}. Skipping...");
            return;
        }

        $this->info('Starting bill generation...');
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $pelanggans = \App\Models\Pelanggan::where('is_active', true)->get();
        $generatedCount = 0;

        $waClient = new \App\Services\WhatsappClient();
        foreach ($pelanggans as $p) {
            $exists = \App\Models\Tagihan::where('id_pelanggan', $p->id_pelanggan)
                ->where('bulan', $currentMonth)
                ->where('tahun', $currentYear)
                ->exists();

            if (!$exists && $p->harga_layanan > 0) {
                if ($p->isBulanGratis($currentMonth, $currentYear)) {
                    \App\Models\Tagihan::create([
                        'id_pelanggan' => $p->id_pelanggan,
                        'bulan' => $currentMonth,
                        'tahun' => $currentYear,
                        'jumlah' => 0,
                        'status' => 'paid',
                        'metode_pembayaran' => 'Bonus Gratis',
                        'paid_at' => now(),
                        'catatan_admin' => 'Bonus Gratis Pemasangan (2 Bulan Pertama)',
                    ]);
                    $generatedCount++;

                    // Kirim Notifikasi WA Promo Gratis
                    if ($p->no_wa && $p->wa_active && \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1') {
                        $monthName = date('F', mktime(0, 0, 0, $currentMonth, 10));
                        $message = "🎉 *PROMO BONUS GRATIS LAYANAN*\n\n";
                        $message .= "Halo *" . $p->kode_pelanggan . "* " . $p->nama_pelanggan . ",\n\n";
                        $message .= "Tagihan internet Anda untuk periode *" . $monthName . " " . $currentYear . "* telah terbit.\n\n";
                        $message .= "Status: *LUNAS (PROMO GRATIS)*\n";
                        $message .= "Jumlah Tagihan: *Rp 0*\n\n";
                        $message .= "Terima kasih telah memilih layanan internet kami! Nikmati koneksi Anda nggih.";
                        $waClient->sendMessage($p->no_wa, ['text' => $message], true);
                    }
                } else {
                    $tagihan = \App\Models\Tagihan::create([
                        'id_pelanggan' => $p->id_pelanggan,
                        'bulan' => $currentMonth,
                        'tahun' => $currentYear,
                        'jumlah' => $p->harga_layanan,
                        'status' => 'unpaid',
                    ]);
                    $generatedCount++;

                    // Kirim Notifikasi WA
                    if ($p->no_wa && $p->wa_active && \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1') {
                        $monthName = date('F', mktime(0, 0, 0, $currentMonth, 10));
                        $message = "🔔 *PEMBERITAHUAN TAGIHAN BARU*\n\n";
                        $message .= "Halo " . $p->nama_pelanggan . ",\n";
                        $message .= "Tagihan internet Anda untuk periode *" . $monthName . " " . $currentYear . "* telah terbit.\n\n";
                        $message .= "Jumlah: *Rp " . number_format($p->harga_layanan) . "*\n";
                        $message .= "Status: *BELUM BAYAR*\n\n";
                        $message .= "Silakan lakukan pembayaran sebelum tanggal " . \App\Models\Setting::get('billing_isolir_date', '10') . " agar layanan tidak terisolir otomatis.\n";
                        $message .= "Anda dapat mengecek detail tagihan dengan membalas pesan ini ketik: *Cek Tagihan*";

                        $waClient->sendMessage($p->no_wa, ['text' => $message], true);
                    }
                }
            }
        }

        $this->info("Successfully generated $generatedCount bills and sent notifications for $currentMonth/$currentYear.");
    }
}

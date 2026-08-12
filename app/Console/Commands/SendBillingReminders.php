<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\Pelanggan;
use App\Services\WhatsappClient;

class SendBillingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:remind {--force : Force send reminders ignoring scheduled date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminder for unpaid bills';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(0);
        $globalEnabled = Setting::get('wa_billing_notification_enabled', '1');
        if ($globalEnabled != '1') {
            $this->info('WhatsApp billing notifications are globally disabled. Skipping reminders.');
            return;
        }

        $enabled = Setting::get('billing_reminder_enabled', '1');
        if ($enabled != '1') {
            $this->info('Billing reminder is disabled in settings.');
            return;
        }

        $reminderDate = (int) Setting::get('billing_reminder_date', '5');
        
        if (!$this->option('force') && now()->day != $reminderDate) {
            $this->info("Today is " . now()->day . ". Reminder is scheduled for day {$reminderDate}. Skipping...");
            return;
        }

        $this->info('Starting billing reminders...');
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Get active customers with unpaid bills for the current month.
        // Split into two groups:
        //   (a) truly unpaid — no proof uploaded yet        → send normal reminder
        //   (b) proof uploaded, awaiting admin verification → send "sedang diverifikasi" notice
        $baseQuery = function ($query) use ($currentMonth, $currentYear) {
            $query->where('status', 'unpaid')
                  ->where('bulan', $currentMonth)
                  ->where('tahun', $currentYear);
        };

        $unpaidPelanggan = Pelanggan::where('is_active', true)
            ->whereHas('tagihan', $baseQuery)
            ->with(['tagihan' => function ($q) use ($currentMonth, $currentYear) {
                $q->where('status', 'unpaid')
                  ->where('bulan', $currentMonth)
                  ->where('tahun', $currentYear);
            }])->get();

        $waClient = new WhatsappClient();
        $sentCount = 0;
        $verifyCount = 0;

        foreach ($unpaidPelanggan as $p) {
            if (!$p->no_wa || !$p->wa_active || $p->tagihan->count() === 0) {
                continue;
            }

            $tagihan   = $p->tagihan->first();
            $monthName = date('F', mktime(0, 0, 0, $currentMonth, 10));

            // Pelanggan sudah upload bukti bayar tapi belum diverifikasi admin
            if (!empty($tagihan->bukti_bayar)) {
                $message  = "⏳ *KONFIRMASI PEMBAYARAN*\n\n";
                $message .= "Halo *" . $p->kode_pelanggan . "* " . $p->nama_pelanggan . ",\n\n";
                $message .= "Bukti pembayaran tagihan internet Anda untuk periode *" . $monthName . " " . $currentYear . "* sudah kami terima dan sedang dalam proses verifikasi admin.\n\n";
                $message .= "Mohon tunggu konfirmasi dari kami. Jika ada pertanyaan, ketik *Cek Tagihan*.\n\n";
                $message .= "Terima kasih atas kesabarannya.";

                try {
                    $waClient->sendMessage($p->no_wa, ['text' => $message], true);
                    $verifyCount++;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim notif verifikasi tagihan: ' . $e->getMessage());
                }
                continue;
            }

            // Pelanggan belum bayar dan belum upload bukti
            $message  = "🔔 *PENGINGAT TAGIHAN*\n\n";
            $message .= "Halo *" . $p->kode_pelanggan . "* " . $p->nama_pelanggan . ",\n\n";
            $message .= "Kami mengingatkan bahwa tagihan internet Anda untuk periode *" . $monthName . " " . $currentYear . "* sebesar *Rp " . number_format($tagihan->jumlah) . "* masih berstatus *BELUM BAYAR*.\n\n";
            $message .= "Mohon segera lakukan pembayaran sebelum tanggal jatuh tempo agar layanan internet Anda tidak terputus.\n\n";
            $message .= "Ketik *Cek Tagihan* untuk melihat detail pembayaran.\n\n";
            $message .= "Abaikan pesan ini jika Anda sudah melakukan pembayaran. Terima kasih.";

            try {
                $waClient->sendMessage($p->no_wa, ['text' => $message], true);
                $sentCount++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal kirim reminder tagihan: ' . $e->getMessage());
            }
        }

        $this->info("Successfully sent $sentCount reminders and $verifyCount verification notices for $currentMonth/$currentYear.");
    }
}

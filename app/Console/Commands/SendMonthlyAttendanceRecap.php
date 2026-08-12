<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\User;
use App\Models\Absensi;
use App\Services\WhatsappClient;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class SendMonthlyAttendanceRecap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:send-monthly-recap {--force : Force send the recap for a specific month and year} {--month= : Month to generate (1-12)} {--year= : Year to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the monthly employee attendance recap PDF via WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(0);

        // Gather recipient numbers
        $recipientPhones = [];

        // 1. Add target phone from setting if configured
        $settingTarget = Setting::get('wa_rekap_absensi_target');
        if (!empty($settingTarget)) {
            $recipientPhones[] = $settingTarget;
        }

        // 2. Add all Admin, Magang, and Teknisi users (exclude fachrozi/facrozi)
        $targetUsers = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['Admin', 'Magang', 'Teknisi']);
        })->whereNotNull('no_hp')
          ->where('no_hp', '!=', '')
          ->where('name', 'not like', '%facrozi%')
          ->where('name', 'not like', '%fachrozi%')
          ->get();

        foreach ($targetUsers as $tu) {
            $recipientPhones[] = $tu->no_hp;
        }

        // 3. Sanitize and de-duplicate numbers
        $uniquePhones = [];
        foreach ($recipientPhones as $phone) {
            $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);
            $cleaned = ltrim($cleaned, '+');
            if (str_starts_with($cleaned, '0')) {
                $cleaned = '62' . substr($cleaned, 1);
            }
            if (!empty($cleaned) && !in_array($cleaned, $uniquePhones)) {
                $uniquePhones[] = $cleaned;
            }
        }

        if (empty($uniquePhones)) {
            $this->error('No target phone numbers or staff numbers found.');
            return;
        }

        $this->info("Recipients list: " . implode(', ', $uniquePhones));

        // Determine month and year
        if ($this->option('force') && $this->option('month') && $this->option('year')) {
            $month = (int)$this->option('month');
            $year = (int)$this->option('year');
        } else {
            // Default to previous month
            $previousMonth = now()->subMonth();
            $month = $previousMonth->month;
            $year = $previousMonth->year;
        }

        $this->info("Generating employee attendance recap for {$month}/{$year}...");

        // Fetch users (exclude customers: id_role == 4 and fachrozi/facrozi)
        $allUsers = User::where('id_role', '!=', 4)
            ->where('name', 'not like', '%facrozi%')
            ->where('name', 'not like', '%fachrozi%')
            ->orderBy('name')
            ->get();

        if ($allUsers->isEmpty()) {
            $this->warn('No employees found to generate attendance recap.');
            return;
        }

        $startDate = Carbon::create($year, $month, 11)->subMonth()->startOfDay();
        $endDate = Carbon::create($year, $month, 10)->endOfDay();

        $startDateFormatted = $startDate->translatedFormat('d F Y');
        $endDateFormatted = $endDate->translatedFormat('d F Y');
        $periodeLabel = "{$startDateFormatted} s/d {$endDateFormatted}";

        $reportData = [];
        $batasMasukStr = Setting::get('absensi_batas_masuk', '08:00:00');
        $batasMasukTime = Carbon::parse($batasMasukStr);

        foreach ($allUsers as $u) {
            $uAbsensis = Absensi::where('user_id', $u->id)
                ->whereBetween('tgl', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();
            
            $hadir = $uAbsensis->whereIn('status_kehadiran', ['Hadir', 'Terlambat', 'Pulang Lebih Awal', 'Terlambat & Pulang Awal'])->count();
            
            $workDays = 0;
            $currentDate = $startDate->copy();
            $periodEndDate = $endDate->greaterThan(now()) ? now() : $endDate;

            while ($currentDate->lessThanOrEqualTo($periodEndDate)) {
                if (!$currentDate->isWeekend()) {
                    $workDays++;
                }
                $currentDate->addDay();
            }
            
            $alpha = max(0, $workDays - $hadir);

            $totalWorkingHours = 0;
            $totalLateMinutes = 0;

            foreach ($uAbsensis as $abs) {
                // hitung jam kerja
                if ($abs->jam_masuk && $abs->jam_pulang) {
                    try {
                        $masuk = Carbon::parse($abs->jam_masuk);
                        $pulang = Carbon::parse($abs->jam_pulang);
                        if ($pulang->greaterThan($masuk)) {
                            $totalWorkingHours += $pulang->diffInMinutes($masuk, true) / 60;
                        }
                    } catch (\Exception $e) {
                        Log::error("Error parsing attendance times for user {$u->id}: " . $e->getMessage());
                    }
                }

                // hitung jam telat
                if ($abs->jam_masuk) {
                    try {
                        $masuk = Carbon::parse($abs->jam_masuk);
                        if ($masuk->greaterThan($batasMasukTime)) {
                            $totalLateMinutes += $masuk->diffInMinutes($batasMasukTime, true);
                        }
                    } catch (\Exception $e) {
                        Log::error("Error parsing late hours for user {$u->id}: " . $e->getMessage());
                    }
                }
            }
            $totalWorkingHours = (int) round($totalWorkingHours);
            $totalLateHours = (int) round($totalLateMinutes / 60);

            $reportData[] = [
                'user' => $u,
                'hadir' => $hadir,
                'alpha' => $alpha,
                'jam_telat' => $totalLateHours,
                'total_jam_kerja' => $totalWorkingHours
            ];
        }

        // Indonesian Month Mapping
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $monthName = $monthNames[$month] ?? Carbon::create()->month($month)->translatedFormat('F');

        // Formatted Date for Signatures
        $reportDate = now()->translatedFormat('d F Y');

        // Generate PDF
        $pdf = Pdf::loadView('kepegawaian.absensi_pdf', compact('reportData', 'monthName', 'year', 'reportDate', 'periodeLabel'))
            ->setOption('isRemoteEnabled', true);
        $pdfContent = $pdf->output();

        $filename = "Rekap_Kehadiran_{$monthName}_{$year}.pdf";

        // Save a backup locally in storage
        try {
            $storagePath = 'rekap_absensi/' . $filename;
            $fullPath = storage_path('app/public/' . $storagePath);
            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }
            file_put_contents($fullPath, $pdfContent);
            $this->info("Local PDF copy saved at: " . $fullPath);
        } catch (\Exception $e) {
            Log::warning("SendMonthlyAttendanceRecap: Could not save copy of PDF to storage: " . $e->getMessage());
        }

        $caption = "Berikut adalah laporan *Rekapitulasi Kehadiran Pegawai* Rozitech Multimedia Indonesia untuk periode *{$periodeLabel}* dalam format PDF.";

        $waClient = new WhatsappClient();
        $sentCount = 0;

        foreach ($uniquePhones as $phone) {
            $this->info("Sending PDF to {$phone} via WhatsApp...");
            $success = $waClient->sendFile($phone, $pdfContent, $filename, 'application/pdf', $caption);
            if ($success) {
                $sentCount++;
                $this->info("  ✓ Successfully sent to {$phone}.");
            } else {
                $this->error("  ✗ Failed to send to {$phone}.");
            }
        }

        $this->info("Attendance recap sending completed. Sent to {$sentCount} of " . count($uniquePhones) . " recipients.");
    }
}

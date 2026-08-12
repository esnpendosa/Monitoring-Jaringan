<?php

namespace App\Services;

use App\Models\User;
use App\Models\Absensi;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FingerprintService
{
    protected $devices = [];

    public function __construct()
    {
        $devicesJson = Setting::get('fingerspot_devices');
        if ($devicesJson) {
            $this->devices = json_decode($devicesJson, true);
        }
    }

    public function getDevices(): array
    {
        return $this->devices;
    }

    /**
     * Pull attendance data from all registered fingerspot/solutions devices (if configured as cloud)
     */
    public function pullData(): array
    {
        $totalSynced = 0;
        $deviceResults = [];

        foreach ($this->devices as $device) {
            if (empty($device['url']) || str_contains($device['url'], 'iclock/cdata') || str_contains($device['url'], request()->getHost())) {
                $deviceResults[] = [
                    'name'    => $device['name'] ?? $device['sn'],
                    'sn'      => $device['sn'],
                    'status'  => 'ok',
                    'synced'  => 0,
                    'message' => 'Push ADMS Active (Automatic)'
                ];
                continue;
            }

            $result = [
                'name'   => $device['name'] ?? $device['sn'],
                'sn'     => $device['sn'],
                'status' => 'error',
                'synced' => 0,
                'error'  => null,
            ];

            try {
                $response = Http::timeout(15)->post($device['url'], [
                    'sn' => $device['sn'],
                    'sc' => $device['sc'] ?? '',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['success']) && $data['success'] === false) {
                        $result['error'] = $data['message'] ?? 'Unknown API error';
                    } else {
                        $logs = $data['data'] ?? (is_array($data) && !isset($data['success']) ? $data : []);
                        $synced = 0;
                        foreach ($logs as $log) {
                            $log['sn'] = $device['sn'];
                            $this->syncLog($log, $device['name'] ?? 'Solution X105');
                            $synced++;
                        }
                        $result['status'] = 'ok';
                        $result['synced'] = $synced;
                        $totalSynced += $synced;
                    }
                } else {
                    $result['error'] = "HTTP {$response->status()}: " . $response->body();
                }
            } catch (\Exception $e) {
                $result['error'] = $e->getMessage();
                Log::error("Fingerprint sync error for {$device['sn']}: " . $e->getMessage());
            }

            $deviceResults[] = $result;
        }

        return [
            'total_synced' => $totalSynced,
            'devices'      => $deviceResults,
        ];
    }

    /**
     * Synchronize a single attendance log entry to the database
     */
    public function syncLog($log, $deviceName = null, $userId = null)
    {
        if (!$deviceName) {
            $deviceName = $this->devices[0]['name'] ?? 'Solution X105';
        }

        $waClient = app(\App\Services\WhatsappClient::class);
        $pin = $log['pin'] ?? $log['userid'] ?? $log['Userid'] ?? null;
        $rawDateTime = $log['scan'] ?? $log['date_time'] ?? $log['checktime'] ?? $log['Checktime'] ?? null;
        $status = $log['status'] ?? 0; // 0 = Masuk, 1 = Pulang, default 0

        if (!$rawDateTime) return;
        if (!$pin && !$userId) return;

        try {
            $dateTime = Carbon::parse($rawDateTime, 'Asia/Jakarta');
        } catch (\Exception $e) {
            Log::error("FINGERPRINT ERROR: Failed to parse datetime '{$rawDateTime}': " . $e->getMessage());
            return;
        }

        $date = $dateTime->toDateString();
        $time = $dateTime->format('H:i:s');

        // Find user by ID or PIN
        if ($userId) {
            $user = User::find($userId);
        } else {
            $normalizedPin = ltrim($pin, '0');
            if ($normalizedPin === '') $normalizedPin = '0';
            
            $user = User::where('pin_fingerspot', $pin)
                ->orWhere('pin_fingerspot', $normalizedPin)
                ->first();
                
            if (!$user) {
                $candidates = User::whereNotNull('pin_fingerspot')->get();
                foreach ($candidates as $c) {
                    if (ltrim($c->pin_fingerspot, '0') === $normalizedPin) {
                        $user = $c;
                        break;
                    }
                }
            }
        }

        if (!$user) {
            Log::warning("FINGERPRINT ERROR: No user found for PIN '{$pin}' or UserID '{$userId}'.");
            return;
        }

        Log::info("FINGERPRINT: Processing log for '{$user->name}' at {$dateTime}");

        $absensi = Absensi::firstOrNew([
            'user_id' => $user->id,
            'tgl'     => $date,
        ]);

        $isNewMasuk = false;
        $isNewPulang = false;

        $minInterval = Setting::get('absensi_min_interval', 5);
        $batasMasuk = Setting::get('absensi_batas_masuk', '08:00:00');
        $batasPulang = Setting::get('absensi_batas_pulang', '17:00:00');

        // 1. Determine check-in (earliest scan)
        if (!$absensi->jam_masuk || $time < $absensi->jam_masuk) {
            if ($status == 0 || !$absensi->jam_masuk) {
                // Jika sudah ada jam_masuk sebelumnya, geser ke jam_pulang
                // HANYA jika jam_masuk lama itu lebih besar dari jam_masuk baru
                // dan belum ada jam_pulang — artinya ini scan masuk yang lebih awal
                if ($absensi->jam_masuk && !$absensi->jam_pulang) {
                    $absensi->jam_pulang = $absensi->jam_masuk;
                    // Tandai pulang hanya jika beda lebih dari minInterval
                    $diffMinutes = Carbon::parse($time)->diffInMinutes(Carbon::parse($absensi->jam_masuk));
                    $isNewPulang = $diffMinutes >= $minInterval;
                }
                $absensi->jam_masuk = $time;
                $absensi->pin = $pin;
                $isNewMasuk = true;
            }
        }

        // 2. Determine check-out (latest scan after min interval)
        if ($absensi->jam_masuk && $time > $absensi->jam_masuk) {
            $scanMasuk = Carbon::parse($absensi->jam_masuk);
            $scanCurrent = Carbon::parse($time);
            $diffMinutes = $scanCurrent->diffInMinutes($scanMasuk);

            if ($status == 1 || $diffMinutes >= $minInterval) {
                if (!$absensi->jam_pulang || $time > $absensi->jam_pulang) {
                    $absensi->jam_pulang = $time;
                    $isNewPulang = true;
                }
            }
        }

        // 3. Re-calculate status
        if ($absensi->jam_masuk) {
            $isTerlambat = $absensi->jam_masuk > $batasMasuk;
            if ($absensi->jam_pulang) {
                $isPulangAwal = $absensi->jam_pulang < $batasPulang;
                if ($isTerlambat && $isPulangAwal) {
                    $absensi->status_kehadiran = 'Terlambat & Pulang Awal';
                } elseif ($isTerlambat) {
                    $absensi->status_kehadiran = 'Terlambat';
                } elseif ($isPulangAwal) {
                    $absensi->status_kehadiran = 'Pulang Lebih Awal';
                } else {
                    $absensi->status_kehadiran = 'Hadir';
                }
            } else {
                $absensi->status_kehadiran = $isTerlambat ? 'Terlambat' : 'Hadir';
            }
        }

        Log::info("Saving Absensi for User ID {$user->id} on Date {$date}: Masuk={$absensi->jam_masuk}, Pulang={$absensi->jam_pulang}");
        $absensi->lokasi = $deviceName;
        $absensi->save();

        // 4. WhatsApp Notification
        if (($isNewMasuk || $isNewPulang) && $date == date('Y-m-d')) {
            // Get phone number from User or Teknisi profile
            $noWa = $user->no_hp;
            if (!$noWa && $user->teknisi) {
                $noWa = $user->teknisi->no_hp;
            }

            if ($noWa) {
                $hariTanggal = $dateTime->locale('id')->translatedFormat('l, d F Y');
                $namaId = strtoupper($user->name) . " (" . ($pin ?: '-') . ")";

                if ($isNewMasuk && !$isNewPulang) {
                    $pesan = "📢 *NOTIFIKASI ABSENSI MASUK*\n\n"
                           . "📅 Hari/Tgl: *{$hariTanggal}*\n"
                           . "👤 Nama: *{$namaId}*\n"
                           . "⏰ Jam Masuk: *{$time} WIB*\n"
                           . "📍 Lokasi: *{$deviceName}*\n"
                           . "📝 Status: *{$absensi->status_kehadiran}*\n\n"
                           . "Selamat bekerja! Tetap semangat dan jaga keselamatan selalu. 💪";
                } else {
                    $jamMasuk = $absensi->jam_masuk ?? '--:--:--';
                    $jamPulang = $absensi->jam_pulang ?? '--:--:--';
                    
                    // Hitung durasi kerja jika masuk dan pulang tersedia
                    $durasiKerja = '';
                    if ($absensi->jam_masuk && $absensi->jam_pulang) {
                        try {
                            $masukCarbon = Carbon::parse($absensi->jam_masuk);
                            $pulangCarbon = Carbon::parse($absensi->jam_pulang);
                            $durasiMenit = $masukCarbon->diffInMinutes($pulangCarbon);
                            $jam = intdiv($durasiMenit, 60);
                            $menit = $durasiMenit % 60;
                            $durasiKerja = "\n⏱️ Durasi Kerja: *{$jam} jam {$menit} menit*";
                        } catch (\Exception $e) {}
                    }

                    $pesan = "📢 *NOTIFIKASI ABSENSI PULANG*\n\n"
                           . "📅 Hari/Tgl: *{$hariTanggal}*\n"
                           . "👤 Nama: *{$namaId}*\n"
                           . "📍 Lokasi: *{$deviceName}*\n\n"
                           . "Detail Kehadiran:\n"
                           . "📥 Jam Masuk: *{$jamMasuk} WIB*\n"
                           . "📤 Jam Pulang: *{$jamPulang} WIB*"
                           . $durasiKerja . "\n"
                           . "📝 Status: *{$absensi->status_kehadiran}*\n\n"
                           . "Terima kasih atas kerja keras Anda hari ini. Selamat beristirahat dan hati-hati di jalan! 🏡";
                }

                // Non-blocking: jika WA bot mati tidak akan menghambat proses fingerprint
                try {
                    $waClient->sendMessage($noWa, $pesan, true);
                } catch (\Exception $e) {
                    Log::warning("FINGERPRINT: Gagal kirim notif WA ke {$noWa}: " . $e->getMessage());
                }
            }
        }
    }
}

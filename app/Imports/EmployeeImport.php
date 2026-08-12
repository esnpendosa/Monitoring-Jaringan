<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Biodata;
use App\Models\Unit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class EmployeeImport implements ToModel, WithHeadingRow
{
    use Importable;

    protected $fonnte;
    protected $units;

    public function __construct()
    {
        $this->fonnte = app(\App\Services\FonnteService::class);
        $this->units = Unit::all();
    }

    public function model(array $row)
    {
        if (!isset($row['email']) || empty($row['email'])) {
            return null;
        }

        $isNew = !User::where('email', $row['email'])->exists();
        $passwordRaw = $row['password'] ?? ($row['username'] ?? explode('@', $row['email'])[0]);

        // Find matching unit from database (by ID or by Name/Slug)
        $rawUnit = trim($row['unit'] ?? '');
        $matchedUnit = null;
        if (is_numeric($rawUnit)) {
            $matchedUnit = $this->units->firstWhere('id', (int) $rawUnit);
        }
        if (!$matchedUnit && !empty($rawUnit)) {
            $matchedUnit = $this->units->first(function($u) use ($rawUnit) {
                return strtolower($u->nama) === strtolower($rawUnit) || 
                       $u->slug === Str::slug($rawUnit);
            });
        }
        $finalUnit = $matchedUnit ? $matchedUnit->nama : ($rawUnit ?: 'Pusat');

        // Find or Create User
        $user = User::updateOrCreate(
            ['email' => $row['email']],
            [
                'name'     => $row['nama_pegawai'],
                'username' => $row['username'] ?? explode('@', $row['email'])[0],
                'password' => Hash::make($passwordRaw),
                'role'     => strtolower($row['role']) ?? 'pegawai',
                'unit'     => $finalUnit,
                'pin_fingerspot' => $row['pin_fingerprint'] ?? null,
            ]
        );

        // Update Biodata
        $noWa = $this->formatWhatsApp($row['no_wa_ex_628xxx'] ?? '');
        
        Biodata::updateOrCreate(
            ['user_id' => $user->id],
            [
                'kode_pegawai' => $row['kode_pegawai'] ?? null,
                'nik' => $row['nik_ktp'] ?? null,
                'no_wa' => $noWa,
                'tempat_lahir' => $row['tempat_lahir'] ?? null,
                'tgl_lahir' => isset($row['tanggal_lahir_yyyy_mm_dd']) ? $row['tanggal_lahir_yyyy_mm_dd'] : null,
                'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
                'agama' => $row['agama'] ?? null,
                'status_pegawai' => $row['status_pegawai'] ?? 'Aktif',
            ]
        );

        // Kirim Notifikasi WA jika user baru & nomor WA ada
        if ($isNew && $noWa) {
            $waMessage = "Yth. Bapak/Ibu *{$user->name}*,\ndimohon untuk mengisi biodata pada Sistem Kepegawaian Perkumpulan Manbaul Ulum Bungah sebagai berikut:\n\n"
                       . "Alamat : https://pmub.my.id\n"
                       . "Username : *{$user->username}*\n"
                       . "Password : *{$passwordRaw}*\n\n"
                       . "Silahkan mengganti password setelah login kemudian mengisi semua data yang diperlukan, terima kasih.";
            
            $this->fonnte->sendMessage($noWa, $waMessage);
        }

        return $user;
    }

    private function formatWhatsApp($number)
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (empty($number)) return null;
        if (str_starts_with($number, '08')) {
            $number = '628' . substr($number, 2);
        } elseif (str_starts_with($number, '8')) {
            $number = '62' . $number;
        }
        return $number;
    }
}

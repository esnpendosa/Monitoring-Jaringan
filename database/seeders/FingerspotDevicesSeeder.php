<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class FingerspotDevicesSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * PENTING UNTUK developer.fingerspot.io:
         * 1. Login ke https://developer.fingerspot.io
         * 2. Menu 'Mesin Absensi' -> 'API SDK' -> Centang mesin & tekan Aktifkan.
         * 3. 'sn' di bawah diisi dengan CLOUD ID (bukan SN stiker).
         * 4. 'sc' di bawah diisi dengan API TOKEN / Authorization.
         */
        $devices = [
            ['name' => 'Solutions X100-C (Utama)', 'url' => 'https://pmub.my.id/iclock/cdata', 'sn' => 'NJF7254700390', 'sc' => ''],
        ];
        
        Setting::updateOrCreate(
            ['key' => 'fingerspot_devices'],
            ['value' => json_encode($devices)]
        );
    }
}

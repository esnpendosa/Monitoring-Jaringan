<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Modem;

class ModemSeeder extends Seeder
{
    public function run(): void
    {
        $modems = [
            [
                'nama' => 'HUAWEI EchoLife EG8141H5',
                'merek' => 'HUAWEI',
                'model' => 'EchoLife EG8141H5',
                'ip_address' => '192.168.18.1',
                'deskripsi' => 'Modem ONT GPON Router Huawei EchoLife EG8141H5 dengan 1 port POTS, 1 GE LAN port, 3 FE LAN ports, dan WiFi.',
                'spesifikasi' => "Merek: HUAWEI\nModel: EchoLife EG8141H5\nIP Default: 192.168.18.1\nUsername: telecomadmin / admin\nPassword: admintelecom / admin\nTipe: GPON ONT",
                'is_active' => true,
            ],
            [
                'nama' => 'HUAWEI F663NV3a (X PON ONU)',
                'merek' => 'HUAWEI',
                'model' => 'F663NV3a',
                'ip_address' => '192.168.1.1',
                'deskripsi' => 'Modem X-PON ONU (Dual Mode GPON/EPON) F663NV3a dengan interface LAN berkecepatan tinggi dan koneksi WiFi yang stabil.',
                'spesifikasi' => "Merek: HUAWEI\nModel: F663NV3a\nIP Default: 192.168.1.1\nUsername: admin / user\nPassword: admin / user\nTipe: XPON ONU",
                'is_active' => true,
            ],
            [
                'nama' => 'HUAWEI HG6145D2',
                'merek' => 'HUAWEI',
                'model' => 'HG6145D2',
                'ip_address' => '192.168.1.1',
                'deskripsi' => 'Modem ONT GPON Fiberhome/Huawei HG6145D2 dengan Dual Band WiFi (2.4GHz & 5GHz) dan 4 port Gigabit Ethernet.',
                'spesifikasi' => "Merek: HUAWEI\nModel: HG6145D2\nIP Default: 192.168.1.1\nUsername: admin / user\nPassword: admin / user\nTipe: GPON ONT Dual Band",
                'is_active' => true,
            ],
            [
                'nama' => 'ZTE F670L',
                'merek' => 'ZTE',
                'model' => 'F670L',
                'ip_address' => '192.168.1.1',
                'deskripsi' => 'Modem ONT GPON ZTE F670L Dual Band AC1200 Gigabit Premium dengan 4 port LAN, 1 port Tel, dan WiFi 2.4GHz & 5GHz.',
                'spesifikasi' => "Merek: ZTE\nModel: ZXHN F670L\nIP Default: 192.168.1.1\nUsername: user / admin\nPassword: user / telkomasean\nTipe: GPON ONT Dual Band AC1200",
                'is_active' => true,
            ],
        ];

        foreach ($modems as $modem) {
            Modem::updateOrCreate(
                ['nama' => $modem['nama']],
                $modem
            );
        }
    }
}

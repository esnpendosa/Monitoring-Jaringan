<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pelanggan;
use App\Models\User;
use App\Models\Role;
use App\Models\Teknisi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ThesisKnnDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = base_path('perhitungan-knn-fix new.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command?->error("Excel file 'perhitungan-knn-fix new.xlsx' not found in root!");
            return;
        }

        $this->command?->info("Truncating existing pelanggan, users, and KNN results to clean up...");
        
        // Disable foreign keys to safely truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('knn_detail_tetangga')->truncate();
        DB::table('knn_hasil')->truncate();
        DB::table('rute_detail')->truncate();
        DB::table('rute')->truncate();
        DB::table('tiket_chats')->truncate();
        DB::table('tiket_gangguan')->truncate();
        
        // Delete pelanggan user accounts (role_id = 4)
        $pelangganUserIds = User::where('id_role', 4)->pluck('id')->toArray();
        if (!empty($pelangganUserIds)) {
            User::whereIn('id', $pelangganUserIds)->delete();
        }
        
        DB::table('pelanggan')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command?->info("Loading Excel file...");
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $imported = 0;
        
        // Loop from row index 3 (4th row) to 102 (103rd row) for the 100 training/testing data points
        // In the Excel rows:
        // Index 3 is Row 4 (No 1)
        // Index 102 is Row 103 (No 100)
        for ($i = 3; $i <= 102; $i++) {
            if (!isset($rows[$i])) continue;
            
            $row = $rows[$i];
            $no = $row[0];
            $name = trim($row[1]);
            // Excel stores coordinates with thousands separators (e.g. "-713,516" or "11,259,841")
            // Remove commas before casting so we get -713516, not -713.516
            $latRaw = (double) str_replace(',', '', $row[2]);
            $lngRaw = (double) str_replace(',', '', $row[3]);
            $usage = (double)$row[4];
            $device = (int)$row[5];
            $priority = strtoupper(trim($row[6]));

            if (empty($name)) continue;

            // Convert scaled integer coordinates to standard decimal degrees
            // e.g. -713516 -> -7.13516, 11259841 -> 112.59841
            $lat = ($latRaw < -90 || $latRaw > 90) ? $latRaw / 100000 : $latRaw;
            $lng = ($lngRaw < -180 || $lngRaw > 180) ? $lngRaw / 100000 : $lngRaw;

            $code = 'PEL' . sprintf('%03d', $no);

            // Create Pelanggan
            $pelanggan = Pelanggan::create([
                'kode_pelanggan' => $code,
                'nama_pelanggan' => $name,
                'alamat' => 'Jl. Thesis Area No. ' . $no . ', Gresik',
                'latitude' => $lat,
                'longitude' => $lng,
                'usage_gb' => $usage,
                'jumlah_device' => $device,
                'prioritas_label' => $priority,
                'harga_layanan' => 100000,
                'paket' => '10 Mbps',
                'is_active' => 1,
                'wa_active' => 0,
                'billing_date' => 1,
                'ip_address' => '192.168.10.' . ($no + 10)
            ]);

            // Create User Account
            $user = User::create([
                'name' => $name,
                'email' => strtolower($code) . '@rtrwnet.com',
                'username_email' => strtolower($code),
                'password' => Hash::make('12345678'),
                'id_role' => 4, // Role Pelanggan
                'is_active' => true
            ]);

            $pelanggan->update(['id_user' => $user->id]);
            $imported++;
        }

        $this->command?->info("Successfully seeded {$imported} pelanggan records matching the thesis dataset!");
        
        // Seed a default test client 'Sari' if she's not there, as a dummy unclassified customer
        if (isset($rows[107])) {
            $row = $rows[107];
            $name = trim($row[1]); // 'Sari'
            $latRaw = (double) str_replace(',', '', $row[2]);
            $lngRaw = (double) str_replace(',', '', $row[3]);
            $usage = (double)$row[4];
            $device = (int)$row[5];
            
            $lat = ($latRaw < -90 || $latRaw > 90) ? $latRaw / 100000 : $latRaw;
            $lng = ($lngRaw < -180 || $lngRaw > 180) ? $lngRaw / 100000 : $lngRaw;
            
            $code = 'PEL101';
            
            $pelanggan = Pelanggan::create([
                'kode_pelanggan' => $code,
                'nama_pelanggan' => $name,
                'alamat' => 'Jl. Kebonsari No. 101, Gresik',
                'latitude' => $lat,
                'longitude' => $lng,
                'usage_gb' => $usage,
                'jumlah_device' => $device,
                'prioritas_label' => null, // Leave null so it can be classified dynamically!
                'harga_layanan' => 100000,
                'paket' => '10 Mbps',
                'is_active' => 1,
                'wa_active' => 0,
                'billing_date' => 1,
                'ip_address' => '192.168.10.111'
            ]);
            
            $user = User::create([
                'name' => $name,
                'email' => strtolower($code) . '@rtrwnet.com',
                'username_email' => strtolower($code),
                'password' => Hash::make('12345678'),
                'id_role' => 4, // Role Pelanggan
                'is_active' => true
            ]);
            
            $pelanggan->update(['id_user' => $user->id]);
            $this->command?->info("Successfully seeded test client 'Sari' (PEL101) for dynamic KNN testing!");
        }
    }
}

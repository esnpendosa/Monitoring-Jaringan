<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userData = [
            [
                'email' => 'yayasan@pmu.id',
                'name' => 'Akbar Yayasan',
                'password' => bcrypt('password'),
                'role' => 'yayasan',
                'unit' => 'Yayasan / Pusat',
                'pin_fingerspot' => null,
            ],
            [
                'email' => 'admin@pmu.id',
                'name' => 'Admin Unit MA',
                'password' => bcrypt('password'),
                'role' => 'admin_unit',
                'unit' => 'MA Manbaul Ulum',
                'pin_fingerspot' => null,
            ],
            [
                'email' => 'pegawai@pmu.id',
                'name' => 'Budi Pegawai',
                'password' => bcrypt('password'),
                'role' => 'pegawai',
                'unit' => 'MA Manbaul Ulum',
                'pin_fingerspot' => '000000037',
            ],
        ];

        foreach ($userData as $data) {
            // Find existing user by email
            $user = User::where('email', $data['email'])->first();

            // If not found by email, try finding by pin_fingerspot if it's not null
            if (!$user && isset($data['pin_fingerspot']) && $data['pin_fingerspot']) {
                $user = User::where('pin_fingerspot', $data['pin_fingerspot'])->first();
            }

            if ($user) {
                // Update existing user
                $user->update($data);
            } else {
                // Create new user
                User::create($data);
            }
        }
    }
}

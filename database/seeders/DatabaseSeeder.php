<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Pelanggan;
use App\Models\Teknisi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Permissions
        $permissions = [
            ['code' => 'dashboard_view', 'name' => 'View Dashboard', 'module' => 'Dashboard'],
            ['code' => 'pelanggan_manage', 'name' => 'Manage Pelanggan', 'module' => 'Pelanggan'],
            ['code' => 'knn_process', 'name' => 'Process KNN', 'module' => 'KNN'],
            ['code' => 'rute_manage', 'name' => 'Manage Rute', 'module' => 'Rute'],
            ['code' => 'tiket_manage', 'name' => 'Manage Tiket', 'module' => 'Tiket'],
            ['code' => 'mikrotik_monitor', 'name' => 'Monitor Mikrotik', 'module' => 'Mikrotik'],
            ['code' => 'user_manage', 'name' => 'Manage Users', 'module' => 'Users'],
            ['code' => 'report_view', 'name' => 'View Reports', 'module' => 'Reports'],
            ['code' => 'billing_view', 'name' => 'View Billing', 'module' => 'Keuangan'],
            ['code' => 'inventory_manage', 'name' => 'Manage Inventory', 'module' => 'Inventory'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['code' => $p['code']], $p);
        }

        // 2. Roles
        $adminRole = Role::updateOrCreate(['name' => 'Admin'], ['description' => 'Administrator with full access']);
        $managerRole = Role::updateOrCreate(['name' => 'Manajer'], ['description' => 'Manager with reporting access']);
        $teknisiRole = Role::updateOrCreate(['name' => 'Teknisi'], ['description' => 'Technician with route and visit access']);
        $pelangganRole = Role::updateOrCreate(['name' => 'Pelanggan'], ['description' => 'Customer with support and usage access']);

        // Assign all permissions to Admin
        $adminRole->permissions()->sync(Permission::all()->pluck('id_permission'));
        
        // Assign dashboard, monitoring, reports, customer management, and billing view to Manager
        $managerRole->permissions()->sync(Permission::whereIn('code', ['dashboard_view', 'mikrotik_monitor', 'report_view', 'pelanggan_manage', 'billing_view'])->pluck('id_permission'));
        
        // Assign route and visit to Teknisi
        $teknisiRole->permissions()->sync(Permission::whereIn('code', ['dashboard_view', 'rute_manage', 'tiket_manage'])->pluck('id_permission'));

        // Assign dashboard and support to Pelanggan
        $pelangganRole->permissions()->sync(Permission::whereIn('code', ['dashboard_view', 'tiket_manage', 'billing_view'])->pluck('id_permission'));

        // 3. Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@wifimanager.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'id_role' => $adminRole->id_role,
                'username_email' => 'admin',
                'is_active' => true,
            ]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@wifimanager.com'],
            [
                'name' => 'Manager Net',
                'password' => Hash::make('password'),
                'id_role' => $managerRole->id_role,
                'username_email' => 'manager',
                'is_active' => true,
            ]
        );

        $techUser = User::updateOrCreate(
            ['email' => 'tech@wifimanager.com'],
            [
                'name' => 'Technician One',
                'password' => Hash::make('password'),
                'id_role' => $teknisiRole->id_role,
                'username_email' => 'tech',
                'is_active' => true,
            ]
        );

        // 4. Teknisi Profile
        $teknisi = Teknisi::updateOrCreate(
            ['id_user' => $techUser->id],
            [
                'nama_teknisi' => 'Technician One',
                'no_hp' => '08123456789',
                'base_latitude' => -7.1238534,
                'base_longitude' => 112.592705,
                'is_active' => true,
            ]
        );

        // 5. Dummy Pelanggan (Area Gresik)
        $pelangganData = [
            ['kode' => 'PEL001', 'nama' => 'Budi Santoso', 'lat' => -7.1580, 'lng' => 112.6550, 'usage' => 120, 'dev' => 5],
            ['kode' => 'PEL002', 'nama' => 'Siti Aminah', 'lat' => -7.1620, 'lng' => 112.6480, 'usage' => 45, 'dev' => 2],
            ['kode' => 'PEL003', 'nama' => 'Andi Wijaya', 'lat' => -7.1550, 'lng' => 112.6620, 'usage' => 210, 'dev' => 8],
            ['kode' => 'PEL004', 'nama' => 'Lani Marlina', 'lat' => -7.1650, 'lng' => 112.6420, 'usage' => 15, 'dev' => 1],
            ['kode' => 'PEL005', 'nama' => 'Eko Prasetyo', 'lat' => -7.1700, 'lng' => 112.6530, 'usage' => 300, 'dev' => 12],
            ['kode' => 'PEL006', 'nama' => 'Dewi Lestari', 'lat' => -7.1520, 'lng' => 112.6450, 'usage' => 80, 'dev' => 4],
            ['kode' => 'PEL007', 'nama' => 'Rian Hidayat', 'lat' => -7.1750, 'lng' => 112.6600, 'usage' => 5, 'dev' => 1],
            ['kode' => 'PEL008', 'nama' => 'Maya Sari', 'lat' => -7.1480, 'lng' => 112.6520, 'usage' => 175, 'dev' => 6],
            ['kode' => 'PEL009', 'nama' => 'Hendra Putra', 'lat' => -7.1800, 'lng' => 112.6700, 'usage' => 95, 'dev' => 3],
            ['kode' => 'PEL010', 'nama' => 'Siska Putri', 'lat' => -7.1420, 'lng' => 112.6610, 'usage' => 20, 'dev' => 2],
        ];

        foreach ($pelangganData as $p) {
            Pelanggan::updateOrCreate(
                ['kode_pelanggan' => $p['kode']],
                [
                    'nama_pelanggan' => $p['nama'],
                    'alamat' => 'Jl. Dummy No. ' . rand(1, 100),
                    'latitude' => $p['lat'],
                    'longitude' => $p['lng'],
                    'usage_gb' => $p['usage'],
                    'jumlah_device' => $p['dev'],
                    'prioritas_label' => rand(0, 5) > 3 ? 'High' : (rand(0, 5) > 2 ? 'Medium' : 'Low'),
                ]
            );
        }

        // Call Custom Seeders
        $this->call([
            ModemSeeder::class,
            TutorialSeeder::class,
        ]);
    }
}
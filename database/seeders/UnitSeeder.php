<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            'Yayasan / Pusat',
            'KBM NU 34 Al Firdaus',
            'TKM NU 51 Manbaul ulum',
            'MI Manbaul Ulum',
            'MTs Manbaul Ulum',
            'MA Manbaul Ulum',
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['nama' => $unit],
                ['slug' => \Illuminate\Support\Str::slug($unit)]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UlkeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['id' => 1, 'ulke_adi' => 'Türkiye', 'ulke_kodu' => 'TR'],
            ['id' => 2, 'ulke_adi' => 'İspanya', 'ulke_kodu' => 'ES'],
            ['id' => 3, 'ulke_adi' => 'Yunanistan', 'ulke_kodu' => 'GR'],
            ['id' => 4, 'ulke_adi' => 'Birleşik Krallık', 'ulke_kodu' => 'UK'],
        ];

        foreach ($countries as $country) {
            \App\Models\Ulke::updateOrCreate(['id' => $country['id']], $country);
        }
    }
}

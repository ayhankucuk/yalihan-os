<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['ad' => 'Elektrik', 'icon' => 'bolt'],
            ['ad' => 'Su', 'icon' => 'droplet'],
            ['ad' => 'İnternet', 'icon' => 'wifi'],
            ['ad' => 'Aidat', 'icon' => 'building'],
            ['ad' => 'Havuz Bakımı', 'icon' => 'pool'],
            ['ad' => 'Bahçe Bakımı', 'icon' => 'tree'],
            ['ad' => 'Temizlik', 'icon' => 'sparkles'],
            ['ad' => 'Sigorta', 'icon' => 'shield-check'],
            ['ad' => 'Emlak Vergisi', 'icon' => 'file-text'],
            ['ad' => 'Tamirat / Tadilat', 'icon' => 'wrench'],
        ];

        foreach ($items as $index => $item) {
            \App\Models\ExpenseItem::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($item['ad'])],
                [
                    'ad' => $item['ad'],
                    'icon' => $item['icon'],
                    'display_order' => ($index + 1) * 10,
                    'aktiflik_durumu' => 1,
                ]
            );
        }
    }
}

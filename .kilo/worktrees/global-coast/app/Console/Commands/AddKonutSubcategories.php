<?php

namespace App\Console\Commands;

use App\Models\IlanKategori;
use Illuminate\Console\Command;

class AddKonutSubcategories extends Command
{
    protected $signature = 'categories:add-konut-subcategories';
    protected $description = 'Add missing subcategories to Konut category';

    public function handle()
    {
        $yeniAltlar = [
            ['name' => 'İkiz Villa', 'slug' => 'ikiz-villa', 'parent_id' => 1, 'seviye' => 1],
            ['name' => 'Tripleks', 'slug' => 'tripleks', 'parent_id' => 1, 'seviye' => 1],
            ['name' => 'Residence', 'slug' => 'residence', 'parent_id' => 1, 'seviye' => 1],
            ['name' => 'Stüdyo', 'slug' => 'studyo', 'parent_id' => 1, 'seviye' => 1],
            ['name' => 'Malikane', 'slug' => 'malikane', 'parent_id' => 1, 'seviye' => 1],
            ['name' => 'Taş Ev', 'slug' => 'tas-ev', 'parent_id' => 1, 'seviye' => 1],
        ];

        $this->info('🏠 KONUT ALT KATEGORİLERİ EKLEME');
        $this->newLine();

        $added = 0;
        foreach ($yeniAltlar as $alt) {
            $existing = IlanKategori::where('slug', $alt['slug'])->first();
            if (!$existing) {
                IlanKategori::create($alt);
                $this->info('✅ Eklendi: ' . $alt['name']);
                $added++;
            } else {
                $this->line('⏭️  Zaten var: ' . $alt['name']);
            }
        }

        $this->newLine();
        $this->info('📊 SONUÇ:');
        $this->line('   Yeni eklenen: ' . $added . ' adet');
        $this->line('   Toplam alt kategori: ' . IlanKategori::where('parent_id', 1)->count());

        $this->newLine();
        $this->info('📋 TÜM KONUT ALT KATEGORİLERİ:');
        $konutAltlar = IlanKategori::where('parent_id', 1)->orderBy('id')->get(['id', 'name']);
        foreach ($konutAltlar as $k) {
            $this->line('   ' . $k->id . '. ' . $k->name);
        }

        return 0;
    }
}

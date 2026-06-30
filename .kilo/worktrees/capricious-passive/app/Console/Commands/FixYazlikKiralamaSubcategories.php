<?php

namespace App\Console\Commands;

use App\Models\IlanKategori;
use Illuminate\Console\Command;

class FixYazlikKiralamaSubcategories extends Command
{
    protected $signature = 'categories:fix-yazlik-kiralama';
    protected $description = 'Fix Yazlık Kiralama subcategories naming conflict';

    public function handle()
    {
        $this->info('🏖️ YAZLIK KİRALAMA ALT KATEGORİLERİ DÜZELTİLİYOR');
        $this->newLine();

        // Mevcut yanlış isimleri değiştir
        $updates = [
            19 => ['name' => 'Villa', 'slug' => 'villa-yazlik'],
            20 => ['name' => 'Apart', 'slug' => 'apart-yazlik'],
            21 => ['name' => 'Bungalov', 'slug' => 'bungalov'],
        ];

        foreach ($updates as $id => $data) {
            $kategori = IlanKategori::find($id);
            if ($kategori) {
                $old = $kategori->name;
                $kategori->update($data);
                $this->info("✅ Güncellendi: {$old} → {$data['name']}");
            }
        }

        // Pansiyon ekle (yoksa)
        $pansiyon = IlanKategori::where('slug', 'pansiyon-yazlik')->first();
        if (!$pansiyon) {
            IlanKategori::create([
                'name' => 'Pansiyon',
                'slug' => 'pansiyon-yazlik',
                'parent_id' => 4,
                'seviye' => 1
            ]);
            $this->info('✅ Eklendi: Pansiyon');
        } else {
            $this->line('⏭️  Zaten var: Pansiyon');
        }

        $this->newLine();
        $this->info('📋 YENİ LİSTE:');
        $yazlik = IlanKategori::where('parent_id', 4)->orderBy('id')->get(['id', 'name']);
        foreach ($yazlik as $k) {
            $this->line('   ' . $k->id . '. ' . $k->name);
        }

        $this->newLine();
        $this->info('✅ Kafa karışıklığı giderildi!');
        $this->line('   Alt Kategoriler: Villa, Apart, Bungalov, Pansiyon');
        $this->line('   Yayın Tipleri: Günlük Kiralık, Haftalık Kiralık, etc.');

        return 0;
    }
}

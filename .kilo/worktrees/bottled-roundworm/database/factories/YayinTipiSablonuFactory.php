<?php

namespace Database\Factories;

use App\Models\YayinTipiSablonu;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class YayinTipiSablonuFactory extends Factory
{
    protected $model = YayinTipiSablonu::class;

    public function definition(): array
    {
        $name = $this->faker->word() . ' Template';
        return [
            'kategori_id' => \App\Models\IlanKategori::factory(),
            'ad' => $name,
            'slug' => Str::slug($name),
            'aciklama' => $this->faker->sentence(),
            'aktiflik_durumu' => true,
            'display_order' => $this->faker->numberBetween(1, 100),
            'varsayilan_ozellikler' => [],
            'fiyat_ayarlari' => [],
        ];
    }
}

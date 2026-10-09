<?php

namespace Database\Factories\V2;

use App\Models\V2\Ilan as V2Ilan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IlanFactory extends Factory
{
    protected $model = V2Ilan::class;

    public function definition(): array
    {
        $baslik = $this->faker->sentence(5);

        return [
            'baslik' => $baslik,
            'slug' => \Illuminate\Support\Str::slug($baslik . '-' . uniqid()),
            'aciklama' => $this->faker->paragraph(5),
            'fiyat' => $this->faker->randomFloat(2, 100000, 10000000),
            'para_birimi' => 'TL',
            'referans_no' => 'REF-' . uniqid(),
            'yayin_durumu' => 'yayinda',
            'danisman_id' => User::factory(),
            'ilan_sahibi_id' => 1,
            'ana_kategori_id' => 1,
            'alt_kategori_id' => null,
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ulke_id' => 1,
            'brut_m2' => $this->faker->numberBetween(50, 500),
            'net_m2' => $this->faker->numberBetween(40, 450),
            'tenant_id' => 1,
            'user_id' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

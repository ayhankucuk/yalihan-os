<?php

namespace Database\Factories\V2;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\V2\Ilan;
use App\Models\V2\User;

class IlanFactory extends Factory
{
    protected $model = Ilan::class;

    public function definition()
    {
        return [
            'ilan_no' => $this->faker->unique()->numberBetween(100000, 999999),
            'slug' => $this->faker->unique()->slug(),
            'user_id' => User::factory(),
            'danisman_id' => null,
            'ana_kategori_id' => \App\Models\IlanKategori::factory(),
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'baslik' => $this->faker->sentence(),
            'aciklama' => $this->faker->paragraph(),
            'fiyat' => $this->faker->randomFloat(2, 100000, 10000000),
            'yayin_durumu' => 'yayinda',
            'brut_m2' => $this->faker->numberBetween(50, 500),
            'net_m2' => $this->faker->numberBetween(40, 450),
        ];
    }
}

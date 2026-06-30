<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\IlanKategori;

class IlanKategoriFactory extends Factory
{
    protected $model = IlanKategori::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'slug' => $this->faker->slug(),
            'seviye' => 0,
            'aktiflik_durumu' => true,
        ];
    }
}

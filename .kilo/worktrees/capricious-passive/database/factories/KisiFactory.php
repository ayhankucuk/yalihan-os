<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kisi>
 */
class KisiFactory extends Factory
{
    protected $model = \App\Models\Kisi::class;

    public function definition(): array
    {
        return [
            'ad' => $this->faker->firstName(),
            'soyad' => $this->faker->lastName(),
            'eposta' => $this->faker->unique()->safeEmail(), // Context7: email → eposta
            'telefon' => $this->faker->phoneNumber(),
            'kisi_tipi' => \App\Enums\KisiTipi::ALICI->value,
            'aktiflik_durumu' => true,
            // Optional fields - only include if needed
            // 'adres' => $this->faker->address(),
            // 'tc_kimlik' => $this->faker->numerify('###########'),
            // 'notlar' => $this->faker->optional()->paragraph(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Kisi;
use App\Models\Talep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TalepFactory extends Factory
{
    protected $model = Talep::class;

    /**
     * Gerçek tablo kolonları:
     * id, kisi_id, danisman_id, talep_tipi, emlak_tipi,
     * min_fiyat, max_fiyat, il_id, ilce_id, notlar,
     * talep_durumu, oncelik, created_at, updated_at, deleted_at
     */
    public function definition(): array
    {
        return [
            'kisi_id' => Kisi::factory(),
            'danisman_id' => User::factory(),
            'talep_tipi' => $this->faker->randomElement(['satis', 'kira']),
            'emlak_tipi' => $this->faker->randomElement(['daire', 'villa', 'arsa', 'isyeri']),
            'min_fiyat' => $this->faker->numberBetween(100000, 500000),
            'max_fiyat' => $this->faker->numberBetween(500000, 2000000),
            'il_id' => null,
            'ilce_id' => null,
            'notlar' => $this->faker->paragraph(),
            'talep_durumu' => $this->faker->randomElement(['Aktif', 'Beklemede', 'Tamamlandı', 'İptal', 'Acil']),
            'oncelik' => $this->faker->randomElement(['düşük', 'normal', 'yüksek', 'acil']),
        ];
    }
}

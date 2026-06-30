<?php

namespace Database\Factories;

use App\Models\UpsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class UpsTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UpsTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'yayin_tipi_sablonu_id' => 1, // Default dummy
            'kategori_id' => 1,
            'yayin_tipi_id' => 1,
            'template_json' => [
                'zorunlu_alanlar' => ['baslik', 'fiyat'],
                'opsiyonel_alanlar' => [],
                'gizli_alanlar' => [],
                'ui_ipuclari' => [],
                'validasyon_kurallari' => []
            ],
            'template_version' => 1,
            'template_hash' => $this->faker->sha256,
            'aktiflik_durumu' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

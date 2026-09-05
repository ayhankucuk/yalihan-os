<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Property;
use App\Models\User;
use App\Models\IlanKategori;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for Ilan (Listing) model.
 *
 * Note: The Sprint 12B property_id → ilanlar relationship was not fully
 * implemented in the database schema. The ilanlar table does not have a
 * property_id column. The configure()/afterMaking() hooks that attempted
 * to set property_id have been removed.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ilan>
 */
class IlanFactory extends Factory
{
    protected $model = Ilan::class;

    public function definition(): array
    {
        $baslik = $this->faker->sentence(5);

        return [
            'baslik' => $baslik,
            'slug' => Str::slug($baslik . '-' . uniqid()),
            'aciklama' => $this->faker->paragraph(5),
            'fiyat' => $this->faker->randomFloat(2, 100000, 10000000),
            'para_birimi' => 'TL',
            'referans_no' => 'REF-' . uniqid(),
            'yayin_durumu' => 'yayinda',
            'danisman_id' => User::factory(),
            'ilan_sahibi_id' => Kisi::factory(),
            'ana_kategori_id' => IlanKategori::factory(),
            'alt_kategori_id' => null,
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'yayin_tipi_id' => null,
            'brut_m2' => $this->faker->numberBetween(50, 500),
            'net_m2' => $this->faker->numberBetween(40, 450),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Factory state: Ilan with at least 1 photograph (for completion_score = 100).
     */
    public function withPhoto(): static
    {
        return $this->afterCreating(function (Ilan $ilan) {
            \Illuminate\Support\Facades\DB::table('ilan_fotograflari')->insert([
                'ilan_id'         => $ilan->id,
                'dosya_yolu'      => 'test/photo-' . uniqid() . '.jpg',
                'dosya_adi'       => 'photo-' . uniqid() . '.jpg',
                'display_order'   => 1,
                'kapak_fotografi' => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\Property;
use App\Domain\PropertyDocument\Models\PropertyDocument;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\PropertyDocument\Models\PropertyDocument>
 */
class PropertyDocumentFactory extends Factory
{
    protected $model = PropertyDocument::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'property_id' => Property::factory(),
            'dokuman_tipi' => 'TITLE_DEED',
            'dosya_yolu' => null,
            'referans_no' => $this->faker->numerify('TD-#####'),
            'yayin_tarihi' => $this->faker->date(),
            'son_gecerlilik_tarihi' => null,
            'durum' => 'AKTIF',
            'notu' => null,
            'olusturan_id' => User::factory(),
            'idempotency_key' => $this->faker->uuid(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attrs) => [
            'son_gecerlilik_tarihi' => now()->subDay()->toDateString(),
        ]);
    }

    public function titleDeed(): static
    {
        return $this->state(fn (array $attrs) => [
            'dokuman_tipi' => 'TITLE_DEED',
        ]);
    }
}

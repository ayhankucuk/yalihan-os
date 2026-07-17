<?php

namespace Database\Factories;

use App\Models\Property;
use App\Domain\PropertyAccess\Models\PropertyAccessAsset;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\PropertyAccess\Models\PropertyAccessAsset>
 */
class PropertyAccessAssetFactory extends Factory
{
    protected $model = PropertyAccessAsset::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'property_id' => Property::factory(),
            'varlik_tipi' => 'KEY',
            'tanimlayici_no' => null,
            'tanim' => $this->faker->sentence(3),
            'durum' => 'AKTIF',
            'olusturan_id' => User::factory(),
        ];
    }

    public function alarmCode(): static
    {
        return $this->state(fn (array $attrs) => [
            'varlik_tipi' => 'ALARM_CODE',
            'tanimlayici_no' => $this->faker->numerify('####'),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attrs) => [
            'durum' => 'KAYIP',
        ]);
    }
}

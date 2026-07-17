<?php

namespace Database\Factories;

use App\Models\Property;
use App\Domain\PropertyRepresentative\Models\PropertyRepresentative;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\PropertyRepresentative\Models\PropertyRepresentative>
 */
class PropertyRepresentativeFactory extends Factory
{
    protected $model = PropertyRepresentative::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'property_id' => Property::factory(),
            'kisi_id' => Kisi::factory(),
            'temsil_yetu_tipi' => 'FULL',
            'baslangic_tarihi' => $this->faker->date(),
            'bitis_tarihi' => null,
            'notu' => null,
            'olusturan_id' => User::factory(),
            'idempotency_key' => $this->faker->uuid(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => [
            'bitis_tarihi' => $this->faker->date(),
        ]);
    }
}

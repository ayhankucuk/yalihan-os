<?php

namespace Database\Factories;

use App\Models\Property;
use App\Domain\PropertyOwnership\Models\PropertyOwnership;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\PropertyOwnership\Models\PropertyOwnership>
 */
class PropertyOwnershipFactory extends Factory
{
    protected $model = PropertyOwnership::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'property_id' => Property::factory(),
            'kisi_id' => Kisi::factory(),
            'pay_orani' => 1.0,
            'sahiplik_tipi' => 'OWNER',
            'yetkili_temsilci_id' => null,
            'baslangic_tarihi' => $this->faker->date(),
            'bitis_tarihi' => null,
            'atama_kaynagi' => 'MANUAL',
            'atama_notu' => null,
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

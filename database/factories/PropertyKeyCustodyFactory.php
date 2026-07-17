<?php

namespace Database\Factories;

use App\Domain\PropertyAccess\Models\PropertyAccessAsset;
use App\Domain\PropertyAccess\Models\PropertyKeyCustody;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\PropertyAccess\Models\PropertyKeyCustody>
 */
class PropertyKeyCustodyFactory extends Factory
{
    protected $model = PropertyKeyCustody::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'asset_id' => PropertyAccessAsset::factory(),
            'kisi_id' => Kisi::factory(),
            'islem_tipi' => 'TESLIM',
            'islem_tarihi' => now(),
            'notu' => null,
            'olusturan_id' => User::factory(),
            'idempotency_key' => $this->faker->uuid(),
        ];
    }

    public function handover(): static
    {
        return $this->state(fn (array $attrs) => ['islem_tipi' => 'TESLIM']);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attrs) => ['islem_tipi' => 'IADE']);
    }
}

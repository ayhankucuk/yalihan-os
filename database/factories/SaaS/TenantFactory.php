<?php

namespace Database\Factories\SaaS;

use App\Models\SaaS\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaaS\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'uuid' => $this->faker->uuid(),
            'status' => 'active',
        ];
    }
}

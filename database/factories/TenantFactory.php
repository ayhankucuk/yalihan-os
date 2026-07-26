<?php

namespace Database\Factories;

use App\Models\SaaS\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = strtolower(fake()->unique()->domainWord()) . '.test';

        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->company(),
            'domain' => $domain,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}

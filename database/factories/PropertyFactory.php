<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Property model.
 *
 * Sprint 12B: Canonical Property Aggregate Infrastructure
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1, // Default tenant
            'canonical_reference' => 'test-' . uniqid(),
            'lifecycle_state' => 'DRAFT',
        ];
    }

    /**
     * Indicate that the property is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'lifecycle_state' => 'ACTIVE',
        ]);
    }

    /**
     * Indicate that the property is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'lifecycle_state' => 'ARCHIVED',
        ]);
    }

    /**
     * Create property for legacy listing mapping.
     */
    public function legacy(string $tenantId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
            'canonical_reference' => "legacy-tenant:{$tenantId}",
        ]);
    }
}

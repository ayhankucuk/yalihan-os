<?php

namespace Database\Factories;

use App\Models\FeaturePack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Context7 Compliant Factory
 * - aktiflik_durumu (NOT status/active)
 */
class FeaturePackFactory extends Factory
{
    protected $model = FeaturePack::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'aktiflik_durumu' => true,
        ];
    }

    /**
     * Indicate that the pack is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'aktiflik_durumu' => false,
        ]);
    }
}

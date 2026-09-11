<?php

namespace Database\Factories;

use App\Models\IlanTakvimSync;
use App\Models\Ilan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IlanTakvimSync>
 */
class IlanTakvimSyncFactory extends Factory
{
    protected $model = IlanTakvimSync::class;

    public function definition(): array
    {
        return [
            'ilan_id' => Ilan::factory(),
            'platform' => $this->faker->randomElement(['airbnb', 'booking', 'google_calendar']),
            'external_listing_id' => $this->faker->optional(0.7)->uuid(),
            'external_calendar_id' => $this->faker->optional(0.3)->uuid(),
            'is_sync_active' => true,
            'auto_sync' => true,
            'last_sync_at' => $this->faker->optional(0.6)->dateTimeBetween('-7 days', 'now'),
            'next_sync_at' => $this->faker->optional(0.5)->dateTimeBetween('now', '+1 hour'),
            'sync_interval_minutes' => $this->faker->randomElement([15, 30, 60]),
            'senkron_durumu' => 'active', // context7-ignore
            'sync_count' => $this->faker->numberBetween(0, 100),
            'error_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attrs): array => [
            'senkron_durumu' => 'pasif', // context7-ignore
            'auto_sync' => false,
        ]);
    }

    public function airbnb(): static
    {
        return $this->state(fn(array $attrs): array => [
            'platform' => 'airbnb',
        ]);
    }

    public function booking(): static
    {
        return $this->state(fn(array $attrs): array => [
            'platform' => 'booking',
        ]);
    }

    public function due(): static
    {
        return $this->state(fn(array $attrs): array => [
            'next_sync_at' => now()->subMinutes(5),
        ]);
    }

    public function future(): static
    {
        return $this->state(fn(array $attrs): array => [
            'next_sync_at' => now()->addDays(3),
        ]);
    }
}

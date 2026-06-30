<?php

namespace Database\Factories;

use App\Models\HandlerExecution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HandlerExecution>
 */
class HandlerExecutionFactory extends Factory
{
    protected $model = HandlerExecution::class;

    public function definition(): array
    {
        return [
            'handler_name' => $this->faker->randomElement([
                'TelegramNotificationHandler',
                'NotificationLoggerHandler',
                'EmailNotificationHandler',
            ]),
            'event_name' => $this->faker->randomElement([
                'ilan.created',
                'ilan.updated',
                'lead.assigned',
                'lead.status_changed',
            ]),
            'event_id' => 'evt_' . $this->faker->uuid(),
            'event_payload' => [
                'tenant_id' => $this->faker->randomNumber(1, 100),
                'data' => $this->faker->sentence(),
            ],
            'status' => HandlerExecution::STATUS_PENDING,
            'attempt_count' => 0,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
            'tenant_id' => 1,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => HandlerExecution::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => HandlerExecution::STATUS_SUCCESS,
            'started_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => HandlerExecution::STATUS_FAILED,
            'attempt_count' => 1,
            'error_message' => 'Handler failed',
            'started_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);
    }

    public function deadLetter(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => HandlerExecution::STATUS_DEAD_LETTER,
            'attempt_count' => 3,
            'error_message' => 'Max attempts exceeded',
            'started_at' => now()->subSeconds(15),
            'finished_at' => now(),
        ]);
    }
}

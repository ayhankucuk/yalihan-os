<?php

declare(strict_types=1);

namespace Database\Factories\Settlement;

use App\Enums\ReconciliationResult;
use App\Models\Settlement\ReconciliationExecution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReconciliationExecution>
 */
class ReconciliationExecutionFactory extends Factory
{
    protected $model = ReconciliationExecution::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'execution_type' => 'auto',
            'bank_transaction_id' => null,
            'settlement_allocation_id' => null,
            'reservation_id' => null,
            'result' => ReconciliationResult::PENDING->value,
            'result_status' => 'pending',
            'expected_amount' => null,
            'actual_amount' => null,
            'discrepancy_amount' => null,
            'discrepancy_reason' => null,
            'operator_id' => null,
            'operator_notes' => null,
            'execution_trigger' => 'system',
            'execution_context' => [],
            'attempt_number' => 1,
        ];
    }

    public function forReservation(int $reservationId): self
    {
        return $this->state(fn (array $attrs) => ['reservation_id' => $reservationId]);
    }

    public function exactMatch(): self
    {
        return $this->state(fn (array $attrs) => [
            'result' => ReconciliationResult::EXACT_MATCH->value,
            'result_status' => 'completed',
        ]);
    }

    public function discrepancy(): self
    {
        return $this->state(fn (array $attrs) => [
            'result' => ReconciliationResult::DISCREPANCY->value,
            'result_status' => 'discrepancy_held',
            'discrepancy_amount' => 50.00,
        ]);
    }
}

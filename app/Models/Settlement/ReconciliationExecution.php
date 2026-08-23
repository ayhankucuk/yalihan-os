<?php

namespace App\Models\Settlement;

use App\Enums\ReconciliationResult;
use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ReconciliationExecution — C5.1: APPEND-ONLY reconciliation attempt log
 *
 * REPLAY RULE (SAAB C5.1 Invariant):
 *   Reconciliations are NEVER updated or deleted.
 *   A replay creates a NEW execution record with attempt_number incremented.
 *   The old execution record remains immutable as audit evidence.
 *
 * SAAB C5.1 Scope boundary:
 *   RECONCILED ≠ PAYOUT_SETTLED.
 *   This record marks the matching result only. Actual payout release is C5.6.
 */
class ReconciliationExecution extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'reconciliation_executions';

    protected $fillable = [
        'tenant_id',
        'execution_type',
        'bank_transaction_id',
        'settlement_allocation_id',
        'reservation_id',
        'result',
        'result_status',
        'expected_amount',
        'actual_amount',
        'discrepancy_amount',
        'discrepancy_reason',
        'operator_id',
        'operator_notes',
        'execution_trigger',
        'execution_context',
        'attempt_number',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'bank_transaction_id' => 'integer',
        'settlement_allocation_id' => 'integer',
        'reservation_id' => 'integer',
        'expected_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'discrepancy_amount' => 'decimal:4',
        'operator_id' => 'integer',
        'execution_context' => 'array',
        'attempt_number' => 'integer',
        'result' => ReconciliationResult::class,
    ];

    /**
     * C5.1 Invariant: executions are APPEND-ONLY.
     * This model does not expose update() or delete() for production use.
     * Replays increment attempt_number and create new records.
     */
    public function isDiscrepancy(): bool
    {
        return $this->result === ReconciliationResult::DISCREPANCY;
    }

    public function isReconciled(): bool
    {
        return in_array($this->result, [ReconciliationResult::EXACT_MATCH, ReconciliationResult::WITHIN_TOLERANCE], true);
    }
}

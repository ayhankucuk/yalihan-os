<?php

namespace App\Models\Settlement;

use App\Enums\BankTransactionMatchStatus;
use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BankTransaction — C5.1: RAW immutable bank account movement
 *
 * One row per bank statement line item from ingest.
 * All "raw_" columns are immutable.
 * No UPDATE or DELETE on existing evidence.
 *
 * C5.1 scope: CSV/MT940 import only. No actual bank API (C5.3 deferred).
 */
class BankTransaction extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'bank_transactions';

    protected $fillable = [
        'tenant_id',
        'bank_account_id',
        'transaction_date',
        'value_date',
        'amount',
        'currency',
        'debit_credit',
        'reference_text',
        'iban',
        'sender_name',
        'raw_payload',
        'source',
        'source_reference',
        'match_status',
        'matched_settlement_id',
        'reconciliation_execution_id',
        'ingestion_status',
        'idempotency_key',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'bank_account_id' => 'integer',
        'transaction_date' => 'date',
        'value_date' => 'date',
        'amount' => 'decimal:4',
        'matched_settlement_id' => 'integer',
        'reconciliation_execution_id' => 'integer',
        'match_status' => BankTransactionMatchStatus::class,
        'raw_payload' => 'array',
    ];

    /**
     * Idempotent ingest: check for existing record.
     */
    public static function findByIdempotencyKey(string $key, int $tenantId): ?self
    {
        return static::withoutGlobalScopes()
            ->where('idempotency_key', $key)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function reconciliationExecution(): BelongsTo
    {
        return $this->belongsTo(ReconciliationExecution::class, 'reconciliation_execution_id');
    }
}

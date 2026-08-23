<?php

namespace App\Models\Settlement;

use App\Enums\AllocationStatus;
use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SettlementAllocation — C5.1: Per-reservation allocation from a provider settlement batch
 *
 * One row per reservation within a provider settlement batch.
 * Immutable after creation.
 */
class SettlementAllocation extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'settlement_allocations';

    protected $fillable = [
        'tenant_id',
        'provider_settlement_id',
        'reservation_id',
        'reconciliation_execution_id',
        'gross_amount',
        'channel_fee_amount',
        'net_amount',
        'currency',
        'allocation_status',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'provider_settlement_id' => 'integer',
        'reservation_id' => 'integer',
        'reconciliation_execution_id' => 'integer',
        'gross_amount' => 'decimal:4',
        'channel_fee_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'allocation_status' => AllocationStatus::class,
    ];

    public function providerSettlement(): BelongsTo
    {
        return $this->belongsTo(ProviderSettlement::class, 'provider_settlement_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PropertyReservation::class, 'reservation_id');
    }

    public function reconciliationExecution(): BelongsTo
    {
        return $this->belongsTo(ReconciliationExecution::class, 'reconciliation_execution_id');
    }
}

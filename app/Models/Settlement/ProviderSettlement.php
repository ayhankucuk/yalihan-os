<?php

namespace App\Models\Settlement;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Enums\SettlementStatus;
use App\Enums\VccStatus;
use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProviderSettlement — C5.1: RAW immutable OTA/channel payout evidence
 *
 * One row per external payout event from a provider (Booking.com, Airbnb, etc.).
 * All "raw_" prefixed columns are immutable after creation.
 * No UPDATE or DELETE is permitted on existing evidence.
 *
 * Scope exclusions (C5.1 = foundation only):
 *   - No ledger posting (C5.5)
 *   - No payout release (C5.6)
 *   - No actual bank API integration (C5.3)
 */
class ProviderSettlement extends BaseModel
{
    use HasFactory;
    use HasCountryScope;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'provider_settlements';

    protected $fillable = [
        'tenant_id',
        'provider',
        'external_settlement_id',
        'external_reservation_id',
        'reservation_id',
        // RAW immutable amounts
        'gross_amount',
        'channel_fee_amount',
        'net_amount',
        'currency',
        // RAW immutable payout metadata
        'payout_type',
        'payout_status',
        'bank_transfer_reference',
        'payout_date',
        'value_date',
        // VCC fields (C5.1-D01: VCC Status Parity)
        'vcc_status',
        'vcc_reference',
        'vcc_charged_amount',
        'vcc_charge_date',
        'vcc_currency',
        // RAW immutable payload
        'raw_payload',
        'raw_source',
        // State
        'settlement_status',
        'allocated_to_id',
        // Idempotency
        'idempotency_key',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'reservation_id' => 'integer',
        'gross_amount' => 'decimal:4',
        'channel_fee_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'payout_date' => 'date',
        'value_date' => 'date',
        'raw_payload' => 'array',
        'allocated_to_id' => 'integer',
        'payout_type' => PayoutType::class,
        'payout_status' => PayoutStatus::class,
        'settlement_status' => SettlementStatus::class,
        'vcc_status' => VccStatus::class,
        'vcc_charged_amount' => 'decimal:4',
        'vcc_charge_date' => 'date',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────────────────────────

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PropertyReservation::class, 'reservation_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SettlementAllocation::class, 'provider_settlement_id');
    }

    public function reconciliationExecution(): BelongsTo
    {
        return $this->belongsTo(ReconciliationExecution::class, 'allocated_to_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Idempotent ingest: check for existing record by idempotency key.
     */
    public static function findByIdempotencyKey(string $key, int $tenantId): ?self
    {
        return static::withoutGlobalScopes()
            ->where('idempotency_key', $key)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Canonical check: is this settlement settled (RECONCILED status)?
     */
    public function isReconciled(): bool
    {
        return $this->settlement_status === SettlementStatus::RECONCILED;
    }

    // ──────────────────────────────────────────────────────────────
    // VCC helpers (C5.1-D01 Recovery)
    // Booking.com wire contract: AVAILABLE, NOT_LOADED, FUNDED,
    // PARTIALLY_CHARGED, FULLY_CHARGED, CANCELLED, UNKNOWN.
    // Chargeability: only FUNDED → true.
    // ──────────────────────────────────────────────────────────────

    /**
     * Is this a VCC settlement (has VCC reference)?
     * VCC settlements are separate from bank transfer settlements.
     */
    public function isVcc(): bool
    {
        return !empty($this->vcc_reference);
    }

    /**
     * Is the VCC in a chargeable state?
     * Booking.com semantics: only FUNDED → chargeable.
     * AVAILABLE, NOT_LOADED, PARTIALLY_CHARGED, FULLY_CHARGED, CANCELLED, UNKNOWN → false.
     */
    public function isVccChargeable(): bool
    {
        return $this->vcc_status !== null && $this->vcc_status->isChargeable();
    }

    /**
     * Is the VCC in a terminal state?
     * Booking.com semantics: FULLY_CHARGED, CANCELLED, UNKNOWN → terminal.
     */
    public function isVccTerminal(): bool
    {
        return $this->vcc_status !== null && $this->vcc_status->isTerminal();
    }
}

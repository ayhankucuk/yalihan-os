<?php

namespace App\Domains\Finance\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PayoutReconciliation
 *
 * EX-002 Finance Agent — WAVE 1
 *
 * Bir Airbnb payout import kaydını rezervasyon bazında eşleştirir.
 * Her kayıt tek bir rezervasyona ait komisyon ve net ödeme hesabını içerir.
 * Idempotency: idempotency_key unique constraint ile sağlanır.
 *
 * @property int    $id
 * @property int    $tenant_id
 * @property int    $airbnb_payout_import_id
 * @property int|null $reservation_id
 * @property int|null $ilan_id
 * @property string $idempotency_key
 * @property float  $reservation_amount
 * @property float  $yalihan_commission_rate
 * @property float  $yalihan_commission_amount
 * @property float  $owner_net_amount
 * @property string $currency
 * @property string $reconciliation_status
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property string|null $notes
 */
class PayoutReconciliation extends BaseModel
{
    use SoftDeletes;

    protected $table = 'payout_reconciliations';

    protected $fillable = [
        'tenant_id',
        'airbnb_payout_import_id',
        'reservation_id',
        'ilan_id',
        'idempotency_key',
        'reservation_amount',
        'yalihan_commission_rate',
        'yalihan_commission_amount',
        'owner_net_amount',
        'currency',
        'reconciliation_status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'tenant_id'                 => 'integer',
        'airbnb_payout_import_id'   => 'integer',
        'reservation_id'            => 'integer',
        'ilan_id'                   => 'integer',
        'reservation_amount'        => 'decimal:2',
        'yalihan_commission_rate'   => 'decimal:2',
        'yalihan_commission_amount' => 'decimal:2',
        'owner_net_amount'          => 'decimal:2',
        'approved_at'               => 'datetime',
    ];

    // ─── Status Constants ────────────────────────────────────────────────────

    public const STATUS_PENDING          = 'pending';
    public const STATUS_MATCHED          = 'matched';
    public const STATUS_UNMATCHED        = 'unmatched';
    public const STATUS_DISPUTED         = 'disputed';
    public const STATUS_APPROVED         = 'approved';

    // ─── Relationships ───────────────────────────────────────────────────────

    public function payoutImport(): BelongsTo
    {
        return $this->belongsTo(AirbnbPayoutImport::class, 'airbnb_payout_import_id');
    }

    public function ownerPayout(): BelongsTo
    {
        return $this->belongsTo(OwnerPayout::class, 'ilan_id', 'ilan_id');
    }

    // ─── Business Methods ────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->reconciliation_status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->reconciliation_status === self::STATUS_APPROVED;
    }

    public function isDisputed(): bool
    {
        return $this->reconciliation_status === self::STATUS_DISPUTED;
    }

    public function approve(int $approvedBy): void
    {
        if (!in_array($this->reconciliation_status, [self::STATUS_MATCHED, self::STATUS_PENDING], true)) {
            throw new \LogicException(
                "Cannot approve reconciliation #{$this->id}: status is '{$this->reconciliation_status}', expected 'matched' or 'pending'."
            );
        }

        $this->reconciliation_status = self::STATUS_APPROVED;
        $this->approved_by = $approvedBy;
        $this->approved_at = now();
        $this->save();
    }

    public function markAsMatched(): void
    {
        if ($this->reconciliation_status === self::STATUS_APPROVED) {
            throw new \LogicException(
                "Cannot mark reconciliation #{$this->id} as matched: already approved."
            );
        }

        $this->reconciliation_status = self::STATUS_MATCHED;
        $this->save();
    }

    public function markAsUnmatched(string $reason): void
    {
        if ($this->reconciliation_status === self::STATUS_APPROVED) {
            throw new \LogicException(
                "Cannot mark approved reconciliation #{$this->id} as unmatched."
            );
        }

        $this->reconciliation_status = self::STATUS_UNMATCHED;
        $this->notes = $reason;
        $this->save();
    }

    public function markAsDisputed(string $reason): void
    {
        if ($this->reconciliation_status === self::STATUS_APPROVED) {
            throw new \LogicException(
                "Cannot mark approved reconciliation #{$this->id} as disputed."
            );
        }

        $this->reconciliation_status = self::STATUS_DISPUTED;
        $this->notes = $reason;
        $this->save();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('reconciliation_status', self::STATUS_MATCHED);
    }

    public function scopeForImport($query, int $importId)
    {
        return $query->where('airbnb_payout_import_id', $importId);
    }
}

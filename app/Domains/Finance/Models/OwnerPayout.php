<?php

namespace App\Domains\Finance\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * OwnerPayout
 *
 * EX-002 Finance Agent — WAVE 1
 *
 * Bir dönem için ev sahibine yapılacak net ödemeyi temsil eder.
 * Birden fazla PayoutReconciliation kaydından türetilir.
 * Idempotency: idempotency_key unique constraint ile sağlanır.
 *
 * @property int    $id
 * @property int    $tenant_id
 * @property int    $owner_kisi_id
 * @property int    $ilan_id
 * @property string $idempotency_key
 * @property string $period_start
 * @property string $period_end
 * @property float  $gross_rental_income
 * @property float  $total_yalihan_commission
 * @property float  $net_owner_payout
 * @property string $currency
 * @property int    $reconciliation_count
 * @property string $payout_status
 * @property int|null $prepared_by
 * @property \Carbon\Carbon|null $prepared_at
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property int|null $paid_by
 * @property \Carbon\Carbon|null $paid_at
 * @property string|null $payment_reference
 * @property string|null $notes
 */
class OwnerPayout extends BaseModel
{
    use SoftDeletes;

    protected $table = 'owner_payouts';

    protected $fillable = [
        'tenant_id',
        'owner_kisi_id',
        'ilan_id',
        'idempotency_key',
        'period_start',
        'period_end',
        'gross_rental_income',
        'total_yalihan_commission',
        'net_owner_payout',
        'currency',
        'reconciliation_count',
        'payout_status',
        'prepared_by',
        'prepared_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'tenant_id'               => 'integer',
        'owner_kisi_id'           => 'integer',
        'ilan_id'                 => 'integer',
        'gross_rental_income'     => 'decimal:2',
        'total_yalihan_commission'=> 'decimal:2',
        'net_owner_payout'        => 'decimal:2',
        'reconciliation_count'    => 'integer',
        'period_start'            => 'date',
        'period_end'              => 'date',
        'prepared_at'             => 'datetime',
        'approved_at'             => 'datetime',
        'paid_at'                 => 'datetime',
    ];

    // ─── Status Constants ────────────────────────────────────────────────────

    public const STATUS_DRAFT            = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED         = 'approved';
    public const STATUS_PAID             = 'paid';
    public const STATUS_CANCELLED        = 'cancelled';

    // ─── Relationships ───────────────────────────────────────────────────────

    public function reconciliations(): HasMany
    {
        // BLOCKER FIX: ilan_id tek başına yeterli değil.
        // Period + tenant bazında scoped sorgu OwnerPayoutPreparationService'te yapılıyor.
        // Bu relation yalnızca tenant-scoped dönem filtresiyle kullanılmalıdır.
        return $this->hasMany(PayoutReconciliation::class, 'ilan_id', 'ilan_id')
            ->where('tenant_id', $this->tenant_id)
            ->where('reconciliation_status', PayoutReconciliation::STATUS_APPROVED);
    }

    // ─── Business Methods ────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->payout_status === self::STATUS_DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return $this->payout_status === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->payout_status === self::STATUS_APPROVED;
    }

    public function isPaid(): bool
    {
        return $this->payout_status === self::STATUS_PAID;
    }

    public function submitForApproval(int $preparedBy): void
    {
        if (!$this->isDraft()) {
            throw new \LogicException(
                "Cannot submit payout #{$this->id} for approval: current status is '{$this->payout_status}', expected 'draft'."
            );
        }

        $this->payout_status = self::STATUS_PENDING_APPROVAL;
        $this->prepared_by   = $preparedBy;
        $this->prepared_at   = now();
        $this->save();
    }

    public function approve(int $approvedBy): void
    {
        // BLOCKER FIX: state transition bypass engeli
        if (!$this->isPendingApproval() && !$this->isDraft()) {
            throw new \LogicException(
                "Cannot approve payout #{$this->id}: current status is '{$this->payout_status}', expected 'pending_approval' or 'draft'."
            );
        }

        $this->payout_status = self::STATUS_APPROVED;
        $this->approved_by   = $approvedBy;
        $this->approved_at   = now();
        $this->save();
    }

    public function markAsPaid(int $paidBy, string $paymentReference): void
    {
        // BLOCKER FIX: sadece approved payout ödenebilir
        if (!$this->isApproved()) {
            throw new \LogicException(
                "Cannot mark payout #{$this->id} as paid: current status is '{$this->payout_status}', expected 'approved'."
            );
        }

        $this->payout_status      = self::STATUS_PAID;
        $this->paid_by            = $paidBy;
        $this->paid_at            = now();
        $this->payment_reference  = $paymentReference;
        $this->save();
    }

    public function cancel(string $reason): void
    {
        if ($this->isPaid()) {
            throw new \LogicException(
                "Cannot cancel payout #{$this->id}: already paid."
            );
        }

        $this->payout_status = self::STATUS_CANCELLED;
        $this->notes         = $reason;
        $this->save();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('payout_status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeForOwner($query, int $ownerKisiId)
    {
        return $query->where('owner_kisi_id', $ownerKisiId);
    }

    public function scopeForPeriod($query, string $periodStart, string $periodEnd)
    {
        return $query->where('period_start', '>=', $periodStart)
                     ->where('period_end', '<=', $periodEnd);
    }
}

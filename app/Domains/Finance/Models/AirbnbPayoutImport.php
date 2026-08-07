<?php

namespace App\Domains\Finance\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AirbnbPayoutImport
 *
 * EX-002 Finance Agent — WAVE 1
 *
 * Airbnb platformundan gelen ham payout verisini temsil eder.
 * Her kayıt bir Airbnb payout transaction'ına karşılık gelir.
 * Idempotency: airbnb_payout_id unique constraint ile sağlanır.
 *
 * @property int    $id
 * @property int    $tenant_id
 * @property string $airbnb_payout_id
 * @property string $period_start
 * @property string $period_end
 * @property float  $gross_amount
 * @property float  $airbnb_fees
 * @property float  $net_amount
 * @property string $currency
 * @property array|null $raw_payload
 * @property string $import_status
 * @property int|null $imported_by
 * @property \Carbon\Carbon|null $imported_at
 * @property string|null $error_message
 */
class AirbnbPayoutImport extends BaseModel
{
    use SoftDeletes;

    protected $table = 'airbnb_payout_imports';

    protected $fillable = [
        'tenant_id',
        'airbnb_payout_id',
        'period_start',
        'period_end',
        'gross_amount',
        'airbnb_fees',
        'net_amount',
        'currency',
        'raw_payload',
        'import_status',
        'imported_by',
        'imported_at',
        'error_message',
    ];

    protected $casts = [
        'tenant_id'    => 'integer',
        'gross_amount' => 'decimal:2',
        'airbnb_fees'  => 'decimal:2',
        'net_amount'   => 'decimal:2',
        'raw_payload'  => 'array',
        'imported_at'  => 'datetime',
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    // ─── Status Constants ───────────────────────────────────────────────────

    public const STATUS_PENDING     = 'pending';
    public const STATUS_PROCESSING  = 'processing';
    public const STATUS_RECONCILED  = 'reconciled';
    public const STATUS_FAILED      = 'failed';

    // ─── Relationships ───────────────────────────────────────────────────────

    public function reconciliations(): HasMany
    {
        return $this->hasMany(PayoutReconciliation::class, 'airbnb_payout_import_id');
    }

    // ─── Business Methods ────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->import_status === self::STATUS_PENDING;
    }

    public function isReconciled(): bool
    {
        return $this->import_status === self::STATUS_RECONCILED;
    }

    public function isFailed(): bool
    {
        return $this->import_status === self::STATUS_FAILED;
    }

    public function markAsProcessing(): void
    {
        // BLOCKER FIX: state transition guard
        if ($this->import_status === self::STATUS_RECONCILED) {
            throw new \LogicException(
                "Cannot mark import #{$this->id} as processing: already reconciled."
            );
        }

        $this->import_status = self::STATUS_PROCESSING;
        $this->save();
    }

    public function markAsReconciled(): void
    {
        if ($this->import_status === self::STATUS_FAILED) {
            throw new \LogicException(
                "Cannot mark import #{$this->id} as reconciled: current status is 'failed'. Fix errors first."
            );
        }

        $this->import_status = self::STATUS_RECONCILED;
        $this->save();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->import_status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->save();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('import_status', self::STATUS_PENDING);
    }

    public function scopeForPeriod($query, string $periodStart, string $periodEnd)
    {
        return $query->where('period_start', '>=', $periodStart)
                     ->where('period_end', '<=', $periodEnd);
    }
}

<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LedgerEntry Model
 * 🛡️ SAB §12: Finance Domain Hardening (Legacy Debt)
 *
 * @deprecated Use App\Models\LedgerEntry instead (richer schema with FX, transaction_group, immutable support)
 * This class exists only for backward compatibility and will be removed in future versions.
 */
class LedgerEntry extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'account_id',
        'reference_type',
        'reference_id',
        'debit_amount',
        'credit_amount',
        'description',
        'entry_date',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'entry_date' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }
}

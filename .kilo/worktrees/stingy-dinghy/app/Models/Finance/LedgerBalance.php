<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * LedgerBalance Model
 * 🛡️ SAB §12: Finance Domain Hardening (Legacy Debt)
 *
 * @deprecated Use App\Models\LedgerBalance instead (richer schema with total_debit, total_credit, version support)
 * This class exists only for backward compatibility and will be removed in future versions.
 */
class LedgerBalance extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'account_id',
        'currency',
        'net_balance',
    ];

    protected $casts = [
        'net_balance' => 'decimal:2',
    ];
}

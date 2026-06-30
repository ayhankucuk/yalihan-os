<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * FinancialSetting Model
 * 🛡️ SAB §12: Finance Domain Hardening
 */
class FinancialSetting extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'tenant_id',
        'default_commission_rate',
        'min_commission_rate',
        'max_commission_rate',
        'office_share',
        'agent_share',
        'payment_delay_days',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'default_commission_rate' => 'decimal:2',
        'min_commission_rate' => 'decimal:2',
        'max_commission_rate' => 'decimal:2',
        'office_share' => 'decimal:2',
        'agent_share' => 'decimal:2',
    ];
}

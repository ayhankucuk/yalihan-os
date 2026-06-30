<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class CountryFinancialRule extends BaseModel
{
    use HasCountryScope;

    protected $table = 'country_financial_rules';

    protected $fillable = [
        'country_code',
        'country_name',
        'rental_commission_rate',
        'sales_commission_rate',
        'advisory_fee_rate',
        'tax_rate',
        'default_currency',
        'is_active',
    ];

    protected $casts = [
        'rental_commission_rate' => 'float',
        'sales_commission_rate'  => 'float',
        'advisory_fee_rate'      => 'float',
        'tax_rate'               => 'float',
        'is_active'        => \App\Enums\AktiflikDurumu::class,
    ];
}

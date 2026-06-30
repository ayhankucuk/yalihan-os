<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transaction Model
 * Finance write authority for legacy transactions table.
 */
class Transaction extends BaseModel
{
    use HasCountryScope;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'ilan_id',
        'islem_turu',
        'islem_tutari',
        'currency',
        'payment_method',
        'payment_date',
        'description',
        'receipt_number',
        'bank_reference',
        'is_verified',
        'recorded_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'islem_tutari' => 'decimal:2',
        'is_verified' => 'boolean',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
    ];
}

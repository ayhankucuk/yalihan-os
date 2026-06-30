<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends BaseModel
{
    use HasCountryScope;

    protected $table = 'financial_transactions';

    // Immutable audit trail — no soft delete
    protected $fillable = [
        'property_id',
        'reservation_id',
        'country_code',
        'base_currency',
        'base_amount',
        'display_currency',
        'display_amount',
        'fx_rate_locked',
        'islem_tipi',
        'islem_durumu',
        'created_by',
        'sebep',
        'kaynak',
    ];

    protected $casts = [
        'base_amount'    => 'float',
        'display_amount' => 'float',
        'fx_rate_locked' => 'float',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'property_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class, 'reservation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}

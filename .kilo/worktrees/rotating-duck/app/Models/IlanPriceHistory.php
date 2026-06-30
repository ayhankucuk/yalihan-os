<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IlanPriceHistory extends BaseModel
{
    use HasCountryScope;

    public $timestamps = false;

    protected $table = 'ilan_price_history';

    protected $fillable = [
        'ilan_id',
        'old_price',
        'new_price',
        'currency',
        'change_reason',
        'changed_by',
        'additional_data',
        'created_at',
    ];

    protected $casts = [
        'old_price' => 'float',
        'new_price' => 'float',
        'additional_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

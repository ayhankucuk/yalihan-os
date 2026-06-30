<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bonus Model
 * 🛡️ SAB §12: Finance Domain Hardening
 */
class Bonus extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'tenant_id',
        'agent_id',
        'target_month',
        'prim_tutari',
        'bonus_type',
        'reason',
        'is_paid',
        'paid_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'prim_tutari' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}

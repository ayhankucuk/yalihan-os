<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Models\SaaS\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiCreditBalance Model
 * 🛡️ SAB §12.2: Credit-Based AI Guard (Circuit Breaker SSOT)
 */
class AiCreditBalance extends BaseModel
{
    protected $table = 'ai_credit_balances';

    protected $fillable = [
        'tenant_id',
        'available_credits',
        'used_credits',
        'monthly_limit',
        'last_reset_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'available_credits' => 'integer',
        'used_credits' => 'integer',
        'monthly_limit' => 'integer',
        'last_reset_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the credit balance.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Helper to check if credits are available.
     */
    public function hasCredits(int $amount = 1): bool
    {
        return $this->available_credits >= $amount;
    }
}

<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiWorkspaceWallet extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_workspace_wallets';

    protected $fillable = [
        'tenant_id',
        'balance',
        'currency',
        'low_balance_threshold',
        'durum'
    ];

    protected $casts = [
        'balance' => 'integer',
        'low_balance_threshold' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AiTransaction::class, 'wallet_id');
    }
}

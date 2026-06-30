<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiTransaction extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_transactions';

    // Immutable: Disable timestamps (we only have created_at)
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'amount',
        'final_balance',
        'reason',
        'reference_type',
        'reference_id',
        'meta',
        'idempotency_key'
    ];

    protected $casts = [
        'amount' => 'integer',
        'final_balance' => 'integer',
        'meta' => 'array',
        'created_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });

        static::updating(function ($model) {
            throw new \Exception("AiTransaction is immutable.");
        });
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(AiWorkspaceWallet::class, 'wallet_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

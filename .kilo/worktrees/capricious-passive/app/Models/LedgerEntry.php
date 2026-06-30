<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ledger_entries';

    public $timestamps = false; // Immutable, handled manually

    protected $fillable = [
        'tenant_id',
        'transaction_group_id',
        'account_id',
        'debit_amount',
        'credit_amount',
        'currency',
        'fx_rate_locked',
        'base_amount',
        'reference_type',
        'reference_id',
        'sebep',
        'kaynak',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'fx_rate_locked' => 'decimal:6',
        'base_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

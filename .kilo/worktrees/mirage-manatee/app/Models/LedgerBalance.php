<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class LedgerBalance extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ledger_balances';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'currency',
        'total_debit',
        'total_credit',
        'net_balance',
        'version'
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'net_balance' => 'decimal:2',
        'version' => 'integer'
    ];

    /**
     * Get the account that owns the balance.
     */
    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }
}

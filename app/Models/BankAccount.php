<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BankAccount — C5.1/C5.3: Bank account metadata for settlement reconciliation
 *
 * Stores bank account metadata used for bank transaction ingestion.
 * One row per bank account per tenant.
 *
 * C5.1 scope: This is a reference table. Actual bank API integration is C5.3 deferred.
 */
class BankAccount extends BaseModel
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'tenant_id',
        'bank_name',
        'account_name',
        'iban',
        'account_number',
        'currency',
        'account_type',
        'is_active',
        'source',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get bank transactions for this account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(\App\Models\Settlement\BankTransaction::class, 'bank_account_id');
    }
}

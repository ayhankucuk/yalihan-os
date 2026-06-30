<?php

namespace App\Models\SaaS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingLedgerEntry extends Model
{
    // 🛡️ GOVERNANCE: Append-only model. Update/Delete is forbidden by policy.
    protected $fillable = [
        'tenant_id', 'islem_turu', 'islem_tutari', 'currency', 'reference_type', 'reference_id', 'metadata'
    ];

    protected $casts = [
        'islem_tutari' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

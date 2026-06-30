<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LedgerAccount Model
 * 🛡️ SAB §12: Finance Domain Hardening (Legacy Debt)
 *
 * @deprecated Use App\Models\LedgerAccount instead (richer schema with FX support)
 * This class exists only for backward compatibility and will be removed in future versions.
 */
class LedgerAccount extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'name',
        'tip',
        'code',
        'description',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    public function balance(): HasMany
    {
        return $this->hasMany(LedgerBalance::class, 'account_id');
    }
}

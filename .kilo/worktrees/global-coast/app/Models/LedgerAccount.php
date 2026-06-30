<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerAccount extends BaseModel
{
    use HasFactory;
    use HasCountryScope;
    use SoftDeletes;

    protected $table = 'ledger_accounts';

    protected $fillable = [
        'tenant_id',
        'name',
        'tip',
        'currency',
        'ulke_id',
        'display_order',
        'aktiflik_durumu',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'aktiflik_durumu' => 'boolean',
        'display_order' => 'integer',
        'ulke_id' => 'integer',
    ];

    public function ulke(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'ulke_id');
    }

    public function balances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LedgerBalance::class, 'account_id');
    }
}

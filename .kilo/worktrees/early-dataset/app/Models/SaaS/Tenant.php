<?php

namespace App\Models\SaaS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = ['uuid', 'name', 'domain', 'status'];

    /**
     * Get the tenant's current subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    /**
     * Get all subscriptions for the tenant.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all billing ledger entries.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(BillingLedgerEntry::class);
    }
}

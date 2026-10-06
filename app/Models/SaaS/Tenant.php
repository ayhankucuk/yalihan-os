<?php

namespace App\Models\SaaS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    // context7-ignore: durum, status, is_active — physical DB column names retained for backward compat
    // aktiflik_durumu: Context7 canonical — HuntOpportunitiesCommand reads this column
    protected $fillable = ['uuid', 'name', 'domain', 'status', 'durum', 'aktiflik_durumu'];

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

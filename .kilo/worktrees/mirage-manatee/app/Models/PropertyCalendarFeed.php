<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyCalendarFeed extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'property_id',
        'provider',
        'ical_url',
        'sync_enabled',
        'sync_frequency_minutes',
        'last_synced_at',
        'last_sync_hash',
        'last_sync_error',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'property_id');
    }
}

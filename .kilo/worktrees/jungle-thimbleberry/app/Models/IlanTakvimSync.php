<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class IlanTakvimSync extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_takvim_sync';

    protected $fillable = [
        'ilan_id',
        'platform',
        'external_calendar_id',
        'external_listing_id',
        'is_sync_active',
        'auto_sync',
        'last_sync_at',
        'next_sync_at',
        'sync_interval_minutes',
        'sync_settings',
        'api_key',
        'api_secret',
        'senkron_durumu',
        'last_error',
        'last_error_at',
        'sync_count',
        'error_count',
    ];

    protected $casts = [
        'auto_sync' => 'boolean',
        'last_sync_at' => 'datetime',
        'next_sync_at' => 'datetime',
        'last_error_at' => 'datetime',
        'sync_settings' => 'array',
        'sync_interval_minutes' => 'integer',
        'sync_count' => 'integer',
        'error_count' => 'integer',
    ];

    public function ilan()
    {
        return $this->belongsTo(Ilan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('senkron_durumu', 'active'); // context7-ignore
    }
}

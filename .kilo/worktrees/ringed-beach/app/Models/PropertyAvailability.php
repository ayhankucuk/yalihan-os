<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyAvailability extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'property_id',
        'date',
        'is_available',
        'block_reason',
        'source_system',
        'external_ref',
        'reservation_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'property_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class, 'reservation_id');
    }
}

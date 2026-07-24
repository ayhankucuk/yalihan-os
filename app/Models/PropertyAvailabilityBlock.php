<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyAvailabilityBlock extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'property_availability_blocks';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'reservation_id',
        'block_type',
        'starts_at',
        'ends_at',
        'status',
        'source',
        'idempotency_key',
        'released_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'reservation_id' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class, 'reservation_id');
    }
}

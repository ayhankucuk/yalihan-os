<?php

namespace App\Models\Calendar;

use App\Models\BaseModel;
use App\Models\CommercialOffering;
use App\Models\Property;
use App\Models\PropertyReservation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnifiedCalendarProjection extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'property_id',
        'commercial_offering_id',
        'reservation_id',
        'availability_block_id',
        'calendar_date',
        'source_type',
        'status',
        'nightly_rate',
        'currency',
        'is_checkin_day',
        'is_checkout_day',
        'guest_name',
        'external_source',
        'source_event_id',
        'last_projected_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'property_id' => 'integer',
        'commercial_offering_id' => 'integer',
        'reservation_id' => 'integer',
        'availability_block_id' => 'integer',
        'calendar_date' => 'string',
        'nightly_rate' => 'decimal:2',
        'is_checkin_day' => 'boolean',
        'is_checkout_day' => 'boolean',
        'last_projected_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(PropertyReservation::class, 'reservation_id');
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CommercialOffering::class, 'commercial_offering_id');
    }
}

<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use App\Traits\SabGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FeatureAssignment extends BaseModel
{
    use HasFactory;
    use SabGuard;
    use HasCountryScope;

    protected $fillable = [
        'feature_id',
        'assignable_type',
        'assignable_id',
        'main_category_id',
        'sub_category_id',
        'listing_type_id',
        'scope_type',
        'value',
        'is_required',
        'is_visible',
        'is_inherited',
        'origin_category_name',
        'source_type',
        'metadata',
        'display_order',
        'conditional_logic',
        'group_name',
        'aktiflik_durumu', // Context7: is_active → aktiflik_durumu (canonical)
        'label_override',
        'field_slug',
        'field_type',
        'options_json',
        'rolled_back_at',
        'created_by',
        'updated_by',
        'visible_if_json',
        'required_if_json',
        'enabled_if_json',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
        'is_inherited' => 'boolean',
        'display_order' => 'integer',
        'conditional_logic' => 'array',
        'metadata' => 'array',
        'options_json' => 'array',
        'visible_if_json' => 'array',
        'required_if_json' => 'array',
        'enabled_if_json' => 'array',
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class, // Context7: is_active → aktiflik_durumu
        'rolled_back_at' => 'datetime',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}

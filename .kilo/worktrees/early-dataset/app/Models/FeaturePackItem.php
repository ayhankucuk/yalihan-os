<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use App\Traits\SabGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * UPS Feature Pack Item Model
 *
 * Context7 Compliance: Join table for pack ↔ features
 * - display_order: canonical integer flag
 */
class FeaturePackItem extends BaseModel
{
    use HasFactory;
    use SabGuard;
    use HasCountryScope;

    protected $table = 'ups_feature_pack_items';

    protected $fillable = [
        'feature_pack_id',
        'feature_id',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Get parent pack
     */
    public function pack()
    {
        return $this->belongsTo(FeaturePack::class, 'feature_pack_id');
    }

    /**
     * Get feature
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order'); // context7-ignore
    }
}

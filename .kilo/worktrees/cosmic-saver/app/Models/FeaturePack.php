<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use App\Traits\SabGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * UPS Feature Pack Model
 *
 * Context7 Compliance: Feature bundles (Airbnb, Booking.com presets)
 * - slug: snake_case normalized
 * - aktiflik_durumu: canonical boolean flag
 * - display_order: canonical integer flag
 */
class FeaturePack extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use SabGuard;
    use HasCountryScope;

    protected $table = 'ups_feature_packs';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'display_order',
        'aktiflik_durumu',
    ];

    protected $casts = [
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer',
    ];

    /**
     * Boot model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pack) {
            if (empty($pack->slug)) {
                // Normalize: lowercase, replace - with _, alphanumeric + underscore only
                $pack->slug = Str::slug($pack->name, '_');
                $pack->slug = preg_replace('/[^a-z0-9_]/', '', strtolower($pack->slug));
            }
        });
    }

    /**
     * Get pack items (features)
     */
    public function items()
    {
        return $this->hasMany(FeaturePackItem::class, 'feature_pack_id');
    }

    /**
     * Get features through items
     */
    public function features()
    {
        return $this->belongsToMany(Feature::class, 'ups_feature_pack_items', 'feature_pack_id', 'feature_id')
            ->withPivot('display_order')
            ->withTimestamps()
            ->orderBy('ups_feature_pack_items.display_order'); // context7-ignore
    }

    /**
     * Scope: Enabled packs only
     */
    public function scopeEnabled($query)
    {
        return $query->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name'); // context7-ignore
    }

    /**
     * Add feature to pack (idempotent)
     */
    public function addFeature(Feature $feature, int $displayOrder = 0): bool
    {
        $existing = FeaturePackItem::where('feature_pack_id', $this->id)
            ->where('feature_id', $feature->id)
            ->first();

        if ($existing) {
            return false; // Already exists, skipped
        }

        FeaturePackItem::create([
            'feature_pack_id' => $this->id,
            'feature_id' => $feature->id,
            'display_order' => $displayOrder,
        ]);

        return true; // Created
    }

    /**
     * Remove feature from pack
     */
    public function removeFeature(Feature $feature): bool
    {
        return FeaturePackItem::where('feature_pack_id', $this->id)
            ->where('feature_id', $feature->id)
            ->delete() > 0;
    }

    /**
     * Get feature slugs array
     */
    public function getFeatureSlugs(): array
    {
        return $this->features()->pluck('slug')->toArray();
    }
}

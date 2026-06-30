<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use App\Traits\SabGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FeatureCategory extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use SabGuard;
    use HasCountryScope;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'display_order',
        'applies_to', // ✅ Uygulama alanı (hangi kategorilere uygulanır)
    ];

    protected $casts = [
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer',
        'applies_to' => 'array', // ✅ JSON array olarak cast et
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get all features in this category
     * Context7: Veritabanında feature_category_id kolonu var
     */
    public function features()
    {
        return $this->hasMany(Feature::class, 'feature_category_id');
    }

    /**
     * Get only active features
     */
    public function activeFeatures()
    {
        return $this->features()
            ->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF)
            ->orderBy('display_order'); // context7-ignore
    }

    /**
     * Scope: Only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type); // context7-ignore
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        // ✅ NULL display_order'ları en sona at
        return $query->orderByRaw('COALESCE(display_order, 999999) ASC') // context7-ignore
                     ->orderBy('name'); // context7-ignore
    }

    /**
     * Get applies_to as reliable array
     * Handles both JSON string and array types
     */
    public function getAppliesToArrayAttribute()
    {
        $applies = $this->applies_to;

        if (is_array($applies)) {
            return $applies;
        }

        if (is_string($applies)) {
            $decoded = json_decode($applies, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            // Legacy fallback: "konut,arsa" comma-separated
            if (!empty($applies)) {
                return array_map('trim', explode(',', $applies));
            }
        }

        return [];
    }

    /**
     * Get IlanKategori instances from applies_to slugs
     */
    public function getAppliedKategorilerAttribute()
    {
        $slugs = $this->applies_to_array;

        if (empty($slugs)) {
            return collect();
        }

        return \App\Models\IlanKategori::whereIn('slug', $slugs)
            ->where('aktiflik_durumu', true)
            ->orderBy('name') // context7-ignore
            ->get();
    }
}
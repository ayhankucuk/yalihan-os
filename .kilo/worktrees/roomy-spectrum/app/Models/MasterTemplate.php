<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Master Template Model
 *
 * Represents a reusable template of features that can be applied
 * to multiple categories/yayin tipleri at once.
 *
 * Context7 Compliance:
 * - aktiflik_durumu: ✅ Standardized
 * - display_order: ✅ Standardized
 */
class MasterTemplate extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'master_templates';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'feature_ids',
        'metadata',
        'is_active',
        'display_order',
        'created_by',
    ];

    protected $casts = [
        'feature_ids' => 'array',
        'metadata' => 'array',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }

            if (is_null($template->display_order)) {
                $template->display_order = static::max('display_order') + 1;
            }
        });
    }

    /**
     * Get features collection
     */
    public function getFeatures()
    {
        if (empty($this->feature_ids)) {
            return collect();
        }

        return Feature::whereIn('id', $this->feature_ids)
            ->where('is_active', \App\Enums\AktiflikDurumu::AKTIF)
            ->ordered() // context7-ignore
            ->get();
    }

    /**
     * Get feature count
     */
    public function getFeatureCountAttribute(): int
    {
        return count($this->feature_ids ?? []);
    }

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name'); // context7-ignore
    }

    /**
     * Add feature to template
     */
    public function addFeature(int $featureId): void
    {
        $featureIds = $this->feature_ids ?? [];

        if (!in_array($featureId, $featureIds)) {
            $featureIds[] = $featureId;
            $this->feature_ids = $featureIds;
            $this->save();
        }
    }

    /**
     * Remove feature from template
     */
    public function removeFeature(int $featureId): void
    {
        $featureIds = $this->feature_ids ?? [];
        $featureIds = array_filter($featureIds, fn($id) => $id !== $featureId);
        $this->feature_ids = array_values($featureIds);
        $this->save();
    }

    /**
     * Check if has feature
     */
    public function hasFeature(int $featureId): bool
    {
        return in_array($featureId, $this->feature_ids ?? []);
    }

    /**
     * Get metadata value
     */
    public function getMeta(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Set metadata value
     */
    public function setMeta(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
        $this->save();
    }
}

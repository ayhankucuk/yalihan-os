<?php

namespace App\Models;

use App\Enums\UpsFeatureLifecycle;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Feature extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type', // context7-ignore
        'options',
        'unit',
        'feature_category_id',
        'display_order',
        'is_required',
        'is_filterable',
        'is_searchable',
        'aktiflik_durumu',
        'lifecycle',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_searchable' => 'boolean',
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer',
        'lifecycle' => UpsFeatureLifecycle::class,
        'deprecated_at' => 'datetime',
        'archived_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($feature) {
            if (empty($feature->slug)) {
                $feature->slug = Str::slug($feature->name);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(FeatureCategory::class, 'feature_category_id');
    }

    public function assignments()
    {
        return $this->hasMany(FeatureAssignment::class);
    }

    /**
     * Scope: Sort features consistently for admin and API consumers.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw('COALESCE(display_order, 999999) ASC') // context7-ignore
            ->orderBy('name'); // context7-ignore
    }

    /**
     * Scope: Keep only assignable features (exclude archived/deprecated records).
     */
    public function scopeAssignable(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->whereNull('deprecated_at');
    }

    /**
     * Scope: Filter by lifecycle state.
     */
    public function scopeLifecycle(Builder $query, UpsFeatureLifecycle $state): Builder
    {
        return $query->where('lifecycle', $state->value);
    }

    /**
     * Scope: Only active features (aktiflik_durumu = AKTIF).
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * Scope: Features with no assignments (orphaned).
     */
    public function scopeOrphaned(Builder $query): Builder
    {
        return $query->whereDoesntHave('assignments');
    }
}

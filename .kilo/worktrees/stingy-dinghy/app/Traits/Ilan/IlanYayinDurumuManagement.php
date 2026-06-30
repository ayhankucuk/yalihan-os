<?php

namespace App\Traits\Ilan;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

trait IlanYayinDurumuManagement
{
    /**
     * Bootable trait method for creating/updating slugs.
     */
    public static function bootIlanYayinDurumuManagement(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug) && ! empty($model->baslik)) {
                $model->slug = Str::slug($model->baslik.'-'.uniqid());
            }
        });

        static::updating(function ($model) {
            if (empty($model->slug) && ! empty($model->baslik)) {
                $model->slug = Str::slug($model->baslik.'-'.uniqid());
            }
        });
    }

    /**
     * Bootable trait method for global scopes and tracking.
     */
    public static function bootedIlanYayinDurumuManagement(): void
    {
        static::addGlobalScope('visibility', function (\Illuminate\Database\Eloquent\Builder $builder) {
            // Default ordering by visibility_score descending
            $builder->orderBy('visibility_score', 'desc'); // context7-ignore
        });

        // SAB Phase 17B: Minimum Tracking
        static::creating(function ($ilan) {
            if (auth()->check()) {
                $ilan->created_by = auth()->id();
                $ilan->updated_by = auth()->id();
            }
        });

        static::updating(function ($ilan) {
            if (auth()->check()) {
                $ilan->updated_by = auth()->id();
            }
        });
    }

    /**
     * Activity Log Configuration
     * Architectural Enhancement: Audit trail for all listing changes
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'baslik',
                'fiyat',
                'yayin_durumu',
                'il_id',
                'ilce_id',
                'mahalle_id',
                'danisman_id',
                'ana_kategori_id',
                'alt_kategori_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "İlan {$eventName}")
            ->useLogName('ilanlar');
    }
}

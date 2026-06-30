<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiExperiment extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_deneyler';

    protected $fillable = [
        'deney_adi',
        'deney_slug',
        'hedef_kategori',
        'varyasyonlar',
        'kazanan_varyasyon_anahtari',
        'baslangic_tarihi',
        'bitis_tarihi',
        'is_active'
    ];

    protected $casts = [
        'varyasyonlar' => 'array',
        'baslangic_tarihi' => 'datetime',
        'bitis_tarihi' => 'datetime',
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Get related feature usages
     */
    public function usages(): HasMany
    {
        return $this->hasMany(AiFeatureUsage::class, 'deney_id');
    }

    /**
     * Scope for active experiments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF)
                     ->where(function ($q) {
                         $q->whereNull('baslangic_tarihi')
                           ->orWhere('baslangic_tarihi', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('bitis_tarihi')
                           ->orWhere('bitis_tarihi', '>=', now());
                     });
    }
}

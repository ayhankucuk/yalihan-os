<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * AnalyticsReport Model
 *
 * Phase 6: Analytics Dashboard & Reporting
 * Context7 Compliance: Uses canonical field 'rapor_durumu'
 */
class AnalyticsReport extends BaseModel
{
    use HasCountryScope;

    protected $table = 'analytics_reports';

    protected $fillable = [
        'user_id',
        'rapor_adi',
        'rapor_durumu',      // ✅ SAB Canonical (hazirlanıyor|tamamlandı|gonderildi|hata)
        'siralama_sirasi',   // ✅ SAB Canonical (replaced 'display_order')
        'is_active',   // ✅ SAB Canonical (replaced 'is_active', 'enabled', 'aktif')
        'baslangic_tarihi',
        'bitis_tarihi',
        'parametreler',
        'dosya_yolu',
    ];

    protected $casts = [
        'parametreler' => 'array',
        'baslangic_tarihi' => 'datetime',
        'bitis_tarihi' => 'datetime',
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

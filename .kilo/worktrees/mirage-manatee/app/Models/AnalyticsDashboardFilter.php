<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * AnalyticsDashboardFilter Model
 *
 * Phase 6: Analytics Dashboard & Reporting
 * Context7 Compliance: Uses canonical fields 'analiz_durumu', 'varsayilan_mi'
 */
class AnalyticsDashboardFilter extends BaseModel
{
    use HasCountryScope;

    protected $table = 'analytics_dashboard_filters';

    protected $fillable = [
        'user_id',
        'filtre_adi',
        'analiz_durumu',     // ✅ SAB Canonical (aktif|sonlandırıldı|kilitli|arsiv)
        'siralama_sirasi',   // ✅ SAB Canonical (replaced 'display_order')
        'is_active',   // ✅ SAB Canonical (replaced 'is_active', 'enabled')
        'varsayilan_mi',     // ✅ SAB Canonical (replaced 'is_default', 'is_primary')
        'filtre_kurallari',
    ];

    protected $casts = [
        'filtre_kurallari' => 'array',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'varsayilan_mi' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

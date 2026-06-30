<?php

namespace App\Models;

use App\Traits\HasCountryScope;

/**
 * OwnerReportMetric Model (Aggregated Read Model)
 * Context7 Standard: C7-OWNER-METRIC-READ-MODEL-V1
 */
class OwnerReportMetric extends BaseModel
{
    use HasCountryScope;

    protected $table = 'owner_report_metrics';

    protected $fillable = [
        'owner_id',
        'ilan_id',
        'periyot_tipi',
        'periyot_degeri',
        'toplam_gelir',
        'toplam_gider',
        'net_kar',
        'doluluk_orani',
        'rezervasyon_sayisi',
    ];

    protected $casts = [
        'toplam_gelir' => 'decimal:2',
        'toplam_gider' => 'decimal:2',
        'net_kar' => 'decimal:2',
        'doluluk_orani' => 'decimal:2',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}

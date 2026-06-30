<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use App\Modules\Finans\Models\Komisyon;

/**
 * OwnerReportRow Model (Read-Only Projection)
 * Context7 Standard: C7-OWNER-REPORT-READ-MODEL-V1
 */
class OwnerReportRow extends BaseModel
{
    use HasCountryScope;

    protected $table = 'owner_report_rows';

    protected $fillable = [
        'owner_id',
        'ilan_id',
        'kayit_tarihi',
        'islem_tipi',
        'tutar',
        'para_birimi',
        'aciklama',
        'metadata',
    ];

    protected $casts = [
        'kayit_tarihi' => 'date',
        'tutar' => 'decimal:2',
        'metadata' => 'array',
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

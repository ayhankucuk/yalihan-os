<?php

namespace App\Models;

use App\Traits\HasCountryScope;

/**
 * OwnerReportExport Model
 * Context7 Standard: C7-OWNER-EXPORT-TRACKER-V1
 */
class OwnerReportExport extends BaseModel
{
    use HasCountryScope;

    protected $table = 'owner_report_exports';

    protected $fillable = [
        'owner_id',
        'dosya_adi',
        'dosya_yolu',
        'format',
        'islem_durumu', // bekliyor, isleniyor, tamamlandi, hata
        'filtreler',
        'tamamlanma_tarihi',
        'hata_mesaji',
    ];

    protected $casts = [
        'filtreler' => 'array',
        'tamamlanma_tarihi' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

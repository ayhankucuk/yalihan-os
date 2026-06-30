<?php

namespace App\Models\Deprecated;


/**
 * Turizm (yazlık) detail table for ilan listings.
 * Referenced by: CortexROIEngine, CortexGoldenVisaAnalyzer, IlanVerticalDomainService,
 *                CortexPDFReportGenerator, IlanCrudService, Ilan::ensureDetailTableExists()
 */
class IlanTurizmDetail extends \App\Models\BaseModel
{
    protected $table = 'ilan_turizm_details';

    protected $fillable = [
        'ilan_id',
        'check_in_saati',
        'check_out_saati',
        'min_konaklama',
        'max_misafir',
        'gunluk_fiyat',
        'temizlik_ucreti',
        'havuz_var',
        'sezon_baslangic',
        'sezon_bitis',
    ];

    protected $casts = [
        'min_konaklama' => 'integer',
        'max_misafir' => 'integer',
        'gunluk_fiyat' => 'decimal:2',
        'temizlik_ucreti' => 'decimal:2',
        'havuz_var' => 'boolean',
        'sezon_baslangic' => 'date',
        'sezon_bitis' => 'date',
    ];

    public function ilan()
    {
        return $this->belongsTo(\App\Models\Ilan::class, 'ilan_id');
    }
}

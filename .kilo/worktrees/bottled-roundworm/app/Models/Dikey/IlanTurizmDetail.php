<?php

namespace App\Models\Dikey;

use App\Models\BaseModel;

/**
 * Turizm (yazlık) dikey detay tablosu.
 *
 * Tablo: ilan_turizm_details
 * Namespace: App\Models\Dikey (B-006 Seçenek A — 2026-06-12)
 *
 * Kullanıcılar: IlanCrudService, IlanRelationships, IlanDetailTables,
 *               CortexROIEngine, IlanVerticalDomainService, CortexPDFReportGenerator
 *
 * @see docs/known-debt.md T-UPS-V2 — JSONB göçü planlanıyor
 */
class IlanTurizmDetail extends BaseModel
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
        'min_konaklama'   => 'integer',
        'max_misafir'     => 'integer',
        'gunluk_fiyat'    => 'decimal:2',
        'temizlik_ucreti' => 'decimal:2',
        'havuz_var'       => 'boolean',
        'sezon_baslangic' => 'date',
        'sezon_bitis'     => 'date',
    ];

    public function ilan()
    {
        return $this->belongsTo(\App\Models\Ilan::class, 'ilan_id');
    }
}

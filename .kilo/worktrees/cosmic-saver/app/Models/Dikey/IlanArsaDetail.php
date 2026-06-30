<?php

namespace App\Models\Dikey;

use App\Models\BaseModel;

/**
 * Arsa dikey detay tablosu.
 *
 * Tablo: ilan_arsa_details
 * Namespace: App\Models\Dikey (B-006 Seçenek A — 2026-06-12)
 *
 * Kullanıcılar: IlanCrudService, IlanRelationships, IlanDetailTables,
 *               CortexROIEngine, IlanVerticalDomainService, CortexPDFReportGenerator
 *
 * @see docs/known-debt.md T-UPS-V2 — JSONB göçü planlanıyor
 */
class IlanArsaDetail extends BaseModel
{
    protected $table = 'ilan_arsa_details';

    protected $fillable = [
        'ilan_id',
        'ada_no',
        'parsel_no',
        'imar_durumu',
        'kaks',
        'taks',
    ];

    protected $casts = [
        'kaks' => 'decimal:2',
        'taks' => 'decimal:2',
    ];

    public function ilan()
    {
        return $this->belongsTo(\App\Models\Ilan::class, 'ilan_id');
    }
}

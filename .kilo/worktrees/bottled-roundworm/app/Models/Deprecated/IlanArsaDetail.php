<?php

namespace App\Models\Deprecated;


/**
 * Arsa detail table for ilan listings.
 * Referenced by: CortexROIEngine, IlanVerticalDomainService,
 *                CortexPDFReportGenerator, IlanCrudService, Ilan::ensureDetailTableExists()
 */
class IlanArsaDetail extends \App\Models\BaseModel
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

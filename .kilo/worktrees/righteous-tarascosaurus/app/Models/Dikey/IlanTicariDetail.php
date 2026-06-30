<?php

namespace App\Models\Dikey;

use App\Models\BaseModel;
use App\Models\Ilan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IlanTicariDetail — İşyeri/Ticari dikey detay modeli
 *
 * Context7: Dikey domain modeli (app/Models/Dikey/)
 * Table: ilan_ticari_details
 *
 * B-006 P5D: Deprecated\IlanTicariDetail ghost → App\Models\Dikey\IlanTicariDetail
 */
class IlanTicariDetail extends BaseModel
{
    protected $table = 'ilan_ticari_details';

    protected $fillable = [
        'ilan_id',
        'isyeri_tipi',
        'kira_bilgisi',
        'kira_getirisi',
        'kat_adedi',
        'ofis_adedi',
        'asansor_var',
        'depo_var',
        'otopark_var',
        'ek_bilgiler',
    ];

    protected $casts = [
        'kira_getirisi' => 'decimal:2',
        'kat_adedi'     => 'integer',
        'ofis_adedi'    => 'integer',
        'asansor_var'   => 'boolean',
        'depo_var'      => 'boolean',
        'otopark_var'   => 'boolean',
        'ek_bilgiler'   => 'array',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}

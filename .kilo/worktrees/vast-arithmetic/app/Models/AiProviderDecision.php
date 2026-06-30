<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProviderDecision extends BaseModel
{
    use HasCountryScope;

    protected $fillable = [
        'correlation_id',
        'kategori_id',
        'yayin_tipi_id',
        'chosen_provider',
        'scores_json',
        'reason_json',
        'debug_metadata'
    ];

    protected $casts = [
        'scores_json' => 'array',
        'reason_json' => 'array',
        'debug_metadata' => 'array',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }

    public function yayinTipi(): BelongsTo
    {
        return $this->belongsTo(YayinTipiSablonu::class, 'yayin_tipi_id');
    }
}

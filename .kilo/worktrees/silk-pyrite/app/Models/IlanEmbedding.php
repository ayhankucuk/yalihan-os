<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Ilan;
use App\Traits\HasActiveScope;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ilan Embedding Model
 *
 * Context7 Standardı: C7-ILAN-EMBEDDING-MODEL-2026-01-19
 */
class IlanEmbedding extends BaseModel
{
    use HasFactory;
    use HasActiveScope;
    use HasCountryScope;

    protected $table = 'ilan_embeddings';

    protected $fillable = [
        'ilan_id',
        'embedding',
        'model_name',
        'dimensions',
        'is_active', // Context7: aktiflik_durumu
        'display_order',   // Context7: display_order
    ];

    protected $casts = [
        'embedding' => 'array',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'dimensions' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * İlan ilişkisi
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}

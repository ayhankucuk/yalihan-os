<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lead Embedding Model
 *
 * Context7 Standardı: C7-LEAD-EMBEDDING-MODEL-2026-01-13
 */
class LeadEmbedding extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'lead_embeddings';

    protected $fillable = [
        'lead_id',
        'kisi_id',
        'embedding',
        'model_name',
        'dimensions',
        'aktiflik_durumu',
        'display_order'
    ];

    protected $casts = [
        'embedding' => 'array',
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer'
    ];

    /**
     * Relationship with Lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * Relationship with Kisi (Contact)
     */
    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    /**
     * Scope for active embeddings
     */
    public function scopeAktif($query)
    {
        return $query->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İlan Resim Model
 *
 * Context7 standartlarına uygun ilan resim yönetimi
 */
class IlanResim extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_resimleri';

    protected $fillable = [
        'ilan_id',
        'dosya_adi',
        'dosya_yolu',
        'dosya_boyutu',
        'mime_type',
        'sira_no',
        'ana_resim',
        'alt_text',
        'aciklama',
    ];

    protected $casts = [
        'ana_resim' => 'boolean',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * İlan ilişkisi
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Scope: Aktif resimler
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * Scope: Ana resimler
     */
    public function scopeAnaResim($query)
    {
        return $query->where('ana_resim', true);
    }

    /**
     * Scope: Sıraya göre sırala
     */
    public function scopeSirali($query)
    {
        return $query->orderBy('sira_no', 'asc'); // context7-ignore
    }

    /**
     * Resim URL'sini al
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->dosya_yolu);
    }
}

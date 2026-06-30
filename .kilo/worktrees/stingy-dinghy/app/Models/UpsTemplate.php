<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class UpsTemplate extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'ups_templates';

    protected $fillable = [
        'yayin_tipi_sablonu_id',
        'kategori_id',
        'yayin_tipi_id',
        'template_json',
        'template_version',
        'template_hash',
        'sealed_at',
        'sealed_by_user_id',
        'is_active',
        'active_junction_id', // NULL=inactive | yayin_tipi_sablonu_id=active (UNIQUE partial) // context7-ignore
    ];

    protected $casts = [
        'template_json' => 'array',
        'sealed_at' => 'datetime',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'template_version' => 'integer'
    ];

    /**
     * Relationship to the junction table
     */
    public function junction()
    {
        return $this->belongsTo(YayinTipiSablonu::class, 'yayin_tipi_sablonu_id');
    }

    /**
     * Ilgili kategori
     */
    public function kategori()
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }

    /**
     * Ilgili yayin tipi sablonu
     */
    public function yayinTipi()
    {
        return $this->belongsTo(YayinTipiSablonu::class, 'yayin_tipi_sablonu_id');
    }

    /**
     * Self-referential master template (ust sablona referans)
     * ConfigSnapshotService with(['masterTemplate']) icin gerekli
     */
    public function masterTemplate()
    {
        return $this->belongsTo(UpsTemplate::class, 'yayin_tipi_sablonu_id');
    }

    /**
     * Scope for active templates
     */
    /**
     * Boot the model.
     *
     * Sealed Guard: Mühürlenmiş template'in içerik alanları değiştirilemez.
     * aktiflik_durumu ve active_junction_id deactivation için izin verilir.
     */
    protected static function booted(): void
    {
        static::updating(function (self $template): void {
            if ($template->getOriginal('sealed_at') === null) {
                return; // Not sealed — allow all updates
            }

            // Sealed template: only deactivation fields are allowed
            $allowedKeys = ['is_active', 'active_junction_id', 'deleted_at']; // context7-ignore
            $dirty = array_keys($template->getDirty());
            $forbidden = array_diff($dirty, $allowedKeys);

            if (!empty($forbidden)) {
                throw new \RuntimeException(
                    'SealedTemplateViolation: Mühürlenmiş template değiştirilemez. '
                    . 'Değiştirilen alanlar: ' . implode(', ', $forbidden)
                    . ' (Template ID: ' . $template->getKey() . ')'
                );
            }
        });
    }

    /**
     * Aktif şablonları döndürür (Context7 kanonik scope adı).
     * ORDER BY id DESC ensures deterministic first() when invariant is violated.
     *
     * @deprecated scopeActive() → scopeAktif() (Context7 kanonik)
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', 1)->orderBy('id', 'desc'); // context7-ignore
    }

    /**
     * @deprecated Kullan: scopeAktif()
     */
    public function scopeActive($query)
    {
        return $this->scopeAktif($query);
    }

    /**
     * Scope for specific context
     */
    public function scopeForContext($query, int $kategoriId, int $yayinTipiId)
    {
        return $query->where('kategori_id', $kategoriId)
                     ->where('yayin_tipi_id', $yayinTipiId);
    }
}

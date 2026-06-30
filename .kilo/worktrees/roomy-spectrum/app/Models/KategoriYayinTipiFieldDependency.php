<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KategoriYayinTipiFieldDependency extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'kategori_yayin_tipi_field_dependencies';

    protected $fillable = [
        'kategori_slug',
        'yayin_tipi_id', // WFC-002
        'yayin_tipi',    // Legacy/Sync
        'field_slug',
        'field_name',
        'field_type',
        'field_category',
        'field_options',
        'field_unit',
        'field_icon',
        'is_active', // Context7
        'required',
        'display_order', // ord&#101;r
        'ai_auto_fill',
        'ai_suggestion',
        'ai_prompt_key',
        'searchable',
        'show_in_card',
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'yayin_tipi_id' => 'integer',
        'required' => 'boolean',
        'ai_auto_fill' => 'boolean',
        'ai_suggestion' => 'boolean',
        'searchable' => 'boolean',
        'show_in_card' => 'boolean',
        'display_order' => 'integer', // ord&#101;r
    ];

    /**
     * Scope: Aktif field'ları getir
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * Scope: Kategori ve yayın tipine göre filtrele
     */
    public function scopeForKategoriYayinTipi($query, $kategoriSlug, $yayinTipi)
    {
        $query->where('kategori_slug', $kategoriSlug);

        if (is_numeric($yayinTipi)) {
            return $query->where('yayin_tipi_id', $yayinTipi);
        }

        return $query->where('yayin_tipi', $yayinTipi);
    }

    /**
     * Scope: AI özellikli field'ları getir
     */
    public function scopeWithAI($query)
    {
        return $query->where(function ($q) {
            $q->where('ai_auto_fill', true)
                ->orWhere('ai_suggestion', true);
        });
    }

    /**
     * Scope: Kategorieye göre filtrele
     */
    public function scopeForKategori($query, $kategoriSlug)
    {
        return $query->where('kategori_slug', $kategoriSlug);
    }

    /**
     * Scope: Yayın tipine göre filtrele
     */
    public function scopeForYayinTipi($query, $yayinTipi)
    {
        if (is_numeric($yayinTipi)) {
            return $query->where('yayin_tipi_id', $yayinTipi);
        }

        return $query->where('yayin_tipi', $yayinTipi);
    }

    /**
     * Scope: Field kategorisine göre filtrele
     */
    public function scopeForCategory($query, $fieldCategory)
    {
        return $query->where('field_category', $fieldCategory);
    }

    /**
     * Sıralama scope'u
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('field_name'); // ord&#101;r // context7-ignore
    }
}

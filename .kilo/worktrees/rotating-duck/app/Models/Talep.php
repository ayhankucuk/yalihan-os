<?php

namespace App\Models;

use App\Models\Ilan;
use App\Traits\HasActiveScope;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Talep extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasActiveScope;
    use HasCountryScope;

    protected $table = 'talepler';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'baslik',          // ✅ Added 2026-01-31
        'aciklama',        // ✅ Added 2026-01-31
        'talep_tipi',
        'emlak_tipi',
        'talep_durumu',   // ✅ SAB: Canonical naming convention
        'oncelik',
        'one_cikan',      // ✅ Added 2026-01-31
        'kisi_id',        // Context7: kisi_id → kisi_id (reform 2025-11-24)
        'danisman_id',
        'alt_kategori_id', // ✅ Added 2026-01-31
        // Context7: category_id yerine alt_kategori_id
        'il_id',
        'ilce_id',
        'mahalle_id',      // ✅ Added 2026-01-31
        'min_fiyat',
        'max_fiyat',
        // Context7: Talep reformu - 2025-11-24
        // Context7: Talep reformu - 2025-11-24
        // Context7: Talep reformu - 2025-11-24
        'notlar',

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_fiyat' => 'decimal:2',
        'max_fiyat' => 'decimal:2',
        'min_metrekare' => 'integer',
        'max_metrekare' => 'integer',
        'aranan_ozellikler_json' => 'array',
        'metadata' => 'array',
        'talep_durumu' => \App\Enums\TalepDurumu::class, // ✅ SAB: talep_durumu (TalepDurumu Enum)
        'deleted_at' => 'datetime',
    ];

    // --- İLİŞKİLER ---

    /**
     * Talebi oluşturan kişi (müşteri).
     */
    public function kisi()
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    /**
     * Talebi sisteme ekleyen kullanıcı (danışman).
     */
    public function kullanici()
    {
        return $this->belongsTo(User::class, 'kullanici_id');
    }

    /**
     * Talebin danışmanı (alias for danisman_id).
     * Context7: danisman relationship
     */
    public function danisman()
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }

    /**
     * Talebin ait olduğu ilan kategorisi.
     * Context7: category_id yerine alt_kategori_id kullanılmalı
     */
    public function kategori()
    {
        return $this->belongsTo(IlanKategori::class, 'alt_kategori_id');
    }

    /**
     * Alt kategori (alias)
     * Context7 uyumlu naming
     */
    public function altKategori()
    {
        return $this->belongsTo(IlanKategori::class, 'alt_kategori_id');
    }

    // --- GLOBAL ADRES İLİŞKİLERİ ---

    public function ulke()
    {
        return $this->belongsTo(Ulke::class, 'ulke_id');
    }

    // Context7 kuralı: il() relationship kullanımı

    public function il()
    {
        return $this->belongsTo(Il::class, 'il_id');
    }

    public function ilce()
    {
        return $this->belongsTo(Ilce::class, 'ilce_id');
    }

    public function mahalle()
    {
        return $this->belongsTo(Mahalle::class, 'mahalle_id');
    }

    /**
     * Talebin eşleşmeleri (eslesmeler tablosu üzerinden)
     */
    public function eslesme()
    {
        return $this->hasMany(Eslesme::class, 'talep_id');
    }

    /**
     * Talebin eşleşmeleri (plural form - alias)
     */
    public function eslesmeler()
    {
        return $this->hasMany(Eslesme::class, 'talep_id');
    }

    /**
     * Talebin eşleştiği ilanlar
     */
    public function eslesenIlanlar()
    {
        return $this->belongsToMany(Ilan::class, 'eslesmeler', 'talep_id', 'ilan_id')
            ->withPivot('eslesme_durumu', 'notlar') // ✅ SAB: eslesme_durumu
            ->withTimestamps();
    }

    /**
     * Talebin eşleştiği ilanlar (Alias for eslesenIlanlar)
     */
    public function ilanlar()
    {
        return $this->eslesenIlanlar();
    }

    // --- ACCESSORS & MUTATORS ---

    /**
     * Tam adres bilgisini döndürür.
     */
    public function getTamAdresAttribute()
    {
        $adresParcalari = [
            $this->mahalle->name ?? null,
            $this->ilce->name ?? null,
            $this->il->il_adi ?? null,
            $this->ulke->name ?? null,
        ];

        return implode(', ', array_filter($adresParcalari));
    }



    /**
     * Durum filter (Context7 canonical: talep_durumu)
     */
    public function scopeByDurum($query, $talepDurumu)
    {
        if ($talepDurumu instanceof \App\Enums\TalepDurumu) {
            return $query->where('talep_durumu', $talepDurumu->value);
        }

        return $query->where('talep_durumu', $talepDurumu);
    }

    public function scopeActive($query)
    {
        return $query->byDurum(\App\Enums\TalepDurumu::AKTIF);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Support\Str;

/**
 * Özellik Kategorileri Modeli
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $aktiflik_durumu
 * @property int $display_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class OzellikKategori extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    /**
     * Tablo adı tanımlama
     */
    protected $table = 'ozellik_kategorileri';

    /**
     * Toplu atama için izin verilen alanlar
     * Context7 Mühürlü: Sadece veritabanındaki kolonlar
     * @sealed 2025-12-31
     */
    protected $fillable = [
        'name',
        'slug',
        'aciklama',
        'parent_id',
        'icon',
        'display_order',
        'is_active',
        // ✅ SAB: display_order, aktiflik_durumu added
    ];

    /**
     * Tip dönüşümleri
     * Context7 Mühürlü
     */
    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Model kayıt edilmeden önce slug oluşturma
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kategori) {
            if (empty($kategori->slug)) {
                $kategori->slug = Str::slug($kategori->name);
            }
            // Context7: display_order varsayılan değer atama
            if (is_null($kategori->display_order)) {
                $kategori->display_order = (int) (static::max('display_order') + 1);
            }
        });

        static::updating(function ($kategori) {
            if ($kategori->isDirty('name') && empty($kategori->slug)) {
                $kategori->slug = Str::slug($kategori->name);
            }
        });

        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('ozellik_kategorileri_full'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('ozellik_kategorileri_full'));
    }

    /**
     * Bu kategoriye ait özellikleri getiren ilişki
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ozellikler()
    {
        return $this->hasMany(Ozellik::class, 'kategori_id');
    }

    /**
     * Aktif kategorileri filtreleme
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF); // ✅ Aktif kategoriler (Context7)
    }

    /**
     * Arama filtresinde gösterilecek kategoriler
     */
    public function scopeAramaFiltresi($query)
    {
        return $query->where('arama_filtresi', true);
    }

    /**
     * İlan kartında gösterilecek kategoriler
     */
    public function scopeIlanKartindaGoster($query)
    {
        return $query->where('ilan_kartinda_goster', true);
    }

    /**
     * Detay sayfasında gösterilecek kategoriler
     */
    public function scopeDetaySayfasindaGoster($query)
    {
        return $query->where('detay_sayfasinda_goster', true);
    }

    /**
     * Zorunlu özellik kategorileri
     */
    public function scopeZorunlu($query)
    {
        return $query->where('zorunlu', true);
    }

    /**
     * Belirli veri tipindeki kategoriler
     */
    public function scopeVeriTipi($query, $tip)
    {
        return $query->where('veri_tipi', $tip);
    }

    /**
     * Belirli emlak türü için uyumlu kategoriler
     */
    public function scopeEmlakTuruIcin($query, $emlakTuru)
    {
        return $query->where(function ($q) use ($emlakTuru) {
            $q->whereJsonContains('uyumlu_emlak_turleri', $emlakTuru)
                ->orWhereNull('uyumlu_emlak_turleri');
        });
    }

    /**
     * Belirli kategori için uyumlu özellik kategorileri
     */
    public function scopeKategoriIcin($query, $kategoriId)
    {
        return $query->where(function ($q) use ($kategoriId) {
            $q->whereJsonContains('uyumlu_kategoriler', $kategoriId)
                ->orWhereNull('uyumlu_kategoriler');
        });
    }

    /**
     * Sıralı kategoriler
     */
    public function scopeSiralı($query)
    {
        return $query->orderBy('display_order'); // context7-ignore
    }

    /**
     * En çok kullanılan kategoriler
     */
    public function scopeEnCokKullanilan($query, $limit = 10)
    {
        return $query->orderBy('kullanim_sayisi', 'desc')->limit($limit); // context7-ignore
    }

    /**
     * Veri tipi etiketini getir
     */
    public function getVeriTipiEtiketiAttribute()
    {
        $etiketler = [
            'text' => 'Metin',
            'number' => 'Sayı',
            'boolean' => 'Evet/Hayır',
            'select' => 'Seçim',
            'multiselect' => 'Çoklu Seçim',
            'date' => 'Tarih',
            'file' => 'Dosya',
        ];

        return $etiketler[$this->veri_tipi] ?? $this->veri_tipi;
    }

    /**
     * Kategori rengini getir
     */
    public function getRenkAttribute()
    {
        return $this->renk_kodu ?: $this->getVarsayilanRenk();
    }

    /**
     * Varsayılan kategori rengi
     */
    protected function getVarsayilanRenk()
    {
        $renkler = [
            'text' => '#6B7280',
            'number' => '#3B82F6',
            'boolean' => '#10B981',
            'select' => '#F59E0B',
            'multiselect' => '#8B5CF6',
            'date' => '#EF4444',
            'file' => '#06B6D4',
        ];

        return $renkler[$this->veri_tipi] ?? '#6B7280';
    }
}

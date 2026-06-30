<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Photo extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ilan_fotograflari';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ilan_id',
        'dosya_yolu', // ✅ SAB: Tablodaki gerçek kolon adı
        'dosya_adi',
        'dosya_boyutu',
        'mime_type',
        'kapak_fotografi', // ✅ SAB: Tablodaki gerçek kolon adı
        'display_order', // ✅ SAB: Tablodaki gerçek kolon adı
        'aciklama',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'kapak_fotografi' => 'boolean',
        'display_order' => 'integer',
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Fotoğraf silindiğinde dosyaları da sil
        static::deleting(function ($photo) {
            if ($photo->isForceDeleting()) {
                // Hard delete - dosyaları sil
                if ($photo->dosya_yolu) {
                    Storage::disk('public')->delete($photo->dosya_yolu);
                }
            }
        });
    }

    /**
     * Relationship: İlan
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class);
    }

    /**
     * Scope: Öne çıkan fotoğraflar
     */
    public function scopeFeatured($query)
    {
        return $query->where('kapak_fotografi', true);
    }

    /**
     * Scope: Sıralı fotoğraflar
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at'); // context7-ignore
    }

    /**
     * Accessor: Fotoğraf URL'i
     */
    public function getUrlAttribute()
    {
        return $this->dosya_yolu ? Storage::url($this->dosya_yolu) : null;
    }

    /**
     * Accessor: Thumbnail URL'i (dosya_yolu kullanarak)
     */
    public function getThumbnailUrlAttribute()
    {
        return $this->url; // Thumbnail için ayrı kolon yok, aynı dosya_yolu kullanılıyor
    }

    /**
     * Backward compatibility: path attribute
     */
    public function getPathAttribute()
    {
        return $this->dosya_yolu;
    }

    /**
     * Backward compatibility: thumbnail attribute
     */
    public function getThumbnailAttribute()
    {
        return null; // Thumbnail kolonu yok
    }

    /**
     * Backward compatibility: one_cikan attribute
     */
    public function getIsFeaturedAttribute()
    {
        return $this->kapak_fotografi;
    }

    /**
     * Accessor: Dosya boyutu (human readable)
     */
    public function getFormattedSizeAttribute()
    {
        if (! $this->dosya_boyutu) {
            return null;
        }

        // dosya_boyutu string olarak saklanıyor, parse et
        $size = is_numeric($this->dosya_boyutu) ? (int) $this->dosya_boyutu : 0;
        if ($size === 0) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;

        return number_format($size / pow(1024, $power), 2).' '.$units[$power];
    }

    /**
     * Helper: Öne çıkarılmış mı?
     */
    public function isFeatured()
    {
        return $this->kapak_fotografi;
    }

    /**
     * Helper: Öne çıkar
     */
    public function setAsFeatured()
    {
        // Önce bu ilanın diğer fotoğraflarını featured'dan çıkar
        static::where('ilan_id', $this->ilan_id)
            ->where('id', '!=', $this->id)
            ->update(['kapak_fotografi' => false]);

        $this->update(['kapak_fotografi' => true]);

        return $this;
    }

    /**
     * Helper: Featured'dan çıkar
     */
    public function unsetAsFeatured()
    {
        $this->update(['kapak_fotografi' => false]);

        return $this;
    }

    /**
     * Get image URL (alias for url attribute)
     * Used in views for consistency
     */
    public function getImageUrl()
    {
        return $this->url;
    }

    /**
     * Get thumbnail URL (alias for thumbnail_url attribute)
     * Used in views for consistency
     */
    public function getThumbnailImageUrl()
    {
        return $this->thumbnail_url;
    }
}

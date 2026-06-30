<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ConfigOption Model
 *
 * Kategori ve Yayın Tipi bazlı dinamik config seçenekleri.
 * Önceki Deprecated\ConfigOption ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property string      $option_key
 * @property string      $option_type   simple|associative|object_array|nested
 * @property mixed       $option_value  JSON
 * @property int|null    $kategori_id
 * @property int|null    $yayin_tipi_id
 * @property string|null $label
 * @property string|null $description
 * @property string|null $icon
 * @property bool        $aktiflik_durumu
 * @property int         $display_order
 */
class ConfigOption extends BaseModel
{
    use HasCountryScope;

    protected $table = 'config_options';

    protected $fillable = [
        'option_key',
        'option_type',
        'option_value',
        'kategori_id',
        'yayin_tipi_id',
        'label',
        'description',
        'icon',
        'aktiflik_durumu',
        'display_order',
    ];

    protected $casts = [
        'option_value'    => 'array',
        'aktiflik_durumu' => 'boolean',
        'display_order'   => 'integer',
    ];

    // -------------------------------------------------------------------------
    // İlişkiler
    // -------------------------------------------------------------------------

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }

    public function yayinTipi(): BelongsTo
    {
        return $this->belongsTo(YayinTipiSablonu::class, 'yayin_tipi_id');
    }

    // -------------------------------------------------------------------------
    // Scope'lar
    // -------------------------------------------------------------------------

    /** Aktif config seçenekleri */
    public function scopeActive($query) // context7-ignore
    {
        return $query->where('aktiflik_durumu', true);
    }

    /** Belirli option_key'e göre filtrele */
    public function scopeForOptionKey($query, string $optionKey)
    {
        return $query->where('option_key', $optionKey);
    }

    /** Kategori bazlı filtrele (null = global) */
    public function scopeForKategori($query, ?int $kategoriId)
    {
        return $query->where('kategori_id', $kategoriId);
    }

    /** Yayın tipi bazlı filtrele (null = global) */
    public function scopeForYayinTipi($query, ?int $yayinTipiId)
    {
        return $query->where('yayin_tipi_id', $yayinTipiId);
    }

    /** display_order'a göre sırala */
    public function scopeOrdered($query) // context7-ignore
    {
        return $query->orderBy('display_order')->orderBy('id'); // ✅ deterministic
    }

    // -------------------------------------------------------------------------
    // Statik Yardımcılar
    // -------------------------------------------------------------------------

    /**
     * En spesifik config'i getir — önce kategori+yayinTipi, sonra sadece kategori,
     * en son global (null, null) sırasıyla dener.
     *
     * ConfigOptionHelper::fetchFromDatabase() tarafından çağrılır.
     */
    public static function getMostSpecific(
        string $optionKey,
        ?int $kategoriId = null,
        ?int $yayinTipiId = null
    ): ?self {
        // 1. Tam eşleşme — kategori + yayın tipi
        if ($kategoriId && $yayinTipiId) {
            $result = self::forOptionKey($optionKey)
                ->forKategori($kategoriId)
                ->forYayinTipi($yayinTipiId)
                ->active() // context7-ignore
                ->orderBy('id')
                ->first();

            if ($result) {
                return $result;
            }
        }

        // 2. Sadece kategori
        if ($kategoriId) {
            $result = self::forOptionKey($optionKey)
                ->forKategori($kategoriId)
                ->whereNull('yayin_tipi_id')
                ->active() // context7-ignore
                ->orderBy('id')
                ->first();

            if ($result) {
                return $result;
            }
        }

        // 3. Global (her ikisi de null)
        return self::forOptionKey($optionKey)
            ->whereNull('kategori_id')
            ->whereNull('yayin_tipi_id')
            ->active() // context7-ignore
            ->orderBy('id')
            ->first();
    }
}

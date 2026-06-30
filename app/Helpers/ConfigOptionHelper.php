<?php

namespace App\Helpers;

use App\Models\ConfigOption;
use Illuminate\Support\Facades\Cache;

/**
 * Config Option Helper
 *
 * Config seçeneklerini database'den çeker, fallback olarak config dosyasını kullanır
 * Context7: C7-CONFIG-OPTIONS-HELPER-2025-12-15
 *
 * Cache Strategy:
 * - Tag-based cache (Redis kullanılıyorsa)
 * - Fallback to simple cache key
 */

/**
 * Config Option Helper
 *
 * Config seçeneklerini database'den çeker, fallback olarak config dosyasını kullanır
 * Context7: C7-CONFIG-OPTIONS-HELPER-2025-12-15
 */
class ConfigOptionHelper
{
    /**
     * Config seçeneğini getir
     *
     * @param string $optionKey Option key (örn: 'oda_sayisi_options')
     * @param int|null $kategoriId Kategori ID (opsiyonel)
     * @param int|null $yayinTipiId Yayın tipi ID (opsiyonel)
     * @param mixed $default Varsayılan değer (config dosyasından)
     * @return mixed
     */
    public static function get($optionKey, $kategoriId = null, $yayinTipiId = null, $default = null)
    {
        // Cache key oluştur
        $cacheKey = self::getCacheKey($optionKey, $kategoriId, $yayinTipiId);

        // ✅ Tag-based cache (Redis kullanılıyorsa)
        $cacheTags = ['config_options', "config_option:{$optionKey}"];
        if ($kategoriId) {
            $cacheTags[] = "kategori:{$kategoriId}";
        }
        if ($yayinTipiId) {
            $cacheTags[] = "yayin_tipi:{$yayinTipiId}";
        }

        // Cache'den çek (tag-based veya normal)
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                return Cache::tags($cacheTags)->remember($cacheKey, 3600, function () use ($optionKey, $kategoriId, $yayinTipiId, $default) {
                    return self::fetchFromDatabase($optionKey, $kategoriId, $yayinTipiId, $default);
                });
            }
        } catch (\Exception $e) {
            // Tag-based cache desteklenmiyorsa normal cache kullan
        }

        // Normal cache (tag-based desteklenmiyorsa)
        return Cache::remember($cacheKey, 3600, function () use ($optionKey, $kategoriId, $yayinTipiId, $default) {
            return self::fetchFromDatabase($optionKey, $kategoriId, $yayinTipiId, $default);
        });
    }

    /**
     * Database'den config seçeneğini çek
     */
    private static function fetchFromDatabase($optionKey, $kategoriId, $yayinTipiId, $default)
    {
        // Database'den en spesifik config'i getir
        $configOption = ConfigOption::getMostSpecific($optionKey, $kategoriId, $yayinTipiId);

        if ($configOption && $configOption->option_value) {
            return $configOption->option_value;
        }

        // Fallback: Config dosyasından çek
        if ($default === null) {
            $default = config("yali_options.{$optionKey}", []);
        }

        return $default;
    }

    /**
     * Config seçeneğini kaydet
     *
     * @param string $optionKey
     * @param mixed $optionValue
     * @param string $optionType
     * @param int|null $kategoriId
     * @param int|null $yayinTipiId
     * @param array $metadata
     * @return ConfigOption
     */
    public static function set($optionKey, $optionValue, $optionType, $kategoriId = null, $yayinTipiId = null, $metadata = [])
    {
        $configOption = ConfigOption::updateOrCreate(
            [
                'option_key' => $optionKey,
                'kategori_id' => $kategoriId,
                'yayin_tipi_id' => $yayinTipiId,
            ],
            [
                'option_type' => $optionType,
                'option_value' => $optionValue,
                'label' => $metadata['label'] ?? null,
                'description' => $metadata['description'] ?? null,
                'icon' => $metadata['icon'] ?? null,
                'aktiflik_durumu' => $metadata['aktiflik_durumu'] ?? ($metadata['status'] ?? true),
                'display_order' => $metadata['display_order'] ?? 0,
            ]
        );

        // Cache'i temizle
        self::clearCache($optionKey, $kategoriId, $yayinTipiId);

        return $configOption;
    }

    /**
     * Cache key oluştur
     */
    private static function getCacheKey($optionKey, $kategoriId = null, $yayinTipiId = null)
    {
        return "config_option:{$optionKey}:" . ($kategoriId ?? 'null') . ':' . ($yayinTipiId ?? 'null');
    }

    /**
     * Cache'i temizle
     *
     * ✅ İyileştirme: Tag-based cache temizleme (Redis kullanılıyorsa)
     */
    public static function clearCache($optionKey, $kategoriId = null, $yayinTipiId = null)
    {
        // Tag-based cache temizleme (Redis kullanılıyorsa)
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                $tags = ['config_options', "config_option:{$optionKey}"];
                if ($kategoriId) {
                    $tags[] = "kategori:{$kategoriId}";
                }
                if ($yayinTipiId) {
                    $tags[] = "yayin_tipi:{$yayinTipiId}";
                }

                // Tüm ilgili tag'leri temizle
                Cache::tags($tags)->flush();
                return;
            }
        } catch (\Exception $e) {
            // Tag-based cache desteklenmiyorsa normal cache kullan
        }

        // Normal cache temizleme (fallback)
        Cache::forget(self::getCacheKey($optionKey, $kategoriId, $yayinTipiId));

        // Tüm kombinasyonları temizle (wildcard)
        if ($kategoriId || $yayinTipiId) {
            Cache::forget(self::getCacheKey($optionKey, null, null));
            if ($kategoriId) {
                Cache::forget(self::getCacheKey($optionKey, $kategoriId, null));
            }
            if ($yayinTipiId) {
                Cache::forget(self::getCacheKey($optionKey, null, $yayinTipiId));
            }
        }
    }

    /**
     * Tüm cache'i temizle
     *
     * ✅ İyileştirme: Tag-based cache temizleme (Redis kullanılıyorsa)
     */
    public static function clearAllCache()
    {
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                // Tag-based cache temizleme (sadece config_options tag'i)
                Cache::tags(['config_options'])->flush();
                return;
            }
        } catch (\Exception $e) {
            // Tag-based cache desteklenmiyorsa normal cache kullan
        }

        // Fallback: Tüm cache'i temizle (dikkatli kullan!)
        // Cache::flush(); // Yorum satırına alındı - çok tehlikeli!

        // Alternatif: Sadece config_option ile başlayan cache'leri temizle
        // Bu implementasyon cache driver'a bağlı, şimdilik yorum satırında
    }
}

<?php

namespace App\Services\Slug;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

/**
 * Merkezi Slug Generation Servisi
 *
 * Context7 Standardı: C7-SLUG-GENERATOR-2025-12-06
 *
 * Tüm slug generation işlemlerini merkezi olarak yönetir.
 * Benzersiz slug üretir ve tutarlılık sağlar.
 *
 * @package App\Services\Slug
 */
class SlugGenerator
{
    /**
     * Benzersiz slug üret
     *
     * @param string $base Base string (slug'a çevrilecek)
     * @param string $modelClass Model class name (örn: FeatureCategory::class)
     * @param int|null $excludeId Hariç tutulacak ID (update işlemleri için)
     * @param string $column Slug kolonu adı (varsayılan: 'slug')
     * @return string
     */
    public function generateUnique(
        string $base,
        string $modelClass,
        ?int $excludeId = null,
        string $column = 'slug'
    ): string {
        // Base string'i slug'a çevir
        $slug = Str::slug($base);

        // Boş slug kontrolü
        if (empty($slug)) {
            $slug = 'item';
        }

        $original = $slug;
        $counter = 2;

        // Benzersiz slug bul
        while (
            $modelClass::where($column, $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Slug'ı validate et
     *
     * @param string $slug Slug string
     * @return bool
     */
    public function isValid(string $slug): bool
    {
        // Slug format kontrolü
        if (empty($slug)) {
            return false;
        }

        // Sadece alfanumerik, tire ve alt çizgi karakterleri
        return (bool) preg_match('/^[a-z0-9_-]+$/', $slug);
    }

    /**
     * Slug'ı normalize et
     *
     * @param string $slug Slug string
     * @return string
     */
    public function normalize(string $slug): string
    {
        // Türkçe karakterleri İngilizce'ye çevir
        $slug = Str::slug($slug);

        // Boş slug kontrolü
        if (empty($slug)) {
            return 'item';
        }

        return $slug;
    }
}

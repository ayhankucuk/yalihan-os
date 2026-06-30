<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * HasAIUsageTracking Trait
 *
 * Code Duplication Reduction - 2025-11-11
 * Ortak kullanım takibi metodları için trait
 *
 * Kullanım:
 * - AIKnowledgeBase model'inde kullanılıyor
 * - AIEmbedding model'inde kullanılıyor
 *
 * Duplicate metodlar:
 * - scopeByLanguage()
 * - scopeRecentlyUsed()
 * - scopePopular()
 * - incrementUsage()
 */
trait HasAIUsageTracking
{
    /**
     * Scope: Dil bazlı filtreleme
     */
    public function scopeByLanguage(Builder $query, ?string $language = null): Builder
    {
        // Default language kontrolü - AIKnowledgeBase için 'tr', AIEmbedding için null
        $defaultLanguage = property_exists($this, 'defaultLanguage') ? $this->defaultLanguage : null;
        $language = $language ?? $defaultLanguage;

        if ($language === null) {
            return $query;
        }

        return $query->where('language', $language);
    }

    /**
     * Scope: Son kullanılan kayıtlar
     */
    public function scopeRecentlyUsed(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_used_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope: Popüler kayıtlar (kullanım sayısına göre)
     */
    public function scopePopular(Builder $query, int $minUsage = 10): Builder
    {
        return $query->where('usage_count', '>=', $minUsage);
    }

    /**
     * Kullanım sayısını artır ve son kullanım tarihini güncelle
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => Carbon::now()]);
    }
}

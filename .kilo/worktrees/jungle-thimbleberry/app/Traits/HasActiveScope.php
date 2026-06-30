<?php

namespace App\Traits;

use App\Enums\IlanDurumu;

/**
 * HasActiveScope Trait
 *
 * Context7 Standardı: C7-ACTIVE-SCOPE-TRAIT-2025-11-06
 *
 * 18+ modelde tekrarlanan scopeActive metodunu bir trait'e çıkarır
 * DRY prensibi - Code duplication önlendi
 *
 * ⚠️ IMPORTANT: status field FORBIDDEN by Context7 (removed 2025-11-06)
 */
trait HasActiveScope
{
    /**
     * Scope a query to only include active records.
     *
     * Context7: Model-specific field detection
     * Standardized active state detection across various models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        $table = $this->getTable();
        $schema = $this->getConnection()->getSchemaBuilder();

        // 1. Canonical: yayin_durumu (string state)
        if ($schema->hasColumn($table, 'yayin_durumu')) {
            return $query->where($table . '.yayin_durumu', IlanDurumu::YAYINDA->value);
        }

        // 2. Canonical boolean: aktiflik_durumu
        if ($schema->hasColumn($table, 'aktiflik_durumu')) {
            return $query->where($table . '.aktiflik_durumu', true);
        }

        // 3. Legacy boolean: aktif_mi
        if ($schema->hasColumn($table, 'aktif_mi')) {
            return $query->where($table . '.aktif_mi', true);
        }

        // 4. Featured flag
        if ($schema->hasColumn($table, 'one_cikan')) {
            return $query->where($table . '.one_cikan', true);
        }

        // 5. Legacy is_active
        if ($schema->hasColumn($table, 'is_active')) {
            return $query->where($table . '.is_active', 1);
        }

        // No active field found - return unfiltered
        return $query;
    }

    /**
     * Context7 kanonik alias: scopeAktif → scopeActive
     *
     * SAB kuralı: Türkçe kanonik scope adı zorunlu.
     */
    public function scopeAktif($query)
    {
        return $this->scopeActive($query);
    }
}

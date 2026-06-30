<?php

namespace App\Services\PropertyType;

use App\Models\YayinTipiSablonu;

/**
 * PropertyTypeBulkService — Application Service
 *
 * Toplu yayın tipi işlemleri (reorder, vb.)
 * SAB v4.1 Kural 1/11: Controller'dan mutation logic taşıması
 */
class PropertyTypeBulkService
{
    /**
     * Yayın tiplerini sırala (display_order güncelle).
     *
     * @param  array  $items  [{id, display_order}, ...]
     */
    public function reorderYayinTipleri(array $items): void
    {
        foreach ($items as $item) {
            if (! isset($item['id'])) {
                continue;
            }

            YayinTipiSablonu::where('id', (int) $item['id'])
                ->update(['display_order' => (int) ($item['display_order'] ?? 0)]);
        }
    }
}

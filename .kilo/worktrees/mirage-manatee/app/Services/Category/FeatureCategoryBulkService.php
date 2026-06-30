<?php

namespace App\Services\Category;

use App\Models\FeatureCategory;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\Log;

class FeatureCategoryBulkService
{
    use GuardsAgentWrites;

    /**
     * Create a new feature category.
     */
    public function createCategory(array $data): FeatureCategory
    {
        $this->blockAgentWrite(__FUNCTION__);

        return FeatureCategory::create($data);
    }

    public function reorder(array $items): void
    {
        foreach ($items as $item) {
            if (! isset($item['id'], $item['display_order'])) {
                continue;
            }

            FeatureCategory::where('id', (int) $item['id'])
                ->update(['display_order' => (int) $item['display_order']]);
        }
    }

    /**
     * Update a feature category.
     */
    public function updateCategory(FeatureCategory $kategori, array $data): FeatureCategory
    {
        $this->blockAgentWrite(__FUNCTION__);

        $kategori->update($data);

        return $kategori;
    }

    /**
     * Delete a feature category.
     */
    public function deleteCategory(FeatureCategory $kategori): void
    {
        $this->blockAgentWrite(__FUNCTION__);

        $kategori->delete();
    }

    /**
     * Toggle aktiflik_durumu for a single category.
     */
    public function toggleDurum(FeatureCategory $kategori): FeatureCategory
    {
        $kategori->aktiflik_durumu = ! $kategori->aktiflik_durumu;
        $kategori->save();

        return $kategori;
    }

    /**
     * Quick update (partial fields) for a category.
     */
    public function quickUpdate(FeatureCategory $kategori, array $data): FeatureCategory
    {
        $kategori->update($data);

        return $kategori;
    }

    /**
     * Duplicate a feature category.
     *
     * @return FeatureCategory The duplicated category
     */
    public function duplicate(FeatureCategory $kategori): FeatureCategory
    {
        $yeni = $kategori->replicate();
        $yeni->name = $kategori->name . ' Kopya';
        $yeni->slug = null;
        $yeni->display_order = (int) (FeatureCategory::max('display_order') + 1);
        $yeni->save();

        return $yeni;
    }

    /**
     * Bulk toggle aktiflik_durumu for multiple categories.
     */
    public function bulkToggleDurum(array $ids, bool $aktiflikDurumu): int
    {
        $this->blockAgentWrite(__FUNCTION__);

        return FeatureCategory::whereIn('id', $ids)
            ->update(['aktiflik_durumu' => $aktiflikDurumu]);
    }

    /**
     * Bulk delete categories.
     */
    public function bulkDelete(array $ids): int
    {
        $this->blockAgentWrite(__FUNCTION__);

        return FeatureCategory::whereIn('id', $ids)->delete();
    }
}

<?php

namespace App\Actions\Ilan\Feature;

use App\Models\FeatureCategory;
use App\Helpers\FeatureCacheHelper;

class BulkFeatureCategoryAction
{
    public function handle(array $ids, string $action, ?bool $aktiflikValue = null): void
    {
        $query = FeatureCategory::whereIn('id', $ids);

        switch ($action) {
            case 'delete':
                $query->delete();
                break;
            case 'toggle-aktiflik':
                if (!is_null($aktiflikValue)) {
                    $query->update(['aktiflik_durumu' => $aktiflikValue]);
                }
                break;
        }

        FeatureCacheHelper::clearCategoryCache();
    }
}

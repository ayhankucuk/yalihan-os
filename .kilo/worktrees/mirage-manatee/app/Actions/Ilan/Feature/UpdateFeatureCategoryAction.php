<?php

namespace App\Actions\Ilan\Feature;

use App\Models\FeatureCategory;
use App\Helpers\FeatureCacheHelper;

class UpdateFeatureCategoryAction
{
    public function handle(FeatureCategory $category, array $data): FeatureCategory
    {
        $category->update($data);

        FeatureCacheHelper::clearCategoryCache();

        return $category->fresh();
    }
}

<?php

namespace App\Actions\Ilan\Feature;

use App\Models\FeatureCategory;
use App\Helpers\FeatureCacheHelper;

class StoreFeatureCategoryAction
{
    public function handle(array $data): FeatureCategory
    {
        $category = FeatureCategory::create($data);

        FeatureCacheHelper::clearCategoryCache();

        return $category;
    }
}

<?php

namespace App\Actions\Ilan\Feature;

use App\Models\Feature;
use App\Helpers\FeatureCacheHelper;

class BulkFeatureAction
{
    public function handle(array $ids, string $action): void
    {
        $query = Feature::whereIn('id', $ids);

        switch ($action) {
            case 'activate':
                $query->update(['aktiflik_durumu' => true]);
                break;
            case 'deactivate':
                $query->update(['aktiflik_durumu' => false]);
                break;
            case 'delete':
                $query->delete();
                break;
        }

        FeatureCacheHelper::clearCategoryCache();
    }
}

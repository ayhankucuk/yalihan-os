<?php

namespace App\Actions\PropertyHub;

use App\Models\Feature;
use App\Models\TemplateChangeLog;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use Exception;

class DeleteFeatureAction
{
    public function __construct(
        private readonly PropertyHubOrchestrator $hub
    ) {}

    public function handle(Feature $feature, int $userId): void
    {
        $featureName = $feature->name;
        $featureId = $feature->id;

        // Check if feature has assignments
        $assignmentCount = $feature->assignments()->count();

        if ($assignmentCount > 0) {
            throw new Exception("Bu özellik {$assignmentCount} yerde kullanılıyor. Önce atamaları kaldırın.");
        }

        $feature->delete();

        // Invalidate cache
        $this->hub->cacheService->invalidate('features');

        // Log change
        TemplateChangeLog::create([
            'aksiyon_tipi' => 'delete',
            'entity_type' => Feature::class,
            'entity_id' => $featureId,
            'aciklama' => "Feature silindi: {$featureName}",
            'user_id' => $userId,
        ]);
    }
}

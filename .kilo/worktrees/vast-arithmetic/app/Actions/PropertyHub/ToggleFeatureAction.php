<?php

namespace App\Actions\PropertyHub;

use App\Models\Feature;
use App\Models\TemplateChangeLog;
use App\Services\PropertyHub\PropertyHubOrchestrator;

class ToggleFeatureAction
{
    public function __construct(
        private readonly PropertyHubOrchestrator $hub
    ) {}

    public function handle(Feature $feature, int $userId): Feature
    {
        $feature->aktiflik_durumu = !$feature->aktiflik_durumu;
        $feature->save();

        // Invalidate cache
        $this->hub->cacheService->invalidate('features');

        // Log change
        TemplateChangeLog::create([
            'aksiyon_tipi' => 'update',
            'entity_type' => Feature::class,
            'entity_id' => $feature->id,
            'aciklama' => $feature->aktiflik_durumu
                ? "Feature aktifleştirildi: {$feature->name}"
                : "Feature pasifleştirildi: {$feature->name}",
            'user_id' => $userId,
        ]);

        return $feature;
    }
}

<?php

namespace App\Actions\PropertyHub;

use App\Models\Feature;
use App\Models\TemplateChangeLog;
use App\Services\PropertyHub\PropertyHubOrchestrator;

class ArchiveFeatureAction
{
    public function __construct(
        private readonly PropertyHubOrchestrator $hub
    ) {}

    public function handle(Feature $feature, int $userId): void
    {
        $feature->update([
            'aktiflik_durumu' => false,
            'lifecycle_durumu' => 'archived',
        ]);

        // Invalidate cache
        $this->hub->cacheService->invalidate('features');

        // Log change
        TemplateChangeLog::create([
            'aksiyon_tipi' => 'archive',
            'entity_type' => Feature::class,
            'entity_id' => $feature->id,
            'aciklama' => "Feature arşivlendi: {$feature->name}",
            'user_id' => $userId,
        ]);
    }
}

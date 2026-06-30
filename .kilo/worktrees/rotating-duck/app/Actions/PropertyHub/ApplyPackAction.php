<?php

namespace App\Actions\PropertyHub;

use App\Models\FeaturePack;
use App\Models\TemplateChangeLog;
use App\Services\PropertyHub\PropertyHubOrchestrator;

class ApplyPackAction
{
    public function __construct(
        private readonly PropertyHubOrchestrator $hub
    ) {}

    public function handle(FeaturePack $pack, array $yayinTipiIds, int $userId): array
    {
        $totalAdded = 0;
        $totalSkipped = 0;

        foreach ($yayinTipiIds as $yayinTipiId) {
            $result = $this->hub->aggregateRoot->applyFeaturePack(
                pivotId: $yayinTipiId,
                packId: $pack->id,
                mode: 'merge',
                userId: $userId
            );

            $totalAdded += $result['added_count'];
            $totalSkipped += $result['skipped_count'];
        }

        // Invalidate cache
        $this->hub->cacheService->invalidate('assignments');

        // Log change
        TemplateChangeLog::create([
            'aksiyon_tipi' => 'apply_pack',
            'entity_type' => FeaturePack::class,
            'entity_id' => $pack->id,
            'aciklama' => "Pack uygulandı: {$pack->name} → " . count($yayinTipiIds) . " template",
            'user_id' => $userId,
            'yeni_degerler' => ['added' => $totalAdded, 'skipped' => $totalSkipped],
        ]);

        return [
            'added' => $totalAdded,
            'skipped' => $totalSkipped,
        ];
    }
}

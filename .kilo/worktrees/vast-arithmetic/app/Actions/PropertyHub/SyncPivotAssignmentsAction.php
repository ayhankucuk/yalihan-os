<?php

namespace App\Actions\PropertyHub;

use App\Models\AltKategoriYayinTipi;
use App\Services\PropertyHub\PropertyHubOrchestrator;
use Illuminate\Support\Facades\Log;

class SyncPivotAssignmentsAction
{
    public function __construct(
        private readonly PropertyHubOrchestrator $hub
    ) {}

    public function handle(int $yayinTipiId, int $altKategoriId, array $featureIds, int $userId): array
    {
        // Find or Create Pivot
        $pivot = AltKategoriYayinTipi::firstOrCreate(
            [
                'yayin_tipi_id' => $yayinTipiId,
                'alt_kategori_id' => $altKategoriId,
            ],
            [
                'aktiflik_durumu' => true,
                'display_order' => 0
            ]
        );

        // Aggregate Root'a delege et
        $result = $this->hub->aggregateRoot->syncFeatures(
            pivotId: $pivot->id,
            featureIds: $featureIds,
            userId: $userId
        );

        $this->hub->cacheService->invalidate('assignments');

        Log::info("Pivot assignments updated for Category {$altKategoriId} - Template {$yayinTipiId}", [
            'user_id' => $userId,
            'added' => $result['added'],
            'removed' => $result['removed']
        ]);

        return $result;
    }
}

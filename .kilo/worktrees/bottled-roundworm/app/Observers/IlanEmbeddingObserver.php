<?php

namespace App\Observers;

use App\Models\Ilan;
use App\Services\AI\SemanticSearchService;
use App\Services\Logging\LogService;

/**
 * Ilan Embedding Observer
 *
 * Context7: Otomatik semantik senkronizasyon observer'ı.
 * Date: 2026-01-19
 */
class IlanEmbeddingObserver
{
    protected SemanticSearchService $semanticService;

    public function __construct(SemanticSearchService $semanticService)
    {
        $this->semanticService = $semanticService;
    }

    /**
     * Handle the Ilan "saved" event.
     */
    public function saved(Ilan $ilan): void
    {
        // Skip sync if title and description are missing
        if (!$ilan->baslik && !$ilan->aciklama) {
            return;
        }

        // Ideally this should be queued in production
        try {
            $this->semanticService->syncIlan($ilan);
        } catch (\Exception $e) {
            LogService::error('Observer semantic sync failed', ['id' => $ilan->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Handle the Ilan "deleted" event.
     */
    public function deleted(Ilan $ilan): void
    {
        $ilan->embedding()->delete();
    }
}

<?php

namespace App\Services\AI;

use App\Models\AiExperiment;
use Illuminate\Support\Facades\Cache;

class AiExperimentService
{
    /**
     * Get active variation for a specific kategori and user context
     * 
     * @param string $kategoriSlug
     * @param string $userId (optional for deterministic splitting)
     * @return array|null [experiment_id, varyasyon_anahtari, config]
     */
    public function getActiveVariation(string $kategoriSlug, ?string $userId = null): ?array
    {
        $activeExperiments = Cache::remember('ai_active_experiments', 300, function () {
            return AiExperiment::active()->get();
        });

        if ($activeExperiments->isEmpty()) {
            return null;
        }

        // Find experiment for this category or global
        $deney = $activeExperiments->first(function ($exp) use ($kategoriSlug) {
            return $exp->hedef_kategori === $kategoriSlug || $exp->hedef_kategori === 'all';
        });

        if (!$deney) {
            return null;
        }

        $varyasyonlar = $deney->varyasyonlar;
        if (empty($varyasyonlar)) {
            return null;
        }

        // Trivial traffic splitting (50/50 or proportional)
        // Using session or random for now. For production-ready, use hash(userId + experimentId)
        $keys = array_keys($varyasyonlar);
        $selectedIndex = (crc32($userId ?? session()->getId()) % count($keys));
        $selectedKey = $keys[$selectedIndex];

        return [
            'deney_id' => $deney->id,
            'varyasyon_anahtari' => $selectedKey,
            'config' => $varyasyonlar[$selectedKey]
        ];
    }

    /**
     * Seal an experiment by promoting a winner
     */
    public function sealWinner(int $id, string $variationKey): void
    {
        $deney = AiExperiment::findOrFail($id);
        $deney->update([
            'kazanan_varyasyon_anahtari' => $variationKey,
            'aktiflik_durumu' => 0,
            'bitis_tarihi' => now()
        ]);
        
        Cache::forget('ai_active_experiments');
    }
}

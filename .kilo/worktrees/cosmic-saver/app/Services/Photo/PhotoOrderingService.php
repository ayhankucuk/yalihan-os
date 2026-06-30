<?php

namespace App\Services\Photo;

use App\Models\AdvisorPhoto;
use App\Models\Kisi;

/**
 * Photo Ordering Service
 * MVP: Simple ordering by quality score
 */
class PhotoOrderingService
{
    /**
     * Calculate optimal photo ordering
     * Returns: array of photo IDs in recommended order
     */
    public function determineOptimalOrder(Kisi $advisor): array
    {
        $photos = $advisor->photos()->get();

        if ($photos->isEmpty()) {
            return [];
        }

        // MVP: Simple ordering by quality score (descending)
        $sorted = $photos
            ->sortByDesc('quality_score')
            ->values();

        return $sorted->pluck('id')->toArray();
    }

    /**
     * Apply optimal ordering to photos
     * Sets display_order and featured flag
     */
    public function applyOptimalOrder(Kisi $advisor): void
    {
        $optimalOrder = $this->determineOptimalOrder($advisor);

        // Update display_order for all photos
        foreach ($optimalOrder as $index => $photoId) {
            AdvisorPhoto::where('id', $photoId)
                ->where('kisi_id', $advisor->id)
                ->update(['display_order' => $index + 1]);
        }

        // Reset featured flag
        AdvisorPhoto::where('kisi_id', $advisor->id)
            ->update(['featured' => false, 'featured_at' => null]);

        // Set featured photo (highest quality = first in order)
        if (!empty($optimalOrder)) {
            AdvisorPhoto::find($optimalOrder[0])
                ->update([
                    'featured' => true,
                    'featured_at' => now(),
                ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Adapters\Fake\PropertyPipeline;

use App\Contracts\PropertyPipeline\PhotoPipelineInterface;

/**
 * FakePhotoPipeline — P01 Sprint 4.1
 *
 * No filesystem dependency. Returns deterministic fake photo metadata.
 */
class FakePhotoPipeline implements PhotoPipelineInterface
{
    public function process(int $ilanId, array $photoUrls = []): array
    {
        $processed = [];

        foreach ($photoUrls as $index => $url) {
            $processed[] = [
                'photo_id' => 1000 + $ilanId * 10 + $index,
                'url' => $url,
                'thumbnail_url' => $url . '?thumb=1',
                'siralama' => $index + 1,
            ];
        }

        // Always return at least one placeholder if no photos provided
        if (empty($processed)) {
            $processed[] = [
                'photo_id' => 999,
                'url' => "https://placeholder.fake/ilan/{$ilanId}/default.jpg",
                'thumbnail_url' => "https://placeholder.fake/ilan/{$ilanId}/default_thumb.jpg",
                'siralama' => 1,
            ];
        }

        return $processed;
    }
}

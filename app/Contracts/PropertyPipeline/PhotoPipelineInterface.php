<?php

declare(strict_types=1);

namespace App\Contracts\PropertyPipeline;

/**
 * PhotoPipeline Interface — P01 Property Pipeline (Sprint 4.1)
 *
 * Port: Media / Fake implementation.
 * Photo processing port.
 */
interface PhotoPipelineInterface
{
    /**
     * Process photos for a property.
     *
     * @param int $ilanId
     * @param array $photoUrls External URLs or local paths
     * @return array<int, array{photo_id: int, url: string, thumbnail_url: string, siralama: int>
     */
    public function process(int $ilanId, array $photoUrls): array;
}

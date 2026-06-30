<?php

namespace App\Domain\PropertyHub\Listeners;

use App\Domain\PropertyHub\Events\FeatureAssignedEvent;
use App\Services\Ups\UpsCacheService;

/**
 * FeatureAssignedListener
 *
 * [SAB ENFORCEMENT]: Event-Driven Cache Invalidation
 * Feature atandiktan sonra cache invalidate edilir.
 */
class FeatureAssignedListener
{
    public function __construct(
        private UpsCacheService $cacheService,
        private \App\Services\PropertyType\FeatureAssignmentService $featureAssignmentService
    ) {}

    public function handle(FeatureAssignedEvent $event): void
    {
        // Cache invalidation (UPS Cache)
        $this->cacheService->invalidate('assignments');
        $this->cacheService->invalidate('features');

        // FeatureAssignmentService Cache Versioning
        $this->featureAssignmentService->invalidateCache();
    }
}

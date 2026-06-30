<?php

namespace App\Listeners;

use App\Events\IlanCreated;
use App\Events\IlanUpdated;
use App\Events\IlanDeleted;
use App\Events\IlanKopyalandi;
use App\Events\IlanPasifeAlindi;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

/**
 * Invalidate Ilan Cache Listener
 *
 * Automatically invalidates ilan caches when ilan events occur.
 * Decouples cache management from business logic.
 *
 * Phase 12: Extended to handle IlanKopyalandi and IlanPasifeAlindi.
 */
class InvalidateIlanCache
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private CacheManager $cache
    ) {}

    /**
     * Handle incoming events
     */
    public function handle(object $event): void
    {
        if ($event instanceof IlanCreated) {
            $this->handleCreated($event);
        } elseif ($event instanceof IlanUpdated) {
            $this->handleUpdated($event);
        } elseif ($event instanceof IlanDeleted) {
            $this->handleDeleted($event);
        } elseif ($event instanceof IlanKopyalandi) {
            $this->handleKopyalandi($event);
        } elseif ($event instanceof IlanPasifeAlindi) {
            $this->handlePasifeAlindi($event);
        }
    }

    /**
     * Handle IlanCreated event
     */
    public function handleCreated(IlanCreated $event): void
    {
        Log::debug("Cache invalidation triggered by IlanCreated", [
            'ilan_id' => $event->ilan->id
        ]);

        // Invalidate list caches (new ilan added)
        $this->cache->flushTag('ilan');
    }

    /**
     * Handle IlanUpdated event
     */
    public function handleUpdated(IlanUpdated $event): void
    {
        Log::debug("Cache invalidation triggered by IlanUpdated", [
            'ilan_id' => $event->ilan->id
        ]);

        // Invalidate specific ilan
        $this->cache->forget((string)$event->ilan->id, 'ilan');

        // Invalidate list caches
        $this->cache->flushTag('ilan');
    }

    /**
     * Handle IlanDeleted event
     */
    public function handleDeleted(IlanDeleted $event): void
    {
        Log::debug("Cache invalidation triggered by IlanDeleted", [
            'ilan_id' => $event->ilanId
        ]);

        // Invalidate specific ilan
        $this->cache->forget((string)$event->ilanId, 'ilan');

        // Invalidate list caches
        $this->cache->flushTag('ilan');
    }

    /**
     * Handle IlanKopyalandi event (Phase 12)
     */
    public function handleKopyalandi(IlanKopyalandi $event): void
    {
        Log::debug("Cache invalidation: IlanKopyalandi", [
            'kaynak_id' => $event->kaynakIlan->id,
            'yeni_id' => $event->yeniIlan->id,
        ]);

        $this->cache->flushTag('ilan');
    }

    /**
     * Handle IlanPasifeAlindi event (Phase 12)
     */
    public function handlePasifeAlindi(IlanPasifeAlindi $event): void
    {
        Log::debug("Cache invalidation: IlanPasifeAlindi", [
            'ilan_id' => $event->ilan->id,
            'onceki_durum' => $event->oncekiDurum,
        ]);

        $this->cache->forget((string)$event->ilan->id, 'ilan');
        $this->cache->flushTag('ilan');
    }
}

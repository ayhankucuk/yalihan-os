<?php

namespace App\Listeners\CRM;

use Illuminate\Support\Facades\Log;

/**
 * KisiSyncListener — CRM Kisi Event Subscriber
 *
 * Listens to Kisi lifecycle events to synchronize
 * CRM data across the system.
 *
 * Phase 12: Event-Driven Foundation subscriber.
 */
class KisiSyncListener
{
    /**
     * Handle Kisi created events.
     */
    public function handleKisiCreated(object $event): void
    {
        Log::channel('sab')->info('KisiSyncListener: Kisi created', [
            'kisi_id' => $event->kisi->id ?? null,
        ]);
    }

    /**
     * Handle Kisi updated events.
     */
    public function handleKisiUpdated(object $event): void
    {
        Log::channel('sab')->info('KisiSyncListener: Kisi updated', [
            'kisi_id' => $event->kisi->id ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): void
    {
        if (class_exists(\App\Events\CRM\KisiCreated::class)) {
            $events->listen(
                \App\Events\CRM\KisiCreated::class,
                [self::class, 'handleKisiCreated']
            );
        }

        if (class_exists(\App\Events\CRM\KisiUpdated::class)) {
            $events->listen(
                \App\Events\CRM\KisiUpdated::class,
                [self::class, 'handleKisiUpdated']
            );
        }
    }
}

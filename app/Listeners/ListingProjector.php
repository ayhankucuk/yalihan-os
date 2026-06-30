<?php

namespace App\Listeners;

use App\Events\ListingCreated;
use App\Events\ListingUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListingProjector implements ShouldQueue
{
    use InteractsWithQueue;

    /** @inheritDoc */
    public int $tries = 3;

    /** @inheritDoc */
    public array $backoff = [10, 30, 60];

    public string $queue = 'projections';

    /**
     * Handle the event.
     */
    public function handleListingCreated(ListingCreated $event): void
    {
        if ($this->hasBeenProcessed($event->eventId)) {
            return;
        }

        try {
            DB::transaction(function () use ($event) {
                DB::table('proj_listings')->updateOrInsert(
                    ['ilan_id' => $event->listingId],
                    [
                        'baslik' => $event->title,
                        'yayin_durumu' => $event->yayinDurumu,
                        'fiyat' => $event->price,
                        'para_birimi' => $event->currencyId,
                        'danisman_id' => $event->ownerId,
                        'kategori_id' => $event->categoryId,
                        'il_id' => $event->cityId,
                        'created_at' => $event->occurredAt,
                        'updated_at' => $event->occurredAt,
                    ]
                );

                $this->markAsProcessed($event->eventId);
            });
        } catch (Throwable $e) {
            Log::critical('ListingProjector handleListingCreated failed', [
                'event_id' => $event->eventId,
                'ilan_id' => $event->listingId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handleListingUpdated(ListingUpdated $event): void
    {
        if ($this->hasBeenProcessed($event->eventId)) {
            return;
        }

        try {
            DB::transaction(function () use ($event) {
            DB::table('proj_listings')->updateOrInsert(
                ['ilan_id' => $event->listingId],
                [
                    'baslik' => $event->title,
                    'yayin_durumu' => $event->yayinDurumu,
                    'fiyat' => $event->price,
                    'para_birimi' => $event->currencyId,
                    'danisman_id' => $event->ownerId,
                    'kategori_id' => $event->categoryId,
                    'il_id' => $event->cityId,
                    'updated_at' => $event->occurredAt,
                ]
            );

            if ($event->priceChanged) {
                DB::table('proj_activity_stream')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'occurred_at' => $event->occurredAt,
                    'actor_id' => $event->ownerId,
                    'listing_id' => $event->listingId,
                    'type' => 'price_changed', // context7-ignore
                    'payload' => json_encode(['new_price' => $event->price]),
                ]);
            }

            if ($event->yayinDurumuChanged) {
                DB::table('proj_activity_stream')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'occurred_at' => $event->occurredAt,
                    'actor_id' => $event->ownerId,
                    'listing_id' => $event->listingId,
                    'type' => 'yayin_durumu_changed', // context7-ignore
                    'payload' => json_encode(['yeni_durum' => $event->yayinDurumu]),
                ]);
            }

            $this->markAsProcessed($event->eventId);
            });
        } catch (Throwable $e) {
            Log::critical('ListingProjector handleListingUpdated failed', [
                'event_id' => $event->eventId,
                'ilan_id' => $event->listingId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function hasBeenProcessed(string $eventId): bool
    {
        return DB::table('proj_event_offsets')
            ->where('projector_name', static::class)
            ->where('event_id', $eventId)
            ->exists();
    }

    private function markAsProcessed(string $eventId): void
    {
        DB::table('proj_event_offsets')->insert([
            'projector_name' => static::class,
            'event_id' => $eventId,
            'processed_at' => now(),
        ]);
    }
}

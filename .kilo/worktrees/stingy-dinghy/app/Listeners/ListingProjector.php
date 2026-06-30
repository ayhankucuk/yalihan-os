<?php

namespace App\Listeners;

use App\Events\ListingCreated;
use App\Events\ListingUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListingProjector implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'projections';

    /**
     * Handle the event.
     */
    public function handleListingCreated(ListingCreated $event): void
    {
        if ($this->hasBeenProcessed($event->eventId)) {
            return;
        }

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
    }

    public function handleListingUpdated(ListingUpdated $event): void
    {
        if ($this->hasBeenProcessed($event->eventId)) {
            return;
        }

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
                    'type' => 'price_changed',
                    'payload' => json_encode(['new_price' => $event->price]),
                ]);
            }

            if ($event->yayinDurumuChanged) {
                DB::table('proj_activity_stream')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'occurred_at' => $event->occurredAt,
                    'actor_id' => $event->ownerId,
                    'listing_id' => $event->listingId,
                    'type' => 'yayin_durumu_changed',
                    'payload' => json_encode(['yeni_durum' => $event->yayinDurumu]),
                ]);
            }

            $this->markAsProcessed($event->eventId);
        });
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

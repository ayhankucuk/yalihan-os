<?php

namespace App\Listeners;

use App\Events\LeadRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeadProjector implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'projections';

    /**
     * Handle the event.
     */
    public function handle(LeadRegistered $event): void
    {
        if ($this->hasBeenProcessed($event->eventId)) {
            return;
        }

        DB::transaction(function () use ($event) {
            $date = Carbon::parse($event->occurredAt)->toDateString();

            $column = match ($event->type) {
                'lead' => 'lead_count',
                'message' => 'message_count',
                'action' => 'action_count',
                default => null,
            };

            if ($column) {
                $exists = DB::table('proj_leads_daily')
                    ->where('date', $date)
                    ->where('owner_id', $event->ownerId)
                    ->where('listing_id', $event->listingId)
                    ->exists();

                if (!$exists) {
                    DB::table('proj_leads_daily')->insert([
                        'date' => $date,
                        'owner_id' => $event->ownerId,
                        'listing_id' => $event->listingId,
                        'lead_count' => 0,
                        'message_count' => 0,
                        'action_count' => 0,
                    ]);
                }

                DB::table('proj_leads_daily')
                    ->where('date', $date)
                    ->where('owner_id', $event->ownerId)
                    ->where('listing_id', $event->listingId)
                    ->increment($column);
            }

            // Add an activity stream log
            DB::table('proj_activity_stream')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'occurred_at' => $event->occurredAt,
                'actor_id' => null, // Lead is external
                'listing_id' => $event->listingId,
                'type' => 'lead_received',
                'payload' => json_encode(['lead_type' => $event->type]),
            ]);

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

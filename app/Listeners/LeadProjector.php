<?php

namespace App\Listeners;

use App\Events\LeadRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class LeadProjector implements ShouldQueue
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
    public function handle(LeadRegistered $event): void
    {
        if ($this->hasBeenProcessed($event->eventId)) {
            return;
        }

        try {
            DB::transaction(function () use ($event) {
            $date = Carbon::parse($event->occurredAt)->toDateString();

            $column = match ($event->type) { // context7-ignore
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
                'type' => 'lead_received', // context7-ignore
                'payload' => json_encode(['lead_type' => $event->type]), // context7-ignore
            ]);

            $this->markAsProcessed($event->eventId);
        });
        } catch (Throwable $e) {
            Log::critical('LeadProjector handle failed', [
                'event_id' => $event->eventId,
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

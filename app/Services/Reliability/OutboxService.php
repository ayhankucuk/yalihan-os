<?php

namespace App\Services\Reliability;

use App\Models\OutboxEntry;
use Illuminate\Support\Facades\Log;

class OutboxService
{
    /**
     * Publish an event to the Transactional Outbox.
     *
     * @param string $eventKey
     * @param array $payload
     * @param string|null $idempotencyKey
     * @return OutboxEntry
     */
    public function publish(string $eventKey, array $payload, ?string $idempotencyKey = null): OutboxEntry
    {
        if ($idempotencyKey) {
            $existing = OutboxEntry::where('idempotency_key', $idempotencyKey)->orderBy('id')->first();
            if ($existing) {
                return $existing;
            }
        }

        $entry = OutboxEntry::create([
            'event_key' => $eventKey,
            'payload' => $payload,
            'yayin_durumu' => 'PENDING',
            'attempts' => 0,
            'idempotency_key' => $idempotencyKey
        ]);

        Log::info("📨 Outbox event recorded", [
            'id' => $entry->id,
            'event_key' => $eventKey,
            'idempotency_key' => $idempotencyKey
        ]);

        return $entry;
    }
}

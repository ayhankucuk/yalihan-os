<?php

namespace App\Listeners\Property;

use App\Domain\Property\Events\CommercialOfferingCreated;
use App\Domain\Property\Events\CommercialOfferingActivated;
use App\Domain\Property\Events\CommercialOfferingPriceChanged;
use App\Models\Hermes\HermesEventLog;
use Illuminate\Support\Str;

class RecordCommercialOfferingOnTimeline
{
    /**
     * Handles CommercialOfferingCreated event. Replay-safe via DB constraint & payload lookup.
     */
    public function handleCreated(CommercialOfferingCreated $event): void
    {
        $offering = $event->offering;
        $projectionType = 'CommercialOfferingCreated';
        $sourceEventId = 'offering-' . $offering->id . '-created';

        $exists = HermesEventLog::where('tenant_id', $offering->tenant_id)
            ->where(function ($q) use ($projectionType, $sourceEventId, $offering) {
                $q->where(function ($sub) use ($projectionType, $sourceEventId) {
                    $sub->where('projection_type', $projectionType)
                        ->where('source_event_id', $sourceEventId);
                })->orWhere('payload->offering_id', $offering->id);
            })
            ->exists();

        if ($exists) {
            return;
        }

        try {
            HermesEventLog::create([
                'tenant_id' => $offering->tenant_id,
                'event_class' => get_class($event),
                'event_name' => 'Commercial Offering Created',
                'projection_type' => $projectionType,
                'source_event_id' => $sourceEventId,
                'occurred_at' => now(),
                'payload' => [
                    'workspace_id' => $offering->workspace_id,
                    'offering_id' => $offering->id,
                    'property_id' => $offering->property_id,
                    'offering_type' => $offering->offering_type,
                    'fiyat' => $offering->fiyat,
                    'para_birimi' => $offering->para_birimi,
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Suppress duplicate constraint exception for concurrent executions
        }
    }

    /**
     * Handles CommercialOfferingActivated event. Replay-safe.
     */
    public function handleActivated(CommercialOfferingActivated $event): void
    {
        $offering = $event->offering;
        $projectionType = 'CommercialOfferingActivated';
        $sourceEventId = 'offering-' . $offering->id . '-activated';

        $exists = HermesEventLog::where('tenant_id', $offering->tenant_id)
            ->where(function ($q) use ($projectionType, $sourceEventId, $offering) {
                $q->where(function ($sub) use ($projectionType, $sourceEventId) {
                    $sub->where('projection_type', $projectionType)
                        ->where('source_event_id', $sourceEventId);
                })->orWhere('payload->offering_id', $offering->id);
            })
            ->exists();

        if ($exists) {
            return;
        }

        try {
            HermesEventLog::create([
                'tenant_id' => $offering->tenant_id,
                'event_class' => get_class($event),
                'event_name' => 'Commercial Offering Activated',
                'projection_type' => $projectionType,
                'source_event_id' => $sourceEventId,
                'occurred_at' => now(),
                'payload' => [
                    'workspace_id' => $offering->workspace_id,
                    'offering_id' => $offering->id,
                    'property_id' => $offering->property_id,
                    'yayin_durumu' => $offering->yayin_durumu,
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Suppress duplicate constraint exception for concurrent executions
        }
    }

    /**
     * Handles CommercialOfferingPriceChanged event. Replay-safe.
     */
    public function handlePriceChanged(CommercialOfferingPriceChanged $event): void
    {
        $offering = $event->offering;
        $projectionType = 'CommercialOfferingPriceChanged';
        $sourceEventId = 'offering-' . $offering->id . '-price-' . $event->oldPrice->getAmount() . '-' . $event->newPrice->getAmount();

        $exists = HermesEventLog::where('tenant_id', $offering->tenant_id)
            ->where('projection_type', $projectionType)
            ->where('source_event_id', $sourceEventId)
            ->exists();

        if ($exists) {
            return;
        }

        try {
            HermesEventLog::create([
                'tenant_id' => $offering->tenant_id,
                'event_class' => get_class($event),
                'event_name' => 'Commercial Offering Price Changed',
                'projection_type' => $projectionType,
                'source_event_id' => $sourceEventId,
                'occurred_at' => now(),
                'payload' => [
                    'workspace_id' => $offering->workspace_id,
                    'offering_id' => $offering->id,
                    'old_price' => $event->oldPrice->getAmount(),
                    'new_price' => $event->newPrice->getAmount(),
                    'para_birimi' => $event->newPrice->getCurrency(),
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Suppress duplicate constraint exception for concurrent executions
        }
    }
}

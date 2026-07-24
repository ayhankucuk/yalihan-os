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
     * Handles CommercialOfferingCreated event. Replay-safe via payload offering_id lookup.
     */
    public function handleCreated(CommercialOfferingCreated $event): void
    {
        $offering = $event->offering;

        $exists = HermesEventLog::where('tenant_id', $offering->tenant_id)
            ->where('event_class', get_class($event))
            ->where('event_name', 'Commercial Offering Created')
            ->where('payload->offering_id', $offering->id)
            ->exists();

        if ($exists) {
            return;
        }

        HermesEventLog::create([
            'tenant_id' => $offering->tenant_id,
            'event_class' => get_class($event),
            'event_name' => 'Commercial Offering Created',
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
    }

    /**
     * Handles CommercialOfferingActivated event. Replay-safe.
     */
    public function handleActivated(CommercialOfferingActivated $event): void
    {
        $offering = $event->offering;

        $exists = HermesEventLog::where('tenant_id', $offering->tenant_id)
            ->where('event_class', get_class($event))
            ->where('event_name', 'Commercial Offering Activated')
            ->where('payload->offering_id', $offering->id)
            ->exists();

        if ($exists) {
            return;
        }

        HermesEventLog::create([
            'tenant_id' => $offering->tenant_id,
            'event_class' => get_class($event),
            'event_name' => 'Commercial Offering Activated',
            'occurred_at' => now(),
            'payload' => [
                'workspace_id' => $offering->workspace_id,
                'offering_id' => $offering->id,
                'property_id' => $offering->property_id,
                'yayin_durumu' => $offering->yayin_durumu,
            ],
        ]);
    }

    /**
     * Handles CommercialOfferingPriceChanged event. Replay-safe.
     */
    public function handlePriceChanged(CommercialOfferingPriceChanged $event): void
    {
        $offering = $event->offering;

        HermesEventLog::create([
            'tenant_id' => $offering->tenant_id,
            'event_class' => get_class($event),
            'event_name' => 'Commercial Offering Price Changed',
            'occurred_at' => now(),
            'payload' => [
                'workspace_id' => $offering->workspace_id,
                'offering_id' => $offering->id,
                'old_price' => $event->oldPrice->getAmount(),
                'new_price' => $event->newPrice->getAmount(),
                'para_birimi' => $event->newPrice->getCurrency(),
            ],
        ]);
    }
}

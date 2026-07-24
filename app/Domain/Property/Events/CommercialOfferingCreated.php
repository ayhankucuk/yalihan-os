<?php

namespace App\Domain\Property\Events;

use App\Models\CommercialOffering;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CommercialOfferingCreated
{
    use Dispatchable;
    use SerializesModels;

    public string $eventId;

    public function __construct(
        public CommercialOffering $offering,
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?? (string) Str::uuid();
    }
}

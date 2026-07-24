<?php

namespace App\Domain\Property\Events;

use App\Models\CommercialOffering;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CommercialOfferingPriceChanged
{
    use Dispatchable;
    use SerializesModels;

    public string $eventId;

    public function __construct(
        public CommercialOffering $offering,
        public Money $oldPrice,
        public Money $newPrice,
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?? (string) Str::uuid();
    }
}

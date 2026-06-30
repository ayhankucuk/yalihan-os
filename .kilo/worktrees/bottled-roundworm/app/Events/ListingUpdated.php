<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ramsey\Uuid\Uuid;

class ListingUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $eventId;
    public int $listingId;
    public ?string $title;
    public int $yayinDurumu;
    public float $price;
    public ?int $currencyId;
    public ?int $ownerId;
    public ?int $categoryId;
    public ?int $cityId;
    public bool $yayinDurumuChanged;
    public bool $priceChanged;
    public bool $isStale;
    public string $occurredAt;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $listingId,
        ?string $title,
        int $yayinDurumu,
        float $price,
        ?int $currencyId,
        ?int $ownerId,
        ?int $categoryId,
        ?int $cityId,
        bool $yayinDurumuChanged,
        bool $priceChanged,
        bool $isStale
    ) {
        $this->eventId = Uuid::uuid4()->toString();
        $this->listingId = $listingId;
        $this->title = $title;
        $this->yayinDurumu = $yayinDurumu;
        $this->price = $price;
        $this->currencyId = $currencyId;
        $this->ownerId = $ownerId;
        $this->categoryId = $categoryId;
        $this->cityId = $cityId;
        $this->yayinDurumuChanged = $yayinDurumuChanged;
        $this->priceChanged = $priceChanged;
        $this->isStale = $isStale;
        $this->occurredAt = now()->toDateTimeString();
    }
}

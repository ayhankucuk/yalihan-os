<?php

declare(strict_types=1);

namespace App\Events\Hermes;

use App\Contracts\Hermes\HermesEventContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DriveWebhookEvent
 *
 * Implements HermesEventContract for Drive webhook file changes.
 */
class DriveWebhookEvent implements HermesEventContract
{
    use Dispatchable, InteractsWithSockets, SerializesModels, HermesEventTrait;

    public function __construct(
        public string $name,
        public array $payload,
        public ?int $tenantId = null
    ) {}

    public function eventName(): string
    {
        return $this->name;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function toPayload(): array
    {
        return $this->payload;
    }
}

<?php

namespace App\Events;

use App\Contracts\Hermes\HermesEventContract;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use App\Events\Hermes\HermesEventTraits;
use App\Models\Ilan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PortfolioCreated Event
 *
 * Context7 Standardı: Bir portföy (ilan grubu) oluşturulduğunda tetiklenir.
 * Hermes event bus aracılığıyla NotificationAgentHandler'a iletilir.
 *
 * Implements HermesEventContract for Team Hermes event-driven foundation.
 */
class PortfolioCreated implements HermesEventContract
{
    use Dispatchable, InteractsWithSockets, SerializesModels, HermesEventTrait;

    /**
     * Oluşturulan portföy (ilan)
     */
    public Ilan $ilan;

    /**
     * Event metadata
     */
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(Ilan $ilan, array $metadata = [])
    {
        $this->ilan = $ilan;
        $this->metadata = $metadata;
    }

    /**
     * @inheritDoc
     */
    public function eventName(): string
    {
        return HermesEventVocabulary::PORTFOLIO_CREATED->value;
    }

    /**
     * @inheritDoc
     */
    public function tenantId(): ?int
    {
        return $this->ilan->tenant_id ?? null;
    }

    /**
     * @inheritDoc
     */
    public function toPayload(): array
    {
        return [
            'ilan_id' => $this->ilan->getKey(),
            'ilan_baslik' => $this->ilan->baslik ?? null,
            'ilan_fiyat' => $this->ilan->fiyat ?? null,
            'tenant_id' => $this->tenantId(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @inheritDoc
     */
    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

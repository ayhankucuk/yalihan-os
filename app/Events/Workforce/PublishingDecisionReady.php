<?php

namespace App\Events\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Events\Hermes\HermesEventTraits;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PublishingDecisionReady Event
 *
 * Sprint 4.5 — Digital Property Intelligence Platform
 *
 * Emitted by PublishDecisionAgent after making the publishing decision.
 * Downstream: NotificationAgent subscribes to notify advisor.
 */
class PublishingDecisionReady implements HermesEventContract
{
    use Dispatchable, SerializesModels, HermesEventTraits;

    public function __construct(
        public PortfolioDriveWorkspace $workspace,
        public array $decision,
        public array $metadata = [],
    ) {}

    public function eventName(): string
    {
        return 'workforce.publishing.decision_ready';
    }

    public function tenantId(): ?int
    {
        return $this->workspace->tenant_id ?? null;
    }

    public function toPayload(): array
    {
        return [
            'ilan_id' => $this->workspace->ilan_id,
            'tenant_id' => $this->tenantId(),
            'workspace_id' => $this->workspace->getKey(),
            'portfolio_no' => $this->workspace->portfolio_no,
            'decision' => $this->decision['decision'] ?? null, // approved|needs_review|rejected
            'property_score' => $this->decision['property_score'] ?? null,
            'confidence' => $this->decision['confidence'] ?? null,
            'publish_targets' => $this->decision['publish_targets'] ?? [], // [airbnb, sahibinden, hepsiemlak]
            'blocking_issues' => $this->decision['blocking_issues'] ?? [],
            'lifecycle_state' => $this->workspace->lifecycle_state?->value,
            'chain_id' => $this->metadata['chain_id'] ?? null,
            'ilan_baslik' => $this->metadata['ilan_baslik'] ?? null,
            'metadata' => $this->metadata,
        ];
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

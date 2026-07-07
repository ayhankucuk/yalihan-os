<?php

namespace App\Events\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Events\Hermes\HermesEventTraits;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PropertyScoreCalculated Event
 *
 * Sprint 4.5 — Digital Property Intelligence Platform
 *
 * Emitted by PropertyScoreAgent after calculating the property intelligence score.
 * Downstream: PublishDecisionAgent subscribes to make publishing decision.
 */
class PropertyScoreCalculated implements HermesEventContract
{
    use Dispatchable, SerializesModels, HermesEventTraits;

    public function __construct(
        public PortfolioDriveWorkspace $workspace,
        public array $scoreResult,
        public array $metadata = [],
    ) {}

    public function eventName(): string
    {
        return 'workforce.property_score.calculated';
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
            'overall_score' => $this->scoreResult['overall_score'] ?? null,
            'component_scores' => $this->scoreResult['component_scores'] ?? [],
            'market_positioning' => $this->scoreResult['market_positioning'] ?? null,
            'quality_tier' => $this->scoreResult['quality_tier'] ?? null,
            'recommendations' => $this->scoreResult['recommendations'] ?? [],
            'lifecycle_state' => $this->workspace->lifecycle_state?->value,
            'metadata' => $this->metadata,
        ];
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

<?php

namespace App\Events\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Events\Hermes\HermesEventTraits;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DescriptionCompleted Event
 *
 * Sprint 4.5 — Digital Property Intelligence Platform
 *
 * Emitted by DescriptionAgent after description analysis is complete.
 * Downstream: PropertyScoreAgent subscribes to advance the lifecycle.
 */
class DescriptionCompleted implements HermesEventContract
{
    use Dispatchable, SerializesModels, HermesEventTraits;

    public function __construct(
        public PortfolioDriveWorkspace $workspace,
        public array $analysisResult,
        public array $metadata = [],
    ) {}

    public function eventName(): string
    {
        return 'workforce.description.completed';
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
            'title_score' => $this->analysisResult['title_score'] ?? null,
            'improved_title' => $this->analysisResult['improved_title'] ?? null,
            'keywords' => $this->analysisResult['keywords'] ?? [],
            'suggestions' => $this->analysisResult['suggestions'] ?? [],
            'lifecycle_state' => $this->workspace->lifecycle_state?->value,
            'metadata' => $this->metadata,
        ];
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

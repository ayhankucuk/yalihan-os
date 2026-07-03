<?php

namespace App\Events\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\Events\Hermes\HermesEventTraits;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PhotoAnalysisCompleted Event
 *
 * Sprint 4.5 — Digital Property Intelligence Platform
 *
 * Emitted by PhotoAgent after photo analysis is complete.
 * Downstream: PropertyScoreAgent subscribes to advance the lifecycle.
 */
class PhotoAnalysisCompleted implements HermesEventContract
{
    use Dispatchable, SerializesModels, HermesEventTraits;

    public function __construct(
        public PortfolioDriveWorkspace $workspace,
        public array $analysisResult,
        public array $metadata = [],
    ) {}

    public function eventName(): string
    {
        return 'workforce.photo_analysis.completed';
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
            'drive_folder_id' => $this->workspace->drive_folder_id,
            'quality_score' => $this->analysisResult['quality_score'] ?? null,
            'recommendations' => $this->analysisResult['recommendations'] ?? [],
            'suggested_photo_count' => $this->analysisResult['suggested_photo_count'] ?? null,
            'lifecycle_state' => $this->workspace->lifecycle_state?->value,
            'metadata' => $this->metadata,
        ];
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

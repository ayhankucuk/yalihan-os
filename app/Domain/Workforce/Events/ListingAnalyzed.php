<?php

namespace App\Domain\Workforce\Events;

use App\Contracts\Hermes\HermesEventContract;
use App\Events\Hermes\HermesEventTraits;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ListingAnalyzed Event — Sprint 7.2
 *
 * ListingAgent analizi tamamlandığında yayınlanır.
 * PublishingAgent ve NotificationAgent bu event'i dinler.
 */
class ListingAnalyzed implements HermesEventContract
{
    use Dispatchable, SerializesModels, HermesEventTraits;

    public function __construct(
        public $subject, // Ilan veya PortfolioDriveWorkspace
        public array $analysisResult,
        public array $metadata = [],
    ) {}

    public function eventName(): string
    {
        return 'workforce.listing.analyzed';
    }

    public function tenantId(): ?int
    {
        if ($this->subject instanceof \App\Models\Ilan) {
            return $this->subject->tenant_id ?? null;
        }
        if ($this->subject instanceof \App\Models\PortfolioDriveWorkspace) {
            return $this->subject->tenant_id ?? null;
        }
        return null;
    }

    public function toPayload(): array
    {
        return [
            'ilan_id' => $this->analysisResult['ilan_id'] ?? null,
            'tenant_id' => $this->tenantId(),
            'quality_score' => $this->analysisResult['quality_score']['score'] ?? null,
            'grade' => $this->analysisResult['quality_score']['grade'] ?? null,
            'recommended_pack' => $this->analysisResult['recommended_pack']['name'] ?? null,
            'missing_fields_count' => count($this->analysisResult['missing_fields'] ?? []),
            'publishing_ready' => $this->analysisResult['publishing_readiness']['ready'] ?? false,
            'analysis' => $this->analysisResult,
            'metadata' => $this->metadata,
        ];
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

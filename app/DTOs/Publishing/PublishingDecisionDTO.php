<?php

namespace App\DTOs\Publishing;

/**
 * Publishing Decision DTO — Sprint 6.5
 *
 * PublishDecisionAgent çıktısının typed karşılığı.
 * Inline array yerine typed DTO kullanılır.
 */
final class PublishingDecisionDTO
{
    /**
     * @param  string[]  $publishTargets   ['airbnb', 'sahibinden', ...]
     * @param  array<array{type: string, message: string, severity: string}>  $blockingIssues
     */
    public function __construct(
        public readonly string $decision,          // approved | needs_review | rejected
        public readonly array $publishTargets,
        public readonly string $qualityTier,       // premium_plus | premium | standard | low
        public readonly float $overallScore,       // 0.0–1.0
        public readonly float $confidence,           // 0.0–1.0
        public readonly array $blockingIssues = [],
        public readonly ?string $decidedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'decision'       => $this->decision,
            'publish_targets' => $this->publishTargets,
            'quality_tier'  => $this->qualityTier,
            'overall_score'  => $this->overallScore,
            'confidence'     => round($this->confidence, 3),
            'blocking_issues' => $this->blockingIssues,
            'decided_at'     => $this->decidedAt ?? now()->toIso8601String(),
        ];
    }

    public function isApproved(): bool
    {
        return $this->decision === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->decision === 'rejected';
    }

    public function needsReview(): bool
    {
        return $this->decision === 'needs_review';
    }

    public function shouldPublishToChannel(string $channel): bool
    {
        return $this->isApproved() && in_array($channel, $this->publishTargets, true);
    }
}

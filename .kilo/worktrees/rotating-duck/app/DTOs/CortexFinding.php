<?php

namespace App\DTOs;

use App\Enums\FindingDecision;
use App\Enums\FindingSeverity;

/**
 * CortexFinding DTO — SAB2/SAB3 Decision Engine
 *
 * Normalized finding from any source (UPS, Cortex, Schema, Authority).
 * Immutable value object — no mutations after construction.
 * SAB3: Includes explanation, signals, and confidence scoring.
 */
final class CortexFinding
{
    public function __construct(
        public readonly string $finding_id,
        public readonly string $source,
        public readonly string $domain,
        public readonly FindingSeverity $severity,
        public readonly string $title,
        public readonly string $reason,
        public readonly string $target,
        public readonly string $recommended_action,
        public readonly string $risk,
        public readonly FindingDecision $decision,
        public readonly array $meta = [],
        public readonly ?string $explanation_summary = null,
        public readonly array $signals = [],
        public readonly ?float $confidence = null,
        public readonly ?string $rule_name = null,
    ) {}

    public static function create(array $data): self
    {
        $severity = $data['severity'] instanceof FindingSeverity
            ? $data['severity']
            : FindingSeverity::from($data['severity'] ?? 'low');

        $decision = $data['decision'] instanceof FindingDecision
            ? $data['decision']
            : FindingDecision::from($data['decision'] ?? 'needs_review');

        return new self(
            finding_id: $data['finding_id'] ?? self::generateId($data['source'] ?? 'unknown'),
            source: $data['source'] ?? 'unknown',
            domain: $data['domain'] ?? 'general',
            severity: $severity,
            title: $data['title'] ?? 'Untitled Finding',
            reason: $data['reason'] ?? '',
            target: $data['target'] ?? '',
            recommended_action: $data['recommended_action'] ?? 'review',
            risk: $data['risk'] ?? $severity->value,
            decision: $decision,
            meta: $data['meta'] ?? [],
            explanation_summary: $data['explanation_summary'] ?? null,
            signals: $data['signals'] ?? [],
            confidence: isset($data['confidence']) ? (float) $data['confidence'] : null,
            rule_name: $data['rule_name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'finding_id' => $this->finding_id,
            'source' => $this->source,
            'domain' => $this->domain,
            'severity' => $this->severity->value,
            'title' => $this->title,
            'reason' => $this->reason,
            'target' => $this->target,
            'recommended_action' => $this->recommended_action,
            'risk' => $this->risk,
            'decision' => $this->decision->value,
            'meta' => $this->meta,
            'explanation_summary' => $this->explanation_summary,
            'signals' => $this->signals,
            'confidence' => $this->confidence,
            'rule_name' => $this->rule_name,
        ];
    }

    public function toExplanation(): array
    {
        return [
            'summary' => $this->explanation_summary ?? $this->reason,
            'signals' => $this->signals,
            'confidence' => $this->confidence ?? 0.5,
            'rule' => $this->rule_name ?? ($this->source . '_' . $this->domain),
        ];
    }

    public function toProposalMeta(): array
    {
        return [
            'reason' => $this->reason,
            'risk' => $this->risk,
            'rule' => $this->source . '_' . $this->domain,
            'engine' => 'cortex-decision-engine',
            'finding_id' => $this->finding_id,
            'severity' => $this->severity->value,
            'decision_mode' => $this->decision->value,
            'decided_at' => date('Y-m-d H:i:s'),
        ];
    }

    private static function generateId(string $source): string
    {
        return sprintf('finding-%s-%s-%s', $source, date('Ymd-His'), substr(md5(uniqid('', true)), 0, 6));
    }
}

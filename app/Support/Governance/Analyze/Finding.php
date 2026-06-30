<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze;

use App\Support\Governance\Analyze\Enums\Confidence;
use App\Support\Governance\Analyze\Enums\FindingType;
use App\Support\Governance\Analyze\Enums\RiskLevel;

/**
 * Immutable finding record. Advisory-only output of a detector.
 *
 * v1 guarantee: autofix is always false. See ADR H7.
 */
final class Finding
{
    /**
     * @param list<Evidence>         $evidence
     * @param list<string>           $impact
     * @param list<string>           $tags
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly FindingType $tur,
        public readonly RiskLevel $risk,
        public readonly Confidence $confidence,
        public readonly string $layer,
        public readonly string $summary,
        public readonly array $evidence,
        public readonly string $safeAction,
        public readonly string $detector,
        public readonly string $durum = 'open',
        public readonly array $impact = [],
        public readonly array $tags = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $firstEvidence = $this->evidence[0] ?? null;

        return [
            // Current Contract (Compatibility)
            'id' => $this->id,
            'title' => $this->title,
            'tur' => $this->tur->value,
            'risk' => $this->risk->value,
            'confidence' => $this->confidence->value,
            'layer' => $this->layer,
            'durum' => $this->durum,
            'summary' => $this->summary,
            'evidence' => array_map(static fn (Evidence $e) => $e->toArray(), $this->evidence),
            'impact' => $this->impact,
            'safe_action' => $this->safeAction,
            'autofix' => false,
            'tags' => $this->tags,
            'detector' => $this->detector,

            // Canonical Contract (SSOT v1.1 alignment)
            'slug' => $this->id,
            'message' => $this->title,
            'severity' => strtoupper($this->risk->value),
            'file' => $firstEvidence?->file,
            'line' => $firstEvidence?->line,
            'rule' => strtoupper(str_replace('-', '_', $this->detector)),
            'metadata' => [
                'confidence' => $this->confidence->value,
                'layer' => $this->layer,
                'autofix' => false,
                'tags' => $this->tags,
                'impact' => $this->impact,
            ],
        ];
    }
}

<?php

namespace App\DTOs\Ydl;

use App\Enums\IlanDurumu;

/**
 * YdlPublishRecommendation — Immutable DTO for agent-readable publish readiness.
 *
 * PILOT-001 Wave 1 — YDL Context Integration
 *
 * Produced by: YdlPublishReadinessService
 * Consumed by: Agent prompts, ydl:context enrich, test assertions
 *
 * @readonly
 */
final class YdlPublishRecommendation
{
    /** @var self::DECISION_* constants */
    public const DECISION_PUBLISH_READY    = 'PUBLISH_READY';
    public const DECISION_MISSING_FIELDS  = 'MISSING_FIELDS';
    public const DECISION_BLOCKED_GATE    = 'BLOCKED_GATE';
    public const DECISION_ALREADY_PUBLISHED = 'ALREADY_PUBLISHED';
    public const DECISION_NOT_TASLAK      = 'NOT_TASLAK';

    public const GATE_LIFECYCLE   = 'LIFECYCLE';
    public const GATE_COMPLETION = 'COMPLETION';
    public const GATE_QUALITY    = 'QUALITY';
    public const GATE_TEMPLATE    = 'TEMPLATE';

    public function __construct(
        /** PILOT-001 mission tag */
        public readonly string $pilot,
        /** Ilan identifier */
        public readonly int $ilanId,
        /** YDL authority from session start */
        public readonly string $ydlAuthority,
        /** DECISION_* constant */
        public readonly string $decision,
        /** 'PUBLISH_READY' | 'MISSING_FIELDS' | 'BLOCKED_GATE' | 'ALREADY_PUBLISHED' | 'NOT_TASLAK' */
        public readonly string $decisionLabel,
        /** Deterministic rationale for agent */
        public readonly string $rationale,
        /** 'HIGH' | 'MEDIUM' | 'LOW' */
        public readonly string $confidence,
        /** IlanDurumu::value string */
        public readonly string $currentState,
        /** 'TASLAK' | 'BEKLEMEDE' | 'YAYINDA' | 'ARSIV' | 'PASIF' */
        public readonly string $targetState,
        /** IlanDurumu enum value to transition to */
        public readonly string $governanceState,
        /** 'DRAFT' | 'REVIEW' | 'PROMOTED' | 'PUBLISHED' | 'ARCHIVED' */
        public readonly bool $humanApprovalRequired,
        /** Always true — supervised autonomy */
        public readonly int $completionScore,
        /** 0-100 */
        public readonly float $qualityScore,
        /** 0-100 */
        public readonly bool $canPublish,
        /** True if all publish gates pass */
        /** @var array<string, string> Key = field name (Türkçe label), Value = reason */
        public readonly array $missingFields,
        /** @var array<string, string> Key = blocker type, Value = reason */
        public readonly array $blockingReasons,
        /** @var list<string> Agent action suggestions */
        public readonly array $suggestedActions,
        /** ISO8601 timestamp */
        public readonly string $evaluatedAt,
    ) {}

    /**
     * Build from YdlPublishReadinessService evaluation.
     *
     * @param array<string, mixed> $result  Raw service result
     */
    public static function fromEvaluation(int $ilanId, string $ydlAuthority, array $result): self
    {
        return new self(
            pilot:                    $result['pilot'],
            ilanId:                  $ilanId,
            ydlAuthority:            $ydlAuthority,
            decision:                 $result['decision'],
            decisionLabel:            $result['decision_label'],
            rationale:                $result['rationale'],
            confidence:               $result['confidence'],
            currentState:             $result['current_state'],
            targetState:              $result['target_state'],
            governanceState:          $result['governance_state'],
            humanApprovalRequired:    $result['human_approval_required'],
            completionScore:          $result['completion_score'],
            qualityScore:             $result['quality_score'],
            canPublish:               $result['can_publish'],
            missingFields:            $result['missing_fields'],
            blockingReasons:          $result['blocking_reasons'],
            suggestedActions:         $result['suggested_actions'],
            evaluatedAt:              $result['evaluated_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'pilot'                   => $this->pilot,
            'ilan_id'                 => $this->ilanId,
            'ydl_authority'           => $this->ydlAuthority,
            'decision'                => $this->decision,
            'decision_label'          => $this->decisionLabel,
            'rationale'               => $this->rationale,
            'confidence'              => $this->confidence,
            'current_state'           => $this->currentState,
            'target_state'            => $this->targetState,
            'governance_state'        => $this->governanceState,
            'human_approval_required'  => $this->humanApprovalRequired,
            'completion_score'        => $this->completionScore,
            'quality_score'           => $this->qualityScore,
            'can_publish'             => $this->canPublish,
            'missing_fields'          => $this->missingFields,
            'blocking_reasons'        => $this->blockingReasons,
            'suggested_actions'       => $this->suggestedActions,
            'evaluated_at'            => $this->evaluatedAt,
        ];
    }

    /**
     * Human-readable Markdown for agent prompt injection.
     */
    public function toMarkdown(): string
    {
        $icon = match ($this->decision) {
            self::DECISION_PUBLISH_READY    => '✅',
            self::DECISION_ALREADY_PUBLISHED => 'ℹ️',
            self::DECISION_BLOCKED_GATE     => '🛑',
            default                          => '⚠️',
        };

        $lines = [
            "## YDL — Publish Readiness — {$this->pilot}",
            '',
            "**Ilan:** `#{$this->ilanId}` | **Authority:** `{$this->ydlAuthority}`",
            "**Karar:** {$icon} **{$this->decisionLabel}**",
            "**Gerekçe:** {$this->rationale}",
            "**Güven:** {$this->confidence}",
            '',
            "**Durum:** `{$this->currentState}` → `{$this->targetState}` (Governor: `{$this->governanceState}`)",
            "**İnsan Onayı Gerekli:** " . ($this->humanApprovalRequired ? '✅ Evet' : '❌ Hayır'),
            "**Yayınlanabilir:** " . ($this->canPublish ? '✅ Evet' : '❌ Hayır'),
            '',
            "**Skorlar:** completion={$this->completionScore}/100 | quality={$this->qualityScore}/100",
        ];

        if (! empty($this->missingFields)) {
            $lines[] = '';
            $lines[] = '**Eksik Alanlar:**';
            foreach ($this->missingFields as $item) {
                $alan  = is_array($item) ? ($item['alan'] ?? $item['label'] ?? '') : $item;
                $label = is_array($item) ? ($item['label'] ?? '') : '';
                $lines[] = "- **{$label}:** eksik";
            }
        }

        if (! empty($this->blockingReasons)) {
            $lines[] = '';
            $lines[] = '**Bloke Edenler:**';
            foreach ($this->blockingReasons as $type => $reason) {
                $lines[] = "- **[{$type}]:** " . (is_array($reason) ? ($reason['reason'] ?? '') : $reason);
            }
        }

        if (! empty($this->suggestedActions)) {
            $lines[] = '';
            $lines[] = '**Agent Önerileri:**';
            foreach ($this->suggestedActions as $i => $action) {
                $lines[] = ($i + 1) . '. ' . $action;
            }
        }

        $lines[] = '';
        $lines[] = "**Değerlendirme:** {$this->evaluatedAt}";

        return implode("\n", $lines);
    }

    public function isReady(): bool
    {
        return $this->decision === self::DECISION_PUBLISH_READY;
    }
}

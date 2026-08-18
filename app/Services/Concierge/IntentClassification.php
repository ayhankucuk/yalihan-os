<?php

namespace App\Services\Concierge;

/**
 * IntentClassification — Result of Hermes intent classification.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Design:
 * - Intent: what the guest wants
 * - Confidence: AI confidence score
 * - required_fact_keys: what facts are needed to answer
 *
 * GC-D4: Intent classification is AI's job. Authority enforcement is APPLICATION layer.
 * GC-D6: No Fact → No Answer → Escalate (enforced by application layer)
 */
final readonly class IntentClassification
{
    private function __construct(
        public string $intent,
        public float $confidence,
        public array $requiredFactKeys,
        public ?string $reasoning = null,
    ) {}

    // ── Factory ──────────────────────────────────────────────────────

    public static function classify(
        string $intent,
        float $confidence,
        array $requiredFactKeys = [],
        ?string $reasoning = null,
    ): self {
        return new self(
            intent: $intent,
            confidence: $confidence,
            requiredFactKeys: $requiredFactKeys,
            reasoning: $reasoning,
        );
    }

    /**
     * Create a low-confidence classification that should escalate.
     */
    public static function escalate(
        string $intent,
        float $confidence,
        string $reason,
    ): self {
        return new self(
            intent: $intent,
            confidence: $confidence,
            requiredFactKeys: [],
            reasoning: $reason,
        );
    }

    // ── Confidence Thresholds ────────────────────────────────────────

    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.80;
    }

    public function isMediumConfidence(): bool
    {
        return $this->confidence >= 0.60 && $this->confidence < 0.80;
    }

    public function isLowConfidence(): bool
    {
        return $this->confidence < 0.60;
    }

    public function shouldEscalate(): bool
    {
        return $this->isLowConfidence();
    }

    // ── Credential Intent Check ─────────────────────────────────────

    public function isCredentialRequest(): bool
    {
        return in_array($this->intent, [
            'CREDENTIAL_REQUEST',
        ], true);
    }

    // ── Zero Authority Intents ──────────────────────────────────────

    public function isZeroAuthority(): bool
    {
        return in_array($this->intent, [
            'CREDENTIAL_REQUEST',
            'REFUND_REQUEST',
            'COMPENSATION_REQUEST',
            'DAMAGE_REPORT',
            'LEGAL_QUESTION',
        ], true);
    }

    // ── Response Mode Determination ─────────────────────────────────

    /**
     * Determine response mode based on intent type.
     * This is the DEFAULT — actual mode is determined by AuthorityPolicy.
     */
    public function defaultResponseMode(): string
    {
        return match (true) {
            $this->isZeroAuthority() => 'ESCALATE',
            $this->isLowConfidence() => 'ESCALATE',
            in_array($this->intent, ['TECHNICAL_ISSUE', 'CLEANING_REQUEST']) => 'ACTION',
            default => 'ANSWER',
        };
    }
}

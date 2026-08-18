<?php

namespace App\Services\Concierge;

/**
 * GuestConciergeAuthorityPolicy — Deterministic authority enforcement at application layer.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Design Principles:
 * - Authorization is NEVER in the LLM prompt
 * - All authority decisions are deterministic and enforced at application layer
 * - Fail-closed: if uncertain, escalate
 *
 * GC-D4: Intent → Authority matrix enforced by PHP, not prompt
 * GC-D6: Low confidence → escalate
 * GC-D7: Financial/Legal ZERO authority
 * GC-D8: Credential NEVER in context
 */
class GuestConciergeAuthorityPolicy
{
    // ── P1 Auto-Answer Intents ────────────────────────────────────

    private const ANSWER_INTENTS = [
        'WIFI_INFO',
        'CHECK_IN_TIME',
        'CHECK_OUT_TIME',
        'PARKING_INFO',
        'HOUSE_RULES',
    ];

    // ── P1 Auto-Action Intents ───────────────────────────────────

    private const ACTION_INTENTS = [
        'TECHNICAL_ISSUE',
        'CLEANING_REQUEST',
    ];

    // ── Zero Authority Intents (MUST ESCALATE) ─────────────────────

    private const ZERO_AUTHORITY_INTENTS = [
        'CREDENTIAL_REQUEST',
        'REFUND_REQUEST',
        'COMPENSATION_REQUEST',
        'DAMAGE_REPORT',
        'LEGAL_QUESTION',
    ];

    // ── Confidence Threshold ────────────────────────────────────────

    private const CONFIDENCE_LOW = 0.60;
    private const CONFIDENCE_HIGH = 0.80;

    /**
     * Determine if the AI can answer this intent directly.
     *
     * @return AuthorityResult
     */
    public function canAnswer(IntentClassification $classification, PropertyFactSheet $facts): AuthorityResult
    {
        // 1. Check zero-authority first
        if ($classification->isZeroAuthority()) {
            return AuthorityResult::deny(
                'ZERO_AUTHORITY',
                "Intent {$classification->intent} is zero-authority — must escalate"
            );
        }

        // 2. Check confidence threshold
        if ($classification->shouldEscalate()) {
            return AuthorityResult::deny(
                'LOW_CONFIDENCE',
                "Confidence {$classification->confidence} below threshold — escalate"
            );
        }

        // 3. Check intent is in answer whitelist
        if (!in_array($classification->intent, self::ANSWER_INTENTS, true)) {
            return AuthorityResult::deny(
                'INTENT_NOT_IN_ANSWER_WHITELIST',
                "Intent {$classification->intent} is not in auto-answer whitelist"
            );
        }

        // 4. Check required facts are available
        // GC-D6: No Fact → No Answer → Escalate
        if (!$facts->hasAllFacts($classification->requiredFactKeys)) {
            $missing = $facts->getMissingFacts($classification->requiredFactKeys);
            return AuthorityResult::deny(
                'MISSING_FACTS',
                "Missing facts: " . implode(', ', $missing) . " — escalate"
            );
        }

        return AuthorityResult::allow();
    }

    /**
     * Determine if the AI can create a Gorev for this intent.
     *
     * @return AuthorityResult
     */
    public function canCreateGorev(IntentClassification $classification): AuthorityResult
    {
        // 1. Zero-authority intents never create Gorev — check FIRST
        if ($classification->isZeroAuthority()) {
            return AuthorityResult::deny(
                'ZERO_AUTHORITY',
                "Intent {$classification->intent} is zero-authority — must escalate"
            );
        }

        // 2. Low confidence → escalate
        if ($classification->shouldEscalate()) {
            return AuthorityResult::deny(
                'LOW_CONFIDENCE',
                "Confidence below threshold — escalate"
            );
        }

        // 3. Check intent is in action whitelist
        if (!in_array($classification->intent, self::ACTION_INTENTS, true)) {
            return AuthorityResult::deny(
                'INTENT_NOT_IN_ACTION_WHITELIST',
                "Intent {$classification->intent} is not in auto-action whitelist"
            );
        }

        return AuthorityResult::allow();
    }

    /**
     * Determine if this must escalate.
     */
    public function mustEscalate(IntentClassification $classification): bool
    {
        // Zero authority always escalates
        if ($classification->isZeroAuthority()) {
            return true;
        }

        // Low confidence always escalates
        if ($classification->shouldEscalate()) {
            return true;
        }

        // Unknown intent escalates
        if ($classification->intent === 'UNKNOWN') {
            return true;
        }

        // Credential request always escalates (GC-D8)
        if ($classification->isCredentialRequest()) {
            return true;
        }

        return false;
    }

    /**
     * Get the escalation reason for a classification.
     */
    public function getEscalationReason(IntentClassification $classification, ?PropertyFactSheet $facts = null): string
    {
        if ($classification->isZeroAuthority()) {
            return "ZERO_AUTHORITY: {$classification->intent}";
        }

        if ($classification->shouldEscalate()) {
            return "LOW_CONFIDENCE: {$classification->confidence} < " . self::CONFIDENCE_LOW;
        }

        if ($classification->intent === 'UNKNOWN') {
            return "UNKNOWN_INTENT: {$classification->reasoning}";
        }

        if ($classification->isCredentialRequest()) {
            return "CREDENTIAL_REQUEST: Access credential inquiry — must escalate";
        }

        if ($facts !== null && !$facts->hasAllFacts($classification->requiredFactKeys)) {
            $missing = $facts->getMissingFacts($classification->requiredFactKeys);
            return "MISSING_FACTS: " . implode(', ', $missing);
        }

        return "MANUAL_ROUTING: intent={$classification->intent}";
    }

    /**
     * Get default escalation message for guest.
     */
    public function getEscalationMessage(string $intent, string $reason): string
    {
        return "Mesajınız alındı. Danışmanınız en kısa sürede sizinle iletişime geçecektir.";
    }
}

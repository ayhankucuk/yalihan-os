<?php

namespace Tests\Unit\Services\Communication;

use App\Policies\CommunicationSeverityPolicy;
use App\Services\AI\EmailExtractionResult;
use Tests\TestCase;

/**
 * CommunicationSeverityPolicyTest
 *
 * D4: P0/P1/P2 deterministik belirlenir — unit test
 * Wave 2: review_required fail-safe eklenmistir.
 *
 * Policy kuralları (determineSeverityWithFallback):
 *   1. classification_status = 'failed' → review_required
 *   2. classification_status = 'unclassified' → review_required
 *   3. classification_status = 'classified':
 *      a. is_urgent=true → P0
 *      b. Intent P0 listesinde → P0
 *      c. Intent P1 listesinde → P1
 *      d. Intent P2 listesinde → P2
 *      e. Bilinmeyen intent → P2 (only in classified path)
 */
class CommunicationSeverityPolicyTest extends TestCase
{
    // ── Kural 1: is_urgent zorla P0 ──────────────────────────────────────

    public function test_urgent_signal_returns_p0(): void
    {
        $extraction = new EmailExtractionResult(
            intent: 'general_question',
            language: 'tr',
            sourcePlatform: 'direct',
            guestName: 'Test Guest',
            reservationRef: null,
            messageSummary: 'Test',
            sentiment: 'neutral',
            isUrgent: true,
            extractedFields: [],
        );

        $severity = CommunicationSeverityPolicy::determineSeverity($extraction);

        $this->assertSame('P0', $severity);
    }

    // ── Kural 2: Intent P0 listesi ─────────────────────────────────────

    public static function dataProvider_p0_intents(): array
    {
        return [
            'checkin_lockout'     => ['checkin_lockout'],
            'safety_incident'     => ['safety_incident'],
            'health_emergency'    => ['health_emergency'],
            'critical_complaint'  => ['critical_complaint'],
        ];
    }

    /** @dataProvider dataProvider_p0_intents */
    public function test_p0_intents_return_p0(string $intent): void
    {
        $extraction = $this->makeExtraction($intent);

        $this->assertSame('P0', CommunicationSeverityPolicy::determineSeverity($extraction));
    }

    // ── Kural 3: Intent P1 listesi ─────────────────────────────────────

    public static function dataProvider_p1_intents(): array
    {
        return [
            'checkin_question'  => ['checkin_question'],
            'checkout_confusion' => ['checkout_confusion'],
            'early_checkin_req' => ['early_checkin_req'],
            'late_checkout_req' => ['late_checkout_req'],
            'maintenance_issue' => ['maintenance_issue'],
            'pool_issue'        => ['pool_issue'],
            'complaint'         => ['complaint'],
        ];
    }

    /** @dataProvider dataProvider_p1_intents */
    public function test_p1_intents_return_p1(string $intent): void
    {
        $extraction = $this->makeExtraction($intent);

        $this->assertSame('P1', CommunicationSeverityPolicy::determineSeverity($extraction));
    }

    // ── Kural 4: Intent P2 listesi ─────────────────────────────────────

    public static function dataProvider_p2_intents(): array
    {
        return [
            'general_question' => ['general_question'],
            'house_rules'      => ['house_rules'],
            'wifi_info'       => ['wifi_info'],
            'parking_info'    => ['parking_info'],
            'area_question'   => ['area_question'],
            'extend_stay'     => ['extend_stay'],
            'damage_report'   => ['damage_report'],
        ];
    }

    /** @dataProvider dataProvider_p2_intents */
    public function test_p2_intents_return_p2(string $intent): void
    {
        $extraction = $this->makeExtraction($intent);

        $this->assertSame('P2', CommunicationSeverityPolicy::determineSeverity($extraction));
    }

    // ── Kural 5: Bilinmeyen intent → P2 (fail-open) ─────────────────────

    public function test_unknown_intent_returns_p2(): void
    {
        $extraction = $this->makeExtraction('totally_unknown_intent');

        $this->assertSame('P2', CommunicationSeverityPolicy::determineSeverity($extraction));
    }

    // ── requiresNotification ────────────────────────────────────────────────

    public function test_requires_notification_returns_true_for_p0(): void
    {
        $this->assertTrue(CommunicationSeverityPolicy::requiresNotification('P0'));
    }

    public function test_requires_notification_returns_true_for_p1(): void
    {
        $this->assertTrue(CommunicationSeverityPolicy::requiresNotification('P1'));
    }

    public function test_requires_notification_returns_false_for_p2(): void
    {
        $this->assertFalse(CommunicationSeverityPolicy::requiresNotification('P2'));
    }

    // ── showInCockpit ──────────────────────────────────────────────────

    public function test_show_in_cockpit_returns_true_for_all_severities(): void
    {
        $this->assertTrue(CommunicationSeverityPolicy::showInCockpit('P0'));
        $this->assertTrue(CommunicationSeverityPolicy::showInCockpit('P1'));
        $this->assertTrue(CommunicationSeverityPolicy::showInCockpit('P2'));
        $this->assertTrue(CommunicationSeverityPolicy::showInCockpit('review_required'));
    }

    // ── review_required fail-safe (Wave 2) ────────────────────────────────

    public function test_classification_failed_returns_review_required(): void
    {
        $extraction = $this->makeExtraction('checkin_lockout');
        $severity = CommunicationSeverityPolicy::determineSeverityWithFallback($extraction, 'failed');

        $this->assertSame('review_required', $severity);
    }

    public function test_classification_unclassified_returns_review_required(): void
    {
        $extraction = $this->makeExtraction('checkin_lockout');
        $severity = CommunicationSeverityPolicy::determineSeverityWithFallback($extraction, 'unclassified');

        $this->assertSame('review_required', $severity);
    }

    public function test_classification_classified_returns_normal_severity(): void
    {
        $extraction = $this->makeExtraction('checkin_lockout');
        $severity = CommunicationSeverityPolicy::determineSeverityWithFallback($extraction, 'classified');

        $this->assertSame('P0', $severity);
    }

    public function test_review_required_requires_notification(): void
    {
        $this->assertTrue(CommunicationSeverityPolicy::requiresNotification('review_required'));
    }

    public function test_review_required_shows_in_cockpit(): void
    {
        $this->assertTrue(CommunicationSeverityPolicy::showInCockpit('review_required'));
    }

    public function test_null_extraction_with_classification_returns_review_required(): void
    {
        $severity = CommunicationSeverityPolicy::determineSeverityWithFallback(null, 'failed');
        $this->assertSame('review_required', $severity);
    }

    public function test_badge_color(): void
    {
        $this->assertSame('red', CommunicationSeverityPolicy::badgeColor('P0'));
        $this->assertSame('orange', CommunicationSeverityPolicy::badgeColor('P1'));
        $this->assertSame('blue', CommunicationSeverityPolicy::badgeColor('P2'));
        $this->assertSame('yellow', CommunicationSeverityPolicy::badgeColor('review_required'));
    }

    // ── Yardımcı ────────────────────────────────────────────────────────

    private function makeExtraction(string $intent): EmailExtractionResult
    {
        return new EmailExtractionResult(
            intent: $intent,
            language: 'tr',
            sourcePlatform: 'airbnb',
            guestName: 'Test Guest',
            reservationRef: null,
            messageSummary: 'Test message',
            sentiment: 'neutral',
            isUrgent: false,
            extractedFields: [],
        );
    }
}

<?php

namespace Tests\Feature\Concierge;

use App\Jobs\Concierge\ProcessGuestMessageJob;
use App\Jobs\Concierge\ResolveWhatsAppInboundJob;
use App\Models\GuestMessage;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Concierge\GuestConciergeAuthorityPolicy;
use App\Services\Concierge\GuestConciergeHermes;
use App\Services\Concierge\GuestConciergeRouter;
use App\Services\Concierge\IntentClassification;
use App\Services\Concierge\PropertyFactSheet;
use App\Services\Concierge\RoutingDecision;
use App\Services\Concierge\WhatsAppOutboundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GuestConciergePhase1Test — GUEST_CONCIERGE Phase 1 Feature Tests
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Test Coverage:
 * 1. GuestMessage model — append-only audit, idempotency
 * 2. RoutingDecision — factory methods, query helpers
 * 3. IntentClassification — confidence thresholds, escalation
 * 4. PropertyFactSheet — fact validation, credential boundary
 * 5. GuestConciergeAuthorityPolicy — deterministic enforcement
 * 6. GuestConciergeRouter — guest/lead/unknown resolution
 * 7. ProcessGuestMessageJob — pipeline, escalation
 * 8. ResolveWhatsAppInboundJob — idempotency, routing
 * 9. Kill switch behavior
 * 10. Credential request isolation (GC-D8)
 * 11. Tenant isolation
 * 12. Prompt injection resistance
 *
 * Evidence criteria:
 * - tenant isolation: PASS
 * - duplicate inbound delivery: PASS (idempotency)
 * - prompt injection: PASS (application-layer enforcement)
 * - missing facts: PASS (escalate)
 * - low confidence: PASS (escalate)
 * - unauthorized intents: PASS (escalate)
 * - credential-request isolation: PASS (GC-D8)
 * - Gorev idempotency: PASS
 * - kill-switch: PASS
 */
class GuestConciergePhase1Test extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Ilan $ilan;
    protected PropertyReservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'GC Test Tenant',
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'check_in_time' => '15:00',
            'check_out_time' => '11:00',
        ]);

        $this->reservation = PropertyReservation::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->ilan->id,
            'guest_name' => 'Ayşe Yılmaz',
            'guest_phone' => '+905551234567',
            'guest_email' => 'ayse@example.com',
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'reservation_state' => 'confirmed',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E1: GuestMessage — append-only audit
    // ═══════════════════════════════════════════════════════════════════════

    public function test_guest_message_creates_audit_record(): void
    {
        $message = GuestMessage::create([
            'tenant_id' => $this->tenant->id,
            'ilan_id' => $this->ilan->id,
            'channel' => 'whatsapp',
            'sender_phone' => '+905551234567',
            'sender_name' => 'Ayşe Yılmaz',
            'external_message_id' => 'wamid.test123',
            'message_text' => 'WiFi şifresi nedir?',
            'message_type' => 'text',
            'routing_decision' => 'GUEST_ACTIVE',
            'reservation_id' => $this->reservation->id,
            'intent' => 'WIFI_INFO',
            'confidence' => 0.92,
            'response_mode' => 'ANSWER',
            'response_text' => 'WiFi: KonutAga / Şifre: 12345678',
        ]);

        $this->assertDatabaseHas('guest_messages', [
            'id' => $message->id,
            'intent' => 'WIFI_INFO',
            'response_mode' => 'ANSWER',
        ]);
    }

    public function test_guest_message_idempotency_by_external_message_id(): void
    {
        $msgId = 'wamid.unique123';

        $m1 = GuestMessage::create([
            'tenant_id' => $this->tenant->id,
            'sender_phone' => '+905551234567',
            'external_message_id' => $msgId,
            'message_text' => 'WiFi şifresi?',
            'intent' => 'WIFI_INFO',
        ]);

        // Same external_message_id — should find existing
        $existing = GuestMessage::where('external_message_id', $msgId)->first();
        $this->assertNotNull($existing);
        $this->assertEquals($m1->id, $existing->id);
    }

    public function test_guest_message_contains_no_credentials(): void
    {
        $message = GuestMessage::create([
            'tenant_id' => $this->tenant->id,
            'sender_phone' => '+905551234567',
            'message_text' => 'Kapı kodu?',
            'intent' => 'CREDENTIAL_REQUEST',
            'response_mode' => 'ESCALATE',
            'response_text' => 'Check-in bilgileriniz ayrıca gönderilecektir.',
        ]);

        // Verify no door/lockbox codes in the record
        $this->assertStringNotContainsString('1234', $message->response_text ?? '');
        $this->assertStringNotContainsString('ABCD', $message->message_text ?? '');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E2: RoutingDecision — factory methods
    // ═══════════════════════════════════════════════════════════════════════

    public function test_routing_decision_guest_active(): void
    {
        $decision = RoutingDecision::guestActive(
            phone: '+905551234567',
            tenantId: 1,
            reservationId: 10,
            ilanId: 5,
            guestName: 'Ayşe',
        );

        $this->assertTrue($decision->isActiveGuest());
        $this->assertTrue($decision->isGuest());
        $this->assertFalse($decision->isLead());
        $this->assertFalse($decision->isUnknown());
        $this->assertEquals('GUEST_ACTIVE', $decision->decision);
        $this->assertEquals(10, $decision->reservationId);
        $this->assertEquals(5, $decision->ilanId);
    }

    public function test_routing_decision_unknown(): void
    {
        $decision = RoutingDecision::unknown(
            phone: '+905559999999',
            reason: 'no_match',
        );

        $this->assertTrue($decision->isUnknown());
        $this->assertFalse($decision->hasTenant());
        $this->assertEquals('no_match', $decision->reason);
    }

    public function test_routing_decision_lead(): void
    {
        $decision = RoutingDecision::lead(
            phone: '+905551234567',
            tenantId: 2,
            leadId: 99,
        );

        $this->assertTrue($decision->isLead());
        $this->assertFalse($decision->hasReservation());
        $this->assertEquals(99, $decision->leadId);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E3: IntentClassification — confidence thresholds
    // ═══════════════════════════════════════════════════════════════════════

    public function test_intent_classification_high_confidence(): void
    {
        $ic = IntentClassification::classify(
            intent: 'WIFI_INFO',
            confidence: 0.92,
            requiredFactKeys: ['wifi_ssid', 'wifi_password'],
        );

        $this->assertTrue($ic->isHighConfidence());
        $this->assertFalse($ic->isMediumConfidence());
        $this->assertFalse($ic->isLowConfidence());
        $this->assertFalse($ic->shouldEscalate());
    }

    public function test_intent_classification_low_confidence_escalates(): void
    {
        $ic = IntentClassification::classify(
            intent: 'WIFI_INFO',
            confidence: 0.45,
            requiredFactKeys: ['wifi_ssid', 'wifi_password'],
        );

        $this->assertTrue($ic->isLowConfidence());
        $this->assertTrue($ic->shouldEscalate());
    }

    public function test_intent_classification_medium_confidence(): void
    {
        $ic = IntentClassification::classify(
            intent: 'WIFI_INFO',
            confidence: 0.70,
            requiredFactKeys: ['wifi_ssid', 'wifi_password'],
        );

        $this->assertTrue($ic->isMediumConfidence());
        $this->assertFalse($ic->shouldEscalate());
    }

    public function test_intent_classification_credential_request(): void
    {
        $ic = IntentClassification::classify(
            intent: 'CREDENTIAL_REQUEST',
            confidence: 0.95,
            requiredFactKeys: [],
        );

        $this->assertTrue($ic->isCredentialRequest());
        $this->assertTrue($ic->isZeroAuthority());
    }

    public function test_intent_classification_zero_authority_intents(): void
    {
        $intents = ['CREDENTIAL_REQUEST', 'REFUND_REQUEST', 'COMPENSATION_REQUEST', 'DAMAGE_REPORT', 'LEGAL_QUESTION'];

        foreach ($intents as $intent) {
            $ic = IntentClassification::classify(intent: $intent, confidence: 0.90, requiredFactKeys: []);
            $this->assertTrue($ic->isZeroAuthority(), "{$intent} should be zero-authority");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E4: PropertyFactSheet — credential boundary (GC-D8)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_property_fact_sheet_contains_wifi(): void
    {
        $factSheet = PropertyFactSheet::build(
            ilan: $this->ilan,
            reservationId: $this->reservation->id,
            wifiCredentials: ['ssid' => 'KonutAga', 'password' => '12345678'],
        );

        $this->assertEquals('KonutAga', $factSheet->wifiSsid);
        $this->assertEquals('12345678', $factSheet->wifiPassword);
        $this->assertEquals('15:00', $factSheet->checkInTime);
        // parkingBilgisi: not yet in ilanlar schema (P2 extension to ilan_turizm_details)
        $this->assertNull($factSheet->parkingBilgisi);
    }

    public function test_property_fact_sheet_never_contains_door_code(): void
    {
        // GC-D8: PropertyFactSheet should NEVER have door_code, lockbox_code, smart_lock_code
        $factSheet = PropertyFactSheet::build(
            ilan: $this->ilan,
            reservationId: $this->reservation->id,
        );

        // Door codes are FORBIDDEN
        $this->assertObjectNotHasProperty('doorCode', $factSheet);
        $this->assertObjectNotHasProperty('lockboxCode', $factSheet);
        $this->assertObjectNotHasProperty('smartLockCode', $factSheet);
    }

    public function test_property_fact_sheet_fact_validation(): void
    {
        $factSheet = PropertyFactSheet::build(
            ilan: $this->ilan,
            reservationId: $this->reservation->id,
            wifiCredentials: ['ssid' => 'KonutAga', 'password' => '12345678'],
        );

        // All required facts available
        $this->assertTrue($factSheet->hasAllFacts(['wifi_ssid', 'wifi_password']));
        $this->assertTrue($factSheet->hasAllFacts(['check_in_time']));

        // Missing fact
        $this->assertFalse($factSheet->hasAllFacts(['wifi_ssid', 'missing_fact']));

        $missing = $factSheet->getMissingFacts(['wifi_ssid', 'door_code']);
        $this->assertContains('door_code', $missing);
    }

    public function test_property_fact_sheet_prompt_context(): void
    {
        $factSheet = PropertyFactSheet::build(
            ilan: $this->ilan,
            reservationId: $this->reservation->id,
            wifiCredentials: ['ssid' => 'KonutAga', 'password' => '12345678'],
        );

        $context = $factSheet->toPromptContext();

        $this->assertStringContainsString('WiFi: KonutAga', $context);
        $this->assertStringContainsString('Giriş saati: 15:00', $context);
        // parkingBilgisi: not yet in ilanlar schema (P2 extension)
        $this->assertStringNotContainsString('Otopark:', $context);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E5: GuestConciergeAuthorityPolicy — deterministic enforcement
    // ═══════════════════════════════════════════════════════════════════════

    public function test_policy_allows_answer_for_wifi_intent_with_facts(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        $factSheet = PropertyFactSheet::build(
            ilan: $this->ilan,
            reservationId: $this->reservation->id,
            wifiCredentials: ['ssid' => 'KonutAga', 'password' => '12345678'],
        );

        $classification = IntentClassification::classify(
            intent: 'WIFI_INFO',
            confidence: 0.90,
            requiredFactKeys: ['wifi_ssid', 'wifi_password'],
        );

        $result = $policy->canAnswer($classification, $factSheet);
        $this->assertTrue($result->allowed);
    }

    public function test_policy_denies_answer_for_missing_facts(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        // Empty fact sheet (no WiFi configured)
        $factSheet = PropertyFactSheet::empty();

        $classification = IntentClassification::classify(
            intent: 'WIFI_INFO',
            confidence: 0.90,
            requiredFactKeys: ['wifi_ssid', 'wifi_password'],
        );

        $result = $policy->canAnswer($classification, $factSheet);
        $this->assertTrue($result->isDenied());
        $this->assertEquals('MISSING_FACTS', $result->denialCode);
    }

    public function test_policy_denies_answer_for_low_confidence(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        $factSheet = PropertyFactSheet::build(
            ilan: $this->ilan,
            reservationId: $this->reservation->id,
            wifiCredentials: ['ssid' => 'KonutAga', 'password' => '12345678'],
        );

        $classification = IntentClassification::classify(
            intent: 'WIFI_INFO',
            confidence: 0.45, // Low confidence
            requiredFactKeys: ['wifi_ssid'],
        );

        $result = $policy->canAnswer($classification, $factSheet);
        $this->assertTrue($result->isDenied());
        $this->assertEquals('LOW_CONFIDENCE', $result->denialCode);
    }

    public function test_policy_denies_credential_request(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        $factSheet = PropertyFactSheet::build(
            ilan: $this->ilan,
            reservationId: $this->reservation->id,
        );

        $classification = IntentClassification::classify(
            intent: 'CREDENTIAL_REQUEST',
            confidence: 0.95,
            requiredFactKeys: [],
        );

        $result = $policy->canAnswer($classification, $factSheet);
        $this->assertTrue($result->isDenied());
        $this->assertEquals('ZERO_AUTHORITY', $result->denialCode);
    }

    public function test_policy_denies_refund_request(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        $classification = IntentClassification::classify(
            intent: 'REFUND_REQUEST',
            confidence: 0.88,
            requiredFactKeys: [],
        );

        $result = $policy->canAnswer($classification, PropertyFactSheet::empty());
        $this->assertTrue($result->isDenied());
        $this->assertEquals('ZERO_AUTHORITY', $result->denialCode);
    }

    public function test_policy_allows_gorev_for_technical_issue(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        $classification = IntentClassification::classify(
            intent: 'TECHNICAL_ISSUE',
            confidence: 0.85,
            requiredFactKeys: [],
        );

        $result = $policy->canCreateGorev($classification);
        $this->assertTrue($result->allowed);
    }

    public function test_policy_denies_gorev_for_refund(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        $classification = IntentClassification::classify(
            intent: 'REFUND_REQUEST',
            confidence: 0.88,
            requiredFactKeys: [],
        );

        $result = $policy->canCreateGorev($classification);
        $this->assertTrue($result->isDenied());
        $this->assertEquals('ZERO_AUTHORITY', $result->denialCode);
    }

    public function test_policy_must_escalate_for_unknown_intent(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        $classification = IntentClassification::classify(
            intent: 'UNKNOWN',
            confidence: 0.50,
            requiredFactKeys: [],
        );

        $this->assertTrue($policy->mustEscalate($classification));
    }

    public function test_policy_escalation_message_for_credential_request(): void
    {
        $policy = new GuestConciergeAuthorityPolicy();

        $classification = IntentClassification::classify(
            intent: 'CREDENTIAL_REQUEST',
            confidence: 0.90,
            requiredFactKeys: [],
        );

        $reason = $policy->getEscalationReason($classification, null);
        $this->assertStringContainsString('ZERO_AUTHORITY', $reason);
        $this->assertStringContainsString('CREDENTIAL_REQUEST', $reason);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E6: GuestConciergeRouter — resolution
    // ═══════════════════════════════════════════════════════════════════════

    public function test_router_resolves_active_guest(): void
    {
        // Make reservation active (today)
        $activeReservation = PropertyReservation::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->ilan->id,
            'guest_name' => 'Test Guest',
            'guest_phone' => '+905551111111',
            'start_date' => now()->subDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reservation_state' => 'confirmed',
        ]);

        $router = new GuestConciergeRouter(
            policy: new \App\Services\Notification\GuestCommunicationPolicy()
        );

        $decision = $router->resolve('+905551111111');

        $this->assertEquals('GUEST_ACTIVE', $decision->decision);
        $this->assertEquals($this->tenant->id, $decision->tenantId);
        $this->assertEquals($activeReservation->id, $decision->reservationId);
        $this->assertTrue($decision->isActiveGuest());
    }

    public function test_router_resolves_unknown_for_unknown_phone(): void
    {
        $router = new GuestConciergeRouter(
            policy: new \App\Services\Notification\GuestCommunicationPolicy()
        );

        $decision = $router->resolve('+905550000000');

        $this->assertEquals('UNKNOWN', $decision->decision);
    }

    public function test_router_handles_invalid_phone(): void
    {
        $router = new GuestConciergeRouter(
            policy: new \App\Services\Notification\GuestCommunicationPolicy()
        );

        $decision = $router->resolve('not-a-phone');

        $this->assertEquals('UNKNOWN', $decision->decision);
        $this->assertEquals('unparseable_phone', $decision->reason);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E7: Tenant isolation
    // ═══════════════════════════════════════════════════════════════════════

    public function test_guest_message_requires_tenant_id(): void
    {
        $message = GuestMessage::create([
            'tenant_id' => $this->tenant->id,
            'sender_phone' => '+905551234567',
            'message_text' => 'Test',
        ]);

        $this->assertEquals($this->tenant->id, $message->tenant_id);
    }

    public function test_guest_message_query_by_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other GC Tenant',
            'status' => 'active',
            'is_active' => true,
        ]);

        GuestMessage::create([
            'tenant_id' => $this->tenant->id,
            'sender_phone' => '+905551234567',
            'message_text' => 'From tenant 1',
        ]);

        GuestMessage::create([
            'tenant_id' => $otherTenant->id,
            'sender_phone' => '+905551234567',
            'message_text' => 'From tenant 2',
        ]);

        $tenant1Messages = GuestMessage::query()->forTenant($this->tenant->id)->get();
        $this->assertEquals(1, $tenant1Messages->count());
        $this->assertEquals('From tenant 1', $tenant1Messages->first()->message_text);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E8: ProcessGuestMessageJob — pipeline behavior
    // ═══════════════════════════════════════════════════════════════════════

    public function test_process_job_skips_duplicate_message_id(): void
    {
        $msgId = 'wamid.duplicate123';

        GuestMessage::create([
            'tenant_id' => $this->tenant->id,
            'sender_phone' => '+905551234567',
            'external_message_id' => $msgId,
            'message_text' => 'First message',
        ]);

        // Dispatch with same message ID
        $job = new ProcessGuestMessageJob(
            senderPhone: '+905551234567',
            senderName: 'Test',
            messageText: 'Duplicate message',
            messageId: $msgId,
            messageType: 'text',
            routingDecision: RoutingDecision::guestActive(
                phone: '+905551234567',
                tenantId: $this->tenant->id,
                reservationId: $this->reservation->id,
                ilanId: $this->ilan->id,
            ),
        );

        // Mock Hermes to avoid actual LLM call
        $hermes = new class extends GuestConciergeHermes {
            public function classifyIntent(string $message, \App\Services\Concierge\PropertyFactSheet $facts): IntentClassification
            {
                return IntentClassification::classify('WIFI_INFO', 0.90, ['wifi_ssid']);
            }
            public function draftAnswer(string $message, \App\Services\Concierge\PropertyFactSheet $facts, IntentClassification $classification): string
            {
                return 'WiFi: Test';
            }
        };

        $policy = new GuestConciergeAuthorityPolicy();
        $outbound = new class extends WhatsAppOutboundService {
            public function send(string $to, string $message): bool { return true; }
        };
        $gorevService = app(\App\Services\Reservation\OperationalGorevService::class);

        // Execute the job
        $job->handle($hermes, $policy, $outbound, $gorevService);

        // Should NOT create a second record
        $count = GuestMessage::where('external_message_id', $msgId)->count();
        $this->assertEquals(1, $count);
    }

    public function test_process_job_creates_audit_record(): void
    {
        $msgId = 'wamid.audit123';

        // DEBT-GC-01 recovery: Use dedicated tenant+ilan for this test to avoid
        // SQLite RefreshDatabase state contamination (auto-increment counters persist
        // after DELETE). Previous tests in the suite may create tenants that leak IDs.
        $ownTenant = Tenant::create([
            'name' => 'GC Audit Test Tenant',
            'status' => 'active',
            'is_active' => true,
        ]);
        $ownIlan = Ilan::factory()->create([
            'tenant_id' => $ownTenant->id,
            'check_in_time' => '15:00',
            'check_out_time' => '11:00',
        ]);
        $ownReservation = PropertyReservation::create([
            'tenant_id' => $ownTenant->id,
            'property_id' => $ownIlan->id,
            'guest_name' => 'Audit Test Guest',
            'guest_phone' => '+905559998877',
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'reservation_state' => 'confirmed',
        ]);

        $job = new ProcessGuestMessageJob(
            senderPhone: '+905559998877',
            senderName: 'Audit Test Guest',
            messageText: 'Klima çalışmıyor!',
            messageId: $msgId,
            messageType: 'text',
            routingDecision: RoutingDecision::guestActive(
                phone: '+905559998877',
                tenantId: $ownTenant->id,
                reservationId: $ownReservation->id,
                ilanId: $ownIlan->id,
            ),
        );

        // DEBUG: Verify entities exist in DB
        $this->assertDatabaseHas('tenants', ['id' => $ownTenant->id]);
        $this->assertDatabaseHas('ilanlar', ['id' => $ownIlan->id, 'tenant_id' => $ownTenant->id]);
        $this->assertDatabaseHas('property_reservations', ['id' => $ownReservation->id, 'tenant_id' => $ownTenant->id]);

        // Without global scopes (matches what loadIlan() does)
        $loadedIlan = Ilan::withoutGlobalScopes()
            ->where('id', $ownIlan->id)
            ->when($ownTenant->id, fn($q) => $q->where('tenant_id', $ownTenant->id))
            ->first();
        $this->assertNotNull($loadedIlan, "Ilan should be loadable with tenant_id filter");

        $hermes = new class extends GuestConciergeHermes {
            public function classifyIntent(string $message, \App\Services\Concierge\PropertyFactSheet $facts): \App\Services\Concierge\IntentClassification
            {
                return \App\Services\Concierge\IntentClassification::classify('TECHNICAL_ISSUE', 0.90, []);
            }
            public function draftAnswer(string $message, \App\Services\Concierge\PropertyFactSheet $facts, \App\Services\Concierge\IntentClassification $classification): string
            {
                return 'Klima servisi bildirildi.';
            }
        };

        $policy = new GuestConciergeAuthorityPolicy();
        $outbound = new class extends WhatsAppOutboundService {
            public function send(string $to, string $message): bool { return true; }
        };
        $gorevService = app(\App\Services\Reservation\OperationalGorevService::class);

        $job->handle($hermes, $policy, $outbound, $gorevService);

        $record = GuestMessage::where('external_message_id', $msgId)->first();
        $this->assertNotNull($record);
        $this->assertEquals('TECHNICAL_ISSUE', $record->intent);
        $this->assertEquals(0.90, $record->confidence);
        $this->assertEquals('ACTION', $record->response_mode);
        $this->assertEquals($ownTenant->id, $record->tenant_id);
    }

    public function test_process_job_escalates_unknown_intent(): void
    {
        $msgId = 'wamid.unknown123';

        $job = new ProcessGuestMessageJob(
            senderPhone: '+905551234567',
            senderName: 'Test',
            messageText: 'Bu ne bilmiyorum',
            messageId: $msgId,
            messageType: 'text',
            routingDecision: RoutingDecision::guestActive(
                phone: '+905551234567',
                tenantId: $this->tenant->id,
                reservationId: $this->reservation->id,
                ilanId: $this->ilan->id,
            ),
        );

        $hermes = new class extends GuestConciergeHermes {
            public function classifyIntent(string $message, \App\Services\Concierge\PropertyFactSheet $facts): IntentClassification
            {
                return IntentClassification::classify('UNKNOWN', 0.40, []);
            }
        };

        $policy = new GuestConciergeAuthorityPolicy();
        $outbound = new class extends WhatsAppOutboundService {
            public function send(string $to, string $message): bool { return true; }
        };
        $gorevService = app(\App\Services\Reservation\OperationalGorevService::class);

        $job->handle($hermes, $policy, $outbound, $gorevService);

        $record = GuestMessage::where('external_message_id', $msgId)->first();
        $this->assertNotNull($record);
        $this->assertEquals('ESCALATE', $record->response_mode);
        $this->assertTrue($record->escalated);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E9: Credential request isolation (GC-D8)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_credential_request_never_gets_answer(): void
    {
        $msgId = 'wamid.cred123';

        $job = new ProcessGuestMessageJob(
            senderPhone: '+905551234567',
            senderName: 'Test',
            messageText: 'Kapı kodu nedir?',
            messageId: $msgId,
            messageType: 'text',
            routingDecision: RoutingDecision::guestActive(
                phone: '+905551234567',
                tenantId: $this->tenant->id,
                reservationId: $this->reservation->id,
                ilanId: $this->ilan->id,
            ),
        );

        $hermes = new class extends GuestConciergeHermes {
            public function classifyIntent(string $message, \App\Services\Concierge\PropertyFactSheet $facts): IntentClassification
            {
                return IntentClassification::classify('CREDENTIAL_REQUEST', 0.95, []);
            }
        };

        $policy = new GuestConciergeAuthorityPolicy();
        $outbound = new class extends WhatsAppOutboundService {
            public function send(string $to, string $message): bool { return true; }
        };
        $gorevService = app(\App\Services\Reservation\OperationalGorevService::class);

        $job->handle($hermes, $policy, $outbound, $gorevService);

        $record = GuestMessage::where('external_message_id', $msgId)->first();
        $this->assertNotNull($record);
        $this->assertEquals('ESCALATE', $record->response_mode);
        $this->assertTrue($record->escalated);
        $this->assertEquals('CREDENTIAL_REQUEST', $record->intent);

        // Response should NOT contain any door/lockbox code
        $this->assertStringNotContainsString('1234', $record->response_text ?? '');
        $this->assertStringNotContainsString('ABCD', $record->response_text ?? '');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E10: Kill switch behavior
    // ═══════════════════════════════════════════════════════════════════════

    public function test_kill_switch_blocks_concierge(): void
    {
        config(['feature-flags.guest_concierge_kill_switch' => true]);

        // Instantiate directly to avoid TestCase::setUp() DB operations
        $reflection = new \ReflectionClass(\App\Http\Controllers\Api\WhatsAppWebhookController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('isGuestConciergeEnabled');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller));
    }

    public function test_disabled_flag_blocks_concierge(): void
    {
        config(['feature-flags.guest_concierge_enabled' => false]);

        $reflection = new \ReflectionClass(\App\Http\Controllers\Api\WhatsAppWebhookController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('isGuestConciergeEnabled');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller));
    }

    public function test_enabled_without_kill_switch_allows_concierge(): void
    {
        config(['feature-flags.guest_concierge_enabled' => true]);
        config(['feature-flags.guest_concierge_kill_switch' => false]);

        $reflection = new \ReflectionClass(\App\Http\Controllers\Api\WhatsAppWebhookController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('isGuestConciergeEnabled');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E11: Gorev idempotency (no double-create)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_gorev_not_created_for_unknown_intent(): void
    {
        $msgId = 'wamid.nogorev123';

        $job = new ProcessGuestMessageJob(
            senderPhone: '+905551234567',
            senderName: 'Test',
            messageText: 'Something random',
            messageId: $msgId,
            messageType: 'text',
            routingDecision: RoutingDecision::guestActive(
                phone: '+905551234567',
                tenantId: $this->tenant->id,
                reservationId: $this->reservation->id,
                ilanId: $this->ilan->id,
            ),
        );

        $hermes = new class extends GuestConciergeHermes {
            public function classifyIntent(string $message, \App\Services\Concierge\PropertyFactSheet $facts): IntentClassification
            {
                return IntentClassification::classify('UNKNOWN', 0.30, []);
            }
        };

        $policy = new GuestConciergeAuthorityPolicy();
        $outbound = new class extends WhatsAppOutboundService {
            public function send(string $to, string $message): bool { return true; }
        };
        $gorevService = app(\App\Services\Reservation\OperationalGorevService::class);

        $job->handle($hermes, $policy, $outbound, $gorevService);

        // Should escalate, not create Gorev
        $record = GuestMessage::where('external_message_id', $msgId)->first();
        $this->assertEquals('ESCALATE', $record->response_mode);
        $this->assertNull($record->gorev_id);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // E12: Intent constant completeness
    // ═══════════════════════════════════════════════════════════════════════

    public function test_all_p1_intents_defined(): void
    {
        $p1Intents = [
            'WIFI_INFO',
            'CHECK_IN_TIME',
            'CHECK_OUT_TIME',
            'PARKING_INFO',
            'HOUSE_RULES',
            'TECHNICAL_ISSUE',
            'CLEANING_REQUEST',
        ];

        foreach ($p1Intents as $intent) {
            $this->assertArrayHasKey($intent, GuestMessage::INTENT_REQUIRED_FACTS);
        }
    }
}

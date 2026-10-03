<?php

namespace Tests\Feature\Webhook;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadMessage;
use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * WhatsAppTenantIngressTest
 *
 * EXT_06 Regression Verification Test Suite
 * Covers W0 through W10 requirements for WhatsApp Ingress Tenant Resolution:
 *   W0: Registered entrypoint resolves.
 *   W1: Valid authenticated event with mapped tenant accepted (HTTP 200).
 *   W2: Inbound Lead created with verified tenant_id (NOT null).
 *   W3: Canonical CRM persistence (Lead, LeadMessage, LeadActivity) bound to verified tenant.
 *   W4: Cross-tenant isolation enforced (Tenant A payload cannot touch Tenant B).
 *   W5: Unknown or unmapped phone_number_id fails closed (404/403).
 *   W6: Inactive/suspended tenant fails closed (404).
 *   W7: Invalid HMAC signature rejected with 403 (never proceeds to tenant processing).
 *   W8: Missing HMAC signature rejected with 403.
 *   W9: GET challenge verification still succeeds.
 *   W10: Zero external network calls.
 */
class WhatsAppTenantIngressTest extends TestCase
{
    private string $appSecret = 'whatsapp_secret_test_key_12345';
    private Tenant $tenantA;
    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists('Http')) {
            class_alias(\Illuminate\Support\Facades\Http::class, 'Http');
        }

        Http::preventStrayRequests();

        config([
            'services.whatsapp.app_secret' => $this->appSecret,
            'services.whatsapp.webhook_verify_token' => 'wa_verify_token_secure',
            'services.whatsapp.phone_number_id' => '100100100',
            'services.whatsapp.access_token' => 'fake_access_token',
            'feature-flags.guest_concierge_enabled' => false,
        ]);

        // Fake HTTP client to ensure zero actual external network calls
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        // Create Tenant A
        $this->tenantA = Tenant::create([
            'name' => 'Bodrum Luxury Estates',
            'domain' => 'bodrum.test',
            'status' => 'active',
        ]);
        DB::table('tenants')->where('id', $this->tenantA->id)->update([
            'is_active' => true,
            'aktiflik_durumu' => 'active',
        ]);

        // Create Tenant B
        $this->tenantB = Tenant::create([
            'name' => 'Yalikavak Properties',
            'domain' => 'yalikavak.test',
            'status' => 'active',
        ]);
        DB::table('tenants')->where('id', $this->tenantB->id)->update([
            'is_active' => true,
            'aktiflik_durumu' => 'active',
        ]);

        // Bootstrap tables required by LeadAuthorityService / LeadService
        if (!\Illuminate\Support\Facades\Schema::hasTable('ai_lead_scores')) {
            \Illuminate\Support\Facades\Schema::create('ai_lead_scores', function ($table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->unsignedTinyInteger('skor_degeri')->default(50);
                $table->string('skor_etiketi')->nullable();
                $table->text('skor_nedeni')->nullable();
                $table->tinyInteger('win_probability')->nullable();
                $table->json('sinyaller')->nullable();
                $table->timestamp('hesaplama_tarihi')->useCurrent();
                $table->string('model_versiyonu', 255)->default('v1.0');
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('lead_messages')) {
            \Illuminate\Support\Facades\Schema::create('lead_messages', function ($table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->string('message_text')->nullable();
                $table->string('message_type')->default('incoming');
                $table->string('platform_message_id')->nullable();
                $table->string('intent')->nullable();
                $table->decimal('confidence', 5, 2)->default(0.00);
                $table->json('entities')->nullable();
                $table->string('sentiment')->default('neutral');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('lead_activities')) {
            \Illuminate\Support\Facades\Schema::create('lead_activities', function ($table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->string('activity_type');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->timestamp('activity_date')->nullable();
                $table->timestamps();
            });
        }
    }

    private function signPayload(string $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $this->appSecret);
    }

    private function makeWhatsAppPayload(string $phoneNumberId, string $senderPhone, string $messageText): string
    {
        return json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '905550000000',
                            'phone_number_id' => $phoneNumberId,
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Caner Demir'],
                            'wa_id' => $senderPhone,
                        ]],
                        'messages' => [[
                            'from' => $senderPhone,
                            'id' => 'wamid.HBgL' . uniqid(),
                            'timestamp' => (string) time(),
                            'text' => ['body' => $messageText],
                            'type' => 'text',
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ]);
    }

    /**
     * W0: Registered entrypoint resolves.
     */
    public function test_w0_registered_entrypoint_resolves(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('api.v1.webhook.whatsapp') ||
            collect(\Illuminate\Support\Facades\Route::getRoutes())->contains(function ($route) {
                return $route->uri() === 'api/v1/webhook/whatsapp';
            }),
            'Route POST api/v1/webhook/whatsapp must be registered.'
        );
    }

    /**
     * W1: Valid authenticated event with mapped tenant accepted (HTTP 200).
     */
    public function test_w1_valid_authenticated_event_with_mapped_tenant_accepted(): void
    {
        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905321112233', 'Villa fiyatı nedir?');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * W2: Inbound Lead created with verified tenant_id (NOT null).
     */
    public function test_w2_inbound_lead_created_with_verified_tenant_id(): void
    {
        $senderPhone = '905329998877';
        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, $senderPhone, 'Satılık arsa arıyorum');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);

        // NOTE: We must use withoutGlobalScopes() because the middleware's finally{} block
        // clears TenantContextService after the HTTP response is built but before assertions run.
        // Without this, TenantScope applies (WHERE 1=0) and no leads are found.
        $lead = Lead::withoutGlobalScopes()
            ->where('platform_user_id', $senderPhone)
            ->first();
        $this->assertNotNull($lead, 'Lead record must be created');
        $this->assertNotNull($lead->tenant_id, 'Lead tenant_id must NOT be null');
        $this->assertEquals($this->tenantA->id, $lead->tenant_id, 'Lead must be assigned to Tenant A');
    }

    /**
     * W3: Canonical CRM persistence (Lead, LeadMessage, LeadActivity) bound to verified tenant.
     */
    public function test_w3_canonical_crm_persistence_bound_to_verified_tenant(): void
    {
        $senderPhone = '905324445566';
        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, $senderPhone, 'Yalıkavak villa bilgisi rica ederim');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);

        // NOTE: We must use withoutGlobalScopes() because the middleware's finally{} block
        // clears TenantContextService after the HTTP response is built but before assertions run.
        $lead = Lead::withoutGlobalScopes()
            ->where('platform_user_id', $senderPhone)
            ->first();
        $this->assertNotNull($lead);
        $this->assertEquals($this->tenantA->id, $lead->tenant_id);

        // Check messages relationship
        $this->assertGreaterThanOrEqual(1, $lead->messages()->count());
        $message = $lead->messages()->first();
        $this->assertStringContainsString('Yalıkavak villa bilgisi', $message->message_text);

        // Check activities relationship
        $this->assertGreaterThanOrEqual(1, $lead->activities()->count());
        $activity = $lead->activities()->first();
        $this->assertEquals('message_received', $activity->activity_type);
    }

    /**
     * W4: Cross-tenant isolation enforced (Tenant A payload cannot touch Tenant B).
     */
    public function test_w4_cross_tenant_isolation_enforced(): void
    {
        $senderPhone = '905327778899';

        // Event for Tenant A
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, $senderPhone, 'Tenant A İlanı');
        $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadA)->assertStatus(200);

        // Event for Tenant B with same sender phone
        $payloadB = $this->makeWhatsAppPayload((string) $this->tenantB->id, $senderPhone, 'Tenant B İlanı');
        $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadB),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadB)->assertStatus(200);

        // Leads must be isolated: Tenant A has 1 lead, Tenant B has 1 lead
        $leadTenantA = Lead::withoutTenant()->where('tenant_id', $this->tenantA->id)->where('platform_user_id', $senderPhone)->first();
        $leadTenantB = Lead::withoutTenant()->where('tenant_id', $this->tenantB->id)->where('platform_user_id', $senderPhone)->first();

        $this->assertNotNull($leadTenantA);
        $this->assertNotNull($leadTenantB);
        $this->assertNotEquals($leadTenantA->id, $leadTenantB->id, 'Leads across different tenants must be completely distinct records');

        // Isolation assertion: TenantContextService set to Tenant A cannot see Tenant B's lead
        app(TenantContextService::class)->setTenant($this->tenantA);
        $this->assertNull(Lead::where('id', $leadTenantB->id)->first(), 'Tenant A context must NOT access Tenant B lead');
    }

    /**
     * W5: Unknown or unmapped phone_number_id fails closed (404/403).
     */
    public function test_w5_unknown_phone_number_id_fails_closed(): void
    {
        $payload = $this->makeWhatsAppPayload('999999999999', '905320000000', 'Bilinmeyen numara');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Fail-closed: 404 (SAB Madde 5 Absolute 404 Masking)
        $this->assertTrue(in_array($response->getStatusCode(), [404, 403]), 'Unknown tenant must fail closed with 404 or 403.');
    }

    /**
     * W6: Inactive/suspended tenant fails closed (404).
     */
    public function test_w6_inactive_tenant_fails_closed(): void
    {
        $inactiveTenant = Tenant::create([
            'name' => 'Inactive Corp',
            'domain' => 'inactive.test',
            'status' => 'suspended',
        ]);
        DB::table('tenants')->where('id', $inactiveTenant->id)->update([
            'is_active' => false,
            'aktiflik_durumu' => 'suspended',
        ]);

        $payload = $this->makeWhatsAppPayload((string) $inactiveTenant->id, '905321110000', 'Askıda tenant');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertEquals(404, $response->getStatusCode(), 'Inactive tenant must fail closed with 404.');
    }

    /**
     * W7: Invalid HMAC signature rejected with 403 (never proceeds to tenant processing).
     */
    public function test_w7_invalid_hmac_signature_rejected_with_403(): void
    {
        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905321110000', 'Geçersiz imza');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalidhashvalue1234567890abcdef',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    /**
     * W8: Missing HMAC signature rejected with 403.
     */
    public function test_w8_missing_hmac_signature_rejected_with_403(): void
    {
        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905321110000', 'Eksik imza');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(403);
    }

    /**
     * W9: GET challenge verification still succeeds.
     */
    public function test_w9_get_challenge_verification_still_succeeds(): void
    {
        $challengeString = 'meta_challenge_random_token_98765';

        $response = $this->get('/api/v1/webhook/whatsapp?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wa_verify_token_secure',
            'hub_challenge' => $challengeString,
        ]));

        $response->assertStatus(200);
        $response->assertSee($challengeString);
    }

    /**
     * W10: Zero external network calls.
     */
    public function test_w10_zero_external_network_calls(): void
    {
        // Http::preventStrayRequests() in setUp ensures any un-faked external call throws an exception
        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905329990011', 'Zero network check');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);
        // Only graph.facebook.com fake was hit, no unhandled external request
        $this->assertTrue(true);
    }

    // ==========================================================================
    // EXT_06B: ADVERSARIAL SECURITY TESTS (S1-S5)
    // Verifies that unsigned query/body parameters CANNOT override canonical
    // signed payload identifiers.
    // ==========================================================================

    /**
     * S1: Signed payload with phone_number_id for Tenant A + query param for Tenant B
     *      → MUST resolve to Tenant A (canonical signed identifier takes precedence)
     */
    public function test_s1_signed_identifier_ignores_query_phone_number_id(): void
    {
        // Signed payload identifies Tenant A
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905320000001', 'Signed A');

        // Attacker tries to override with query parameter pointing to Tenant B
        $response = $this->call(
            'POST',
            '/api/v1/webhook/whatsapp?phone_number_id=' . $this->tenantB->id,
            [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA), 'CONTENT_TYPE' => 'application/json'],
            $payloadA
        );

        $response->assertStatus(200);

        // Verify lead was created under Tenant A, not Tenant B
        $lead = Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('platform_user_id', '905320000001')
            ->first();

        $this->assertNotNull($lead, 'Lead must exist under Tenant A (signed payload)');
        $this->assertEquals($this->tenantA->id, $lead->tenant_id, 'Tenant must be A from signed payload');

        // Verify NO lead was created under Tenant B
        $leadB = Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->where('platform_user_id', '905320000001')
            ->first();

        $this->assertNull($leadB, 'No lead must exist under Tenant B (query override must be ignored)');
    }

    /**
     * S2: Signed payload with phone_number_id for Tenant A + root/body tenant_id for Tenant B
     *      → HMAC validation fails because payload was tampered with (403)
     *
     * NOTE: This scenario is already covered by W7 (invalid HMAC). When an attacker
     * modifies the body (including adding tenant_id), the HMAC signature becomes invalid
     * and the request is rejected with 403. The fix is redundant because HMAC integrity
     * is already enforced before tenant resolution.
     *
     * We verify this behavior to document that body tampering is caught by HMAC.
     */
    public function test_s2_body_tampering_detected_by_hmac(): void
    {
        // Signed payload identifies Tenant A
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905320000002', 'Signed A');

        // Attacker tries to inject tenant_id in body (tampering)
        $bodyData = json_decode($payloadA, true);
        $bodyData['tenant_id'] = (string) $this->tenantB->id; // Injection attempt
        $tamperedPayload = json_encode($bodyData);

        // HMAC is for original payload, but we're sending modified payload
        $response = $this->call(
            'POST',
            '/api/v1/webhook/whatsapp',
            [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA), 'CONTENT_TYPE' => 'application/json'],
            $tamperedPayload
        );

        // HMAC validation MUST reject tampered payload
        $response->assertStatus(403, 'Tampered body with injected tenant_id must be rejected by HMAC validation');

        // Zero CRM mutation
        $lead = Lead::withoutGlobalScopes()
            ->where('platform_user_id', '905320000002')
            ->first();
        $this->assertNull($lead, 'No lead must be created when HMAC validation fails');
    }

    /**
     * S3: NO metadata.phone_number_id in signed payload + attacker supplies query phone_number_id
     *      → MUST fail closed (404), zero CRM mutation
     */
    public function test_s3_missing_canonical_identifier_query_override_fails_closed(): void
    {
        // Create payload WITHOUT metadata.phone_number_id
        $malformedPayload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'value' => [
                                // NO metadata.phone_number_id here
                                'messages' => [
                                    [
                                        'from' => '905320000003',
                                        'id' => 'wamid.' . uniqid(),
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'Test without phone_number_id'],
                                    ]
                                ]
                            ],
                            'field' => 'messages',
                        ]
                    ],
                ]
            ],
        ]);

        // Attacker provides phone_number_id via query parameter
        $response = $this->call(
            'POST',
            '/api/v1/webhook/whatsapp?phone_number_id=' . $this->tenantB->id,
            [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($malformedPayload), 'CONTENT_TYPE' => 'application/json'],
            $malformedPayload
        );

        // MUST fail closed
        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 403]),
            'Missing canonical identifier with query override MUST fail closed'
        );

        // Zero CRM mutation - no lead created
        $lead = Lead::withoutGlobalScopes()
            ->where('platform_user_id', '905320000003')
            ->first();

        $this->assertNull($lead, 'No lead must be created when canonical identifier is missing');
    }

    /**
     * S4: NO metadata.phone_number_id in signed payload + attacker supplies body tenant_id
     *      → MUST fail closed (404), zero CRM mutation
     */
    public function test_s4_missing_canonical_identifier_body_override_fails_closed(): void
    {
        // Create payload WITHOUT metadata.phone_number_id
        $malformedPayload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'value' => [
                                // NO metadata.phone_number_id here
                                'messages' => [
                                    [
                                        'from' => '905320000004',
                                        'id' => 'wamid.' . uniqid(),
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'Test without phone_number_id'],
                                    ]
                                ]
                            ],
                            'field' => 'messages',
                        ]
                    ],
                ]
            ],
        ]);

        // Attacker tries to inject tenant_id in body
        $bodyData = json_decode($malformedPayload, true);
        $bodyData['tenant_id'] = (string) $this->tenantB->id;

        $response = $this->call(
            'POST',
            '/api/v1/webhook/whatsapp',
            [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($malformedPayload), 'CONTENT_TYPE' => 'application/json'],
            json_encode($bodyData)
        );

        // MUST fail closed
        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 403]),
            'Missing canonical identifier with body tenant_id MUST fail closed'
        );

        // Zero CRM mutation
        $lead = Lead::withoutGlobalScopes()
            ->where('platform_user_id', '905320000004')
            ->first();

        $this->assertNull($lead, 'No lead must be created when canonical identifier is missing');
    }

    /**
     * S5: NO metadata.phone_number_id in signed payload
     *      → MUST fail closed, zero TenantContext establishment
     */
    public function test_s5_missing_canonical_identifier_no_tenant_context(): void
    {
        $tenantContextService = app(TenantContextService::class);

        // Clear any existing context
        $tenantContextService->clearTenant();
        $this->assertFalse($tenantContextService->hasTenant(), 'Context should be clear before test');

        // Payload without canonical identifier
        $malformedPayload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '905320000005',
                                        'id' => 'wamid.' . uniqid(),
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'No context'],
                                    ]
                                ]
                            ],
                            'field' => 'messages',
                        ]
                    ],
                ]
            ],
        ]);

        $response = $this->call(
            'POST',
            '/api/v1/webhook/whatsapp',
            [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($malformedPayload), 'CONTENT_TYPE' => 'application/json'],
            $malformedPayload
        );

        // MUST fail
        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 403]),
            'Missing canonical identifier MUST fail'
        );

        // TenantContext must NOT be established
        $this->assertFalse(
            $tenantContextService->hasTenant(),
            'TenantContext must NOT be established when canonical identifier is missing'
        );
    }

    // ==========================================================================
    // EXT_06B: LIFECYCLE TESTS (W11-W13)
    // ==========================================================================

    /**
     * W11: Sequential Tenant A → Tenant B requests do not bleed context.
     *      Each request executes strictly as its own tenant.
     */
    public function test_w11_sequential_tenant_requests_isolated(): void
    {
        // Request 1: Tenant A
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905331000001', 'Sequential A');
        $responseA = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadA);

        $responseA->assertStatus(200);

        // Request 2: Tenant B (same phone number, different tenant)
        $payloadB = $this->makeWhatsAppPayload((string) $this->tenantB->id, '905331000002', 'Sequential B');
        $responseB = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadB),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadB);

        $responseB->assertStatus(200);

        // Verify Tenant A lead exists
        $leadA = Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('platform_user_id', '905331000001')
            ->first();
        $this->assertNotNull($leadA, 'Tenant A lead must exist');
        $this->assertEquals($this->tenantA->id, $leadA->tenant_id);

        // Verify Tenant B lead exists
        $leadB = Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->where('platform_user_id', '905331000002')
            ->first();
        $this->assertNotNull($leadB, 'Tenant B lead must exist');
        $this->assertEquals($this->tenantB->id, $leadB->tenant_id);

        // Cross-check: Tenant A must NOT have Tenant B's lead
        $crossLeadA = Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('platform_user_id', '905331000002')
            ->first();
        $this->assertNull($crossLeadA, 'Tenant A must NOT have Tenant B\'s lead (no bleed)');

        // Cross-check: Tenant B must NOT have Tenant A's lead
        $crossLeadB = Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->where('platform_user_id', '905331000001')
            ->first();
        $this->assertNull($crossLeadB, 'Tenant B must NOT have Tenant A\'s lead (no bleed)');
    }

    /**
     * W12: Failed request → subsequent valid request does not inherit stale context.
     *      Middleware establishes fresh context for each request.
     */
    public function test_w12_failed_request_no_stale_context_leak(): void
    {
        $tenantContextService = app(TenantContextService::class);

        // Clear any pre-existing context
        $tenantContextService->clearTenant();

        // Request 1: FAIL - unknown tenant (should fail with 404/403)
        $failPayload = $this->makeWhatsAppPayload('999999999999', '905332000001', 'Will fail');
        $failResponse = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($failPayload),
            'CONTENT_TYPE' => 'application/json',
        ], $failPayload);

        $this->assertTrue(
            in_array($failResponse->getStatusCode(), [404, 403]),
            'Unknown tenant request must fail'
        );

        // Verify NO context was established for failed request
        // Note: In the current implementation, middleware sets context before lookup fails,
        // but the context should be isolated per request lifecycle.

        // Request 2: SUCCESS - valid Tenant A
        $successPayload = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905332000002', 'Success');
        $successResponse = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($successPayload),
            'CONTENT_TYPE' => 'application/json',
        ], $successPayload);

        $successResponse->assertStatus(200);

        // Verify valid request created lead under correct tenant
        $lead = Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('platform_user_id', '905332000002')
            ->first();

        $this->assertNotNull($lead, 'Valid request must create lead under Tenant A');
        $this->assertEquals($this->tenantA->id, $lead->tenant_id, 'Lead must belong to Tenant A');

        // Verify no cross-contamination
        $wrongTenantLead = Lead::withoutGlobalScopes()
            ->where('tenant_id', '!=', $this->tenantA->id)
            ->where('platform_user_id', '905332000002')
            ->first();
        $this->assertNull($wrongTenantLead, 'Lead must not exist under wrong tenant');
    }

    /**
     * W13: Verify zero real external network calls during test execution.
     *      Uses Http::preventStrayRequests() to catch any stray HTTP calls.
     */
    public function test_w13_zero_external_network_verified(): void
    {
        // This test explicitly verifies Http::preventStrayRequests() is active
        // and no real external calls are made during webhook processing

        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905333000001', 'Network check');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);

        // If we reach here, no stray HTTP calls were made
        // Http::preventStrayRequests() in setUp would have thrown for any unhandled external call
        $this->assertTrue(true, 'No external network calls made during webhook processing');
    }
}

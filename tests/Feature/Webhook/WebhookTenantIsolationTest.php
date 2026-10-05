<?php

namespace Tests\Feature\Webhook;

use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * EXT_06C — Webhook Tenant İzolasyonu Test Suite
 *
 * Canonical Contract: WhatsApp tenant authority is ONLY the signed Meta payload's
 * metadata.phone_number_id. No query/body fallback exists.
 *
 * Tests L1-L5 lifecycle scenarios.
 */
class WebhookTenantIsolationTest extends TestCase
{
    private string $appSecret = 'whatsapp_secret_test_key_12345';
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected TenantContextService $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable SecurityMiddleware to avoid logger null issues in tests
        $this->withoutMiddleware(\App\Http\Middleware\SecurityMiddleware::class);

        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->tenantContext = app(TenantContextService::class);
        $this->tenantContext->clearTenant();

        config([
            'services.whatsapp.app_secret' => $this->appSecret,
            'services.whatsapp.webhook_verify_token' => 'wa_verify_token_secure',
            'services.whatsapp.phone_number_id' => '100100100',
            'services.whatsapp.access_token' => 'fake_access_token',
            'feature-flags.guest_concierge_enabled' => false,
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
                $table->string('platform')->default('whatsapp');
                $table->string('message_id')->nullable();
                $table->text('direction');
                $table->text('content');
                $table->string('media_url')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('lead_activities')) {
            \Illuminate\Support\Facades\Schema::create('lead_activities', function ($table) {
                $table->id();
                $table->unsignedBigInteger('lead_id');
                $table->string('activity_type');
                $table->json('payload')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

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

        Log::spy();
    }

    /**
     * Create WhatsApp payload - same as WhatsAppTenantIngressTest
     */
    protected function makeWhatsAppPayload(string $phoneNumberId, string $senderPhone, string $messageText): string
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
                            'profile' => ['name' => 'Test User'],
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

    protected function signPayload(string $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $this->appSecret);
    }

    /** @test */
    public function signed_payload_with_canonical_phone_number_id_resolves_tenant(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\SecurityMiddleware::class);

        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905321000001', 'Test message');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);
    }

    /** @test */
    public function missing_phone_number_id_rejects(): void
    {
        // Create payload without metadata.phone_number_id
        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '905550000000',
                            // NO phone_number_id
                        ],
                        'messages' => [],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ]);

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 403]),
            'Missing phone_number_id must fail closed'
        );
    }

    /** @test */
    public function unknown_phone_number_id_rejects(): void
    {
        $payload = $this->makeWhatsAppPayload('999999999', '905320000000', 'Unknown');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 403]),
            'Unknown phone_number_id must fail closed'
        );
    }

    /** @test */
    public function inactive_tenant_rejects(): void
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

        $payload = $this->makeWhatsAppPayload((string) $inactiveTenant->id, '905321000000', 'Inactive');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 403]),
            'Inactive tenant must fail closed'
        );
    }

    /** @test */
    public function query_parameter_alone_cannot_resolve_tenant(): void
    {
        // Payload without phone_number_id, but query has tenant_id
        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '905550000000',
                            // NO phone_number_id
                        ],
                        'messages' => [],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ]);

        $response = $this->call('POST', '/api/v1/webhook/whatsapp?tenant_id=' . $this->tenantA->id, [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 403]),
            'Query parameter alone must fail'
        );
    }

    /** @test */
    public function payload_tenant_id_alone_cannot_resolve_tenant(): void
    {
        // Payload with tenant_id in body but no phone_number_id
        $payload = json_encode([
            'tenant_id' => $this->tenantA->id, // Should be ignored
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '905550000000',
                            // NO phone_number_id
                        ],
                        'messages' => [],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ]);

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 403]),
            'Payload tenant_id alone must fail'
        );
    }

    /** @test */
    public function canonical_signed_identifier_wins_over_query(): void
    {
        // Canonical authority: signed payload phone_number_id always wins.
        // Attacker-supplied query/body parameters are ignored.
        // With finally{} cleanup, context is empty after each request.
        // We verify canonical routing by checking DB state (Lead existence).

        // Malicious request: ?tenant_id=A in query, but payload contains B's phone_number_id
        $payloadB = $this->makeWhatsAppPayload((string) $this->tenantB->id, '905329990001', 'Tenant B');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp?tenant_id=' . $this->tenantA->id, [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadB),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadB);

        $response->assertStatus(200);

        // After the request: context is EMPTY (finally{} cleanup)
        $this->assertFalse(
            $this->tenantContext->hasTenant(),
            'Context must be empty after request (finally{} cleanup)'
        );

        // Verify canonical routing: B's lead was created under B (not A)
        $leadB = \App\Models\Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->where('platform_user_id', '905329990001')
            ->first();
        $this->assertNotNull($leadB, 'Canonical tenant B resolved — lead must exist under B');

        // Verify no bleed to A (the query-parameter value)
        $crossLeadA = \App\Models\Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('platform_user_id', '905329990001')
            ->first();
        $this->assertNull($crossLeadA, 'Lead must NOT exist under query-param A');
    }

    /** @test */
    public function invalid_signature_rejected(): void
    {
        $payload = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905321000000', 'Test');

        $response = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid_signature',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function L1_a_success_then_b_success_no_bleed(): void
    {
        // L1: A success → B success → no A bleed
        //
        // With finally{} cleanup, context is empty after each request.
        // We verify tenant routing via DB state (Lead existence under correct tenant).

        // Step 1: A success
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905331000001', 'Tenant A');
        $responseA = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadA);
        $responseA->assertStatus(200);

        // Step 2: B success
        $payloadB = $this->makeWhatsAppPayload((string) $this->tenantB->id, '905331000002', 'Tenant B');
        $responseB = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadB),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadB);
        $responseB->assertStatus(200);

        // After B request: context is EMPTY (finally{} cleanup ran)
        $this->assertFalse(
            $this->tenantContext->hasTenant(),
            'After request, context must be empty (finally{} cleanup)'
        );

        // Cross-check: verify B's lead exists under B, not A
        $leadB = \App\Models\Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->where('platform_user_id', '905331000002')
            ->first();
        $this->assertNotNull($leadB, 'B\'s lead must exist under B');

        $crossLeadA = \App\Models\Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('platform_user_id', '905331000002')
            ->first();
        $this->assertNull($crossLeadA, 'B\'s lead must NOT bleed into A');
    }

    /** @test */
    public function L2_a_success_then_invalid_hmac_fail(): void
    {
        // L2: A success → invalid HMAC failure → no A context survives
        //
        // With finally{} cleanup, context is cleared after each request.
        // HMAC failures exit early (before setTenant), so context is empty.

        // Step 1: A success
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905332000001', 'Tenant A');
        $responseA = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadA);
        $responseA->assertStatus(200);

        // After A: context cleared (finally{})
        $this->assertFalse($this->tenantContext->hasTenant());

        // Step 2: B fails at HMAC check
        $payloadB = $this->makeWhatsAppPayload((string) $this->tenantB->id, '905332000002', 'Tenant B');
        $responseB = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
            'CONTENT_TYPE' => 'application/json',
        ], $payloadB);
        $responseB->assertStatus(403);

        // Context should be cleared after HMAC failure (entry clear + finally clear)
        $this->assertFalse($this->tenantContext->hasTenant());
    }

    /** @test */
    public function L3_a_success_then_unknown_tenant_fail(): void
    {
        // L3: A success → unknown tenant failure → no A context survives
        //
        // With finally{} cleanup, each request's context is independent.

        // Step 1: A success
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905333000001', 'Tenant A');
        $responseA = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadA);
        $responseA->assertStatus(200);

        // After A: context cleared (finally{})
        $this->assertFalse($this->tenantContext->hasTenant());

        // Step 2: Unknown phone_number_id
        $payloadB = $this->makeWhatsAppPayload('999999999', '905333000002', 'Unknown');
        $responseB = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadB),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadB);
        $this->assertTrue(in_array($responseB->getStatusCode(), [404, 403]));

        // Context should be cleared — unknown tenant never set
        $this->assertFalse($this->tenantContext->hasTenant());
    }

    /** @test */
    public function L4_a_success_then_failed_request_then_b_success_no_bleed(): void
    {
        // L4: A success → failed request → B success → B only
        //
        // Verifies that a failed request does not pollute the subsequent valid request.
        // With finally{} cleanup, each request's context is independent.

        // Step 1: A success
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905334000001', 'Tenant A');
        $responseA = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadA);
        $responseA->assertStatus(200);

        // After A: context cleared (finally{})
        $this->assertFalse($this->tenantContext->hasTenant());

        // Step 2: Failed request (unknown tenant)
        $failPayload = $this->makeWhatsAppPayload('999999999', '905334000002', 'Unknown');
        $failResponse = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($failPayload),
            'CONTENT_TYPE' => 'application/json',
        ], $failPayload);
        $this->assertTrue(in_array($failResponse->getStatusCode(), [404, 403]));

        // After failed: context still empty
        $this->assertFalse($this->tenantContext->hasTenant());

        // Step 3: B success — must process correctly without A bleed
        $payloadB = $this->makeWhatsAppPayload((string) $this->tenantB->id, '905334000003', 'Tenant B');
        $responseB = $this->call('POST', '/api/v1/webhook/whatsapp', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadB),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadB);
        $responseB->assertStatus(200);

        // After B: context cleared (finally{})
        $this->assertFalse($this->tenantContext->hasTenant());

        // Verify B's lead exists under B (no bleed to A)
        $leadB = \App\Models\Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantB->id)
            ->where('platform_user_id', '905334000003')
            ->first();
        $this->assertNotNull($leadB, 'B\'s lead must exist under B');

        $crossLeadA = \App\Models\Lead::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantA->id)
            ->where('platform_user_id', '905334000003')
            ->first();
        $this->assertNull($crossLeadA, 'B\'s lead must NOT bleed into A');
    }

    /** @test */
    public function L5_downstream_throws_context_cleared(): void
    {
        // L5: Tenant A established → downstream $next() throws → context cleared after middleware exits
        //
        // Architecture:
        //   VerifyWebhookTenant::handle() → clearTenant() (entry)
        //   → HMAC OK
        //   → phone_number_id resolved → setTenant(A)
        //   → $next($request) [controller throws RuntimeException]
        //   → catch block logs + abort(404)
        //   → finally { clearTenant(); } ← MUST execute even when catch rethrows
        //   → AFTER middleware boundary: context is EMPTY
        //
        // Approach: Direct middleware unit-style test using a Closure mock for $next().
        // The Closure simulates a downstream that throws, letting us verify the
        // finally{} cleanup runs regardless of the catch{} block.

        $this->tenantContext->clearTenant();
        $this->assertFalse($this->tenantContext->hasTenant(), 'Context must start clean');

        // Build a minimal HTTP request with valid HMAC for Tenant A
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905335000001', 'Tenant A');
        $request = \Illuminate\Http\Request::create('/test', 'POST', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadA);

        // Mock $next: simulates a downstream controller throwing an exception
        $nextThrows = false;
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled, &$nextThrows) {
            $nextCalled = true;
            $nextThrows = true;
            throw new \RuntimeException('Simulated downstream failure for L5');
        };

        // Instantiate middleware directly (not via HTTP routing)
        $middleware = $this->app->make(\App\Http\Middleware\VerifyWebhookTenant::class);

        // Expect abort(404) to be called by the catch block
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        try {
            $middleware->handle($request, $next);
            $this->fail('Middleware should have aborted with 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // This is the expected abort(404) from the catch block
            $this->assertEquals(404, $e->getStatusCode());
            // Verify $next was actually called (the downstream DID throw)
            $this->assertTrue($nextCalled, '$next() must have been called by the middleware');
            // Now verify: after the middleware exited, context is CLEARED
            // This proves the finally{} block executed
            $this->assertFalse(
                $this->tenantContext->hasTenant(),
                'L5 FAIL: Tenant context NOT cleared after downstream threw and middleware exited. ' .
                'The finally{} block did not execute or did not clear context.'
            );
            throw $e; // Re-throw to satisfy expectException
        }
    }

    /** @test */
    public function L6_downstream_succeeds_context_cleared(): void
    {
        // L6: Tenant A established → downstream $next() succeeds → context cleared after middleware returns
        //
        // Architecture:
        //   VerifyWebhookTenant::handle() → clearTenant() (entry)
        //   → HMAC OK
        //   → phone_number_id resolved → setTenant(A)
        //   → $next($request) [returns response]
        //   → finally { clearTenant(); } ← MUST execute after normal return
        //   → AFTER middleware boundary: context is EMPTY
        //
        // Approach: Direct middleware unit-style test with a Closure mock for $next().
        // The Closure simulates successful downstream, letting us verify the
        // finally{} cleanup runs on the normal return path.

        $this->tenantContext->clearTenant();
        $this->assertFalse($this->tenantContext->hasTenant(), 'Context must start clean');

        // Build a minimal HTTP request with valid HMAC for Tenant A
        $payloadA = $this->makeWhatsAppPayload((string) $this->tenantA->id, '905336000001', 'Tenant A');
        $request = \Illuminate\Http\Request::create('/test', 'POST', [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signPayload($payloadA),
            'CONTENT_TYPE' => 'application/json',
        ], $payloadA);

        // Mock $next: simulates a downstream controller returning successfully
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response()->json(['status' => 'ok']);
        };

        // Instantiate middleware directly
        $middleware = $this->app->make(\App\Http\Middleware\VerifyWebhookTenant::class);

        // Call handle() directly with the mock $next
        $response = $middleware->handle($request, $next);

        // Verify $next was called
        $this->assertTrue($nextCalled, '$next() must have been called by the middleware');
        $this->assertEquals(200, $response->getStatusCode());

        // CRITICAL ASSERTION (L6): Context is EMPTY after middleware returns.
        // This proves the finally{} block executed on the normal completion path.
        $this->assertFalse(
            $this->tenantContext->hasTenant(),
            'L6 FAIL: Tenant context NOT cleared after downstream succeeded and middleware returned. ' .
            'The finally{} block did not execute or did not clear context.'
        );
    }

    protected function tearDown(): void
    {
        $this->tenantContext->clearTenant();
        \Mockery::close();
        parent::tearDown();
    }
}

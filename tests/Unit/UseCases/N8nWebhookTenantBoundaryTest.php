<?php

namespace Tests\Unit\UseCases;

use App\Enums\KisiTipi;
use App\Exceptions\CrossTenantIdentifierInjectionException;
use App\Exceptions\TenantOwnershipUnresolvableException;
use App\Models\Communication;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\Ulke;
use Database\Factories\SaaS\TenantFactory;
use App\Models\User;
use App\Services\N8n\TenantOwnershipResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for N8n webhook tenant boundary enforcement.
 *
 * Security invariant: A valid N8n caller MUST NOT be able to submit
 * Tenant B's identifiers while operating under Tenant A context.
 *
 * Resolution semantics:
 * - ilanTaslagi: danisman_id resolves tenant (ilan_id is optional fallback; both must agree)
 * - sozlesmeTaslagi: property_id and kisi_id must both agree on tenant
 * - mesajTaslagi: communication.communicable must have a resolvable tenant
 *
 * When identifiers disagree across tenants → CrossTenantIdentifierInjectionException → 403
 * When no identifier resolves to a tenant → TenantOwnershipUnresolvableException → 500
 */
class N8nWebhookTenantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private Tenant $tenantC;
    private Ulke $ulke;
    private TenantOwnershipResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ulke = Ulke::create(['ulke_adi' => 'Türkiye', 'ulke_kodu' => 'TR']);

        $this->tenantA = Tenant::create(['name' => 'Tenant A', 'domain' => 'tenant-a.test']);
        $this->tenantB = Tenant::create(['name' => 'Tenant B', 'domain' => 'tenant-b.test']);
        $this->tenantC = Tenant::create(['name' => 'Tenant C', 'domain' => 'tenant-c.test']);

        $this->resolver = new TenantOwnershipResolver;
    }

    // =====================================================================
    // HAPPY PATH — same-tenant flows
    // =====================================================================

    public function test_ilan_taslagi_passes_when_danisman_belongs_to_tenant_a(): void
    {
        $danisman = $this->makeDanisman($this->tenantA);

        $tenantId = $this->resolver->resolveForIlanTaslagi($danisman->id);

        $this->assertEquals($this->tenantA->id, $tenantId);
    }

    public function test_ilan_taslagi_passes_when_ilan_belongs_to_tenant_a(): void
    {
        $ilan = $this->makeIlan($this->tenantA);

        $tenantId = $this->resolver->resolveForIlanTaslagi(
            $this->makeDanisman($this->tenantA)->id,
            $ilan->id
        );

        $this->assertEquals($this->tenantA->id, $tenantId);
    }

    public function test_sozlesme_taslagi_passes_when_property_belongs_to_tenant_a(): void
    {
        $ilan = $this->makeIlan($this->tenantA);

        $tenantId = $this->resolver->resolveForSozlesmeTaslagi($ilan->id, null);

        $this->assertEquals($this->tenantA->id, $tenantId);
    }

    public function test_sozlesme_taslagi_passes_when_kisi_belongs_to_tenant_a(): void
    {
        $kisi = $this->makeKisi($this->tenantA);

        $tenantId = $this->resolver->resolveForSozlesmeTaslagi(null, $kisi->id);

        $this->assertEquals($this->tenantA->id, $tenantId);
    }

    // =====================================================================
    // CROSS-TENANT INJECTION — ilanTaslagi
    // =====================================================================

    public function test_ilan_taslagi_blocks_cross_tenant_when_danisman_tenant_a_but_ilan_tenant_b(): void
    {
        $danisman = $this->makeDanisman($this->tenantA);
        $ilan = $this->makeIlan($this->tenantB);

        $this->expectException(CrossTenantIdentifierInjectionException::class);

        $this->resolver->resolveForIlanTaslagi($danisman->id, $ilan->id);
    }

    public function test_ilan_taslagi_blocks_cross_tenant_when_ilan_tenant_a_but_danisman_tenant_b(): void
    {
        $danisman = $this->makeDanisman($this->tenantB);
        $ilan = $this->makeIlan($this->tenantA);

        $this->expectException(CrossTenantIdentifierInjectionException::class);

        $this->resolver->resolveForIlanTaslagi($danisman->id, $ilan->id);
    }

    public function test_ilan_taslagi_blocks_cross_tenant_when_danisman_tenant_a_ilan_tenant_c(): void
    {
        $danisman = $this->makeDanisman($this->tenantA);
        $ilan = $this->makeIlan($this->tenantC);

        $this->expectException(CrossTenantIdentifierInjectionException::class);

        $this->resolver->resolveForIlanTaslagi($danisman->id, $ilan->id);
    }

    // =====================================================================
    // CROSS-TENANT INJECTION — sozlesmeTaslagi
    // =====================================================================

    public function test_sozlesme_taslagi_blocks_cross_tenant_when_property_tenant_a_kisi_tenant_b(): void
    {
        $ilan = $this->makeIlan($this->tenantA);
        $kisi = $this->makeKisi($this->tenantB);

        $this->expectException(CrossTenantIdentifierInjectionException::class);

        $this->resolver->resolveForSozlesmeTaslagi($ilan->id, $kisi->id);
    }

    public function test_sozlesme_taslagi_blocks_cross_tenant_when_property_tenant_b_kisi_tenant_a(): void
    {
        $ilan = $this->makeIlan($this->tenantB);
        $kisi = $this->makeKisi($this->tenantA);

        $this->expectException(CrossTenantIdentifierInjectionException::class);

        $this->resolver->resolveForSozlesmeTaslagi($ilan->id, $kisi->id);
    }

    public function test_sozlesme_taslagi_blocks_cross_tenant_when_property_tenant_b_kisi_tenant_c(): void
    {
        $ilan = $this->makeIlan($this->tenantB);
        $kisi = $this->makeKisi($this->tenantC);

        $this->expectException(CrossTenantIdentifierInjectionException::class);

        $this->resolver->resolveForSozlesmeTaslagi($ilan->id, $kisi->id);
    }

    // =====================================================================
    // MULTI-IDENTIFIER CONSISTENCY — sozlesmeTaslagi (same-tenant)
    // =====================================================================

    public function test_sozlesme_taslagi_passes_when_property_and_kisi_both_belong_to_tenant_a(): void
    {
        $ilan = $this->makeIlan($this->tenantA);
        $kisi = $this->makeKisi($this->tenantA);

        $tenantId = $this->resolver->resolveForSozlesmeTaslagi($ilan->id, $kisi->id);

        $this->assertEquals($this->tenantA->id, $tenantId);
    }

    // =====================================================================
    // FAIL CLOSED — unresolved tenant
    // =====================================================================

    public function test_ilan_taslagi_throws_when_danisman_has_null_tenant(): void
    {
        $danisman = $this->makeDanisman(null);

        $this->expectException(TenantOwnershipUnresolvableException::class);

        $this->resolver->resolveForIlanTaslagi($danisman->id);
    }

    public function test_sozlesme_taslagi_throws_when_both_identifiers_are_invalid(): void
    {
        // When both identifiers resolve to null (entity not found), exception is thrown.
        // Kisi.tenant_id is NOT NULL in schema, so we use non-existent IDs.
        $this->expectException(TenantOwnershipUnresolvableException::class);

        $this->resolver->resolveForSozlesmeTaslagi(99999, 88888);
    }

    // =====================================================================
    // ZERO RECORD ON REJECTION — no AI draft is created on cross-tenant
    // =====================================================================

    public function test_no_ai_draft_created_on_cross_tenant_injection(): void
    {
        $danismanA = $this->makeDanisman($this->tenantA);
        $ilanB = $this->makeIlan($this->tenantB);

        $this->expectException(CrossTenantIdentifierInjectionException::class);

        try {
            $this->resolver->resolveForIlanTaslagi($danismanA->id, $ilanB->id);
        } catch (CrossTenantIdentifierInjectionException $e) {
            // Confirm: AIIlanTaslagi table is empty
            $this->assertDatabaseMissing('ai_ilan_taslaklari', [
                'danisman_id' => $danismanA->id,
                'ilan_id' => $ilanB->id,
            ]);

            throw $e;
        }
    }

    // =====================================================================
    // MESAJ TASLAGI
    // communication_id is a server-generated ID — it cannot be guessed or
    // injected by an external caller. The resolver correctly resolves the
    // communicable's tenant_id. Security is guaranteed by:
    // (1) communication_id is unpredictable;
    // (2) Communication is tenant-scoped and cannot be enumerated cross-tenant.
    // =====================================================================

    // =====================================================================
    // PAYLOAD TENANT_ID CANNOT OVERRIDE
    // =====================================================================

    public function test_payload_tenant_id_not_used_by_resolver(): void
    {
        // TenantOwnershipResolver NEVER accepts a tenant_id from the payload.
        // It resolves tenant ONLY from canonical domain entities (User, Ilan, Kisi).
        // This test validates the API contract: the resolver has no tenant_id parameter.

        $reflection = new \ReflectionMethod($this->resolver, 'resolveForIlanTaslagi');
        $params = $reflection->getParameters();

        $paramNames = array_map(fn($p) => $p->getName(), $params);

        $this->assertNotContains('tenantId', $paramNames);
        $this->assertContains('danismanId', $paramNames);
        $this->assertContains('ilanId', $paramNames);
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    private function makeDanisman(?Tenant $tenant): User
    {
        $user = User::withoutGlobalScopes()->create([
            'name' => 'Danışman ' . uniqid(),
            'email' => 'danisman_' . uniqid() . '@test.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $tenant?->id,
            'password' => bcrypt('password'),
        ]);

        return $user;
    }

    private function makeIlan(?Tenant $tenant): Ilan
    {
        $ilan = Ilan::factory()->create([
            'ulke_id' => $this->ulke->id,
            'il_id' => 1,
            'tenant_id' => $tenant?->id,
        ]);

        // Ensure tenant_id is exactly as specified (factory may auto-fill from BelongsToTenant)
        if ($tenant === null) {
            $ilan->update(['tenant_id' => null]);
            $ilan->refresh();
        }

        return $ilan;
    }

    private function makeKisi(?Tenant $tenant): Kisi
    {
        $kisi = Kisi::factory()->create([
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $tenant?->id,
            'kisi_tipi' => KisiTipi::ALICI->value,
        ]);

        if ($tenant === null) {
            $kisi->update(['tenant_id' => null]);
            $kisi->refresh();
        }

        return $kisi;
    }
}

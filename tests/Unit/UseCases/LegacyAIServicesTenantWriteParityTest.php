<?php

namespace Tests\Unit\UseCases;

use App\Enums\TaslakDurumu;
use App\Exceptions\TenantOwnershipUnresolvableException;
use App\Exceptions\CountryOwnershipUnresolvableException;
use App\Exceptions\CrossTenantIdentifierInjectionException;
use App\Models\AI\AIContractDraft;
use App\Models\AI\AIIlanTaslagi;
use App\Models\AI\AIMessage;
use App\Models\Communication;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\Ulke;
use App\Models\User;
use App\Services\AI\AIContractService;
use App\Services\AI\AIIlanTaslagiService;
use App\Services\AI\AIMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression tests for LEGACY_AI_SERVICES_TENANT_PARITY_01.
 *
 * Verifies that all three legacy AI services write records with correct:
 *   - tenant_id (canonical resolution via TenantOwnershipResolver)
 *   - ulke_id  (canonical resolution via CountryOwnershipResolver)
 *
 * These tests are Phase 3 write-parity tests.
 * They complement Phase 2 read-isolation tests (AIModelsTenantReadIsolationTest).
 *
 * Phase 2 files must NOT be modified during Phase 3.
 */
class LegacyAIServicesTenantWriteParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset TenantContextService singleton — prevents state leaking between tests
        // when BelongsToTenant trait auto-assigns tenant_id from singleton's stale state
        $tenantService = app(\App\Services\SaaS\TenantContextService::class);
        $reflection = new \ReflectionClass($tenantService);
        $prop = $reflection->getProperty('currentTenant');
        $prop->setAccessible(true);
        $prop->setValue($tenantService, null);

        $this->ulke = Ulke::create(['ulke_adi' => 'Türkiye', 'ulke_kodu' => 'TR']);
        $this->tenant = Tenant::create(['name' => 'Test Tenant', 'domain' => 'test.local']);

        // Prevent non-faked HTTP requests from leaking between tests
        Http::preventStrayRequests();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AIMessageService: write parity
    // ─────────────────────────────────────────────────────────────────────────

    public function test_legacy_ai_message_service_writes_correct_tenant_id(): void
    {
        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'Danışman',
            'email' => 'd@test.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'password' => bcrypt('password'),
        ]);

        $ilan = Ilan::withoutGlobalScopes()->create([
            'baslik' => 'Test İlan',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        $communication = Communication::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'communicable_type' => Ilan::class,
            'communicable_id' => $ilan->id,
            'channel' => 'telegram',
            'message' => 'Test mesaj',
        ]);

        Http::fake([
            '*' => Http::response(['content' => 'AI yanıtı', 'model' => 'ollama']),
        ]);

        $service = app(AIMessageService::class);
        $message = $service->generateDraftReply($communication->id);

        $this->assertDatabaseHas('ai_messages', [
            'id' => $message->id,
            'tenant_id' => $this->tenant->id,
            'ulke_id' => $this->ulke->id,
        ]);
        $this->assertEquals($this->tenant->id, $message->tenant_id);
        $this->assertEquals($this->ulke->id, $message->ulke_id);
    }

    public function test_legacy_ai_message_service_writes_correct_ulke_id(): void
    {
        $ilan = Ilan::withoutGlobalScopes()->create([
            'baslik' => 'Test İlan 2',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        $communication = Communication::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'communicable_type' => Ilan::class,
            'communicable_id' => $ilan->id,
            'channel' => 'email',
            'message' => 'Email test',
        ]);

        Http::fake([
            '*' => Http::response(['content' => 'Email AI yanıtı']),
        ]);

        $service = app(AIMessageService::class);
        $message = $service->generateDraftReply($communication->id);

        $this->assertEquals($this->ulke->id, $message->ulke_id);
        $this->assertDatabaseHas('ai_messages', [
            'id' => $message->id,
            'ulke_id' => $this->ulke->id,
        ]);
    }

    public function test_legacy_ai_message_service_fails_closed_without_resolvable_tenant(): void
    {
        // Ilan with null tenant_id → tenant resolution fails
        $ilan = Ilan::withoutGlobalScopes()->create([
            'baslik' => 'Tenant-free İlan',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => null,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        $communication = Communication::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'communicable_type' => Ilan::class,
            'communicable_id' => $ilan->id,
            'channel' => 'telegram',
            'message' => 'Test',
        ]);

        Http::fake(['*' => Http::response(['content' => 'Yanıt'])]);

        $service = app(AIMessageService::class);

        $this->expectException(TenantOwnershipUnresolvableException::class);
        $service->generateDraftReply($communication->id);
    }

    public function test_legacy_ai_message_service_fails_closed_without_resolvable_country(): void
    {
        // Ilan with null ulke_id → country resolution fails
        $ulke2 = Ulke::create(['ulke_adi' => 'Germany', 'ulke_kodu' => 'DE']);

        $ilan = Ilan::withoutGlobalScopes()->create([
            'baslik' => 'Country-free İlan',
            'ulke_id' => null,
            'tenant_id' => $this->tenant->id,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        $communication = Communication::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'communicable_type' => Ilan::class,
            'communicable_id' => $ilan->id,
            'channel' => 'telegram',
            'message' => 'Test',
        ]);

        Http::fake(['*' => Http::response(['content' => 'Yanıt'])]);

        $service = app(AIMessageService::class);

        $this->expectException(CountryOwnershipUnresolvableException::class);
        $service->generateDraftReply($communication->id);
    }

    public function test_legacy_message_service_writes_zero_records_on_tenant_failure(): void
    {
        $ilan = Ilan::withoutGlobalScopes()->create([
            'baslik' => 'Tenant-free',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => null,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        $communication = Communication::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'communicable_type' => Ilan::class,
            'communicable_id' => $ilan->id,
            'channel' => 'telegram',
            'message' => 'Test',
        ]);

        Http::fake(['*' => Http::response(['content' => 'Yanıt'])]);

        $service = app(AIMessageService::class);

        try {
            $service->generateDraftReply($communication->id);
        } catch (TenantOwnershipUnresolvableException) {
            // Expected
        }

        $this->assertDatabaseMissing('ai_messages', [
            'communication_id' => $communication->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AIContractService: write parity
    // ─────────────────────────────────────────────────────────────────────────

    public function test_legacy_contract_service_writes_correct_tenant_id(): void
    {
        $ilan = Ilan::withoutGlobalScopes()->make([
            'baslik' => 'Sözleşme İlanı',
            'tenant_id' => $this->tenant->id,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);
        $ilan->forceFill(['ulke_id' => $this->ulke->id])->save();

        Http::fake([
            '*' => Http::response(['content' => 'Sözleşme içeriği', 'model' => 'deepseek-r1']),
        ]);

        $service = app(AIContractService::class);
        $draft = $service->generateDraft('kira', $ilan->id, null);

        $this->assertDatabaseHas('ai_contract_drafts', [
            'id' => $draft->id,
            'tenant_id' => $this->tenant->id,
            'ulke_id' => $this->ulke->id,
            'property_id' => $ilan->id,
        ]);
        $this->assertEquals($this->tenant->id, $draft->tenant_id);
        $this->assertEquals($this->ulke->id, $draft->ulke_id);
    }

    public function test_legacy_contract_service_writes_correct_ulke_id(): void
    {
        $ilan = Ilan::withoutGlobalScopes()->make([
            'baslik' => 'Ulke Test İlanı',
            'tenant_id' => $this->tenant->id,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);
        $ilan->forceFill(['ulke_id' => $this->ulke->id])->save();

        Http::fake(['*' => Http::response(['content' => 'Sözleşme içeriği'])]);

        $service = app(AIContractService::class);
        $draft = $service->generateDraft('satis', $ilan->id, null);

        $this->assertEquals($this->ulke->id, $draft->ulke_id);
        $this->assertDatabaseHas('ai_contract_drafts', [
            'id' => $draft->id,
            'ulke_id' => $this->ulke->id,
        ]);
    }

    public function test_legacy_contract_service_fails_closed_without_resolvable_tenant(): void
    {
        // tenant_id=null — only tenant resolution fails.
        // ulke_id is set via forceFill so ulke resolution succeeds first.
        $ilan = Ilan::withoutGlobalScopes()->make([
            'baslik' => 'Tenant-free sözleşme',
            'tenant_id' => null,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);
        $ilan->forceFill(['ulke_id' => $this->ulke->id])->save();

        Http::fake(['*' => Http::response(['content' => 'Sözleşme'])]);

        $service = app(AIContractService::class);

        $this->expectException(TenantOwnershipUnresolvableException::class);
        $service->generateDraft('kira', $ilan->id, null);
    }

    public function test_legacy_contract_service_fails_closed_on_cross_tenant_injection(): void
    {
        // Ilan belongs to tenant A, Kisi belongs to tenant B → cross-tenant injection
        $tenantB = Tenant::create(['name' => 'Tenant B', 'domain' => 'tenant-b.local']);

        $ilan = Ilan::withoutGlobalScopes()->make([
            'baslik' => 'Tenant A İlanı',
            'tenant_id' => $this->tenant->id,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);
        $ilan->forceFill(['ulke_id' => $this->ulke->id])->save();

        // Use canonical KisiTipi enum value 'lead' (Aday Müşteri)
        $kisi = Kisi::withoutGlobalScopes()->create([
            'ad' => 'Kişi',
            'soyad' => 'Test',
            'telefon' => '5550000001',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $tenantB->id,
            'kisi_tipi' => 'lead',
        ]);

        Http::fake(['*' => Http::response(['content' => 'Sözleşme'])]);

        $service = app(AIContractService::class);

        $this->expectException(CrossTenantIdentifierInjectionException::class);
        $service->generateDraft('kira', $ilan->id, $kisi->id);
    }

    public function test_legacy_contract_service_writes_zero_records_on_tenant_failure(): void
    {
        $ilan = Ilan::withoutGlobalScopes()->make([
            'baslik' => 'Tenant-free',
            'tenant_id' => null,
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);
        $ilan->forceFill(['ulke_id' => $this->ulke->id])->save();

        Http::fake(['*' => Http::response(['content' => 'Sözleşme'])]);

        $service = app(AIContractService::class);

        try {
            $service->generateDraft('kira', $ilan->id, null);
        } catch (TenantOwnershipUnresolvableException) {
            // Expected
        }

        $this->assertDatabaseMissing('ai_contract_drafts', [
            'property_id' => $ilan->id,
            'contract_type' => 'kira',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AIIlanTaslagiService: write parity
    // ─────────────────────────────────────────────────────────────────────────

    public function test_legacy_ilan_draft_service_writes_correct_tenant_id(): void
    {
        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'İlan Danışmanı',
            'email' => 'ilan@d.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'password' => bcrypt('password'),
        ]);

        Http::fake([
            '*' => Http::response([
                'baslik' => 'AI İlan Başlığı',
                'aciklama' => 'AI Açıklama',
                'fiyat' => 5000000,
            ]),
        ]);

        $service = app(AIIlanTaslagiService::class);
        $taslak = $service->generateDraft(['data' => 'test'], $danisman->id);

        $this->assertDatabaseHas('ai_ilan_taslaklari', [
            'id' => $taslak->id,
            'tenant_id' => $this->tenant->id,
            'ulke_id' => $this->ulke->id,
            'danisman_id' => $danisman->id,
        ]);
        $this->assertEquals($this->tenant->id, $taslak->tenant_id);
        $this->assertEquals($this->ulke->id, $taslak->ulke_id);
    }

    public function test_legacy_ilan_draft_service_writes_correct_ulke_id(): void
    {
        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'Ulke Ilan Danışmanı',
            'email' => 'ulke@d.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'password' => bcrypt('password'),
        ]);

        Http::fake(['*' => Http::response(['baslik' => 'İlan', 'fiyat' => 1000])]);

        $service = app(AIIlanTaslagiService::class);
        $taslak = $service->generateDraft(['data' => 'test'], $danisman->id);

        $this->assertEquals($this->ulke->id, $taslak->ulke_id);
        $this->assertDatabaseHas('ai_ilan_taslaklari', [
            'id' => $taslak->id,
            'ulke_id' => $this->ulke->id,
        ]);
    }

    public function test_legacy_ilan_draft_service_fails_closed_without_resolvable_tenant(): void
    {
        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'Tenant-free Danışman',
            'email' => 'notenant@d.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => null,
            'password' => bcrypt('password'),
        ]);

        Http::fake(['*' => Http::response(['baslik' => 'İlan'])]);

        $service = app(AIIlanTaslagiService::class);

        $this->expectException(TenantOwnershipUnresolvableException::class);
        $service->generateDraft(['data' => 'test'], $danisman->id);
    }

    public function test_legacy_ilan_draft_service_writes_zero_records_on_tenant_failure(): void
    {
        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'Tenant-free',
            'email' => 'notenant2@d.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => null,
            'password' => bcrypt('password'),
        ]);

        Http::fake(['*' => Http::response(['baslik' => 'İlan'])]);

        $service = app(AIIlanTaslagiService::class);

        try {
            $service->generateDraft(['data' => 'test'], $danisman->id);
        } catch (TenantOwnershipUnresolvableException) {
            // Expected
        }

        $this->assertDatabaseMissing('ai_ilan_taslaklari', [
            'danisman_id' => $danisman->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Country ownership non-regression
    // ─────────────────────────────────────────────────────────────────────────

    public function test_legacy_services_country_ownership_remains_correct(): void
    {
        $ulkeDE = Ulke::create(['ulke_adi' => 'Germany', 'ulke_kodu' => 'DE']);
        $tenantDE = Tenant::create(['name' => 'DE Tenant', 'domain' => 'de.local']);

        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'DE Danışman',
            'email' => 'de@d.com',
            'ulke_id' => $ulkeDE->id,
            'tenant_id' => $tenantDE->id,
            'password' => bcrypt('password'),
        ]);

        Http::fake(['*' => Http::response(['baslik' => 'İlan'])]);

        $service = app(AIIlanTaslagiService::class);
        $taslak = $service->generateDraft(['data' => 'test'], $danisman->id);

        $this->assertEquals($ulkeDE->id, $taslak->ulke_id);
        $this->assertEquals($tenantDE->id, $taslak->tenant_id);
    }
}

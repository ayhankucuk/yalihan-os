<?php

namespace Tests\Unit\UseCases;

use App\Enums\KisiTipi;
use App\Enums\TaslakDurumu;
use App\Exceptions\TenantOwnershipUnresolvableException;
use App\Models\AI\AIContractDraft;
use App\Models\AI\AIIlanTaslagi;
use App\Models\AI\AIMessage;
use App\Models\Communication;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\Ulke;
use App\Models\User;
use App\UseCases\N8n\DTOs\AIContractDraftDTO;
use App\UseCases\N8n\DTOs\AIIlanTaslagiDTO;
use App\UseCases\N8n\DTOs\AIMesajTaslagiDTO;
use App\UseCases\N8n\ProcessAIContractDraftUseCase;
use App\UseCases\N8n\ProcessAIIlanTaslagiUseCase;
use App\UseCases\N8n\ProcessAIMesajTaslagiUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Persistence regression tests for N8N_AI_TENANT_ID_MISSING_02.
 *
 * Verifies that all three N8n UseCases write records with correct:
 *   - ulke_id (from CountryOwnershipResolver — commit 3ae44b6b)
 *   - tenant_id (from TenantOwnershipResolver — N8N_AI_TENANT_ID_MISSING_02)
 *
 * Tenant ownership resolution:
 *   - ilanTaslagi:    User(danisman_id).tenant_id OR Ilan(ilan_id).tenant_id
 *   - sozlesmeTaslagi: Ilan(property_id).tenant_id OR Kisi(kisi_id).tenant_id
 *   - mesajTaslagi:  Communication.communicable.tenant_id (polymorphic)
 *
 * Scope bypass: N8n webhook flows have no authenticated user and no tenant context.
 * resolveViaKisi uses withoutGlobalScopes() to bypass CountryScope + TenantScope.
 */
class N8nUseCasesPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ulke = Ulke::create(['ulke_adi' => 'Türkiye', 'ulke_kodu' => 'TR']);
        $this->tenant = Tenant::create(['name' => 'Test Tenant', 'domain' => 'test.local']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COUNTRY + TENANT happy-path: ilanTaslagi
    // ─────────────────────────────────────────────────────────────────────────

    public function test_process_ai_ilan_taslagi_use_case_persists_all_attributes(): void
    {
        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'Test Danışman',
            'email' => 'danisman@test.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'password' => bcrypt('password'),
        ]);

        $dto = new AIIlanTaslagiDTO(
            danismanId: $danisman->id,
            ilanId: null,
            data: ['baslik' => 'Lüks Bodrum Villası'],
            aiResponse: ['baslik' => 'Lüks Bodrum Villası', 'fiyat' => 15000000],
            aiModelUsed: 'anythingllm',
            aiPromptVersion: '2.0'
        );

        $useCase = app(ProcessAIIlanTaslagiUseCase::class);
        $taslak = $useCase->handle($dto);

        $this->assertInstanceOf(AIIlanTaslagi::class, $taslak);
        $this->assertDatabaseHas('ai_ilan_taslaklari', [
            'id' => $taslak->id,
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'danisman_id' => $danisman->id,
            'yayin_durumu' => TaslakDurumu::TASLAK->value,
            'ai_model_used' => 'anythingllm',
            'ai_prompt_version' => '2.0',
        ]);
        $this->assertEquals($this->ulke->id, $taslak->ulke_id);
        $this->assertEquals($this->tenant->id, $taslak->tenant_id);
        $this->assertNotNull($taslak->ai_generated_at);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COUNTRY + TENANT happy-path: sozlesmeTaslagi
    // ─────────────────────────────────────────────────────────────────────────

    public function test_process_ai_contract_draft_use_case_persists_all_attributes(): void
    {
        $kisi = Kisi::withoutCountryScope()->create([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'kisi_tipi' => KisiTipi::ALICI->value,
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $dto = new AIContractDraftDTO(
            contractType: 'kira',
            content: 'Kira Sözleşmesi Taslağı İçeriği',
            propertyId: null,
            kisiId: $kisi->id,
            aiModelUsed: 'deepseek-r1'
        );

        $useCase = app(ProcessAIContractDraftUseCase::class);
        $draft = $useCase->handle($dto);

        $this->assertInstanceOf(AIContractDraft::class, $draft);
        $this->assertDatabaseHas('ai_contract_drafts', [
            'id' => $draft->id,
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'contract_type' => 'kira',
            'property_id' => null,
            'kisi_id' => $kisi->id,
            'content' => 'Kira Sözleşmesi Taslağı İçeriği',
            'draft_content' => 'Kira Sözleşmesi Taslağı İçeriği',
            'yayin_durumu' => TaslakDurumu::TASLAK->value,
            'ai_model_used' => 'deepseek-r1',
        ]);
        $this->assertEquals($this->ulke->id, $draft->ulke_id);
        $this->assertEquals($this->tenant->id, $draft->tenant_id);
        $this->assertNotNull($draft->ai_generated_at);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COUNTRY + TENANT happy-path: mesajTaslagi
    // ─────────────────────────────────────────────────────────────────────────

    public function test_process_ai_mesaj_taslagi_use_case_persists_all_attributes(): void
    {
        $user = User::withoutGlobalScopes()->create([
            'name' => 'Test Sahibi',
            'email' => 'sahip@test.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'password' => bcrypt('password'),
        ]);

        $kisi = Kisi::withoutCountryScope()->create([
            'ad' => 'Müşteri',
            'soyad' => 'Test',
            'kisi_tipi' => KisiTipi::ALICI->value,
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
        ]);

        $communication = Communication::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'communicable_type' => Kisi::class,
            'communicable_id' => $kisi->id,
            'channel' => 'telegram',
            'message' => 'Test mesaj',
        ]);

        $dto = new AIMesajTaslagiDTO(
            communicationId: $communication->id,
            channel: 'telegram',
            content: 'Merhaba, bu bir AI mesaj taslağıdır.',
            aiModelUsed: 'ollama/llama3'
        );

        $useCase = app(ProcessAIMesajTaslagiUseCase::class);
        $message = $useCase->handle($dto);

        $this->assertInstanceOf(AIMessage::class, $message);
        $this->assertDatabaseHas('ai_messages', [
            'id' => $message->id,
            'ulke_id' => $this->ulke->id,
            'tenant_id' => $this->tenant->id,
            'communication_id' => $communication->id,
            'channel' => 'telegram',
            'role' => 'assistant',
            'content' => 'Merhaba, bu bir AI mesaj taslağıdır.',
            'mesaj_durumu' => 'draft',
            'ai_model_used' => 'ollama/llama3',
        ]);
        $this->assertEquals($this->ulke->id, $message->ulke_id);
        $this->assertEquals($this->tenant->id, $message->tenant_id);
        $this->assertNotNull($message->ai_generated_at);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COUNTRY fail-closed: existing tests (preserved)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_mesaj_taslagi_fails_closed_when_communication_not_found(): void
    {
        $dto = new AIMesajTaslagiDTO(
            communicationId: 99999,
            channel: 'telegram',
            content: 'Test',
            aiModelUsed: 'ollama'
        );

        $useCase = app(ProcessAIMesajTaslagiUseCase::class);
        $this->expectException(\App\Exceptions\CountryOwnershipUnresolvableException::class);
        $useCase->handle($dto);
    }

    public function test_mesaj_taslagi_fails_closed_when_communicable_has_null_ulke(): void
    {
        $kisi = Kisi::withoutCountryScope()->create([
            'ad' => 'Null',
            'soyad' => 'UlkeKisi',
            'kisi_tipi' => KisiTipi::LEAD->value,
            'ulke_id' => null,
            'tenant_id' => $this->tenant->id,
        ]);

        $communication = Communication::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'communicable_type' => Kisi::class,
            'communicable_id' => $kisi->id,
            'channel' => 'whatsapp',
            'message' => 'Test',
        ]);

        $dto = new AIMesajTaslagiDTO(
            communicationId: $communication->id,
            channel: 'whatsapp',
            content: 'Test',
            aiModelUsed: 'ollama'
        );

        $useCase = app(ProcessAIMesajTaslagiUseCase::class);
        $this->expectException(\App\Exceptions\CountryOwnershipUnresolvableException::class);
        $useCase->handle($dto);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TENANT fail-closed: ilanTaslagi
    // ─────────────────────────────────────────────────────────────────────────

    public function test_ilan_taslagi_fails_closed_when_danisman_has_null_tenant(): void
    {
        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'Tenant-free Danışman',
            'email' => 'notenant@test.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => null,
            'password' => bcrypt('password'),
        ]);

        $dto = new AIIlanTaslagiDTO(
            danismanId: $danisman->id,
            ilanId: null,
            data: ['baslik' => 'Bodrum Villası'],
            aiResponse: ['baslik' => 'Bodrum Villası'],
            aiModelUsed: 'anythingllm',
            aiPromptVersion: '2.0'
        );

        $useCase = app(ProcessAIIlanTaslagiUseCase::class);
        $this->expectException(TenantOwnershipUnresolvableException::class);
        $useCase->handle($dto);
    }

    public function test_ilan_taslagi_fails_closed_when_both_danisman_and_ilan_have_null_tenant(): void
    {
        $danisman = User::withoutGlobalScopes()->create([
            'name' => 'Tenant-free Danışman',
            'email' => 'notenant2@test.com',
            'ulke_id' => $this->ulke->id,
            'tenant_id' => null,
            'password' => bcrypt('password'),
        ]);

        $dto = new AIIlanTaslagiDTO(
            danismanId: $danisman->id,
            ilanId: null,
            data: ['baslik' => 'Bodrum Villası'],
            aiResponse: ['baslik' => 'Bodrum Villası'],
            aiModelUsed: 'anythingllm',
            aiPromptVersion: '2.0'
        );

        $useCase = app(ProcessAIIlanTaslagiUseCase::class);
        $this->expectException(TenantOwnershipUnresolvableException::class);
        $useCase->handle($dto);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TENANT fail-closed: sozlesmeTaslagi
    // ─────────────────────────────────────────────────────────────────────────

    public function test_sozlesme_taslagi_fails_closed_when_ilan_has_null_tenant(): void
    {
        // Ilan.tenant_id is nullable, so we can test the null-tenant fail-closed path.
        // Kisi.tenant_id is NOT NULL (enforced by migration 2026_07_18) — cannot test null path.
        $ilan = Ilan::factory()->create(['ulke_id' => $this->ulke->id, 'il_id' => 1]);
        $ilan->update(['tenant_id' => null]);
        $ilan->refresh();

        $dto = new AIContractDraftDTO(
            contractType: 'kira',
            content: 'Kira Sözleşmesi',
            propertyId: $ilan->id,
            kisiId: null,
            aiModelUsed: 'deepseek-r1'
        );

        $useCase = app(ProcessAIContractDraftUseCase::class);
        $this->expectException(TenantOwnershipUnresolvableException::class);
        $useCase->handle($dto);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TENANT fail-closed: mesajTaslagi
    // ─────────────────────────────────────────────────────────────────────────

    public function test_mesaj_taslagi_fails_closed_when_communicable_has_null_tenant(): void
    {
        // Ilan.tenant_id is nullable, so we can test the null-tenant fail-closed path.
        // Kisi.tenant_id is NOT NULL (enforced by migration 2026_07_18) — cannot test null path.
        $ilan = Ilan::factory()->create(['ulke_id' => $this->ulke->id, 'il_id' => 1]);
        $ilan->update(['tenant_id' => null]);
        $ilan->refresh();

        $communication = Communication::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'communicable_type' => Ilan::class,
            'communicable_id' => $ilan->id,
            'channel' => 'telegram',
            'message' => 'Test',
        ]);

        $dto = new AIMesajTaslagiDTO(
            communicationId: $communication->id,
            channel: 'telegram',
            content: 'Test',
            aiModelUsed: 'ollama'
        );

        $useCase = app(ProcessAIMesajTaslagiUseCase::class);
        // Country resolves (ulke_id is set), tenant fails (tenant_id is null).
        $this->expectException(TenantOwnershipUnresolvableException::class);
        $useCase->handle($dto);
    }
}

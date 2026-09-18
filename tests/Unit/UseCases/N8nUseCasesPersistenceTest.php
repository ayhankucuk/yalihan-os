<?php

namespace Tests\Unit\UseCases;

use App\Enums\KisiTipi;
use App\Enums\TaslakDurumu;
use App\Models\AI\AIContractDraft;
use App\Models\AI\AIIlanTaslagi;
use App\Models\AI\AIMessage;
use App\Models\Communication;
use App\Models\Ilan;
use App\Models\Kisi;
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
 * Persistence regression tests for N8N-AI-USECASES-MODEL-PERSISTENCE-CONTRACT-DRIFT.
 *
 * Verifies that handle() calls on all three N8n UseCases write records
 * to SQLite DB with correct ulke_id resolved from canonical ownership chains.
 *
 * Country ownership resolution:
 *   - ilanTaslagi:    User(danisman_id).ulke_id OR Ilan(ilan_id).ulke_id
 *   - sozlesmeTaslagi: Ilan(property_id).ulke_id OR Kisi(kisi_id).ulke_id
 *   - mesajTaslagi:  Communication.communicable.ulke_id (polymorphic)
 */
class N8nUseCasesPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Shared country fixture — used across all three tests.
        $this->ulke = Ulke::create(['ulke_adi' => 'Türkiye', 'ulke_kodu' => 'TR']);
    }

    public function test_process_ai_mesaj_taslagi_use_case_persists_all_attributes(): void
    {
        // Communication requires: polymorphic communicable (Kisi) + Ulke.
        // Kisi.ulke_id column is confirmed present in mysql-schema.sql.
        $kisi = Kisi::create([
            'ad' => 'Müşteri',
            'soyad' => 'Test',
            'kisi_tipi' => KisiTipi::ALICI->value,
            'ulke_id' => $this->ulke->id,
        ]);

        $communication = Communication::create([
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
            'communication_id' => $communication->id,
            'channel' => 'telegram',
            'role' => 'assistant',
            'content' => 'Merhaba, bu bir AI mesaj taslağıdır.',
            'mesaj_durumu' => 'draft',
            'ai_model_used' => 'ollama/llama3',
        ]);
        $this->assertEquals($this->ulke->id, $message->ulke_id);
        $this->assertNotNull($message->ai_generated_at);
    }

    public function test_process_ai_contract_draft_use_case_persists_all_attributes(): void
    {
        // Contract draft via Kisi (lead/person) — Kisi must have ulke_id.
        $kisi = Kisi::create([
            'ad' => 'Ahmet',
            'soyad' => 'Yılmaz',
            'kisi_tipi' => KisiTipi::ALICI->value, // 'alici' — not 'Müşteri'
            'ulke_id' => $this->ulke->id,
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
            'contract_type' => 'kira',
            'property_id' => null,
            'ilan_id' => null,
            'kisi_id' => $kisi->id,
            'content' => 'Kira Sözleşmesi Taslağı İçeriği',
            'draft_content' => 'Kira Sözleşmesi Taslağı İçeriği',
            'yayin_durumu' => TaslakDurumu::TASLAK->value,
            'ai_model_used' => 'deepseek-r1',
        ]);
        $this->assertEquals($this->ulke->id, $draft->ulke_id);
        $this->assertNotNull($draft->ai_generated_at);
    }

    public function test_process_ai_ilan_taslagi_use_case_persists_all_attributes(): void
    {
        // ilanTaslagi resolves via User(danisman_id).ulke_id.
        $danisman = User::create([
            'name' => 'Test Danışman',
            'email' => 'danisman@test.com',
            'ulke_id' => $this->ulke->id,
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
            'danisman_id' => $danisman->id,
            'yayin_durumu' => TaslakDurumu::TASLAK->value,
            'ai_model_used' => 'anythingllm',
            'ai_prompt_version' => '2.0',
        ]);
        $this->assertEquals($this->ulke->id, $taslak->ulke_id);
        $this->assertNotNull($taslak->ai_generated_at);
    }

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
        // Communication to a Kisi with null ulke_id should fail-closed.
        $kisi = Kisi::create([
            'ad' => 'Null',
            'soyad' => 'UlkeKisi',
            'kisi_tipi' => KisiTipi::LEAD->value, // 'lead'
            'ulke_id' => null,
        ]);

        $communication = Communication::create([
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
}

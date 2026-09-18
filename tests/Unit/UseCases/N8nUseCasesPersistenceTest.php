<?php

namespace Tests\Unit\UseCases;

use App\Enums\TaslakDurumu;
use App\Models\AI\AIContractDraft;
use App\Models\AI\AIIlanTaslagi;
use App\Models\AI\AIMessage;
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
 * to SQLite DB without mass-assignment truncation or field mismatch.
 */
class N8nUseCasesPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_ai_mesaj_taslagi_use_case_persists_all_attributes(): void
    {
        $dto = new AIMesajTaslagiDTO(
            communicationId: 101,
            channel: 'telegram',
            content: 'Merhaba, bu bir AI mesaj taslağıdır.',
            aiModelUsed: 'ollama/llama3'
        );

        $useCase = app(ProcessAIMesajTaslagiUseCase::class);
        $message = $useCase->handle($dto);

        $this->assertInstanceOf(AIMessage::class, $message);
        $this->assertDatabaseHas('ai_messages', [
            'id' => $message->id,
            'communication_id' => 101,
            'channel' => 'telegram',
            'role' => 'assistant',
            'content' => 'Merhaba, bu bir AI mesaj taslağıdır.',
            'mesaj_durumu' => 'draft',
            'ai_model_used' => 'ollama/llama3',
        ]);

        $this->assertNotNull($message->ai_generated_at);
    }

    public function test_process_ai_contract_draft_use_case_persists_all_attributes(): void
    {
        $dto = new AIContractDraftDTO(
            contractType: 'kira',
            content: 'Kira Sözleşmesi Taslağı İçeği',
            propertyId: 505,
            kisiId: 303,
            aiModelUsed: 'deepseek-r1'
        );

        $useCase = app(ProcessAIContractDraftUseCase::class);
        $draft = $useCase->handle($dto);

        $this->assertInstanceOf(AIContractDraft::class, $draft);
        $this->assertDatabaseHas('ai_contract_drafts', [
            'id' => $draft->id,
            'contract_type' => 'kira',
            'property_id' => 505,
            'ilan_id' => 505,
            'kisi_id' => 303,
            'content' => 'Kira Sözleşmesi Taslağı İçeği',
            'draft_content' => 'Kira Sözleşmesi Taslağı İçeği',
            'yayin_durumu' => TaslakDurumu::TASLAK->value,
            'ai_model_used' => 'deepseek-r1',
        ]);

        $this->assertNotNull($draft->ai_generated_at);
    }

    public function test_process_ai_ilan_taslagi_use_case_persists_all_attributes(): void
    {
        $dto = new AIIlanTaslagiDTO(
            danismanId: 42,
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
            'danisman_id' => 42,
            'yayin_durumu' => TaslakDurumu::TASLAK->value,
            'ai_model_used' => 'anythingllm',
            'ai_prompt_version' => '2.0',
        ]);

        $this->assertNotNull($taslak->ai_generated_at);
    }
}

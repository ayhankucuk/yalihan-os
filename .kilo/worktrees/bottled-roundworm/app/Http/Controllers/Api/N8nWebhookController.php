<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\AIIlanTaslagiService;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\UseCases\N8n\AnalyzeMarketUseCase;
use App\UseCases\N8n\DTOs\AIContractDraftDTO;
use App\UseCases\N8n\DTOs\AIIlanTaslagiDTO;
use App\UseCases\N8n\DTOs\AIMesajTaslagiDTO;
use App\UseCases\N8n\DTOs\AnalyzeMarketDTO;
use App\UseCases\N8n\DTOs\TriggerReverseMatchDTO;
use App\UseCases\N8n\ProcessAIContractDraftUseCase;
use App\UseCases\N8n\ProcessAIIlanTaslagiUseCase;
use App\UseCases\N8n\ProcessAIMesajTaslagiUseCase;
use App\UseCases\N8n\TriggerReverseMatchUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * n8n Webhook Controller
 *
 * Context7 Standardı: C7-N8N-WEBHOOK-CONTROLLER-2025-11-20
 *
 * n8n workflow'larından gelen webhook isteklerini işler.
 * AnythingLLM entegrasyonu için endpoint'ler sağlar.
 *
 * ✅ SAB L5 Compliance: Zero Business Logic / Pure Delegation.
 */
class N8nWebhookController extends Controller
{
    public function __construct(
        protected AIIlanTaslagiService $ilanTaslagiService,
        protected ProcessAIIlanTaslagiUseCase $processAIIlanTaslagiUseCase,
        protected ProcessAIMesajTaslagiUseCase $processAIMesajTaslagiUseCase,
        protected ProcessAIContractDraftUseCase $processAIContractDraftUseCase,
        protected AnalyzeMarketUseCase $analyzeMarketUseCase,
        protected TriggerReverseMatchUseCase $triggerReverseMatchUseCase
    ) {
    }

    /**
     * AI İlan Taslağı Webhook
     */
    public function ilanTaslagi(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'danisman_id' => 'required|integer|exists:users,id',
                'data' => 'required|array',
                'ai_response' => 'required|array',
                'ai_model_used' => 'nullable|string',
                'ai_prompt_version' => 'nullable|string',
            ]);

            if ($validated->fails()) {
                return ResponseService::validationError($validated->errors()->toArray());
            }

            $taslak = $this->processAIIlanTaslagiUseCase->handle(
                AIIlanTaslagiDTO::fromRequest($validated->validated())
            );

            return ResponseService::success([
                'taslak_id' => $taslak->id,
                'islem_durumu' => $taslak->yayin_durumu,
            ], 'AI ilan taslağı başarıyla oluşturuldu');
        } catch (\Exception $e) {
            LogService::error(
                'n8n webhook: AI ilan taslağı hatası',
                ['error' => $e->getMessage()],
                $e,
                LogService::CHANNEL_API
            );

            return ResponseService::serverError('AI ilan taslağı oluşturulamadı', $e);
        }
    }

    /**
     * AI Mesaj Taslağı Webhook
     */
    public function mesajTaslagi(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'communication_id' => 'required|integer|exists:communications,id',
                'channel' => 'required|string|in:telegram,whatsapp,instagram,email,web',
                'content' => 'required|string',
                'ai_model_used' => 'nullable|string',
            ]);

            if ($validated->fails()) {
                return ResponseService::validationError($validated->errors()->toArray());
            }

            $message = $this->processAIMesajTaslagiUseCase->handle(
                AIMesajTaslagiDTO::fromRequest($validated->validated())
            );

            return ResponseService::success([
                'message_id' => $message->id,
                'islem_durumu' => $message->mesaj_durumu,
            ], 'AI mesaj taslağı başarıyla oluşturuldu');
        } catch (\Exception $e) {
            LogService::error(
                'n8n webhook: AI mesaj taslağı hatası',
                ['error' => $e->getMessage()],
                $e,
                LogService::CHANNEL_API
            );

            return ResponseService::serverError('AI mesaj taslağı oluşturulamadı', $e);
        }
    }

    /**
     * AI Sözleşme Taslağı Webhook
     */
    public function sozlesmeTaslagi(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'contract_type' => 'required|string|in:kira,satis',
                'property_id' => 'nullable|integer|exists:ilanlar,id',
                'kisi_id' => 'nullable|integer|exists:kisiler,id',
                'content' => 'required|string',
                'ai_model_used' => 'nullable|string',
            ]);

            if ($validated->fails()) {
                return ResponseService::validationError($validated->errors()->toArray());
            }

            $draft = $this->processAIContractDraftUseCase->handle(
                AIContractDraftDTO::fromRequest($validated->validated())
            );

            return ResponseService::success([
                'draft_id' => $draft->id,
                'islem_durumu' => $draft->yayin_durumu,
            ], 'AI sözleşme taslağı başarıyla oluşturuldu');
        } catch (\Exception $e) {
            LogService::error(
                'n8n webhook: AI sözleşme taslağı hatası',
                ['error' => $e->getMessage()],
                $e,
                LogService::CHANNEL_API
            );

            return ResponseService::serverError('AI sözleşme taslağı oluşturulamadı', $e);
        }
    }

    /**
     * Test Webhook
     */
    public function test(Request $request)
    {
        LogService::info('n8n webhook test', ['request' => $request->all()], LogService::CHANNEL_API);

        return ResponseService::success([
            'message' => 'n8n webhook test başarılı',
            'timestamp' => now()->toISOString(),
            'received_data' => $request->all(),
        ], 'Webhook test başarılı');
    }

    /**
     * Emsal Arama (Market Analysis)
     */
    public function analyzeMarket(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'location' => 'required|string|max:255',
                'm2' => 'required|numeric|min:1',
                'type' => 'required|string|in:tarla,arsa,konut,isyeri,villa,yazlik', // context7-ignore
            ]);

            if ($validated->fails()) {
                return ResponseService::validationError($validated->errors()->toArray());
            }

            LogService::api('n8n_webhook_analyze_market', $validated->validated());

            $result = $this->analyzeMarketUseCase->handle(
                AnalyzeMarketDTO::fromRequest($validated->validated())
            );

            return ResponseService::success($result, 'Emsal arama başarıyla tamamlandı');
        } catch (\Exception $e) {
            LogService::error(
                'n8n webhook: Emsal arama hatası',
                ['error' => $e->getMessage()],
                $e,
                LogService::CHANNEL_API
            );

            return ResponseService::serverError('Emsal arama sırasında hata oluştu', $e);
        }
    }

    /**
     * Taslak İlan Oluştur (Telegram'dan gelen ham metin)
     */
    public function createDraftListing(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'text' => 'required|string|min:10',
                'danisman_id' => 'nullable|integer|exists:users,id',
                'source' => 'nullable|string|in:telegram,whatsapp,instagram,email',
            ]);

            if ($validated->fails()) {
                return ResponseService::validationError($validated->errors()->toArray());
            }

            $validatedData = $validated->validated();
            $danismanId = $validatedData['danisman_id'] ?? auth()->id();
            $source = $validatedData['source'] ?? 'telegram';

            LogService::api('n8n_webhook_create_draft_listing', [
                'danisman_id' => $danismanId,
                'source' => $source,
                'text_length' => strlen($validatedData['text']),
            ]);

            $taslak = $this->ilanTaslagiService->generateDraft([
                'raw_text' => $validatedData['text'],
                'source' => $source,
                'extracted_data' => [],
            ], $danismanId);

            return ResponseService::success([
                'taslak_id' => $taslak->id,
                'islem_durumu' => $taslak->yayin_durumu,
                'message' => 'Taslak ilan başarıyla oluşturuldu',
            ], 'Taslak ilan başarıyla oluşturuldu');
        } catch (\Exception $e) {
            LogService::error(
                'n8n webhook: Taslak ilan oluşturma hatası',
                ['error' => $e->getMessage()],
                $e,
                LogService::CHANNEL_API
            );

            return ResponseService::serverError('Taslak ilan oluşturulamadı', $e);
        }
    }

    /**
     * Tersine Eşleştirme Tetikle (Reverse Match Trigger)
     */
    public function triggerReverseMatch(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'ilan_id' => 'required|integer|exists:ilanlar,id',
            ]);

            if ($validated->fails()) {
                return ResponseService::validationError($validated->errors()->toArray());
            }

            LogService::api('n8n_webhook_trigger_reverse_match', $validated->validated());

            $ilan = $this->triggerReverseMatchUseCase->handle(
                TriggerReverseMatchDTO::fromRequest($validated->validated())
            );

            return ResponseService::success([
                'ilan_id' => $ilan->id,
                'ilan_baslik' => $ilan->baslik,
                'islem_durumu' => $ilan->yayin_durumu,
                'message' => 'Tersine eşleştirme işlemi tetiklendi. Queue\'da işlenecek.',
            ], 'Tersine eşleştirme başarıyla tetiklendi');
        } catch (\InvalidArgumentException $e) {
            return ResponseService::error($e->getMessage(), $e->getCode(), [], 'INVALID_STATUS');
        } catch (\Exception $e) {
            LogService::error(
                'n8n webhook: Tersine eşleştirme tetikleme hatası',
                ['error' => $e->getMessage()],
                $e,
                LogService::CHANNEL_API
            );

            return ResponseService::serverError('Tersine eşleştirme tetiklenemedi', $e);
        }
    }
}

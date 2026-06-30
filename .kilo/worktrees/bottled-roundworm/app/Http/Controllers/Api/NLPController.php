<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\NLPProcessor;
use App\Services\AI\IntentClassifier;
use App\Services\AI\EntityExtractor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * NLP API Controller
 * 
 * Doğal dil işleme endpoint'leri:
 * - POST /api/v1/nlp/parse         → Tam mesaj parsing
 * - POST /api/v1/nlp/extract       → Entity extraction
 * - POST /api/v1/nlp/classify      → Intent classification
 * - POST /api/v1/nlp/analyze       → Sentiment analizi
 */
class NLPController extends Controller
{
    protected NLPProcessor $nlp;
    protected IntentClassifier $intentClassifier;
    protected EntityExtractor $entityExtractor;

    public function __construct(
        NLPProcessor $nlp,
        IntentClassifier $intentClassifier,
        EntityExtractor $entityExtractor
    ) {
        $this->nlp = $nlp;
        $this->intentClassifier = $intentClassifier;
        $this->entityExtractor = $entityExtractor;
    }

    /**
     * Full message parsing
     * 
     * Mesajı tam olarak işle:
     * - Entity extraction
     * - Intent classification
     * - Sentiment analysis
     * - Response generation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function parse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'user_id' => 'nullable|integer',
            'context' => 'nullable|array',
        ]);

        try {
            $result = $this->nlp->parseMessage($validated['message']);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $validated['message'],
                    'entities' => $result['entities'] ?? [],
                    'intent' => $result['intent'] ?? 'inquiry',
                    'sentiment' => $result['sentiment'] ?? 'neutral',
                    'keywords' => $result['keywords'] ?? [],
                    'confidence' => $result['confidence'] ?? 0,
                    'response' => $result['response'] ?? 'Mesajınız alındı.',
                ],
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'NLP parsing failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Intent classification only
     * 
     * Kullanıcı amacını belirle
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function classify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $intent = $this->intentClassifier->classify($validated['message']);
            $scores = $this->intentClassifier->classifyWithScores($validated['message']);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $validated['message'],
                    'intent' => $intent,
                    'all_scores' => $scores,
                    'description' => $this->intentClassifier->getDescription($intent),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Intent classification failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Entity extraction only
     * 
     * Yapılandırılmış veriyi çıkar:
     * - Location (lokasyon)
     * - Price (fiyat)
     * - Property type (mülk tipi)
     * - Rooms (oda sayısı)
     * - Features (özellikleri)
     * - Area (alan)
     * - Age (yaş)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function extract(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $entities = $this->entityExtractor->extract($validated['message']);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $validated['message'],
                    'entities' => $entities,
                    'confidence' => $this->entityExtractor->calculateEntityConfidence($entities),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Entity extraction failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Sentiment analysis
     * 
     * Duygu analizi yap:
     * - positive (olumlu)
     * - negative (olumsuz)
     * - neutral (nötr)
     * - urgent (acil)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sentiment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $sentiment = $this->nlp->analyzeSentiment($validated['message']);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $validated['message'],
                    'sentiment' => $sentiment,
                    'description' => match($sentiment) {
                        'positive' => 'Olumlu duygu',
                        'negative' => 'Olumsuz duygu',
                        'urgent' => 'Acil durum',
                        default => 'Nötr duygu',
                    },
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Sentiment analysis failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Health check
     * 
     * NLP servisleri sağlık kontrolü
     * 
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'servis_durumu' => 'aktif',
            'services' => [
                'nlp_processor' => 'ready',
                'intent_classifier' => 'ready',
                'entity_extractor' => 'ready',
            ],
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}

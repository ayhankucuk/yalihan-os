<?php

namespace App\Services\AI;

use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Talep;
use App\Models\Opportunity;

/**
 * Orchestrator Service for AI & Zeka Endpoints
 *
 * SAB v4.1 Rule #11 Enforcer: Reduces constructor dependencies in AIController.
 * This facade orchestrates actions between the Controller and Domain/Sub-services.
 * Eliminates direct DB mutations and Inline Logic from Controller.
 */
class AIOrchestrator
{
    public function __construct(
        public AudioService $audioService,
        public BriefingService $briefingService,
        protected \App\Application\Shared\Services\TenantContextResolver $tenantContextResolver,
        protected \App\Services\AI\Prompts\AiPromptRegistry $promptRegistry,
        protected \App\Services\AI\Validation\ListingAIResponseValidator $listingValidator,
        protected \App\Services\AI\Providers\DeepSeekCortexProvider $deepSeekProvider,
        protected \App\Services\AI\Providers\OpenAICortexProvider $openAIProvider,
        protected \App\Services\AI\Monetization\AiBudgetGuard $budgetGuard
    ) {
    }

    /**
     * Simple rule-based analysis (fallback when AI not configured)
     */
    public function simpleAnalysis(array $data, array $context): array
    {
        $baslik = $data['baslik'] ?? '';
        $tip = $data['tip'] ?? '';
        $kategoriId = $data['kategori_id'] ?? null;

        // Priority logic
        $priority = 'Orta';
        if (stripos($baslik, 'acil') !== false || stripos($baslik, 'urgent') !== false) {
            $priority = 'Yüksek';
        } elseif (stripos($baslik, 'önemli') !== false) {
            $priority = 'Yüksek';
        }

        // Estimated time logic
        $estimatedTime = '2-3 gün';
        if ($priority === 'Yüksek') {
            $estimatedTime = '24 saat';
        }

        // Category determination
        $category = 'Genel Talep';
        if ($kategoriId) {
            $kategori = \App\Models\IlanKategori::find($kategoriId);
            $category = $kategori->name ?? 'Genel Talep';
        }

        // Suggestion
        $suggestion = 'Detaylı lokasyon ve bütçe bilgisi ekleyerek arama sonuçlarınızı iyileştirebilirsiniz.';
        if ($tip === 'Satılık') {
            $suggestion = 'Satılık ilanlar için tapu durumu ve imar bilgilerini belirtmeniz önerilir.';
        } elseif ($tip === 'Kiralık') {
            $suggestion = 'Kiralık talepte aidat ve depozito beklentilerinizi belirtmeniz önerilir.';
        }

        return [
            'category' => $category,
            'priority' => $priority,
            'estimated_time' => $estimatedTime,
            'suggestion' => $suggestion,
        ];
    }

    /**
     * İlan lokasyonunu formatla
     *
     * @param  \App\Models\Ilan  $ilan
     */
    public function formatLocation(Ilan $ilan): string
    {
        $parts = [];

        if ($ilan->mahalle && $ilan->mahalle->mahalle_adi) {
            $parts[] = $ilan->mahalle->mahalle_adi;
        }

        if ($ilan->ilce && $ilan->ilce->ilce_adi) {
            $parts[] = $ilan->ilce->ilce_adi;
        }

        if ($ilan->il && $ilan->il->il_adi) {
            $parts[] = $ilan->il->il_adi;
        }

        return implode(', ', $parts);
    }

    /**
     * Fallback description generator (rule-based, no AI)
     */
    public function generateDescriptionFallback(string $baslik, string $tip, string $kategori, string $il, string $ilce): string
    {
        $parts = [];

        // Opening
        if ($tip === 'Satılık') {
            $parts[] = "Satılık {$kategori} arayışındayız.";
        } elseif ($tip === 'Kiralık') {
            $parts[] = "Kiralık {$kategori} talep ediyoruz.";
        } elseif ($tip === 'Günlük Kiralık') {
            $parts[] = "Günlük kiralık {$kategori} arıyoruz.";
        } else {
            $parts[] = "{$kategori} arayışımız var.";
        }

        // Location
        if ($il && $ilce) {
            $parts[] = "Lokasyon olarak {$il}, {$ilce} bölgesini tercih ediyoruz.";
        } elseif ($il) {
            $parts[] = "{$il} ilinde araştırma yapıyoruz.";
        }

        // Closing
        $parts[] = 'İlginize teşekkür ederiz, detaylı bilgi için iletişime geçebilirsiniz.';

        return implode(' ', $parts);
    }

    /**
     * Pazarlama videosu render sürecini başlatır. Array döner.
     */
    public function queueVideoRender(int $ilanId): array
    {
        $ilan = Ilan::find($ilanId);
        if (! $ilan) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('İlan bulunamadı');
        }

        // Phase3-WA: delegated to IlanCrudService as single write authority
        // AI does not directly mutate Ilan — delegates metadata update
        $ilanCrudService = app(\App\Services\Ilan\IlanCrudService::class);
        $ilan = $ilanCrudService->update($ilan, [
            'baslik' => $ilan->baslik,
            'video_isleme_durumu' => 'queued',
            'video_last_frame' => 0,
        ]);

        \App\Jobs\RenderMarketingVideo::dispatch($ilan->id);

        return [
            'ilan_id' => $ilan->id,
            'video_isleme_durumu' => $ilan->video_isleme_durumu,
        ];
    }

    /**
     * AI Fiyat Önerisi - Benzer ilanların fiyat istatistiklerini al
     */
    public function getPriceSuggestionsMetrics(array $data): ?object
    {
        $kategoriId = $data['kategori_id'] ?? null;
        $ilId = $data['il_id'] ?? null;
        $ilceId = $data['ilce_id'] ?? null;
        $tip = $data['tip'] ?? null;

        // Veritabanından benzer ilanların fiyat istatistiklerini al
        return Ilan::query()
            ->when($kategoriId, fn($q) => $q->where('alt_kategori_id', $kategoriId))
            ->when($ilId, fn($q) => $q->where('il_id', $ilId))
            ->when($ilceId, fn($q) => $q->where('ilce_id', $ilceId))
            ->when($tip, fn($q) => $q->where('yayin_tipi', $tip))
            ->selectRaw('
                MIN(fiyat) as min,
                AVG(fiyat) as avg,
                MAX(fiyat) as max,
                COUNT(*) as count
            ')
            ->first();
    }

    /**
     * Get Kisi by ID
     */
    public function getKisi(int $id): ?Kisi
    {
        return Kisi::find($id);
    }

    /**
     * Get Ilan by ID
     */
    public function getIlan(int $id): ?Ilan
    {
        return Ilan::find($id);
    }

    /**
     * Create a Talep DTO for matching
     */
    public function createMatchTalep(array $data): Talep
    {
        $talep = new Talep;
        $talep->fill([
            'alt_kategori_id' => $data['kategori_id'] ?? $data['alt_kategori_id'] ?? null,
            'il_id' => $data['il_id'] ?? null,
            'ilce_id' => $data['ilce_id'] ?? null,
            'mahalle_id' => $data['mahalle_id'] ?? null,
            'min_fiyat' => $data['min_fiyat'] ?? null,
            'max_fiyat' => $data['max_fiyat'] ?? null,
            'min_metrekare' => $data['min_metrekare'] ?? null,
            'max_metrekare' => $data['max_metrekare'] ?? null,
            'aranan_ozellikler_json' => $data['aranan_ozellikler'] ?? $data['aranan_ozellikler_json'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        if (isset($data['latitude']) || isset($data['lat'])) {
            $metadata = $talep->metadata ?? [];
            $metadata['latitude'] = $data['latitude'] ?? $data['lat'];
            $metadata['longitude'] = $data['longitude'] ?? $data['lng'];
            $talep->metadata = $metadata;
        }

        return $talep;
    }

    /**
     * Get Opportunity by ID
     */
    public function getOpportunity(int $id): ?Opportunity
    {
        return Opportunity::find($id);
    }

    /**
     * 🧠 SAAB AI Orchestration Engine
     * Routes requests with Multi-provider Failover (Primary: DeepSeek, Fallback: OpenAI)
     */
    public function orchestrateAI(\App\Application\AI\DTOs\CortexRequestData $request): \App\Application\AI\DTOs\CortexResponseData
    {
        $tenant = $this->tenantContextResolver->getTenant();
        $featureKey = $request->getOption('feature_key', 'general_ai');

        // 🛡️ Phase 12: Credit-Based Circuit Breaker
        $this->budgetGuard->canExecute($tenant, $featureKey);

        // 1. Primary Call (DeepSeek)
        $response = $this->deepSeekProvider->execute($request);

        if ($response->success) {
            $this->budgetGuard->deductCredits($tenant, $featureKey);
            return $response;
        }

        // 🛡️ Failover Logic
        $forbiddenFailoverCodes = ['AI_MODEL_MISMATCH', 'AI_BUDGET_EXCEEDED', 'INVALID_INPUT', 'AI_RATE_LIMIT_EXCEEDED'];
        
        if (in_array($response->errorCode, $forbiddenFailoverCodes)) {
            return $response; // Hard Fail per SAAB policy
        }

        if ($request->getOption('no_fallback', false)) {
            return $response;
        }

        // ⚡ Fallback Call (OpenAI)
        \Illuminate\Support\Facades\Log::warning('AI_FAILOVER_TRIGGERED', [
            'reason' => $response->errorCode,
            'feature' => $request->getFeatureKey()
        ]);

        // Note: OpenAI call must adhere to the same DTO contract.
        // For simplicity here, we assume openAIService can handle CortexRequestData 
        // or we wrap it in a compatible way.
        return $this->fallbackToOpenAI($request);
    }

    protected function fallbackToOpenAI(\App\Application\AI\DTOs\CortexRequestData $request): \App\Application\AI\DTOs\CortexResponseData
    {
        \Illuminate\Support\Facades\Log::info('AI_FAILOVER_EXECUTING', [
            'provider' => 'openai',
            'feature' => $request->getFeatureKey()
        ]);

        $response = $this->openAIProvider->execute($request);
        
        if ($response->success) {
            $tenant = $this->tenantContextResolver->getTenant();
            $featureKey = $request->getOption('feature_key', 'general_ai');
            $this->budgetGuard->deductCredits($tenant, $featureKey);
        }

        return $response;
    }

    /**
     * 🏗️ High-level Listing Generation (SAAB v24.1)
     * Model-agnostic execution via Registry and Validator.
     */
    public function generateListing(array $input): \App\Services\AI\DTO\ListingAIResultData
    {
        $tenantContext = $this->tenantContextResolver->resolve();
        $promptTemplate = $this->promptRegistry->get('listing_generation');
        $systemPrompt = str_replace('{{INPUT}}', json_encode($input), $promptTemplate);
        
        $request = new \App\Application\AI\DTOs\CortexRequestData(
            taskType: \App\Domain\AI\Enums\AITaskType::ANALYZE_PROPERTY,
            input: ['prompt_instructions' => $systemPrompt],
            tenantContext: $tenantContext,
            model: config('ai.default_model'),
            meta: [
                'feature_key' => 'listing_generation',
                'temperature' => config('ai.temperature')
            ]
        );

        $response = $this->orchestrateAI($request);

        if (!$response->success) {
            throw new \App\Domain\AI\Exceptions\InvalidAIResponseException("AI_GENERATION_FAILED: " . $response->errorMessage);
        }

        // 🛡️ Final Contract Validation
        return $this->listingValidator->validate($response->rawText);
    }
}

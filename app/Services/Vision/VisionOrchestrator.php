<?php

namespace App\Services\Vision;

use App\DTOs\Vision\PublishingMediaDTO;
use App\DTOs\Vision\VisionAnalysisDTO;
use App\Events\Vision\MetadataExtracted;
use App\Events\Vision\PublishingPrepared;
use App\Events\Vision\VisionAnalyzed;
use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Services\Vision\Contracts\VisionProviderContract;
use App\Services\Vision\Providers\MockVisionProvider;
use App\Services\Vision\Providers\OpenAIVisionProvider;
use Illuminate\Support\Facades\Log;

/**
 * Vision Orchestrator — Sprint 6.4
 *
 * AI Vision pipeline'ını koordine eder:
 *
 *   1. Vision Provider (OpenAI / Mock)
 *   2. Fusion Engine (AI + Rule)
 *   3. Metadata Extraction
 *   4. Publishing Preparation
 *   5. Persist to DB
 *   6. Events
 *
 * SAB Authority:
 *   - Tek write authority — model yazma zincirine uyar.
 *   - Controller → VisionOrchestrator → VisionProvider / FusionEngine / MetadataExtractionService
 */
class VisionOrchestrator
{
    private ?VisionProviderContract $provider = null;

    public function __construct(
        private readonly VisionFusionEngine $fusionEngine,
        private readonly MetadataExtractionService $metadataExtractor,
        private readonly PublishingPreparationService $publishingService,
    ) {}

    /**
     * Bir fotoğrafı AI Vision ile analiz eder.
     */
    public function analyzePhoto(
        IlanFotografi $fotograf,
        bool $dispatchEvents = true,
    ): VisionAnalysisDTO {
        $ilan = $fotograf->ilan;

        // Step 1: Get provider
        $provider = $this->resolveProvider();

        // Step 2: AI Vision analysis
        $imagePath = $this->resolveImagePath($fotograf);
        $context = [
            'ilan_id'    => $ilan?->id,
            'fotograf_id' => $fotograf->id,
            'room_hint'  => $fotograf->oda_turu ?? 'other',
        ];

        $start = microtime(true);
        $aiResult = $provider->analyze($imagePath, $context);
        $latencyMs = (microtime(true) - $start) * 1000;

        // Step 3: Fusion (Rule Engine + AI)
        $fusion = $this->fusionEngine->fuse($fotograf, $aiResult);

        // Step 4: Merge fusion into DTO
        $finalResult = $this->mergeFusion($aiResult, $fusion);

        // Step 5: Persist to DB
        $this->persistPhotoAnalysis($fotograf, $finalResult, $aiResult);

        // Step 6: Events
        if ($dispatchEvents) {
            event(new VisionAnalyzed(
                ilan_id: $ilan?->id ?? 0,
                fotograf_id: $fotograf->id,
                analysis: $finalResult,
                provider: $provider->providerName(),
                latency_ms: $latencyMs,
            ));
        }

        return $finalResult;
    }

    /**
     * Bir ilandaki tüm fotoğrafları analiz eder.
     */
    public function analyzeIlan(
        int $ilanId,
        bool $dispatchEvents = true,
    ): array {
        $ilan = Ilan::with('fotograflar')->findOrFail($ilanId);
        $fotograflar = $ilan->fotograflar;

        if ($fotograflar->isEmpty()) {
            return [
                'ilan_id' => $ilanId,
                'analyzed' => 0,
                'analyses' => [],
                'publishing' => $this->publishingService->prepare($ilan, []),
            ];
        }

        // Step 1: AI batch analysis
        $provider = $this->resolveProvider();
        $aiResults = [];

        foreach ($fotograflar as $fotograf) {
            $imagePath = $this->resolveImagePath($fotograf);
            $context = [
                'ilan_id'     => $ilanId,
                'fotograf_id' => $fotograf->id,
                'room_hint'   => $fotograf->oda_turu ?? 'other',
            ];

            $start = microtime(true);
            $aiResult = $provider->analyze($imagePath, $context);
            $aiResults[$fotograf->id] = $aiResult;
        }

        // Step 2: Fusion for all photos
        $fusionResults = $this->fusionEngine->fuseBatch($fotograflar->all(), $aiResults);

        // Step 3: Build final DTOs + persist
        $analyses = [];
        foreach ($fotograflar as $fotograf) {
            $aiResult = $aiResults[$fotograf->id];
            $fusion = $fusionResults[$fotograf->id] ?? [];

            $finalResult = $this->mergeFusion($aiResult, $fusion);
            $analyses[$fotograf->id] = $finalResult;

            // Persist
            $this->persistPhotoAnalysis($fotograf, $finalResult, $aiResult);
        }

        // Step 4: Aggregate metadata
        $aggregated = $this->metadataExtractor->aggregateForIlan($ilan, $analyses);

        // Step 5: Publishing preparation
        $publishing = $this->publishingService->prepare($ilan, $analyses);

        // Step 6: Update ilan-level AI fields
        $this->persistIlanLevel($ilan, $publishing, $aggregated);

        // Step 7: Events
        if ($dispatchEvents) {
            $this->dispatchEvents($ilanId, $ilan, $analyses, $aggregated, $publishing);
        }

        return [
            'ilan_id'   => $ilanId,
            'analyzed' => count($analyses),
            'analyses' => $analyses,
            'aggregated' => $aggregated,
            'publishing' => $publishing,
        ];
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function resolveProvider(): VisionProviderContract
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $driver = config('vision.driver', 'mock');

        $this->provider = match ($driver) {
            'openai' => new OpenAIVisionProvider(),
            'mock'   => new MockVisionProvider(),
            default  => new MockVisionProvider(),
        };

        return $this->provider;
    }

    /**
     * Provider'ı test için override et.
     */
    public function setProvider(VisionProviderContract $provider): void
    {
        $this->provider = $provider;
    }

    private function resolveImagePath(IlanFotografi $fotograf): string
    {
        $path = $fotograf->dosya_yolu ?? '';

        if (empty($path)) {
            throw new \RuntimeException("Fotograf {$fotograf->id} için dosya yolu yok.");
        }

        if (str_starts_with($path, '/')) {
            return public_path(ltrim($path, '/'));
        }

        return public_path('storage/' . $path);
    }

    private function mergeFusion(VisionAnalysisDTO $aiResult, array $fusion): VisionAnalysisDTO
    {
        return new VisionAnalysisDTO(
            fotograf_id: $aiResult->fotograf_id,
            objects: $aiResult->objects,
            rooms: $aiResult->rooms,
            furniture: $aiResult->furniture,
            amenities: $aiResult->amenities,
            luxuryFeatures: $aiResult->luxuryFeatures,
            views: $aiResult->views,
            architecturalStyles: $aiResult->architecturalStyles,
            ai_quality_score: $aiResult->ai_quality_score,
            ai_quality_breakdown: $aiResult->ai_quality_breakdown,
            overall_confidence: $aiResult->overall_confidence,
            provider: $fusion['provider'] ?? $aiResult->provider,
            final_room_type: $fusion['oda_turu'] ?? $aiResult->final_room_type,
            fusion_confidence: (float) ($fusion['fusion_confidence'] ?? $aiResult->fusion_confidence),
            raw_response: $aiResult->raw_response,
            error: $aiResult->error,
        );
    }

    private function persistPhotoAnalysis(
        IlanFotografi $fotograf,
        VisionAnalysisDTO $finalResult,
        VisionAnalysisDTO $aiResult,
    ): void {
        $metadata = $this->metadataExtractor->extract($aiResult);

        $updateData = [
            'oda_turu'       => $finalResult->final_room_type ?? $fotograf->oda_turu,
            'oda_turu_guven' => $finalResult->fusion_confidence * 100,
            'vision_data'    => json_encode([
                'ai_quality_score' => $finalResult->ai_quality_score,
                'ai_confidence'    => $finalResult->overall_confidence,
                'provider'         => $finalResult->provider,
                'fusion_confidence' => $finalResult->fusion_confidence,
                'rooms'            => array_map(fn($r) => $r->toArray(), $finalResult->rooms),
                'luxury_features'  => array_map(fn($l) => $l->toArray(), $finalResult->luxuryFeatures),
                'metadata'         => $metadata,
                'analyzed_at'      => now()->toIso8601String(),
            ]),
        ];

        $fotograf->updateQuietly($updateData);
    }

    private function persistIlanLevel(
        Ilan $ilan,
        PublishingMediaDTO $publishing,
        array $aggregated,
    ): void {
        $ilan->forceFill([
            'vision_score'       => $publishing->vision_score,
            'vision_ai_confidence' => $publishing->avg_ai_confidence,
            'vision_rooms'       => json_encode($aggregated['oda_dagilimi'] ?? []),
            'vision_amenities'   => json_encode(array_keys($aggregated['ameniteler'] ?? [])),
            'vision_luxury'     => json_encode(array_keys($aggregated['lüks_ozellikler'] ?? [])),
            'vision_media'      => json_encode($publishing->toArray()),
        ])->saveQuietly();
    }

    private function dispatchEvents(
        int $ilanId,
        Ilan $ilan,
        array $analyses,
        array $aggregated,
        PublishingMediaDTO $publishing,
    ): void {
        // MetadataExtracted
        event(new MetadataExtracted(
            ilan_id: $ilanId,
            analyzed_photo_count: count($analyses),
            aggregated_metadata: $aggregated,
            room_distribution: $aggregated['oda_dagilimi'] ?? [],
            feature_summary: [
                'amenities'  => array_keys($aggregated['ameniteler'] ?? []),
                'luxury'     => array_keys($aggregated['lüks_ozellikler'] ?? []),
                'styles'     => array_keys($aggregated['mimari_stiller'] ?? []),
            ],
        ));

        // PublishingPrepared
        event(new PublishingPrepared(
            ilan_id: $ilanId,
            media: $publishing,
            is_ready: $publishing->is_publishing_ready,
            readiness_issues: $publishing->readiness_issues,
        ));
    }
}

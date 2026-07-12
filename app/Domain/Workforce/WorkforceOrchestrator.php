<?php

namespace App\Domain\Workforce;

use App\Domain\Workforce\DTO\WorkforceContext;
use App\Domain\Workforce\DTO\WorkforceResult;
use App\Domain\Workforce\Listing\ListingAgent;
use App\Domain\Workforce\Publishing\PublishingAgent;
use App\Enums\AgentStatus;

/**
 * WorkforceOrchestrator — Sprint 7.2
 *
 * ListingAgent → PublishingAgent zincirini koordine eder.
 * Tüm ajanları sırayla çalıştırır, sonuçları birleştirir.
 */
class WorkforceOrchestrator
{
    public function __construct(
        private readonly ListingAgent $listingAgent,
        private readonly PublishingAgent $publishingAgent,
    ) {}

    /**
     * Tam Workforce zincirini çalıştır.
     *
     * 1. ListingAgent → Workspace analiz
     * 2. PublishingAgent → Publishing package oluştur
     */
    public function run(WorkforceContext $context): WorkforceResult
    {
        // 1. ListingAgent
        $listingResult = $this->listingAgent->handle($context);

        if (!$listingResult->isOk()) {
            return $listingResult;
        }

        // 2. ListingAgent sonucunu context'e ekle (PublishingAgent için)
        $enrichedContext = $context->withSharedData([
            'ilan_id' => $listingResult->get('ilan_id'),
            'ilan_baslik' => $listingResult->get('ilan_baslik'),
            'listing_agent_result' => $listingResult->payload,
            'quality_score' => $listingResult->get('quality_score')['score'] ?? null,
            'publishing_ready' => $listingResult->get('publishing_readiness')['ready'] ?? false,
        ]);

        // 3. PublishingAgent
        $publishingResult = $this->publishingAgent->handle($enrichedContext);

        if (!$publishingResult->isOk()) {
            return $publishingResult;
        }

        // 4. Birleştirilmiş sonuç
        return WorkforceResult::success(
            agent: \App\Enums\AgentType::LISTING_AGENT, // Primary agent
            payload: [
                'listing' => $listingResult->payload,
                'publishing' => $publishingResult->payload,
                'ilan_id' => $listingResult->get('ilan_id'),
                'quality_score' => $listingResult->get('quality_score')['score'] ?? null,
                'publishing_ready' => $listingResult->get('publishing_readiness')['ready'] ?? false,
            ],
            metadata: [
                'listing_latency_ms' => $listingResult->get('latency_ms'),
                'publishing_latency_ms' => $publishingResult->get('latency_ms'),
                'total_agents' => 2,
            ],
        );
    }

    /**
     * Sadece ListingAgent çalıştır.
     */
    public function runListingOnly(WorkforceContext $context): WorkforceResult
    {
        return $this->listingAgent->handle($context);
    }

    /**
     * Sadece PublishingAgent çalıştır.
     */
    public function runPublishingOnly(WorkforceContext $context): WorkforceResult
    {
        return $this->publishingAgent->handle($context);
    }
}

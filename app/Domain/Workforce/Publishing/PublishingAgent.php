<?php

namespace App\Domain\Workforce\Publishing;

use App\Domain\Workforce\BaseWorkforceAgent;
use App\Domain\Workforce\DTO\WorkforceContext;
use App\Domain\Workforce\DTO\WorkforceResult;
use App\Domain\Workforce\Events\ListingAnalyzed;
use App\Enums\AgentType;
use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;

/**
 * PublishingAgent — Sprint 7.4
 *
 * ListingAgent sonucunu alir, PublishPackage olusturur,
 * kanallari yonlendirir ve sonuclari loglar.
 */
class PublishingAgent extends BaseWorkforceAgent
{
    public const AGENT_TYPE = AgentType::PUBLISHING_AGENT;

    public function __construct(
        private readonly ChannelRouter $router,
        private readonly NotificationHook $notifier,
    ) {
        parent::__construct(app(\App\Services\AI\YalihanCortex::class));
    }

    public function description(): string
    {
        return 'Yayinlama ajani: ListingAgent sonucunu alir, PublishPackage olusturur ve kanal yurutmesini tetikler.';
    }

    protected function execute(WorkforceContext $context): WorkforceResult
    {
        // ListingAgent sonucunu al
        $ilanId = $context->sharedData['ilan_id']
            ?? $context->workspace?->ilan_id
            ?? $context->workspace?->ilan?->getKey();

        $ilan = $ilanId ? Ilan::find($ilanId) : null;

        if (!$ilan) {
            return WorkforceResult::failure(
                agent: $this->getType(),
                error: 'Ilan ID bulunamadi',
            );
        }

        $listingResult = $context->sharedData['listing_agent_result'] ?? [];
        $qualityScore = $listingResult['quality_score']['score'] ?? 50;
        $publishingReady = $listingResult['publishing_readiness']['ready'] ?? false;
        $coverPhoto = $listingResult['publishing_readiness']['cover_photo'] ?? null;

        // Workspace al
        $workspace = PortfolioDriveWorkspace::where('ilan_id', $ilan->getKey())->first();

        // Kanal routing
        $channels = $this->router->route($ilan, $qualityScore);
        $routingExplanation = $this->router->explain($ilan, $qualityScore, $channels);

        // PublishPackage olustur
        $package = PublishPackage::build(
            ilan: $ilan,
            userId: auth()->id() ?? 0,
            qualityScore: $qualityScore,
            channels: $channels,
            payload: [
                'ilan_data' => $ilan->toArray(),
                'listing_result' => $listingResult,
            ],
            coverPhoto: $coverPhoto,
            metadata: [
                'routing' => $routingExplanation,
                'publishing_ready' => $publishingReady,
                'workspace_id' => $workspace?->getKey(),
            ],
        );

        // Workspace'e kaydet (audit trail)
        $this->persistPackage($workspace, $package);

        // Notification
        $this->notifier->notify($ilan, auth()->id() ?? 0, [], $package->id);

        // ListingAnalyzed event tetikle
        event(new ListingAnalyzed($workspace ?? $ilan, $package->toArray()));

        $this->log('PublishingAgent package olusturuldu', [
            'ilan_id' => $ilan->getKey(),
            'package_id' => $package->id,
            'channels' => array_map(fn($c) => $c->value, $channels),
            'quality_score' => $qualityScore,
            'publishing_ready' => $publishingReady,
        ]);

        return WorkforceResult::success(
            agent: $this->getType(),
            payload: [
                'ilan_id' => $ilan->getKey(),
                'package_id' => $package->id,
                'package' => $package->toArray(),
                'channels' => array_map(fn($c) => [
                    'value' => $c->value,
                    'label' => $c->label(),
                    'type' => $c->type(),
                    'threshold' => $c->minQualityScore(),
                ], $channels),
                'routing' => $routingExplanation,
                'publishing_ready' => $publishingReady,
                'quality_score' => $qualityScore,
            ],
            metadata: [
                'ilan_id' => $ilan->getKey(),
                'channel_count' => count($channels),
                'package_status' => $package->status,
            ],
        );
    }

    private function persistPackage(?PortfolioDriveWorkspace $workspace, PublishPackage $package): void
    {
        if (!$workspace) return;

        $meta = $workspace->metadata_json ?? [];
        $meta['last_publishing_package'] = $package->toArray();
        $meta['last_published_at'] = now()->toIso8601String();
        $workspace->updateQuietly(['metadata_json' => $meta]);
    }
}

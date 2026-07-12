<?php

namespace App\Domain\Workforce\Publishing;

use App\Domain\Workforce\BaseWorkforceAgent;
use App\Domain\Workforce\DTO\WorkforceContext;
use App\Domain\Workforce\DTO\WorkforceResult;
use App\Domain\Workforce\Events\ListingAnalyzed;
use App\Enums\AgentType;
use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Support\Facades\DB;

/**
 * PublishingAgent — Sprint 7.2 Phase 3
 *
 * ListingAgent sonucunu alır ve yayınlama package oluşturur.
 * Kanalları doğrudan bilmez — PublishingCapability ile çalışır.
 */
class PublishingAgent extends BaseWorkforceAgent
{
    public const AGENT_TYPE = AgentType::PUBLISHING_AGENT;

    public function __construct()
    {
        parent::__construct(app(\App\Services\AI\YalihanCortex::class));
    }

    public function description(): string
    {
        return 'Yayınlama ajanı: ListingAgent sonucunu alır, yayınlama package oluşturur ve kanal yürütmesini tetikler.';
    }

    protected function execute(WorkforceContext $context): WorkforceResult
    {
        $ilanId = $context->sharedData['ilan_id'] ?? $context->workspace?->ilan_id;

        if (!$ilanId) {
            return WorkforceResult::failure(
                agent: $this->getType(),
                error: 'İlan ID bulunamadı',
            );
        }

        $ilan = Ilan::find($ilanId);
        if (!$ilan) {
            return WorkforceResult::failure(
                agent: $this->getType(),
                error: "İlan #{$ilanId} bulunamadı",
            );
        }

        // Workspace al
        $workspace = PortfolioDriveWorkspace::where('ilan_id', $ilan->getKey())->first();

        // ListingAgent sonucunu al (sharedData'dan)
        $listingResult = $context->sharedData['listing_agent_result'] ?? [];

        // Publishing package oluştur
        $package = $this->buildPublishingPackage($ilan, $listingResult, $workspace);

        // Workspace'e kaydet (audit trail için)
        if ($workspace) {
            $this->savePublishingPackage($workspace, $package);
        }

        // ListingAnalyzed event'ini tetikle
        event(new ListingAnalyzed($workspace ?? $ilan, $package));

        $this->log('PublishingAgent package olusturuldu', [
            'ilan_id' => $ilan->getKey(),
            'package_id' => $package['id'],
            'channels' => implode(', ', $package['channels']),
        ]);

        return WorkforceResult::success(
            agent: $this->getType(),
            payload: [
                'ilan_id' => $ilan->getKey(),
                'package' => $package,
            ],
            metadata: [
                'ilan_id' => $ilan->getKey(),
                'channel_count' => count($package['channels']),
            ],
        );
    }

    /**
     * Yayınlama package oluştur.
     *
     * @param array<string, mixed> $listingResult
     */
    private function buildPublishingPackage(
        Ilan $ilan,
        array $listingResult,
        ?PortfolioDriveWorkspace $workspace
    ): array {
        // Kanal seçimi — yayın durumuna göre
        $channels = $this->selectChannels($ilan, $listingResult);

        // Paket ID
        $packageId = 'PKG-' . strtoupper(uniqid());

        return [
            'id' => $packageId,
            'ilan_id' => $ilan->getKey(),
            'workspace_id' => $workspace?->getKey(),
            'ilan_baslik' => $ilan->baslik,
            'ilan_fiyat' => $ilan->fiyat,
            'yayin_tipi' => $ilan->yayin_tipi,
            'kategori' => $ilan->kategori,
            'channels' => $channels,
            'quality_score' => $listingResult['quality_score']['score'] ?? null,
            'publishing_ready' => $listingResult['publishing_readiness']['ready'] ?? false,
            'blocking_issues' => $listingResult['publishing_readiness']['blocking_missing'] ?? [],
            'recommended_pack' => $listingResult['recommended_pack']['name'] ?? null,
            'status' => 'draft',
            'created_at' => now()->toIso8601String(),
            'created_by' => $this->resolveUserId(),
        ];
    }

    /**
     * Hangi kanallara yayınlanacağını belirle.
     *
     * @param array<string, mixed> $listingResult
     * @return array<string>
     */
    private function selectChannels(Ilan $ilan, array $listingResult): array
    {
        $channels = ['yalihan']; // Her zaman Yalıhan

        // Kalite skoru yüksekse dış kanalları ekle
        $qualityScore = $listingResult['quality_score']['score'] ?? 0;

        if ($qualityScore >= 60) {
            $channels[] = 'sahibinden';
        }

        if ($qualityScore >= 70) {
            $channels[] = 'hepsiemlak';
        }

        if ($qualityScore >= 80) {
            $channels[] = 'emlakjet';
        }

        // Yayın tipine göre ek kanallar
        $yayinTipi = mb_strtolower($ilan->yayin_tipi ?? '');

        if (str_contains($yayinTipi, 'kiralık') || str_contains($yayinTipi, 'günlük')) {
            // Kiralık için ek platformlar
            if ($qualityScore >= 75) {
                $channels[] = 'airbnb';
            }
        }

        return array_unique($channels);
    }

    /**
     * Publishing package'ı workspace'e kaydet.
     */
    private function savePublishingPackage(PortfolioDriveWorkspace $workspace, array $package): void
    {
        $metadata = $workspace->metadata_json ?? [];
        $metadata['publishing_package'] = $package;
        $workspace->updateQuietly(['metadata_json' => $metadata]);
    }

    /**
     * Kullanıcı ID'sini çöz.
     */
    private function resolveUserId(): ?int
    {
        return auth()->id();
    }
}

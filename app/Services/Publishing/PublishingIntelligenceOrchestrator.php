<?php

namespace App\Services\Publishing;

use App\Contracts\Publishing\ChannelAdapterContract;
use App\DTOs\Publishing\ChannelPayloadDTO;
use App\DTOs\Publishing\ChannelReadinessDTO;
use App\DTOs\Publishing\ChannelReadinessItem;
use App\DTOs\Publishing\PublishingDecisionDTO;
use App\Events\Publishing\PublishingPackageReady;
use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Publishing\Adapters\AirbnbAdapter;
use App\Services\Publishing\Adapters\HepsiemlakAdapter;
use App\Services\Publishing\Adapters\SahibindenAdapter;
use App\DTOs\Vision\PublishingMediaDTO;
use Illuminate\Support\Facades\Log;

/**
 * Publishing Intelligence Orchestrator — Sprint 6.5
 *
 * Tüm kanal adapter'larını koordine eder.
 * Business/channel kuralları BURADA yer alır — adapter'larda DEĞİL.
 *
 * Pipeline:
 *   1. Ilan verisi oku (TenantScope korunur — WITHOUT SCOPE YAZILMAZ)
 *   2. PublishDecisionAgent kararını oku
 *   3. AI Vision verilerini oku
 *   4. Her kanal için Adapter.buildPayload() çağır
 *   5. ChannelReadinessAssessment üret
 *   6. PublishingPackageReady event fırlat
 *   7. Dön (PUBLISH ETMEZ — sadece payload üretir)
 *
 * @rule Adapter sadece transform yapar — iş mantığı ORCHESTRATOR'da.
 * @rule Real API çağrısı YAZILMAZ (Sprint 6.6+).
 * @rule withoutGlobalScopes() KULLANILMAZ.
 */
class PublishingIntelligenceOrchestrator
{
    /** @var array<string, ChannelAdapterContract> */
    private array $adapters = [];

    public function __construct(
        private readonly AirbnbAdapter $airbnbAdapter,
        private readonly SahibindenAdapter $sahibindenAdapter,
        private readonly HepsiemlakAdapter $hepsiemlakAdapter,
    ) {
        $this->adapters = [
            'airbnb' => $this->airbnbAdapter,
            'sahibinden' => $this->sahibindenAdapter,
            'hepsiemlak' => $this->hepsiemlakAdapter,
        ];
    }

    /**
     * Ana orchestrasyon metodu.
     *
     * @param  Ilan  $ilan  TenantScope korunur
     * @param  array  $visionData  AI Vision çıktısı (vision_media JSON decode)
     * @param  PublishingDecisionDTO|null  $decision  Publish kararı
     * @param  PortfolioDriveWorkspace|null  $workspace  Workspace (opsiyonel)
     * @return PublishingPackage
     */
    public function orchestrate(
        Ilan $ilan,
        array $visionData = [],
        ?PublishingDecisionDTO $decision = null,
        ?PortfolioDriveWorkspace $workspace = null,
    ): PublishingPackage {
        $traceId = uniqid('pub_', true);
        $startTime = microtime(true);

        Log::info('PublishingIntelligenceOrchestrator: starting', [
            'ilan_id' => $ilan->id,
            'tenant_id' => $ilan->tenant_id,
            'trace_id' => $traceId,
            'has_workspace' => $workspace !== null,
            'has_vision' => !empty($visionData),
            'decision' => $decision?->decision,
        ]);

        // ─── Step 1: Karar kontrolü ─────────────────────────────────────────
        // Replay-safe: aynı ilan tekrar tetiklenirse aynı sonucu üretir.
        if ($decision && $decision->isRejected()) {
            Log::info('PublishingIntelligenceOrchestrator: rejected by decision', [
                'ilan_id' => $ilan->id,
                'blocking_issues' => $decision->blockingIssues,
            ]);

            return $this->buildRejectedPackage($ilan, $decision, $traceId);
        }

        // ─── Step 2: Vision media DTO hazırla ────────────────────────────────
        $media = $this->buildMediaDto($ilan, $visionData);

        // ─── Step 3: Her kanal için payload üret ────────────────────────────
        /** @var ChannelPayloadDTO[] $channelPayloads */
        $channelPayloads = [];
        $channelReadiness = [];

        foreach ($this->adapters as $channel => $adapter) {
            $payload = $this->buildPayloadForChannel(
                $ilan,
                $visionData,
                $decision,
                $adapter,
            );

            $channelPayloads[$channel] = $payload;

            // Readiness değerlendirmesi
            $channelReadiness[$channel] = $this->assessChannelReadiness(
                $ilan,
                $adapter,
                $payload,
            );
        }

        // ─── Step 4: Global readiness ───────────────────────────────────────
        $globalIssues = $this->collectGlobalIssues($ilan, $channelPayloads);
        $anyReady = count(array_filter($channelReadiness, fn($r) => $r->isReady())) > 0;

        $readinessDto = new ChannelReadinessDTO(
            ilanId: $ilan->id,
            channels: $channelReadiness,
            globalIssues: $globalIssues,
            isGloballyReady: $anyReady && empty($globalIssues),
        );

        // ─── Step 5: PublishingPackage oluştur ──────────────────────────────
        $package = new PublishingPackage(
            ilanId: $ilan->id,
            tenantId: $ilan->tenant_id ?? 0,
            workspaceId: $workspace?->getKey(),
            payloads: $channelPayloads,
            readiness: $readinessDto,
            decision: $decision,
            visionMedia: $media,
            traceId: $traceId,
            generatedAt: now()->toIso8601String(),
            elapsedMs: round((microtime(true) - $startTime) * 1000, 2),
        );

        // ─── Step 6: Event fırlat ───────────────────────────────────────────
        event(new PublishingPackageReady($package));

        Log::info('PublishingIntelligenceOrchestrator: completed', [
            'ilan_id' => $ilan->id,
            'trace_id' => $traceId,
            'ready_channels' => array_keys(array_filter(
                $channelPayloads,
                fn($p) => !$p->hasErrors()
            )),
            'elapsed_ms' => $package->elapsedMs,
        ]);

        return $package;
    }

    /**
     * Belirli bir kanal için payload üretir.
     */
    public function buildPayloadForChannel(
        Ilan $ilan,
        array $visionData,
        ?PublishingDecisionDTO $decision,
        ChannelAdapterContract $adapter,
    ): ChannelPayloadDTO {
        try {
            return $adapter->buildPayload($ilan, $visionData, $decision);
        } catch (\Throwable $e) {
            Log::warning('PublishingIntelligenceOrchestrator: adapter error', [
                'adapter' => $adapter->name(),
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ]);

            // Hata durumunda boş payload — fırlatma, errors'a yaz
            return new ChannelPayloadDTO(
                channel: $adapter->name(),
                ilanId: $ilan->id,
                mappedFields: [],
                errors: ['Adapter error: ' . $e->getMessage()],
            );
        }
    }

    /**
     * Channel readiness değerlendirmesi üretir.
     */
    private function assessChannelReadiness(
        Ilan $ilan,
        ChannelAdapterContract $adapter,
        ChannelPayloadDTO $payload,
    ): ChannelReadinessItem {
        $missingFields = $adapter->validate($ilan);
        $isReady = empty($missingFields) && !$payload->hasErrors();

        // Score: 0–100 (tamamlanan alan oranı)
        $requiredCount = count($adapter->requiredFields());
        $missingCount = count($missingFields);
        $filledCount = max(0, $requiredCount - $missingCount);
        $score = $requiredCount > 0
            ? (int) round(($filledCount / $requiredCount) * 100)
            : 0;

        return new ChannelReadinessItem(
            channel: $adapter->name(),
            isReady: $isReady,
            missingFields: $missingFields,
            warnings: $this->buildWarnings($ilan, $adapter),
            score: $score,
        );
    }

    /**
     * Adapter'a özel uyarılar üretir.
     *
     * @return string[]
     */
    private function buildWarnings(Ilan $ilan, ChannelAdapterContract $adapter): array
    {
        $warnings = [];

        // Fotoğraf yoksa uyar
        if ($ilan->fotograflar()->count() === 0) {
            $warnings[] = 'Fotoğraf yok — kanal görünürlüğü düşük olacak';
        }

        // Açıklama yoksa uyar
        if (empty($ilan->aciklama)) {
            $warnings[] = 'Açıklama eksik — AI description üretimi önerilir';
        }

        return $warnings;
    }

    /**
     * Tüm kanalları etkileyen global eksiklikleri toplar.
     *
     * @return string[]
     */
    private function collectGlobalIssues(Ilan $ilan, array $channelPayloads): array
    {
        $issues = [];

        // Fiyat yoksa tüm kanalları etkiler
        if (empty($ilan->fiyat) || $ilan->fiyat <= 0) {
            $issues[] = 'Fiyat tanımlanmamış — hiçbir kanala publish yapılamaz';
        }

        // Konum eksikse
        if (empty($ilan->il_id) && empty($ilan->il)) {
            $issues[] = 'İl bilgisi eksik — lokasyon bazlı kanallar etkilenir';
        }

        return $issues;
    }

    /**
     * Rejected paket oluşturur (replay-safe).
     */
    private function buildRejectedPackage(
        Ilan $ilan,
        PublishingDecisionDTO $decision,
        string $traceId,
    ): PublishingPackage {
        $emptyPayloads = [];
        foreach ($this->adapters as $channel => $adapter) {
            $emptyPayloads[$channel] = new ChannelPayloadDTO(
                channel: $channel,
                ilanId: $ilan->id,
                mappedFields: [],
                errors: ['Karar: rejected — ' . ($decision->blockingIssues[0]['message'] ?? 'bloke edilmiş')],
            );
        }

        $readiness = new ChannelReadinessDTO(
            ilanId: $ilan->id,
            channels: [],
            globalIssues: array_column($decision->blockingIssues, 'message'),
            isGloballyReady: false,
        );

        return new PublishingPackage(
            ilanId: $ilan->id,
            tenantId: $ilan->tenant_id ?? 0,
            workspaceId: null,
            payloads: $emptyPayloads,
            readiness: $readiness,
            decision: $decision,
            visionMedia: null,
            traceId: $traceId,
            generatedAt: now()->toIso8601String(),
            elapsedMs: 0,
        );
    }

    // ─── Vision Media DTO ────────────────────────────────────────────────────

    private function buildMediaDto(Ilan $ilan, array $visionData): ?PublishingMediaDTO
    {
        if (empty($visionData)) {
            return null;
        }

        if (isset($visionData['ilan_id'])) {
            return PublishingMediaDTO::fromArray($visionData);
        }

        $mediaData = $visionData['media'] ?? $visionData;
        if (is_array($mediaData) && !empty($mediaData)) {
            return PublishingMediaDTO::fromArray([
                'ilan_id' => $ilan->id,
                'hero_fotograf_id' => $visionData['hero_fotograf_id'] ?? null,
                'photo_order' => $visionData['photo_order'] ?? [],
                'title_hints' => $visionData['title_hints'] ?? [],
                'detected_rooms' => $visionData['detected_rooms'] ?? [],
                'detected_amenities' => $visionData['detected_amenities'] ?? [],
                'detected_luxury_features' => $visionData['detected_luxury_features'] ?? [],
                'vision_score' => $visionData['vision_score'] ?? 0,
                'avg_ai_confidence' => $visionData['avg_ai_confidence'] ?? 0.0,
            ]);
        }

        return null;
    }

    /**
     * Kayıtlı adapter'ları döner.
     *
     * @return array<string, ChannelAdapterContract>
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }
}

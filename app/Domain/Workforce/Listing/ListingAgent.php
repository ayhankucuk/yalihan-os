<?php

namespace App\Domain\Workforce\Listing;

use App\Domain\Workforce\BaseWorkforceAgent;
use App\Domain\Workforce\DTO\WorkforceContext;
use App\Domain\Workforce\DTO\WorkforceResult;
use App\Domain\Workforce\Support\FeaturePackRecommender;
use App\Domain\Workforce\Support\MissingFieldDetector;
use App\Domain\Workforce\Support\QualityScorer;
use App\Enums\AgentType;
use App\Models\FeaturePack;
use App\Models\Ilan;
use App\Models\Ozellik;

/**
 * ListingAgent — Sprint 7.2 Phase 2
 *
 * Workspace + Ilan verisini analiz eder:
 * 1. Feature Pack önerir
 * 2. Eksik alanları tespit eder
 * 3. Kalite skorunu hesaplar
 * 4. Yayına hazır durumunu belirler
 */
class ListingAgent extends BaseWorkforceAgent
{
    public const AGENT_TYPE = AgentType::LISTING_AGENT;

    public function __construct(
        private readonly FeaturePackRecommender $recommender,
        private readonly MissingFieldDetector $fieldDetector,
        private readonly QualityScorer $qualityScorer,
    ) {
        parent::__construct(app(\App\Services\AI\YalihanCortex::class));
    }

    public function description(): string
    {
        return 'İlan analiz ajanı: Workspace verisini analiz eder, Feature Pack önerir, eksik alanları tespit eder ve kalite skoru hesaplar.';
    }

    protected function execute(WorkforceContext $context): WorkforceResult
    {
        $workspace = $context->workspace;
        $ilan = $workspace?->ilan;

        if (!$ilan) {
            return WorkforceResult::failure(
                agent: $this->getType(),
                error: 'Workspace bağlı ilan bulunamadı',
            );
        }

        $ilanData = $this->extractIlanData($ilan);

        // 1. Feature Pack öner
        $recommendedPack = $this->recommender->recommend($ilanData, $ilan);

        // 2. Eksik alanları tespit et
        $missingFields = $this->fieldDetector->detect($ilan, $recommendedPack);

        // 3. Kalite skoru hesapla
        $quality = $this->qualityScorer->score($ilan, $ilanData, $missingFields);

        // 4. Yayına hazır mı?
        $publishingReadiness = $this->assessPublishingReadiness($ilan, $missingFields);

        $this->log('ListingAgent analiz tamamlandi', [
            'ilan_id' => $ilan->getKey(),
            'quality_score' => $quality['score'],
            'pack' => $recommendedPack?->name,
            'missing_count' => count($missingFields),
            'publishing_ready' => $publishingReadiness['ready'],
        ]);

        return WorkforceResult::success(
            agent: $this->getType(),
            payload: [
                'ilan_id' => $ilan->getKey(),
                'ilan_baslik' => $ilan->baslik,
                'ilan_data' => $ilanData,
                'recommended_pack' => $recommendedPack?->toArray(),
                'missing_fields' => $missingFields,
                'quality_score' => $quality,
                'publishing_readiness' => $publishingReadiness,
            ],
            metadata: [
                'ilan_id' => $ilan->getKey(),
                'pack_id' => $recommendedPack?->getKey(),
            ],
        );
    }

    /**
     * İlan verisini array olarak çıkar.
     *
     * @return array<string, mixed>
     */
    private function extractIlanData(Ilan $ilan): array
    {
        return [
            'id' => $ilan->getKey(),
            'baslik' => $ilan->baslik,
            'fiyat' => $ilan->fiyat,
            'brut_m2' => $ilan->brut_m2,
            'net_m2' => $ilan->net_m2,
            'bina_yasi' => $ilan->bina_yasi,
            'oda_sayisi' => $ilan->oda_sayisi,
            'banyo_sayisi' => $ilan->banyo_sayisi,
            'kat' => $ilan->kat,
            'toplam_kat' => $ilan->toplam_kat,
            'yayin_tipi' => $ilan->yayin_tipi,
            'kategori' => $ilan->kategori,
            'alt_kategori' => $ilan->alt_kategori,
            'esyali' => $ilan->esyali,
            'isitma' => $ilan->isinma_tipi ?? $ilan->isitma,
            'adres' => $ilan->adres,
            'lat' => $ilan->lat,
            'lng' => $ilan->lng,
            'ilan_no' => $ilan->ilan_no,
            'referans_no' => $ilan->referans_no,
        ];
    }

    /**
     * İlan yayına hazır mı?
     *
     * @param array<string> $missingFields
     */
    private function assessPublishingReadiness(Ilan $ilan, array $missingFields): array
    {
        $blocking = array_filter($missingFields, fn($f) => $f['severity'] === 'blocking');
        $advisory = array_filter($missingFields, fn($f) => $f['severity'] !== 'blocking');

        $ready = empty($blocking);

        return [
            'ready' => $ready,
            'can_review' => empty($advisory),
            'blocking_missing' => array_values($blocking),
            'advisory_missing' => array_values($advisory),
            'lifecycle_target' => $ready ? 'READY_FOR_PUBLISH' : 'NEEDS_REVIEW',
        ];
    }
}

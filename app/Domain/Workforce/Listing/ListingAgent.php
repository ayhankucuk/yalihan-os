<?php

namespace App\Domain\Workforce\Listing;

use App\Domain\Workforce\BaseWorkforceAgent;
use App\Domain\Workforce\DTO\WorkforceContext;
use App\Domain\Workforce\DTO\WorkforceResult;
use App\Domain\Workforce\Support\FeaturePackRecommender;
use App\Domain\Workforce\Support\MissingFieldDetector;
use App\Domain\Workforce\Support\PhotoIntelligence;
use App\Domain\Workforce\Support\PropertyDescriptionGenerator;
use App\Domain\Workforce\Support\QualityScorer;
use App\Enums\AgentType;
use App\Models\Ilan;
use App\Services\AI\YalihanCortex;

/**
 * ListingAgent — Sprint 7.2 + Sprint 7.3
 *
 * Bir Workspace'i alip Publishing Ready seviyesine getiren dijital calisan.
 *
 * Zincir:
 *  1. Feature Pack oneri
 *  2. Eksik alan tespiti
 *  3. AI aciklama uretimi
 *  4. Foto graf analizi
 *  5. Kalite skoru
 *  6. Publishing Ready karari
 */
class ListingAgent extends BaseWorkforceAgent
{
    public const AGENT_TYPE = AgentType::LISTING_AGENT;

    public function __construct(
        private readonly FeaturePackRecommender $recommender,
        private readonly MissingFieldDetector $fieldDetector,
        private readonly PropertyDescriptionGenerator $descriptionGenerator,
        private readonly PhotoIntelligence $photoIntelligence,
        private readonly QualityScorer $qualityScorer,
    ) {
        parent::__construct(app(YalihanCortex::class));
    }

    public function description(): string
    {
        return 'İlan analiz ajanı: Workspace\'i alır, Feature Pack önerir, açıklama üretir, fotoğraf analiz eder ve Publishing Ready sonucu döndürür.';
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

        // 1. Feature Pack oneri
        $recommendedPack = $this->recommender->recommend($ilanData, $ilan);

        // 2. Eksik alanlari tespit et
        $missingFields = $this->fieldDetector->detect($ilan, $recommendedPack);

        // 3. AI aciklama uretimi
        $description = $this->descriptionGenerator->generate($ilan);

        // 4. Foto graf analizi
        $photoAnalysis = $workspace
            ? $this->photoIntelligence->analyze($workspace)
            : ['toplam_foto' => 0, 'kapak_onerilen' => null, 'envanter' => [], 'eksikler' => [], 'puan' => 0, 'source' => 'no_workspace'];

        // 5. Kalite skoru (photo dahil)
        $quality = $this->qualityScorer->score(
            $ilan,
            $ilanData,
            $missingFields,
            $photoAnalysis
        );

        // 6. Publishing Readiness
        $publishingReadiness = $this->assessPublishingReadiness($ilan, $missingFields, $photoAnalysis, $description);

        $this->log('ListingAgent tamamlandi', [
            'ilan_id' => $ilan->getKey(),
            'quality_score' => $quality['score'],
            'pack' => $recommendedPack?->name,
            'missing_count' => count($missingFields),
            'photos' => $photoAnalysis['toplam_foto'],
            'publishing_ready' => $publishingReadiness['ready'],
        ]);

        return WorkforceResult::success(
            agent: $this->getType(),
            payload: [
                'ilan_id' => $ilan->getKey(),
                'ilan_baslik' => $ilan->baslik,
                'ilan_data' => $ilanData,

                // Feature Pack
                'recommended_pack' => $recommendedPack?->toArray(),

                // Eksik alanlar
                'missing_fields' => $missingFields,

                // AI Aciklama
                'description' => [
                    'baslik' => $description['baslik'],
                    'aciklama' => $description['aciklama'],
                    'kisa_aciklama' => $description['kisa_aciklama'],
                    'anahtar_kelimeler' => $description['anahtar_kelimeler'],
                    'source' => $description['source'] ?? 'template',
                ],

                // Foto graf Analizi
                'photo_analysis' => $photoAnalysis,

                // Kalite Skoru
                'quality_score' => $quality,

                // Publishing Ready
                'publishing_readiness' => $publishingReadiness,
            ],
            metadata: [
                'ilan_id' => $ilan->getKey(),
                'pack_id' => $recommendedPack?->getKey(),
                'photos_analyzed' => $photoAnalysis['toplam_foto'],
                'description_source' => $description['source'] ?? 'template',
            ],
        );
    }

    /**
     * İlan verisini array olarak cikar.
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
            'havuz_var' => $ilan->havuz_var,
            'site_icerisinde' => $ilan->site_icerisinde,
            'balkon' => $ilan->balkon ?? $ilan->balkon_var ?? null,
            'asansor' => $ilan->asansor ?? null,
            'otopark' => $ilan->otopark ?? null,
        ];
    }

    /**
     * Publishing Ready durumunu belirler.
     *
     * @param array<string, mixed> $description
     * @param array<string, mixed> $photoAnalysis
     * @param array<array{field: string, severity: string}> $missingFields
     */
    private function assessPublishingReadiness(
        Ilan $ilan,
        array $missingFields,
        array $photoAnalysis,
        array $description
    ): array {
        $blocking = array_filter($missingFields, fn($f) => $f['severity'] === 'blocking');
        $advisory = array_filter($missingFields, fn($f) => $f['severity'] !== 'blocking');

        // Foto graf blocking
        $photoBlocking = $photoAnalysis['toplam_foto'] === 0
            ? [['field' => 'photos', 'severity' => 'blocking', 'label' => 'Fotoğraf', 'reason' => 'En az bir fotoğraf gereklidir.']]
            : [];

        // Aciklama blocking — aciklama yok veya bos
        $descBlocking = empty($description['aciklama'])
            ? [['field' => 'aciklama', 'severity' => 'blocking', 'label' => 'Açıklama', 'reason' => 'İlan açıklaması boş bırakılamaz.']]
            : [];

        $allBlocking = array_merge($blocking, $photoBlocking, $descBlocking);

        // Uyarilar — advisory alanlar + eksik foto graf kategorileri
        $warnings = array_values($advisory);
        foreach ($photoAnalysis['eksikler'] ?? [] as $eksik) {
            $warnings[] = [
                'field' => 'photo_' . $eksik,
                'severity' => 'advisory',
                'label' => 'Fotoğraf: ' . ucfirst(str_replace('_', ' ', $eksik)),
                'reason' => ucfirst($eksik) . ' fotoğrafı eksik — eklenmesi önerilir.',
            ];
        }

        $ready = empty($allBlocking);

        return [
            'ready' => $ready,
            'can_review' => empty($allBlocking) && empty($warnings),
            'blocking_missing' => $allBlocking,
            'advisory_missing' => $warnings,
            'lifecycle_target' => $ready ? 'READY_FOR_PUBLISH' : 'NEEDS_REVIEW',
            'cover_photo' => $photoAnalysis['kapak_onerilen'] ?? null,
            'has_description' => !empty($description['aciklama']),
            'has_photos' => ($photoAnalysis['toplam_foto'] ?? 0) > 0,
        ];
    }
}

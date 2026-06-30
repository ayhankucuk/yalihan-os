<?php

namespace App\Services\Cortex;

use App\Governance\Metrics\GovernanceMetrics;
use App\Models\Ilan;
use App\Models\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use App\Enums\IlanDurumu;
use App\Services\Governance\DecisionComparator;
use App\Governance\Alerting\GovernanceAlerter;

/**
 * Cortex Matching Engine v1.0
 *
 * 🫡 KAPTAN'IN EMRETTİĞİ ZEKA KATMANI
 *
 * Bu servis, Lead'ler ile İlanlar arasındaki akıllı eşleşmeyi sağlar.
 * Context7 standartlarına (lat/lng, yayin_durumu) %100 uyumludur.
 *
 * @governance INTENTIONAL_CROSS_TENANT — global corpus ORM, MatchingEngine kasıtlı bypass (SAB authority.json §intentional_bypass)
 * @sealed 2025-12-31
 */
class MatchingEngine
{
    public function __construct(
        protected ?DecisionComparator $comparator = null,
        protected ?GovernanceAlerter $alerter = null
    ) {}

    /**
     * Bir Lead için en uygun ilanları bulur
     *
     * @param Lead $lead
     * @param int $limit
     * @return Collection
     */
    public function findMatchesForLead(Lead $lead, int $limit = 5): Collection
    {
        // Phase 4D: Inference Leakage Guard
        if ($this->comparator && $this->alerter) {
            // Lead -> Talep dönüşümü (Semantik Hizalama)
            // Eğer Lead ve Talep farklıysa burada mapping yapılmalı.
            // Şimdilik $lead'in Talep özelliklerini taşıdığını varsayıyoruz.
            $decision = $this->comparator->compareAndDecide($lead);

            // Drift Score'u telemetriye bas
            Log::info('[Phase 4D] Inference Drift Analysis', [
                'lead_id' => $lead->id,
                'drift_score' => $decision['comparison']['inference_drift'],
                'source' => $decision['final_decision_source'],
                'governance_domain' => 'MATCHING_DRIFT_RECOVERY'
            ]);

            // Kritik Sapma Alarmı
            if ($decision['comparison']['is_drift_detected']) {
                $this->alerter->createAlert(
                    'CRITICAL_INFERENCE_DRIFT',
                    array_merge($decision['comparison'], ['governance_domain' => 'MATCHING_DRIFT_RECOVERY']),
                    'high'
                );
            }

            // Fallback sonucu dön (Eğer drift yüksekse SQL motorundan gelir)
            if ($decision['final_decision_source'] === 'CONTROL_GROUP (SQL)') {
                $results = collect($decision['results'])->pluck('ilan')->take($limit);
            } else {
                $results = collect($decision['results'])->take($limit);
            }

            // ✅ Rozet Logic: Eğer doğrulama yapıldıysa Precision flag ekle
            $isVerified = $decision['is_precision_verified'] ?? false;
            $results->each(function($ilan) use ($isVerified) {
                $ilan->is_precision_verified = $isVerified;
            });

            return $results;
        }

        // [INTENTIONAL CROSS-TENANT] Matching engine operates on global active listing corpus.
        // Demand ↔ Supply matching requires cross-portfolio visibility by design.
        // This is NOT a security bypass — see docs/governance/PHASE4_SEMANTIC_CLASSIFICATION.md
        $ilanlar = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        // Phase 4C — Expected Bypass: Direct ORM write burada intentional.
        // Scoring sonucu çok sayıda ilan üzerinde toplu güncelleme gerektirir.
        // Repository Authority bypass — SEMANTIC CLASSIFICATION ile onaylı.
        // Ref: docs/governance/PHASE4_SEMANTIC_CLASSIFICATION.md
        // FIX #9: N+1 UPDATE → tek Upsert. 500 ilan = 1 sorgu (önceden 500 sorgu).
        GovernanceMetrics::increment('repository.expected_bypass', [
            'reason'     => 'cortex_score_bulk_upsert',
            'class'      => self::class,
            'tenant_id'  => null, // Global corpus — by design
        ]);

        $rankedAt = now();

        $matches = $ilanlar->map(function (Ilan $ilan) use ($lead, $rankedAt) {
            $scores = $this->calculateScores($lead, $ilan);

            return [
                'ilan'        => $ilan,
                'total_score' => $scores['total'],
                'breakdown'   => $scores['breakdown'],
            ];
        });

        // Tüm skorları tek sorguda yaz (N+1 → 1)
        $upsertRows = $matches->map(fn ($m) => [
            'id'               => $m['ilan']->id,
            'cortex_score'     => $m['total_score'],
            'cortex_ranked_at' => $rankedAt,
        ])->values()->toArray();

        if (!empty($upsertRows)) {
            Ilan::upsert($upsertRows, ['id'], ['cortex_score', 'cortex_ranked_at']);
        }

        return $matches->sortByDesc('total_score')->take($limit);
    }

    /**
     * Bir İlan için en uygun Lead'leri bulur
     *
     * @param Ilan $ilan
     * @param int $limit
     * @return Collection
     */
    public function findMatchesForIlan(Ilan $ilan, int $limit = 5): Collection
    {
        // [INTENTIONAL CROSS-TENANT] Lead corpus must be global for reverse matching.
        // A listing must be matchable against all qualified buyers, not just owner's leads.
        // This is NOT a security bypass — see docs/governance/PHASE4_SEMANTIC_CLASSIFICATION.md
        $leads = Lead::whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        $matches = $leads->map(function (Lead $lead) use ($ilan) {
            $scores = $this->calculateScores($lead, $ilan);

            return [
                'lead' => $lead,
                'total_score' => $scores['total'],
                'breakdown' => $scores['breakdown']
            ];
        });

        return $matches->sortByDesc('total_score')->take($limit);
    }

    /**
     * Skorlama Algoritması (Cortex Logic)
     */
    private function calculateScores(Lead $lead, Ilan $ilan): array
    {
        // 1. Lokasyon Skoru (%40)
        $distance = $this->calculateDistance(
            $lead->lat, $lead->lng,
            $ilan->lat, $ilan->lng
        );

        // 5km altı tam puan (100), sonrası doğrusal azalır (her km için 2 puan düşüş)
        $locationScore = $distance <= 5 ? 100 : max(0, 100 - ($distance - 5) * 2);

        // 2. Bütçe Skoru (%30)
        $budgetScore = 0;
        if ($ilan->fiyat >= $lead->butce_min && $ilan->fiyat <= $lead->butce_max) {
            $budgetScore = 100;
        } else {
            // Bütçe dışındaysa yakınlığa göre puan ver (%20 tolerans)
            $diff = 0;
            if ($ilan->fiyat < $lead->butce_min) {
                $diff = ($lead->butce_min - $ilan->fiyat) / $lead->butce_min;
            } else {
                $diff = ($ilan->fiyat - $lead->butce_max) / $lead->butce_max;
            }
            $budgetScore = max(0, 100 - ($diff * 200));
        }

        // 3. Özellik Skoru (%25)
        $featureScore = 0;
        $requestedRooms = $lead->ilgi_alanlari['oda_sayisi'] ?? null;

        if ($requestedRooms && $ilan->oda_sayisi == $requestedRooms) {
            $featureScore = 100;
        } else {
            // Oda sayısı yakınsa (örn: 3+1 vs 2+1) kısmi puan
            $diff = abs(($ilan->oda_sayisi ?? 0) - ($requestedRooms ?? 0));
            $featureScore = max(0, 100 - ($diff * 30));
        }

        // 4. Vision Kalite Skoru (%10)
        // quality_score 1-10 arası gelir, 100'lük sisteme çeviriyoruz
        $visionScore = ($ilan->quality_score ?? 5) * 10;

        // Ağırlıklı Toplam
        $totalScore = ($locationScore * 0.35) + ($budgetScore * 0.30) + ($featureScore * 0.25) + ($visionScore * 0.10);

        return [
            'total' => round($totalScore, 2),
            'breakdown' => [
                'location' => round($locationScore, 2),
                'budget' => round($budgetScore, 2),
                'features' => round($featureScore, 2),
                'vision' => round($visionScore, 2),
                'distance_km' => round($distance, 2)
            ]
        ];
    }

    /**
     * Haversine Formula (KM cinsinden mühürlü)
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371; // KM

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

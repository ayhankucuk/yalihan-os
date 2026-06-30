<?php

namespace App\Services\AI;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Models\Talep;
use App\Services\Logging\LogService;
use Illuminate\Support\Collection;

/**
 * Smart Property Matcher AI Service
 *
 * Context7: Talep ve Ilan modelleri arasında gelişmiş eşleştirme algoritması
 *
 * Özellikler:
 * - Weighted Scoring System (100 puan üzerinden)
 * - Hard Filtering (Status, Fiyat, Kategori)
 * - Soft Scoring (Konum, Fiyat Uyumu, Özellik Uyumu)
 * - Haversine Distance Calculation
 */
class SmartPropertyMatcherAI
{
    /**
     * Scoring weights (toplam 100 puan)
     */
    private const SCORE_LOCATION = 40;      // Konum puanı (max)

    private const SCORE_PRICE = 30;         // Fiyat uyumu (max)

    private const SCORE_FEATURES = 30;      // Özellik uyumu (max)

    /**
     * Hard filter parameters
     */
    private const PRICE_FLEXIBILITY = 0.20; // %20 esneme payı

    private const MAX_DISTANCE_KM = 10;      // Maksimum mesafe (km)

    private const MAX_RESULTS = 20;          // Maksimum sonuç sayısı

    /**
     * Tersine eşleştirme: İlan için uygun talepleri bul
     *
     * Context7: Reverse Matching - Yeni ilan eklendiğinde uygun talepleri bulur
     */
    public function reverseMatch(Ilan $ilan): array
    {
        $startTime = microtime(true);

        try {
            LogService::ai(
                'reverse_matching_started',
                'SmartPropertyMatcherAI',
                [
                    'ilan_id' => $ilan->id,
                    'ilan_baslik' => $ilan->baslik,
                ]
            );

            // 1. Hard Filtering - Uygun talepleri filtrele
            $filteredTalepler = $this->applyReverseHardFilters($ilan);

            if ($filteredTalepler->isEmpty()) {
                LogService::ai(
                    'reverse_matching_no_talepler_after_hard_filter',
                    'SmartPropertyMatcherAI',
                    [
                        'ilan_id' => $ilan->id,
                        'ilan_alt_kategori_id' => $ilan->alt_kategori_id,
                        'ilan_fiyat' => $ilan->fiyat,
                    ]
                );
                return [];
            }

            // 2. Soft Scoring - Her talep için puan hesapla
            $scoredResults = $this->calculateReverseScores($ilan, $filteredTalepler);

            // Debug: Tüm puanları logla (80'den düşük olanlar dahil)
            LogService::ai(
                'reverse_matching_scored_results',
                'SmartPropertyMatcherAI',
                [
                    'ilan_id' => $ilan->id,
                    'filtered_count' => $filteredTalepler->count(),
                    'all_scores' => collect($scoredResults)->pluck('score')->toArray(),
                    'scores_above_80' => collect($scoredResults)->filter(fn($r) => $r['score'] >= 80)->count(),
                ]
            );

            // 3. Sıralama ve limit (sadece 80+ puanlı olanlar)
            $sortedResults = collect($scoredResults)
                ->filter(fn($result) => $result['score'] >= 80)
                ->sortByDesc('score')
                ->values()
                ->toArray();

            $duration = microtime(true) - $startTime;

            LogService::ai(
                'reverse_matching_completed',
                'SmartPropertyMatcherAI',
                [
                    'ilan_id' => $ilan->id,
                    'results_count' => count($sortedResults),
                    'duration_ms' => round($duration * 1000, 2),
                ]
            );

            return $sortedResults;
        } catch (\Exception $e) {
            LogService::error(
                'Reverse matching failed',
                [
                    'ilan_id' => $ilan->id,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            return [];
        }
    }

    /**
     * Ana eşleştirme metodu
     */
    public function match(Talep $talep): array
    {
        $startTime = microtime(true);

        try {
            LogService::ai(
                'property_matching_started',
                'SmartPropertyMatcherAI',
                [
                    'talep_id' => $talep->id,
                    'talep_baslik' => $talep->baslik,
                    'kisi_id' => $talep->kisi_id,
                ]
            );

            // 1. Hard Filtering - Uygun ilanları filtrele
            $filteredIlans = $this->applyHardFilters($talep);

            if ($filteredIlans->isEmpty()) {
                LogService::ai(
                    'property_matching_no_results',
                    'SmartPropertyMatcherAI',
                    ['talep_id' => $talep->id]
                );

                return [];
            }

            // 2. Soft Scoring - Her ilan için puan hesapla
            $scoredResults = $this->calculateScores($talep, $filteredIlans);

            // 3. Sıralama ve limit
            $sortedResults = collect($scoredResults)
                ->sortByDesc('score')
                ->take(self::MAX_RESULTS)
                ->values()
                ->toArray();

            $duration = microtime(true) - $startTime;

            LogService::ai(
                'property_matching_completed',
                'SmartPropertyMatcherAI',
                [
                    'talep_id' => $talep->id,
                    'results_count' => count($sortedResults),
                    'duration_ms' => round($duration * 1000, 2),
                ]
            );

            return $sortedResults;
        } catch (\Exception $e) {
            LogService::error(
                'Property matching failed',
                [
                    'talep_id' => $talep->id,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            return [];
        }
    }

    /**
     * Tersine Hard Filtering - İlan için uygun talepleri filtrele
     */
    private function applyReverseHardFilters(Ilan $ilan): Collection
    {
        $query = Talep::query()
            ->where('talep_durumu', \App\Enums\TalepDurumu::AKTIF) // Context7: talep_durumu logic
            ->whereNull('deleted_at');

        // Kategori filtresi (zorunlu)
        if ($ilan->alt_kategori_id) {
            $query->where('alt_kategori_id', $ilan->alt_kategori_id);
        }

        // Fiyat filtresi (%20 esneme payı)
        // İlan fiyatı, talep min/max fiyat aralığında olmalı (esneme ile)
        if ($ilan->fiyat) {
            $ilanFiyat = $ilan->fiyat;
            $minPriceWithFlex = $ilanFiyat * (1 - self::PRICE_FLEXIBILITY); // %20 düşük
            $maxPriceWithFlex = $ilanFiyat * (1 + self::PRICE_FLEXIBILITY); // %20 yüksek

            $query->where(function ($q) use ($minPriceWithFlex, $maxPriceWithFlex) {
                // Talep min_fiyat kontrolü: Talep min_fiyat <= İlan fiyatı (esneme ile)
                // Yani talep min_fiyat çok yüksek olmamalı
                $q->where(function ($subQ) use ($maxPriceWithFlex) {
                    $subQ->whereNull('min_fiyat')
                        ->orWhere('min_fiyat', '<=', $maxPriceWithFlex);
                });

                // Talep max_fiyat kontrolü: Talep max_fiyat >= İlan fiyatı (esneme ile)
                // Yani talep max_fiyat çok düşük olmamalı
                $q->where(function ($subQ) use ($minPriceWithFlex) {
                    $subQ->whereNull('max_fiyat')
                        ->orWhere('max_fiyat', '>=', $minPriceWithFlex);
                });
            });
        }

        // Eager loading - Performans için
        $query->with([
            'kisi:id,ad,soyad',
            'danisman:id,name,email',
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'mahalle:id,mahalle_adi',
        ]);

        return $query->get();
    }

    /**
     * Tersine Soft Scoring - Her talep için puan hesapla
     */
    private function calculateReverseScores(Ilan $ilan, Collection $talepler): array
    {
        $results = [];

        foreach ($talepler as $talep) {
            $score = 0;
            $reasons = [];

            // 1. Konum Puanı (Max 40 puan) - Aynı mantık
            $locationScore = $this->calculateLocationScore($talep, $ilan);
            $score += $locationScore['score'];
            $reasons = array_merge($reasons, $locationScore['reasons']);

            // 2. Fiyat Uyumu (Max 30 puan) - Aynı mantık
            $priceScore = $this->calculatePriceScore($talep, $ilan);
            $score += $priceScore['score'];
            $reasons = array_merge($reasons, $priceScore['reasons']);

            // 3. Özellik Uyumu (Max 30 puan) - Aynı mantık
            $featureScore = $this->calculateFeatureScore($talep, $ilan);
            $score += $featureScore['score'];
            $reasons = array_merge($reasons, $featureScore['reasons']);

            // Toplam skor 100'ü geçmemeli
            $score = min(100, $score);

            $results[] = [
                'talep' => $talep,
                'score' => round($score, 2),
                'reasons' => $reasons,
                'breakdown' => [
                    'location' => $locationScore['score'],
                    'price' => $priceScore['score'],
                    'features' => $featureScore['score'],
                ],
            ];
        }

        return $results;
    }

    /**
     * Hard Filtering - Uygun ilanları filtrele
     *
     * Filtreler:
     * - Status: Sadece IlanDurumu::YAYINDA->value olanlar
     * - Fiyat: Talep min/max fiyatının %20 esneme payı dahilinde
     * - Kategori: alt_kategori_id eşleşmesi zorunlu
     */
    private function applyHardFilters(Talep $talep): Collection
    {
        $query = Ilan::query()
            ->where('yayin_durumu', \App\Enums\IlanDurumu::YAYINDA->value) // Context7: yayin_durumu for Ilan
            ->whereNull('deleted_at');

        // Kategori filtresi (zorunlu)
        if ($talep->alt_kategori_id) {
            $query->where('alt_kategori_id', $talep->alt_kategori_id);
        }

        // Fiyat filtresi (%20 esneme payı)
        if ($talep->min_fiyat || $talep->max_fiyat) {
            $query->where(function ($q) use ($talep) {
                // Min fiyat kontrolü
                if ($talep->min_fiyat) {
                    $minPriceWithFlex = $talep->min_fiyat * (1 - self::PRICE_FLEXIBILITY);
                    $q->where('fiyat', '>=', $minPriceWithFlex);
                }

                // Max fiyat kontrolü
                if ($talep->max_fiyat) {
                    $maxPriceWithFlex = $talep->max_fiyat * (1 + self::PRICE_FLEXIBILITY);
                    $q->where('fiyat', '<=', $maxPriceWithFlex);
                }
            });
        }

        // Eager loading - Performans için
        $query->with([
            'il:id,il_adi',
            'ilce:id,ilce_adi',
            'mahalle:id,mahalle_adi',
            'ozellikler:id,slug,name',
        ]);

        return $query->get();
    }

    /**
     * Soft Scoring - Her ilan için puan hesapla
     */
    private function calculateScores(Talep $talep, Collection $ilans): array
    {
        $results = [];

        foreach ($ilans as $ilan) {
            $score = 0;
            $reasons = [];

            // 1. Konum Puanı (Max 40 puan)
            $locationScore = $this->calculateLocationScore($talep, $ilan);
            $score += $locationScore['score'];
            $reasons = array_merge($reasons, $locationScore['reasons']);

            // 2. Fiyat Uyumu (Max 30 puan)
            $priceScore = $this->calculatePriceScore($talep, $ilan);
            $score += $priceScore['score'];
            $reasons = array_merge($reasons, $priceScore['reasons']);

            // 3. Özellik Uyumu (Max 30 puan)
            $featureScore = $this->calculateFeatureScore($talep, $ilan);
            $score += $featureScore['score'];
            $reasons = array_merge($reasons, $featureScore['reasons']);

            // Toplam skor 100'ü geçmemeli
            $score = min(100, $score);

            $results[] = [
                'ilan' => $ilan,
                'score' => round($score, 2),
                'reasons' => $reasons,
                'breakdown' => [
                    'location' => $locationScore['score'],
                    'price' => $priceScore['score'],
                    'features' => $featureScore['score'],
                ],
            ];
        }

        return $results;
    }

    /**
     * Konum Puanı Hesaplama (Max 40 puan)
     *
     * - İlçe ID aynıysa: 40 puan
     * - İl ID aynıysa: 20 puan
     * - Koordinatlar varsa: Haversine ile 10km çapa kadar puan
     */
    private function calculateLocationScore(Talep $talep, Ilan $ilan): array
    {
        $score = 0;
        $reasons = [];

        // İlçe ID eşleşmesi (tam puan)
        if ($talep->ilce_id && $ilan->ilce_id && $talep->ilce_id === $ilan->ilce_id) {
            $score = self::SCORE_LOCATION;
            $ilceAdi = $ilan->ilce->ilce_adi ?? 'N/A';
            $reasons[] = "Aynı ilçe ({$ilceAdi})";

            return ['score' => $score, 'reasons' => $reasons];
        }

        // İl ID eşleşmesi (yarı puan)
        if ($talep->il_id && $ilan->il_id && $talep->il_id === $ilan->il_id) {
            $score = self::SCORE_LOCATION / 2;
            $ilAdi = $ilan->il->il_adi ?? 'N/A';
            $reasons[] = "Aynı il ({$ilAdi})";
        }

        // Koordinat bazlı mesafe hesaplama
        if ($this->hasCoordinates($talep) && $this->hasCoordinates($ilan)) {
            // Talep koordinatları
            $talepLat = $this->getLatitude($talep);
            $talepLng = $this->getLongitude($talep);

            // Ilan koordinatları
            $ilanLat = $ilan->latitude ?? $ilan->lat ?? null;
            $ilanLng = $ilan->longitude ?? $ilan->lng ?? null;

            $distance = $this->calculateHaversineDistance(
                $talepLat,
                $talepLng,
                $ilanLat,
                $ilanLng
            );

            if ($distance !== null && $distance <= self::MAX_DISTANCE_KM) {
                // Mesafe azaldıkça puan artar (10km = 0 puan, 0km = 20 puan)
                $distanceScore = max(0, self::SCORE_LOCATION / 2 * (1 - ($distance / self::MAX_DISTANCE_KM)));
                $score = max($score, $distanceScore);
                $reasons[] = "Yakın mesafe ({$distance} km)";
            }
        }

        return [
            'score' => round($score, 2),
            'reasons' => $reasons,
        ];
    }

    /**
     * Fiyat Uyumu Hesaplama (Max 30 puan)
     *
     * - Tam bütçe ortasındaysa: 30 puan
     * - Sınırlara yaklaştıkça: Puan düşer
     */
    private function calculatePriceScore(Talep $talep, Ilan $ilan): array
    {
        $score = 0;
        $reasons = [];

        if (! $talep->min_fiyat && ! $talep->max_fiyat) {
            // Fiyat kriteri yoksa, orta puan ver
            return ['score' => self::SCORE_PRICE / 2, 'reasons' => ['Fiyat kriteri belirtilmemiş']];
        }

        if (! $ilan->fiyat) {
            return ['score' => 0, 'reasons' => ['İlan fiyatı belirtilmemiş']];
        }

        $ilanFiyat = (float) $ilan->fiyat;

        // Bütçe ortası hesapla
        if ($talep->min_fiyat && $talep->max_fiyat) {
            $budgetCenter = ($talep->min_fiyat + $talep->max_fiyat) / 2;
            $budgetRange = $talep->max_fiyat - $talep->min_fiyat;

            // Bütçe ortasına ne kadar yakınsa o kadar yüksek puan
            $distanceFromCenter = abs($ilanFiyat - $budgetCenter);
            $normalizedDistance = $budgetRange > 0 ? ($distanceFromCenter / $budgetRange) : 0;

            // 0-1 arası normalized distance, 1'e yaklaştıkça puan düşer
            $score = self::SCORE_PRICE * (1 - min(1, $normalizedDistance));

            if ($normalizedDistance < 0.1) {
                $reasons[] = 'Bütçe ortasında';
            } elseif ($normalizedDistance < 0.3) {
                $reasons[] = 'Bütçeye yakın';
            } else {
                $reasons[] = 'Bütçe sınırlarında';
            }
        } elseif ($talep->min_fiyat) {
            // Sadece min fiyat var
            if ($ilanFiyat >= $talep->min_fiyat) {
                $score = self::SCORE_PRICE;
                $reasons[] = 'Minimum fiyat kriterini karşılıyor';
            } else {
                $score = 0;
                $reasons[] = 'Minimum fiyatın altında';
            }
        } elseif ($talep->max_fiyat) {
            // Sadece max fiyat var
            if ($ilanFiyat <= $talep->max_fiyat) {
                $score = self::SCORE_PRICE;
                $reasons[] = 'Maksimum fiyat kriterini karşılıyor';
            } else {
                $score = 0;
                $reasons[] = 'Maksimum fiyatın üstünde';
            }
        }

        return [
            'score' => round($score, 2),
            'reasons' => $reasons,
        ];
    }

    /**
     * Özellik Uyumu Hesaplama (Max 30 puan)
     *
     * Talep'in aranan_ozellikler_json alanındaki özellikler ile
     * İlan'ın özellikleri karşılaştırılır.
     */
    private function calculateFeatureScore(Talep $talep, Ilan $ilan): array
    {
        $score = 0;
        $reasons = [];

        // Talep'te aranan özellikler
        $arananOzellikler = $talep->aranan_ozellikler_json ?? [];

        if (empty($arananOzellikler)) {
            // Aranan özellik yoksa, orta puan ver
            return ['score' => self::SCORE_FEATURES / 2, 'reasons' => ['Özellik kriteri belirtilmemiş']];
        }

        // İlan'ın özelliklerini al (slug bazlı)
        $ilanOzellikleri = $ilan->ozellikler->pluck('slug')->toArray();

        if (empty($ilanOzellikleri)) {
            return ['score' => 0, 'reasons' => ['İlan özellik bilgisi yok']];
        }

        // Eşleşen özellik sayısı
        $matchedFeatures = 0;
        $matchedFeatureNames = [];

        foreach ($arananOzellikler as $arananOzellik) {
            // Aranan özellik string veya array olabilir
            $featureSlug = is_array($arananOzellik)
                ? ($arananOzellik['slug'] ?? $arananOzellik['name'] ?? null)
                : $arananOzellik;

            if ($featureSlug && in_array($featureSlug, $ilanOzellikleri)) {
                $matchedFeatures++;
                $matchedFeatureNames[] = $featureSlug;
            }
        }

        // Puan hesapla: Eşleşen özellik sayısı / Toplam aranan özellik sayısı
        $totalFeatures = count($arananOzellikler);
        $matchRatio = $totalFeatures > 0 ? ($matchedFeatures / $totalFeatures) : 0;
        $score = self::SCORE_FEATURES * $matchRatio;

        if ($matchedFeatures > 0) {
            $reasons[] = "{$matchedFeatures}/{$totalFeatures} özellik eşleşti: " . implode(', ', $matchedFeatureNames);
        } else {
            $reasons[] = 'Özellik eşleşmesi yok';
        }

        return [
            'score' => round($score, 2),
            'reasons' => $reasons,
        ];
    }

    /**
     * Haversine mesafe hesaplama (km)
     */
    private function calculateHaversineDistance(
        ?float $lat1,
        ?float $lon1,
        ?float $lat2,
        ?float $lon2
    ): ?float {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        // Dünya yarıçapı (km)
        $earthRadius = 6371;

        // Dereceyi radyana çevir
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        // Haversine formülü
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Mesafe (km)
        return $earthRadius * $c;
    }

    /**
     * Koordinat kontrolü
     *
     * @param  Talep|Ilan  $model
     */
    private function hasCoordinates($model): bool
    {
        // Ilan için: latitude/longitude veya lat/lng
        if ($model instanceof Ilan) {
            $lat = $model->latitude ?? $model->lat ?? null;
            $lng = $model->longitude ?? $model->lng ?? null;

            return $lat !== null && $lng !== null;
        }

        // Talep için: metadata içinde koordinat olabilir veya koordinat_lat/koordinat_lng
        if ($model instanceof Talep) {
            // Önce metadata kontrolü
            if ($model->metadata && is_array($model->metadata)) {
                $lat = $model->metadata['latitude'] ?? $model->metadata['lat'] ?? null;
                $lng = $model->metadata['longitude'] ?? $model->metadata['lng'] ?? null;
                if ($lat !== null && $lng !== null) {
                    return true;
                }
            }

            // Sonra direkt alan kontrolü (eğer varsa)
            $lat = $model->koordinat_lat ?? $model->latitude ?? $model->lat ?? null;
            $lng = $model->koordinat_lng ?? $model->longitude ?? $model->lng ?? null;

            return $lat !== null && $lng !== null;
        }

        return false;
    }

    /**
     * Talep'ten latitude değerini al
     */
    private function getLatitude(Talep $talep): ?float
    {
        // Metadata kontrolü
        if ($talep->metadata && is_array($talep->metadata)) {
            $lat = $talep->metadata['latitude'] ?? $talep->metadata['lat'] ?? null;
            if ($lat !== null) {
                return (float) $lat;
            }
        }

        // Direkt alan kontrolü
        return $talep->koordinat_lat ?? $talep->latitude ?? $talep->lat ?? null;
    }

    /**
     * Talep'ten longitude değerini al
     */
    private function getLongitude(Talep $talep): ?float
    {
        // Metadata kontrolü
        if ($talep->metadata && is_array($talep->metadata)) {
            $lng = $talep->metadata['longitude'] ?? $talep->metadata['lng'] ?? null;
            if ($lng !== null) {
                return (float) $lng;
            }
        }

        // Direkt alan kontrolü
        return $talep->koordinat_lng ?? $talep->longitude ?? $talep->lng ?? null;
    }
}

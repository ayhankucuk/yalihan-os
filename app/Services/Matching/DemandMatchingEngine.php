<?php

namespace App\Services\Matching;

use App\Models\Ilan;
use App\Models\Talep;
use App\Enums\IlanDurumu;
use App\Enums\TalepDurumu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 YALIHAN EŞLEŞME MOTORU 1.0
 *
 * Demand Matching Engine - Arz ile Talep arasında matematiksel eşleştirme
 * Context7: Type-safe, skorlama disiplinli, telemetri uyumlu
 *
 * Skorlama Sistemi (0-100):
 * - 100: Mükemmel eşleşme
 * - 80-99: Yüksek uyum
 * - 60-79: Orta uyum (Potansiyel)
 * - 40-59: Düşük uyum
 * - 0-39: Uyumsuz
 *
 * @governance INTENTIONAL_CROSS_TENANT — global corpus ORM, DemandMatchingEngine kasıtlı bypass (SAB authority.json §intentional_bypass)
 */
class DemandMatchingEngine
{
    /**
     * Minimum eşleşme skoru (altındakiler gösterilmez)
     */
    const MIN_MATCH_SCORE = 40;

    /**
     * Fiyat tolerans yüzdesi (±)
     */
    const PRICE_TOLERANCE = 0.15; // %15

    /**
     * 🎯 Ana Eşleştirme Metodu
     *
     * Bir talep için uygun ilanları bulur ve skorlar
     *
     * @param Talep $talep
     * @param int $limit Maksimum sonuç sayısı
     * @return Collection Skorlanmış ilanlar
     */
    public function matchDemand(Talep $talep, int $limit = 10): Collection
    {
        // 🔍 1. SÜZGEÇ KATMANI: SQL seviyesinde ön filtreleme (TASK-2 Hardening)
        $ilanlar = $this->getIlanCandidates($talep);

        // 🎯 2. SKORLAMA: Her ilan için uyum skoru hesapla
        $skorlanmisIlanlar = $ilanlar->map(function ($ilan) use ($talep) {
            $skor = $this->calculateMatchScore($ilan, $talep);

            return [
                'ilan' => $ilan,
                'skor' => $skor,
                'detay' => $this->getMatchDetails($ilan, $talep, $skor),
            ];
        });

        // 🔥 3. FİLTRELEME: Minimum skoru geçenleri al
        $uygunIlanlar = $skorlanmisIlanlar
            ->filter(fn($item) => $item['skor'] >= self::MIN_MATCH_SCORE)
            ->sortByDesc('skor')
            ->take($limit);

        // 📊 4. TELEMETRI: Log kaydet
        Log::info('🎯 Matching Engine', [
            'talep_id' => $talep->id,
            'toplam_ilan' => $ilanlar->count(),
            'uygun_ilan' => $uygunIlanlar->count(),
            'en_yuksek_skor' => $uygunIlanlar->first()['skor'] ?? 0,
        ]);

        // 🎯 5. n8n BİLDİRİMİ: %90+ skorlu eşleşmeleri bildir
        if (config('n8n.enabled')) {
            $webhookService = app(\App\Services\Notification\N8nWebhookService::class);

            foreach ($uygunIlanlar as $eslesen) {
                if ($eslesen['skor'] >= config('n8n.thresholds.min_match_score', 90)) {
                    $webhookService->notifyHighMatch(
                        $eslesen['ilan'],
                        $talep,
                        $eslesen['skor'],
                        $eslesen['detay']
                    );
                }
            }
        }

        return $uygunIlanlar;
    }

    /**
     * 🧮 Eşleşme Skoru Hesaplama (0-100)
     *
     * 🤖 PHASE 8 - SPRINT 2: AI-Powered Dynamic Weights
     *
     * Ağırlıklar artık geçmiş başarılı eşleşmelerden öğrenilerek
     * otomatik olarak optimize ediliyor!
     *
     * Varsayılan ağırlıklar (fallback):
     * - Lokasyon: %40
     * - Fiyat: %30
     * - Kategori: %20
     * - Metrekare: %10
     *
     * @param Ilan $ilan
     * @param Talep $talep
     * @return float
     */
    protected function calculateMatchScore(Ilan $ilan, Talep $talep): float
    {
        // 🤖 AI-Powered: Dinamik ağırlıkları al (talep bazlı)
        $optimizer = app(MatchingWeightsOptimizer::class);
        $weights = $optimizer->getOptimizedWeights($talep);

        $skorlar = [];

        // 🗺️ 1. LOKASYON SKORU (dinamik ağırlık)
        $skorlar['lokasyon'] = $this->calculateLocationScore($ilan, $talep) * $weights['lokasyon'];

        // 💰 2. FİYAT SKORU (dinamik ağırlık)
        $skorlar['fiyat'] = $this->calculatePriceScore($ilan, $talep) * $weights['fiyat'];

        // 🏠 3. KATEGORİ SKORU (dinamik ağırlık)
        $skorlar['kategori'] = $this->calculateCategoryScore($ilan, $talep) * $weights['kategori'];

        // 📐 4. METREKARE SKORU (dinamik ağırlık)
        $skorlar['metrekare'] = $this->calculateAreaScore($ilan, $talep) * $weights['metrekare'];

        // 🧠 5. SEMANTİK BONUS (Phase 8 - Sprint 3)
        // Açıklama metinlerindeki ortak kelimeleri değerlendir
        $semanticBonus = $optimizer->calculateSimpleSemanticBonus($ilan, $talep);
        $skorlar['semantic'] = $semanticBonus; // 0-20 arası bonus

        // 🎯 TOPLAM SKOR (base score + semantic bonus)
        $baseScore = array_sum(array_filter($skorlar, fn($key) => $key !== 'semantic', ARRAY_FILTER_USE_KEY));
        $totalScore = $baseScore + $semanticBonus;

        return round($totalScore, 2);
    }

    /**
     * 🗺️ Lokasyon Eşleşme Skoru
     *
     * Coğrafi kilit sistemi:
     * - Aynı mahalle: 100
     * - Aynı ilçe: 80
     * - Aynı il: 60
     * - Farklı il: 0
     */
    protected function calculateLocationScore(Ilan $ilan, Talep $talep): float
    {
        // İl kontrolü (zorunlu)
        if ($talep->il_id && $ilan->il_id !== $talep->il_id) {
            return 0; // Farklı il = Eşleşme yok
        }

        // Mahalle eşleşmesi (en yüksek skor)
        if ($talep->mahalle_id && $ilan->mahalle_id === $talep->mahalle_id) {
            return 100;
        }

        // İlçe eşleşmesi
        if ($talep->ilce_id && $ilan->ilce_id === $talep->ilce_id) {
            return 80;
        }

        // Sadece il eşleşmesi
        if ($talep->il_id && $ilan->il_id === $talep->il_id) {
            return 60;
        }

        // Lokasyon belirtilmemiş
        return 50;
    }

    /**
     * 💰 Fiyat Eşleşme Skoru
     *
     * Toleranslı fiyat eşleşmesi:
     * - Bütçe içinde: 100
     * - %15 tolerans içinde: 90
     * - %30 tolerans içinde: 70
     * - Dışında: 0
     */
    protected function calculatePriceScore(Ilan $ilan, Talep $talep): float
    {
        // Talep fiyat aralığı yoksa
        if (!$talep->min_fiyat && !$talep->max_fiyat) {
            return 50; // Nötr skor
        }

        $ilanFiyat = $ilan->fiyat;

        // Min fiyat kontrolü
        if ($talep->min_fiyat && $ilanFiyat < $talep->min_fiyat) {
            $fark = ($talep->min_fiyat - $ilanFiyat) / $talep->min_fiyat;

            if ($fark <= self::PRICE_TOLERANCE) {
                return 90; // %15 tolerans içinde
            } elseif ($fark <= 0.30) {
                return 70; // %30 tolerans içinde
            }

            return 0; // Çok düşük
        }

        // Max fiyat kontrolü
        if ($talep->max_fiyat && $ilanFiyat > $talep->max_fiyat) {
            $fark = ($ilanFiyat - $talep->max_fiyat) / $talep->max_fiyat;

            if ($fark <= self::PRICE_TOLERANCE) {
                return 90; // %15 tolerans içinde
            } elseif ($fark <= 0.30) {
                return 70; // %30 tolerans içinde
            }

            return 0; // Çok yüksek
        }

        // Bütçe içinde
        return 100;
    }

    /**
     * 🏠 Kategori Eşleşme Skoru
     *
     * İlan kategorisi ile talep kategorisi eşleşmesi
     */
    protected function calculateCategoryScore(Ilan $ilan, Talep $talep): float
    {
        if (!$talep->alt_kategori_id) {
            return 50; // Kategori belirtilmemiş
        }

        // Tam kategori eşleşmesi
        if ($ilan->alt_kategori_id === $talep->alt_kategori_id) {
            return 100;
        }

        // Ana kategori eşleşmesi kontrolü (parent_id)
        if ($ilan->kategori && $talep->altKategori) {
            if ($ilan->kategori->parent_id === $talep->altKategori->parent_id) {
                return 70; // Aynı ana kategori
            }
        }

        return 0; // Kategori uyumsuz
    }

    /**
     * 📐 Metrekare Eşleşme Skoru
     *
     * Alan toleranslı eşleşme
     */
    protected function calculateAreaScore(Ilan $ilan, Talep $talep): float
    {
        // Talep metrekare aralığı yoksa
        if (!$talep->min_metrekare && !$talep->max_metrekare) {
            return 50; // Nötr skor
        }

        $ilanMetrekare = $ilan->metrekare;

        // Min metrekare kontrolü
        if ($talep->min_metrekare && $ilanMetrekare < $talep->min_metrekare) {
            $fark = ($talep->min_metrekare - $ilanMetrekare) / $talep->min_metrekare;

            if ($fark <= 0.10) {
                return 90; // %10 tolerans
            }

            return 50; // Küçük ama kabul edilebilir
        }

        // Max metrekare kontrolü
        if ($talep->max_metrekare && $ilanMetrekare > $talep->max_metrekare) {
            $fark = ($ilanMetrekare - $talep->max_metrekare) / $talep->max_metrekare;

            if ($fark <= 0.10) {
                return 90; // %10 tolerans
            }

            return 50; // Büyük ama kabul edilebilir
        }

        // Aralık içinde
        return 100;
    }

    /**
     * 📊 Eşleşme Detaylarını Al
     *
     * Skorun nasıl hesaplandığını açıklar
     */
    protected function getMatchDetails(Ilan $ilan, Talep $talep, float $skor): array
    {
        return [
            'genel_skor' => $skor,
            'kategori' => $this->getScoreCategory($skor),
            'lokasyon_uyumu' => $this->calculateLocationScore($ilan, $talep),
            'fiyat_uyumu' => $this->calculatePriceScore($ilan, $talep),
            'kategori_uyumu' => $this->calculateCategoryScore($ilan, $talep),
            'metrekare_uyumu' => $this->calculateAreaScore($ilan, $talep),
            'aciklama' => $this->getScoreExplanation($skor),
        ];
    }

    /**
     * 🏷️ Skor Kategorisi
     */
    protected function getScoreCategory(float $skor): string
    {
        return match (true) {
            $skor >= 90 => 'Mükemmel Eşleşme',
            $skor >= 80 => 'Yüksek Uyum',
            $skor >= 60 => 'Orta Uyum (Potansiyel)',
            $skor >= 40 => 'Düşük Uyum',
            default => 'Uyumsuz',
        };
    }

    /**
     * 📝 Skor Açıklaması
     */
    protected function getScoreExplanation(float $skor): string
    {
        return match (true) {
            $skor >= 90 => 'Bu ilan taleple neredeyse tam uyumlu. Hemen görüşme ayarlayın!',
            $skor >= 80 => 'Yüksek uyum var. Müşteriye sunulabilir.',
            $skor >= 60 => 'Potansiyel eşleşme. Detayları kontrol edin.',
            $skor >= 40 => 'Düşük uyum. Alternatif olarak değerlendirilebilir.',
            default => 'Bu ilan taleple uyumlu değil.',
        };
    }

    /**
     * 🔄 REVERSE MATCHING: İlan için Potansiyel Alıcılar Bul
     *
     * Bir ilan için bekleyen talepleri tarar ve uygun alıcıları bulur.
     *
     * @param Ilan $ilan
     * @param int $minScore Minimum eşleşme skoru (varsayılan: 70)
     * @return Collection Skorlanmış talepler
     */
    public function findPotentialBuyers(Ilan $ilan, int $minScore = 70): Collection
    {
        // 1. SÜZGEÇ KATMANI: SQL seviyesinde ön filtreleme (Reverse Matching)
        $aktifTalepler = $this->getTalepCandidates($ilan);

        // 2. Her talebi skorla (re-use existing logic)
        $skorlanmisTalepler = $aktifTalepler->map(function ($talep) use ($ilan) {
            $skor = $this->calculateMatchScore($ilan, $talep);

            return [
                'talep' => $talep,
                'skor' => $skor,
                'yuzde' => round($skor, 0), // 0-100
                'kategori' => $this->getScoreCategory($skor),
                'aciklama' => $this->getScoreExplanation($skor),
                'detaylar' => $this->getMatchDetails($ilan, $talep, $skor),
                'eslesme_nedenleri' => $this->getReverseMatchReasons($ilan, $talep),
            ];
        });

        // 3. Minimum skoru geçenleri filtrele ve sırala
        return $skorlanmisTalepler
            ->filter(fn($item) => $item['skor'] >= $minScore)
            ->sortByDesc('skor')
            ->values();
    }

    /**
     * Reverse Matching için Eşleşme Nedenlerini Al
     */
    protected function getReverseMatchReasons(Ilan $ilan, Talep $talep): array
    {
        $reasons = [];

        // Lokasyon nedenleri
        if ($ilan->mahalle_id && $talep->mahalle_id && $ilan->mahalle_id === $talep->mahalle_id) {
            $reasons[] = "Aynı mahalle ({$ilan->mahalle->mahalle_adi})";
        } elseif ($ilan->ilce_id && $talep->ilce_id && $ilan->ilce_id === $talep->ilce_id) {
            $reasons[] = "Aynı ilçe ({$ilan->ilce->ilce_adi})";
        }

        // Bütçe nedeni
        if ($ilan->fiyat && $talep->max_fiyat) {
            if ($ilan->fiyat <= $talep->max_fiyat) {
                $margin = $talep->max_fiyat - $ilan->fiyat;
                $reasons[] = "Bütçe içinde (+" . number_format($margin, 0, ',', '.') . " TL fark var)";
            }
        }

        // Kategori nedeni
        if ($ilan->alt_kategori_id && $talep->alt_kategori_id && $ilan->alt_kategori_id === $talep->alt_kategori_id) {
            $reasons[] = "Aranan kategori";
        }

        // Metrekare nedeni
        if ($ilan->metrekare && $talep->min_metrekare && $talep->max_metrekare) {
            $m2 = (float) $ilan->metrekare;
            if ($m2 >= $talep->min_metrekare && $m2 <= $talep->max_metrekare) {
                $reasons[] = "Alan kriteri ({$m2} m²)";
            }
        }

        if (empty($reasons)) {
            $reasons[] = "Kısmi eşleşme";
        }

        return $reasons;
    }

    /**
     * 🛰️ SQL PRE-FILTERING: Ilan Candidates
     *
     * Önemli: Bu metod OOM riskini önlemek için veritabanı seviyesinde filtreleme yapar.
     */
    protected function getIlanCandidates(Talep $talep): Collection
    {
        $tolerance = config('crm.matching.price_tolerance', 0.15);
        $maxCandidates = config('crm.matching.max_candidates', 500);

        // [INTENTIONAL CROSS-TENANT] Matching requires global listing corpus.
        // Demand ↔ Supply pairing must search across all active listings, not just owner's portfolio.
        // This is NOT a security bypass — see docs/governance/PHASE4_SEMANTIC_CLASSIFICATION.md
        $query = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value);

        Log::debug('🔍 SQL Pre-filter Trace', [
            'talep_id' => $talep->id,
            'target_il' => $talep->il_id,
            'target_alt_cat' => $talep->alt_kategori_id,
            'raw_attributes' => $talep->getAttributes()
        ]);

        // Kategori ön filtresi
        if ($talep->alt_kategori_id) {
            $query->where('alt_kategori_id', $talep->alt_kategori_id);
        }

        // Lokasyon ön filtresi (İl bazlı zorunlu filtre)
        if ($talep->il_id) {
            $query->where('il_id', $talep->il_id);
        }

        // İlçe ve Mahalle (Opsiyonel ama güçlendirici)
        if ($talep->ilce_id) {
            $query->where('ilce_id', $talep->ilce_id);
        }
        if ($talep->mahalle_id) {
            $query->where('mahalle_id', $talep->mahalle_id);
        }

        // Fiyat ön filtresi (Toleranslı)
        if ($talep->min_fiyat) {
            $query->where('fiyat', '>=', $talep->min_fiyat * (1 - $tolerance));
        }
        if ($talep->max_fiyat) {
            $query->where('fiyat', '<=', $talep->max_fiyat * (1 + $tolerance));
        }

        // Metrekare ön filtresi (Toleranslı - Brut veya Alan kontrolü)
        $m2Tolerance = 0.20; // Metrekarede %20 daha geniş tolerans
        if ($talep->min_metrekare) {
            $minM2 = $talep->min_metrekare * (1 - $m2Tolerance);
            $query->where(function ($q) use ($minM2) {
                $q->where('brut_m2', '>=', $minM2)
                    ->orWhere('alan_m2', '>=', $minM2);
            });
        }
        if ($talep->max_metrekare) {
            $maxM2 = $talep->max_metrekare * (1 + $m2Tolerance);
            $query->where(function ($q) use ($maxM2) {
                $q->where('brut_m2', '<=', $maxM2)
                    ->orWhere('alan_m2', '<=', $maxM2);
            });
        }

        $results = $query->with(['il', 'ilce', 'mahalle', 'altKategori'])
            ->limit($maxCandidates)
            ->get();

        Log::debug('🛰️ Candidates Found', [
            'count' => $results->count(),
            'ids' => $results->pluck('id')->toArray(),
            'cat_ids' => $results->pluck('alt_kategori_id')->toArray()
        ]);

        return $results;
    }

    /**
     * 🛰️ SQL PRE-FILTERING: Talep Candidates (Reverse Matching)
     */
    protected function getTalepCandidates(Ilan $ilan): Collection
    {
        $tolerance = config('crm.matching.price_tolerance', 0.15);
        $maxCandidates = config('crm.matching.max_candidates', 500);

        // [INTENTIONAL CROSS-TENANT] Reverse matching requires global buyer/demand corpus.
        // A listed property must be matched against all qualified buyers, not just owner's leads.
        // This is NOT a security bypass — see docs/governance/PHASE4_SEMANTIC_CLASSIFICATION.md
        $query = Talep::where('talep_durumu', TalepDurumu::AKTIF->value)
            ->whereNotNull('kisi_id');

        // Kategori ön filtresi
        if ($ilan->alt_kategori_id) {
            $query->where('alt_kategori_id', $ilan->alt_kategori_id);
        }

        // Lokasyon ön filtresi
        if ($ilan->il_id) {
            $query->where('il_id', $ilan->il_id);
        }

        // Fiyat ön filtresi (İlanın fiyatına göre talepleri filtrele)
        if ($ilan->fiyat) {
            $query->where(function($q) use ($ilan, $tolerance) {
                $q->whereNull('max_fiyat')
                  ->orWhere('max_fiyat', '>=', $ilan->fiyat * (1 - $tolerance));
            });
        }

        return $query->with(['kisi', 'danisman', 'il', 'ilce', 'mahalle', 'altKategori'])
            ->limit($maxCandidates)
            ->get();
    }

    /**
     * 🔄 Toplu Eşleştirme
     *
     * Tüm bekleyen talepleri aktif ilanlarla eşleştir
     *
     * @return array İstatistikler
     */
    public function matchAllPendingDemands(): array
    {
        // [INTENTIONAL CROSS-TENANT] Bulk matching operates on all pending demands across all agents.
        // This is a system-level background operation, not an agent-facing CRUD action.
        // This is NOT a security bypass — see docs/governance/PHASE4_SEMANTIC_CLASSIFICATION.md
        $talepler = Talep::where('talep_durumu', TalepDurumu::BEKLEMEDE->value)->get();

        $istatistikler = [
            'toplam_talep' => $talepler->count(),
            'eslesen_talep' => 0,
            'toplam_eslesen_ilan' => 0,
        ];

        foreach ($talepler as $talep) {
            $eslesenIlanlar = $this->matchDemand($talep, 5);

            if ($eslesenIlanlar->isNotEmpty()) {
                $istatistikler['eslesen_talep']++;
                $istatistikler['toplam_eslesen_ilan'] += $eslesenIlanlar->count();
            }
        }

        return $istatistikler;
    }
}

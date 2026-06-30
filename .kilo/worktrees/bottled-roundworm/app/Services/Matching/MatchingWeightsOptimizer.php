<?php

namespace App\Services\Matching;

/**
 * @sab-ignore-catch
 */

use App\Models\AiLog;
use App\Models\Ilan;
use App\Models\Talep;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🤖 MATCHING WEIGHTS OPTIMIZER
 * 
 * YalihanCortex AI tarafından desteklenen dinamik ağırlık optimizasyonu
 * Geçmiş başarılı eşleşmelerden öğrenerek en iyi parametreleri bulur
 * 
 * Context7: ML-based optimization, learning from historical data
 * Phase 8 - Sprint 2: AI-Powered Scoring
 */
class MatchingWeightsOptimizer
{
    /**
     * Varsayılan ağırlıklar (fallback)
     */
    const DEFAULT_WEIGHTS = [
        'lokasyon' => 0.40,
        'fiyat' => 0.30,
        'kategori' => 0.20,
        'metrekare' => 0.10,
    ];

    /**
     * Cache key
     */
    const CACHE_KEY = 'matching_optimized_weights_v2';

    /**
     * Cache süresi (24 saat)
     */
    const CACHE_TTL = 86400;

    /**
     * 🎯 Optimize edilmiş ağırlıkları al
     * 
     * 🤖 PHASE 8 - SPRINT 2: Context-Aware Dynamic Weights
     * 
     * Talebin tipine (Arsa, Konut, Ticari) ve özelliklerine göre
     * optimize edilmiş ağırlık matrisini döndürür.
     * 
     * @param Talep $talep İlgili talep
     * @return array Optimize edilmiş ağırlıklar
     */
    public function getOptimizedWeights(Talep $talep): array
    {
        // 1. Talep kategorisine göre temel ağırlıkları belirle
        $baseWeights = $this->getBaseWeightsByCategory($talep);

        // 2. Geçmiş verilerden fine-tuning yap
        $adjustedWeights = $this->adjustByHistory($baseWeights, $talep);

        // 3. Aciliyet seviyesine göre optimize et
        $finalWeights = $this->adjustByUrgency($adjustedWeights, $talep);

        // 4. Cache'e kaydet (kategori bazlı)
        $cacheKey = self::CACHE_KEY . '_' . ($talep->alt_kategori_id ?? 'default');
        Cache::put($cacheKey, $finalWeights, self::CACHE_TTL);

        return $finalWeights;
    }

    /**
     * 🏠 Kategori bazlı temel ağırlıklar
     * 
     * Her kategori için optimize edilmiş başlangıç ağırlıkları
     * 
     * @param Talep $talep
     * @return array
     */
    protected function getBaseWeightsByCategory(Talep $talep): array
    {
        // Kategori slug'ını al
        $kategoriSlug = $talep->altKategori->slug ?? 'default';

        // Kategori bazlı ağırlık matrisi
        $weightMatrix = [
            // 🏡 ARSA: Lokasyon ve imar durumu çok önemli
            'arsa' => [
                'lokasyon' => 0.50,  // Lokasyon en kritik
                'fiyat' => 0.25,     // Fiyat ikincil
                'kategori' => 0.15,  // Kategori az önemli
                'metrekare' => 0.10, // Alan son sırada
            ],

            // 🏠 KONUT: Dengeli ağırlıklar
            'konut' => [
                'lokasyon' => 0.35,
                'fiyat' => 0.35,
                'kategori' => 0.20,
                'metrekare' => 0.10,
            ],

            // 🏢 TİCARİ: Lokasyon ve metrekare çok önemli
            'ticari' => [
                'lokasyon' => 0.45,
                'fiyat' => 0.20,
                'kategori' => 0.15,
                'metrekare' => 0.20,
            ],

            // 🏖️ YAZLIK: Loks yon ve özellikler
            'yazlik' => [
                'lokasyon' => 0.40,
                'fiyat' => 0.30,
                'kategori' => 0.20,
                'metrekare' => 0.10,
            ],
        ];

        // Kategori eşleşmesi yoksa varsayılan ağırlıkları kullan
        foreach ($weightMatrix as $key => $weights) {
            if (str_contains($kategoriSlug, $key)) {
                return $weights;
            }
        }

        return self::DEFAULT_WEIGHTS;
    }

    /**
     * 📚 Geçmiş verilerden ince ayar (Fine-Tuning)
     * 
     * 🤖 PHASE 8 - SPRINT 2: Historical Learning
     * 
     * Cortex Score > 80 olan başarılı eşleşmeleri analiz ederek
     * temel ağırlıkları optimize eder.
     * 
     * @param array $baseWeights Temel ağırlıklar
     * @param Talep $talep İlgili talep
     * @return array İnce ayar yapılmış ağırlıklar
     */
    protected function adjustByHistory(array $baseWeights, Talep $talep): array
    {
        try {
            // Aynı kategorideki başarılı eşleşmeleri al
            $categoryMatches = $this->getSuccessfulMatchesByCategory($talep->alt_kategori_id);

            if ($categoryMatches->isEmpty() || $categoryMatches->count() < 5) {
                // Yeterli veri yoksa base weights'i döndür
                return $baseWeights;
            }

            // Kategori bazlı korelasyon hesapla
            $correlations = $this->calculateCorrelations($categoryMatches);
            $historicalWeights = $this->normalizeWeights($correlations);

            // Base weights ile historical weights'i harmanla (%70 historical, %30 base)
            $adjusted = [];
            foreach ($baseWeights as $key => $value) {
                $historicalValue = $historicalWeights[$key] ?? $value;
                $adjusted[$key] = round(($historicalValue * 0.70) + ($value * 0.30), 2);
            }

            // Toplamın 1.0 olduğundan emin ol
            $sum = array_sum($adjusted);
            if ($sum != 1.0) {
                $adjusted['lokasyon'] += (1.0 - $sum);
                $adjusted['lokasyon'] = round($adjusted['lokasyon'], 2);
            }

            return $adjusted;
        } catch (\Exception $e) {
            // Hata durumunda base weights  döndür
            return $baseWeights;
        }
    }

    /**
     * ⚡ Aciliyet seviyesine göre optimizasyon
     * 
     * 🤖 PHASE 8 - SPRINT 2: Urgency-Based Adjustment
     * 
     * Talebin aciliyet seviyesine göre ağırlıkları ayarlar:
     * - Y üksek aciliyet → Lokasyon ağırlığı artar (hızlı karar)
     * - Düşük aciliyet → Fiyat ağırlığı artar (dikkatli değerlendirme)
     * 
     * @param array $weights Mevcut ağırlıklar
     * @param Talep $talep İlgili talep
     * @return array Aciliyete göre ayarlanmış ağırlıklar
     */
    protected function adjustByUrgency(array $weights, Talep $talep): array
    {
        // Talebin güncellik seviyesini hesapla
        $daysSinceCreated = $talep->created_at->diffInDays(now());

        // Aciliyet faktörü (0 = yeni, 90+ = çok eski)
        if ($daysSinceCreated > 60) {
            // ÇOK ESKİ TALEP: Lokasyonu biraz gevşet, fiyatı vurgula
            $weights['lokasyon'] *= 0.90; // %10 azalt
            $weights['fiyat'] *= 1.15;     // %15 artır
        } elseif ($daysSinceCreated > 30) {
            // ESKİ TALEP: Hafif ayarlama
            $weights['lokasyon'] *= 0.95; // %5 azalt
            $weights['fiyat'] *= 1.05;     // %5 artır
        } elseif ($daysSinceCreated < 7) {
            // YENİ TALEP: Lokasyonu vurgula (hızlı eşleştirme)
            $weights['lokasyon'] *= 1.10; // %10 artır
            $weights['fiyat'] *= 0.95;     // %5 azalt
        }

        // Normalize et
        $total = array_sum($weights);
        foreach ($weights as $key => $value) {
            $weights[$key] = round($value / $total, 2);
        }

        // Toplamın 1.0 olduğundan emin ol
        $sum = array_sum($weights);
        if ($sum != 1.0) {
            $weights['lokasyon'] += (1.0 - $sum);
            $weights['lokasyon'] = round($weights['lokasyon'], 2);
        }

        return $weights;
    }

    /**
     * 📊 Kategori bazlı başarılı eşleşmeleri getir
     * 
     * @param int|null $kategoriId
     * @return \Illuminate\Support\Collection
     */
    protected function getSuccessfulMatchesByCategory(?int $kategoriId): \Illuminate\Support\Collection
    {
        if (!$kategoriId) {
            return collect([]);
        }

        $logs = AiLog::where('event_type', 'reverse_match_notification_sent')
            ->where('created_at', '>=', now()->subDays(90))
            ->whereNotNull('metadata')
            ->get();

        return $logs->filter(function ($log) use ($kategoriId) {
            $metadata = is_string($log->metadata) ? json_decode($log->metadata, true) : $log->metadata;
            
            // Score > 80 ve aynı kategoride
            if (!isset($metadata['score']) || $metadata['score'] < 80) {
                return false;
            }

            // Talep kategorisini kontrol et
            if (isset($metadata['talep_id'])) {
                $talep = Talep::find($metadata['talep_id']);
                return $talep && $talep->alt_kategori_id == $kategoriId;
            }

            return false;
        })->map(function ($log) {
            $metadata = is_string($log->metadata) ? json_decode($log->metadata, true) : $log->metadata;
            
            return [
                'ilan_id' => $metadata['ilan_id'] ?? null,
                'talep_id' => $metadata['talep_id'] ?? null,
                'score' => $metadata['score'] ?? 0,
                'urgency_level' => $metadata['urgency_level'] ?? 'NORMAL',
            ];
        });
    }

    /**
     * 🧠 Ağırlıkları yeniden hesapla
     * 
     * Geçmiş başarılı eşleşmeleri analiz ederek en iyi ağırlıkları bulur
     * 
     * @return array
     */
    public function calculateOptimizedWeights(): array
    {
        try {
            // 1. Geçmiş başarılı eşleşmeleri al (son 90 gün)
            $successfulMatches = $this->getSuccessfulMatches();

            if ($successfulMatches->isEmpty()) {
                // Veri yoksa varsayılan ağırlıkları kullan
                return self::DEFAULT_WEIGHTS;
            }

            // 2. Her parametre için korelasyon analizi yap
            $correlations = $this->calculateCorrelations($successfulMatches);

            // 3. Korelasyonlara göre ağırlıkları normalize et
            $optimizedWeights = $this->normalizeWeights($correlations);

            // 4. Sonuçları logla
            \App\Services\Logging\LogService::ai(
                'matching_weights_optimized',
                'MatchingWeightsOptimizer',
                [
                    'old_weights' => self::DEFAULT_WEIGHTS,
                    'new_weights' => $optimizedWeights,
                    'sample_size' => $successfulMatches->count(),
                    'correlations' => $correlations,
                ]
            );

            return $optimizedWeights;
        } catch (\Exception $e) {
            \App\Services\Logging\LogService::error(
                'Matching weights optimization failed',
                ['error' => $e->getMessage()],
                $e,
                \App\Services\Logging\LogService::CHANNEL_AI
            );

            return self::DEFAULT_WEIGHTS;
        }
    }

    /**
     * 📊 Başarılı eşleşmeleri getir
     * 
     * AiLog'dan reverse_match_notification_sent eventlerini al
     * Score > 80 olanları "başarılı" kabul et
     * 
     * @return \Illuminate\Support\Collection
     */
    protected function getSuccessfulMatches(): \Illuminate\Support\Collection
    {
        // Son 90 gündeki başarılı eşleşmeler
        $logs = AiLog::where('event_type', 'reverse_match_notification_sent')
            ->where('created_at', '>=', now()->subDays(90))
            ->whereNotNull('metadata')
            ->get();

        return $logs->filter(function ($log) {
            $metadata = is_string($log->metadata) ? json_decode($log->metadata, true) : $log->metadata;
            
            // Score > 80 olanları başarılı kabul et
            return isset($metadata['score']) && $metadata['score'] >= 80;
        })->map(function ($log) {
            $metadata = is_string($log->metadata) ? json_decode($log->metadata, true) : $log->metadata;
            
            return [
                'ilan_id' => $metadata['ilan_id'] ?? null,
                'talep_id' => $metadata['talep_id'] ?? null,
                'score' => $metadata['score'] ?? 0,
                'urgency_level' => $metadata['urgency_level'] ?? 'NORMAL',
            ];
        });
    }

    /**
     * 🔬 Korelasyon analizi
     * 
     * Her parametre için başarılı eşleşmelerle korelasyonunu hesapla
     * 
     * @param \Illuminate\Support\Collection $matches
     * @return array
     */
    protected function calculateCorrelations(\Illuminate\Support\Collection $matches): array
    {
        $correlations = [
            'lokasyon' => 0,
            'fiyat' => 0,
            'kategori' => 0,
            'metrekare' => 0,
        ];

        $totalScore = 0;
        $count = 0;

        foreach ($matches as $match) {
            if (!$match['ilan_id'] || !$match['talep_id']) {
                continue;
            }

            // İlan ve talebi fetch et
            $ilan = Ilan::find($match['ilan_id']);
            $talep = Talep::find($match['talep_id']);

            if (!$ilan || !$talep) {
                continue;
            }

            // Her parametre için uyum skorunu hesapla
            $engine = new DemandMatchingEngine();
            
            $lokasyonSkoru = $this->invokeProtectedMethod($engine, 'calculateLocationScore', [$ilan, $talep]);
            $fiyatSkoru = $this->invokeProtectedMethod($engine, 'calculatePriceScore', [$ilan, $talep]);
            $kategoriSkoru = $this->invokeProtectedMethod($engine, 'calculateCategoryScore', [$ilan, $talep]);
            $metrekareSkoru = $this->invokeProtectedMethod($engine, 'calculateAreaScore', [$ilan, $talep]);

            // Skorları topla (yüksek skorlar = yüksek öncelik)
            $correlations['lokasyon'] += ($lokasyonSkoru / 100) * $match['score'];
            $correlations['fiyat'] += ($fiyatSkoru / 100) * $match['score'];
            $correlations['kategori'] += ($kategoriSkoru / 100) * $match['score'];
            $correlations['metrekare'] += ($metrekareSkoru / 100) * $match['score'];

            $totalScore += $match['score'];
            $count++;
        }

        // Ortalama al
        if ($count > 0) {
            foreach ($correlations as $key => $value) {
                $correlations[$key] = $value / $totalScore;
            }
        }

        return $correlations;
    }

    /**
     * ⚖️ Ağırlıkları normalize et
     * 
     * Korelasyonları toplamı 1.0 olacak şekilde normalize et
     * 
     * @param array $correlations
     * @return array
     */
    protected function normalizeWeights(array $correlations): array
    {
        $total = array_sum($correlations);

        if ($total == 0) {
            return self::DEFAULT_WEIGHTS;
        }

        $normalized = [];
        foreach ($correlations as $key => $value) {
            $normalized[$key] = round($value / $total, 2);
        }

        // Toplamın exactly 1.0 olduğundan emin ol (rounding hataları için)
        $sum = array_sum($normalized);
        if ($sum != 1.0) {
            $normalized['lokasyon'] += (1.0 - $sum);
            $normalized['lokasyon'] = round($normalized['lokasyon'], 2);
        }

        return $normalized;
    }

    /**
     * 🔓 Protected metodu çağır (Reflection kullanarak)
     * 
     * @param object $object
     * @param string $methodName
     * @param array $parameters
     * @return mixed
     */
    protected function invokeProtectedMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * 🔄 Cache'i temizle
     * 
     * Yeni veriler geldiğinde cache'i temizleyip yeniden hesaplama tetikle
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * 📈 Optimizasyon istatistikleri
     * 
     * @return array
     */
    public function getOptimizationStats(): array
    {
        // Stats metodu için varsayılan ağırlıkları kullan
        $optimizedWeights = self::DEFAULT_WEIGHTS;
        $defaultWeights = self::DEFAULT_WEIGHTS;

        $changes = [];
        foreach ($optimizedWeights as $key => $value) {
            $delta = $value - $defaultWeights[$key];
            $changes[$key] = [
                'old' => $defaultWeights[$key],
                'new' => $value,
                'delta' => round($delta, 3),
                'change_percent' => round(($delta / $defaultWeights[$key]) * 100, 1),
            ];
        }

        $successfulMatches = $this->getSuccessfulMatches();

        return [
            'weights' => $optimizedWeights,
            'changes' => $changes,
            'sample_size' => $successfulMatches->count(),
            'last_updated' => Cache::get(self::CACHE_KEY . '_timestamp', 'never'),
            'cache_expires_in' => Cache::get(self::CACHE_KEY) ? self::CACHE_TTL . ' seconds' : 'expired',
        ];
    }

    /**
     * 🧠 Basit Semantik Bonus (Phase 8 - Sprint 3 PoC)
     * 
     * OpenAI Embeddings olmadan keyword-based semantic matching.
     * İlan ve talep açıklamalarındaki ortak pozitif kelimeleri tespit eder.
     * 
     * @param \App\Models\Ilan $ilan
     * @param \App\Models\Talep $talep
     * @return float Semantik bonus skoru (0-20)
     */
    public function calculateSimpleSemanticBonus($ilan, $talep): float
    {
        // Pozitif etkili anahtar kelimeler (Context7: Emlak sektörü odaklı)
        $positiveKeywords = [
            // Lokasyon kalitesi
            'huzurlu', 'sakin', 'merkezi', 'prestijli', 'elit', 'lüks',
            'yeşil', 'denize yakın', 'deniz manzaralı', 'şehir manzaralı',
            'ulaşım kolay', 'merkeze yakın', 'havaalanına yakın',
            
            // Yatırım değeri
            'yatırım', 'değer kazanacak', 'gelecek vaat eden', 'kârlı',
            'yüksek getiri', 'fırsat', 'cazip fiyat',
            
            // Yaşam kalitesi
            'aile için ideal', 'güvenli', 'sessiz', 'modern', 'yeni',
            'bakımlı', 'ferah', 'aydınlık', 'geniş', 'kullanışlı',
            
            // Sosyal olanaklar
            'alışveriş merkezi yakın', 'okullara yakın', 'hastaneye yakın',
            'spor tesisi', 'yürüyüş yolu', 'park', 'sosyal tesis',
        ];

        $ilanText = strtolower($ilan->aciklama ?? '');
        $talepText = strtolower($talep->notlar ?? '');
        
        // Hem ilan hem talep boşsa bonus yok
        if (empty($ilanText) || empty($talepText)) {
            return 0;
        }

        $score = 0;
        $matchedKeywords = [];

        foreach ($positiveKeywords as $keyword) {
            // Hem ilanda hem talepte geçiyorsa
            if (str_contains($ilanText, $keyword) && str_contains($talepText, $keyword)) {
                $score += 5; // Her eşleşen kelime için +5 puan
                $matchedKeywords[] = $keyword;
            }
        }

        // Log için (debugging)
        if ($score > 0) {
            \Log::debug('Semantic bonus calculated', [
                'ilan_id' => $ilan->id ?? 'N/A',
                'talep_id' => $talep->id ?? 'N/A',
                'matched_keywords' => $matchedKeywords,
                'bonus_score' => min($score, 20),
            ]);
        }

        // Maksimum 20 puan bonus (4 keyword match)
        return min($score, 20);
    }

    /**
     * 🎯 PHASE 8 - SPRINT 3: Full Semantic Search
     * 
     * OpenAI Embeddings kullanarak gerçek anlamsal benzerlik hesapla
     * 
     * @param Ilan $ilan
     * @param Talep $talep
     * @return float 0-100 arası semantic similarity score
     */
    public function calculateFullSemanticScore(\App\Models\Ilan $ilan, \App\Models\Talep $talep): float
    {
        try {
            $embeddingService = app(\App\Services\AI\EmbeddingService::class);

            // 1. İlan ve talep için embeddings al (cache'den veya API'den)
            $ilanVector = null;
            $talepVector = null;

            // İlan embedding'i kontrol et
            if ($ilan->embedding_vector) {
                // Database'den al
                $ilanVector = is_string($ilan->embedding_vector) 
                    ? json_decode($ilan->embedding_vector, true) 
                    : $ilan->embedding_vector;
            } else {
                // API'den oluştur
                $ilanVector = $embeddingService->getIlanEmbedding($ilan);
                
                // Database'e kaydet
                if ($ilanVector) {
                    $ilan->update([
                        'embedding_vector' => json_encode($ilanVector),
                        'embedding_generated_at' => now(),
                    ]);
                }
            }

            // Talep embedding'i kontrol et
            if ($talep->embedding_vector) {
                // Database'den al
                $talepVector = is_string($talep->embedding_vector)
                    ? json_decode($talep->embedding_vector, true)
                    : $talep->embedding_vector;
            } else {
                // API'den oluştur
                $talepVector = $embeddingService->getTalepEmbedding($talep);
                
                // Database'e kaydet
                if ($talepVector) {
                    $talep->update([
                        'embedding_vector' => json_encode($talepVector),
                        'embedding_generated_at' => now(),
                    ]);
                }
            }

            // 2. Her iki vector de varsa cosine similarity hesapla
            if ($ilanVector && $talepVector) {
                $similarity = $embeddingService->cosineSimilarity($ilanVector, $talepVector);
                
                // Cosine similarity 0-1 arası, biz 0-100'e çeviriyoruz
                $score = $similarity * 100;

                \Log::info('🎯 Full Semantic Score calculated', [
                    'ilan_id' => $ilan->id,
                    'talep_id' => $talep->id,
                    'similarity' => round($similarity, 4),
                    'score' => round($score, 2),
                ]);

                return round($score, 2);
            }

            // Embedding oluşturulamadıysa simple semantic'e geri dön
            \Log::warning('Semantic vectors not available, falling back to simple semantic', [
                'ilan_id' => $ilan->id,
                'talep_id' => $talep->id,
                'has_ilan_vector' => !is_null($ilanVector),
                'has_talep_vector' => !is_null($talepVector),
            ]);

            return $this->calculateSimpleSemanticBonus($ilan, $talep);

        } catch (\Exception $e) {
            \Log::error('Full semantic score calculation failed', [
                'ilan_id' => $ilan->id ?? 'N/A',
                'talep_id' => $talep->id ?? 'N/A',
                'error' => $e->getMessage(),
            ]);

            // Hata durumunda simple semantic'e geri dön
            return $this->calculateSimpleSemanticBonus($ilan, $talep);
        }
    }
}

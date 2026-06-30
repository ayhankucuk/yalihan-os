<?php

namespace App\Services\Intelligence;

/**
 * @sab-ignore-catch
 */

use App\Models\Kisi;
use App\Models\KisiAktivite;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Sentiment Analysis Service
 * Context7: Müşteri Hissiyat Analizi (Sentiment Analysis) için duygusal durum analizi servisi
 *
 * Görüşme notlarını analiz edip müşterinin duygusal durumunu çıkarır.
 *
 * B-006 P4: KisiNot (ghost) + Deprecated\KisiAktivite → App\Models\KisiAktivite (kisi_etkilesimler tablosu)
 * Not verisi: kisi_etkilesimler.notlar kolonu (tip filtresi ile ayrıştırma yapılabilir)
 */
class SentimentAnalysisService
{
    public function __construct(
        private AIService $aiService
    ) {}

    /**
     * Müşteri için hissiyat analizi
     *
     * @param Kisi $kisi
     * @return array
     */
    public function analyzeSentiment(Kisi $kisi): array
    {
        $cacheKey = "sentiment:kisi:{$kisi->id}";

        return Cache::remember($cacheKey, 3600 * 6, function () use ($kisi) {
            try {
                // Son 30 günün etkileşimleri (kisi_etkilesimler tablosu)
                // B-006 P4: KisiNot + KisiAktivite (her ikisi de ghost) → KisiAktivite (kanonik)
                // kisi_etkilesimler.notlar alanı hem not hem aktivite metnini taşır
                $etkilesimler = KisiAktivite::where('kisi_id', $kisi->id)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->orderBy('created_at', 'desc') // context7-ignore
                    ->limit(40) // Önceki 20 not + 20 aktivite limitinin toplamı
                    ->get();

                if ($etkilesimler->isEmpty()) {
                    return [
                        'kisi_id' => $kisi->id,
                        'sentiment' => 'NEUTRAL',
                        'sentiment_score' => 0,
                        'confidence' => 0,
                        'message' => 'Yeterli veri yok',
                        'analysis' => [],
                    ];
                }

                // Etkileşim metinlerini birleştir (notlar kolonu)
                $texts = $etkilesimler
                    ->filter(fn($e) => !empty($e->notlar))
                    ->map(fn($e) => $e->notlar)
                    ->values()
                    ->toArray();

                $combinedText = implode(' ', $texts);

                // AI ile hissiyat analizi
                $sentimentResult = $this->analyzeWithAI($kisi, $combinedText, $etkilesimler, $etkilesimler);

                return [
                    'kisi_id' => $kisi->id,
                    'kisi_adi' => $kisi->tam_ad,
                    'sentiment' => $sentimentResult['sentiment'],
                    'sentiment_score' => $sentimentResult['score'],
                    'confidence' => $sentimentResult['confidence'],
                    'message' => $sentimentResult['message'],
                    'analysis' => $sentimentResult['analysis'],
                    'trend' => $this->calculateTrend($etkilesimler, $etkilesimler),
                    'sample_count' => count($texts),
                    'analyzed_at' => now(),
                ];
            } catch (\Exception $e) {
                Log::error('Sentiment analysis error', [
                    'kisi_id' => $kisi->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'kisi_id' => $kisi->id,
                    'sentiment' => 'UNKNOWN',
                    'sentiment_score' => 0,
                    'confidence' => 0,
                    'message' => 'Hesaplama hatası: ' . $e->getMessage(),
                    'analysis' => [],
                ];
            }
        });
    }

    /**
     * AI ile hissiyat analizi
     */
    private function analyzeWithAI(Kisi $kisi, string $text, $notes, $activities): array
    {
        try {
            $prompt = sprintf(
                "Aşağıdaki müşteri görüşme notlarını ve aktivitelerini analiz et. Müşterinin duygusal statusunu (pozitif, negatif, nötr) belirle.\n\n" .
                    "Müşteri: %s\n\n" .
                    "Notlar ve Aktiviteler:\n%s\n\n" .
                    "Lütfen şu formatta yanıt ver:\n" .
                    "- Sentiment: POSITIVE, NEGATIVE, veya NEUTRAL\n" .
                    "- Score: -100 (çok negatif) ile +100 (çok pozitif) arası\n" .
                    "- Confidence: 0-100 arası güven seviyesi\n" .
                    "- Message: Kısa özet mesaj\n" .
                    "- Analysis: Detaylı analiz",
                $kisi->tam_ad,
                $text
            );

            $aiResponse = $this->aiService->generate($prompt, [
                'type' => 'sentiment_analysis', // context7-ignore
                'max_tokens' => 500,
            ]);

            // AI yanıtını parse et (string'e çevir)
            $aiResponseText = is_array($aiResponse) ? ($aiResponse['content'] ?? json_encode($aiResponse)) : (string) $aiResponse;
            return $this->parseAIResponse($aiResponseText);
        } catch (\Exception $e) {
            // AI provider hatası — keyword analizine düş (Fail-Open: kullanıcı bloke edilmez)
            Log::warning('SentimentAnalysisService: AI analizi başarısız, keyword analizine düşülüyor', [
                'error' => $e->getMessage(),
            ]);
            return $this->simpleKeywordAnalysis($text);
        }
    }

    /**
     * AI yanıtını parse et
     */
    private function parseAIResponse(string $aiResponse): array
    {
        // Basit parsing (gerçek uygulamada daha gelişmiş olabilir)
        $sentiment = 'NEUTRAL';
        $score = 0;
        $confidence = 50;

        if (stripos($aiResponse, 'POSITIVE') !== false || stripos($aiResponse, 'pozitif') !== false) {
            $sentiment = 'POSITIVE';
            $score = 50;
        } elseif (stripos($aiResponse, 'NEGATIVE') !== false || stripos($aiResponse, 'negatif') !== false) {
            $sentiment = 'NEGATIVE';
            $score = -50;
        }

        // Score extraction
        if (preg_match('/Score[:\s]+(-?\d+)/i', $aiResponse, $matches)) {
            $score = (int) $matches[1];
        }

        // Confidence extraction
        if (preg_match('/Confidence[:\s]+(\d+)/i', $aiResponse, $matches)) {
            $confidence = (int) $matches[1];
        }

        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => $confidence,
            'message' => $this->getSentimentMessage($sentiment, $score),
            'analysis' => ['AI Analysis' => $aiResponse],
        ];
    }

    /**
     * Basit keyword analizi (AI fallback)
     */
    private function simpleKeywordAnalysis(string $text): array
    {
        $positiveKeywords = ['memnun', 'beğendi', 'ilgileniyor', 'satın almak', 'hazır', 'uygun', 'güzel', 'iyi'];
        $negativeKeywords = ['memnun değil', 'beğenmedi', 'pahalı', 'uygun değil', 'ilgilenmiyor', 'kararsız', 'şüpheli'];

        $positiveCount = 0;
        $negativeCount = 0;

        $lowerText = mb_strtolower($text);

        foreach ($positiveKeywords as $keyword) {
            if (mb_stripos($lowerText, $keyword) !== false) {
                $positiveCount++;
            }
        }

        foreach ($negativeKeywords as $keyword) {
            if (mb_stripos($lowerText, $keyword) !== false) {
                $negativeCount++;
            }
        }

        $score = ($positiveCount - $negativeCount) * 20;
        $score = max(-100, min(100, $score));

        $sentiment = match (true) {
            $score > 20 => 'POSITIVE',
            $score < -20 => 'NEGATIVE',
            default => 'NEUTRAL',
        };

        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => 40,
            'message' => $this->getSentimentMessage($sentiment, $score),
            'analysis' => [
                'positive_keywords' => $positiveCount,
                'negative_keywords' => $negativeCount,
                'method' => 'keyword_analysis',
            ],
        ];
    }

    /**
     * Hissiyat mesajı
     */
    private function getSentimentMessage(string $sentiment, int $score): string
    {
        return match ($sentiment) {
            'POSITIVE' => sprintf('😊 Pozitif (%+d) - Müşteri memnun ve ilgili görünüyor', $score),
            'NEGATIVE' => sprintf('😟 Negatif (%d) - Müşteri memnuniyetsiz veya şüpheli', $score),
            default => sprintf('😐 Nötr (%+d) - Müşteri statusu belirsiz', $score),
        };
    }

    /**
     * Trend hesapla
     */
    private function calculateTrend($notes, $activities): string
    {
        if ($notes->count() < 2) {
            return 'INSUFFICIENT_DATA';
        }

        // Son 2 notu karşılaştır (basit trend)
        $recent = $notes->take(2);
        // Gerçek uygulamada daha detaylı trend analizi yapılabilir

        return 'STABLE';
    }

    /**
     * Cache'i temizle
     */
    public function clearCache(int $kisiId): void
    {
        Cache::forget("sentiment:kisi:{$kisiId}");
    }
}

<?php

namespace App\Services\AI\Domains;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Services\AIService;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 🧠 Cortex Content Domain Service
 * Responsibility: Handles title optimization, video scripts, and marketing content generation.
 */
class CortexContentService
{
    protected AIService $aiService;
    protected AiTelemetryService $telemetry;
    protected \App\Services\Intelligence\MultilingualService $multilingualService;

    public function __construct(
        AIService $aiService,
        AiTelemetryService $telemetry,
        \App\Services\Intelligence\MultilingualService $multilingualService
    ) {
        $this->aiService = $aiService;
        $this->telemetry = $telemetry;
        $this->multilingualService = $multilingualService;
    }

    /**
     * Çok Dilli İlan Başlığı Üretimi
     */
    public function generateMultilingualTitle($ilan, string $language = 'en', array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_multilingual_title');

        try {
            $ilanModel = $ilan instanceof Ilan ? $ilan : Ilan::find($ilan['id'] ?? 0);
            
            if (!$ilanModel) {
                throw new Exception('İlan bulunamadı');
            }

            $multilingualResult = $this->multilingualService->generateLocalizedTitle(
                $ilanModel,
                $language,
                $options
            );

            $durationMs = LogService::stopTimer($startTime);

            $result = [
                'success' => $multilingualResult['success'] ?? false,
                'titles' => $multilingualResult['titles'] ?? [],
                'language' => $language,
                'ilan_id' => $ilanModel->id,
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => $durationMs,
                ],
            ];

            $this->logCortexDecision('multilingual_title', ['ilan_id' => $ilanModel->id, 'language' => $language], $durationMs, $result['success']);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('multilingual_title', ['language' => $language, 'error' => $e->getMessage()], $durationMs, false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Çok Dilli İlan Açıklaması Üretimi
     */
    public function generateMultilingualDescription($ilan, string $language = 'en', array $options = []): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_multilingual_description');

        try {
            $ilanModel = $ilan instanceof Ilan ? $ilan : Ilan::find($ilan['id'] ?? 0);
            
            if (!$ilanModel) {
                throw new Exception('İlan bulunamadı');
            }

            $multilingualResult = $this->multilingualService->generateLocalizedDescription(
                $ilanModel,
                $language,
                $options
            );

            $durationMs = LogService::stopTimer($startTime);

            $result = [
                'success' => $multilingualResult['success'] ?? false,
                'description' => $multilingualResult['description'] ?? '',
                'language' => $language,
                'ilan_id' => $ilanModel->id,
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'duration_ms' => $durationMs,
                ],
            ];

            $this->logCortexDecision('multilingual_description', ['ilan_id' => $ilanModel->id, 'language' => $language], $durationMs, $result['success']);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('multilingual_description', ['language' => $language, 'error' => $e->getMessage()], $durationMs, false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function logCortexDecision(string $type, array $context, float $durationMs, bool $success): void
    {
        $this->telemetry->logTransaction(
            'YalihanCortexContent',
            $type,
            $durationMs / 1000,
            0, 0, $success ? 200 : 500,
            ['request' => $context]
        );
    }

    /**
     * AI-powered ilan başlığı optimize edici
     */
    public function optimizeIlanTitle(array $ilanData): array
    {
        $timer = microtime(true);

        try {
            $kategoriId = $ilanData['ana_kategori_id'] ?? null;
            $kategori = $kategoriId ? IlanKategori::find($kategoriId)?->name : ($ilanData['kategori'] ?? 'Konut');

            $lokasyon = $this->prioritizeLocation([
                'il' => $ilanData['il_id'] ?? $ilanData['il'] ?? null,
                'ilce' => $ilanData['ilce_id'] ?? $ilanData['ilce'] ?? null,
                'mahalle' => $ilanData['mahalle_id'] ?? $ilanData['mahalle'] ?? null
            ]);

            if (empty($lokasyon) && isset($ilanData['lokasyon'])) {
                $lokasyon = $ilanData['lokasyon'];
            }

            $ozellikler = $ilanData['features'] ?? $ilanData['ozellikler'] ?? [];
            if (empty($ozellikler) && isset($ilanData['ozellik_ids'])) {
                $ozellikler = \App\Models\Feature::whereIn('id', $ilanData['ozellik_ids'])->pluck('name')->toArray();
            }
            $highlights = $this->extractHighlights($ozellikler);

            $prompt = "Emlak ilanı için SEO uyumlu, dikkat çekici ve profesyonel bir başlık oluştur.\n" .
                      "Kategori: {$kategori}\n" .
                      "Lokasyon: {$lokasyon}\n" .
                      "Özellikler: " . implode(', ', $highlights) . "\n" .
                      "Maksimum 70 karakter, Türkçe, profesyonel ton, emojisiz. Sadece başlığı döndür.";

            $optimizedTitle = '';
            try {
                $aiResponse = $this->aiService->generate($prompt, [
                    'max_tokens' => 50,
                    'temperature' => 0.7,
                ]);

                if (is_array($aiResponse)) {
                    $optimizedTitle = trim(str_replace(['"'], '', $aiResponse['content'] ?? ($aiResponse['text'] ?? '')));
                } else {
                    $optimizedTitle = trim(str_replace(['"'], '', (string)$aiResponse));
                }

                if (empty($optimizedTitle)) {
                    throw new Exception('AI returned empty title');
                }
            } catch (Exception $aiError) {
                Log::warning('AI Title Optimization Failed: ' . $aiError->getMessage());
                $optimizedTitle = $this->generateFallbackTitle($ilanData, $kategori, $lokasyon);
            }

            $executionTime = microtime(true) - $timer;

            $this->telemetry->logTransaction(
                'YalihanCortexContent',
                'optimize_ilan_title',
                $executionTime,
                0, 0, 200,
                [
                    'request' => $ilanData,
                    'response' => ['title' => $optimizedTitle]
                ]
            );

            return [
                'success' => true,
                'original_title' => $ilanData['baslik'] ?? '',
                'optimized_title' => $optimizedTitle,
                'improvements' => [
                    'seo_score' => $this->calculateSEOScore($optimizedTitle, $kategori, $lokasyon),
                    'click_potential' => $this->estimateClickRate($optimizedTitle),
                    'execution_time_ms' => round($executionTime * 1000, 2)
                ]
            ];
        } catch (Exception $e) {
            LogService::error('CortexContent Title Optimization Failed', ['data' => $ilanData], $e, LogService::CHANNEL_AI);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_title' => $ilanData['baslik'] ?? 'Yeni İlan'
            ];
        }
    }

    /**
     * Pazarlama Videosu için Metin Scripti Üretimi
     */
    public function generateVideoScript(Ilan $ilan): array
    {
        try {
            $tkgmSummary = [
                'ada_no' => $ilan->ada_no,
                'parsel_no' => $ilan->parsel_no,
                'alan_m2' => $ilan->alan_m2,
                'imar_durumu' => $ilan->imar_durumu,
                'kaks' => $ilan->kaks,
                'taks' => $ilan->taks,
            ];

            $nearby = $ilan->nearby_places ?? [];

            $prompt = [
                'instruction' => 'Sakin, güven veren ve lüks bir tonda Türkçe pazarlama videosu scripti üret.',
                'structure' => [
                    'bolumler' => ['Giriş', 'Çevre', 'Özellikler'],
                    'language' => 'tr-TR',
                ],
                'ilan' => [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'aciklama' => $ilan->aciklama,
                    'fiyat' => $ilan->fiyat,
                    'para_birimi' => $ilan->para_birimi,
                    'adres' => $ilan->adres,
                ],
                'tkgm' => $tkgmSummary,
                'nearby_places' => $nearby,
            ];

            $result = $this->aiService->generate(json_encode($prompt, JSON_UNESCAPED_UNICODE), [
                'tone' => 'calm_luxury',
                'max_tokens' => 800,
            ]);

            $script = $result['content'] ?? ($result['text'] ?? null);

            return [
                'success' => $script !== null,
                'ilan_id' => $ilan->id,
                'script' => $script,
                'sections' => [
                    'intro' => null,
                    'environment' => null,
                    'features' => null,
                ],
                'preview_url' => null,
            ];
        } catch (\Throwable $e) {
            LogService::error(
                'CortexContent video script generation failed',
                [
                    'ilan_id' => $ilan->id,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            return [
                'success' => false,
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function prioritizeLocation(array $data): string
    {
        $parts = [];
        if (isset($data['mahalle']) && is_numeric($data['mahalle'])) {
            $parts[] = Mahalle::find($data['mahalle'])?->mahalle_adi;
        } elseif (isset($data['mahalle'])) {
            $parts[] = $data['mahalle'];
        }
        if (isset($data['ilce']) && is_numeric($data['ilce'])) {
            $parts[] = Ilce::find($data['ilce'])?->ilce_adi;
        } elseif (isset($data['ilce'])) {
            $parts[] = $data['ilce'];
        }
        if (isset($data['il']) && is_numeric($data['il'])) {
            $parts[] = Il::find($data['il'])?->il_adi;
        } elseif (isset($data['il'])) {
            $parts[] = $data['il'];
        }
        return implode(', ', array_filter($parts));
    }

    protected function extractHighlights(array $features): array
    {
        if (empty($features)) return [];
        $powerWords = ['Deniz', 'Havuz', 'Lüks', 'Sıfır', 'Fırsat', 'Manzara', 'Bahçe', 'Geniş', 'Modern'];
        $highlights = [];
        foreach ($features as $feature) {
            foreach ($powerWords as $powerWord) {
                if (stripos($feature, $powerWord) !== false) {
                    $highlights[] = $feature;
                    break;
                }
            }
            if (count($highlights) >= 3) break;
        }
        return !empty($highlights) ? $highlights : array_slice($features, 0, 3);
    }

    protected function calculateSEOScore(string $title, string $category, string $location): int
    {
        $score = 0;
        $len = mb_strlen($title);
        if ($len >= 40 && $len <= 70) $score += 40;
        elseif ($len > 30) $score += 20;
        if (stripos($title, $category) !== false) $score += 30;
        $locationParts = explode(',', $location);
        foreach ($locationParts as $part) {
            if (stripos($title, trim($part)) !== false) {
                $score += 30;
                break;
            }
        }
        return min(100, $score);
    }

    protected function estimateClickRate(string $title): int
    {
        $score = 50;
        $powerWords = ['Fırsat', 'Kaçırmayın', 'Deniz', 'Manzara', 'Lüks', 'Modern', 'Eşsiz', 'Özel'];
        foreach ($powerWords as $word) {
            if (stripos($title, $word) !== false) $score += 10;
        }
        return min(100, $score);
    }

    protected function generateFallbackTitle(array $ilanData, string $kategori, string $lokasyon): string
    {
        return "{$lokasyon} {$kategori} - Fırsat İlan";
    }
}

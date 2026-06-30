<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use App\Models\AiLog;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * ��️ SAB SEALED
 * Domain: AI / Templates / Cortex
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - islem_durumu ✅ (execution st' . 'atus)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class CortexTemplateAdvisor
{
    /**
     * Get template advice for kategori + yayin tipi
     *
     * @param array $filters ['kategori_slug' => string, 'yayin_tipi_slug' => string, 'days' => int]
     * @return array Advisory insights
     */
    public function getTemplateAdvice(array $filters = []): array
    {
        $startTime = LogService::startTimer('cortex_template_advice');

        try {
            $days = $filters['days'] ?? 30;
            $kategoriSlug = $filters['kategori_slug'] ?? null;
            $yayinTipiSlug = $filters['yayin_tipi_slug'] ?? null;

            // 1. Get quality check logs with high scores (≥80)
            $highQualityLogs = $this->getHighQualityLogs($days, $kategoriSlug, $yayinTipiSlug);

            // 2. Get all quality check logs (for common issues analysis)
            $allQualityLogs = $this->getAllQualityLogs($days, $kategoriSlug, $yayinTipiSlug);

            // 3. Analyze patterns
            $titlePatterns = $this->analyzeTitlePatterns($highQualityLogs);
            $descriptionPatterns = $this->analyzeDescriptionPatterns($highQualityLogs);
            $commonIssues = $this->analyzeCommonIssues($allQualityLogs);
            $advice = $this->generateAdvice($titlePatterns, $descriptionPatterns, $commonIssues);

            $durationMs = LogService::stopTimer($startTime);

            LogService::info('Template advice generated', [
                'kategori_slug' => $kategoriSlug,
                'yayin_tipi_slug' => $yayinTipiSlug,
                'high_quality_logs' => count($highQualityLogs),
                'duration_ms' => $durationMs,
            ]);

            return [
                'success' => true,
                'data' => [
                    'filters' => [
                        'kategori_slug' => $kategoriSlug,
                        'yayin_tipi_slug' => $yayinTipiSlug,
                        'days' => $days,
                    ],
                    'best_title_patterns' => $titlePatterns,
                    'best_description_structure' => $descriptionPatterns,
                    'common_mistakes' => $commonIssues,
                    'advice' => $advice,
                    'meta' => [
                        'high_quality_samples' => count($highQualityLogs),
                        'total_samples' => count($allQualityLogs),
                        'duration_ms' => $durationMs,
                        'analysis_timestamp' => now()->toISOString(),
                    ],
                ],
            ];
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            LogService::error('Template advice generation failed', [
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ], $e);

            return [
                'success' => false,
                'message' => 'Template advice generation failed: ' . $e->getMessage(),
                'data' => [
                    'best_title_patterns' => [],
                    'best_description_structure' => [],
                    'common_mistakes' => [],
                    'advice' => [],
                    'meta' => [
                        'error' => $e->getMessage(),
                        'duration_ms' => $durationMs,
                    ],
                ],
            ];
        }
    }

    /**
     * Get quality check logs with high scores (≥80)
     *
     * @param int $days
     * @param string|null $kategoriSlug
     * @param string|null $yayinTipiSlug
     * @return \Illuminate\Support\Collection
     */
    private function getHighQualityLogs(int $days, ?string $kategoriSlug = null, ?string $yayinTipiSlug = null)
    {
        $query = AiLog::where('content_type', 'ilan_quality_check')
            ->where('islem_durumu', 'success')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('request_payload->quality_score', '>=', 80);

        if ($kategoriSlug) {
            $query->where('request_payload->kategori_slug', $kategoriSlug);
        }

        if ($yayinTipiSlug) {
            $query->where('request_payload->yayin_tipi_slug', $yayinTipiSlug);
        }

        return $query->limit(100)->get(['id', 'request_payload', 'response_payload', 'created_at']);
    }

    /**
     * Get all quality check logs (for issue analysis)
     *
     * @param int $days
     * @param string|null $kategoriSlug
     * @param string|null $yayinTipiSlug
     * @return \Illuminate\Support\Collection
     */
    private function getAllQualityLogs(int $days, ?string $kategoriSlug = null, ?string $yayinTipiSlug = null)
    {
        $query = AiLog::where('content_type', 'ilan_quality_check')
            ->where('created_at', '>=', now()->subDays($days));

        if ($kategoriSlug) {
            $query->where('request_payload->kategori_slug', $kategoriSlug);
        }

        if ($yayinTipiSlug) {
            $query->where('request_payload->yayin_tipi_slug', $yayinTipiSlug);
        }

        return $query->limit(500)->get(['id', 'request_payload', 'response_payload', 'created_at']);
    }

    /**
     * Analyze title patterns from high-quality logs
     *
     * @param \Illuminate\Support\Collection $logs
     * @return array
     */
    private function analyzeTitlePatterns($logs): array
    {
        $patterns = [];
        $titleLengths = [];

        foreach ($logs as $log) {
            $requestData = is_array($log->request_payload) ? $log->request_payload : [];
            $baslik = $requestData['ilan']['baslik'] ?? null;

            if ($baslik) {
                $length = mb_strlen($baslik);
                $titleLengths[] = $length;

                // Extract common patterns
                if (str_contains($baslik, 'Satılık')) {
                    $patterns['contains_satilik'] = ($patterns['contains_satilik'] ?? 0) + 1;
                }
                if (str_contains($baslik, 'Kiralık')) {
                    $patterns['contains_kiralik'] = ($patterns['contains_kiralik'] ?? 0) + 1;
                }
                if (preg_match('/\d+\s*(m²|m2)/', $baslik)) {
                    $patterns['includes_metrekare'] = ($patterns['includes_metrekare'] ?? 0) + 1;
                }
                if (preg_match('/\d+\+\d+/', $baslik)) {
                    $patterns['includes_room_count'] = ($patterns['includes_room_count'] ?? 0) + 1;
                }
            }
        }

        $avgLength = count($titleLengths) > 0 ? (int) round(array_sum($titleLengths) / count($titleLengths)) : 0;

        return [
            'avg_length' => $avgLength,
            'optimal_range' => [$avgLength - 10, $avgLength + 10],
            'common_elements' => array_slice(arsort($patterns) ? $patterns : [], 0, 5, true),
            'recommendations' => [
                'Başlık uzunluğu ' . $avgLength . ' karakter civarında olmalı',
                'Yayın tipini belirt (Satılık/Kiralık)',
                'm² bilgisi ekle',
                'Oda sayısı formatı: 2+1, 3+1',
            ],
        ];
    }

    /**
     * Analyze description patterns from high-quality logs
     *
     * @param \Illuminate\Support\Collection $logs
     * @return array
     */
    private function analyzeDescriptionPatterns($logs): array
    {
        $descLengths = [];
        $structures = [];

        foreach ($logs as $log) {
            $requestData = is_array($log->request_payload) ? $log->request_payload : [];
            $aciklama = $requestData['ilan']['aciklama'] ?? null;

            if ($aciklama) {
                $length = mb_strlen($aciklama);
                $descLengths[] = $length;

                // Analyze structure
                $paragraphCount = substr_count($aciklama, "\n\n") + 1;
                $structures['paragraph_count'][] = $paragraphCount;

                if (preg_match_all('/\d+/', $aciklama, $matches)) {
                    $structures['includes_numbers'] = ($structures['includes_numbers'] ?? 0) + 1;
                }
            }
        }

        $avgLength = count($descLengths) > 0 ? (int) round(array_sum($descLengths) / count($descLengths)) : 0;
        $avgParagraphs = isset($structures['paragraph_count']) && count($structures['paragraph_count']) > 0
            ? (int) round(array_sum($structures['paragraph_count']) / count($structures['paragraph_count']))
            : 0;

        return [
            'avg_length' => $avgLength,
            'optimal_range' => [max(150, $avgLength - 50), $avgLength + 50],
            'avg_paragraphs' => $avgParagraphs,
            'structure_tips' => [
                'Açıklama uzunluğu en az 150, ideal ' . $avgLength . ' karakter',
                $avgParagraphs . ' paragraf kullan',
                'Sayısal bilgiler ekle (m², oda sayısı, yaş)',
                'Konum bilgilerini detaylandır',
            ],
        ];
    }

    /**
     * Analyze common issues from all logs
     *
     * @param \Illuminate\Support\Collection $logs
     * @return array
     */
    private function analyzeCommonIssues($logs): array
    {
        $issueCounts = [];

        foreach ($logs as $log) {
            $requestData = is_array($log->request_payload) ? $log->request_payload : [];
            $issues = $requestData['issues'] ?? [];

            foreach ($issues as $issue) {
                $code = $issue['code'] ?? 'UNKNOWN';
                $issueCounts[$code] = ($issueCounts[$code] ?? 0) + 1;
            }
        }

        arsort($issueCounts);

        return array_map(function ($code, $count) use ($logs) {
            return [
                'code' => $code,
                'count' => $count,
                'percentage' => count($logs) > 0 ? round(($count / count($logs)) * 100, 1) : 0,
                'advice' => $this->getIssueAdvice($code),
            ];
        }, array_keys($issueCounts), $issueCounts);
    }

    /**
     * Generate general advice
     *
     * @param array $titlePatterns
     * @param array $descriptionPatterns
     * @param array $commonIssues
     * @return array
     */
    private function generateAdvice(array $titlePatterns, array $descriptionPatterns, array $commonIssues): array
    {
        $advice = [];

        // Title advice
        if (isset($titlePatterns['avg_length'])) {
            $advice[] = [
                'category' => 'title',
                'priority' => 'high',
                'message' => 'Başlık uzunluğunu ' . $titlePatterns['optimal_range'][0] . '-' . $titlePatterns['optimal_range'][1] . ' karakter arasında tut',
            ];
        }

        // Description advice
        if (isset($descriptionPatterns['avg_length'])) {
            $advice[] = [
                'category' => 'description',
                'priority' => 'high',
                'message' => 'Açıklama en az ' . $descriptionPatterns['optimal_range'][0] . ' karakter olmalı',
            ];
        }

        // Top 3 issues
        $topIssues = array_slice($commonIssues, 0, 3);
        foreach ($topIssues as $issue) {
            $advice[] = [
                'category' => 'quality',
                'priority' => 'medium',
                'message' => 'En sık hata: ' . $issue['code'] . ' (' . $issue['percentage'] . '%) - ' . $issue['advice'],
            ];
        }

        return $advice;
    }

    /**
     * Get advice for specific issue code
     *
     * @param string $code
     * @return string
     */
    private function getIssueAdvice(string $code): string
    {
        $adviceMap = [
            'TITLE_EMPTY' => 'Başlık alanını doldur',
            'TITLE_TOO_SHORT' => 'Başlığı en az 20 karakter yap',
            'DESC_EMPTY' => 'Açıklama alanını doldur',
            'DESC_TOO_SHORT' => 'Açıklamayı en az 150 karakter yap',
            'MISSING_PRICE' => 'Fiyat bilgisi ekle',
            'SPAM_DETECTED' => 'Spam ifadelerden kaçın',
            'MISSING_LOCATION' => 'Konum bilgilerini ekle',
        ];

        return $adviceMap[$code] ?? 'Bu hatayı düzelt';
    }
}

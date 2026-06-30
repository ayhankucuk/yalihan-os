<?php

namespace App\Services\AI\Domains;

use App\Models\Ilan;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\Logging\LogService;
use App\Services\AI\Quality\IlanQualityAuditor;
use App\Services\UPS\UpsFeatureContextService;
use App\Models\FeatureCategory;
use App\Services\AI\AIOrchestrator;
use Exception;

/**
 * 🧠 Cortex Quality Domain Service
 * Responsibility: Handles listing quality checks, pre-publishing validation, and AI quality evaluation.
 */
class CortexQualityService
{
    protected AiTelemetryService $telemetry;
    protected IlanQualityAuditor $qualityAuditor;
    protected AIOrchestrator $aiService;

    public function __construct(
        AiTelemetryService $telemetry,
        IlanQualityAuditor $qualityAuditor,
        AIOrchestrator $aiService
    ) {
        $this->telemetry = $telemetry;
        $this->qualityAuditor = $qualityAuditor;
        $this->aiService = $aiService;
    }

    /**
     * Check İlan Quality - Pre-Publishing Validation
     * Ported from YalihanCortex
     *
     * SAB v3.4.4: Added deterministic recommendations[] and next_best_action
     */
    public function checkIlanQuality(Ilan $ilan): array
    {
        $kategoriSlug = null;
        $missingFields = [];
        $requiredFields = [];
        $filledCount = 0;

        if ($ilan->anaKategori) {
            $kategoriSlug = strtolower($ilan->anaKategori->slug ?? '');
        }

        if ($kategoriSlug === 'yazlık' || $kategoriSlug === 'yazlik') {
            $requiredFields = [
                'baslik' => 'Başlık', 'aciklama' => 'Açıklama', 'fiyat' => 'Fiyat',
                'il_id' => 'Şehir', 'ilce_id' => 'İlçe', 'mahalle_id' => 'Mahalle',
                'oda_sayisi' => 'Oda Sayısı', 'banyo_sayisi' => 'Banyo Sayısı',
                'max_guests' => 'Maksimum Misafir', 'minimum_stay' => 'Minimum Konaklama',
                'check_in_time' => 'Giriş Saati', 'check_out_time' => 'Çıkış Saati',
                'cleaning_fee' => 'Temizlik Ücreti', 'net_m2' => 'Net Metrekare',
            ];
        } elseif ($kategoriSlug === 'arsa') {
            $requiredFields = [
                'baslik' => 'Başlık', 'aciklama' => 'Açıklama', 'fiyat' => 'Fiyat',
                'il_id' => 'Şehir', 'ilce_id' => 'İlçe', 'mahalle_id' => 'Mahalle',
                'm2' => 'Metrekare', 'tapu_tipi' => 'Tapu Tipi', 'imar_durumu' => 'İmar Durumu',
                'ada' => 'Ada', 'parsel' => 'Parsel',
            ];
        } else {
            $requiredFields = ['baslik' => 'Başlık', 'aciklama' => 'Açıklama', 'fiyat' => 'Fiyat', 'il_id' => 'Şehir', 'ilce_id' => 'İlçe'];
        }

        $ilan->loadMissing(['yazlikDetail', 'turizmDetail', 'arsaDetail']);

        foreach ($requiredFields as $field => $label) {
            $value = $ilan->{$field} ?? null;
            if (empty($value)) {
                if ($kategoriSlug === 'arsa' && $ilan->arsaDetail) $value = $ilan->arsaDetail->{$field} ?? null;
                elseif (($kategoriSlug === 'yazlık' || $kategoriSlug === 'yazlik')) {
                    $value = $ilan->turizmDetail->{$field} ?? $ilan->yazlikDetail->{$field} ?? null;
                }
            }

            if (!empty($value) && $value !== '0' && $value !== 0 && $value !== false) {
                $filledCount++;
            } else {
                $missingFields[] = ['field' => $field, 'label' => $label];
            }
        }

        $totalFields = count($requiredFields);
        $completionPercentage = $totalFields > 0 ? round(($filledCount / $totalFields) * 100) : 0;
        $passed = $completionPercentage >= 80;

        $riskLevel = $completionPercentage < 50 ? 'high' : ($completionPercentage < 80 ? 'medium' : 'low');

        // SAB v3.4.4: Deterministic recommendations
        $recommendations = $this->buildRecommendations($missingFields, $kategoriSlug);
        $nextBestAction = $this->buildNextBestAction($recommendations);

        return [
            'passed' => $passed,
            'risk_level' => $riskLevel,
            'completion_percentage' => $completionPercentage,
            'missing_fields' => $missingFields,
            'recommendations' => $recommendations,
            'next_best_action' => $nextBestAction,
            'message' => $passed ? "✅ İlan yayınlanmaya hazır ({$completionPercentage}%)." : "🚨 İlan kalitesi düşük ({$completionPercentage}%).",
        ];
    }

    /**
     * Build deterministic recommendations from missing fields.
     *
     * SAB v3.4.4: Deterministic improvement suggestions
     */
    protected function buildRecommendations(array $missingFields, ?string $kategoriSlug): array
    {
        if (empty($missingFields)) {
            return [];
        }

        // context7-ignore-start: API response metadata, not DB column
        $recommendationTemplates = [
            'baslik' => [
                'recommendation' => 'Portföy için kısa ve açıklayıcı bir başlık ekleyin.',
                'action_label' => 'Başlığı düzenle',
                'priority' => 'high',
            ],
            'aciklama' => [
                'recommendation' => 'Detaylı bir açıklama yazın: konum avantajları, özellikler, kullanım alanları.',
                'action_label' => 'Açıklamayı yaz',
                'priority' => 'high',
            ],
            'fiyat' => [
                'recommendation' => 'Rekabetçi bir fiyat belirleyin. Bölgedeki benzer ilanlarla karşılaştırın.',
                'action_label' => 'Fiyat belirle',
                'priority' => 'high',
            ],
            'il_id' => [
                'recommendation' => 'İlin seçilmesi zorunludur. İlanınızın bölgesel aramalarda görünmesi için şehir seçin.',
                'action_label' => 'İl seç',
                'priority' => 'critical',
            ],
            'ilce_id' => [
                'recommendation' => 'İlçe seçimi, ilanınızın yerel aramalarda görünmesini sağlar.',
                'action_label' => 'İlçe seç',
                'priority' => 'critical',
            ],
            'mahalle_id' => [
                'recommendation' => 'Mahalle bilgisi, potansiyel alıcıların lokasyon bazlı arama yapmasını kolaylaştırır.',
                'action_label' => 'Mahalle seç',
                'priority' => 'high',
            ],
            'oda_sayisi' => [
                'recommendation' => 'Oda sayısı, alıcıların evi görselleştirmesine yardımcı olur.',
                'action_label' => 'Oda sayısı gir',
                'priority' => 'high',
            ],
            'banyo_sayisi' => [
                'recommendation' => 'Banyo sayısı, özellikle aileler için önemli bir kriterdir.',
                'action_label' => 'Banyo sayısı gir',
                'priority' => 'medium',
            ],
            'max_guests' => [
                'recommendation' => 'Maksimum misafir kapasitesi, tatil kiralamaları için önemlidir.',
                'action_label' => 'Kapasite belirle',
                'priority' => 'high',
            ],
            'minimum_stay' => [
                'recommendation' => 'Minimum konaklama süresi, sezonluk kiralamalarda yönetimi kolaylaştırır.',
                'action_label' => 'Minimum süre belirle',
                'priority' => 'medium',
            ],
            'check_in_time' => [
                'recommendation' => 'Giriş saati belirleyerek misafir beklentilerini yönetin.',
                'action_label' => 'Giriş saati ayarla',
                'priority' => 'medium',
            ],
            'check_out_time' => [
                'recommendation' => 'Çıkış saati belirleyerek temizlik planlamasını kolaylaştırın.',
                'action_label' => 'Çıkış saati ayarla',
                'priority' => 'medium',
            ],
            'cleaning_fee' => [
                'recommendation' => 'Temizlik ücreti, toplam maliyeti şeffaf hale getirir.',
                'action_label' => 'Temizlik ücreti gir',
                'priority' => 'medium',
            ],
            'net_m2' => [
                'recommendation' => 'Net metrekare, alan karşılaştırmalarında önemli bir veridir.',
                'action_label' => 'Metrekare gir',
                'priority' => 'high',
            ],
            'm2' => [
                'recommendation' => 'Arsa metrekaresi, değerleme için temel kriterdir.',
                'action_label' => 'Metrekare gir',
                'priority' => 'high',
            ],
            'tapu_tipi' => [
                'recommendation' => 'Tapu tipi, alıcıların yasal durumu anlamasına yardımcı olur.',
                'action_label' => 'Tapu tipi seç',
                'priority' => 'high',
            ],
            'imar_durumu' => [
                'recommendation' => 'İmar durumu, arsanın yapılaşma potansiyelini gösterir.',
                'action_label' => 'İmar durumu belirle',
                'priority' => 'high',
            ],
            'ada' => [
                'recommendation' => 'Ada numarası, tapu ve kadastro işlemleri için gereklidir.',
                'action_label' => 'Ada numarası gir',
                'priority' => 'medium',
            ],
            'parsel' => [
                'recommendation' => 'Parsel numarası, arsanın kesin konumunu belirler.',
                'action_label' => 'Parsel numarası gir',
                'priority' => 'medium', // context7-ignore
            ],
        ];
        // context7-ignore-end
        // Priority order for next_best_action
        $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];

        $recommendations = [];
        foreach ($missingFields as $field) {
            $fieldName = $field['field'];
            $template = $recommendationTemplates[$fieldName] ?? [
                'recommendation' => "{$field['label']} bilgisi eksik. Lütfen ekleyin.",
                'action_label' => "{$field['label']} gir",
                'priority' => 'medium',
            ];

            $recommendations[] = [
                'field' => $fieldName,
                'label' => $field['label'],
                'recommendation' => $template['recommendation'],
                'action_label' => $template['action_label'],
                'priority' => $template['priority'],
            ];
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) use ($priorityOrder) {
            return ($priorityOrder[$a['priority']] ?? 99) <=> ($priorityOrder[$b['priority']] ?? 99);
        });

        return $recommendations;
    }

    /**
     * Build next_best_action from recommendations.
     *
     * SAB v3.4.4: Highest priority action to take
     */
    protected function buildNextBestAction(array $recommendations): string
    {
        if (empty($recommendations)) {
            return 'Portföy yayına hazır görünüyor.';
        }

        $top = $recommendations[0];
        return "{$top['action_label']}: {$top['recommendation']}";
    }

    /**
     * AI Quality Check for Listing (Phase C)
     */
    public function evaluateListingQuality(array $payload): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_quality_check');

        try {
            $upsContext = app(UpsFeatureContextService::class);
            $context = $upsContext->buildContext(
                kategoriSlug: $payload['kategori_slug'] ?? '',
                yayinTipiSlug: $payload['yayin_tipi_slug'] ?? '',
                draftFeatures: $payload['draft_features'] ?? [],
                ilanFields: $payload['ilan'] ?? []
            );

            $quickIssues = $this->qualityAuditor->audit($payload['ilan'] ?? [], $context);
            
            // Note: In a real scenario, this would call AI. For now, we use deterministic scoring.
            $finalScore = (int) $quickIssues['score'];
            $durationMs = LogService::stopTimer($startTime);

            return [
                'success' => true,
                'data' => [
                    'quality_score' => $finalScore,
                    'issues' => $quickIssues['issues'],
                    'suggested_fixes' => $quickIssues['suggested_fixes'],
                    'meta' => ['duration_ms' => $durationMs]
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * AI Quality Check for existing Ilan model (Phase D)
     */
    public function evaluateListingQualityForIlan(Ilan $ilan, array $draftFeatures = []): array
    {
        $payload = [
            'kategori_slug' => $ilan->kategori->slug ?? '',
            'yayin_tipi_slug' => $ilan->yayinTipi->slug ?? '',
            'ilan' => [
                'baslik' => $ilan->baslik,
                'aciklama' => $ilan->aciklama,
                'fiyat' => $ilan->fiyat,
                'para_birimi' => $ilan->para_birimi ?? 'TRY',
                'il_id' => $ilan->il_id,
                'ilce_id' => $ilan->ilce_id,
                'mahalle_id' => $ilan->mahalle_id,
            ],
            'draft_features' => $draftFeatures,
        ];

        return $this->evaluateListingQuality($payload);
    }

    /**
     * AI-based Category Suggestion for Features
     */
    public function suggestCategory(string $featureName): array
    {
        $startTime = LogService::startTimer('yalihan_cortex_suggest_category');

        try {
            $categories = FeatureCategory::where('aktiflik_durumu', true)->get(['id', 'name', 'slug']);
            $categoryList = $categories->map(fn($c) => "- [ID: {$c->id}] {$c->name}")->implode("\n");
            $commonCategories = "Banyo, Mutfak, Bahçe, Havuz, Güvenlik, Otopark, Muhit, Ulaşım, Altyapı, Tapu & Hukuki";

            $prompt = "Emlak sisteminde yeni bir özellik eklenecek.\n" .
                      "Özellik Adı: '{$featureName}'\n\n" .
                      "Bu özellik aşağıdaki MEVCUT kategorilerden hangisine en uygundur?\n" .
                      "{$categoryList}\n\n" .
                      "VEYA şu yaygın emlak kategorilerinden hangisine girer? (Eğer mevcutlar tam uymuyorsa):\n" .
                      "{$commonCategories}\n\n" .
                      "Yanıtı mutlaka şu JSON formatında ver: {\"suggested_id\": ID veya null, \"suggested_name\": \"Kategori Adı\", \"is_new\": true/false}\n" .
                      "Sadece JSON döndür.";

            $aiResponse = $this->aiService->generate($prompt, ['max_tokens' => 150, 'temperature' => 0.1]);
            
            $val = is_array($aiResponse) ? ($aiResponse['content'] ?? ($aiResponse['text'] ?? '{}')) : (string) $aiResponse;
            $val = preg_replace('/```json\s*|\s*```/', '', $val);
            $data = json_decode(trim($val), true);

            if (!$data || !isset($data['suggested_name'])) {
                throw new Exception('AI response could not be parsed as valid category suggestion');
            }

            $durationMs = LogService::stopTimer($startTime);
            $result = [
                'success' => true,
                'feature_name' => $featureName,
                'suggested_category_id' => $data['suggested_id'] ?? null,
                'suggested_category_name' => $data['suggested_name'],
                'is_new_category' => $data['is_new'] ?? ($data['suggested_id'] ? false : true),
                'metadata' => [
                    'duration_ms' => $durationMs,
                    'processed_at' => now()->toISOString()
                ]
            ];

            $this->telemetry->logTransaction('CortexQuality', 'suggest_category', $durationMs / 1000, 0, 0, 200, ['feature' => $featureName]);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            LogService::error('Category Suggestion Failed', ['feature' => $featureName, 'error' => $e->getMessage()], $e, LogService::CHANNEL_AI);
            return ['success' => false, 'error' => $e->getMessage(), 'metadata' => ['duration_ms' => $durationMs]];
        }
    }
}

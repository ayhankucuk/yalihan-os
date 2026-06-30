<?php

namespace App\Services\AI\Copilot;

use App\Models\Ilan;
use App\Services\AI\IntelligenceHub;
use App\Services\AIDeal\DealPredictionService;
use App\Services\AI\ChurnRiskService;
use App\Services\AI\LeadScoreCalculator;
use Illuminate\Support\Facades\Log;

class CopilotPredictionEngine
{
    public function __construct(
        protected IntelligenceHub $intelligenceHub,
        protected DealPredictionService $dealPredictor,
    ) {}

    public function predict(array $context): array
    {
        return match ($context['type']) {
            'ilan-detail', 'ilan-edit' => $this->ilanPredictions($context),
            'dashboard' => $this->dashboardPredictions($context),
            'ilan-list' => $this->ilanListPredictions($context),
            'crm-detail', 'crm-edit' => $this->crmPredictions($context),
            'crm-list', 'crm-dashboard' => $this->crmListPredictions($context),
            'talep-detail', 'talep-edit' => $this->talepPredictions($context),
            default => $this->defaultPredictions($context),
        };
    }

    protected function ilanPredictions(array $context): array
    {
        $ilanId = $context['entity_id'] ?? null;
        if (!$ilanId) {
            return $this->defaultPredictions($context);
        }

        $ilan = Ilan::find($ilanId);
        if (!$ilan) {
            return $this->defaultPredictions($context);
        }

        try {
            // Use IntelligenceHub for health scoring
            $health = $this->intelligenceHub->getListingHealth($ilanId);

            // Use DealPredictor for deal probability
            $prediction = $this->dealPredictor->predict($ilan, ['trigger' => 'copilot']);

            $overallHealth = $health['overall_health'] ?? 0;
            $dealProbability = $prediction['scores']['total'] ?? 0;

            // §4.3 Explainable Prediction — Build signal trace
            $signals = $this->buildExplainableSignals($context['data'] ?? [], $health, $prediction);

            return array_merge([
                'health_score' => round($overallHealth),
                'health_label' => $this->scoreLabel($overallHealth),
                'deal_probability' => round($dealProbability),
                'risk_level' => $this->riskLevel($overallHealth, $dealProbability),
                'potential_score' => $this->calculatePotential($context['data'] ?? [], $overallHealth),
                'breakdown' => [
                    'market' => $health['scores']['market']['score'] ?? 0,
                    'quality' => $health['scores']['quality']['score'] ?? 0,
                    'seo' => $health['scores']['seo']['score'] ?? 0,
                    'match' => $health['scores']['match']['score'] ?? 0,
                ],
            ], $signals);
        } catch (\Exception $e) {
            Log::warning('CopilotPredictionEngine ilan prediction failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);

            // Fallback to data-based scoring
            return $this->dataBasedScore($context['data'] ?? []);
        }
    }

    /**
     * §4.3 Explainable Prediction — Supporting/Weak/Missing signal trace
     */
    protected function buildExplainableSignals(array $data, array $health, array $prediction): array
    {
        $supporting = [];
        $weak = [];
        $missing = [];

        // Photo signals
        $photoCount = $data['photo_count'] ?? 0;
        if ($photoCount >= 5) {
            $supporting[] = ['signal' => 'Yeterli fotoğraf', 'detail' => $photoCount . ' fotoğraf mevcut', 'impact' => '+15'];
        } elseif ($photoCount > 0) {
            $weak[] = ['signal' => 'Az fotoğraf', 'detail' => $photoCount . '/5 fotoğraf', 'impact' => '-10'];
        } else {
            $missing[] = ['signal' => 'Fotoğraf yok', 'detail' => 'En az 5 fotoğraf önerilir', 'impact' => '-25'];
        }

        // Price signal
        if ($data['has_price'] ?? false) {
            $supporting[] = ['signal' => 'Fiyat bilgisi mevcut', 'detail' => 'Aramada görünür', 'impact' => '+10'];
        } else {
            $missing[] = ['signal' => 'Fiyat eksik', 'detail' => 'Aramalarda görünmez', 'impact' => '-30'];
        }

        // Description signal
        if ($data['has_description'] ?? false) {
            $ilan = $data['ilan'] ?? [];
            $aciklamaLen = mb_strlen($ilan['aciklama'] ?? '');
            if ($aciklamaLen >= 150) {
                $supporting[] = ['signal' => 'Detaylı açıklama', 'detail' => $aciklamaLen . ' karakter', 'impact' => '+10'];
            } else {
                $weak[] = ['signal' => 'Kısa açıklama', 'detail' => $aciklamaLen . ' karakter (min 150)', 'impact' => '-5'];
            }
        } else {
            $missing[] = ['signal' => 'Açıklama eksik', 'detail' => 'SEO ve güven için gerekli', 'impact' => '-15'];
        }

        // Location signal
        if ($data['has_location'] ?? false) {
            $supporting[] = ['signal' => 'Konum bilgisi mevcut', 'detail' => 'Bölgesel aramalarda görünür', 'impact' => '+10'];
        } else {
            $missing[] = ['signal' => 'Konum eksik', 'detail' => 'Arama filtrelemesi çalışmaz', 'impact' => '-15'];
        }

        // Coordinates signal
        if ($data['has_coordinates'] ?? false) {
            $supporting[] = ['signal' => 'Harita koordinatı var', 'detail' => 'Haritada gösterilir', 'impact' => '+5'];
        } else {
            $weak[] = ['signal' => 'Koordinat yok', 'detail' => 'Haritada gösterilmez', 'impact' => '-5'];
        }

        // Category/template signal
        if ($data['has_category'] ?? false) {
            $supporting[] = ['signal' => 'Kategori seçili', 'detail' => 'Doğru filtreleme', 'impact' => '+5'];
        } else {
            $missing[] = ['signal' => 'Kategori eksik', 'detail' => 'Filtreleme çalışmaz', 'impact' => '-10'];
        }

        // Market score from IntelligenceHub
        $marketScore = $health['scores']['market']['score'] ?? null;
        if ($marketScore !== null) {
            if ($marketScore >= 60) {
                $supporting[] = ['signal' => 'Pazar skoru iyi', 'detail' => 'Pazar: ' . round($marketScore) . '/100', 'impact' => '+' . round($marketScore * 0.25)];
            } else {
                $weak[] = ['signal' => 'Pazar skoru düşük', 'detail' => 'Pazar: ' . round($marketScore) . '/100', 'impact' => '-' . round((100 - $marketScore) * 0.1)];
            }
        }

        return [
            'explainable' => [
                'supporting_signals' => $supporting,
                'weak_signals' => $weak,
                'missing_data' => $missing,
                'signal_count' => count($supporting) + count($weak) + count($missing),
                'confidence_note' => $this->explainableConfidenceNote($supporting, $weak, $missing),
            ],
        ];
    }

    protected function explainableConfidenceNote(array $supporting, array $weak, array $missing): string
    {
        $ratio = count($supporting) / max(count($supporting) + count($weak) + count($missing), 1);

        return match (true) {
            $ratio >= 0.7 => 'Yüksek güvenilirlik — çoğu veri mevcut ve destekleyici.',
            $ratio >= 0.4 => 'Orta güvenilirlik — bazı veriler eksik veya zayıf.',
            default => 'Düşük güvenilirlik — kritik veriler eksik, tahmin güvenilir değil.',
        };
    }

    protected function dashboardPredictions(array $context): array
    {
        $data = $context['data'] ?? [];
        $totalIlan = $data['toplam_ilan'] ?? 0;
        $aktifIlan = $data['aktif_ilan'] ?? 0;
        $fotosuzIlan = $data['fotosuz_ilan'] ?? 0;
        $fiyatsizIlan = $data['fiyatsiz_ilan'] ?? 0;

        $portfolioHealth = $totalIlan > 0
            ? round((1 - (($fotosuzIlan + $fiyatsizIlan) / max($aktifIlan, 1))) * 100)
            : 0;

        return [
            'health_score' => max(0, min(100, $portfolioHealth)),
            'health_label' => $this->scoreLabel($portfolioHealth),
            'risk_level' => $fiyatsizIlan > 0 ? 'high' : ($fotosuzIlan > 0 ? 'medium' : 'low'),
            'potential_score' => $aktifIlan > 0 ? min(100, $aktifIlan * 5) : 0,
        ];
    }

    protected function ilanListPredictions(array $context): array
    {
        $data = $context['data'] ?? [];
        $toplam = $data['toplam'] ?? 0;
        $fotosuz = $data['fotosuz'] ?? 0;
        $fiyatsiz = $data['fiyatsiz'] ?? 0;
        $aktif = $data['aktif'] ?? 0;

        $completeness = $aktif > 0
            ? round((1 - (($fotosuz + $fiyatsiz) / max($aktif, 1))) * 100)
            : 0;

        return [
            'health_score' => max(0, min(100, $completeness)),
            'health_label' => $this->scoreLabel($completeness),
            'risk_level' => $fiyatsiz > 0 ? 'high' : ($fotosuz > 0 ? 'medium' : 'low'),
        ];
    }

    /**
     * §8 CRM Predictions — Lead scoring integration
     */
    protected function crmPredictions(array $context): array
    {
        $data = $context['data'] ?? [];
        $kisi = $data['kisi'] ?? [];
        $kisiId = $kisi['id'] ?? null;

        // Build lead completeness score
        $completeness = 0;
        $maxFields = 6;

        if (!empty($data['has_phone'])) $completeness++;
        if (!empty($data['has_email'])) $completeness++;
        if (!empty($kisi['il_id'])) $completeness++;
        if (!empty($kisi['meslek'])) $completeness++;
        if (($data['talep_count'] ?? 0) > 0) $completeness++;
        if (!empty($kisi['ad']) && !empty($kisi['soyad'])) $completeness++;

        $profileScore = round(($completeness / $maxFields) * 100);

        // Intent signals
        $intentSignals = [];
        $intentScore = 0;

        if (($data['talep_count'] ?? 0) > 0) {
            $intentSignals[] = 'Aktif talep var';
            $intentScore += 30;
        }
        if (($data['acik_talep'] ?? 0) > 0) {
            $intentSignals[] = $data['acik_talep'] . ' açık talep';
            $intentScore += 20;
        }
        if (!empty($data['has_phone']) && !empty($data['has_email'])) {
            $intentSignals[] = 'Tam iletişim bilgisi';
            $intentScore += 15;
        }
        if (($data['eslesme_count'] ?? 0) > 0) {
            $intentSignals[] = 'Eşleşme geçmişi var';
            $intentScore += 15;
        }

        $intentScore = min(100, $intentScore);

        // Temperature
        $temperature = match (true) {
            $intentScore >= 60 => 'Sıcak',
            $intentScore >= 30 => 'Ilık',
            default => 'Soğuk',
        };

        return [
            'health_score' => $profileScore,
            'health_label' => $this->scoreLabel($profileScore),
            'risk_level' => $profileScore < 40 ? 'high' : ($profileScore < 70 ? 'medium' : 'low'),
            'lead_temperature' => $temperature,
            'intent_score' => $intentScore,
            'intent_signals' => $intentSignals,
            'profile_completeness' => $profileScore,
            'explainable' => [
                'supporting_signals' => $intentScore >= 30 ? [
                    ['signal' => 'Lead sıcaklığı: ' . $temperature, 'detail' => 'Niyet skoru: ' . $intentScore, 'impact' => '+' . $intentScore],
                ] : [],
                'weak_signals' => $profileScore < 70 ? [
                    ['signal' => 'Profil tamamlığı düşük', 'detail' => $profileScore . '%', 'impact' => '-' . (100 - $profileScore)],
                ] : [],
                'missing_data' => array_values(array_filter([
                    empty($data['has_phone']) ? ['signal' => 'Telefon eksik', 'detail' => 'İletişim kritik', 'impact' => '-20'] : null,
                    empty($data['has_email']) ? ['signal' => 'E-posta eksik', 'detail' => 'Pazarlama kısıtlı', 'impact' => '-10'] : null,
                    ($data['talep_count'] ?? 0) === 0 ? ['signal' => 'Talep yok', 'detail' => 'Eşleştirme yapılamaz', 'impact' => '-30'] : null,
                ])),
                'confidence_note' => $profileScore >= 70 ? 'Yeterli CRM verisi mevcut.' : 'Profil bilgileri eksik — tahmin güvenilirliği düşük.',
            ],
        ];
    }

    /**
     * §8 CRM List Predictions — Aggregate lead health
     */
    protected function crmListPredictions(array $context): array
    {
        $data = $context['data'] ?? [];
        $toplam = $data['toplam_kisi'] ?? 0;
        $iletisimsiz = $data['iletisimsiz'] ?? 0;
        $talepsiz = $data['talepsiz'] ?? 0;

        $crmHealth = $toplam > 0
            ? round((1 - (($iletisimsiz + $talepsiz) / max($toplam * 2, 1))) * 100)
            : 0;

        return [
            'health_score' => max(0, min(100, $crmHealth)),
            'health_label' => $this->scoreLabel($crmHealth),
            'risk_level' => $iletisimsiz > 5 ? 'high' : ($talepsiz > 10 ? 'medium' : 'low'),
        ];
    }

    /**
     * §9.2 Talep Predictions — Match readiness
     */
    protected function talepPredictions(array $context): array
    {
        $data = $context['data'] ?? [];
        $talep = $data['talep'] ?? [];

        // Match readiness score
        $readiness = 0;
        $maxFactors = 5;

        if (!empty($data['has_kisi'])) $readiness++;
        if (!empty($talep['il_id'])) $readiness++;
        if (!empty($talep['min_fiyat']) || !empty($talep['max_fiyat'])) $readiness++;
        if (!empty($talep['emlak_tipi'])) $readiness++;
        if (!empty($talep['talep_tipi'])) $readiness++;

        $readinessScore = round(($readiness / $maxFactors) * 100);

        $matchReady = $readinessScore >= 60;

        return [
            'health_score' => $readinessScore,
            'health_label' => $matchReady ? 'Eşleştirmeye Hazır' : 'Bilgi Eksik',
            'risk_level' => $readinessScore < 40 ? 'high' : ($readinessScore < 70 ? 'medium' : 'low'),
            'match_readiness' => $readinessScore,
            'match_ready' => $matchReady,
            'explainable' => [
                'supporting_signals' => array_values(array_filter([
                    !empty($data['has_kisi']) ? ['signal' => 'Kişi bağlı', 'detail' => 'İletişim yapılabilir', 'impact' => '+20'] : null,
                    !empty($talep['il_id']) ? ['signal' => 'Konum belirtilmiş', 'detail' => 'Bölge filtresi aktif', 'impact' => '+20'] : null,
                    (!empty($talep['min_fiyat']) || !empty($talep['max_fiyat'])) ? ['signal' => 'Bütçe belirtilmiş', 'detail' => 'Fiyat filtresi aktif', 'impact' => '+20'] : null,
                ])),
                'missing_data' => array_values(array_filter([
                    empty($data['has_kisi']) ? ['signal' => 'Kişi bağlantısı yok', 'detail' => 'Sahipsiz talep', 'impact' => '-20'] : null,
                    empty($talep['il_id']) ? ['signal' => 'Konum belirtilmemiş', 'detail' => 'Eşleştirme alanı belirsiz', 'impact' => '-20'] : null,
                    (empty($talep['min_fiyat']) && empty($talep['max_fiyat'])) ? ['signal' => 'Bütçe belirtilmemiş', 'detail' => 'Fiyat filtresi kullanılamaz', 'impact' => '-20'] : null,
                ])),
                'weak_signals' => [],
                'confidence_note' => $matchReady ? 'Eşleştirme için yeterli veri mevcut.' : 'Eksik kriterler eşleştirme kalitesini düşürür.',
            ],
        ];
    }

    protected function defaultPredictions(array $context): array
    {
        return [
            'health_score' => null,
            'health_label' => null,
            'risk_level' => 'unknown',
        ];
    }

    protected function dataBasedScore(array $data): array
    {
        $score = 100;
        $deductions = [];

        if (!($data['has_price'] ?? true)) {
            $score -= 30;
            $deductions[] = 'fiyat_eksik';
        }
        if (($data['photo_count'] ?? 0) === 0) {
            $score -= 25;
            $deductions[] = 'foto_eksik';
        } elseif (($data['photo_count'] ?? 0) < 5) {
            $score -= 10;
            $deductions[] = 'az_foto';
        }
        if (!($data['has_description'] ?? true)) {
            $score -= 15;
            $deductions[] = 'aciklama_eksik';
        }
        if (!($data['has_location'] ?? true)) {
            $score -= 15;
            $deductions[] = 'konum_eksik';
        }
        if (!($data['has_category'] ?? true)) {
            $score -= 10;
            $deductions[] = 'kategori_eksik';
        }

        $score = max(0, $score);

        return [
            'health_score' => $score,
            'health_label' => $this->scoreLabel($score),
            'deal_probability' => null,
            'risk_level' => $this->riskLevel($score, null),
            'potential_score' => $this->calculatePotential($data, $score),
            'deductions' => $deductions,
        ];
    }

    protected function calculatePotential(array $data, float $healthScore): int
    {
        // Potential = how much room for improvement
        $missing = 0;
        if (!($data['has_price'] ?? true)) $missing++;
        if (($data['photo_count'] ?? 0) < 5) $missing++;
        if (!($data['has_description'] ?? true)) $missing++;
        if (!($data['has_location'] ?? true)) $missing++;
        if (!($data['has_coordinates'] ?? true)) $missing++;

        // Higher missing = higher potential for improvement
        if ($missing === 0) {
            return min(100, (int) $healthScore);
        }

        return min(100, (int) ($healthScore + ($missing * 10)));
    }

    protected function scoreLabel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Mükemmel',
            $score >= 60 => 'İyi',
            $score >= 40 => 'Orta',
            default => 'Düşük',
        };
    }

    protected function riskLevel(float $healthScore, ?float $dealProb): string
    {
        $combined = $dealProb !== null
            ? ($healthScore * 0.6 + $dealProb * 0.4)
            : $healthScore;

        return match (true) {
            $combined >= 70 => 'low',
            $combined >= 40 => 'medium',
            default => 'high',
        };
    }
}

<?php

namespace App\Services\AI\Portfolio;

use App\Models\Ilan;
use App\Models\User;
use App\Services\AI\IntelligenceHub;
use App\Services\Logging\LogService;
use Illuminate\Support\Collection;

/**
 * 🏥 AI Portfolio Doctor Service
 *
 * Responsibility: Orchestrates portfolio-level health diagnostics and actions.
 * Domain: AI / Decision Augmentation
 * Context7 Compliant: ✅
 */
class PortfolioDoctorService
{
    public function __construct(
        private IntelligenceHub $intelligenceHub,
        private \App\Application\AI\Actions\AuditPortfolioHealthAction $auditAction
    ) {}

    /**
     * Get summary view for the doctor's dashboard
     */
    public function getPortfolioSummary(User $user): array
    {
        $listings = Ilan::where('danisman_id', $user->id)
            ->whereIn('yayin_durumu', ['yayinda', 'onay_bekliyor', 'taslak'])
            ->get();

        if ($listings->isEmpty()) {
            return [
                'total_listings' => 0,
                'average_health' => 0,
                'critical_issue_count' => 0,
                'portfolio_health' => 'empty',
                'last_sync' => now()->toISOString(),
                'listings' => [],
            ];
        }

        $healthData = [];
        $criticalCount = 0;
        $listingsData = [];

        foreach ($listings as $listing) {
            $report = $this->getDiagnosticReport($listing);
            $healthData[] = $report['health']['overall_health'] ?? 0;

            if (($report['health']['overall_health'] ?? 0) < 40) {
                $criticalCount++;
            }

            // Normalize health payload to match Context7 standard explicitly if needed,
            // or use the full report. For exact Context7 compliance of the requested schema:
            $report['health'] = [
                'market_score' => $report['health']['market_score'] ?? 0,
                'quality_score' => $report['health']['quality_score'] ?? 0,
                'seo_score' => $report['health']['seo_score'] ?? 0,
                'match_score' => $report['health']['match_potential'] ?? 0,
            ];

            $listingsData[] = $report;
        }

        $avgHealth = count($healthData) > 0 ? (int)(array_sum($healthData) / count($healthData)) : 0;

        return [
            'total_listings' => $listings->count(),
            'average_health' => $avgHealth,
            'critical_issue_count' => $criticalCount,
            'portfolio_health' => $this->getPortfolioHealth($avgHealth, $criticalCount),
            'last_sync' => now()->toISOString(),
            'listings' => $listingsData,
        ];
    }

    /**
     * Get listings that need immediate attention
     */
    public function getProblematicListings(User $user, int $threshold = 60): Collection
    {
        return Ilan::where('danisman_id', $user->id)
            ->whereIn('yayin_durumu', ['yayinda', 'onay_bekliyor', 'taslak'])
            ->get()
            ->map(function ($ilan) {
                return $this->getDiagnosticReport($ilan);
            })
            ->filter(function ($report) use ($threshold) {
                return $report['health']['overall_health'] < $threshold;
            })
            ->sortBy('health.overall_health')
            ->values();
    }

    /**
     * Deep diagnostic report for a specific listing
     */
    public function getDiagnosticReport(Ilan $ilan): array
    {
        $health = $this->intelligenceHub->getListingHealth($ilan->id);

        return [
            'ilan' => [
                'id' => $ilan->id,
                'baslik' => $ilan->baslik,
                'fiyat' => $ilan->fiyat,
                'para_birimi' => $ilan->para_birimi,
                'slug' => $ilan->slug,
            ],
            'health' => $health,
            'diagnosis' => $this->generateDiagnosis($health),
            'treatment_plan' => $this->getTreatmentPlan($ilan, $health),
        ];
    }

    /**
     * Generate actionable treatment plan
     */
    public function getTreatmentPlan(Ilan $ilan, array $health): array
    {
        $recommendations = $health['recommendations'] ?? [];
        $actions = [];

        foreach ($recommendations as $rec) {
            $actions[] = [
                'action_id' => $rec['kategori'] . '_' . ($rec['code'] ?? 'fix'),
                'title' => $this->getActionTitle($rec),
                'description' => $rec['message'],
                'priority' => $rec['priority'] ?? 'medium',
                'impact' => $this->getActionImpact($rec),
                'url' => route('admin.ilanlar.edit', $ilan->id), // Default edit link
            ];
        }

        return $actions;
    }

    protected function generateDiagnosis(array $health): string
    {
        $score = $health['overall_health'] ?? 0;

        if ($score >= 80) {
            return "İlanın genel durumu oldukça sağlıklı. Ufak dokunuşlarla mükemmelleştirilebilir.";
        } elseif ($score >= 60) {
            return "İlan kabul edilebilir seviyede ancak rakip ilanlara göre geride kalma riski taşıyor.";
        } elseif ($score >= 40) {
            return "İlanın kritik eksikleri var. Özellikle market rekabeti veya veri kalitesi sorunlu.";
        } else {
            return "İlan acil müdahale gerektiriyor. Mevcut haliyle satış/kiralama şansı çok düşük.";
        }
    }

    /**
     * Perform a deep AI audit on the entire portfolio.
     * Uses DeepSeek via Cortex Routing for cost-effective batch analysis.
     */
    public function auditPortfolio(User $user): array
    {
        $summary = $this->getPortfolioSummary($user);
        
        if ($summary['total_listings'] === 0) {
            return $summary;
        }

        $cortexResponse = $this->auditAction->execute([
            'user' => $user->only(['id', 'name']),
            'summary_stats' => [
                'total' => $summary['total_listings'],
                'avg_health' => $summary['average_health'],
                'critical_count' => $summary['critical_issue_count'],
            ],
            'listings' => collect($summary['listings'])->map(fn($l) => [
                'id' => $l['ilan']['id'],
                'title' => $l['ilan']['baslik'],
                'health' => $l['health']['overall_health'] ?? 0,
                'diagnosis' => $l['diagnosis']
            ])->toArray()
        ]);

        return array_merge($summary, [
            'ai_audit' => $cortexResponse->success ? $cortexResponse->output : null,
            'audit_trace_id' => $cortexResponse->traceId,
            'audit_provider' => $cortexResponse->provider,
        ]);
    }

    protected function getPortfolioHealth(int $avgHealth, int $criticalCount): string
    {
        if ($criticalCount > 3) return 'danger';
        if ($avgHealth < 60) return 'warning';
        return 'healthy';
    }

    protected function getActionTitle(array $recommendation): string
    {
        return match ($recommendation['kategori']) {
            'market' => 'Fiyat Optimizasyonu',
            'quality' => 'Veri Tamamlama',
            'seo' => 'İçerik Geliştirme',
            'match' => 'Sunum İyileştirmesi',
            default => 'Genel İyileştirme',
        };
    }

    protected function getActionImpact(array $recommendation): string
    {
        return match ($recommendation['kategori']) {
            'market' => 'Yüksek (Dönüşüm Oranı)',
            'quality' => 'Orta (Güvenilirlik)',
            'seo' => 'Orta (Görünürlük)',
            default => 'Düşük',
        };
    }
}

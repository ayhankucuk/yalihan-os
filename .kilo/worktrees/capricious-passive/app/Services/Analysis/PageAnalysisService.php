<?php

namespace App\Services\Analysis;

/**
 * @sab-ignore-thin
 */

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\Analysis\EmlakYonetimPageAnalyzer;
use App\Services\Analysis\PageAnalyticsService;

class PageAnalysisService
{
    public function __construct(
        private PageAnalyticsService $analyticsService,
        private EmlakYonetimPageAnalyzer $emlakAnalyzer
    ) {}

    /**
     * Run the enhanced analysis (Phase 1A: Refactored to use Artisan::call)
     */
    public function runEnhancedAnalysis()
    {
        // 🛡️ SAB Phase 1A: Replace exec() with Artisan::call()
        // Note: The command 'analyze:pages-complete' should be registered in the system.
        // If it's missing, Artisan::call will return non-zero exit code.
        try {
            $exitCode = Artisan::call('analyze:pages-complete');
            
            if ($exitCode !== 0) {
                Log::warning('PageAnalysisService: analyze:pages-complete failed or not found.', ['exit_code' => $exitCode]);
                return $this->generateFallbackData();
            }

            $results = json_decode(Storage::get('analysis/complete_pages_analysis.json'), true);

            if (!$results) {
                return $this->getDefaultEnhancedResults();
            }

            $pages = $results['pages'] ?? [];
            $recommendations = $this->generateRecommendations($pages);

            return [
                'total_pages' => $results['total_pages'] ?? 0,
                'critical_count' => $results['critical_count'] ?? 0,
                'warning_count' => $results['warning_count'] ?? 0,
                'success_count' => $results['success_count'] ?? 0,
                'average_score' => $results['average_score'] ?? 0,
                'pages' => $pages,
                'recommendations' => $recommendations,
                'css_stats' => $results['css_compliance_stats'] ?? [],
                'innovation_stats' => $results['innovation_stats'] ?? [],
                'category_breakdown' => $this->calculateCategoryBreakdown($pages),
            ];

        } catch (\Exception $e) {
            Log::error('PageAnalysisService: runEnhancedAnalysis error', ['error' => $e->getMessage()]);
            return $this->generateFallbackData();
        }
    }

    /**
     * Run complete analysis (Legacy method, refactored)
     */
    public function runCompleteAnalysis()
    {
        $jsonFile = storage_path('app/analysis/complete_pages_analysis.json');

        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $data = json_decode($jsonContent, true);
            if ($data) {
                return $this->processAnalysisData($data);
            }
        }

        // 🛡️ SAB Phase 1A: Replace exec() with Artisan::call()
        try {
            $exitCode = Artisan::call('analyze:pages-complete');
            
            if ($exitCode === 0 && file_exists($jsonFile)) {
                $jsonContent = file_get_contents($jsonFile);
                $data = json_decode($jsonContent, true);
                if ($data) {
                    return $this->processAnalysisData($data);
                }
            }
        } catch (\Exception $e) {
            Log::error('PageAnalysisService: runCompleteAnalysis execution failed', ['error' => $e->getMessage()]);
        }

        return $this->generateFallbackData();
    }

    /**
     * Run single page analysis (Refactored to use Artisan::call with parameters)
     */
    public function runSinglePageAnalysis(string $page)
    {
        // 🛡️ SECURITY P0: Strict Allowlist Validation
        $allowedPages = ['index', 'dashboard', 'wizard', 'listings', 'analytics', 'notifications', 'kisi', 'talep'];
        
        if (!in_array($page, $allowedPages)) {
            Log::warning('PageAnalysisService: Unauthorized page analysis attempt blocked.', ['page' => $page]);
            return ['error' => 'Unauthorized page type'];
        }

        // 🛡️ SAB Phase 1A: Security hardening. 
        // Use Artisan::call with argument array instead of string interpolation in exec().
        try {
            $exitCode = Artisan::call('analyze:pages-complete', [
                '--page' => $page,
                '--format' => 'json'
            ]);

            if ($exitCode === 0) {
                $output = Artisan::output();
                return json_decode($output, true) ?? [];
            }
        } catch (\Exception $e) {
            Log::error('PageAnalysisService: runSinglePageAnalysis failed', ['page' => $page, 'error' => $e->getMessage()]);
        }

        return [];
    }

    public function runEmlakAnalysis()
    {
        try {
            $emlakPages = $this->emlakAnalyzer->analyzeEmlakPages();
            
            $totalPages = count($emlakPages);
            $criticalCount = 0;
            $warningCount = 0;
            $successCount = 0;
            $totalScore = 0;

            foreach ($emlakPages as $pageKey => $analysis) {
                $totalScore += $analysis['score'];
                switch ($analysis['severity']) {
                    case 'critical': $criticalCount++; break;
                    case 'warning': $warningCount++; break;
                    case 'success': $successCount++; break;
                }
            }

            return [
                'total_pages' => $totalPages,
                'critical_count' => $criticalCount,
                'warning_count' => $warningCount,
                'success_count' => $successCount,
                'average_score' => $totalPages > 0 ? round($totalScore / $totalPages, 1) : 0,
                'pages' => $emlakPages,
                'recommendations' => $this->emlakAnalyzer->generateEmlakRecommendations($emlakPages),
                'category_breakdown' => $this->calculateCategoryBreakdown($emlakPages),
            ];
        } catch (\Exception $e) {
            Log::error('PageAnalysisService: Emlak analysis error', ['error' => $e->getMessage()]);
            return $this->generateFallbackData();
        }
    }

    public function processAnalysisData($data)
    {
        $severityStats = $data['statistics'] ?? ['critical' => 0, 'danger' => 0, 'warning' => 0, 'success' => 0];
        $pageDetails = [];

        foreach ($data['results'] ?? [] as $pageKey => $analysis) {
            $pageDetails[] = [
                'name' => $analysis['page'],
                'score' => $analysis['score'],
                'severity' => $analysis['severity'],
                'type' => $analysis['type'] ?? 'unknown', // context7-ignore
                'controller' => $analysis['controller'],
                'issues' => $analysis['controller_analysis']['issues'] ?? [],
            ];
        }

        return [
            'total_pages' => $data['total_pages'] ?? 0,
            'critical_count' => ($severityStats['critical'] ?? 0) + ($severityStats['danger'] ?? 0),
            'warning_count' => $severityStats['warning'] ?? 0,
            'success_count' => $severityStats['success'] ?? 0,
            'average_score' => $data['average_score'] ?? 0,
            'pages' => $pageDetails,
            'recommendations' => $this->generateRecommendations($pageDetails),
            'css_stats' => $data['css_compliance_stats'] ?? [],
            'innovation_stats' => $data['innovation_stats'] ?? [],
            'category_breakdown' => $this->calculateCategoryBreakdown($pageDetails),
        ];
    }

    public function calculateCategoryBreakdown($pages)
    {
        $breakdown = [];
        foreach ($pages as $page) {
            $category = $page['category'] ?? 'General';
            if (!isset($breakdown[$category])) {
                $breakdown[$category] = ['count' => 0, 'total_score' => 0, 'avg_score' => 0];
            }
            $breakdown[$category]['count']++;
            $breakdown[$category]['total_score'] += $page['score'] ?? 0;
            $breakdown[$category]['avg_score'] = $breakdown[$category]['count'] > 0 
                ? round($breakdown[$category]['total_score'] / $breakdown[$category]['count'], 1) 
                : 0;
        }
        return $breakdown;
    }

    public function generateRecommendations($pages)
    {
        // Simple mock recommendations for now, logic can be ported from controller if needed
        return [
            ['priority' => 'HIGH', 'title' => 'Controller Implementation', 'description' => 'Eksik controllerları tamamlayın.'],
            ['priority' => 'MEDIUM', 'title' => 'CSS Compliance', 'description' => 'Modern CSS standartlarına geçin.']
        ];
    }

    public function generateFallbackData()
    {
        return [
            'total_pages' => 0,
            'critical_count' => 0,
            'warning_count' => 0,
            'success_count' => 0,
            'average_score' => 0,
            'pages' => [],
            'recommendations' => ['Analysis failed. Please run artisan analyze:pages-complete manually.'],
            'category_breakdown' => [],
            'css_stats' => [],
            'innovation_stats' => []
        ];
    }

    private function getDefaultEnhancedResults()
    {
        return $this->generateFallbackData();
    }
}

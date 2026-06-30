<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Log;

/**
 * TKGM Bulk Query Service
 *
 * [SAB v24.0] Stabilization Layer
 * Purpose: Handle batch TKGM queries safely without request timeout risks.
 */
class TkgmBulkQueryService
{
    public function __construct(
        private readonly TKGMService $tkgmService
    ) {}

    /**
     * Process a batch of TKGM queries
     *
     * @param array $queries
     * @return array
     */
    public function bulkQuery(array $queries): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        // Stabilization: Increase time limit based on query count
        // 0.5s sleep + average 2s API time = ~2.5s per query
        $queryCount = count($queries);
        $estimatedTime = $queryCount * 3;
        
        if ($estimatedTime > 30) {
            @set_time_limit($estimatedTime + 60);
        }

        foreach ($queries as $index => $query) {
            try {
                $result = $this->tkgmService->parselSorgula(
                    $query['ada'] ?? '',
                    $query['parsel'] ?? '',
                    $query['il'] ?? '',
                    $query['ilce'] ?? '',
                    $query['mahalle'] ?? null
                );

                $results[] = [
                    'index' => $index,
                    'query' => $query,
                    'result' => $result,
                    'success' => $result['success'] ?? false,
                ];

                if ($result['success'] ?? false) {
                    $successCount++;
                } else {
                    $failureCount++;
                }

                // Rate limiting for live API calls (0.5s)
                if ($queryCount > 1 && $index < ($queryCount - 1)) {
                    usleep(500000); 
                }

            } catch (\Exception $e) {
                Log::error('BulkQuery Item Error', [
                    'query' => $query,
                    'error' => $e->getMessage()
                ]);

                $results[] = [
                    'index' => $index,
                    'query' => $query,
                    'result' => ['success' => false, 'message' => $e->getMessage()],
                    'success' => false,
                ];
                $failureCount++;
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'summary' => [
                'total' => $queryCount,
                'success' => $successCount,
                'failure' => $failureCount,
            ]
        ];
    }
}

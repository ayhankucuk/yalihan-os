<?php

namespace App\Services;

use App\Models\AiLog;

class SystemMonitorService
{
    protected $repository;

    public function __construct(\App\Repositories\SystemMonitorRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAISummary(): array
    {
        $since = now()->subHours(24);
        $logs = $this->repository->getLogsSince($since);

        $total = $logs->count();
        $successLogs = $logs->whereBetween('aktiflik_kodu', [200, 299]);
        $success = $successLogs->count();
        $failed = $logs->where('aktiflik_kodu', '>=', 400)->count();

        $avgResponse = $total > 0 ? $successLogs->avg('duration_ms') ?? 0 : 0;
        $avgTokens = $total > 0 ? $successLogs->avg('total_tokens') ?? 0 : 0;

        $successRate = $total > 0 ? round(($success / $total) * 100, 2) : 0;
        $errorRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;

        $totalCost = 0;
        foreach ($successLogs as $log) {
            $totalCost += $this->calculateLogCost($log);
        }

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'success_rate' => $successRate,
            'error_rate' => $errorRate,
            'avg_response_ms' => round($avgResponse, 2),
            'avg_tokens' => round($avgTokens, 0),
            'total_cost' => round($totalCost, 4),
        ];
    }

    public function getAIModels(): array
    {
        $since = now()->subDays(7);
        $logs = $this->repository->getLogsSince($since);

        $grouped = $logs->groupBy('model');
        $models = [];

        foreach ($grouped as $model => $modelLogs) {
            $modelName = $model ?: 'unknown';
            $total = $modelLogs->count();
            $successLogs = $modelLogs->whereBetween('aktiflik_kodu', [200, 299]);
            $success = $successLogs->count();
            $failed = $modelLogs->where('aktiflik_kodu', '>=', 400)->count();

            $avgResponse = $total > 0 ? $successLogs->avg('duration_ms') ?? 0 : 0;

            $modelCost = 0;
            foreach ($successLogs as $log) {
                $modelCost += $this->calculateLogCost($log);
            }

            $models[] = [
                'model' => $modelName,
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
                'error_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
                'avg_response_ms' => round($avgResponse, 2),
                'total_cost' => round($modelCost, 4),
            ];
        }

        return array_values($models);
    }

    public function getROISummary(): array
    {
        return [
            'total_opportunities' => 0,
            'avg_match_score' => 0,
            'high_roi_count' => 0,
            'last_hunt' => 'Model eksik (Opportunity)',
        ];
    }

    public function getRecentErrors()
    {
        return $this->repository->getRecentErrors(10)
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->request_type ?? 'unknown',
                    'provider' => $log->provider ?? 'unknown',
                    'error' => $log->error_message ?? ($log->response_payload['error'] ?? 'Unknown error'),
                    'timestamp' => $log->olusturma_tarihi->toIso8601String(),
                ];
            });
    }

    public function getOverallStatus(array $aiSummary): array
    {
        $successRate = $aiSummary['success_rate'] ?? 0;
        $errorRate = $aiSummary['error_rate'] ?? 0;
        if ($successRate >= 90 && $errorRate < 10) {
            $level = 'green';
            $message = 'Sistem sağlıklı';
        } elseif ($successRate >= 70 && $errorRate < 20) {
            $level = 'yellow';
            $message = 'Bazı uyarılar var';
        } else {
            $level = 'red';
            $message = 'Kritik sorunlar tespit edildi';
        }
        return [
            'level' => $level,
            'message' => $message,
            'success_rate' => $successRate,
            'error_rate' => $errorRate,
        ];
    }

    public function calculateLogCost($log): float
    {
        $pricing = config('ai.pricing', []);
        $model = $log->model;
        if (!isset($pricing[$model])) {
            return 0;
        }
        $inputTokens = $log->total_tokens * 0.7;
        $outputTokens = $log->total_tokens * 0.3;
        $inputCost = ($inputTokens / 1000) * $pricing[$model]['input'];
        $outputCost = ($outputTokens / 1000) * $pricing[$model]['output'];
        return $inputCost + $outputCost;
    }
}

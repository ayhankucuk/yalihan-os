<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AI\AiTransaction;
use App\Models\AiLog;
use App\Models\OutboxEntry;
use App\Models\Ilan;
use App\Models\User;
use App\Models\Kisi;
use App\Services\Resilience\CircuitBreaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ObservabilityController extends Controller
{
    public function __construct(
        protected CircuitBreaker $circuitBreaker
    ) {}

    /**
     * Get platform observability metrics.
     */
    public function stats(): JsonResponse
    {
        // 1. Outbox Stats
        $outboxStats = [
            'pending' => OutboxEntry::where('yayin_durumu', 'PENDING')->count(),
            'processing' => OutboxEntry::where('yayin_durumu', 'PROCESSING')->count(),
            'failed' => OutboxEntry::where('yayin_durumu', 'FAILED')->count(),
            'dead_letter' => OutboxEntry::where('yayin_durumu', 'DEAD_LETTER')->count(),
        ];

        // 2. Circuit Breaker States
        $providers = ['ollama', 'openai', 'deepseek', 'gemini', 'tkgm'];
        $circuitBreakerStates = [];
        foreach ($providers as $provider) {
            $circuitBreakerStates[$provider] = [
                'state' => $this->circuitBreaker->getState($provider),
                'failures' => (int) Cache::get("circuit_breaker:failures:{$provider}", 0),
            ];
        }

        // 3. AI Cost & Tokens telemetry (Last 30 days)
        $aiTelemetry = [
            'total_cost_usd' => (float) AiLog::sum('maliyet_usd'),
            'total_tokens' => (int) AiLog::sum('total_tokens'),
            'total_requests' => (int) AiLog::count(),
        ];

        // 4. Database Volume Row Counts
        $dbStats = [
            'users' => User::count(),
            'ilanlar' => Ilan::count(),
            'kisiler' => Kisi::count(),
            'ai_logs' => AiLog::count(),
            'outbox_entries' => OutboxEntry::count(),
        ];

        // 5. Cache Probe
        $cacheWorking = false;
        try {
            Cache::put('observability_ping', 'pong', 5);
            $cacheWorking = Cache::get('observability_ping') === 'pong';
        } catch (\Throwable $e) {
            // Ignore and leave false
        }

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'outbox' => $outboxStats,
                'circuit_breakers' => $circuitBreakerStates,
                'ai_telemetry' => $aiTelemetry,
                'database' => $dbStats,
                'cache' => [
                    'status' => $cacheWorking ? 'healthy' : 'degraded',
                ]
            ]
        ]);
    }
}

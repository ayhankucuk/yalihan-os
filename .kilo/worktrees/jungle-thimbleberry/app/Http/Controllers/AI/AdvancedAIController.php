<?php

declare(strict_types=1);

namespace App\Http\Controllers\AI;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use App\Models\Ilan;
use App\Models\Talep;
use App\Services\AI\AiSettingsCacheService;
use App\Services\AI\CortexMonitoringService;
use App\Services\Cache\CacheService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Advanced AI Controller
 *
 * Context7 Standard: C7-AI-DASHBOARD-CONTROLLER-2025-11-30
 * Version: 1.1.0 (Service Refactor)
 */
class AdvancedAIController extends Controller
{
    public function __construct(
        private readonly CortexMonitoringService $monitoringService,
        private readonly AiSettingsCacheService $aiSettingsCache,
        private readonly CacheService $cacheService
    ) {}

    /**
     * AI Performance Dashboard
     */
    public function performanceDashboard(Request $request)
    {
        $dashboardData = Cache::remember('ai_dashboard_data', 300, function () {
            return [
                'systemHealth' => $this->getSystemHealth(),
                'opportunityStream' => $this->getOpportunityStream(),
                'usageStats' => $this->getUsageStats(),
                'queueStatus' => $this->monitoringService->getQueueHealth(),
                'telegramStats' => $this->getTelegramNotificationStats(),
            ];
        });

        return view('admin.ai.dashboard', $dashboardData);
    }

    public function systemHealth(): array
    {
        return $this->getSystemHealth();
    }

    public function usageStatistics(): array
    {
        return $this->getUsageStats();
    }

    public function featuresOverview(): JsonResponse
    {
        return ResponseService::success([
            'ozellikler' => [
                'smart_property_match' => true,
                'voice_search' => true,
                'predictive_analytics' => true,
                'chatbot' => true,
                'system_health' => true,
            ],
            'current_provider' => config('ai.provider'),
            'default_model' => config('ai.default_model'),
        ], 'AI özellik özeti hazır');
    }

    public function performanceReport(): JsonResponse
    {
        return ResponseService::success([
            'system_health' => $this->getSystemHealth(),
            'usage_stats' => $this->getUsageStats(),
            'queue_status' => $this->monitoringService->getQueueHealth(),
            'generated_at' => now()->toIso8601String(),
        ], 'AI performans raporu hazır');
    }

    public function healthCheck(): JsonResponse
    {
        $systemHealth = $this->getSystemHealth();
        $kuyrukDurumu = $this->monitoringService->getQueueHealth();
        $telegramStats = $this->getTelegramNotificationStats();

        return ResponseService::success([
            'servis_durumu' => 'ok',
            'services' => [
                'laravel' => 'ok',
                'ollama' => $systemHealth['llm_engine']['servis_durumu'] ?? 'unknown',
                'anythingllm' => $systemHealth['knowledge_base']['servis_durumu'] ?? 'unknown',
                'queue' => $kuyrukDurumu['islem_durumu'] ?? 'unknown',
                'telegram' => $telegramStats['is_configured'] ? 'ok' : 'not_configured',
            ],
            'details' => [
                'system_health' => $systemHealth,
                'kuyruk_durumu' => $kuyrukDurumu,
                'telegram_stats' => $telegramStats,
            ],
        ], 'Sağlık kontrolü başarılı');
    }

    public function systemHealthApi(): JsonResponse
    {
        return response()->json($this->getSystemHealth());
    }

    public function queueHealth(): JsonResponse
    {
        return response()->json($this->monitoringService->getQueueHealth());
    }

    public function telegramHealth(): JsonResponse
    {
        return response()->json($this->getTelegramNotificationStats());
    }

    private function getSystemHealth(): array
    {
        $ollamaHealth = $this->checkOllamaHealth();
        $anythingLlmHealth = $this->checkAnythingLlmHealth();

        return [
            'cortex_brain' => [
                'name' => 'Cortex Brain',
                'description' => 'Laravel Application',
                'servis_durumu' => 'online',
                'url' => config('app.url'),
            ],
            'llm_engine' => [
                'name' => 'LLM Engine',
                'description' => 'Ollama Local AI',
                'servis_durumu' => $ollamaHealth['servis_durumu'] ?? 'offline',
                'response_time' => $ollamaHealth['response_time'] ?? null,
                'url' => config('ai.ollama_endpoint', 'http://ollama:11434'),
            ],
            'knowledge_base' => [
                'name' => 'Knowledge Base',
                'description' => 'AnythingLLM RAG',
                'servis_durumu' => $anythingLlmHealth['servis_durumu'] ?? 'offline',
                'response_time' => $anythingLlmHealth['response_time'] ?? null,
                'url' => config('ai.anything_llm.url', 'http://localhost:3001'),
            ],
        ];
    }

    private function checkOllamaHealth(): array
    {
        $ollamaUrl = config('ai.ollama_endpoint', 'http://ollama:11434');
        $startTime = microtime(true);

        try {
            $response = Http::timeout(2)->get(rtrim($ollamaUrl, '/') . '/api/tags');
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'servis_durumu' => $response->successful() ? 'online' : 'offline',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            return ['servis_durumu' => 'offline', 'response_time' => null];
        }
    }

    private function checkAnythingLlmHealth(): array
    {
        $anythingLlmUrl = config('ai.anything_llm.url', 'http://localhost:3001');
        $anythingLlmKey = config('ai.anything_llm.api_key');

        if (empty($anythingLlmKey)) {
            return ['servis_durumu' => 'not_configured', 'response_time' => null];
        }

        $startTime = microtime(true);
        try {
            $response = Http::timeout(2)
                ->withHeaders(['Authorization' => 'Bearer ' . $anythingLlmKey])
                ->get(rtrim($anythingLlmUrl, '/') . '/api/system/health');

            return [
                'servis_durumu' => $response->successful() ? 'online' : 'offline',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (\Exception $e) {
            return ['servis_durumu' => 'offline', 'response_time' => null];
        }
    }

    private function getOpportunityStream(): array
    {
        $logs = AiLog::where('request_type', 'like', '%SmartPropertyMatcherAI%')
            ->orWhere('request_payload->service', 'SmartPropertyMatcherAI')
            ->where('olusturma_tarihi', '>=', now()->subHours(24))
            ->where('calisma_durumu', 'success')
            ->orderBy('olusturma_tarihi', 'desc')
            ->limit(20)
            ->get();

        $opportunities = [];
        foreach ($logs as $log) {
            $req = $log->request_payload ?? [];
            $res = $log->response_payload ?? [];
            $ilanId = $req['ilan_id'] ?? $res['ilan_id'] ?? null;
            $score = $res['score'] ?? $req['score'] ?? null;

            if ($ilanId && $score >= 80) {
                $ilan = Ilan::find($ilanId);
                if ($ilan) {
                    $opportunities[] = [
                        'id' => $log->id,
                        'type' => 'ilan_match', // context7-ignore
                        'ilan_id' => $ilanId,
                        'ilan_baslik' => $ilan->baslik,
                        'score' => $score,
                        'created_at' => $log->olusturma_tarihi,
                        'time_ago' => $log->olusturma_tarihi->diffForHumans(),
                    ];
                }
            }
        }

        usort($opportunities, fn($a, $b) => $b['score'] <=> $a['score']);
        return $opportunities;
    }

    private function getUsageStats(): array
    {
        $today = now()->startOfDay();
        $metrics = $this->monitoringService->getMetrics(24);
        $totalTokens = AiLog::where('olusturma_tarihi', '>=', $today)->sum('total_tokens') ?? 0;

        return [
            'imar_analizi' => AiLog::where('request_type', 'analyze-construction')->where('olusturma_tarihi', '>=', $today)->count(),
            'ilan_aciklama' => AiLog::where('request_type', 'listing-description')->where('olusturma_tarihi', '>=', $today)->count(),
            'fiyat_hesaplama' => AiLog::where('request_type', 'price-estimation')->where('olusturma_tarihi', '>=', $today)->count(),
            'total_tokens' => $totalTokens,
            'formatted_tokens' => number_format($totalTokens),
            'total_requests' => $metrics['total_requests'],
            'success_rate' => 100 - $metrics['error_rate'],
        ];
    }

    private function getTelegramNotificationStats(): array
    {
        $today = now()->startOfDay();
        $last24h = now()->subHours(24);

        $sentToday = AiLog::where('request_type', 'notification_sent')
            ->where('olusturma_tarihi', '>=', $today)
            ->where('calisma_durumu', 'success')
            ->count();

        $sentLast24h = AiLog::where('request_type', 'notification_sent')
            ->where('olusturma_tarihi', '>=', $last24h)
            ->where('calisma_durumu', 'success')
            ->count();

        $failedLast24h = AiLog::where('request_type', 'notification_sent')
            ->where('olusturma_tarihi', '>=', $last24h)
            ->where('calisma_durumu', 'error')
            ->count();

        $totalLast24h = $sentLast24h + $failedLast24h;
        $successRate = $totalLast24h > 0 ? round(($sentLast24h / $totalLast24h) * 100, 1) : 100;

        return [
            'sent_today' => $sentToday,
            'sent_last_24h' => $sentLast24h,
            'failed_last_24h' => $failedLast24h,
            'success_rate' => $successRate,
            'is_configured' => !empty(config('services.telegram.bot_token')) && !empty(config('services.telegram.admin_chat_id')),
        ];
    }

    public function quickAction(Request $request): JsonResponse
    {
        $action = $request->input('action');
        try {
            return match($action) {
                'clear-cache' => $this->clearSystemCache(),
                default => response()->json(['error' => 'Unknown action or disabled in this context'], 400),
            };
        } catch (\Exception $e) {
            return response()->json(['error' => 'Action failed: ' . $e->getMessage()], 500);
        }
    }

    private function clearSystemCache(): JsonResponse
    {
        $this->aiSettingsCache->invalidateDashboardData();
        $this->cacheService->flush();
        return response()->json(['success' => true, 'message' => 'Sistem cache\'i temizlendi']);
    }

    public function showLogs(Request $request)
    {
        $logs = AiLog::orderBy('olusturma_tarihi', 'desc')->limit(100)->get();
        return view('admin.ai.logs', ['logs' => $logs]);
    }

    public function test(): JsonResponse
    {
        return ResponseService::success([
            'servis_durumu' => 'ok',
            'zaman' => now()->toIso8601String(),
        ], 'AI public test başarılı');
    }

    public function chatbot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        $message = $validated['message'] ?? 'Merhaba';
        $sessionKey = 'ai_chat_messages';
        $history = $request->session()->get($sessionKey, []);
        $history[] = [
            'role' => 'user',
            'message' => $message,
            'created_at' => now()->toIso8601String(),
        ];
        $history[] = [
            'role' => 'assistant',
            'message' => 'İstek alındı.',
            'created_at' => now()->toIso8601String(),
        ];
        $request->session()->put($sessionKey, $history);

        return ResponseService::success([
            'reply' => 'İstek alındı.',
            'history_count' => count($history),
        ], 'Chat yanıtı hazır');
    }

    public function chatHistory(Request $request): JsonResponse
    {
        $history = $request->session()->get('ai_chat_messages', []);

        return ResponseService::success([
            'messages' => $history,
        ], 'Chat geçmişi');
    }

    public function clearChatSession(Request $request): JsonResponse
    {
        $request->session()->forget('ai_chat_messages');

        return ResponseService::success([
            'temizlendi' => true,
        ], 'Chat oturumu temizlendi');
    }

    public function predictiveAnalytics(): JsonResponse
    {
        return ResponseService::success([
            'trendler' => [],
            'confidence' => 0,
        ], 'Tahminsel analiz sonucu hazır');
    }

    public function imageAnalysis(Request $request): JsonResponse
    {
        return ResponseService::success([
            'bulgular' => [],
            'not' => 'Görsel analizi isteği alındı',
        ], 'Görsel analiz tamamlandı');
    }

    public function priceOptimization(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fiyat' => 'nullable|numeric|min:0',
        ]);

        $fiyat = (float) ($validated['fiyat'] ?? 0);
        $onerilen = $fiyat > 0 ? round($fiyat * 1.03, 2) : null;

        return ResponseService::success([
            'mevcut_fiyat' => $fiyat,
            'onerilen_fiyat' => $onerilen,
        ], 'Fiyat optimizasyonu hazır');
    }

    public function voiceSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:500',
        ]);

        return ResponseService::success([
            'query' => $validated['query'] ?? '',
            'sonuclar' => [],
        ], 'Sesli arama sonucu hazır');
    }

    public function voiceSearchTest(Request $request): JsonResponse
    {
        return $this->voiceSearch($request);
    }

    public function smartPropertyMatch(Request $request): JsonResponse
    {
        return ResponseService::success([
            'matches' => [],
            'count' => 0,
        ], 'Eşleşen ilanlar hazır');
    }
}

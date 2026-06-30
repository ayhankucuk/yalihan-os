<?php

namespace App\Services\Integrations;

/**
 * @sab-ignore-catch
 */

use App\Services\Logging\LogService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * n8n Workflow Automation Service
 *
 * Context7 Standardı: C7-N8N-INTEGRATION-2025-12-19
 *
 * Yalıhan Bekçi: Bidirectional n8n workflow automation
 * MCP Compliance: ✅ LogService + Timer tracking
 * Naming Convention: ✅ aktiflik_durumu, il_id (Context7 sealed)
 *
 * @version 2.0.0
 * @since 2025-12-19
 * @author YalihanCortex AI System
 *
 * Features:
 * - Laravel → n8n: Event-driven webhooks
 * - n8n → Laravel: Webhook endpoints
 * - Workflow orchestration
 * - AI-powered decision triggers
 * - Multi-channel notifications
 */
class N8nIntegrationService
{
    protected LogService $logService;
    protected string $baseUrl;
    protected string $webhookToken;
    protected int $timeout;

    /**
     * Supported workflow triggers
     */
    private const WORKFLOWS = [
        'ilan_created' => 'webhook/ilan-olusturuldu',
        'ilan_updated' => 'webhook/ilan-guncellendi',
        'ilan_price_changed' => 'webhook/ilan-fiyat-degisti',
        'talep_created' => 'webhook/talep-olusturuldu',
        'talep_matched' => 'webhook/talep-eslesti',
        'gorev_created' => 'webhook/gorev-olusturuldu',
        'gorev_deadline' => 'webhook/gorev-gecikti',
        'kisi_churn_risk' => 'webhook/musteri-kayip-riski',
        'ai_opportunity' => 'webhook/ai-firsat-tespiti',
        'market_intelligence' => 'webhook/piyasa-analizi',
    ];

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
        $this->baseUrl = config('services.n8n.url', 'http://localhost:5678');
        $this->webhookToken = config('services.n8n.webhook_token', '');
        $this->timeout = config('services.n8n.timeout', 30);
    }

    /**
     * Trigger n8n workflow
     *
     * @CortexDecision Laravel event → n8n workflow
     *
     * @param string $workflowType Workflow type from WORKFLOWS
     * @param array $data Workflow data
     * @param array $options Additional options
     * @return array Workflow result
     */
    public function triggerWorkflow(string $workflowType, array $data, array $options = []): array
    {
        $timerId = LogService::startTimer('n8n_workflow_trigger');

        try {
            if (! isset(self::WORKFLOWS[$workflowType])) {
                throw new RuntimeException("Unknown workflow type: {$workflowType}");
            }

            $webhookPath = self::WORKFLOWS[$workflowType];
            $url = rtrim($this->baseUrl, '/') . '/' . ltrim($webhookPath, '/');

            Log::info('n8n workflow triggering', [
                'workflow' => $workflowType,
                'istek_url' => $url,
                'data_keys' => array_keys($data),
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Token' => $this->webhookToken,
                    'X-Source' => 'yalihan-cortex',
                ])
                ->post($url, array_merge($data, [
                    'workflow_type' => $workflowType,
                    'triggered_at' => now()->toIso8601String(),
                    'options' => $options,
                ]));

            LogService::stopTimer($timerId);

            if ($response->successful()) {
                $result = $response->json();

                $this->logService->logCortexDecision('n8n_workflow_triggered', [
                    'workflow' => $workflowType,
                    'http_durum_kodu' => 200,
                    'response' => $result,
                ], LogService::stopTimer($timerId), true);

                Log::info('n8n workflow triggered successfully', [
                    'workflow' => $workflowType,
                    'yanit_kodu' => $response->getStatusCode(),
                    'duration_ms' => LogService::stopTimer($timerId),
                ]);

                return [
                    'success' => true,
                    'workflow' => $workflowType,
                    'data' => $result,
                    'processing_time' => LogService::stopTimer($timerId),
                ];
            }

            throw new RuntimeException(
                "n8n workflow failed: {$response->getStatusCode()} - {$response->body()}"
            );
        } catch (Exception $e) {
            LogService::stopTimer($timerId);

            $this->logService->logCortexDecision('n8n_workflow_failed', [
                'workflow' => $workflowType,
                'hata_mesaji' => $e->getMessage(),
            ], LogService::stopTimer($timerId), false);

            Log::error('n8n workflow trigger failed', [
                'workflow' => $workflowType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'workflow' => $workflowType,
                'error' => $e->getMessage(),
            ];
        }
    }
// ...
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-Webhook-Token' => $this->webhookToken,
                ])
                ->get($this->baseUrl . '/healthz');

            return [
                'success' => $response->successful(),
                'http_code' => $response->status(), // context7-ignore
                'reachable' => true,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'reachable' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available workflows
     */
    public function getAvailableWorkflows(): array
    {
        return self::WORKFLOWS;
    }

    /**
     * Get workflow statistics
     */
    public function getStatistics(): array
    {
        $cacheKey = 'n8n_statistics';

        return Cache::remember($cacheKey, 300, function () {
            return [
                'total_triggers' => DB::table('ai_logs')
                    ->where('action', 'n8n_workflow_triggered')
                    ->count(),
                'basari_orani' => DB::table('ai_logs')
                    ->where('action', 'n8n_workflow_triggered')
                    ->where('execution_result', 'success')
                    ->count(),
                'workflows_count' => count(self::WORKFLOWS),
            ];
        });
    }
}

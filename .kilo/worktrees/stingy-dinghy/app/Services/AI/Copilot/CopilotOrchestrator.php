<?php

namespace App\Services\AI\Copilot;

use App\Services\AI\Copilot\Pipeline\PipelineDispatcher;
use App\Services\AI\Copilot\Support\OutputContractValidator;
use App\Services\AI\Copilot\Support\OutputNormalizer;
use Illuminate\Support\Facades\Log;

class CopilotOrchestrator
{
    public const SCHEMA_FALLBACK = [
        'stage' => 'govern',
        'summary' => 'Schema violation — pipeline halted at GOVERN stage.',
        'findings' => [],
        'fixes' => [],
        'execution' => [],
        'verification' => [],
        'decision' => [
            'action' => 'block',
            'reason' => 'Schema compliance failed — pipeline reset to GOVERN.',
        ],
        'warnings' => ['schema_violation'],
        'meta' => [
            'pipeline_halted' => true,
            'fallback_triggered' => true,
        ],
    ];

    public function __construct(
        protected ContextCollector $contextCollector,
        protected CopilotRuleEngine $ruleEngine,
        protected CopilotPredictionEngine $predictionEngine,
        protected CopilotAuditEngine $auditEngine,
        protected ?WizardCopilotService $wizardService = null,
        protected ?CRMCopilotService $crmService = null,
        protected ?LocationCopilotService $locationService = null,
        protected ?OutputNormalizer $outputNormalizer = null,
        protected ?OutputContractValidator $outputValidator = null,
    ) {
        $this->outputNormalizer ??= new OutputNormalizer();
        $this->outputValidator ??= new OutputContractValidator();
    }

    public function analyze(string $routeName, ?int $entityId = null): array
    {
        $startTime = microtime(true);

        try {
            // 1. Collect context
            $context = $this->contextCollector->collect($routeName, $entityId);

            // 2. Evaluate rules (deterministic)
            $insights = $this->ruleEngine->evaluate($context);

            // 3. Run predictions (scoring)
            $predictions = $this->predictionEngine->predict($context);

            // 4. Run audit checks (data integrity)
            $auditFindings = $this->auditEngine->audit($context);

            // 5. Run specialized services
            $specializedData = $this->runSpecializedServices($context, $entityId);

            // 6. Generate next action
            $nextAction = $this->resolveNextAction($insights, $context);

            // 7. Generate screen summary
            $summary = $this->generateSummary($context, $insights, $predictions, $auditFindings);

            // 8. Calculate confidence
            $confidence = $this->calculateConfidence($context, $insights, $predictions, $auditFindings);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            return [
                'context' => [
                    'tip' => $context['tip'],
                    'route' => $routeName,
                    'entity_id' => $entityId,
                ],
                'summary' => $summary,
                'insights' => array_slice($insights, 0, 5), // Max 5 insights
                'predictions' => $predictions,
                'audit' => [
                    'findings' => array_slice($auditFindings, 0, 5),
                    'total_count' => count($auditFindings),
                    'critical_count' => count(array_filter($auditFindings, fn($f) => $f['severity'] === 'critical')),
                    'high_count' => count(array_filter($auditFindings, fn($f) => $f['severity'] === 'high')),
                ],
                'next_action' => $nextAction,
                'confidence' => $confidence,
                'specialized' => $specializedData,
                'meta' => [
                    'duration_ms' => $durationMs,
                    'insight_count' => count($insights),
                    'audit_count' => count($auditFindings),
                    'timestamp' => now()->toIso8601String(),
                    'version' => '2.0.0',
                    'modules' => $this->activeModules($context['tip']),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('CopilotOrchestrator analysis failed', [
                'route' => $routeName,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);

            return [
                'context' => [
                    'tip' => 'error',
                    'route' => $routeName,
                    'entity_id' => $entityId,
                ],
                'summary' => 'Copilot analizi şu an kullanılamıyor.',
                'insights' => [],
                'predictions' => [],
                'next_action' => null,
                'meta' => [
                    'duration_ms' => round((microtime(true) - $startTime) * 1000),
                    'error' => true,
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        }
    }

    /**
     * Run specialized copilot services based on context type.
     */
    protected function runSpecializedServices(array $context, ?int $entityId): array
    {
        $data = [];

        try {
            switch ($context['tip']) {
                case 'wizard':
                case 'ilan-create':
                    if ($this->wizardService) {
                        $data['wizard'] = $this->wizardService->analyze($context['data']);
                    }
                    break;

                case 'ilan-detail':
                case 'ilan-edit':
                    if ($entityId && $this->locationService) {
                        $data['location'] = $this->locationService->analyzeListing($entityId);
                    }
                    break;

                case 'crm-detail':
                case 'crm-edit':
                    if ($entityId && $this->crmService) {
                        $data['crm'] = $this->crmService->analyzeContact($entityId);
                    }
                    break;

                case 'crm-list':
                case 'crm-dashboard':
                    if ($this->crmService) {
                        $data['crm_aggregate'] = $this->crmService->aggregateListMetrics();
                    }
                    break;

                case 'dashboard':
                    if ($this->locationService) {
                        $data['location_stats'] = $this->locationService->aggregateLocationStats();
                    }
                    if ($this->crmService) {
                        $data['crm_aggregate'] = $this->crmService->aggregateListMetrics();
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::warning('CopilotOrchestrator specialized service failed', [
                'context_type' => $context['tip'],
                'error' => $e->getMessage(),
            ]);
        }

        return $data;
    }

    /**
     * Returns active module names for current context.
     */
    protected function activeModules(string $contextType): array
    {
        $modules = ['rules', 'predictions', 'audit'];

        if (in_array($contextType, ['wizard', 'ilan-create'])) {
            $modules[] = 'wizard';
        }
        if (in_array($contextType, ['ilan-detail', 'ilan-edit', 'dashboard'])) {
            $modules[] = 'location';
        }
        if (in_array($contextType, ['crm-detail', 'crm-edit', 'crm-list', 'crm-dashboard', 'dashboard'])) {
            $modules[] = 'crm';
        }

        return $modules;
    }

    protected function resolveNextAction(array $insights, array $context): ?array
    {
        // Find highest priority actionable insight
        foreach ($insights as $insight) {
            if (!empty($insight['action'])) {
                return [
                    'title' => $insight['title'],
                    'description' => $insight['description'],
                    'url' => $insight['action']['url'] ?? null,
                    'label' => $insight['action']['label'] ?? 'Git',
                    'priority' => $insight['priority'] ?? 99,
                ];
            }
        }

        // Context-specific default next actions
        return match ($context['tip']) {
            'dashboard' => [
                'title' => 'Portföyü keşfet',
                'description' => 'İlanlarınızı gözden geçirin ve eksikleri tamamlayın.',
                'url' => '/admin/ilanlar',
                'label' => 'İlanlar',
            ],
            'ilan-create' => [
                'title' => 'İlan oluşturmaya başlayın',
                'description' => 'Kategori seçerek başlayın, AI geri kalanında yardımcı olacak.',
                'label' => 'Başla',
            ],
            'wizard' => [
                'title' => 'Wizard ile ilan oluştur',
                'description' => 'Kategori ve yayın tipi seçerek alanları otomatik yükleyin.',
                'label' => 'Devam',
            ],
            'crm-list', 'crm-dashboard' => [
                'title' => 'Kişi ekleyin',
                'description' => 'Yeni müşteri kaydı oluşturun.',
                'url' => '/admin/kisiler/create',
                'label' => 'Kişi Ekle',
            ],
            'eslesme-list' => [
                'title' => 'Eşleşmeleri gözden geçir',
                'description' => 'Bekleyen eşleşmeleri onaylayın veya reddedin.',
                'label' => 'Gözden Geçir',
            ],
            'eslesme-detail' => [
                'title' => 'Eşleşme sonucu girin',
                'description' => 'İletişim sonucunu kaydedin.',
                'label' => 'Sonuç Gir',
            ],
            'danisman-list', 'danisman-detail' => [
                'title' => 'Danışman performansı',
                'description' => 'Portföy ve müşteri istatistiklerini görüntüleyin.',
                'label' => 'Detay',
            ],
            default => null,
        };
    }

    protected function generateSummary(array $context, array $insights, array $predictions, array $auditFindings = []): string
    {
        $type = $context['tip'];
        $criticalCount = count(array_filter($insights, fn($i) => $i['tip'] === 'critical'));
        $warningCount = count(array_filter($insights, fn($i) => $i['tip'] === 'warning'));
        $auditCritical = count(array_filter($auditFindings, fn($f) => $f['severity'] === 'critical'));
        $healthScore = $predictions['health_score'] ?? null;

        if ($auditCritical > 0) {
            return $auditCritical . ' kritik denetim bulgusu var. ' . ($criticalCount > 0 ? $criticalCount . ' kritik sorun da mevcut.' : 'Acil müdahale önerilir.');
        }

        if ($criticalCount > 0) {
            return $criticalCount . ' kritik sorun tespit edildi. Hemen müdahale önerilir.';
        }

        if ($warningCount > 0) {
            $suffix = $healthScore !== null ? " (Sağlık: %{$healthScore})" : '';
            return $warningCount . ' uyarı mevcut.' . $suffix;
        }

        if ($healthScore !== null && $healthScore >= 80) {
            return 'Her şey yolunda görünüyor. Sağlık skoru: %' . $healthScore;
        }

        if (count($insights) === 0 && count($auditFindings) === 0) {
            return match ($type) {
                'dashboard' => 'Portföyünüz güncel.',
                'ilan-detail', 'ilan-edit' => 'Bu ilan iyi durumda.',
                'crm-detail' => 'Müşteri kaydı tamamlanmış.',
                'wizard' => 'Wizard hazır, devam edebilirsiniz.',
                'eslesme-list', 'eslesme-detail' => 'Eşleşme durumu normal.',
                'danisman-list', 'danisman-detail' => 'Danışman performansı stabil.',
                default => 'Bu ekran için önerim yok.',
            };
        }

        $total = count($insights) + count($auditFindings);
        return $total . ' öneri mevcut.';
    }

    protected function calculateConfidence(array $context, array $insights, array $predictions, array $auditFindings): array
    {
        $dataCompleteness = $this->assessDataCompleteness($context);
        $predictionQuality = !empty($predictions['health_score']) ? 'high' : 'low';
        $auditCoverage = count($auditFindings) > 0 ? 'active' : 'passive';

        // Confidence score: 0-100
        $score = 50; // base
        if ($dataCompleteness >= 0.8) $score += 25;
        elseif ($dataCompleteness >= 0.5) $score += 15;
        if ($predictionQuality === 'high') $score += 15;
        if ($auditCoverage === 'active') $score += 10;

        return [
            'score' => min(100, $score),
            'label' => $score >= 75 ? 'Yüksek' : ($score >= 50 ? 'Orta' : 'Düşük'),
            'data_completeness' => round($dataCompleteness * 100),
            'sources' => array_filter([
                'rules' => count($insights) > 0,
                'predictions' => !empty($predictions['health_score']),
                'audit' => count($auditFindings) > 0,
            ]),
        ];
    }

    protected function assessDataCompleteness(array $context): float
    {
        $data = $context['data'] ?? [];
        if (empty($data)) return 0.0;

        $type = $context['tip'];

        return match ($type) {
            'ilan-detail', 'ilan-edit' => $this->ilanCompleteness($data),
            'crm-detail', 'crm-edit' => $this->crmCompleteness($data),
            'wizard' => $this->wizardCompleteness($data),
            default => min(1.0, count($data) / 5), // generic heuristic
        };
    }

    protected function ilanCompleteness(array $data): float
    {
        $fields = ['has_price', 'has_description', 'has_location', 'has_coordinates', 'has_category'];
        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($data[$field])) $filled++;
        }
        if (($data['photo_count'] ?? 0) > 0) $filled++;
        return $filled / (count($fields) + 1);
    }

    protected function crmCompleteness(array $data): float
    {
        $filled = 0;
        $total = 3;
        if (!empty($data['has_phone'])) $filled++;
        if (!empty($data['has_email'])) $filled++;
        if (($data['talep_count'] ?? 0) > 0) $filled++;
        return $filled / $total;
    }

    protected function wizardCompleteness(array $data): float
    {
        $filled = 0;
        $total = 4;
        if (!empty($data['ana_kategori_id'])) $filled++;
        if (!empty($data['yayin_tipi_id'])) $filled++;
        if (!empty($data['baslik'])) $filled++;
        if (!empty($data['fiyat'])) $filled++;
        return $filled / $total;
    }

    /**
     * Normalize + validate a raw JSON output string against the output contract.
     * Flow: JSON decode → Normalize → Validate → Return.
     * Returns validated array or GOVERN fallback on failure.
     */
    public function validateExternalOutput(string $rawJson): array
    {
        try {
            $decoded = json_decode($rawJson, true);

            if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                throw \App\Exceptions\Copilot\OutputContractViolationException::invalidJson($rawJson);
            }

            $normalized = $this->outputNormalizer->normalize($decoded);

            $this->outputValidator->validate($normalized);

            return $normalized;
        } catch (\App\Exceptions\Copilot\OutputContractViolationException $e) {
            Log::warning('CopilotOrchestrator output contract violation — pipeline halted at GOVERN', [
                'error' => $e->getMessage(),
            ]);

            return self::SCHEMA_FALLBACK;
        }
    }

    /**
     * Start an async pipeline run.
     * Returns run_uuid immediately — all work happens via queued jobs.
     *
     * @param string $pipelineType 'audit' | 'fix' | 'full'
     * @param array  $inputPayload Context/route/entity info
     * @param string|null $module  'wizard' | 'crm' | 'property_hub' | null
     * @param int|null $triggeredBy User ID who triggered the pipeline
     * @return string The run_uuid for tracking
     */
    public function start(
        string $pipelineType,
        array $inputPayload,
        ?string $module = null,
        ?int $triggeredBy = null,
    ): string {
        $dispatcher = app(PipelineDispatcher::class);

        return $dispatcher->dispatch($pipelineType, $inputPayload, $module, $triggeredBy);
    }

    /**
     * Find a pipeline run by UUID for status polling.
     */
    public function findRun(string $runUuid): ?\App\Models\PipelineRun
    {
        return \App\Models\PipelineRun::where('run_uuid', $runUuid)
            ->with('steps')
            ->first();
    }
}

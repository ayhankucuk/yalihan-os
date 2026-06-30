<?php

namespace App\Services\AI\Domains;

use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\Logging\LogService;
use Exception;

/**
 * 🧠 Cortex Team Domain Service
 *
 * Responsibility: AI-driven team performance analysis.
 * YalihanCortex'ten domain extract — SAB v6.1.2.
 */
class CortexTeamService
{
    protected AiTelemetryService $telemetry;
    protected \App\Services\AI\AIOrchestrator $aiService;

    public function __construct(
        AiTelemetryService $telemetry,
        \App\Services\AI\AIOrchestrator $aiService
    ) {
        $this->telemetry  = $telemetry;
        $this->aiService  = $aiService;
    }

    /**
     * Takım Performans Analizi
     *
     * Gorev verilerini çekerek AI ile performans değerlendirmesi yapar.
     * YalihanCortex'ten taşındı — SAB v6.1.2 domain extract.
     *
     * @param int|null $teamId Takım ID (null = tüm takımlar)
     * @param array $options ['days' => int]
     * @return array Performans analizi
     */
    public function analyzeTeamPerformance(?int $teamId = null, array $options = []): array
    {
        $startTime = LogService::startTimer('cortex_team_performance');

        try {
            $query = \App\Modules\TakimYonetimi\Models\Gorev::query();

            if ($teamId) {
                $query->whereHas('takimUyesi', fn ($q) => $q->where('takim_id', $teamId));
            }

            $gorevler = $query
                ->with(['takimUyesi', 'proje'])
                ->where('created_at', '>=', now()->subDays($options['days'] ?? 30))
                ->get();

            $stats = [
                'total'       => $gorevler->count(),
                'completed'   => $gorevler->where('gorev_durumu', 'tamamlandi')->count(),
                'in_progress' => $gorevler->where('gorev_durumu', 'devam_ediyor')->count(),
                'overdue'     => $gorevler
                    ->where('bitis_tarihi', '<', now())
                    ->where('gorev_durumu', '!=', 'tamamlandi')
                    ->count(),
            ];

            $completionRate = $stats['total'] > 0
                ? round(($stats['completed'] / $stats['total']) * 100, 1)
                : 0;

            // AI performans analizi
            $prompt   = $this->buildTeamPerformancePrompt($stats, $completionRate, $options['days'] ?? 30);
            $aiRaw    = $this->aiService->generate($prompt, [
                'type'       => 'team_performance', // context7-ignore
                'max_tokens' => 800,
            ]);
            $aiParsed = is_array($aiRaw) ? $aiRaw : (json_decode((string) $aiRaw, true) ?? []);

            $durationMs = LogService::stopTimer($startTime);

            $result = [
                'success'         => true,
                'statistics'      => array_merge($stats, ['completion_rate' => $completionRate]),
                'insights'        => $aiParsed['insights'] ?? [],
                'recommendations' => $aiParsed['recommendations'] ?? [],
                'top_performers'  => $this->getTopPerformers($gorevler),
                'needs_attention' => $this->getNeedsAttention($gorevler),
                'metadata'        => [
                    'processed_at' => now()->toISOString(),
                    'team_id'      => $teamId,
                    'days_analyzed' => $options['days'] ?? 30,
                    'duration_ms'  => $durationMs,
                ],
            ];

            $this->logCortexDecision('team_performance', ['team_id' => $teamId], $durationMs, true);

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logCortexDecision('team_performance', ['team_id' => $teamId, 'error' => $e->getMessage()], $durationMs, false);

            LogService::error('CortexTeam: analyzeTeamPerformance başarısız', [
                'team_id' => $teamId,
                'error'   => $e->getMessage(),
            ], $e, LogService::CHANNEL_AI);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Takım performans prompt'u
     */
    protected function buildTeamPerformancePrompt(array $stats, float $completionRate, int $days): string
    {
        return "Aşağıdaki takım performans verilerini analiz et ve JSON formatında değerlendir.\n\n" .
            "Dönem: Son {$days} gün\n" .
            "Toplam görev: {$stats['total']}\n" .
            "Tamamlanan: {$stats['completed']} ({$completionRate}%)\n" .
            "Devam eden: {$stats['in_progress']}\n" .
            "Geciken: {$stats['overdue']}\n\n" .
            "JSON çıktısı: {\n" .
            "  \"insights\": [\"<içgörü1>\", \"<içgörü2>\"],\n" .
            "  \"recommendations\": [\"<öneri1>\", \"<öneri2>\"]\n" .
            "}";
    }

    /**
     * En yüksek performanslı kullanıcılar
     */
    protected function getTopPerformers($gorevler): array
    {
        return $gorevler
            ->where('gorev_durumu', 'tamamlandi')
            ->groupBy(fn ($g) => $g->takimUyesi?->user_id)
            ->map(fn ($group) => [
                'user_id'   => $group->first()->takimUyesi?->user_id,
                'completed' => $group->count(),
            ])
            ->sortByDesc('completed')
            ->take(5)
            ->values()
            ->toArray();
    }

    /**
     * Dikkat gerektiren kullanıcılar (çok sayıda gecikme)
     */
    protected function getNeedsAttention($gorevler): array
    {
        return $gorevler
            ->where('bitis_tarihi', '<', now())
            ->where('gorev_durumu', '!=', 'tamamlandi')
            ->groupBy(fn ($g) => $g->takimUyesi?->user_id)
            ->map(fn ($group) => [
                'user_id' => $group->first()->takimUyesi?->user_id,
                'overdue' => $group->count(),
            ])
            ->sortByDesc('overdue')
            ->take(5)
            ->values()
            ->toArray();
    }

    /**
     * Telemetry logger
     */
    private function logCortexDecision(string $type, array $context, float $durationMs, bool $success): void
    {
        $this->telemetry->logTransaction(
            'CortexTeam',
            $type,
            $durationMs / 1000,
            0, 0,
            $success ? 200 : 500,
            ['request' => $context]
        );
    }
}

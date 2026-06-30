<?php

namespace App\Jobs;

use App\Events\TalepReceived;
use App\Models\Talep;
use App\Models\User;
use App\Notifications\NewSalesOpportunity;
use App\Services\AI\YalihanCortex;
use App\Services\Logging\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * AnalyzeAndPrioritizeDemand Job
 *
 * Context7: Otonom Fırsat Sentezi ve Bildirim Sistemi
 * Yeni talep oluşturulduğunda Yalihan Cortex ile analiz yapar
 * Action Score > 110 olan fırsatları tespit eder ve danışmana bildirim gönderir
 */
class AnalyzeAndPrioritizeDemand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Talep nesnesi
     */
    public Talep $talep;

    /**
     * Create a new job instance.
     */
    public function __construct(Talep $talep)
    {
        $this->talep = $talep;
    }

    /**
     * Execute the job.
     */
    public function handle(YalihanCortex $cortex): void
    {
        $startTime = LogService::startTimer('analyze_and_prioritize_demand');

        try {
            LogService::ai(
                'demand_analysis_started',
                'AnalyzeAndPrioritizeDemand',
                [
                    'talep_id' => $this->talep->id,
                    'talep_baslik' => $this->talep->baslik,
                    'danisman_id' => $this->talep->danisman_id,
                ]
            );

            // 1. Yalihan Cortex ile eşleştirme analizi
            $cortexResult = $cortex->matchForSale($this->talep);

            // 2. Matches kontrolü
            $matches = $cortexResult['matches'] ?? [];

            if (empty($matches)) {
                LogService::ai(
                    'demand_analysis_no_matches',
                    'AnalyzeAndPrioritizeDemand',
                    [
                        'talep_id' => $this->talep->id,
                    ]
                );

                return;
            }

            // 3. En üstteki match'in Action Score'unu kontrol et
            $topMatch = $matches[0] ?? null;

            if (!$topMatch || !isset($topMatch['action_score'])) {
                LogService::ai(
                    'demand_analysis_no_action_score',
                    'AnalyzeAndPrioritizeDemand',
                    [
                        'talep_id' => $this->talep->id,
                    ]
                );

                return;
            }

            $actionScore = (float) ($topMatch['action_score'] ?? 0);

            // 4. Action Score > 110 ise bildirim gönder
            if ($actionScore > 110) {
                $danisman = User::find($this->talep->danisman_id);

                if (!$danisman) {
                    LogService::error(
                        'Danışman bulunamadı',
                        [
                            'talep_id' => $this->talep->id,
                            'danisman_id' => $this->talep->danisman_id,
                        ],
                        null,
                        LogService::CHANNEL_AI
                    );

                    return;
                }

                // Bildirim gönder
                $danisman->notify(new NewSalesOpportunity(
                    $this->talep,
                    $topMatch,
                    $actionScore
                ));

                LogService::ai(
                    'sales_opportunity_notification_sent',
                    'AnalyzeAndPrioritizeDemand',
                    [
                        'talep_id' => $this->talep->id,
                        'danisman_id' => $danisman->id,
                        'action_score' => $actionScore,
                        'ilan_id' => $topMatch['ilan_id'] ?? null,
                    ]
                );
            } else {
                LogService::ai(
                    'demand_analysis_low_action_score',
                    'AnalyzeAndPrioritizeDemand',
                    [
                        'talep_id' => $this->talep->id,
                        'action_score' => $actionScore,
                        'threshold' => 110,
                    ]
                );
            }

            $durationMs = LogService::stopTimer($startTime);

            LogService::ai(
                'demand_analysis_completed',
                'AnalyzeAndPrioritizeDemand',
                [
                    'talep_id' => $this->talep->id,
                    'matches_count' => count($matches),
                    'top_action_score' => $actionScore ?? 0,
                    'notification_sent' => ($actionScore ?? 0) > 110,
                    'duration_ms' => $durationMs,
                ]
            );

        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            LogService::error(
                'Demand analysis failed',
                [
                    'talep_id' => $this->talep->id,
                    'error' => $e->getMessage(),
                    'duration_ms' => $durationMs,
                ],
                $e,
                LogService::CHANNEL_AI
            );

            // Job'ı tekrar dene
            throw $e;
        }
    }
}

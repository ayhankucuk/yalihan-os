<?php

namespace App\Jobs;

use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use App\Services\Integrations\AudioGenerationService;
use App\Services\Logging\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RenderMarketingVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(
        protected int $ilanId
    ) {}

    public function handle(YalihanCortex $cortex, AudioGenerationService $audioService): void
    {
        $ilan = Ilan::find($this->ilanId);
        if (! $ilan) {
            return;
        }

        $ilan->video_isleme_durumu = 'queued';
        $ilan->video_last_frame = 0;
        $ilan->save();

        try {
            $scriptResult = $cortex->generateVideoScript($ilan);

            if (! ($scriptResult['success'] ?? false)) {
                $ilan->video_isleme_durumu = 'failed';
                $ilan->save();

                return;
            }

            $ilan->video_isleme_durumu = 'rendering';
            $ilan->video_last_frame = 30;
            $ilan->save();

            $scriptText = $scriptResult['script'] ?? '';
            $audioPath = $scriptText !== '' ? $audioService->generateAudioFile($scriptText, 'pro_ton', 'calm') : null;

            $ilan->video_isleme_durumu = 'completed';
            $ilan->video_last_frame = 100;
            $ilan->video_url = $scriptResult['preview_url'] ?? null;
            $ilan->save();

            LogService::ai(
                'marketing_video_render_completed',
                'RenderMarketingVideo',
                [
                    'ilan_id' => $ilan->id,
                    'islem_durumu' => $ilan->video_isleme_durumu,
                ]
            );
        } catch (\Throwable $e) {
            $ilan->video_isleme_durumu = 'failed';
            $ilan->save();

            LogService::error(
                'Marketing video render failed',
                [
                    'ilan_id' => $ilan->id,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            throw $e; // Re-throw for queue retry
        }
    }
}

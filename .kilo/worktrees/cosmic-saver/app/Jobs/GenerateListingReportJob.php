<?php

namespace App\Jobs;

use App\Models\Ilan;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate Listing Report Job
 *
 * Triggered when 'firsat_mühru' is awarded.
 * Generates the PDF report and updates the ilan record with the path and hash.
 */
class GenerateListingReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    public function __construct(
        public int $ilanId,
        public string $locale = 'tr'
    ) {
        $this->onQueue('reports');
    }

    public function handle(ReportService $reportService): void
    {
        Log::info("📄 Report generation started for #{$this->ilanId}");

        $ilan = Ilan::find($this->ilanId);

        if (!$ilan) {
            Log::warning("📄 Report generation skipped: Ilan #{$this->ilanId} not found");
            return;
        }

        // Only generate if mühür is active
        if (!$ilan->firsat_mühru) {
            Log::info("📄 Report generation skipped: Ilan #{$this->ilanId} lost mühür");
            return;
        }

        try {
            $result = $reportService->generate($ilan, $this->locale);

            if ($result['success']) {
                $ilan->update([
                    'rapor_yolu' => $result['path'],
                    'rapor_hash' => $result['hash'],
                    'rapor_uretildi_at' => now(),
                    'rapor_surum' => $ilan->rapor_surum + 1,
                    'rapor_gecersiz_mi' => false,
                ]);

                Log::info("✅ Report generated for #{$this->ilanId}: {$result['path']}");
            } else {
                Log::error("❌ Report generation failed for #{$this->ilanId}");
            }
        } catch (\Exception $e) {
            Log::error("🔥 Report generation exception for #{$this->ilanId}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}

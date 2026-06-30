<?php

namespace App\Console\Commands\AI;

use Illuminate\Console\Command;
use App\Services\AI\YalihanCortex;
use App\Models\AI\AIOpportunityLog;
use Illuminate\Support\Facades\Log;

class ScanOpportunities extends Command
{
    protected $signature = 'ai:scan-opportunities';
    protected $description = 'AI Opportunity Engine - Scan projections and generate opportunities';

    public function handle(YalihanCortex $cortex)
    {
        $this->info('Starting AI Opportunity Scan...');

        try {
            $opportunities = $cortex->detectOpportunities(['min_score' => 60]);
            $count = 0;

            foreach ($opportunities as $opp) {
                AIOpportunityLog::updateOrCreate(
                    ['listing_id' => $opp['listing_id']],
                    [
                        'opportunity_score' => $opp['score'],
                        'opportunity_reason' => $opp['reason'],
                        'ek_bilgiler' => [
                            'explanation' => $opp['explanation'],
                            'metadata' => $opp['metadata']
                        ],
                        'aktiflik_durumu' => 1,
                    ]
                );
                $count++;
            }

            $this->info("Scan completed. Found and logged {$count} opportunities.");

            Log::info('AI Opportunity Scan completed', [
                'count' => $count
            ]);

        } catch (\Exception $e) {
            $this->error('Scan failed: ' . $e->getMessage());
            Log::error('AI Opportunity Scan failed', [
                'error' => $e->getMessage()
            ]);
            return 1;
        }

        return 0;
    }
}

<?php

namespace App\Observers;

use App\Models\AILeadScore;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class AILeadScoreObserver
{
    /**
     * Handle the AILeadScore "saved" event.
     */
    public function saved(AILeadScore $score): void
    {
        $lead = $score->lead;

        if (!$lead) {
            return;
        }

        Log::info("AILeadScoreObserver: Checking automation for Lead #{$lead->id} " .
                  "with WinProb: {$score->win_probability}");

        // 1. Auto-Qualify High Potential Leads
        // P0-A FIX: string karşılaştırması kaldırıldı — Lead::CRM_* int sabiti
        if ($score->win_probability >= 80) {
            if ($lead->crm_durumu === Lead::CRM_NEW) {
                $lead->crm_durumu = Lead::CRM_QUALIFIED;
                $lead->save();
                Log::info("AILeadScoreObserver: Auto-qualified Lead #{$lead->id}");
            }
        }

        // 2. Auto-Nurture Low Potential/Zombie Leads
        if ($score->win_probability <= 20) {
            // Add 'Nurture' tag if not present
            $tags = $lead->tags ?? [];
            if (!in_array('Nurture', $tags)) {
                $tags[] = 'Nurture';
                $lead->tags = $tags;
                $lead->save();
                Log::info("AILeadScoreObserver: Added Nurture tag to Lead #{$lead->id}");
            }
        }
    }
}

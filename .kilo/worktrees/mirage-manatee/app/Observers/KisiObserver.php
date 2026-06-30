<?php

namespace App\Observers;

use App\Models\Kisi;
use App\Jobs\AI\SyncLeadToIntelligence;
use Carbon\Carbon;

class KisiObserver
{
    /**
     * Yeni kişi oluşturulduğunda
     * Context7: KisiTask deprecated - task creation disabled
     */
    public function created(Kisi $kisi): void
    {
        // Skip in testing environment
        if (app()->runningUnitTests() || config('app.env') === 'testing') {
            return;
        }

        // 🧠 CRM Intelligence Sync
        SyncLeadToIntelligence::dispatch($kisi);

        // [SAB] Phase 16.1: CQRS Projection Trigger for Lead/Action Tracking
        if ($kisi->user_id) {
            event(new \App\Events\LeadRegistered(
                null,
                (int) $kisi->user_id,
                'lead'
            ));
        }
    }

    /**
     * Kişi güncellendiğinde
     */
    public function updated(Kisi $kisi): void
    {
        // ❌ DISABLED: Pipeline & Segment tracking disabled (deprecated kisi_tasks)

        // 🧠 CRM Intelligence Sync (Vektörel veriyi tazele)
        SyncLeadToIntelligence::dispatch($kisi);
    }

    private function handlePipelineStageChange(Kisi $kisi): void
    {
        // ❌ DISABLED: kisi_tasks table deprecated
        return;
    }

    private function handleSegmentChange(Kisi $kisi): void
    {
        // ❌ DISABLED: kisi_tasks table deprecated
        return;
    }
}

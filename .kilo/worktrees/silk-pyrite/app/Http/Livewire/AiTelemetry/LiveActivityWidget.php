<?php

namespace App\Http\Livewire\AiTelemetry;

use Livewire\Component;
use App\Models\AILog;

/**
 * Live Activity Widget (Widget 6)
 *
 * Purpose: Real-time stream of AI requests
 * Behavior: Polls every 5s (visibility-aware controlled by frontend)
 */
class LiveActivityWidget extends Component
{
    public $loading = true;
    public $activities = [];

    // Polling is handled by wire:poll in the view
    public function fetchRecentActivity()
    {
        // Using existing model for now as placeholder or mocking data, Context7 allows read-only model usage here
        $this->activities = AILog::latest('olusturma_tarihi')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'time' => $log->olusturma_tarihi->format('H:i:s'),
                    'provider' => $log->provider,
                    'endpoint' => $log->endpoint,
                    'aktiflik_kodu' => $log->aktiflik_kodu,
                    'latency' => $log->duration_ms,
                ];
            })
            ->toArray();

        $this->loading = false;
    }

    public function mount()
    {
        $this->fetchRecentActivity();
    }

    public function render()
    {
        return view('livewire.ai-telemetry.live-activity-widget');
    }
}

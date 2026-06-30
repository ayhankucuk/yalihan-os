<?php

namespace App\Http\Livewire\Admin;

use App\Services\Governance\DecisionComparator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * 🛰️ Governance Command Center (GCC)
 * Phase 4 Final: The War Room
 */
class GovernanceCommandCenter extends Component
{
    public array $stats = [];
    public array $heatmap = [];
    public string $authorityMode = 'auto'; // auto, forced_sql, forced_ai

    public function mount()
    {
        $this->loadAuthorityMode();
        $this->loadStats();
    }

    /**
     * Otorite modunu yükler.
     */
    public function loadAuthorityMode()
    {
        $this->authorityMode = Cache::get('governance:authority:mode', 'auto');
    }

    /**
     * Otorite modunu günceller (Kill-Switch).
     */
    public function setAuthorityMode(string $mode)
    {
        $this->authorityMode = $mode;
        Cache::put('governance:authority:mode', $mode);
        
        $this->dispatchBrowserEvent('notify', [
            'notify_type' => 'warning',
            'message' => "Otorite Modu Güncellendi: " . strtoupper($mode)
        ]);
    }

    /**
     * İstatistikleri ve Heatmap verilerini hesaplar.
     */
    public function loadStats()
    {
        // 1. Drift Stats (Last 24h)
        $this->stats = [
            'total_decisions' => DB::table('governance_decisions')->where('occurred_at', '>', now()->subDay())->count(),
            'drift_count' => DB::table('governance_decisions')
                ->where('occurred_at', '>', now()->subDay())
                ->where('is_violation', true)
                ->count(),
            'savings_usd' => Cache::get('governance:savings:usd', 0.0),
            'sql_load_breaker_hits' => Cache::get('governance:cache:hits', 0)
        ];

        // 2. Drift Heatmap (By Category)
        // Not: Gerçek veride JSON extraction yapılır. Şimdilik mock-aggregator.
        $this->heatmap = [
            'Konut' => 0.12,
            'Ticari' => 0.45, // 🔴 Alarm: Ticari'de sapma yüksek
            'Arsa' => 0.08,
            'Villa' => 0.05
        ];
    }

    public function render()
    {
        return view('livewire.admin.governance-command-center');
    }
}

<?php

namespace App\Http\Livewire\Admin;

use App\Governance\Alerting\GovernanceAlerter;
use App\Governance\Analytics\GovernanceAnalytics;
use App\Governance\Metrics\GovernanceMetrics;
use Livewire\Component;

/**
 * Phase 4C — Governance Dashboard (Week 3)
 *
 * Yönetişim sağlığını gerçek zamanlı izlemek için ana panel bileşeni.
 * Livewire v2 uyumlu (Laravel 10 standartı).
 */
class GovernanceDashboard extends Component
{
    public array $healthScore = [];
    public array $drift = [];
    public array $anomalies = [];
    public array $activeAlerts = [];
    public string $lastUpdated = '';

    protected $listeners = [
        'refresh-governance' => 'loadData'
    ];

    /**
     * Component mount aşaması.
     */
    public function mount(): void
    {
        $this->loadData();
    }

    /**
     * Verileri toplayıcılardan (Metrics, Analytics, Alerter) çeker.
     */
    public function loadData(): void
    {
        try {
            $this->healthScore = GovernanceMetrics::getHealthScore();

            $analytics = new GovernanceAnalytics();
            $this->drift = $analytics->detectDrift();
            $this->anomalies = $analytics->detectAnomalies();

            $alerter = new GovernanceAlerter($analytics);
            $this->activeAlerts = $alerter->getActiveAlerts();

            $this->lastUpdated = now()->format('H:i:s');

        } catch (\Throwable $e) {
            $this->healthScore = [
                'overall' => -1,
                'error'   => $e->getMessage()
            ];
            
            $this->drift = ['has_drift' => false, 'error' => true];
            $this->anomalies = [];
            $this->activeAlerts = [];
            
            \Illuminate\Support\Facades\Log::error('[GovernanceDashboard] Veri yükleme hatası', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Bir alarmı onaylar ve listeyi yeniler.
     */
    public function acknowledgeAlert(int $alertId): void
    {
        try {
            $analytics = new GovernanceAnalytics();
            $alerter = new GovernanceAlerter($analytics);
            
            $alerter->acknowledge($alertId, auth()->id());
            
            $this->loadData();
            
            // Livewire v2: dispatchBrowserEvent
            $this->dispatchBrowserEvent('notify', [
                'notify_type' => 'success',
                'message' => 'Alarm onaylandı.'
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[GovernanceDashboard] Alarm onaylama hatası', [
                'id' => $alertId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * View render.
     */
    public function render()
    {
        return view('livewire.admin.governance-dashboard')
            ->layout('admin.layouts.app'); // resources/views/admin/layouts/app.blade.php
    }
}

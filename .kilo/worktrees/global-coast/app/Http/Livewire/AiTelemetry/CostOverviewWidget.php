<?php

namespace App\Http\Livewire\AiTelemetry;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

/**
 * Cost Overview Widget (Widget 1)
 *
 * Purpose: Display total AI cost burn rate over selected period
 * Chart: Line chart with daily limit indicator
 */
class CostOverviewWidget extends Component
{
    public $period = '7d';
    public $provider = '';
    public $loading = true;
    public $data = null;

    protected $listeners = ['filtersChanged'];

    public function mount()
    {
        $this->fetchData();
    }

    public function filtersChanged($filters)
    {
        $this->period = $filters['period'] ?? '7d';
        $this->provider = $filters['provider'] ?? '';
        $this->fetchData();
    }

    public function fetchData()
    {
        $this->loading = true;

        try {
            $response = Http::get(route('admin.ai.telemetry.cost-overview'), [
                'period' => $this->period,
                'provider' => $this->provider,
            ]);

            if ($response->successful()) {
                $this->data = $response->json()['data'];
            } else {
                $this->data = null;
            }
        } catch (\Exception $e) {
            $this->data = null;
            \Log::error('Cost Overview Widget Error: ' . $e->getMessage());
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.ai-telemetry.cost-overview-widget');
    }
}

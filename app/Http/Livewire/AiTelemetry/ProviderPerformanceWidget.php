<?php

namespace App\Http\Livewire\AiTelemetry;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

/**
 * Provider Performance Widget (Widget 3)
 *
 * Purpose: Compare providers by latency, success rate, and cost
 * Chart: Bar chart / Table hybrid
 */
class ProviderPerformanceWidget extends Component
{
    public $period = '24h'; // Fixed to 24h for recent performance
    public $loading = true;
    public $data = null;

    protected $listeners = ['filtersChanged'];

    public function mount()
    {
        $this->fetchData();
    }

    public function filtersChanged($filters)
    {
        // This widget ignores period filter to show recent performance context,
        // but could adapt if needed. For now, strict 24h as per spec "Micro DoD".
        $this->fetchData();
    }

    public function fetchData()
    {
        $this->loading = true;

        try {
            $response = Http::get(route('admin.ai.telemetry.provider-performance'));

            if ($response->successful()) {
                $this->data = $response->json()['data'];
            } else {
                $this->data = [];
            }
        } catch (\Exception $e) {
            $this->data = [];
            \Log::error('Provider Performance Widget Error: ' . $e->getMessage());
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.ai-telemetry.provider-performance-widget');
    }
}

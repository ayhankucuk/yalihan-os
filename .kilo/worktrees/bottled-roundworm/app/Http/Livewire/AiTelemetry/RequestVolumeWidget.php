<?php

namespace App\Http\Livewire\AiTelemetry;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

/**
 * Request Volume Widget (Widget 2)
 *
 * Purpose: Display request volume over time (Total / Successful / Failed)
 * Chart: Multi-line chart (SVG)
 */
class RequestVolumeWidget extends Component
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
            $response = Http::get(route('admin.ai.telemetry.request-volume'), [
                'period' => $this->period,
                'provider' => $this->provider,
            ]);

            if ($response->successful()) {
                $this->data = $response->json()['data'];
            } else {
                $this->data = [];
            }
        } catch (\Exception $e) {
            $this->data = [];
            \Log::error('Request Volume Widget Error: ' . $e->getMessage());
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.ai-telemetry.request-volume-widget');
    }
}

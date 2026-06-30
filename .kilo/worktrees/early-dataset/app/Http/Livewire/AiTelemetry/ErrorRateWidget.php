<?php

namespace App\Http\Livewire\AiTelemetry;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

/**
 * Error Rate Widget (Widget 4)
 *
 * Purpose: Display error rate and breakdown of recent errors
 * Chart: CSS/SVG Donut Chart + List
 */
class ErrorRateWidget extends Component
{
    public $loading = true;
    public $data = null;

    protected $listeners = ['filtersChanged' => 'refresh'];

    public function mount()
    {
        $this->fetchData();
    }

    public function refresh()
    {
        $this->fetchData();
    }

    public function fetchData()
    {
        $this->loading = true;

        try {
            $response = Http::get(route('admin.ai.telemetry.error-analytics'));

            if ($response->successful()) {
                $this->data = $response->json()['data'];
            } else {
                $this->data = [];
            }
        } catch (\Exception $e) {
            $this->data = [];
            \Log::error('Error Rate Widget Error: ' . $e->getMessage());
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.ai-telemetry.error-rate-widget');
    }
}

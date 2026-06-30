<?php

namespace App\Http\Livewire\AiTelemetry;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

/**
 * Token Leaderboard Widget (Widget 5)
 *
 * Purpose: Show top token consumers
 * Chart: Table
 */
class TokenLeaderboardWidget extends Component
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
            $response = Http::get(route('admin.ai.telemetry.token-leaderboard'));

            if ($response->successful()) {
                $this->data = $response->json()['data'];
            } else {
                $this->data = [];
            }
        } catch (\Exception $e) {
            $this->data = [];
            \Log::error('Token Leaderboard Widget Error: ' . $e->getMessage());
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.ai-telemetry.token-leaderboard-widget');
    }
}

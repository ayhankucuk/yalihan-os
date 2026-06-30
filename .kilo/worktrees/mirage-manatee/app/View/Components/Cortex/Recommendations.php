<?php

namespace App\View\Components\Cortex;

use App\Models\Lead;
use App\Services\Cortex\MatchingEngine;
use Illuminate\View\Component;

class Recommendations extends Component
{
    public $lead;
    public $matches;

    /**
     * Create a new component instance.
     */
    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
        $engine = new MatchingEngine();
        $this->matches = $engine->findMatchesForLead($lead);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.cortex.recommendations');
    }
}

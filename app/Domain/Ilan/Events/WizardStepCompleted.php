<?php

namespace App\Domain\Ilan\Events;

use App\Models\Ilan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ⚡ WizardStepCompleted Event
 *
 * Bir sihirbaz adımı başarıyla tamamlandığında tetiklenir.
 */
class WizardStepCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Ilan $ilan,
        public readonly int $completedStep,
        public readonly array $stepData = []
    ) {}
}

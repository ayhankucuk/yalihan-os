<?php

namespace App\Domain\Ilan\Events;

use App\Models\Ilan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 🚀 WizardSubmitted Event
 *
 * İlan sihirbazı tüm adımları başarıyla tamamlanıp onaylandığında tetiklenir.
 * CQRS projeksiyonları, tersine eşleştirme (lead matching) ve AI arama indeksleyicilerini uyarır.
 */
class WizardSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Ilan $ilan,
        public readonly array $allStepsData = []
    ) {}
}

<?php

namespace App\Events\CRM;

use App\Domain\CRM\DTOs\DemandMatchResult;
use App\Models\Ilan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DemandMatched — Domain Event
 *
 * Dispatched when a listing matches a demand with score >= threshold.
 */
class DemandMatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Ilan $ilan,
        public readonly DemandMatchResult $matchResult,
        public readonly string $idempotencyKey
    ) {}
}

<?php

namespace App\Events;

use App\Models\LedgerEntry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LedgerDoubleEntryRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public LedgerEntry $debitEntry;
    public LedgerEntry $creditEntry;

    /**
     * Create a new event instance.
     */
    public function __construct(LedgerEntry $debitEntry, LedgerEntry $creditEntry)
    {
        $this->debitEntry = $debitEntry;
        $this->creditEntry = $creditEntry;
    }
}

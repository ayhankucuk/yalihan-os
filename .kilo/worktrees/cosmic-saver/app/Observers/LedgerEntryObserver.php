<?php

namespace App\Observers;

use App\Models\LedgerEntry;
use RuntimeException;

class LedgerEntryObserver
{
    /**
     * ✅ SAB Core: Enforce Immutable records before DB hits
     */
    public function updating(LedgerEntry $ledgerEntry): bool
    {
        throw new RuntimeException("🔒 [Immutable Policy] Ledger entries cannot be updated. You must create a Reversal Entry.");
    }

    /**
     * ✅ SAB Core: Enforce Immutable records before DB hits
     */
    public function deleting(LedgerEntry $ledgerEntry): bool
    {
        throw new RuntimeException("🔒 [Immutable Policy] Ledger entries cannot be deleted. You must create a Reversal Entry.");
    }

    /**
     * Handle the LedgerEntry "created" event.
     */
    public function created(LedgerEntry $ledgerEntry): void
    {
        //
    }

    /**
     * Handle the LedgerEntry "updated" event.
     */
    public function updated(LedgerEntry $ledgerEntry): void
    {
        //
    }

    /**
     * Handle the LedgerEntry "deleted" event.
     */
    public function deleted(LedgerEntry $ledgerEntry): void
    {
        //
    }

    /**
     * Handle the LedgerEntry "restored" event.
     */
    public function restored(LedgerEntry $ledgerEntry): void
    {
        //
    }

    /**
     * Handle the LedgerEntry "force deleted" event.
     */
    public function forceDeleted(LedgerEntry $ledgerEntry): void
    {
        //
    }
}

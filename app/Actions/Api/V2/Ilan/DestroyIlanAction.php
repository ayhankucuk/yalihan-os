<?php

namespace App\Actions\Api\V2\Ilan;

use App\Models\Ilan;
use App\Models\V2\Ilan as V2Ilan;
use App\Services\Listing\ListingCrudBridge;

/**
 * DestroyIlanAction
 *
 * Sprint 12C Phase 3 Wave 1: API Actions Migration
 * Uses ListingCrudBridge for controlled migration.
 */
class DestroyIlanAction
{
    public function __construct(
        private readonly ListingCrudBridge $bridge,
    ) {}

    // Phase3-WA: delegated to Bridge (controlled migration)
    public function handle(V2Ilan $v2Ilan): Ilan
    {
        $ilan = Ilan::findOrFail($v2Ilan->id);

        return $this->bridge->destroy($ilan);
    }
}

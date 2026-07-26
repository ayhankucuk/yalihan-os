<?php

namespace App\Actions\Api\V2\Ilan;

use App\Models\Ilan;
use App\Models\V2\Ilan as V2Ilan;
use App\Services\Listing\ListingCrudBridge;

/**
 * UpdateIlanAction
 *
 * Sprint 12C Phase 3 Wave 1: API Actions Migration
 * Uses ListingCrudBridge for controlled migration.
 */
class UpdateIlanAction
{
    public function __construct(
        private readonly ListingCrudBridge $bridge,
    ) {}

    // Phase3-WA: delegated to Bridge (controlled migration)
    public function handle(V2Ilan $v2Ilan, array $data): Ilan
    {
        $ilan = Ilan::findOrFail($v2Ilan->id);

        return $this->bridge->update($ilan, $data);
    }
}

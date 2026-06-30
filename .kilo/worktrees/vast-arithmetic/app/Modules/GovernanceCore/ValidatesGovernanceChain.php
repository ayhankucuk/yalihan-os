<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore;

use App\Models\GovernanceDecision;
use Illuminate\Support\Facades\Log;

/**
 * Trait ValidatesGovernanceChain
 *
 * Zero Trust Forensics katmanı — deterministik zincir bütünlüğü doğrulaması.
 * SAB Core Constitution v2.6 — Anti-Bypass Guard aktif.
 */
trait ValidatesGovernanceChain
{
    /**
     * Kriptografik zincir kırılma testini yürütür.
     *
     * @fail-loud Herhangi bir kopmada sessiz kalmaz, loglar ve false döner.
     */
    public function validateChainIntegrity(): bool
    {
        // Explicit orderBy + tie-break (Determinism Standardı)
        $decisions = GovernanceDecision::orderBy('id', 'asc')->get();

        $expectedPrevHash = 'GENESIS_BLOCK_HASH';

        foreach ($decisions as $decision) {
            $calculatedHash = hash('sha256', json_encode([
                'id'             => $decision->id,
                'yayin_durumu'   => $decision->yayin_durumu,
                'prev_hash'      => $decision->prev_hash,
                'created_at'     => $decision->created_at->toDateTimeString(),
            ]));

            if (
                $decision->prev_hash !== $expectedPrevHash ||
                $decision->current_hash !== $calculatedHash
            ) {
                Log::critical('GOVERNANCE_CHAIN_BREACH_DETECTED', [
                    'decision_id'        => $decision->id,
                    'expected_prev'      => $expectedPrevHash,
                    'actual_prev'        => $decision->prev_hash,
                    'calculated_current' => $calculatedHash,
                    'stored_current'     => $decision->current_hash,
                ]);

                return false;
            }

            $expectedPrevHash = $decision->current_hash;
        }

        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Portfolio;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * TapuMatchService - EİDS ID bazlı tapu_id eşleştirme servisi.
 */
class TapuMatchService
{
    use GuardsAgentWrites;
    /**
     * EİDS external_ref'e göre tapu_id bul.
     *
     * @param string $externalRef Format: "eids:..."
     * @return array ['tapu_id' => int|null, 'match_state' => string, 'candidates' => array, 'confidence' => float]
     */
    public function matchByExternalRef(string $externalRef): array
    {
        // Parse external_ref (eids:82681xxx:unit-1, eids:92125750, eids:mandalina:A-2000)
        if (!str_starts_with($externalRef, 'eids:')) {
            return [
                'tapu_id' => null,
                'match_state' => 'invalid_ref',
                'candidates' => [],
                'confidence' => 0.0,
                'error' => 'External ref must start with "eids:"'
            ];
        }

        $parts = explode(':', $externalRef);
        $eidsIdentifier = $parts[1] ?? null;

        if (!$eidsIdentifier) {
            return [
                'tapu_id' => null,
                'match_state' => 'invalid_ref',
                'candidates' => [],
                'confidence' => 0.0,
                'error' => 'Could not parse EİDS identifier'
            ];
        }

        // Try to find matching tapu records
        // Assuming tapu table has: id, eids_id, ada, parsel, mahalle, ilce, il
        // Match logic:
        // 1. Exact EİDS ID match
        // 2. Partial match by project name in metadata
        // 3. Location-based fuzzy match (mahalle + ilce + il)

        // Check if tapu_kayitlari table exists
        if (!DB::getSchemaBuilder()->hasTable('tapu_kayitlari')) {
            Log::info("TapuMatch: tapu_kayitlari table not found, skipping match for {$externalRef}");
            return [
                'tapu_id' => null,
                'match_state' => 'pending',
                'candidates' => [],
                'confidence' => 0.0,
                'note' => 'Tapu kayitlari table not available, pending manual entry'
            ];
        }

        $candidates = DB::table('tapu_kayitlari')
            ->where('eids_id', 'LIKE', "%{$eidsIdentifier}%")
            ->orWhere('external_refs', 'LIKE', "%{$externalRef}%")
            ->get();

        if ($candidates->isEmpty()) {
            Log::info("TapuMatch: No candidates found for {$externalRef}");
            return [
                'tapu_id' => null,
                'match_state' => 'pending',
                'candidates' => [],
                'confidence' => 0.0,
                'note' => 'No tapu records found, pending manual entry'
            ];
        }

        if ($candidates->count() === 1) {
            $match = $candidates->first();
            Log::info("TapuMatch: Single match found for {$externalRef} -> tapu_id={$match->id}");
            return [
                'tapu_id' => $match->id,
                'match_state' => 'matched',
                'candidates' => [$match],
                'confidence' => 0.95,
                'method' => 'exact_eids_match'
            ];
        }

        // Multiple candidates - requires reconciliation
        Log::warning("TapuMatch: Multiple candidates found for {$externalRef}", [
            'count' => $candidates->count(),
            'candidate_ids' => $candidates->pluck('id')->toArray()
        ]);
        return [
            'tapu_id' => null,
            'match_state' => 'reconciliation_required',
            'candidates' => $candidates->toArray(),
            'confidence' => 0.5,
            'note' => 'Multiple tapu records found, manual selection required'
        ];
    }

    /**
     * Batch match: tüm external_ref listesi için eşleştirme yap.
     *
     * @param array $externalRefs
     * @return array ['ref' => matchResult]
     */
    public function batchMatch(array $externalRefs): array
    {
        $results = [];
        foreach ($externalRefs as $ref) {
            $results[$ref] = $this->matchByExternalRef($ref);
        }
        return $results;
    }

    /**
     * Update ilan record with tapu_id and match metadata.
     *
     * @param int $ilanId
     * @param int|null $tapuId
     * @param array $matchMetadata
     * @return bool
     */
    public function updateIlanTapuMatch(int $ilanId, ?int $tapuId, array $matchMetadata): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        try {
            DB::table('ilanlar')
                ->where('id', $ilanId)
                ->update([
                    'tapu_id' => $tapuId,
                    'metadata->tapu_match' => json_encode($matchMetadata),
                    'updated_at' => now()
                ]);
            return true;
        } catch (\Exception $e) {
            Log::error("TapuMatch: Failed to update ilan {$ilanId}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

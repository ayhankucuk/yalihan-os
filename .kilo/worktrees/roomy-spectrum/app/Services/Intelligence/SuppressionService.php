<?php

namespace App\Services\Intelligence;

use App\DTOs\CortexFinding;
use App\Models\GovernanceSuppression;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SuppressionService
{
    use GuardsAgentWrites;
    /**
     * Check if a finding is suppressed by any active suppression rule.
     */
    public function isSuppressed(CortexFinding $finding): bool
    {
        $ruleKey = $finding->rule_name ?? ($finding->source . '_' . $finding->domain);

        $suppressions = GovernanceSuppression::active()->get();

        foreach ($suppressions as $suppression) {
            if ($suppression->matchesFinding($finding->source, $finding->domain, $ruleKey)) {
                Log::channel('daily')->info('Finding suppressed by rule', [
                    'finding_id' => $finding->finding_id,
                    'suppression_id' => $suppression->id,
                    'rule_key' => $ruleKey,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Filter out suppressed findings from a batch.
     *
     * @param CortexFinding[] $findings
     * @return array{passed: CortexFinding[], suppressed: CortexFinding[]}
     */
    public function filterBatch(array $findings): array
    {
        $passed = [];
        $suppressed = [];

        foreach ($findings as $finding) {
            if ($this->isSuppressed($finding)) {
                $suppressed[] = $finding;
            } else {
                $passed[] = $finding;
            }
        }

        return ['passed' => $passed, 'suppressed' => $suppressed];
    }

    /**
     * Create a new suppression rule.
     */
    public function createSuppression(array $data): GovernanceSuppression
    {
        $this->blockAgentWrite(__FUNCTION__);

        $suppression = GovernanceSuppression::create([
            'rule_key' => $data['rule_key'],
            'scope' => $data['scope'] ?? 'domain',
            'source' => $data['source'] ?? null,
            'domain' => $data['domain'] ?? null,
            'reason' => $data['reason'],
            'suppressed_by' => $data['suppressed_by'] ?? Auth::id(),
            'expires_at' => $data['expires_at'] ?? null,
            'aktiflik_durumu' => true,
        ]);

        Log::channel('daily')->info('Suppression rule created', [
            'suppression_id' => $suppression->id,
            'rule_key' => $suppression->rule_key,
            'scope' => $suppression->scope,
            'expires_at' => $suppression->expires_at,
        ]);

        return $suppression;
    }

    /**
     * Deactivate a suppression rule.
     */
    public function removeSuppression(int $suppressionId): bool
    {
        $suppression = GovernanceSuppression::find($suppressionId);

        if (!$suppression) {
            return false;
        }

        $suppression->deactivate();

        Log::channel('daily')->info('Suppression rule deactivated', [
            'suppression_id' => $suppressionId,
            'rule_key' => $suppression->rule_key,
        ]);

        return true;
    }

    /**
     * Get all active suppressions.
     */
    public function getActiveSuppressions()
    {
        return GovernanceSuppression::active()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Expire all past-due suppressions.
     */
    public function cleanupExpired(): int
    {
        $expired = GovernanceSuppression::where('aktiflik_durumu', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $suppression) {
            $suppression->deactivate();
        }

        return $expired->count();
    }
}

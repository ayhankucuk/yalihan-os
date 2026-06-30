<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Core;
use App\Models\PropertyConfigVersion;
use Illuminate\Support\Facades\Log;

/**
 * Version Rollback Service
 *
 * Handles rolling back to a previous APPROVED configuration version.
 */
class VersionRollbackService
{
    public function __construct(
        private readonly VersionActivationService $activationService
    ) {}

    /**
     * Rollback to a specific version.
     *
     * @throws \DomainException
     */
    public function rollback(PropertyConfigVersion $version, int $actorId, string $reason): void
    {
        // Only APPROVED or previously ARCHIVED but once APPROVED versions can be rollback targets
        // Rules say: Rollback = APPROVED -> ACTIVE (atomic swap)
        if ($version->yonetim_durumu !== VersionStateMachine::DURUM_ONAYLANDI &&
            $version->yonetim_durumu !== VersionStateMachine::DURUM_ARSIVLENDI) {
            throw new \DomainException("Yalıhan Governance Error: Rollback target must be APPROVED or ARCHIVED");
        }

        // We temporarily set durum to APPROVED if it was ARCHIVED to allow transition to ACTIVE via activation service
        $eski_durum = $version->yonetim_durumu;
        if ($eski_durum === VersionStateMachine::DURUM_ARSIVLENDI) {
            $version->update(['yonetim_durumu' => VersionStateMachine::DURUM_ONAYLANDI]);
        }

        try {
            $this->activationService->activate($version, $actorId);

            // Log specific rollback action in audit
            $this->activationService->logAudit($version->id, 'rolled_back', $actorId, [
                'reason' => $reason,
                'eski_durum' => $eski_durum
            ]);

            Log::warning("PropertyHub Rollback executed to version {$version->version_hash}", [
                'actor_id' => $actorId,
                'reason' => $reason
            ]);
        } catch (\Exception $e) {
            // Restore state if activation fails
            if ($eski_durum === VersionStateMachine::DURUM_ARSIVLENDI) {
                $version->update(['yonetim_durumu' => VersionStateMachine::DURUM_ARSIVLENDI]);
            }
            throw $e;
        }
    }
}

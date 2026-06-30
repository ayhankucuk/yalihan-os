<?php

namespace App\Services\Governance;

use App\Enums\Governance\GovernanceState;

final class GovernanceTransitionGuard
{
    public function canPromote(GovernanceState $currentState): bool
    {
        return $currentState === GovernanceState::DRAFT;
    }

    public function canPublish(GovernanceState $currentState): bool
    {
        return $currentState === GovernanceState::PROMOTED;
    }

    public function canArchive(GovernanceState $currentState): bool
    {
        return in_array($currentState, [
            GovernanceState::DRAFT,
            GovernanceState::PUBLISHED,
        ], true);
    }
}

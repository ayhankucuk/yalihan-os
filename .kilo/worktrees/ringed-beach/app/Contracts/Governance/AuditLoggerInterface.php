<?php

namespace App\Contracts\Governance;

use App\DataTransferObjects\Governance\GovernanceAuditContext;
use App\Enums\Governance\GovernanceActionType;
use App\Enums\Governance\GovernanceState;

interface AuditLoggerInterface
{
    public function logTransition(
        GovernanceActionType $actionType,
        GovernanceAuditContext $context,
        ?GovernanceState $fromState = null,
        ?GovernanceState $toState = null,
    ): void;
}

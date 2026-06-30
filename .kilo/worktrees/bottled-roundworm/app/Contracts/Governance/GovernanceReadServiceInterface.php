<?php

namespace App\Contracts\Governance;

use App\DataTransferObjects\Governance\DiffProjection;

interface GovernanceReadServiceInterface
{
    /**
     * Salt-Okunur Diff Getirme Konratı.
     */
    public function getDiff(string $entityType, int|string $entityId): DiffProjection;
}

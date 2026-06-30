<?php

namespace App\Contracts\Governance;

use App\DataTransferObjects\Governance\CreateDraftCommand;
// use App\DataTransferObjects\Governance\UpdateDraftCommand;
use App\DataTransferObjects\Governance\PromoteDraftCommand;
use App\DataTransferObjects\Governance\PublishPromotedCommand;
// use App\DataTransferObjects\Governance\ArchiveGovernedEntityCommand;

interface GovernanceServiceInterface
{
    public function createDraft(CreateDraftCommand $command): void;

    // public function updateDraft(UpdateDraftCommand $command): void;

    public function promote(PromoteDraftCommand $command): void;

    public function publish(PublishPromotedCommand $command): void;

    // public function archive(ArchiveGovernedEntityCommand $command): void;
}

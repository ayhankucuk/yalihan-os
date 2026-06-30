<?php

namespace App\Enums\Governance;

enum GovernanceActionType: string
{
    case DRAFT_CREATED = 'draft_created';
    case DRAFT_UPDATED = 'draft_updated';
    case DRAFT_DELETED = 'draft_deleted';
    case PROMOTED = 'promoted';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case ROLLED_BACK = 'rolled_back';
    case REVIEW_REQUESTED = 'review_requested';
    case REVIEW_APPROVED = 'review_approved';
    case REVIEW_REJECTED = 'review_rejected';
    case TRANSITION_REJECTED = 'transition_rejected';
}

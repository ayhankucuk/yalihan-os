<?php

namespace App\Enums\Governance;

enum GovernanceState: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case PROMOTED = 'promoted';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}

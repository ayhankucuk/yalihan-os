<?php

namespace App\Modules\Emlak\Models;

enum IlanStage: string
{
    case STEP_1 = 'step_1';
    case STEP_2 = 'step_2';
    case STEP_3 = 'step_3';
    case STEP_4 = 'step_4';
    case STEP_5 = 'step_5';
    case STEP_6 = 'step_6';
    case STEP_7 = 'step_7';
    case COMPLETED = 'completed';
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}

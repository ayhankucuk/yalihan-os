<?php

namespace App\Domain\AI\Enums;

enum CortexCapability: string
{
    case TEXT_GENERATION = 'text_generation';
    case STRUCTURED_EXTRACTION = 'structured_extraction';
    case BATCH_PROCESSING = 'batch_processing';
    case MULTIMODAL = 'multimodal';
}

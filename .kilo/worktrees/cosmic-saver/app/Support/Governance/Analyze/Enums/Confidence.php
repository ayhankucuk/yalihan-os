<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Enums;

/**
 * Confidence in a finding — orthogonal to RiskLevel.
 *
 * HIGH + HIGH = act now
 * HIGH risk + LOW confidence = investigate, do not panic
 */
enum Confidence: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
}

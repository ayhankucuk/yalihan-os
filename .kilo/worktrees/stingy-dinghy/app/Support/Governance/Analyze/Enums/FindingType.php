<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze\Enums;

/**
 * Finding type — taxonomic classification per ADR H7.
 */
enum FindingType: string
{
    case AUTHORITY_CONFLICT = 'authority_conflict';
    case CONTEXT7_VIOLATION = 'context7_violation';
    case ORPHAN_REFERENCE = 'orphan_reference';
    case DEPRECATED_SURFACE = 'deprecated_surface';
    case ENVIRONMENT_BLOCKER = 'environment_blocker';
    case DOC_DRIFT = 'doc_drift';
    case LEGACY_DEBT = 'legacy_debt';
    case RUNTIME_RISK = 'runtime_risk';
}

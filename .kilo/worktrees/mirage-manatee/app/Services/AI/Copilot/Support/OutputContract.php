<?php

namespace App\Services\AI\Copilot\Support;

final class OutputContract
{
    public const ALLOWED_STAGES = [
        'audit',
        'fix',
        'execution',
        'verify',
        'govern',
    ];

    // context7-ignore
    public const ALLOWED_STATUSES = [
        'safe',
        'warning',
        'unsafe',
    ];

    public const ALLOWED_ACTIONS = [
        'proceed',
        'proceed_with_caution',
        'block',
    ];

    public const REQUIRED_TOP_LEVEL_KEYS = [
        'stage',
        'findings',
        'fixes',
        'execution',
        'verification',
        'decision',
    ];

    public const OPTIONAL_TOP_LEVEL_KEYS = [
        'summary',
        'warnings',
        'meta',
    ];

    public const FORBIDDEN_TOP_LEVEL_KEYS = [
        'status',
    ];
}

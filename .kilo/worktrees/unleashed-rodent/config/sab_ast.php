<?php

/**
 * SAB AST Analyzer Configuration — Pack-P3C
 *
 * This file controls which AST rules are active, their severity, and path exclusions.
 * All rules in P3A/P3B/P3C are report-only — they never block builds.
 *
 * To disable a rule: set 'enabled' => false.
 * To override severity: set 'severity' => 'MEDIUM'.
 * To exclude additional paths: add fragments to 'excluded_paths'.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Global Report-Only Override
    |--------------------------------------------------------------------------
    | When true, ALL AST rules are forced to report-only mode regardless of
    | individual rule settings. This is the P3A/P3B/P3C safe default.
    */
    'report_only' => true,

    /*
    |--------------------------------------------------------------------------
    | AST Rule Definitions
    |--------------------------------------------------------------------------
    | Each key is the Rule ID returned by GovernanceAstRuleInterface::getRuleId().
    |
    | Options per rule:
    |   enabled         (bool)   — Whether the rule is active. Default: true.
    |   severity        (string) — LOW | MEDIUM | HIGH | CRITICAL. Overrides rule default.
    |   excluded_paths  (array)  — Path fragments to skip (merged with rule defaults).
    */
    'rules' => [

        'LanguageHardcodeAST' => [
            'enabled'        => true,
            'severity'       => 'HIGH',
            'excluded_paths' => [
                'database/seeders',
                'database/migrations',
                'tests/',
                'config/',
            ],
        ],

        'SilentCatchAST' => [
            'enabled'        => true,
            'severity'       => 'MEDIUM',
            'excluded_paths' => [
                'database/seeders',
                'database/migrations',
                'tests/',
                'config/',
                'vendor/',
            ],
        ],

        'ForbiddenFunctionAST' => [
            'enabled'        => true,   // Pack-P3D: activated (report-only)
            'severity'       => 'HIGH',
            'forbidden_functions' => [
                'eval',
                'passthru',
                'system',
                'shell_exec',
                'exec',
                'proc_open',
                'popen',
                'dd',
                'dump',
            ],
            'excluded_paths' => [
                'vendor/',
                'tests/',
                'database/migrations/',
            ],
        ],

        'EnvUsageAST' => [
            'enabled'        => true,
            'severity'       => 'HIGH',
            'excluded_paths' => [
                'config/',
                'tests/',
                'vendor/',
                'database/migrations/',
                'public/index.php',
            ],
        ],

        'ForbiddenFieldAST' => [
            'enabled'        => true,
            'severity'       => 'MEDIUM',
            'excluded_paths' => [
                'vendor/',
                'tests/',
                'database/migrations/',
                'app/Services/AI/CodeReviewService.php', // Legacy scanner allowed to use these strings
                'app/Services/Governance/Ast/Rules/ForbiddenFieldAstRule.php', // Rule itself
            ],
        ],
    ],

];

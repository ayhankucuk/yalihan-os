<?php

/**
 * ✅ P1: Test Coverage Configuration
 *
 * Target: 70% code coverage
 * Tools: PHPUnit + Xdebug (coverage)
 *
 * Install: composer require --dev phpunit/phpunit infection/infection
 */

return [
    // Coverage report directory
    'report_path' => env('COVERAGE_REPORT_PATH', 'coverage'),

    // Coverage report formats
    'formats' => [
        'html',      // HTML reports (view in browser)
        'clover',    // Clover XML (for CI/CD)
        'text',      // Text summary (terminal)
    ],

    // Minimum coverage threshold
    'thresholds' => [
        'line' => 70,       // 70% line coverage
        'branch' => 65,     // 65% branch coverage
        'method' => 75,     // 75% method coverage
    ],

    // Directories to cover
    'directories' => [
        'app/Http/Controllers',
        'app/Models',
        'app/Services',
        'app/Repositories',
        'app/Rules',
        'app/Jobs',
    ],

    // Directories to exclude
    'exclude' => [
        'app/Console',
        'app/Exceptions',
        'app/Providers',
        'app/Http/Middleware',
    ],

    // Test directories
    'test_paths' => [
        'tests/Unit',
        'tests/Feature',
    ],

    // Mutation testing (find bugs with code changes)
    'mutation_testing' => env('MUTATION_TESTING_ENABLED', false),

    // Fail on low coverage
    'fail_on_low_coverage' => env('CI') === 'true', // Only fail in CI
];

#!/usr/bin/env php
<?php

/**
 * Command Registry Extractor
 * Extracts all registered Artisan commands for G1 Guard
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Get all registered commands
$artisan = $app->make(Illuminate\Contracts\Console\Kernel::class);
$artisan->bootstrap();

$commands = collect($artisan->all())
    ->map(function ($command) {
        return [
            'name' => $command->getName(),
            'description' => $command->getDescription(),
            'hidden' => $command->isHidden(),
        ];
    })
    ->sortBy('name')
    ->values()
    ->all();

// Output as JSON
echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'total' => count($commands),
    'commands' => $commands,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

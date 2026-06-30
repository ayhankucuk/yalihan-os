<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| OpenClaw Agent Routes
|--------------------------------------------------------------------------
|
| Dedicated API surface for AI agent (OpenClaw) interactions.
| Protected by 3-layer middleware stack:
|   1. openclaw.enabled — kill switch
|   2. openclaw.scope   — token + scope validation
|   3. openclaw.boundary — allowlist, forbidden patterns, proposal-only, source tagging
|
| All routes require X-Agent-Source: openclaw, X-Agent-Token, X-Correlation-Id headers.
| Keep this surface minimal — expand only with ADR justification.
|
*/

Route::get('/health', fn () => response()->json([
    'basarili' => true,
    'servis' => 'openclaw-agent-gateway',
    'zaman_damgasi' => now()->toIso8601String(),
]))->name('api.agent.health');

Route::prefix('context')->group(function () {
    Route::get('/', fn () => response()->json([
        'basarili' => true,
        'context' => [],
    ]))->name('api.agent.context.index');
});

Route::prefix('suggestions')->group(function () {
    Route::get('/', fn () => response()->json([
        'basarili' => true,
        'suggestions' => [],
    ]))->name('api.agent.suggestions.index');
});

Route::prefix('proposals')->group(function () {
    Route::get('/', fn () => response()->json([
        'basarili' => true,
        'proposals' => [],
    ]))->name('api.agent.proposals.index');

    Route::post('/', fn () => response()->json([
        'basarili' => true,
        'id' => null,
    ], 202))->name('api.agent.proposals.store');
});

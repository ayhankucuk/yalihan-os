<?php

/**
 * 🎯 Action Center API Routes (v1)
 *
 * Sprint 15 Phase 2 — Auto-assignment and task management.
 *
 * Endpoints:
 *   GET  /api/v1/action-center/dashboard          — Priority queue for authenticated user
 *   GET  /api/v1/action-center/tasks             — Paginated Gorev list with filters
 *   GET  /api/v1/action-center/tasks/{id}        — Single Gorev detail
 *   PATCH /api/v1/action-center/tasks/{id}/assign       — Assign to user
 *   PATCH /api/v1/action-center/tasks/{id}/status       — Update lifecycle status
 *   POST /api/v1/action-center/tasks/{id}/auto-assign    — Trigger auto-assignment
 *   GET  /api/v1/action-center/stats             — Tenant-scoped statistics
 *
 * Auth: auth:sanctum (all routes)
 * Tenant isolation: enforced via tenant_id on Gorev model
 *
 * @see docs/architecture/sprint-15-action-center-architecture.md
 * @see app/Http/Controllers/Api/V1/ActionCenterController
 * @see app/Services/ActionCenter/ActionAssignmentService
 */

use App\Http\Controllers\Api\V1\ActionCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('action-center')->name('action-center.')->group(function () {

    // Dashboard: priority queue + overdue + unassigned for authenticated user
    Route::get('/dashboard', [ActionCenterController::class, 'dashboard'])
        ->name('dashboard');

    // Statistics
    Route::get('/stats', [ActionCenterController::class, 'stats'])
        ->name('stats');

    // Task list (paginated, filterable)
    Route::get('/tasks', [ActionCenterController::class, 'index'])
        ->name('tasks.index');

    // Single task
    Route::get('/tasks/{id}', [ActionCenterController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('tasks.show');

    // Assign task to a user
    Route::patch('/tasks/{id}/assign', [ActionCenterController::class, 'assign'])
        ->where('id', '[0-9]+')
        ->name('tasks.assign');

    // Update lifecycle status
    Route::patch('/tasks/{id}/status', [ActionCenterController::class, 'updateStatus'])
        ->where('id', '[0-9]+')
        ->name('tasks.status');

    // Trigger auto-assignment engine
    Route::post('/tasks/{id}/auto-assign', [ActionCenterController::class, 'autoAssign'])
        ->where('id', '[0-9]+')
        ->name('tasks.auto-assign');
});

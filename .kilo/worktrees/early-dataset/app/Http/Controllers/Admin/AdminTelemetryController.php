<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Admin Telemetry Controller — MVP (6 Core Fields)
 *
 * Simplified telemetry ingestion with a universal core schema.
 * No per-event schema validation. No forbidden-field detection.
 * Just: allowlist → validate 6 fields → log.
 *
 * Core Fields (every event):
 * 1. event       — string (required, must be in allowlist)
 * 2. trace_id    — string (auto-generated if absent)
 * 3. basarili    — bool   (success flag)
 * 4. http_durum_kodu — int (HTTP status)
 * 5. duration_ms — numeric (latency)
 * 6. context     — object (free-form, no validation on keys)
 *
 * HTTP Contract:
 * - 200: Event logged
 * - 401: Authentication required (middleware)
 * - 422: Missing event / event not in allowlist
 *
 * @version 3.0.0 (MVP)
 * @since 2026-02-15
 * @see docs/adr/2026-02-15-api-contract-freeze.md
 */
class AdminTelemetryController extends Controller
{
    /**
     * Store frontend telemetry event.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // ── Core 6 fields ──
            'event'          => 'required|string|max:100',
            'trace_id'       => 'nullable|string|max:64',
            'basarili'       => 'nullable|boolean',
            'http_durum_kodu' => 'nullable|integer',
            'duration_ms'    => 'nullable|numeric',
            'context'        => 'nullable|array',

            // ── Legacy compat (mapped to context / istek_url) ──
            'payload'   => 'nullable|array',
            'istek_url' => 'nullable|string|max:500',
            'url'       => 'nullable|string|max:500',
            'ts'        => 'nullable|integer',
        ]);

        // ── Allowlist gate ──
        $allowedEvents = config('telemetry-events.allowed_events', []);

        if (!in_array($validated['event'], $allowedEvents)) {
            Log::channel('security')->warning('telemetry_abuse_attempt', [
                'event'   => $validated['event'],
                'user_id' => auth()->id(),
                'ip'      => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Event not in allowlist',
            ], 422);
        }

        // ── Resolve fields (with legacy fallbacks) ──
        $traceId  = $validated['trace_id'] ?? (string) Str::uuid();
        $context  = $validated['context'] ?? $validated['payload'] ?? [];
        $istekUrl = $validated['istek_url'] ?? $validated['url'] ?? $request->header('Referer');

        // ── Log to dedicated telemetry channel ──
        Log::channel('telemetry')->info('frontend_event', [
            'event'          => $validated['event'],
            'trace_id'       => $traceId,
            'basarili'       => $validated['basarili'] ?? null,
            'http_durum_kodu' => $validated['http_durum_kodu'] ?? null,
            'duration_ms'    => $validated['duration_ms'] ?? null,
            'context'        => $context,
            'user_id'        => auth()->id(),
            'istek_url'      => $istekUrl,
            'ts'             => $validated['ts'] ?? now()->timestamp,
            'ip'             => $request->ip(),
        ]);

        return response()->json(['success' => true]);
    }
}

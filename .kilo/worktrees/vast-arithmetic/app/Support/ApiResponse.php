<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * API Contract Response Helper (V2 — PH-AI-TEMPLATE FREEZE)
 *
 * Success shape:
 * {
 *   "success": true,
 *   "data": { ... },
 *   "meta": { "timestamp": "...", "trace_id": "uuid-v4" },
 *   "error": null,
 *   "trace_id": "uuid-v4"
 * }
 *
 * Error shape:
 * {
 *   "success": false,
 *   "data": null,
 *   "meta": { "timestamp": "...", "trace_id": "uuid-v4" },
 *   "error": { "code": "...", "message": "...", "details": { ... } },
 *   "code": "...",
 *   "message": "...",
 *   "trace_id": "uuid-v4"
 * }
 *
 * @see contracts/ai-generate-template-v1.json
 * @see docs/adr/2026-02-15-api-contract-freeze.md
 */
class ApiResponse
{
    /**
    * Success response — { success, data, meta, error, trace_id }
     *
     * @param  mixed       $data      Response payload
     * @param  array       $meta      Extra metadata (trace_id auto-generated if absent)
     * @param  int         $httpCode  HTTP status code (default 200)
     * @param  string|null $message   Unused (kept for trait compat, not emitted in response)
     * @return JsonResponse
     */
    public static function ok($data = null, array $meta = [], int $httpCode = 200, ?string $message = null): JsonResponse
    {
        $traceId = $meta['trace_id'] ?? (string) Str::uuid();

        return response()->json([
            'success'  => true,
            'data'     => $data,
            'meta'     => array_merge([
                'timestamp' => now()->toISOString(),
                'trace_id' => $traceId,
            ], $meta),
            'error'    => null,
            'trace_id' => $traceId,
        ], $httpCode);
    }

    /**
    * Failure response — { success, data, meta, error, code, message, trace_id }
     *
     * @param  string      $code      Machine-readable error code (e.g. PIVOT_NOT_FOUND)
     * @param  string      $message   Human-readable error description
     * @param  mixed       $details   Optional debug/details (shown only in non-production)
     * @param  int         $httpCode  HTTP status code
     * @param  array       $meta      Extra metadata (trace_id auto-generated if absent)
     * @return JsonResponse
     */
    public static function fail(string $code, string $message, $details = null, int $httpCode = 400, array $meta = []): JsonResponse
    {
        $traceId = $meta['trace_id'] ?? (string) Str::uuid();
        $body = [
            'success'  => false,
            'data'     => null,
            'meta'     => array_merge([
                'timestamp' => now()->toISOString(),
                'trace_id' => $traceId,
            ], $meta),
            'error'    => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'code'     => $code,
            'message'  => $message,
            'trace_id' => $traceId,
        ];

        // Expose debug details only in non-production environments
        if ($details === null || app()->isProduction()) {
            $body['error']['details'] = null;
        }

        return response()->json($body, $httpCode);
    }
}

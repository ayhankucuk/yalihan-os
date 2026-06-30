<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;

/**
 * SABJsonContract
 * 
 * Purpose: Enforces a strict, deterministic JSON response format for all SaaS endpoints.
 */
class SABJsonContract
{
    public static function success(mixed $data = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'trace_id' => (string) str()->uuid(),
                'timestamp' => now()->toIso8601String(),
            ], $meta)
        ]);
    }

    public static function error(string $code, string $message, array $details = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => [
                'trace_id' => (string) str()->uuid(),
                'timestamp' => now()->toIso8601String(),
            ],
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ]
        ], $status);
    }
}

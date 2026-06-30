<?php

namespace App\Http\Controllers\Api\Concerns;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Phase 36: API Contract Trait
 *
 * Provides $this->ok() and $this->fail() methods.
 */
trait ApiResponds
{
    /**
     * Return a standardized success response.
     *
     * @param  mixed       $data
     * @param  array       $meta
     * @param  int         $httpCode
     * @param  string|null $message
     * @return JsonResponse
     */
    protected function ok($data = null, array $meta = [], int $httpCode = 200, ?string $message = null): JsonResponse
    {
        return ApiResponse::ok($data, $meta, $httpCode, $message);
    }

    /**
     * Return a standardized fail response.
     *
     * @param  string  $code
     * @param  string  $message
     * @param  mixed   $details
     * @param  int     $httpCode
     * @param  array   $meta
     * @return JsonResponse
     */
    protected function fail(string $code, string $message, $details = null, int $httpCode = 400, array $meta = []): JsonResponse
    {
        return ApiResponse::fail($code, $message, $details, $httpCode, $meta);
    }
}

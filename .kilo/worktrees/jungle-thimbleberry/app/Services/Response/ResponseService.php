<?php

namespace App\Services\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Standardized Response Service
 *
 * Phase 36: API Contract Standardization
 * ALL responses MUST emit: { success, data, meta, error }
 * meta.timestamp is REQUIRED.
 * success=true => error=null
 * success=false => data=null
 */
class ResponseService
{
    /**
     * Standard success response for API
     *
     * Contract: { success:true, data:..., meta:{timestamp,...}, error:null }
     *
     * @param  mixed  $data  Response data
     * @param  string  $message  Success message
     * @param  int  $yanitKodu  HTTP durum kodu
     */
    public static function success($data = null, string $message = 'İşlem başarılı', int $yanitKodu = 200, array $meta = []): JsonResponse
    {
        $payloadData = $data;
        $responseMeta = $meta;

        if ($data instanceof LengthAwarePaginator) {
            $payloadData = $data->items();
            $responseMeta = array_merge($responseMeta, [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'pagination' => true,
            ]);
        } elseif ($data instanceof Paginator) {
            $payloadData = $data->items();
            $responseMeta = array_merge($responseMeta, [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'pagination' => true,
            ]);
        }

        // Phase 36 Contract: Always include timestamp and trace_id in meta
        $responseMeta = array_merge([
            'timestamp' => now()->toISOString(),
            'trace_id' => (string) \Illuminate\Support\Str::uuid(), // Default trace
        ], $responseMeta);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $payloadData,
            'meta'    => $responseMeta,
            'error'   => null,
        ], $yanitKodu);
    }

    /**
     * Standard error response for API
     *
     * Contract: { success:false, data:null, meta:{timestamp,...}, error:{code,message,details?} }
     *
     * @param  string  $message  Error message
     * @param  int  $yanitKodu  HTTP durum kodu
     * @param  array  $errors  Additional error details
     * @param  string|null  $code  Error code
     */
    public static function error(
        string $message = 'Bir hata oluştu',
        int $yanitKodu = 400,
        array $errors = [],
        ?string $code = null,
        $data = null,
        array $meta = []
    ): JsonResponse {
        // Log error in production or if it's a server error
        if (config('app.env') === 'production' || $yanitKodu >= 500) {
            self::logError($message, $yanitKodu, $errors, $code);
        }

        $responseMeta = array_merge([
            'timestamp' => now()->toISOString(),
            'trace_id' => (string) \Illuminate\Support\Str::uuid(), // Default trace
        ], $meta);

        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'meta'    => $responseMeta,
            'error'   => [
                'code'    => $code ?? 'UNKNOWN_ERROR',
                'message' => $message,
                'details' => ! empty($errors) ? $errors : null,
            ],
        ], $yanitKodu);
    }

    /**
     * Standard validation error response
     *
     * @param  array  $errors  Validation errors
     * @param  string  $message  Error message
     */
    public static function validationError(array $errors, string $message = 'Validasyon hatası'): JsonResponse
    {
        return self::error($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Standard not found response
     *
     * @param  string  $message  Error message
     */
    public static function notFound(string $message = 'Kayıt bulunamadı'): JsonResponse
    {
        return self::error($message, 404, [], 'NOT_FOUND');
    }

    /**
     * Standard unauthorized response
     *
     * @param  string  $message  Error message
     */
    public static function unauthorized(string $message = 'Yetkisiz erişim'): JsonResponse
    {
        return self::error($message, 401, [], 'UNAUTHORIZED');
    }

    /**
     * Standard forbidden response
     *
     * @param  string  $message  Error message
     */
    public static function forbidden(string $message = 'Bu işlem için yetkiniz yok'): JsonResponse
    {
        return self::error($message, 403, [], 'FORBIDDEN');
    }

    /**
     * Standard server error response
     *
     * @param  string  $message  Error message
     * @param  \Throwable|null  $exception  Exception for logging
     */
    public static function serverError(string $message = 'Sunucu hatası', ?\Throwable $exception = null): JsonResponse
    {
        if ($exception) {
            self::logException($exception);
        }

        return self::error($message, 500, [], 'SERVER_ERROR');
    }

    /**
     * Standard rate limit response
     *
     * @param  string  $message  Error message
     * @param  int  $retryAfter  Seconds to retry
     */
    public static function rateLimitExceeded(string $message = 'Çok fazla istek', int $retryAfter = 60): JsonResponse
    {
        $response = self::error($message, 429, [], 'RATE_LIMIT_EXCEEDED');
        $response->header('Retry-After', $retryAfter);

        return $response;
    }

    /**
     * Web redirect response with success message
     *
     * @param  string  $route  Route name
     * @param  string  $message  Success message
     */
    public static function redirectSuccess(string $route, string $message = 'İşlem başarılı'): RedirectResponse
    {
        return redirect()->route($route)->with('success', $message);
    }

    /**
     * Web redirect response with error message
     *
     * @param  string  $route  Route name
     * @param  string  $message  Error message
     */
    public static function redirectError(string $route, string $message = 'Bir hata oluştu'): RedirectResponse
    {
        return redirect()->route($route)->with('error', $message);
    }

    /**
     * Web back redirect with success message
     *
     * @param  string  $message  Success message
     */
    public static function backSuccess(string $message = 'İşlem başarılı'): RedirectResponse
    {
        return redirect()->back()->with('success', $message);
    }

    /**
     * Web back redirect with error message
     *
     * @param  string  $message  Error message
     */
    public static function backError(string $message = 'Bir hata oluştu'): RedirectResponse
    {
        return redirect()->back()->with('error', $message);
    }

    /**
     * Log error with context
     */
    protected static function logError(string $message, int $httpCode, array $errors, ?string $code): void
    {
        Log::error('API Error Response', [
            'message' => $message,
            'http_code' => $httpCode,
            'code' => $code,
            'errors' => $errors,
            'url' => Request::url(),
            'method' => Request::method(),
            'user_id' => auth()->id(),
            'ip' => Request::ip(),
        ]);
    }

    /**
     * Log exception with context
     */
    protected static function logException(\Throwable $exception): void
    {
        Log::error('Server Error', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => Request::url(),
            'method' => Request::method(),
            'user_id' => auth()->id(),
            'ip' => Request::ip(),
            'input' => Request::except(['password', 'password_confirmation']),
        ]);
    }
}

<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Standardized Logging Service
 *
 * Context7 Logging Standardization
 * Provides consistent logging format with context
 */
class LogService
{
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';

    const LEVEL_INFO = 'info';

    const LEVEL_WARNING = 'warning';

    const LEVEL_ERROR = 'error';

    const LEVEL_CRITICAL = 'critical';

    /**
     * Log channels
     */
    const CHANNEL_DEFAULT = 'stack';

    const CHANNEL_API = 'api';

    const CHANNEL_DATABASE = 'database';

    const CHANNEL_AUTH = 'auth';

    const CHANNEL_PAYMENT = 'payment';

    const CHANNEL_AI = 'ai';

    /**
     * Log info message with context
     */
    public static function info(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(self::LEVEL_INFO, $message, $context, $channel);
    }

    /**
     * Log error message with context
     */
    public static function error(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
        ?string $channel = null
    ): void {
        if ($exception) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => (function_exists('config') && app()->bound('config') && config('app.debug')) ? $exception->getTraceAsString() : null,
            ];
        }

        self::log(self::LEVEL_ERROR, $message, $context, $channel);
    }

    /**
     * Log warning message with context
     */
    public static function warning(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(self::LEVEL_WARNING, $message, $context, $channel);
    }

    /**
     * Log debug message with context
     */
    public static function debug(string $message, array $context = [], ?string $channel = null): void
    {
        if (function_exists('config') && app()->bound('config') && ! config('app.debug')) {
            return; // Don't log debug in production
        }

        self::log(self::LEVEL_DEBUG, $message, $context, $channel);
    }

    /**
     * Log critical message with context
     */
    public static function critical(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
        ?string $channel = null
    ): void {
        if ($exception) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => (function_exists('config') && app()->bound('config') && config('app.debug')) ? $exception->getTraceAsString() : null,
            ];
        }

        self::log(self::LEVEL_CRITICAL, $message, $context, $channel);
    }

    /**
     * Log API request
     */
    public static function api(
        string $endpoint,
        array $requestData = [],
        ?array $responseData = null,
        ?float $duration = null
    ): void {
        $context = [
            'endpoint' => $endpoint,
            'method' => Request::method(),
            'request' => $requestData,
            'user_id' => Auth::id(),
            'ip' => Request::ip(),
        ];

        if ($responseData !== null) {
            $context['response'] = $responseData;
        }

        if ($duration !== null) {
            $context['duration_ms'] = round($duration * 1000, 2);
        }

        self::log(self::LEVEL_INFO, "API Request: {$endpoint}", $context, self::CHANNEL_API);
    }

    /**
     * Log database operation
     */
    public static function database(
        string $operation,
        string $table,
        array $data = [],
        ?int $affectedRows = null
    ): void {
        $context = [
            'operation' => $operation,
            'table' => $table,
            'user_id' => Auth::id(),
        ];

        if (! empty($data)) {
            $context['data'] = $data;
        }

        if ($affectedRows !== null) {
            $context['affected_rows'] = $affectedRows;
        }

        self::log(self::LEVEL_INFO, "Database {$operation}: {$table}", $context, self::CHANNEL_DATABASE);
    }

    /**
     * Log authentication event
     */
    public static function auth(string $event, ?int $userId = null, array $context = []): void
    {
        $context['event'] = $event;
        $context['user_id'] = $userId ?? Auth::id();
        $context['ip'] = Request::ip();
        $context['user_agent'] = Request::userAgent();

        self::log(self::LEVEL_INFO, "Auth: {$event}", $context, self::CHANNEL_AUTH);
    }

    /**
     * Log AI operation
     */
    public static function ai(
        string $operation,
        string $provider,
        array $context = [],
        ?float $duration = null
    ): void {
        $context['operation'] = $operation;
        $context['provider'] = $provider;
        $context['user_id'] = Auth::id();

        if ($duration !== null) {
            $context['duration_ms'] = round($duration * 1000, 2);
        }

        self::log(self::LEVEL_INFO, "AI {$operation} ({$provider})", $context, self::CHANNEL_AI);
    }

    /**
     * Log Cortex decision (Context7 compatible)
     */
    public static function logCortexDecision(string $decision, array $context = [], ?float $duration = null, bool $success = true): void
    {
        $context['decision'] = $decision;
        $context['success'] = $success;
        if ($duration) {
            $context['duration_ms'] = $duration;
        }
        self::log(self::LEVEL_INFO, "Cortex Decision: {$decision}", $context, self::CHANNEL_AI);
    }

    /**
     * Core logging method
     */
    protected static function log(
        string $level,
        string $message,
        array $context = [],
        ?string $channel = null
    ): void {
        // Add automatic context
        $hasApp = function_exists('app');
        $context = array_merge($context, [
            'timestamp' => ($hasApp && app()->bound('config')) ? now()->toISOString() : date('c'),
            'url' => ($hasApp && app()->bound('request')) ? Request::url() : null,
            'method' => ($hasApp && app()->bound('request')) ? Request::method() : 'CLI',
            'user_id' => ($hasApp && app()->bound('auth')) ? Auth::id() : null,
            'ip' => ($hasApp && app()->bound('request')) ? Request::ip() : null,
        ]);

        // Unit tests may run without a fully booted Laravel container.
        // Skip logging safely when core bindings are unavailable.
        if (! $hasApp || ! app()->bound('log') || ! app()->bound('config')) {
            return;
        }

        // Log to specific channel or default
        $logger = $channel ? Log::channel($channel) : Log::channel(self::CHANNEL_DEFAULT);

        $logger->{$level}($message, $context);
    }

    /**
     * Create structured log entry
     *
     * @param  mixed  $resourceId
     */
    public static function action(
        string $action,
        string $resource,
        $resourceId = null,
        array $context = [],
        string $level = self::LEVEL_INFO
    ): void {
        $message = "{$action}: {$resource}";

        if ($resourceId !== null) {
            $message .= " (ID: {$resourceId})";
        }

        $context['action'] = $action;
        $context['resource'] = $resource;
        $context['resource_id'] = $resourceId;

        self::log($level, $message, $context);
    }

    /**
     * Start timer for performance tracking
     * Context7: MCP uyumluluğu için milisaniye bazında ölçüm
     *
     * @param string $operation Operation name
     * @return float Start time in microseconds
     */
    public static function startTimer(string $operation): float
    {
        return microtime(true);
    }

    /**
     * Stop timer, return duration and optionally log it.
     * Context7: MCP uyumluluğu için milisaniye bazında ölçüm
     *
     * @param float $startTime Start time from startTimer()
     * @param string|array|null $logContext If provided, logs the duration with this context or as a message
     * @return float Duration in milliseconds
     */
    public static function stopTimer(float $startTime, $logContext = null): float
    {
        $duration = microtime(true) - $startTime;
        $durationMs = round($duration * 1000, 2);

        if ($logContext !== null) {
            if (is_array($logContext)) {
                $logContext['duration_ms'] = $durationMs;
                self::info('Operation completed', $logContext);
            } else {
                self::info((string) $logContext, ['duration_ms' => $durationMs]);
            }
        }

        return $durationMs;
    }
}

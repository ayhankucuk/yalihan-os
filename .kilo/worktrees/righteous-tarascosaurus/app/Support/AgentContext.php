<?php

namespace App\Support;

/**
 * AgentContext — Runtime agent request detection singleton.
 *
 * Set by OpenClaw middleware when an authenticated agent request is detected.
 * Queried by service-layer guards to enforce write isolation.
 *
 * Usage:
 *   AgentContext::activate($scope, $correlationId);  // set by middleware
 *   AgentContext::isAgent();                          // check in services
 *   AgentContext::scope();                            // get claimed scope
 */
class AgentContext
{
    private static bool $active = false;

    private static ?string $scope = null;

    private static ?string $correlationId = null;

    private static ?string $tokenHash = null;

    /**
     * Activate agent context (called by OpenClaw middleware after auth).
     */
    public static function activate(string $scope, ?string $correlationId = null, ?string $tokenHash = null): void
    {
        self::$active = true;
        self::$scope = $scope;
        self::$correlationId = $correlationId;
        self::$tokenHash = $tokenHash;
    }

    /**
     * Is the current request from an authenticated agent?
     */
    public static function isAgent(): bool
    {
        return self::$active;
    }

    /**
     * Get the claimed scope (e.g. 'agent.read.context').
     */
    public static function scope(): ?string
    {
        return self::$scope;
    }

    /**
     * Get the correlation ID for tracing.
     */
    public static function correlationId(): ?string
    {
        return self::$correlationId;
    }

    /**
     * Get the hashed token identifier.
     */
    public static function tokenHash(): ?string
    {
        return self::$tokenHash;
    }

    /**
     * Reset context (for testing and request lifecycle cleanup).
     */
    public static function reset(): void
    {
        self::$active = false;
        self::$scope = null;
        self::$correlationId = null;
        self::$tokenHash = null;
    }
}

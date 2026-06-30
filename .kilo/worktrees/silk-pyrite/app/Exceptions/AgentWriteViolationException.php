<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when an agent request attempts a forbidden write operation.
 *
 * This is a domain-level guard — independent of middleware.
 * Even if middleware is bypassed, service-layer guards throw this.
 */
class AgentWriteViolationException extends Exception
{
    protected string $service;
    protected string $method;
    protected ?string $scope;
    protected ?string $correlationId;

    public function __construct(
        string $service,
        string $method,
        ?string $scope = null,
        ?string $correlationId = null,
    ) {
        $this->service = $service;
        $this->method = $method;
        $this->scope = $scope;
        $this->correlationId = $correlationId;

        parent::__construct(
            "Agent write violation: {$service}::{$method}() is forbidden for agent requests. "
            . "Scope: {$scope}, Correlation: {$correlationId}",
            403
        );
    }

    public function context(): array
    {
        return [
            'service' => $this->service,
            'method' => $this->method,
            'scope' => $this->scope,
            'correlation_id' => $this->correlationId,
        ];
    }
}

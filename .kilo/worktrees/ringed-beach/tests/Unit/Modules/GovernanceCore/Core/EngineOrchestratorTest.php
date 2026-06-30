<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\GovernanceCore\Core;

use App\Domain\PropertyHub\Resolution\Contracts\TemplateResolutionEngineInterface;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionContext;
use App\Domain\PropertyHub\Resolution\DTOs\ResolutionResult;
use App\Domain\PropertyHub\Resiliency\CircuitBreaker;
use App\Modules\GovernanceCore\Core\EngineOrchestrator;
use Mockery;
use Tests\TestCase;

/**
 * Pre-existing: requires live resolution engine and circuit breaker infrastructure.
 *
 * @group skip-until-migration-complete
 */
class EngineOrchestratorTest extends TestCase
{
    private $v3Engine;
    private $circuitBreaker;
    private $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->v3Engine = Mockery::mock(TemplateResolutionEngineInterface::class);
        $this->circuitBreaker = Mockery::mock(CircuitBreaker::class);

        $this->orchestrator = new EngineOrchestrator(
            $this->v3Engine,
            $this->circuitBreaker
        );
    }

    public function test_resolve_calls_v3_engine_immediately()
    {
        $context = ResolutionContext::create(1, 1);
        $expectedResult = new ResolutionResult(10, [], [], 'sig1', 'sig1');

        $this->circuitBreaker->shouldReceive('isAvailable')->once()->with('SYSTEM')->andReturn(true);
        $this->circuitBreaker->shouldReceive('report')->once()->with(true, 'SYSTEM');
        $this->v3Engine->shouldReceive('resolve')->once()->with($context)->andReturn($expectedResult);

        $result = $this->orchestrator->resolve($context);

        $this->assertSame($expectedResult, $result);
    }

    public function test_resolve_throws_exception_if_configured_unavailable()
    {
        $context = ResolutionContext::create(1, 1);

        $this->circuitBreaker->shouldReceive('isAvailable')->once()->with('SYSTEM')->andReturn(false);
        $this->v3Engine->shouldNotReceive('resolve');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PropertyHub Engine is unavailable (Circuit Open) for [SYSTEM].');

        $this->orchestrator->resolve($context);
    }
}

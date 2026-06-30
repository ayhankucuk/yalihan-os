<?php

declare(strict_types=1);

namespace Tests\Feature\Resilience;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ChaosEngineeringTest extends TestCase
{
    /**
     * Test AI Timeout simulation opens Circuit Breaker.
     */
    public function test_ai_timeout_circuit_breaker_flow(): void
    {
        $this->artisan('chaos:simulate', ['scenario' => 'ai_timeout'])
            ->expectsOutputToContain('SUCCESS: Circuit Breaker successfully opened')
            ->assertExitCode(0);
    }

    /**
     * Test Database failure simulation rolls back transactions.
     */
    public function test_db_failure_transaction_rollback(): void
    {
        $this->artisan('chaos:simulate', ['scenario' => 'db_failure'])
            ->expectsOutputToContain('SUCCESS: Transaction rolled back successfully')
            ->assertExitCode(0);
    }

    /**
     * Test File error simulation captures write errors.
     */
    public function test_file_error_handling(): void
    {
        $this->artisan('chaos:simulate', ['scenario' => 'file_error'])
            ->expectsOutputToContain('SUCCESS: System correctly caught the write failure')
            ->assertExitCode(0);
    }
}

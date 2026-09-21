<?php

namespace Tests\Feature\Queue;

use App\Jobs\Hermes\AsyncHandlerDispatchJob;
use PHPUnit\Framework\TestCase;

/**
 * Hermes Queue Consumer Contract — BOUNDED REGRESSION GUARD
 *
 * Scope: docker-compose.production.yml + app/Jobs/Hermes/AsyncHandlerDispatchJob.php
 *
 * Enforces the contract established for the Hermes async queue after
 * finding HERMES-QUEUE-NOT-CONSUMED-IN-PRODUCTION (REMEDIATION_02):
 *
 *   1. Production worker consumes: default, notifications, concierge, hermes
 *   2. Established queues (default, notifications, concierge) were not removed
 *   3. Hermes producer (AsyncHandlerDispatchJob) still targets queue 'hermes'
 *   4. Production worker timeout remains 60 seconds
 *   5. No dedicated Hermes worker service was accidentally introduced
 *
 * This test intentionally reads only static text (compose YAML + job source).
 * It does NOT boot the full Laravel container — no DB, no Redis, no queue
 * drivers. It is safe to run in any CI environment.
 */
class HermesQueueConsumerContractTest extends TestCase
{
    private string $composePath;
    private string $jobPath;

    protected function setUp(): void
    {
        parent::setUp();
        // From /tests/Feature/Queue → go up 3 levels to project root
        $root = dirname(__DIR__, 3);
        $this->composePath = $root . '/docker-compose.production.yml';
        $this->jobPath     = $root . '/app/Jobs/Hermes/AsyncHandlerDispatchJob.php';
    }

    /**
     * Extract the --queue=<csv> list from the production queue worker service.
     *
     * @return array<string>
     */
    private function getWorkerQueues(): array
    {
        $this->assertFileExists($this->composePath, 'docker-compose.production.yml not found');
        $yaml = file_get_contents($this->composePath);

        if (!preg_match('/"--queue=([^"]+)"/', $yaml, $m)) {
            $this->fail('Could not extract --queue value from docker-compose.production.yml');
        }

        return array_values(array_filter(array_map('trim', explode(',', $m[1]))));
    }

    /**
     * Extract the --timeout=<int> value from the production queue worker service.
     */
    private function getWorkerTimeout(): int
    {
        $yaml = file_get_contents($this->composePath);
        if (!preg_match('/"--timeout=(\d+)"/', $yaml, $m)) {
            $this->fail('Could not extract --timeout value from docker-compose.production.yml');
        }
        return (int) $m[1];
    }

    /**
     * Contract #1 & #2:
     *   Production worker consumes 'hermes' AND still consumes the
     *   previously established queues (default, notifications, concierge)
     *   with the pre-existing order preserved.
     */
    public function test_production_worker_consumes_hermes_alongside_established_queues(): void
    {
        $workerQueues = $this->getWorkerQueues();

        $this->assertContains('default', $workerQueues,
            "Established queue 'default' was removed from production worker");
        $this->assertContains('notifications', $workerQueues,
            "Established queue 'notifications' was removed from production worker");
        $this->assertContains('concierge', $workerQueues,
            "Established queue 'concierge' was removed from production worker");
        $this->assertContains('hermes', $workerQueues,
            "Hermes queue is not consumed by the production worker — "
            . "AsyncHandlerDispatchJob dispatches to 'hermes' but nothing consumes it.");

        // Order preservation: hermes must appear AFTER the pre-existing queues
        $defaultIdx       = array_search('default', $workerQueues, true);
        $notificationsIdx = array_search('notifications', $workerQueues, true);
        $conciergeIdx     = array_search('concierge', $workerQueues, true);
        $hermesIdx        = array_search('hermes', $workerQueues, true);

        $this->assertLessThan($notificationsIdx, $defaultIdx,
            "Queue order changed: 'default' must precede 'notifications'");
        $this->assertLessThan($conciergeIdx, $notificationsIdx,
            "Queue order changed: 'notifications' must precede 'concierge'");
        $this->assertLessThan($hermesIdx, $conciergeIdx,
            "Queue order changed: 'concierge' must precede 'hermes'");
    }

    /**
     * Contract #3:
     *   AsyncHandlerDispatchJob (the Hermes producer) still targets queue 'hermes'.
     *   Verified by inspecting the queue() method source.
     */
    public function test_hermes_producer_targets_hermes_queue(): void
    {
        $this->assertFileExists($this->jobPath, 'AsyncHandlerDispatchJob.php not found');
        $src = file_get_contents($this->jobPath);

        $this->assertMatchesRegularExpression(
            "/public function queue\(\)\s*:\s*string\s*\{\s*return\s+'hermes'\s*;/",
            $src,
            "AsyncHandlerDispatchJob no longer targets queue 'hermes'. "
            . "Either the queue name was renamed (violates task bounds) or the "
            . "queue() method contract changed."
        );

        $this->assertTrue(class_exists(AsyncHandlerDispatchJob::class));
        $reflection = new \ReflectionClass(AsyncHandlerDispatchJob::class);
        $this->assertTrue(
            $reflection->hasMethod('queue'),
            'AsyncHandlerDispatchJob::queue() method was removed'
        );
    }

    /**
     * Contract #4:
     *   Production worker --timeout remains 60 seconds.
     *   Enforces that the timeout was NOT silently increased to accommodate a
     *   long-running async handler (see WORKER_TIMEOUT_CONTRACT_MISMATCH gate).
     */
    public function test_production_worker_timeout_remains_60_seconds(): void
    {
        $this->assertSame(
            60,
            $this->getWorkerTimeout(),
            'Production worker --timeout has changed. Any change requires an '
            . 'explicit async-handler timeout compatibility audit.'
        );
    }

    /**
     * Contract #5:
     *   No dedicated Hermes worker service was introduced. The Hermes queue is
     *   consumed by the single existing worker (yalihanai-queue-v2), not by a
     *   separate service.
     */
    public function test_no_dedicated_hermes_worker_service_exists(): void
    {
        $yaml = file_get_contents($this->composePath);

        // Count queue:work service commands (not healthcheck greps). There must be exactly one.
        preg_match_all('/command:.*queue:work/', $yaml, $matches);
        $this->assertCount(
            1,
            $matches[0],
            'More than one queue:work worker command is defined in docker-compose.production.yml. '
            . 'The remediation contract forbids introducing a dedicated Hermes worker.'
        );

        // Explicitly assert no service name suggests a dedicated Hermes worker.
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*(yalihanai-hermes|hermes-worker|hermes-queue)/m',
            $yaml,
            'A service resembling a dedicated Hermes worker was introduced. '
            . 'Task bounds forbid this — the shared worker must consume the hermes queue.'
        );

        // The single existing worker service name must still be present.
        $this->assertStringContainsString(
            'yalihanai-queue-v2',
            $yaml,
            "The established queue worker service 'yalihanai-queue-v2' is missing."
        );
    }
}

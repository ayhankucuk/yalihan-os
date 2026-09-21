<?php

namespace Tests\Feature\Queue;

use PHPUnit\Framework\TestCase;

/**
 * Queue Routing Regression Guard — BOUNDED FIX
 *
 * Detects when application code declares explicit onQueue() targets
 * that are NOT consumed by the production docker-compose worker.
 *
 * Scope: docker-compose.production.yml + app/Jobs/*.php
 * Bounded invariant: only the queues confirmed PRODUCTION_VERIFIED
 * in the routing remediation task are checked.
 *
 * Limitation: Does NOT detect future arbitrary queue declarations
 * (e.g. onQueue('foo')) — those require a separate task to activate.
 * This guard enforces that known production queue gaps stay closed.
 */
class QueueRoutingRegressionTest extends TestCase
{
    private static bool $booted = false;
    private string $composePath;
    private string $jobsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootMinimalApp();
        // From /tests/Feature/Queue → go up 3 levels to project root
        $root = dirname(__DIR__, 3);
        $this->composePath = $root . '/docker-compose.production.yml';
        $this->jobsPath    = $root . '/app/Jobs';
    }

    /**
     * Bootstrap only the app container needed for config() resolution.
     * Skips full TestCase DB bootstrap for speed.
     */
    private function bootMinimalApp(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        $app = require dirname(__DIR__, 3) . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    }

    /**
     * Returns the queue names the docker-compose worker listens to.
     *
     * @return array<string>
     */
    private function getWorkerQueues(): array
    {
        $yaml = file_get_contents($this->composePath);
        $this->assertFileExists($this->composePath, 'docker-compose.production.yml not found');

        if (!preg_match('/"--queue=([^"]+)"/', $yaml, $m)) {
            $this->fail('Could not extract --queue value from docker-compose.production.yml');
        }

        return array_filter(array_map('trim', explode(',', $m[1])));
    }

    /**
     * Returns every explicit onQueue() target declared in Jobs.
     * Files without onQueue() use the 'default' queue implicitly.
     *
     * @return array<string>
     */
    private function getApplicationQueues(): array
    {
        $queues = ['default' => true]; // implicit default for queue-less jobs
        // Only scan actual job files under app/Jobs/ (not sub-package dirs like Queue/Contracts/)
        $files  = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->jobsPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (!str_contains($content, 'ShouldQueue')) {
                continue;
            }
            if (preg_match_all("/\\\$this->onQueue\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $m)) {
                foreach ($m[1] as $queue) {
                    if (is_string($queue) && $queue !== '') {
                        $queues[$queue] = true;
                    }
                }
            }

            // Also detect config()-based queue declarations
            // (e.g. config('copilot-pipeline.queues.verification'))
            if (preg_match_all("/config\s*\(\s*['\"]([^'\"]+\.queues\.[^'\"]+)['\"]\s*\)/", $content, $mc)) {
                foreach ($mc[1] as $key) {
                    $val = config($key);
                    if (is_string($val) && $val !== '') {
                        $queues[$val] = true;
                    }
                }
            }
        }

        return array_keys($queues);
    }

    public function test_production_worker_consumes_all_production_verified_queues(): void
    {
        $workerQueues = $this->getWorkerQueues();

        // BOUNDED: Only check the queues PRODUCTION_VERIFIED as active blackout gaps.
        // Do NOT fail on bc001-*, copilot-*, projections, etc. — those are separate tasks.
        $productionVerifiedGaps = ['notifications', 'concierge', 'hermes'];

        $missing = array_diff($productionVerifiedGaps, $workerQueues);

        $this->assertEmpty(
            $missing,
            'Production worker is NOT consuming confirmed production queues: ' . implode(', ', $missing)
        );
    }

    public function test_worker_queue_flag_is_valid_yaml(): void
    {
        $workerQueues = $this->getWorkerQueues();

        $this->assertNotEmpty($workerQueues, 'Worker queue list is empty');
        foreach ($workerQueues as $q) {
            $this->assertMatchesRegularExpression(
                '/^[a-z][a-z0-9_-]*$/',
                $q,
                "Invalid queue name in worker --queue flag: '$q'"
            );
        }
    }

    public function test_known_explicit_job_queues_are_represented(): void
    {
        $workerQueues = $this->getWorkerQueues();
        $appQueues    = $this->getApplicationQueues();

        $unconsumed = [];
        foreach ($appQueues as $queue) {
            // Skip queues without PRODUCTION_VERIFIED activation evidence.
            // These queue names are declared in code but have no production consumer.
            // Each requires a separate activation task (NEW_IDEA protocol).
            $knownUnconsumed = [
                // BC001 bootstrap — requires separate worker/activation decision
                'bc001-workspace', 'bc001-knowledge', 'bc001-ai', 'bc001-publishing',
                // Copilot pipeline — internal CI/CD, not production operational
                'copilot-verification', 'copilot-governance', 'copilot-high', 'copilot-default',
                // CQRS / Intelligence — separate projection pipeline
                'projections', 'ranking', 'cortex-notifications',
                // Hermes internal event routing — 'events' queue not yet consumed
                'events',
                // HIGH / REPORTS — priority & async reporting workloads
                // Not in current production worker topology; separate activation task
                'high', 'reports',
            ];
            if (!in_array($queue, $knownUnconsumed, true) && !in_array($queue, $workerQueues, true)) {
                $unconsumed[] = $queue;
            }
        }

        $this->assertEmpty(
            $unconsumed,
            'Application declares queues with no production consumer: ' . implode(', ', $unconsumed)
        );
    }
}

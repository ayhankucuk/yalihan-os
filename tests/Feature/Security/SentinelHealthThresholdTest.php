<?php

namespace Tests\Feature\Security;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * SENTINEL_HEALTH_THRESHOLD_REMEDIATION_36
 *
 * Verifies SentinelConsoleCommand health threshold contract:
 * - healthScore < 70 → Command::FAILURE
 * - healthScore >= 70 → health threshold does not cause failure
 *
 * @group sentinel
 * @group security
 */
class SentinelHealthThresholdTest extends TestCase
{
    /**
     * Register a fake sab:integrity-scan command.
     * Accepts --diff flag as SentinelConsoleCommand passes it.
     */
    private function registerFakeSabIntegrity(bool $pass): void
    {
        $fakeCommand = new class($pass) extends Command {
            private bool $pass;
            public function __construct(bool $pass) { $this->pass = $pass; parent::__construct(); }
            public function handle(): int
            {
                $this->output->write($this->pass ? '' : 'FAIL');
                return $this->pass ? 0 : 1;
            }
        };
        $fakeCommand->setName('sab:integrity-scan');
        $fakeCommand->getDefinition()->addOption(
            new \Symfony\Component\Console\Input\InputOption('diff', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE)
        );

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($fakeCommand);
    }

    /**
     * Register a fake bekci:health command returning a specific score.
     */
    private function registerFakeBekciHealth(float $score): void
    {
        $fakeCommand = new class($score) extends Command {
            private float $score;
            public function __construct(float $score) { $this->score = $score; parent::__construct(); }
            public function handle(): int
            {
                $this->output->writeln("Overall System Health: {$this->score}%");
                return 0;
            }
        };
        $fakeCommand->setName('bekci:health');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($fakeCommand);
    }

    /**
     * Register a fake cache:clear command.
     */
    private function registerFakeCacheClear(): void
    {
        $fakeCommand = new class extends Command {
            public function handle(): int
            {
                return 0;
            }
        };
        $fakeCommand->setName('cache:clear');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($fakeCommand);
    }

    /**
     * Run sentinel:run and return exit code + output.
     */
    private function runSentinel(): array
    {
        $output = new BufferedOutput();
        $exitCode = Artisan::call('sentinel:run', ['--skip-tests' => true], $output);
        return [$exitCode, $output->fetch()];
    }

    /**
     * healthScore = 69 → Sentinel MUST return FAILURE.
     */
    public function test_health_69_returns_failure(): void
    {
        $this->registerFakeSabIntegrity(true);
        $this->registerFakeBekciHealth(69.0);
        $this->registerFakeCacheClear();

        [$exitCode, $output] = $this->runSentinel();

        $this->assertEquals(
            \Illuminate\Console\Command::FAILURE,
            $exitCode,
            'healthScore=69 must produce Command::FAILURE'
        );
    }

    /**
     * healthScore = 70 → Sentinel SUCCESS (threshold itself does not cause failure).
     */
    public function test_health_70_returns_success(): void
    {
        $this->registerFakeSabIntegrity(true);
        $this->registerFakeBekciHealth(70.0);
        $this->registerFakeCacheClear();

        [$exitCode, $output] = $this->runSentinel();

        $this->assertEquals(
            \Illuminate\Console\Command::SUCCESS,
            $exitCode,
            'healthScore=70 must NOT fail due to health threshold'
        );
    }

    /**
     * healthScore = 71 → Sentinel SUCCESS (well above threshold).
     */
    public function test_health_71_returns_success(): void
    {
        $this->registerFakeSabIntegrity(true);
        $this->registerFakeBekciHealth(71.0);
        $this->registerFakeCacheClear();

        [$exitCode, $output] = $this->runSentinel();

        $this->assertEquals(
            \Illuminate\Console\Command::SUCCESS,
            $exitCode,
            'healthScore=71 must NOT fail due to health threshold'
        );
    }

    /**
     * SAB integrity failure → Sentinel MUST return FAILURE regardless of health score.
     * Regression: preserves existing independent failure behavior.
     */
    public function test_sab_integrity_failure_returns_failure_regardless_of_health(): void
    {
        $this->registerFakeSabIntegrity(false);
        $this->registerFakeBekciHealth(100.0);
        $this->registerFakeCacheClear();

        [$exitCode, $output] = $this->runSentinel();

        $this->assertEquals(
            \Illuminate\Console\Command::FAILURE,
            $exitCode,
            'SAB integrity failure must produce Command::FAILURE even with perfect health'
        );
    }

    /**
     * Error output MUST be present when health < 70.
     */
    public function test_health_below_threshold_produces_error_output(): void
    {
        $this->registerFakeSabIntegrity(true);
        $this->registerFakeBekciHealth(55.0);
        $this->registerFakeCacheClear();

        [$exitCode, $output] = $this->runSentinel();

        $this->assertStringContainsString('55', $output);
        $this->assertTrue(
            str_contains($output, '✗') || str_contains($output, 'error') || str_contains($output, 'düşük'),
            'Error indicator must appear for health < 70'
        );
    }
}

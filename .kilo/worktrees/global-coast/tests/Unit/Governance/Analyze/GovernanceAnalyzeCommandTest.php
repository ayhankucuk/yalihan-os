<?php

declare(strict_types=1);

namespace Tests\Unit\Governance\Analyze;

use App\Console\Commands\Governance\GovernanceAnalyzeCommand;
use App\Support\Governance\Analyze\AnalysisContext;
use App\Support\Governance\Analyze\Contracts\Detector;
use App\Support\Governance\Analyze\Enums\RiskLevel;
use RuntimeException;
use Tests\Unit\Governance\Analyze\Support\AnalyzeTestFactory;
use Tests\TestCase;

class GovernanceAnalyzeCommandTest extends TestCase
{
    public function test_returns_success_when_findings_exist(): void
    {
        $command = $this->makeCommand([
            'format' => 'json',
            'output' => $this->tmpFile('command-success.json'),
        ]);

        $exitCode = $command->handle([
            AnalyzeTestFactory::detector('routes', RiskLevel::HIGH),
        ]);

        $this->assertSame(0, $exitCode);
    }

    public function test_returns_failure_on_internal_exception(): void
    {
        $command = $this->makeCommand([
            'format' => 'json',
            'output' => $this->tmpFile('command-fail.json'),
        ]);

        $failingDetector = AnalyzeTestFactory::detector(
            slug: 'explode',
            risk: RiskLevel::HIGH,
            onDetect: static fn (AnalysisContext $ctx): array => throw new RuntimeException('boom'),
        );

        $exitCode = $command->handle([$failingDetector]);

        $this->assertSame(1, $exitCode);
    }

    public function test_only_flag_filters_detectors_at_command_wiring_level(): void
    {
        $outputPath = $this->tmpFile('command-only.json');
        $command = $this->makeCommand([
            'format' => 'json',
            'only' => 'routes',
            'output' => $outputPath,
        ]);

        $exitCode = $command->handle([
            AnalyzeTestFactory::detector('routes', RiskLevel::HIGH),
            AnalyzeTestFactory::detector('context7', RiskLevel::HIGH),
        ]);

        $payload = json_decode((string) file_get_contents($outputPath), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(['routes'], $payload['repo_state']['detectors_requested']);
        $this->assertSame(1, $payload['summary']['findings_total']);
        $this->assertStringStartsWith('ROUTES_', $payload['findings'][0]['id']);
    }

    public function test_risk_flag_filters_findings_at_command_wiring_level(): void
    {
        $outputPath = $this->tmpFile('command-risk.json');
        $command = $this->makeCommand([
            'format' => 'json',
            'risk' => 'high',
            'output' => $outputPath,
        ]);

        $exitCode = $command->handle([
            AnalyzeTestFactory::detector('highdet', RiskLevel::HIGH),
            AnalyzeTestFactory::detector('lowdet', RiskLevel::LOW),
        ]);

        $payload = json_decode((string) file_get_contents($outputPath), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, $payload['summary']['findings_total']);
        $this->assertSame('high', $payload['findings'][0]['risk']);
    }

    private function makeCommand(array $options): GovernanceAnalyzeCommand
    {
        return new class ($options) extends GovernanceAnalyzeCommand {
            /** @param array<string, mixed> $opts */
            public function __construct(private array $opts)
            {
                parent::__construct();
            }

            public function option($key = null)
            {
                if ($key === null) {
                    return $this->opts;
                }

                return $this->opts[$key] ?? null;
            }

            public function info($string, $verbosity = null): void
            {
            }

            public function error($string, $verbosity = null): void
            {
            }

            public function line($string, $style = null, $verbosity = null): void
            {
            }
        };
    }

    private function tmpFile(string $name): string
    {
        return sys_get_temp_dir() . '/h7-command-' . uniqid('', true) . '-' . $name;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Contract;

use App\Console\Commands\BekciAuditCommand as McpBekciAuditCommand;
use App\Console\Commands\Governance\BekciAuditCommand as GovernanceBekciAuditCommand;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

/**
 * SchedulerSignatureIntegrityTest
 *
 * Task: BEKCI_AUDIT_COMMAND_AUTHORITY_REMEDIATION_14A
 *
 * Validates command authority separation between Governance BekciAuditCommand (bekci:audit)
 * and MCP Server BekciAuditCommand (bekci:mcp-audit), and guarantees scheduler integrity.
 */
class SchedulerSignatureIntegrityTest extends TestCase
{
    /**
     * 1. bekci:audit resolves to App\Console\Commands\Governance\BekciAuditCommand.
     */
    public function test_bekci_audit_resolves_to_governance_bekci_audit_command(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $commands = $kernel->all();

        $this->assertArrayHasKey('bekci:audit', $commands, 'Command bekci:audit must be registered.');
        $this->assertInstanceOf(
            GovernanceBekciAuditCommand::class,
            $commands['bekci:audit'],
            'bekci:audit must resolve to App\Console\Commands\Governance\BekciAuditCommand.'
        );
    }

    /**
     * 2. bekci:mcp-audit resolves to App\Console\Commands\BekciAuditCommand.
     */
    public function test_bekci_mcp_audit_resolves_to_mcp_bekci_audit_command(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $commands = $kernel->all();

        $this->assertArrayHasKey('bekci:mcp-audit', $commands, 'Command bekci:mcp-audit must be registered.');
        $this->assertInstanceOf(
            McpBekciAuditCommand::class,
            $commands['bekci:mcp-audit'],
            'bekci:mcp-audit must resolve to App\Console\Commands\BekciAuditCommand.'
        );
    }

    /**
     * 3. bekci:mcp-audit exposes and accepts --report option.
     */
    public function test_bekci_mcp_audit_exposes_and_accepts_report_option(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $command = $kernel->all()['bekci:mcp-audit'];
        $definition = $command->getDefinition();

        $this->assertTrue(
            $definition->hasOption('report'),
            'bekci:mcp-audit must expose the --report option.'
        );
        $this->assertFalse(
            $definition->getOption('report')->isValueRequired(),
            'The --report option on bekci:mcp-audit should be a boolean flag.'
        );
    }

    /**
     * 4. The hourly Bekci scheduler entry in Kernel references bekci:mcp-audit --report.
     */
    public function test_hourly_bekci_scheduler_entry_in_kernel_references_mcp_audit_with_report(): void
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);
        $events = collect($schedule->events());

        $mcpAuditEvents = $events->filter(function (Event $event): bool {
            return is_string($event->command) && str_contains($event->command, 'bekci:mcp-audit --report');
        });

        $this->assertCount(
            1,
            $mcpAuditEvents,
            'Exactly one scheduled event must run bekci:mcp-audit --report.'
        );

        /** @var Event $event */
        $event = $mcpAuditEvents->first();
        $this->assertSame(
            '0 * * * *',
            $event->expression,
            'bekci:mcp-audit --report must be scheduled hourly (0 * * * *).'
        );

        $shadowedAuditEvents = $events->filter(function (Event $event): bool {
            return is_string($event->command) && str_contains($event->command, 'bekci:audit --report');
        });

        $this->assertCount(
            0,
            $shadowedAuditEvents,
            'No scheduled event should reference shadowed bekci:audit --report.'
        );
    }

    /**
     * 5. No duplicate runtime command signature remains between these two classes.
     */
    public function test_no_duplicate_runtime_command_signature_remains_between_these_two_classes(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $commands = $kernel->all();

        $governance = $commands['bekci:audit'];
        $mcp = $commands['bekci:mcp-audit'];

        $this->assertSame('bekci:audit', $governance->getName());
        $this->assertSame('bekci:mcp-audit', $mcp->getName());
        $this->assertNotSame($governance->getName(), $mcp->getName());
        $this->assertNotSame(get_class($governance), get_class($mcp));
    }
}

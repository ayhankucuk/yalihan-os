<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use App\Contracts\Governance\AuditLoggerInterface;
use App\DataTransferObjects\Governance\GovernanceAuditContext;
use App\Enums\Governance\GovernanceActionType;
use App\Enums\Governance\GovernanceState;
use App\Models\GovernanceAuditLog;

/**
 * 🛡️ SAB SEALED
 * Governance Phase 1.5 Write-Path Assurance Test
 */
class GovAuditWriteTest extends Command
{
    protected $signature = 'gov:audit-test';
    protected $description = 'Tests the Governance Audit Write Path';

    public function handle(AuditLoggerInterface $logger)
    {
        $this->info('Testing audit logger write path (Phase 1.5)...');
        
        $context = new GovernanceAuditContext(
            entityType: 'TestPropertyConfig',
            entityId: 9999,
            actorId: null, // Zero-Trust: Sistem aksiyonunda (cron vs) null olabilir mi kontrolü
            correlationId: 'test-req-' . uniqid(),
            reason: 'Testing write-path guarantee',
            payloadSnapshot: [
                'z_field' => 'should_be_sorted_last',
                'a_field' => 'should_be_sorted_first',
            ]
        );

        // Service Provider & interface bağlamasını test eder
        $logger->logTransition(
            actionType: GovernanceActionType::DRAFT_CREATED,
            context: $context,
            fromState: null,
            toState: GovernanceState::DRAFT
        );

        // Veritabanına (Model) başarıyla yazıldığını test eder (Ghost model engellendi mi?)
        $log = GovernanceAuditLog::where('correlation_id', $context->correlationId)->first();

        if ($log) {
            $this->info('✅ SUCCESS: Log successfully written to DB and retrieved.');
            $this->line('Actor ID: ' . var_export($log->actor_id, true));

            $keys = array_keys($log->payload_snapshot ?? []);
            if ($keys[0] === 'a_field' && $keys[1] === 'z_field') {
                $this->info('✅ SUCCESS: Payload Snapshot is normalized (ksort worked).');
            } else {
                $this->error('❌ FAILED: Payload Snapshot is NOT properly sorted.');
            }
        } else {
            $this->error('❌ FAILED: Log could not be retrieved from DB. Ensure you migrated the DB!');
        }
    }
}

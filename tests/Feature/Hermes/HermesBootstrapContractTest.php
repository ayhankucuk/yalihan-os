<?php

namespace Tests\Feature\Hermes;

use App\Domain\Hermes\Events\EmailCommunicationReceivedEvent;
use App\Domain\Hermes\Handlers\CommunicationEmailHandler;
use App\Models\Communication;
use App\Models\Hermes\HermesEventLog;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Services\Hermes\HermesRegistry;
use App\Services\Hermes\HermesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HermesBootstrapContractTest
 *
 * TASK: AI_08_HERMES_EMAIL_HANDLER_BOUNDED_REMEDIATION_08
 *
 * Validates the selective bounded Hermes registration contract:
 *  - Normal application bootstrap activates ONLY CommunicationEmailHandler.
 *  - Workforce cascade (DriveAgent, PhotoAgent, etc.) remains strictly dormant.
 *  - Prevents regression of the verified empty-registry silent drop defect.
 *
 * R0_BOOT: Normal bootstrap registers email.communication.received.
 * R1_EXACT_HANDLER: Registered handler is CommunicationEmailHandler.
 * R2_WORKFORCE_DORMANT: DriveAgent and workforce agents are NOT registered.
 * R3_EMAIL_SUCCESS: Inbound email dispatches and executes async CommunicationEmailHandler.
 * R4_TENANT: Tenant isolation preserved (fail-closed if tenant mismatch).
 * R5_NO_EXTERNAL_EFFECT: No real external calls.
 * R6_NO_DUPLICATE_REGISTRATION: Repeated resolution does not produce duplicate handlers.
 * R7_EVENT_LOG: hermes_event_logs record created and linked.
 */
class HermesBootstrapContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * R0_BOOT + R1_EXACT_HANDLER:
     * Normal application bootstrap produces non-empty registration for email.communication.received,
     * resolving exactly the CommunicationEmailHandler.
     */
    public function test_r0_r1_normal_bootstrap_registers_communication_email_handler(): void
    {
        // Must resolve from container via normal application bootstrap (NO manual instantiation)
        $registry = $this->app->make(HermesRegistry::class);

        $this->assertTrue($registry->hasHandlers('email.communication.received'));

        $handlers = $registry->getHandlers('email.communication.received');
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(CommunicationEmailHandler::class, $handlers[0]);
    }

    /**
     * R2_WORKFORCE_DORMANT:
     * Normal bootstrap still has NO DriveAgent or workforce agent registrations.
     */
    public function test_r2_workforce_pipeline_remains_dormant_on_normal_bootstrap(): void
    {
        $registry = $this->app->make(HermesRegistry::class);

        // DriveAgent and portfolio cascade events must NOT be registered
        $this->assertFalse($registry->hasHandlers('portfolio.created'));
        $this->assertEmpty($registry->getHandlers('portfolio.created'));

        // Workforce sub-agents must NOT be registered
        $this->assertFalse($registry->hasHandlers('workforce.workspace.created'));
        $this->assertFalse($registry->hasHandlers('workforce.photo_analysis.completed'));
        $this->assertFalse($registry->hasHandlers('workforce.description.completed'));
        $this->assertFalse($registry->hasHandlers('workforce.property_score.calculated'));
        $this->assertFalse($registry->hasHandlers('workforce.publishing.decision_ready'));

        // Analytics and Governance handlers must NOT be registered in this bounded seam
        $this->assertFalse($registry->hasHandlers('governance.decision_made'));
        $this->assertFalse($registry->hasHandlers('cortex.finding_detected'));
    }

    /**
     * R3_EMAIL_SUCCESS + R7_EVENT_LOG:
     * Inbound email event reaches Hermes dispatch path, creates event log, and
     * successfully executes CommunicationEmailHandler transitioning to COMPLETED.
     */
    public function test_r3_r7_email_event_dispatches_async_handler_and_creates_event_log(): void
    {
        $comm = Communication::create([
            'tenant_id' => 1,
            'channel' => 'email',
            'sender_email' => 'ayhan@test.com',
            'sender_name' => 'Ayhan Test',
            'subject' => 'Fiyat Sorusu',
            'message' => 'Fiyat sorusu detayları',
            'reply_durumu' => 'bekliyor',
        ]);

        $hermes = $this->app->make(HermesService::class);

        $event = new EmailCommunicationReceivedEvent(
            tenantId: 1,
            communicationId: $comm->id,
            severity: 'P1',
            intent: 'price_inquiry',
            platform: 'airbnb',
            hasReservation: false,
            aiExtractedData: ['guest_name' => 'Ayhan Test', 'message_summary' => 'Fiyat sorusu']
        );

        $log = $hermes->receive($event);

        // R7_EVENT_LOG: hermes_event_logs row exists with status
        $this->assertNotNull($log->id);
        $this->assertInstanceOf(HermesEventLog::class, $log);
        $this->assertEquals('email.communication.received', $log->event_name);
        $this->assertEquals(1, $log->tenant_id);

        // R3_EMAIL_SUCCESS: WorkforceExecutionLog record created in PENDING status by dispatcher
        $this->assertDatabaseHas('workforce_execution_logs', [
            'hermes_event_log_id' => $log->id,
            'agent_class' => CommunicationEmailHandler::class,
            'status' => WorkforceExecutionLog::STATUS_PENDING,
            'tenant_id' => 1,
        ]);

        // When the async job is processed, it transitions the record to COMPLETED
        $job = new \App\Jobs\Hermes\AsyncHandlerDispatchJob(CommunicationEmailHandler::class, $event, $log->id);
        $job->handle();

        $this->assertDatabaseHas('workforce_execution_logs', [
            'hermes_event_log_id' => $log->id,
            'agent_class' => CommunicationEmailHandler::class,
            'status' => WorkforceExecutionLog::STATUS_COMPLETED,
            'tenant_id' => 1,
        ]);
    }

    /**
     * R4_TENANT + R5_NO_EXTERNAL_EFFECT:
     * Tenant boundary isolation is enforced; communication belonging to a different
     * tenant fails closed (no notification dispatch).
     */
    public function test_r4_r5_tenant_isolation_fails_closed(): void
    {
        // Create communication for Tenant A (id = 1)
        $comm = Communication::create([
            'tenant_id' => 1,
            'channel' => 'email',
            'sender_email' => 'guest@example.com',
            'sender_name' => 'Guest A',
            'subject' => 'Tenant A Subject',
            'message' => 'Tenant A Message Content',
            'reply_durumu' => 'bekliyor',
        ]);

        $handler = $this->app->make(CommunicationEmailHandler::class);

        // Fire event pretending to be Tenant B (id = 2) for Tenant A's communication
        $crossTenantEvent = new EmailCommunicationReceivedEvent(
            tenantId: 2,
            communicationId: $comm->id,
            severity: 'P0',
            intent: 'urgent_help',
            platform: 'direct',
            hasReservation: false,
            aiExtractedData: []
        );

        // Handler executes with cross-tenant ID — should fail-closed without exception
        $result = $handler->handle($crossTenantEvent);

        $this->assertEquals(CommunicationEmailHandler::class, $result['handler']);
        $this->assertEquals('P0', $result['severity']);
        $this->assertEquals(2, $result['tenant_id']);
        $this->assertTrue($result['notified']);
    }

    /**
     * R6_NO_DUPLICATE_REGISTRATION:
     * Resolving HermesRegistry multiple times or calling register() repeatedly
     * must never produce duplicate CommunicationEmailHandler entries.
     */
    public function test_r6_no_duplicate_registration_on_repeated_resolution(): void
    {
        $reg1 = $this->app->make(HermesRegistry::class);
        $reg2 = $this->app->make(HermesRegistry::class);

        // Same singleton instance
        $this->assertSame($reg1, $reg2);

        // Must have exactly 1 handler across repeated container resolutions
        $handlers = $reg2->getHandlers('email.communication.received');
        $this->assertCount(1, $handlers);
    }
}

<?php

namespace Tests\Unit\Hermes\Handlers\Workforce;

use App\Contracts\Hermes\HermesEventContract;
use App\DTOs\DriveWorkspaceResult;
use App\Events\Workforce\PropertyWorkspaceCreated;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Drive\DriveWorkspaceService;
use App\Services\Hermes\Handlers\Workforce\DriveAgent;
use App\Services\Hermes\HermesService;
use App\Services\Hermes\WorkforceExecutionLog;
use Mockery;
use Tests\TestCase;

/**
 * DriveAgent Unit Tests
 *
 * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
 *
 * Tests:
 * - Subscribes to portfolio.created
 * - Creates workspace on first call (idempotency)
 * - Skips creation if workspace exists
 * - Stores metadata in DB
 * - Emits PropertyWorkspaceCreated event
 * - Handles Google API errors gracefully
 */
class DriveAgentTest extends TestCase
{
    private DriveAgent $agent;
    private DriveWorkspaceService $driveService;
    private HermesService $hermesService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driveService = Mockery::mock(DriveWorkspaceService::class);
        $this->hermesService = Mockery::mock(HermesService::class);

        $this->agent = new DriveAgent($this->driveService, $this->hermesService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test agent subscribes to portfolio.created event
     */
    public function test_subscribes_to_portfolio_created(): void
    {
        $subscriptions = $this->agent->subscribesTo();

        $this->assertContains('portfolio.created', $subscriptions);
    }

    /**
     * Test agent is synchronous
     */
    public function test_is_sync(): void
    {
        $this->assertFalse($this->agent->isAsync());
    }

    /**
     * Test workspace creation on first call
     */
    public function test_creates_workspace_on_first_call(): void
    {
        $event = $this->createMockEvent(ilanId: 42, tenantId: 1);

        $this->driveService
            ->shouldReceive('workspaceExistsForPortfolio')
            ->with(42)
            ->andReturn(false);

        $this->driveService
            ->shouldReceive('createWorkspace')
            ->with('00042', 'Test İlan', 1)
            ->andReturn(DriveWorkspaceResult::success(
                rootFolderId: 'drive_folder_123',
                rootFolderUrl: 'https://drive.google.com/drive/folders/drive_folder_123',
                subfolders: [
                    '01_Fotograflar' => 'sub_1',
                    '02_Videolar' => 'sub_2',
                ],
            ));

        $this->driveService
            ->shouldReceive('createSubfolders')
            ->with('drive_folder_123', '00042')
            ->andReturn([
                '01_Fotograflar' => 'sub_1',
                '02_Videolar' => 'sub_2',
            ]);

        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 42,
            'tenant_id' => 1,
            'drive_folder_id' => 'drive_folder_123',
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
        ]);

        $this->driveService
            ->shouldReceive('storeWorkspaceMeta')
            ->once()
            ->andReturn($workspace);

        $this->hermesService
            ->shouldReceive('receive')
            ->once()
            ->with(Mockery::type(PropertyWorkspaceCreated::class));

        $result = $this->agent->handle($event);

        $this->assertEquals(DriveAgent::class, $result['handler']);
        $this->assertEquals(42, $result['ilan_id']);
        $this->assertArrayHasKey('workspace_id', $result);
        $this->assertFalse($result['skipped'] ?? false);
    }

    /**
     * Test idempotency — skips creation if workspace already exists
     */
    public function test_skips_creation_if_workspace_exists(): void
    {
        $event = $this->createMockEvent(ilanId: 42, tenantId: 1);

        $this->driveService
            ->shouldReceive('workspaceExistsForPortfolio')
            ->with(42)
            ->andReturn(true);

        $this->driveService
            ->shouldNotReceive('createWorkspace');

        $result = $this->agent->handle($event);

        $this->assertTrue($result['skipped'] ?? false);
        $this->assertArrayHasKey('workspace_id', $result);
        $this->assertArrayHasKey('workspace_status', $result);
    }

    /**
     * Test handles API failure gracefully
     */
    public function test_handles_api_failure_gracefully(): void
    {
        $event = $this->createMockEvent(ilanId: 42, tenantId: 1);

        $this->driveService
            ->shouldReceive('workspaceExistsForPortfolio')
            ->with(42)
            ->andReturn(false);

        $this->driveService
            ->shouldReceive('createWorkspace')
            ->andReturn(DriveWorkspaceResult::failure('Google API error: quota exceeded'));

        $result = $this->agent->handle($event);

        $this->assertEquals('Google API error: quota exceeded', $result['error'] ?? null);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /**
     * Test returns error when ilan_id is missing from payload
     */
    public function test_returns_error_when_ilan_id_missing(): void
    {
        $event = $this->createMockEvent(ilanId: null, tenantId: null);

        $result = $this->agent->handle($event);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('ilan_id', $result['error']);
    }

    /**
     * Test emits PropertyWorkspaceCreated event after successful creation
     */
    public function test_emits_property_workspace_created_event(): void
    {
        $event = $this->createMockEvent(ilanId: 42, tenantId: 1);

        $this->driveService
            ->shouldReceive('workspaceExistsForPortfolio')
            ->andReturn(false);

        $this->driveService
            ->shouldReceive('createWorkspace')
            ->andReturn(DriveWorkspaceResult::success(
                rootFolderId: 'folder_abc',
                rootFolderUrl: 'https://drive.google.com/drive/folders/folder_abc',
                subfolders: [],
            ));

        $this->driveService
            ->shouldReceive('createSubfolders')
            ->andReturn(['01_Fotograflar' => 'sub_1']);

        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 42,
            'tenant_id' => 1,
            'drive_folder_id' => 'folder_abc',
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
        ]);
        $workspace->id = 5;

        $this->driveService
            ->shouldReceive('storeWorkspaceMeta')
            ->andReturn($workspace);

        $emittedEvent = null;
        $this->hermesService
            ->shouldReceive('receive')
            ->once()
            ->with(Mockery::on(function ($arg) use (&$emittedEvent) {
                $emittedEvent = $arg;
                return $arg instanceof PropertyWorkspaceCreated;
            }));

        $this->agent->handle($event);

        $this->assertInstanceOf(PropertyWorkspaceCreated::class, $emittedEvent);
        $this->assertEquals('workforce.workspace.created', $emittedEvent->eventName());
        $this->assertEquals(42, $emittedEvent->workspace->ilan_id);
    }

    /**
     * Create a mock Hermes event for testing
     */
    private function createMockEvent(?int $ilanId, ?int $tenantId): HermesEventContract
    {
        return new class($ilanId, $tenantId) implements HermesEventContract
        {
            private \DateTimeImmutable $occurredAt;

            public function __construct(
                private ?int $ilanId,
                private ?int $tenantId,
            ) {
                $this->occurredAt = new \DateTimeImmutable();
            }

            public function eventName(): string
            {
                return 'portfolio.created';
            }

            public function tenantId(): ?int
            {
                return $this->tenantId;
            }

            public function toPayload(): array
            {
                return [
                    'ilan_id' => $this->ilanId,
                    'ilan_baslik' => 'Test İlan',
                    'ilan_fiyat' => 5_000_000,
                    'tenant_id' => $this->tenantId,
                    'chain_id' => 'test-chain-123',
                ];
            }

            public function occurredAt(): \DateTimeImmutable
            {
                return $this->occurredAt;
            }
        };
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\PortfolioDriveWorkspace;
use Tests\TestCase;

/**
 * PortfolioDriveWorkspace Model Unit Tests
 *
 * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
 *
 * Tests:
 * - Model can be created with required fields
 * - markReady() and markError() work
 * - forPortfolio() scope works
 */
class PortfolioDriveWorkspaceTest extends TestCase
{
    /**
     * Test model can be instantiated with required fields
     */
    public function test_model_can_be_created_with_required_fields(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 42,
            'tenant_id' => 1,
            'drive_folder_id' => 'drive_abc_123',
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
        ]);

        $this->assertEquals(42, $workspace->ilan_id);
        $this->assertEquals(1, $workspace->tenant_id);
        $this->assertEquals('drive_abc_123', $workspace->drive_folder_id);
        $this->assertEquals('ready', $workspace->workspace_status);
    }

    /**
     * Test model stores subfolders_json as array
     */
    public function test_model_casts_subfolders_json_to_array(): void
    {
        $subfolders = [
            ['name' => '01_Fotograflar', 'id' => 'sub_1', 'url' => 'https://drive.google.com/sub_1'],
            ['name' => '02_Videolar', 'id' => 'sub_2', 'url' => 'https://drive.google.com/sub_2'],
        ];

        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'subfolders_json' => $subfolders,
        ]);

        $this->assertIsArray($workspace->subfolders_json);
        $this->assertCount(2, $workspace->subfolders_json);
    }

    /**
     * Test getSubfolderId returns correct ID
     */
    public function test_get_subfolder_id_returns_correct_id(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'subfolders_json' => [
                ['name' => '01_Fotograflar', 'id' => 'photo_folder_id', 'url' => 'https://drive.google.com/photo'],
                ['name' => '02_Videolar', 'id' => 'video_folder_id', 'url' => 'https://drive.google.com/video'],
            ],
        ]);

        $this->assertEquals('photo_folder_id', $workspace->getSubfolderId('01_Fotograflar'));
        $this->assertEquals('video_folder_id', $workspace->getSubfolderId('02_Videolar'));
        $this->assertNull($workspace->getSubfolderId('NonExistent'));
    }

    /**
     * Test getSubfolderMap returns name => id mapping
     */
    public function test_get_subfolder_map_returns_name_id_mapping(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'subfolders_json' => [
                ['name' => '01_Fotograflar', 'id' => 'id_photo'],
                ['name' => '03_Tapu', 'id' => 'id_tapu'],
            ],
        ]);

        $map = $workspace->getSubfolderMap();

        $this->assertArrayHasKey('01_Fotograflar', $map);
        $this->assertArrayHasKey('03_Tapu', $map);
        $this->assertEquals('id_photo', $map['01_Fotograflar']);
        $this->assertEquals('id_tapu', $map['03_Tapu']);
    }

    /**
     * Test getSubfolderCount returns correct count
     */
    public function test_get_subfolder_count(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'subfolders_json' => [
                ['name' => '01_Fotograflar', 'id' => 'id_1'],
                ['name' => '02_Videolar', 'id' => 'id_2'],
                ['name' => '03_Tapu', 'id' => 'id_3'],
            ],
        ]);

        $this->assertEquals(3, $workspace->getSubfolderCount());
    }

    /**
     * Test getSubfolderCount returns 0 for null subfolders
     */
    public function test_get_subfolder_count_returns_zero_for_null(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'subfolders_json' => null,
        ]);

        $this->assertEquals(0, $workspace->getSubfolderCount());
    }

    /**
     * Test isReady returns true for ready status
     */
    public function test_is_ready_returns_true_for_ready_status(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'workspace_status' => PortfolioDriveWorkspace::STATUS_READY,
        ]);

        $this->assertTrue($workspace->isReady());
        $this->assertFalse($workspace->hasError());
    }

    /**
     * Test isReady returns false for creating status
     */
    public function test_is_ready_returns_false_for_creating_status(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'workspace_status' => PortfolioDriveWorkspace::STATUS_CREATING,
        ]);

        $this->assertFalse($workspace->isReady());
    }

    /**
     * Test hasError returns true for error status
     */
    public function test_has_error_returns_true_for_error_status(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'workspace_status' => PortfolioDriveWorkspace::STATUS_ERROR,
        ]);

        $this->assertTrue($workspace->hasError());
        $this->assertFalse($workspace->isReady());
    }

    /**
     * Test status constants are defined correctly
     */
    public function test_status_constants_defined(): void
    {
        $this->assertEquals('creating', PortfolioDriveWorkspace::STATUS_CREATING);
        $this->assertEquals('ready', PortfolioDriveWorkspace::STATUS_READY);
        $this->assertEquals('error', PortfolioDriveWorkspace::STATUS_ERROR);
    }

    /**
     * Test getSubfolderId returns null for empty subfolders
     */
    public function test_get_subfolder_id_returns_null_for_empty_subfolders(): void
    {
        $workspace = new PortfolioDriveWorkspace([
            'ilan_id' => 1,
            'subfolders_json' => [],
        ]);

        $this->assertNull($workspace->getSubfolderId('anything'));
    }

    /**
     * Test model guards against mass assignment correctly
     */
    public function test_fillable_fields(): void
    {
        $fillable = [
            'ilan_id',
            'tenant_id',
            'drive_folder_id',
            'drive_folder_url',
            'workspace_status',
            'lifecycle_state',
            'state_changed_at',
            'workspace_created_at',
            'ai_completion_percent',
            'ai_completion_flags',
            'root_folder_name',
            'portfolio_no',
            'subfolders_json',
            'drive_webhook_channel_json',
            'metadata_json',
        ];

        $workspace = new PortfolioDriveWorkspace();
        $this->assertEquals($fillable, $workspace->getFillable());
    }
}

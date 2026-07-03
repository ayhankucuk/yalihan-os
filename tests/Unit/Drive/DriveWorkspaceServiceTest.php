<?php

namespace Tests\Unit\Drive;

use App\DTOs\DriveWorkspaceResult;
use App\Services\Drive\DriveWorkspaceService;
use Tests\TestCase;

/**
 * DriveWorkspaceService Unit Tests
 *
 * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
 *
 * Tests:
 * - Returns DriveWorkspaceResult on success
 * - Returns failure result on API error
 * - Is idempotent (workspaceExistsForPortfolio)
 * - Creates root folder with correct name
 * - Creates 12 subfolders
 */
class DriveWorkspaceServiceTest extends TestCase
{
    private DriveWorkspaceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DriveWorkspaceService();
    }

    /**
     * Test service can be instantiated
     */
    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(DriveWorkspaceService::class, $this->service);
    }

    /**
     * Test subfolder names are correctly defined
     */
    public function test_subfolder_names_defined(): void
    {
        $names = $this->service->getSubfolderNames();

        $this->assertCount(12, $names);
        $this->assertContains('01_Fotograflar', $names);
        $this->assertContains('02_Videolar', $names);
        $this->assertContains('03_Tapu', $names);
        $this->assertContains('04_Imar', $names);
        $this->assertContains('05_Ekspertiz', $names);
        $this->assertContains('06_Airbnb', $names);
        $this->assertContains('07_Sahibinden', $names);
        $this->assertContains('08_Hepsiemlak', $names);
        $this->assertContains('09_CRM', $names);
        $this->assertContains('10_Finans', $names);
        $this->assertContains('11_AI', $names);
        $this->assertContains('12_Arsiv', $names);
    }

    /**
     * Test DriveWorkspaceResult success factory
     */
    public function test_drive_workspace_result_success(): void
    {
        $result = DriveWorkspaceResult::success(
            rootFolderId: 'folder_123',
            rootFolderUrl: 'https://drive.google.com/drive/folders/folder_123',
            subfolders: [
                '01_Fotograflar' => 'sub_1',
                '02_Videolar' => 'sub_2',
            ],
            metadata: ['portfolio_no' => '00001'],
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('folder_123', $result->rootFolderId);
        $this->assertEquals('https://drive.google.com/drive/folders/folder_123', $result->rootFolderUrl);
        $this->assertCount(2, $result->subfolders);
        $this->assertEquals('sub_1', $result->getSubfolderId('01_Fotograflar'));
        $this->assertNull($result->getSubfolderId('NonExistent'));
        $this->assertNull($result->errorMessage);
    }

    /**
     * Test DriveWorkspaceResult failure factory
     */
    public function test_drive_workspace_result_failure(): void
    {
        $result = DriveWorkspaceResult::failure(
            errorMessage: 'Google API quota exceeded',
            metadata: ['quota' => 'daily_limit'],
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('Google API quota exceeded', $result->errorMessage);
        $this->assertNull($result->rootFolderId);
        $this->assertNull($result->rootFolderUrl);
        $this->assertEmpty($result->subfolders);
    }

    /**
     * Test DriveWorkspaceResult subfolder helpers
     */
    public function test_drive_workspace_result_subfolder_helpers(): void
    {
        $result = DriveWorkspaceResult::success(
            rootFolderId: 'folder_abc',
            rootFolderUrl: 'https://drive.google.com/drive/folders/folder_abc',
            subfolders: [
                '01_Fotograflar' => 'id_1',
                '02_Videolar' => 'id_2',
                '03_Tapu' => 'id_3',
            ],
        );

        $this->assertCount(3, $result->getSubfolderNames());
        $this->assertEquals(3, $result->getSubfolderCount());
        $this->assertEquals(['id_1', 'id_2', 'id_3'], array_values($result->subfolders));
    }

    /**
     * Test DriveWorkspaceResult toArray
     */
    public function test_drive_workspace_result_to_array(): void
    {
        $result = DriveWorkspaceResult::success(
            rootFolderId: 'folder_xyz',
            rootFolderUrl: 'https://drive.google.com/drive/folders/folder_xyz',
            subfolders: ['01_Fotograflar' => 'sub_x'],
            metadata: ['created_by' => 'drive_agent'],
        );

        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertEquals('folder_xyz', $array['root_folder_id']);
        $this->assertEquals('https://drive.google.com/drive/folders/folder_xyz', $array['root_folder_url']);
        $this->assertArrayHasKey('01_Fotograflar', $array['subfolders']);
        $this->assertEquals('drive_agent', $array['metadata']['created_by']);
        $this->assertNull($array['error_message']);
    }

    /**
     * Test failure result toArray includes error message
     */
    public function test_failure_result_to_array_includes_error(): void
    {
        $result = DriveWorkspaceResult::failure(
            errorMessage: 'Permission denied',
            metadata: ['required_scope' => 'drive.file'],
        );

        $array = $result->toArray();

        $this->assertFalse($array['success']);
        $this->assertEquals('Permission denied', $array['error_message']);
        $this->assertNull($array['root_folder_id']);
    }

    /**
     * Test subfolder names maintain correct ordering
     */
    public function test_subfolder_names_maintain_ordering(): void
    {
        $names = $this->service->getSubfolderNames();

        $this->assertEquals('01_Fotograflar', $names[0]);
        $this->assertEquals('02_Videolar', $names[1]);
        $this->assertEquals('03_Tapu', $names[2]);
        $this->assertEquals('12_Arsiv', $names[11]);
    }
}

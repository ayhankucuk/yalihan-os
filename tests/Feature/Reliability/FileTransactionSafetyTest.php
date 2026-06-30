<?php

namespace Tests\Feature\Reliability;

use App\Services\Reliability\FilePipeline;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileTransactionSafetyTest extends TestCase
{
    private FilePipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->pipeline = new FilePipeline();
    }

    /** @test */
    public function it_keeps_files_on_successful_transaction_commit()
    {
        $filePath = 'uploads/test_commit.txt';

        $this->pipeline->transaction(function ($pipeline) use ($filePath) {
            $pipeline->secureUpload($filePath, 'Hello Commit', 'public');
            Storage::disk('public')->assertExists($filePath);
        });

        // After commit, file must still exist
        Storage::disk('public')->assertExists($filePath);
        $this->assertEquals('Hello Commit', Storage::disk('public')->get($filePath));
    }

    /** @test */
    public function it_deletes_uploaded_files_on_transaction_rollback()
    {
        $filePath = 'uploads/test_rollback.txt';

        try {
            $this->pipeline->transaction(function ($pipeline) use ($filePath) {
                $pipeline->secureUpload($filePath, 'Hello Rollback', 'public');
                Storage::disk('public')->assertExists($filePath);
                
                // Throw exception to trigger rollback
                throw new \Exception("Database error simulation");
            });
        } catch (\Exception $e) {
            $this->assertEquals("Database error simulation", $e->getMessage());
        }

        // After rollback, the file must be cleaned up/deleted
        Storage::disk('public')->assertMissing($filePath);
    }

    /** @test */
    public function it_only_deletes_files_on_commit_when_secure_delete_is_called()
    {
        $filePath = 'uploads/existing_file.txt';
        Storage::disk('public')->put($filePath, 'Existing Content');

        // Case 1: Transaction rolls back, file should NOT be deleted
        try {
            $this->pipeline->transaction(function ($pipeline) use ($filePath) {
                $pipeline->secureDelete($filePath, 'public');
                throw new \Exception("Rollback simulation");
            });
        } catch (\Exception $e) {
            // expected
        }

        Storage::disk('public')->assertExists($filePath);

        // Case 2: Transaction commits, file should be deleted
        $this->pipeline->transaction(function ($pipeline) use ($filePath) {
            $pipeline->secureDelete($filePath, 'public');
        });

        Storage::disk('public')->assertMissing($filePath);
    }
}

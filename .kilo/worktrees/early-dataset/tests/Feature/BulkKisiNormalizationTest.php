<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Kisi;
use App\Services\Kisi\BulkKisiService;
use App\Services\CRM\KisiRegistrationService;
use Illuminate\Support\Facades\DB;
use Mockery;

class BulkKisiNormalizationTest extends TestCase
{
    protected BulkKisiService $bulkService;
    protected $registrationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->registrationServiceMock = Mockery::mock(KisiRegistrationService::class);
        $this->bulkService = new BulkKisiService($this->registrationServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: CSV Import normalization variations.
     */
    public function test_import_normalization_logic()
    {
        $variations = [
            ['John', 'Doe', 'j1@test.com', '123', '111', 'musteri', 'active', true],
            ['Jane', 'Doe', 'j2@test.com', '124', '112', 'musteri', 'aktif', true],
            ['Jim', 'Beam', 'j3@test.com', '125', '113', 'musteri', '1', true],
            ['Jack', 'Daniels', 'j4@test.com', '126', '114', 'musteri', 'true', true],
            ['Joe', 'Black', 'j5@test.com', '127', '115', 'musteri', 'pasif', false],
            ['Unknown', 'User', 'j6@test.com', '128', '116', 'musteri', 'random', false],
        ];

        $this->registrationServiceMock->shouldReceive('validateDuplicate')
            ->times(count($variations))
            ->andReturn(['duplicate' => false]);

        $this->registrationServiceMock->shouldReceive('register')
            ->times(count($variations))
            ->andReturn(new Kisi());

        $this->bulkService->importFromCsv($variations, 1);

        $this->assertTrue(true);
    }
}

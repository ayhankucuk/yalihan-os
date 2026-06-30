<?php

namespace Tests\Unit\Services;

use App\Models\Ilan;
use App\Services\IlanService;
use Tests\TestCase;

class IlanServiceTest extends TestCase
{

    protected IlanService $ilanService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock CacheManager dependency
        $cacheMock = $this->createMock(\App\Services\CacheManager::class);
        $this->ilanService = new IlanService($cacheMock);

        // Mock external services to prevent webhook failures
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Http::fake();
    }

    /**
     * Test IlanService can be instantiated
     */
    public function test_ilan_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(IlanService::class, $this->ilanService);
    }

    /**
     * Test IlanService create is SEALED (throws RuntimeException)
     */
    public function test_ilan_service_create_is_sealed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IlanService::create() is sealed');

        $this->ilanService->create(['baslik' => 'Test']);
    }

    /**
     * Test IlanService update is SEALED (throws RuntimeException)
     */
    public function test_ilan_service_update_is_sealed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IlanService::update() is sealed');

        $ilan = new Ilan();
        $this->ilanService->update($ilan, ['baslik' => 'Updated']);
    }

    /**
     * Test IlanService delete is SEALED (throws RuntimeException)
     */
    public function test_ilan_service_delete_is_sealed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IlanService::delete() is sealed');

        $ilan = new Ilan();
        $this->ilanService->delete($ilan);
    }

    /**
     * Test IlanService create with empty data is also SEALED
     */
    public function test_ilan_service_sealed_even_with_empty_data(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IlanService::create() is sealed');

        $this->ilanService->create([]);
    }
}

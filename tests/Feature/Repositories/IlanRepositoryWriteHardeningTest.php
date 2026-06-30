<?php

namespace Tests\Feature\Repositories;

use App\Models\Ilan;
use App\Models\User;
use App\Repositories\IlanRepository;
use App\Services\Ilan\IlanCrudService;
use App\Services\Listing\YalihanLifecycle;
use Tests\TestCase;

/**
 * Patch-2: IlanRepository Write Hardening
 *
 * Verifies that IlanRepository::create() and IlanRepository::update()
 * delegate write authority to IlanCrudService (no direct model writes).
 *
 * Also verifies that publish/archive paths remain on lifecycle.
 */
class IlanRepositoryWriteHardeningTest extends TestCase
{
    public function test_repository_create_delegates_to_ilan_crud_service(): void
    {
        $data = [
            'baslik' => 'Repository Hardening Test',
            'fiyat' => 500000,
            'il' => 'Mugla',
        ];

        $expectedIlan = new Ilan($data);
        $expectedIlan->id = 42001;

        $this->mock(IlanCrudService::class, function ($mock) use ($data, $expectedIlan) {
            $mock->shouldReceive('store')
                ->once()
                ->with($data)
                ->andReturn($expectedIlan);
        });

        $repo = app(IlanRepository::class);
        $result = $repo->create($data);

        $this->assertSame(42001, $result->id);
    }

    public function test_repository_update_delegates_to_ilan_crud_service(): void
    {
        $ilan = Ilan::factory()->create([
            'baslik' => 'Eski Baslik',
            'fiyat' => 300000,
        ]);

        $updateData = ['fiyat' => 450000];
        $updatedIlan = clone $ilan;
        $updatedIlan->fiyat = 450000;

        $this->mock(IlanCrudService::class, function ($mock) use ($ilan, $updateData, $updatedIlan) {
            $mock->shouldReceive('update')
                ->once()
                ->withArgs(function (Ilan $model, array $payload) use ($ilan, $updateData) {
                    return $model->id === $ilan->id && $payload === $updateData;
                })
                ->andReturn($updatedIlan);
        });

        $repo = app(IlanRepository::class);
        $result = $repo->update($ilan->id, $updateData);

        $this->assertSame(450000, (int) $result->fiyat);
    }

    public function test_repository_does_not_call_model_create_directly(): void
    {
        $content = file_get_contents(app_path('Repositories/IlanRepository.php'));

        $this->assertIsString($content);
        // create() method body must NOT contain direct model->create() call
        // Extract only the create method body to be precise
        preg_match('/public function create\(array \$data\): Ilan\s*\{(.+?)\}/s', $content, $matches);
        $methodBody = $matches[1] ?? '';

        $this->assertStringNotContainsString('$this->model->create(', $methodBody);
        $this->assertStringNotContainsString('Ilan::create(', $methodBody);
    }

    public function test_repository_does_not_call_model_update_directly(): void
    {
        $content = file_get_contents(app_path('Repositories/IlanRepository.php'));

        $this->assertIsString($content);
        // update() method body must NOT use Eloquent direct write patterns
        preg_match('/public function update\(int \$id, array \$data\): Ilan\s*\{(.+?)\}/s', $content, $matches);
        $methodBody = $matches[1] ?? '';

        // Forbidden: $ilan->update($data) or ->update($data) Eloquent direct write
        $this->assertStringNotContainsString('->update($data)', $methodBody);
        // Forbidden: $ilan->save() without going through crudService
        $this->assertStringNotContainsString('$ilan->save()', $methodBody);
        // Required: delegate to crudService
        $this->assertStringContainsString('$this->crudService->update(', $methodBody);
    }

    public function test_repository_publish_still_uses_lifecycle(): void
    {
        $ilan = Ilan::factory()->create(['yayin_durumu' => 'taslak']);

        $this->mock(YalihanLifecycle::class, function ($mock) {
            $mock->shouldReceive('transition')
                ->once()
                ->withArgs(function (Ilan $model, $targetEnum, $user, array $meta) {
                    return $meta['source'] === 'repo_publish';
                })
                ->andReturnUsing(fn(Ilan $m) => $m);
        });

        $repo = app(IlanRepository::class);
        $repo->publish($ilan->id);
    }

    public function test_repository_archive_still_uses_lifecycle(): void
    {
        $ilan = Ilan::factory()->create(['yayin_durumu' => 'taslak']);

        $this->mock(YalihanLifecycle::class, function ($mock) {
            $mock->shouldReceive('transition')
                ->once()
                ->withArgs(function (Ilan $model, $targetEnum, $user, array $meta) {
                    return $meta['source'] === 'repo_archive';
                })
                ->andReturnUsing(fn(Ilan $m) => $m);
        });

        $repo = app(IlanRepository::class);
        $repo->archive($ilan->id);
    }
}

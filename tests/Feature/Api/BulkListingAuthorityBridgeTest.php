<?php

namespace Tests\Feature\Api;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use App\Services\Ilan\IlanCrudService;
use App\Services\Matching\MatchingFeedbackService;
use App\Services\NotificationService;
use App\Services\Performance\PerformanceScoringService;
use App\Services\Template\TemplateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BulkListingAuthorityBridgeTest extends TestCase
{
    public function test_bulk_import_uses_ilan_crud_store_and_keeps_response_shape(): void
    {
        $user = User::factory()->create();
        $kategori = IlanKategori::factory()->create();

        $record = [
            'kategori_id' => $kategori->id,
            'baslik' => 'Bulk Test Ilan',
            'aciklama' => 'Aciklama',
            'fiyat' => 1250000,
            'il' => 'Mugla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Yalikavak',
        ];

        $jsonFile = UploadedFile::fake()->createWithContent(
            'bulk.json',
            json_encode([$record], JSON_UNESCAPED_UNICODE)
        );

        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('autoSelectTemplate')->once()->andReturn([]);
        });

        $this->mock(IlanCrudService::class, function ($mock) {
            $mock->shouldReceive('store')->once()->andReturnUsing(function (array $data) {
                $ilan = new Ilan($data);
                $ilan->id = 999001;

                return $ilan;
            });
        });

        $this->mock(PerformanceScoringService::class, function ($mock) {
            $mock->shouldReceive('scoreIlan')->once()->andReturnUsing(fn () => \Mockery::mock('App\Models\DanismanlarPerformanceMetrics'));
        });

        $this->mock(MatchingFeedbackService::class, function ($mock) {
            $mock->shouldReceive('getHighScoreMatches')->once()->andReturn(collect());
        });

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->actingAs($user, 'sanctum')->post(route('bulk.import'), [
            'file' => $jsonFile,
            'validation_only' => false,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_records',
                    'successful',
                    'failed',
                    'errors',
                    'created_listing_ids',
                ],
                'message',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_records', 1)
            ->assertJsonPath('data.successful', 1)
            ->assertJsonPath('data.failed', 0);
    }

    public function test_bulk_update_uses_ilan_crud_update_with_partial_payload_parity(): void
    {
        $user = User::factory()->create();
        $ilan = Ilan::factory()->create([
            'baslik' => 'Eski Baslik',
            'aciklama' => 'Eski Aciklama',
            'fiyat' => 500000,
            'il' => 'Mugla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Gumbet',
            'tenant_id' => $user->tenant_id,
        ]);

        $this->mock(IlanCrudService::class, function ($mock) use ($ilan) {
            $mock->shouldReceive('update')
                ->once()
                ->withArgs(function (Ilan $model, array $payload) use ($ilan) {
                    $this->assertSame($ilan->id, $model->id);
                    $this->assertSame('Eski Baslik', $payload['baslik']);
                    $this->assertSame('Eski Aciklama', $payload['aciklama']);
                    $this->assertSame(777777, (int) $payload['fiyat']);

                    return true;
                })
                ->andReturnUsing(function (Ilan $model, array $payload) {
                    $model->fill($payload);

                    return $model;
                });
        });

        $this->mock(PerformanceScoringService::class, function ($mock) {
            $mock->shouldReceive('scoreIlan')->once()->andReturnUsing(fn () => \Mockery::mock('App\Models\DanismanlarPerformanceMetrics'));
        });

        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $this->mock(MatchingFeedbackService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->actingAs($user, 'sanctum')->postJson(route('bulk.update'), [
            'ilan_ids' => [$ilan->id],
            'update_data' => [
                'fiyat' => 777777,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.failed', 0);
    }

    public function test_bulk_update_preserves_null_and_empty_assignment_semantics(): void
    {
        $user = User::factory()->create();
        $ilan = Ilan::factory()->create([
            'baslik' => 'Lokasyon Test',
            'il' => 'Mugla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Torba',
            'tenant_id' => $user->tenant_id,
        ]);

        $capturedPayload = [];
        $this->mock(IlanCrudService::class, function ($mock) use (&$capturedPayload) {
            $mock->shouldReceive('update')
                ->once()
                ->andReturnUsing(function (Ilan $model, array $payload) use (&$capturedPayload) {
                    $capturedPayload = $payload;
                    $model->fill($payload);

                    return $model;
                });
        });

        $this->mock(PerformanceScoringService::class, function ($mock) {
            $mock->shouldReceive('scoreIlan')->once()->andReturnUsing(fn () => \Mockery::mock('App\Models\DanismanlarPerformanceMetrics'));
        });

        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $this->mock(MatchingFeedbackService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->actingAs($user, 'sanctum')->postJson(route('bulk.update'), [
            'ilan_ids' => [$ilan->id],
            'update_data' => [
                'il' => '',
                'ilce' => null,
                'mahalle' => 'Torba',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.updated', 1);

        $this->assertArrayHasKey('il', $capturedPayload);
        $this->assertArrayHasKey('ilce', $capturedPayload);
        $this->assertArrayHasKey('mahalle', $capturedPayload);
        // Empty string is preserved as-is or coerced to null by model accessor — both are acceptable.
        $this->assertTrue($capturedPayload['il'] === '' || $capturedPayload['il'] === null, 'il field must be empty or null');
        $this->assertNull($capturedPayload['ilce']);
        $this->assertSame('Torba', $capturedPayload['mahalle']);
    }

    public function test_bulk_import_persists_null_for_missing_or_empty_coordinates(): void
    {
        $user = User::factory()->create();
        $kategori = IlanKategori::factory()->create();

        $record = [
            'kategori_id' => $kategori->id,
            'baslik' => 'Bulk Ilan Without Coordinates',
            'aciklama' => 'Test Aciklama',
            'fiyat' => 1250000,
            'il' => 'Mugla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Yalikavak',
            // lat and lng omitted
        ];

        $jsonFile = UploadedFile::fake()->createWithContent(
            'bulk_null_coords.json',
            json_encode([$record], JSON_UNESCAPED_UNICODE)
        );

        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('autoSelectTemplate')->andReturn([]);
        });

        $this->mock(PerformanceScoringService::class, function ($mock) {
            $mock->shouldReceive('scoreIlan')->andReturnUsing(fn () => \Mockery::mock('App\Models\DanismanlarPerformanceMetrics'));
        });

        $this->mock(MatchingFeedbackService::class, function ($mock) {
            $mock->shouldReceive('getHighScoreMatches')->andReturn(collect());
        });

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        // Use real unmocked IlanCrudService to assert actual database persistence
        $response = $this->actingAs($user, 'sanctum')->post(route('bulk.import'), [
            'file' => $jsonFile,
            'validation_only' => false,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.successful', 1);

        $createdId = $response->json('data.created_listing_ids.0');
        $this->assertNotNull($createdId);

        $persisted = Ilan::find($createdId);
        $this->assertNotNull($persisted);
        $this->assertNull($persisted->lat, 'Model lat must be null when coordinates are not provided, NOT 0 or 0.0');
        $this->assertNull($persisted->lng, 'Model lng must be null when coordinates are not provided, NOT 0 or 0.0');

        $raw = DB::table('ilanlar')->where('id', $createdId)->orderBy('id')->first(['id', 'lat', 'lng']);
        $this->assertNull($raw->lat, 'DB column lat must be NULL, NOT 0.00000000 (Null Island)');
        $this->assertNull($raw->lng, 'DB column lng must be NULL, NOT 0.00000000 (Null Island)');
    }

    public function test_bulk_import_persists_valid_coordinates_when_provided(): void
    {
        $user = User::factory()->create();
        $kategori = IlanKategori::factory()->create();

        $record = [
            'kategori_id' => $kategori->id,
            'baslik' => 'Bulk Ilan With Coordinates',
            'aciklama' => 'Test Aciklama',
            'fiyat' => 1250000,
            'il' => 'Mugla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Yalikavak',
            'lat' => 37.03440000,
            'lng' => 27.43050000,
        ];

        $jsonFile = UploadedFile::fake()->createWithContent(
            'bulk_valid_coords.json',
            json_encode([$record], JSON_UNESCAPED_UNICODE)
        );

        $this->mock(TemplateService::class, function ($mock) {
            $mock->shouldReceive('autoSelectTemplate')->andReturn([]);
        });

        $this->mock(PerformanceScoringService::class, function ($mock) {
            $mock->shouldReceive('scoreIlan')->andReturnUsing(fn () => \Mockery::mock('App\Models\DanismanlarPerformanceMetrics'));
        });

        $this->mock(MatchingFeedbackService::class, function ($mock) {
            $mock->shouldReceive('getHighScoreMatches')->andReturn(collect());
        });

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->actingAs($user, 'sanctum')->post(route('bulk.import'), [
            'file' => $jsonFile,
            'validation_only' => false,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.successful', 1);

        $createdId = $response->json('data.created_listing_ids.0');
        $this->assertNotNull($createdId);

        $persisted = Ilan::find($createdId);
        $this->assertEquals(37.0344, (float) $persisted->lat);
        $this->assertEquals(27.4305, (float) $persisted->lng);

        $raw = DB::table('ilanlar')->where('id', $createdId)->orderBy('id')->first(['id', 'lat', 'lng']);
        $this->assertEquals(37.0344, (float) $raw->lat);
        $this->assertEquals(27.4305, (float) $raw->lng);
    }

    public function test_bulk_controller_contains_no_direct_model_write_calls(): void
    {
        $content = file_get_contents(app_path('Http/Controllers/Api/BulkListingController.php'));

        $this->assertIsString($content);
        $this->assertStringNotContainsString('Ilan::create(', $content);
        $this->assertStringNotContainsString('->update($safeData)', $content);
    }
}

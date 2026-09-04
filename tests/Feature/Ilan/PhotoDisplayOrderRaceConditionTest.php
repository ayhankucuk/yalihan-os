<?php

namespace Tests\Feature\Ilan;

use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Services\Ilan\IlanPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * BACKLOG-8: Fotoğraf display_order Eşzamanlı Yarışı Test
 *
 * Verifies that IlanPhotoService::uploadPhotos() assigns sequential,
 * non-duplicate display_order values using max()+1 within a DB transaction.
 *
 * Old bug: count()+1 without transaction → concurrent uploads get same order.
 * Fix: max()+1 + DB::beginTransaction() + index-based increment.
 *
 * @group ilan
 * @group photo
 */
class PhotoDisplayOrderRaceConditionTest extends TestCase
{
    /**
     * Test: First photo upload gets display_order = 1
     */
    public function test_first_photo_gets_display_order_one(): void
    {
        Storage::fake('public');

        $ilan = Ilan::factory()->create();
        $photo = UploadedFile::fake()->image('test1.jpg');

        $service = app(IlanPhotoService::class);
        $result = $service->uploadPhotos($ilan, [$photo]);

        $this->assertTrue($result['success']);
        $fotograf = IlanFotografi::where('ilan_id', $ilan->id)->first();
        $this->assertNotNull($fotograf);
        $this->assertEquals(1, $fotograf->display_order);
    }

    /**
     * Test: Second upload batch gets display_order starting from max+1
     */
    public function test_second_upload_increments_from_max_order(): void
    {
        Storage::fake('public');

        $ilan = Ilan::factory()->create();

        // Pre-existing photos with display_order 1, 2, 3
        IlanFotografi::create([
            'ilan_id' => $ilan->id,
            'dosya_adi' => 'existing1.jpg',
            'dosya_yolu' => 'ilan-fotograflari/existing1.jpg',
            'display_order' => 1,
        ]);
        IlanFotografi::create([
            'ilan_id' => $ilan->id,
            'dosya_adi' => 'existing2.jpg',
            'dosya_yolu' => 'ilan-fotograflari/existing2.jpg',
            'display_order' => 2,
        ]);
        IlanFotografi::create([
            'ilan_id' => $ilan->id,
            'dosya_adi' => 'existing3.jpg',
            'dosya_yolu' => 'ilan-fotograflari/existing3.jpg',
            'display_order' => 3,
        ]);

        $newPhoto = UploadedFile::fake()->image('new1.jpg');

        $service = app(IlanPhotoService::class);
        $result = $service->uploadPhotos($ilan, [$newPhoto]);

        $this->assertTrue($result['success']);
        $newFotograf = IlanFotografi::where('ilan_id', $ilan->id)
            ->where('dosya_adi', 'new1.jpg')
            ->first();
        $this->assertNotNull($newFotograf);
        $this->assertEquals(4, $newFotograf->display_order);
    }

    /**
     * Test: Multiple photos in one batch get sequential display_order values
     */
    public function test_batch_upload_assigns_sequential_display_orders(): void
    {
        Storage::fake('public');

        $ilan = Ilan::factory()->create();

        $photos = [
            UploadedFile::fake()->image('batch1.jpg'),
            UploadedFile::fake()->image('batch2.jpg'),
            UploadedFile::fake()->image('batch3.jpg'),
        ];

        $service = app(IlanPhotoService::class);
        $result = $service->uploadPhotos($ilan, $photos);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['photos']);

        $fotograflar = IlanFotografi::where('ilan_id', $ilan->id)
            ->orderBy('display_order')
            ->get();

        $this->assertEquals(1, $fotograflar[0]->display_order);
        $this->assertEquals(2, $fotograflar[1]->display_order);
        $this->assertEquals(3, $fotograflar[2]->display_order);
    }

    /**
     * Test: Batch upload after existing photos continues from max+1
     */
    public function test_batch_after_existing_continues_from_max_plus_one(): void
    {
        Storage::fake('public');

        $ilan = Ilan::factory()->create();

        IlanFotografi::create([
            'ilan_id' => $ilan->id,
            'dosya_adi' => 'old.jpg',
            'dosya_yolu' => 'ilan-fotograflari/old.jpg',
            'display_order' => 5,
        ]);

        $photos = [
            UploadedFile::fake()->image('new1.jpg'),
            UploadedFile::fake()->image('new2.jpg'),
        ];

        $service = app(IlanPhotoService::class);
        $result = $service->uploadPhotos($ilan, $photos);

        $this->assertTrue($result['success']);

        $newPhotos = IlanFotografi::where('ilan_id', $ilan->id)
            ->where('dosya_adi', 'like', 'new%')
            ->orderBy('display_order')
            ->get();

        $this->assertEquals(6, $newPhotos[0]->display_order);
        $this->assertEquals(7, $newPhotos[1]->display_order);
    }

    /**
     * Test: No duplicate display_order values exist after multiple uploads
     */
    public function test_no_duplicate_display_orders_after_multiple_uploads(): void
    {
        Storage::fake('public');

        $ilan = Ilan::factory()->create();

        $service = app(IlanPhotoService::class);

        // First batch: 2 photos
        $service->uploadPhotos($ilan, [
            UploadedFile::fake()->image('a1.jpg'),
            UploadedFile::fake()->image('a2.jpg'),
        ]);

        // Second batch: 3 photos
        $service->uploadPhotos($ilan, [
            UploadedFile::fake()->image('b1.jpg'),
            UploadedFile::fake()->image('b2.jpg'),
            UploadedFile::fake()->image('b3.jpg'),
        ]);

        // Third batch: 1 photo
        $service->uploadPhotos($ilan, [
            UploadedFile::fake()->image('c1.jpg'),
        ]);

        $orders = IlanFotografi::where('ilan_id', $ilan->id)
            ->pluck('display_order')
            ->toArray();

        // 6 photos total, orders should be 1-6 with no duplicates
        $this->assertCount(6, $orders);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $orders);
        $this->assertEquals(count($orders), count(array_unique($orders)));
    }
}

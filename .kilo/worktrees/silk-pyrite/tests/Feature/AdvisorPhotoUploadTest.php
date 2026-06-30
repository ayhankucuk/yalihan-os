<?php

namespace Tests\Feature;

use App\Models\AdvisorPhoto;
use App\Models\Kisi;
use App\Modules\Auth\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvisorPhotoUploadTest extends TestCase
{

    protected $user;
    protected $advisor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and test user
        $role = Role::create(['name' => 'admin']);
        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        // Create test advisor (Kişi)
        $this->advisor = Kisi::create([
            'ad' => 'Test',
            'soyad' => 'Danışman',
            'email' => 'advisor@example.com',
            'telefon' => '05551234567',
            'kisi_tipi' => 'danisman',
            'aktiflik_durumu' => true,
        ]);

        Storage::fake('public');
    }

    /**
     * Test: Upload single photo
     */
    public function test_upload_advisor_photo()
    {
        $photo = UploadedFile::fake()->image('advisor.jpg', 640, 800);

        $response = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                ['photo' => $photo]
            );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'photo' => [
                    'id',
                    'quality_score',
                    'display_order',
                    'featured',
                ],
                'analysis' => [
                    'quality_score',
                    'suggestions',
                ],
            ],
        ]);

        // Verify photo saved to database
        $this->assertDatabaseHas('advisor_photos', [
            'kisi_id' => $this->advisor->id,
        ]);
    }

    /**
     * Test: Quality scoring algorithm
     */
    public function test_photo_quality_scoring()
    {
        // High quality photo (high res, portrait, good size)
        $highQualityPhoto = UploadedFile::fake()->image('high-quality.jpg', 1920, 2560);

        $response = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                ['photo' => $highQualityPhoto]
            );

        $response->assertStatus(201);

        // Quality score should be reasonable (>70 for fake small files)
        $this->assertGreaterThan(70, $response->json('data.analysis.quality_score'));
    }

    /**
     * Test: Featured photo auto-selection
     */
    public function test_featured_photo_auto_selection()
    {
        // Upload first photo (quality: ~50)
        $photo1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                ['photo' => $photo1]
            );

        // Upload second photo (higher quality: ~90)
        $photo2 = UploadedFile::fake()->image('photo2.jpg', 2560, 1920);
        $response = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                ['photo' => $photo2]
            );

        // Second photo should be featured (highest quality)
        $this->assertTrue($response->json('data.photo.featured'));

        // Verify in database
        $featuredPhoto = AdvisorPhoto::where('kisi_id', $this->advisor->id)
            ->where('featured', true)
            ->first();

        $this->assertNotNull($featuredPhoto);
        $this->assertGreaterThan(
            AdvisorPhoto::where('featured', false)->first()->quality_score,
            $featuredPhoto->quality_score
        );
    }

    /**
     * Test: List photos ordered by quality
     */
    public function test_list_photos_ordered()
    {
        // Upload multiple photos
        $photos = [];
        for ($i = 0; $i < 3; $i++) {
            $photos[] = UploadedFile::fake()->image("photo{$i}.jpg", 1200 + ($i * 400), 900);
        }

        foreach ($photos as $photo) {
            $this->actingAs($this->user)
                ->postJson(
                    "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                    ['photo' => $photo]
                );
        }

        // Get photos list
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/admin/advisors/{$this->advisor->id}/photos");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'display_order',
                    'quality_score',
                    'featured',
                ],
            ],
        ]);

        // Verify photos are ordered by quality (descending)
        $photoList = $response->json('data');
        for ($i = 1; $i < count($photoList); $i++) {
            $this->assertGreaterThanOrEqual(
                $photoList[$i]['quality_score'],
                $photoList[$i - 1]['quality_score'],
                'Photos should be ordered by quality descending'
            );
        }
    }

    /**
     * Test: Delete photo and reorder
     */
    public function test_delete_photo_and_reorder()
    {
        // Upload two photos
        $photo1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $photo2 = UploadedFile::fake()->image('photo2.jpg', 2560, 1920);

        $response1 = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                ['photo' => $photo1]
            );

        $response1->assertStatus(200);
        $this->assertNotNull($response1->json('data.photo.id'), 'Photo1 upload should return photo ID');

        $response2 = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                ['photo' => $photo2]
            );

        $response2->assertStatus(200);
        $this->assertNotNull($response2->json('data.photo.id'), 'Photo2 upload should return photo ID');

        $photoId1 = $response1->json('data.photo.id');
        $photoId2 = $response2->json('data.photo.id');

        // Verify photo2 is featured (higher quality)
        $photo2Model = AdvisorPhoto::find($photoId2);
        $this->assertNotNull($photo2Model, 'Photo2 should exist');
        $this->assertTrue(
            $photo2Model->featured,
            'Photo2 should be featured initially'
        );

        // Delete photo2
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/{$photoId2}"
            );

        $deleteResponse->assertStatus(200);

        // Verify photo2 deleted
        $this->assertDatabaseMissing('advisor_photos', ['id' => $photoId2]);

        // Verify photo1 now featured (only one left)
        $photo1Model = AdvisorPhoto::find($photoId1);
        $this->assertNotNull($photo1Model, 'Photo1 should exist after photo2 deleted');
        $this->assertTrue(
            $photo1Model->featured,
            'Photo1 should be featured after photo2 deleted'
        );
    }

    /**
     * Test: Invalid file format rejection
     */
    public function test_reject_invalid_file_format()
    {
        $invalidFile = UploadedFile::fake()->create('invalid.pdf', 100);

        $response = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                ['photo' => $invalidFile]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('photo');
    }

    /**
     * Test: File size limit
     */
    public function test_reject_oversized_file()
    {
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(15000); // > 10MB

        $response = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                ['photo' => $largeFile]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('photo');
    }

    /**
     * Test: Missing required field
     */
    public function test_reject_missing_photo()
    {
        $response = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/admin/advisors/{$this->advisor->id}/photos/upload",
                [] // Missing photo
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('photo');
    }
}

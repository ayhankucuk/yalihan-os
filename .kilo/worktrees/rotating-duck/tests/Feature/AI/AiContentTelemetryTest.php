<?php

namespace Tests\Feature\AI;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class AiContentTelemetryTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for auth
        $this->admin = User::factory()->create(['role_id' => 1]);
    }

    /** @test */
    public function it_logs_ai_title_suggestions()
    {
        $payload = [
            'kategori_id' => 1,
            'yayin_tipi_id' => 1,
            'feature_slug' => 'ai_title',
            'confidence' => 0.85,
            'source_tipi' => 'mixed',
            'aksiyon' => 'suggested',
            'neden' => 'AI başlık önerisi üretildi',
            'neden_detay' => ['text' => 'Bodrumda Satılık Villa'],
            'istek_id' => 'test_title_req_123'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/wizard/telemetry/feature-action', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ai_feature_usages', [
            'feature_slug' => 'ai_title',
            'aksiyon' => 'suggested',
            'istek_id' => 'test_title_req_123'
        ]);

        $entry = DB::table('ai_feature_usages')->where('istek_id', 'test_title_req_123')->first();
        $this->assertNotNull($entry->neden_detay);
        // JSON decode to handle unicode escapes properly
        $decodedNeden = json_decode($entry->neden_detay, true);
        $this->assertIsArray($decodedNeden);
        $this->assertEquals('Bodrumda Satılık Villa', $decodedNeden['text'] ?? '');
    }

    /** @test */
    public function it_logs_ai_title_user_selection()
    {
        $payload = [
            'kategori_id' => 1,
            'yayin_tipi_id' => 1,
            'feature_slug' => 'ai_title',
            'confidence' => 1.0,
            'source_tipi' => 'mixed',
            'aksiyon' => 'user_applied',
            'neden' => 'Kullanıcı AI başlığını seçti',
            'neden_detay' => ['text' => 'Seçilen Başlık']
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/wizard/telemetry/feature-action', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ai_feature_usages', [
            'feature_slug' => 'ai_title',
            'aksiyon' => 'user_applied'
        ]);
    }

    /** @test */
    public function it_logs_ai_description_telemetry()
    {
        $payload = [
            'kategori_id' => 1,
            'yayin_tipi_id' => 1,
            'feature_slug' => 'ai_description',
            'confidence' => 0.90,
            'source_tipi' => 'mixed',
            'aksiyon' => 'suggested',
            'neden' => 'AI açıklama üretildi',
            'neden_detay' => ['length' => 500],
            'istek_id' => 'test_desc_req_123'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/wizard/telemetry/feature-action', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ai_feature_usages', [
            'feature_slug' => 'ai_description',
            'aksiyon' => 'suggested',
            'istek_id' => 'test_desc_req_123'
        ]);

        $entry = DB::table('ai_feature_usages')->where('istek_id', 'test_desc_req_123')->first();
        $this->assertNotNull($entry->neden_detay);
        $this->assertStringContainsString('500', $entry->neden_detay);
    }
}

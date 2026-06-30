<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use App\Models\User;

/**
 * Telemetry Endpoint Tests — MVP (6 Core Fields)
 *
 * Contract:
 * - 200: Event logged
 * - 401: Auth required
 * - 422: Missing event / not in allowlist
 *
 * Core fields: event, trace_id, basarili, http_durum_kodu, duration_ms, context
 *
 * @group telemetry
 * @group observability
 * @group skip-until-migration-complete
 */
class TelemetryEndpointTest extends TestCase
{

    // ==================== Core Contract ====================

    /** @test */
    public function accepts_mvp_core_schema()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'          => 'wizard_fetch_context',
            'trace_id'       => 'test-trace-001',
            'basarili'       => true,
            'http_durum_kodu' => 200,
            'duration_ms'    => 245,
            'context'        => [
                'yayin_tipi_id'  => 5,
                'alt_kategori_id' => 15,
                'contextKey'     => '3-15-1',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function requires_event_field()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'basarili'    => true,
            'duration_ms' => 100,
            'context'     => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['event']);
    }

    /** @test */
    public function requires_authentication()
    {
        $response = $this->postJson('/admin/telemetry', [
            'event'       => 'wizard_fetch_context',
            'duration_ms' => 150,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function rejects_event_not_in_allowlist()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'       => 'arbitrary_unconfigured_event',
            'duration_ms' => 100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Event not in allowlist']);
    }

    // ==================== Event Types ====================

    /** @test */
    public function logs_window_error_event()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'          => 'window_error',
            'basarili'       => false,
            'http_durum_kodu' => 0,
            'duration_ms'    => 0,
            'context'        => [
                'hata_mesaji' => 'Uncaught ReferenceError: foo is not defined',
                'dosya_yolu'  => 'http://localhost/js/app.js',
                'satir_no'    => 123,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function logs_unhandled_promise_event()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'       => 'unhandled_promise',
            'basarili'    => false,
            'duration_ms' => 0,
            'context'     => [
                'red_nedeni' => 'Network timeout',
                'stack'      => 'Error stack...',
            ],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function logs_alpine_error_event()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'       => 'alpine_error',
            'basarili'    => false,
            'duration_ms' => 0,
            'context'     => [
                'hata_mesaji'    => 'Alpine init failed',
                'component_name' => 'x-wizard-step',
                'hata_tipi'      => 'ReferenceError',
            ],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function logs_ai_title_generation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'          => 'ai_title_generation',
            'trace_id'       => 'ai-trace-001',
            'basarili'       => true,
            'http_durum_kodu' => 200,
            'duration_ms'    => 2850,
            'context'        => [
                'token_count' => 450,
                'maliyet_usd' => 0.015,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function logs_photo_upload_complete()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'          => 'photo_upload_complete',
            'basarili'       => true,
            'http_durum_kodu' => 200,
            'duration_ms'    => 1850,
            'context'        => [
                'dosya_boyutu_kb' => 2048,
                'mime_tipi'       => 'image/jpeg',
            ],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function logs_form_validation_error()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'       => 'form_validation_error',
            'basarili'    => false,
            'duration_ms' => 0,
            'context'     => [
                'form_name'       => 'ilan-wizard-step-2',
                'gecersiz_alanlar' => ['baslik', 'fiyat'],
                'hata_sayisi'     => 2,
            ],
        ]);

        $response->assertStatus(200);
    }

    // ==================== Legacy Compat ====================

    /** @test */
    public function accepts_legacy_payload_format()
    {
        $user = User::factory()->create();

        // Old format: payload + istek_url + ts (should still work)
        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'     => 'wizard_fetch_context',
            'payload'   => ['duration_ms' => 245, 'basarili' => true],
            'istek_url' => 'http://localhost/admin/ilanlar/create',
            'ts'        => now()->timestamp,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function auto_generates_trace_id_when_absent()
    {
        $user = User::factory()->create();

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function ($channel, $data) {
            return $channel === 'frontend_event'
                && !empty($data['trace_id'])
                && strlen($data['trace_id']) === 36; // UUID v4 length
        });
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $response = $this->actingAs($user)->postJson('/admin/telemetry', [
            'event'       => 'wizard_fetch_context',
            'duration_ms' => 100,
        ]);

        $response->assertStatus(200);
    }
}

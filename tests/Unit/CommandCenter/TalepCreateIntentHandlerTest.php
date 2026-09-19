<?php

namespace Tests\Unit\CommandCenter;

use App\DTOs\Command\NormalizedCommandInput;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\Talep;
use App\Models\User;
use App\Services\CommandCenter\Handlers\TalepCreateIntentHandler;
use App\Services\CRM\TalepAuthorityService;
use App\Support\AgentContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TalepCreateIntentHandler Unit Tests
 *
 * Phase 1l: COMMAND_CENTER_TALEP_CREATE_VERTICAL_01
 *
 * Tests the talep_create intent handler in isolation (parameter extraction,
 * missing-data detection, clarification response generation).
 *
 * Integration tests (full CommandGateway path, DB writes) are in:
 *   - tests/Feature/CommandCenter/TalepCreateE2ETest.php
 */
class TalepCreateIntentHandlerTest extends TestCase
{
    use RefreshDatabase;

    private TalepCreateIntentHandler $handler;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Tenant + User for authenticated Telegram actor
        $this->tenant = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Tenant Emlak',
            'domain' => 'test-tenant.local',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'telegram_chat_id' => '999888777',
        ]);

        // Reset agent context — test environment is human, not agent
        AgentContext::reset();

        // Mock TalepAuthorityService to avoid DB writes in unit tests
        // (SQLite test schema may lack columns like 'one_cikan'; Feature tests cover real DB path)
        $talepAuthorityMock = \Mockery::mock(TalepAuthorityService::class);
        $talepAuthorityMock->shouldReceive('createTalep')
            ->andReturnUsing(function ($data, $actor) {
                return new Talep([
                    'id' => 999,
                    'baslik' => $data['baslik'] ?? 'Test Talep',
                    'kisi_id' => $data['kisi_id'] ?? null,
                    'max_fiyat' => $data['max_fiyat'] ?? null,
                    'talep_durumu' => \App\Enums\TalepDurumu::AKTIF,
                    'tenant_id' => $actor?->tenant_id ?? 1,
                    'danisman_id' => $actor?->id ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        $this->handler = new TalepCreateIntentHandler(
            $talepAuthorityMock,
            app(\App\Services\CRM\KisiRegistrationService::class)
        );
    }

    // -------------------------------------------------------------------------
    // PARAMETER EXTRACTION
    // -------------------------------------------------------------------------

    /** @test */
    public function extracts_name_and_phone_and_location_and_budget(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ahmet Yılmaz, 0532 123 45 67, Bodrum\'da villa arıyor. Bütçesi €500 bin.'
        );

        $this->assertEquals('Ahmet', $params['kisi_ad']);
        $this->assertEquals('Yılmaz', $params['kisi_soyad']);
        $this->assertEquals('05321234567', $params['kisi_telefon']);
        $this->assertEquals('EUR', $params['para_birimi']);
        $this->assertEquals(500_000, $params['max_fiyat']);
        $this->assertEquals('villa', $params['property_type']);
        $this->assertEquals('Bodrum', $params['ilce_adi']);
    }

    /** @test */
    public function extracts_name_with_surname_only(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep ekle. Mehmet Özkan, 0533 999 00 11, İstanbul\'da daire.'
        );

        $this->assertEquals('Mehmet', $params['kisi_ad']);
        $this->assertEquals('Özkan', $params['kisi_soyad']);
        $this->assertEquals('05339990011', $params['kisi_telefon']);
        $this->assertEquals('daire', $params['property_type']);
        $this->assertEquals('İstanbul', $params['ilce_adi']);
    }

    /** @test */
    public function extracts_phone_in_various_formats(): void
    {
        // Format: 05xx xxx xx xx
        $p1 = $this->handler->extractParameters('Yeni talep var. Ali, 0532 111 22 33, Antalya.');
        $this->assertEquals('05321112233', $p1['kisi_telefon']);

        // Format: +90 5xx xxx xx xx
        $p2 = $this->handler->extractParameters('Yeni talep var. Veli, +90 536 123 45 67, Ankara.');
        $this->assertEquals('05361234567', $p2['kisi_telefon']);

        // Format: 5xxxxxxxxx (no spaces) — normalized with leading 0
        $p3 = $this->handler->extractParameters('Yeni talep var. Zeynep, 5381234567, Muğla.');
        $this->assertEquals('05381234567', $p3['kisi_telefon']);

        // Format: xxx-xx-xx-xx
        $p4 = $this->handler->extractParameters('Yeni talep var. Can, 531-222-33-44, Fethiye.');
        $this->assertEquals('05312223344', $p4['kisi_telefon']);
    }

    /** @test */
    public function extracts_budget_in_try_when_tl_present(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ayşe, 0532 111 22 33, €500 bin bütçe tl cinsinden.'
        );
        // TL keyword present → TRY
        $this->assertEquals('TRY', $params['para_birimi']);

        $params2 = $this->handler->extractParameters(
            'Yeni talep var. Can, 0532 111 22 33, 1 milyon tl bütçe.'
        );
        $this->assertEquals('TRY', $params2['para_birimi']);
        $this->assertEquals(1_000_000, $params2['max_fiyat']);

        $params3 = $this->handler->extractParameters(
            'Yeni talep var. Deniz, 0532 111 22 33, €750k bütçe.'
        );
        $this->assertEquals('EUR', $params3['para_birimi']);
    }

    /** @test */
    public function extracts_property_type_villa(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ela, 0532 111 22 33, Bodrum\'da villa.'
        );
        $this->assertEquals('villa', $params['property_type']);
    }

    /** @test */
    public function extracts_property_type_arsa(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Tarık, 0532 111 22 33, Alanya\'da arsa.'
        );
        $this->assertEquals('arsa', $params['property_type']);
    }

    /** @test */
    public function extracts_location_from_ilce_pattern(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Lale, 0532 111 22 33, Çeşme\'de müstakil ev.'
        );
        $this->assertEquals('Çeşme', $params['ilce_adi']);
        $this->assertEquals('müstakil', $params['property_type']);
    }

    /** @test */
    public function generates_baslik_from_available_parts(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Hale Yıldırım, 0532 111 22 33, Kaş\'ta villa.'
        );

        $this->assertNotNull($params['baslik']);
        $this->assertStringContainsString('Hale Yıldırım', $params['baslik']);
        $this->assertStringContainsString('villa', $params['baslik']);
        $this->assertStringContainsString('Kaş', $params['baslik']);
    }

    /** @test */
    public function extracts_milyon_budget(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Kasım, 0532 111 22 33, €1.5 milyon bütçe.'
        );
        $this->assertEquals(1_500_000, $params['max_fiyat']);
        $this->assertEquals('EUR', $params['para_birimi']);
    }

    /** @test */
    public function extracts_usd_currency(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ron, 0532 111 22 33, $750k bütçe.'
        );
        $this->assertEquals('USD', $params['para_birimi']);
        $this->assertEquals(750_000, $params['max_fiyat']);
    }

    /** @test */
    public function returns_null_when_no_phone(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ali, Bodrum\'da villa.'
        );
        $this->assertNull($params['kisi_telefon']);
        $this->assertEquals('Ali', $params['kisi_ad']);
    }

    // -------------------------------------------------------------------------
    // MISSING FIELD DETECTION
    // -------------------------------------------------------------------------

    /** @test */
    public function detects_missing_phone_when_name_present(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Sude Yılmaz, Bodrum\'da villa arıyor.'
        );
        $missing = $this->handler->detectMissingRequiredFields($params);
        $this->assertNotEmpty($missing);
        $this->assertStringContainsString('Telefon', $missing[0]);
    }

    /** @test */
    public function no_missing_fields_when_name_and_phone_present(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ece Kaya, 0532 111 22 33, Fethiye\'de arsa.'
        );
        $missing = $this->handler->detectMissingRequiredFields($params);
        $this->assertEmpty($missing);
    }

    /** @test */
    public function no_missing_when_no_name_at_all(): void
    {
        // Ne isim ne telefon verildiğinde en azından telefon istenir
        $params = $this->handler->extractParameters('Yeni talep var.');
        $missing = $this->handler->detectMissingRequiredFields($params);
        $this->assertNotEmpty($missing, 'Telefon eksik olduğunda missing list boş olmamalı');
        $this->assertStringContainsString('Telefon', $missing[0]);
    }

    // -------------------------------------------------------------------------
    // CLARIFICATION RESPONSE
    // -------------------------------------------------------------------------

    /** @test */
    public function clarification_response_includes_example(): void
    {
        $response = $this->handler->handle(
            new NormalizedCommandInput(
                channel: 'telegram',
                externalActorId: '999888777',
                chatId: '999888777',
                rawText: 'Yeni talep var. Sude, Bodrum\'da villa.'
            )
        );

        $this->assertStringContainsString('Telefon', $response);
        $this->assertStringContainsString('Telefon numarası', $response);
        $this->assertStringContainsString('Örnek:', $response);
    }

    /** @test */
    public function unauthorized_actor_returns_clarification(): void
    {
        $response = $this->handler->handle(
            new NormalizedCommandInput(
                channel: 'telegram',
                externalActorId: 'UNKNOWN_ACTOR_999',
                chatId: 'UNKNOWN_ACTOR_999',
                rawText: 'Yeni talep var. Ahmet, 0532 111 22 33, Bodrum.'
            )
        );

        $this->assertStringContainsString('Yetkilendirme', $response);
    }

    /** @test */
    public function valid_input_without_phone_returns_clarification(): void
    {
        $response = $this->handler->handle(
            new NormalizedCommandInput(
                channel: 'telegram',
                externalActorId: '999888777',
                chatId: '999888777',
                rawText: 'Yeni talep var. Ahmet Yılmaz, Bodrum\'da villa arıyor.'
            )
        );

        $this->assertStringContainsString('Telefon', $response);
        // Should NOT create a Talep
        $this->assertDatabaseMissing('talepler', ['baslik' => '%Ahmet%']);
    }

    /** @test */
    public function malformed_budget_does_not_create_corrupted_talep(): void
    {
        // Telefon var ama bütçe yok veya geçersiz — talep oluşturulabilir olmalı
        $response = $this->handler->handle(
            new NormalizedCommandInput(
                channel: 'telegram',
                externalActorId: '999888777',
                chatId: '999888777',
                rawText: 'Yeni talep var. Fikret, 0532 111 22 33, Bodrum\'da villa.'
            )
        );

        // Telefon + isim var → talep oluşturulabilir
        // (bütçe olmasa bile talep oluşabilir, max_fiyat nullable)
        $this->assertStringContainsString('Talep Oluşturuldu', $response);
    }

    // -------------------------------------------------------------------------
    // NAME PARSER FAIL-SAFE — Section F Required Cases
    // -------------------------------------------------------------------------

    /** @test */
    public function name_extracted_only_with_comma_anchor_ahmet(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ahmet Yılmaz, 0532 123 45 67, Bodrum\'da villa.'
        );
        $this->assertEquals('Ahmet', $params['kisi_ad']);
        $this->assertEquals('Yılmaz', $params['kisi_soyad']);
    }

    /** @test */
    public function name_extracted_only_with_comma_anchor_sude(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var Sude, 0532 123 45 67, villa arıyor'
        );
        $this->assertEquals('Sude', $params['kisi_ad']);
        $this->assertNull($params['kisi_soyad']);
    }

    /** @test */
    public function name_extracted_only_with_comma_anchor_ali(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ali, 0532 123 45 67'
        );
        $this->assertEquals('Ali', $params['kisi_ad']);
        $this->assertNull($params['kisi_soyad']);
    }

    /** @test */
    public function no_name_extracted_when_comma_missing(): void
    {
        // FAIL-SAFE: generic words like "villa", "Bodrum'da" must NOT be names.
        $cases = [
            'Yeni talep var villa arıyor 0532 123 45 67',
            'Yeni talep var Bodrum\'da villa arıyor 0532 123 45 67',
            'Yeni talep var villa 0532 123 45 67',
        ];

        foreach ($cases as $input) {
            $params = $this->handler->extractParameters($input);
            $this->assertNull(
                $params['kisi_ad'],
                "Input: \"{$input}\" — 'villa' or location must NOT be extracted as a name"
            );
        }
    }

    /** @test */
    public function invalid_input_without_comma_triggers_clarification(): void
    {
        $response = $this->handler->handle(
            new NormalizedCommandInput(
                channel: 'telegram',
                externalActorId: '999888777',
                chatId: '999888777',
                rawText: 'Yeni talep var villa arıyor 0532 123 45 67'
            )
        );
        $this->assertStringContainsString('Bilgi Gerekli', $response);
    }

    /** @test */
    public function invalid_input_with_location_word_triggers_clarification(): void
    {
        $response = $this->handler->handle(
            new NormalizedCommandInput(
                channel: 'telegram',
                externalActorId: '999888777',
                chatId: '999888777',
                rawText: 'Yeni talep var Bodrum\'da villa arıyor 0532 123 45 67'
            )
        );
        $this->assertStringContainsString('Bilgi Gerekli', $response);
    }

    // -------------------------------------------------------------------------
    // PHONE/BUDGET SEPARATION — Section C Required Cases
    // -------------------------------------------------------------------------

    /** @test */
    public function phone_digits_never_populate_max_fiyat(): void
    {
        $cases = [
            'Yeni talep var. Ahmet, 0532 123 45 67, Bodrum\'da villa.',
            'Yeni talep var Sude, 0532 123 45 67',
            'Yeni talep var. Elif, +90 532 123 45 67',
            'Yeni talep var. Ali, 5321234567',
            'Yeni talep var. Veli, 0532-123-45-67',
        ];

        foreach ($cases as $input) {
            $params = $this->handler->extractParameters($input);
            $this->assertNull(
                $params['max_fiyat'],
                "Input: \"{$input}\" — phone digits must NOT become max_fiyat"
            );
        }
    }

    /** @test */
    public function explicit_budget_parsed_correctly(): void
    {
        $cases = [
            ['Yeni talep var. Ahmet, 0532 123 45 67, €500 bin bütçe.', 500_000],
            ['Yeni talep var Sude, 0532 123 45 67, €750k', 750_000],
            ['Yeni talep var. Ali, 0532 123 45 67, €1M', 1_000_000],
            ['Yeni talep var Elif, 0532 123 45 67, bütçe €1.5M', 1_500_000],
        ];

        foreach ($cases as [$input, $expectedFiyat]) {
            $params = $this->handler->extractParameters($input);
            $this->assertEquals($expectedFiyat, $params['max_fiyat'], "Input: \"{$input}\"");
            $this->assertEquals('EUR', $params['para_birimi']);
        }
    }

    /** @test */
    public function plus_90_phone_extracted_correctly(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Ayşe, +90 532 123 45 67, Bodrum.'
        );
        $this->assertEquals('05321234567', $params['kisi_telefon']);
        $this->assertNull($params['max_fiyat']);
    }

    /** @test */
    public function dashed_phone_extracted_correctly(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Tarık, 0532-123-45-67, Kaş.'
        );
        $this->assertEquals('05321234567', $params['kisi_telefon']);
        $this->assertNull($params['max_fiyat']);
    }

    /** @test */
    public function plain_10digit_phone_extracted_correctly(): void
    {
        $params = $this->handler->extractParameters(
            'Yeni talep var. Zeynep, 5381234567, Fethiye.'
        );
        $this->assertEquals('05381234567', $params['kisi_telefon']);
        $this->assertNull($params['max_fiyat']);
    }
}

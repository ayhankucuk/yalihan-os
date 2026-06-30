<?php

namespace Tests\Unit\AI;

use App\Services\AI\Validation\ListingAIResponseValidator;
use App\Domain\AI\Exceptions\InvalidAIResponseException;
use Tests\SimpleTestCase;

class ListingAIResponseValidatorTest extends SimpleTestCase
{
    protected ListingAIResponseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ListingAIResponseValidator();
    }

    public function test_it_validates_valid_json()
    {
        $json = file_get_contents(base_path('tests/Fixtures/AI/listing_generation_valid.json'));
        $dto = $this->validator->validate($json);

        $this->assertEquals('Satılık', $dto->tip);
    }

    public function test_it_heals_legacy_type_field()
    {
        $json = json_encode([
            'baslik' => 'Test',
            'aciklama' => 'Test desc',
            'type' => 'Kiralık',
            'kategori' => 'Konut'
        ]);

        $dto = $this->validator->validate($json);
        $this->assertEquals('Kiralık', $dto->tip);
    }

    public function test_it_strips_unknown_fields_gracefully()
    {
        // B-008: LLM çıktıları gürültülüdür — bilinmeyen alanlar exception yerine
        // sessizce kaldırılır, DTO izin verilen alanlarla doldurulur.
        $json = json_encode([
            'baslik' => 'Test',
            'aciklama' => 'Test',
            'tip' => 'Satılık',
            'kategori' => 'Konut',
            'unknown_hacker_field' => 'danger'
        ]);

        $dto = $this->validator->validate($json);

        // Bilinmeyen alan DTO'ya geçmemeli
        $this->assertEquals('Test', $dto->baslik);
        $this->assertEquals('Satılık', $dto->tip);
        // DTO sadece izin verilen alanları içermeli
        $this->assertFalse(property_exists($dto, 'unknown_hacker_field'));
    }

    public function test_it_rejects_missing_baslik()
    {
        $this->expectException(InvalidAIResponseException::class);
        $this->expectExceptionMessage('AI_EMPTY_REQUIRED_FIELD: baslik');

        $json = json_encode([
            'aciklama' => 'Test',
            'tip' => 'Satılık',
            'kategori' => 'Konut'
        ]);

        $this->validator->validate($json);
    }
}

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

    public function test_it_rejects_unknown_fields()
    {
        $this->expectException(InvalidAIResponseException::class);
        $this->expectExceptionMessage('AI_UNKNOWN_FIELD');

        $json = json_encode([
            'baslik' => 'Test',
            'aciklama' => 'Test',
            'tip' => 'Satılık',
            'kategori' => 'Konut',
            'unknown_hacker_field' => 'danger'
        ]);

        $this->validator->validate($json);
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

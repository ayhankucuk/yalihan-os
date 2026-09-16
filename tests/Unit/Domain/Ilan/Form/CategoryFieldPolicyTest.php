<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ilan\Form;

use App\Domain\Ilan\Policies\CategoryFieldPolicy;
use App\Domain\Ilan\ValueObjects\FieldKey;
use PHPUnit\Framework\TestCase;

class CategoryFieldPolicyTest extends TestCase
{
    private CategoryFieldPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CategoryFieldPolicy();
    }

    public function test_resolves_konut_satilik_definitions(): void
    {
        $fields = $this->policy->getFieldDefinitions('konut', 'satilik');

        $this->assertNotEmpty($fields);
        $this->assertTrue($this->policy->isRequired('konut', 'satilik', FieldKey::fromString('oda_sayisi')));
        $this->assertTrue($this->policy->isRequired('konut', 'satilik', FieldKey::fromString('net_m2')));
        $this->assertFalse($this->policy->isRequired('konut', 'satilik', FieldKey::fromString('brut_m2')));
    }

    public function test_normalizes_category_aliases(): void
    {
        $fieldsArsa = $this->policy->getFieldDefinitions('arsa', 'satilik');
        $fieldsArsaArazi = $this->policy->getFieldDefinitions('arsa-arazi', 'satilik');

        $this->assertCount(count($fieldsArsa), $fieldsArsaArazi);
        $this->assertTrue($this->policy->isRequired('arsa', 'satilik', FieldKey::fromString('imar_durumu')));
    }

    public function test_is_allowed_check(): void
    {
        $this->assertTrue($this->policy->isAllowed('konut', 'satilik', FieldKey::fromString('oda_sayisi')));
        $this->assertTrue($this->policy->isAllowed('arsa-arazi', 'satilik', FieldKey::fromString('imar_durumu')));
        $this->assertFalse($this->policy->isAllowed('arsa-arazi', 'satilik', FieldKey::fromString('oda_sayisi')));
    }

    public function test_validates_listing_payload_missing_required(): void
    {
        $data = [
            'oda_sayisi' => '3+1',
            // net_m2 missing
        ];

        $errors = $this->policy->validateListingData('konut', 'satilik', $data);

        $this->assertArrayHasKey('net_m2', $errors);
        $this->assertArrayHasKey('banyo_sayisi', $errors);
        $this->assertArrayNotHasKey('oda_sayisi', $errors);
    }

    public function test_validates_listing_payload_invalid_type_or_range(): void
    {
        $data = [
            'oda_sayisi' => 'invalid_room_format',
            'banyo_sayisi' => '1',
            'net_m2' => 5, // below min 10
            'bulundugu_kat' => '1',
        ];

        $errors = $this->policy->validateListingData('konut', 'satilik', $data);

        $this->assertArrayHasKey('oda_sayisi', $errors);
        $this->assertArrayHasKey('net_m2', $errors);
        $this->assertArrayNotHasKey('banyo_sayisi', $errors);
        $this->assertArrayNotHasKey('bulundugu_kat', $errors);
    }

    public function test_validates_listing_payload_success(): void
    {
        $data = [
            'oda_sayisi' => '3+1',
            'banyo_sayisi' => '2',
            'net_m2' => 150,
            'brut_m2' => 180,
            'bulundugu_kat' => '2',
            'bina_yasi' => '1-5',
            'tapu_durumu' => 'Kat Mülkiyetli',
        ];

        $errors = $this->policy->validateListingData('konut', 'satilik', $data);

        $this->assertEmpty($errors);
    }
}

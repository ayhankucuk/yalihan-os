<?php

declare(strict_types=1);

namespace Tests\Feature\Wizard;

use App\Application\Ilan\Services\DomainFieldResolverAdapter;
use App\Domain\Ilan\Policies\CategoryFieldPolicy;
use App\Domain\Ilan\ValueObjects\FieldKey;
use App\Services\Wizard\FieldEngine\FieldDefinition as LegacyFieldDefinition;
use App\Services\Wizard\FieldEngine\FieldResolver;
use Tests\TestCase;

/**
 * FormFieldContractParityTest — Comprehensive Parity & Strangler Fig Bridge Test
 *
 * FORM-CONTRACT-BRIDGE-01
 */
class FormFieldContractParityTest extends TestCase
{
    private DomainFieldResolverAdapter $adapter;
    private CategoryFieldPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CategoryFieldPolicy();
        $this->adapter = new DomainFieldResolverAdapter($this->policy);
    }

    /**
     * Test 1: Adapter transforms domain policy definitions into legacy FieldDefinition DTOs.
     */
    public function test_adapter_transforms_domain_policy_to_legacy_field_definitions(): void
    {
        $fields = $this->adapter->resolveBySlug('konut', 'satilik');

        $this->assertNotEmpty($fields);
        $this->assertContainsOnlyInstancesOf(LegacyFieldDefinition::class, $fields);

        $slugs = array_map(fn(LegacyFieldDefinition $f) => $f->slug, $fields);
        $this->assertContains('oda_sayisi', $slugs);
        $this->assertContains('net_m2', $slugs);
        $this->assertContains('bulundugu_kat', $slugs);
    }

    /**
     * Test 2: Full parity envelope verification across critical fields.
     */
    public function test_parity_envelope_structure_for_konut_satilik(): void
    {
        $fields = $this->adapter->resolveBySlug('konut', 'satilik');
        $fieldMap = [];
        foreach ($fields as $f) {
            $fieldMap[$f->slug] = $f;
        }

        // 1. Check oda_sayisi envelope & option slug normalization
        $this->assertArrayHasKey('oda_sayisi', $fieldMap);
        $oda = $fieldMap['oda_sayisi'];
        $this->assertSame('select', $oda->type);
        $this->assertTrue($oda->required);
        $this->assertNotEmpty($oda->options);
        $this->assertSame('11', $oda->options[1]['value']);
        $this->assertSame('1+1', $oda->options[1]['label']);
        $this->assertTrue($oda->searchable);
        $this->assertTrue($oda->showInCard);
        $this->assertTrue($oda->aiAutoFill);

        // 2. Check net_m2 envelope
        $this->assertArrayHasKey('net_m2', $fieldMap);
        $netM2 = $fieldMap['net_m2'];
        $this->assertSame('number', $netM2->type);
        $this->assertTrue($netM2->required);
        $this->assertSame('m²', $netM2->unit);
        $this->assertSame(10, $netM2->min);
        $this->assertSame(2000, $netM2->max);
        $this->assertSame(1, $netM2->step);

        // 3. Check brut_m2 envelope
        $this->assertArrayHasKey('brut_m2', $fieldMap);
        $brutM2 = $fieldMap['brut_m2'];
        $this->assertSame('number', $brutM2->type);
        $this->assertFalse($brutM2->required);
        $this->assertSame(10, $brutM2->min);
        $this->assertSame(3000, $brutM2->max);
    }

    /**
     * Test 3: FieldResolver Strangler Fig runtime switch integration.
     */
    public function test_field_resolver_runtime_switch_delegation(): void
    {
        $resolver = app(FieldResolver::class);

        // When flag is false (default)
        config(['feature-flags.use_domain_form_policy' => false]);
        $defaultResult = $resolver->resolveBySlug('konut', 'satilik', 1);
        $this->assertIsArray($defaultResult);

        // When flag is true (Strangler Fig enabled)
        config(['feature-flags.use_domain_form_policy' => true]);
        $domainResult = $resolver->resolveBySlug('konut', 'satilik', 1);
        $adapterResult = $this->adapter->resolveBySlug('konut', 'satilik');

        $this->assertNotEmpty($domainResult);
        $this->assertCount(count($adapterResult), $domainResult);
        $this->assertSame(
            array_map(fn($f) => $f->slug, $adapterResult),
            array_map(fn($f) => $f->slug, $domainResult)
        );

        // Revert flag
        config(['feature-flags.use_domain_form_policy' => false]);
    }

    /**
     * Test 4: Kebab-case legacy aliases normalize to canonical snake_case.
     */
    public function test_legacy_kebab_case_aliases_are_normalized(): void
    {
        $k1 = FieldKey::fromString('oda-sayisi');
        $this->assertSame('oda_sayisi', $k1->value());

        $k2 = FieldKey::fromString('brut-metrekare');
        $this->assertSame('brut_m2', $k2->value());

        $k3 = FieldKey::fromString('bina-yasi');
        $this->assertSame('bina_yasi', $k3->value());

        $k4 = FieldKey::fromString('tapu-durumu');
        $this->assertSame('tapu_durumu', $k4->value());

        $k5 = FieldKey::fromString('denize-mesafe');
        $this->assertSame('denize_mesafe_m', $k5->value());
    }

    /**
     * Test 5: Category alias normalization in adapter.
     */
    public function test_category_alias_normalization_in_adapter(): void
    {
        $fieldsArsa = $this->adapter->resolveBySlug('arsa', 'satilik');
        $fieldsArsaArazi = $this->adapter->resolveBySlug('arsa-arazi', 'satilik');

        $this->assertCount(count($fieldsArsa), $fieldsArsaArazi);
        $this->assertSame(
            array_map(fn($f) => $f->slug, $fieldsArsa),
            array_map(fn($f) => $f->slug, $fieldsArsaArazi)
        );

        $fieldsYazlik = $this->adapter->resolveBySlug('yazlik', 'gunluk');
        $fieldsYazlikKiralama = $this->adapter->resolveBySlug('yazlik-kiralama', 'gunluk');
        $this->assertCount(count($fieldsYazlik), $fieldsYazlikKiralama);
    }

    /**
     * Test 6: Arsa & Arazi envelope validation.
     */
    public function test_arsa_arazi_envelope(): void
    {
        $fields = $this->adapter->resolveBySlug('arsa-arazi', 'satilik');
        $fieldMap = [];
        foreach ($fields as $f) {
            $fieldMap[$f->slug] = $f;
        }

        $this->assertArrayHasKey('net_m2', $fieldMap);
        $this->assertArrayHasKey('imar_durumu', $fieldMap);
        $this->assertArrayHasKey('ada_no', $fieldMap);
        $this->assertArrayHasKey('parsel_no', $fieldMap);
        $this->assertArrayHasKey('kaks', $fieldMap);
        $this->assertArrayHasKey('taks', $fieldMap);
        $this->assertArrayHasKey('yola_cephe', $fieldMap);

        $this->assertSame('select', $fieldMap['imar_durumu']->type);
        $this->assertTrue($fieldMap['imar_durumu']->required);
        $this->assertSame('boolean', $fieldMap['yola_cephe']->type);
    }

    /**
     * Test 7: Feature flag defaults to false and can be toggled.
     */
    public function test_feature_flag_defaults_to_false(): void
    {
        $this->assertFalse(config('feature-flags.use_domain_form_policy'));

        config(['feature-flags.use_domain_form_policy' => true]);
        $this->assertTrue(config('feature-flags.use_domain_form_policy'));

        config(['feature-flags.use_domain_form_policy' => false]);
    }

    /**
     * Test 8: Policy data validation engine.
     */
    public function test_policy_validates_payload_data_accurately(): void
    {
        // Missing required net_m2 in arsa
        $invalidArsaData = [
            'imar_durumu' => 'Konut İmarlı',
        ];
        $errors = $this->policy->validateListingData('arsa-arazi', 'satilik', $invalidArsaData);
        $this->assertArrayHasKey('net_m2', $errors);

        // Valid arsa data
        $validArsaData = [
            'net_m2' => 750,
            'imar_durumu' => 'Konut İmarlı',
            'ada_no' => '120',
            'parsel_no' => '5',
        ];
        $errors = $this->policy->validateListingData('arsa-arazi', 'satilik', $validArsaData);
        $this->assertEmpty($errors);
    }
}

<?php

namespace Tests\Feature\Crud;

use App\Models\Feature;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Regression: IlanCrudService::syncFeatures() data type normalization.
 *
 * Property Engine sends schema option values (select strings, multiselect
 * arrays, boolean strings) which do not match the ilanlar column types.
 * Without normalization MySQL strict mode raises 500:
 *   - 'esyali'    → 'Evet'/'Hayır' (string)  → tinyint(1) boolean
 *   - 'bina-yasi' → '1-5 Yıl' (range string) → year integer
 *   - 'isitma'    → ['Doğalgaz','Klima'] (array) → varchar string
 *
 * This test drives the canonical dynamic-field flow (features[] payload)
 * WITHOUT the top-level workaround and asserts the DB columns are written
 * with DB-native values.
 */
class IlanCrudFeatureNormalizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
    }

    private function makeVillaCategory(): IlanKategori
    {
        return IlanKategori::factory()->create([
            'name' => 'Villa',
            'slug' => 'villa',
            'seviye' => 1,
            'aktiflik_durumu' => true,
        ]);
    }

    private function makeFeature(string $slug, string $type): Feature
    {
        $category = \App\Models\FeatureCategory::firstOrCreate(
            ['slug' => 'test-category'],
            ['name' => 'Test Category', 'aktiflik_durumu' => true, 'display_order' => 0]
        );

        return Feature::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => ucfirst(str_replace('-', ' ', $slug)),
                'type' => $type,
                'feature_category_id' => $category->id,
                'aktiflik_durumu' => true,
                'display_order' => 0,
            ]
        );
    }

    private function baseData(IlanKategori $villa, array $overrides = []): array
    {
        return array_merge([
            'baslik' => 'Bodrum Satılık Villa',
            'aciklama' => 'Deniz manzaralı lüks villa.',
            'fiyat' => 2500000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'taslak',
            'ana_kategori_id' => $villa->id,
            'alt_kategori_id' => $villa->id,
            'yayin_tipi_id' => null,
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
        ], $overrides);
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_normalizes_esyali_boolean(): void
    {
        $villa = $this->makeVillaCategory();
        $this->makeFeature('esyali', 'select');

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($villa, [
                'features' => [
                    'esyali' => 'Evet',
                ],
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertTrue($fresh->esyali, 'esyali "Evet" must normalize to boolean true.');
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
            'esyali' => 1,
        ]);
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_normalizes_esyali_hayir_to_false(): void
    {
        $villa = $this->makeVillaCategory();
        $this->makeFeature('esyali', 'select');

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($villa, [
                'features' => [
                    'esyali' => 'Hayır',
                ],
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertFalse($fresh->esyali, 'esyali "Hayır" must normalize to boolean false.');
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
            'esyali' => 0,
        ]);
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_normalizes_bina_yasi_range_to_year(): void
    {
        $villa = $this->makeVillaCategory();
        $this->makeFeature('bina-yasi', 'select');

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($villa, [
                'features' => [
                    'bina-yasi' => '6-10 Yıl',
                ],
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertEquals(10, $fresh->bina_yasi, 'bina-yasi range "6-10 Yıl" must normalize to year 10.');
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
            'bina_yasi' => 10,
        ]);
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_normalizes_bina_yasi_plain_numeric(): void
    {
        $villa = $this->makeVillaCategory();
        $this->makeFeature('bina-yasi', 'select');

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($villa, [
                'features' => [
                    'bina-yasi' => '5',
                ],
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertEquals(5, $fresh->bina_yasi, 'plain numeric bina-yasi must pass through.');
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_normalizes_isitma_array_to_string(): void
    {
        $villa = $this->makeVillaCategory();
        $this->makeFeature('isitma', 'multiselect');

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($villa, [
                'features' => [
                    'isitma' => ['Doğalgaz', 'Klima'],
                ],
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertEquals(
            'Doğalgaz, Klima',
            $fresh->isitma,
            'multiselect isitma must be joined into a string for the varchar column.'
        );
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
            'isitma' => 'Doğalgaz, Klima',
        ]);
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_normalizes_numeric_columns(): void
    {
        $villa = $this->makeVillaCategory();
        $this->makeFeature('brut-metrekare', 'number');
        $this->makeFeature('oda-sayisi', 'text');

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($villa, [
                'features' => [
                    'brut-metrekare' => '250',
                    'oda-sayisi' => '4',
                ],
            ])
        );

        $fresh = $ilan->fresh();
        $this->assertEquals(250.0, $fresh->brut_m2, 'brut-metrekare must normalize to float.');
        $this->assertEquals(4, $fresh->oda_sayisi, 'oda-sayisi must normalize to int.');
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_persists_unmapped_slugs_to_pivot(): void
    {
        $villa = $this->makeVillaCategory();
        $feature = $this->makeFeature('havuz', 'boolean');

        $ilan = app(IlanCrudService::class)->store(
            $this->baseData($villa, [
                'features' => [
                    'havuz' => '1',
                ],
            ])
        );

        $this->assertDatabaseHas('ilan_feature', [
            'ilan_id' => $ilan->id,
            'feature_id' => $feature->id,
            'value' => '1',
        ]);
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_rejects_unknown_boolean_without_persisting_listing(): void
    {
        $villa = $this->makeVillaCategory();
        $this->makeFeature('esyali', 'select');
        $listingCount = Ilan::count();

        try {
            app(IlanCrudService::class)->store(
                $this->baseData($villa, [
                    'features' => [
                        'esyali' => 'Belirsiz',
                    ],
                ])
            );

            $this->fail('Unknown boolean values must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('esyali', $exception->getMessage());
        }

        $this->assertSame($listingCount, Ilan::count());
    }

    /**
     * @test
     * @group crud
     * @group golden-thread
     */
    public function sync_features_rejects_invalid_numeric_value_without_persisting_listing(): void
    {
        $villa = $this->makeVillaCategory();
        $this->makeFeature('brut-metrekare', 'number');
        $listingCount = Ilan::count();

        try {
            app(IlanCrudService::class)->store(
                $this->baseData($villa, [
                    'features' => [
                        'brut-metrekare' => 'iki yüz',
                    ],
                ])
            );

            $this->fail('Invalid numeric values must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('brut-metrekare', $exception->getMessage());
        }

        $this->assertSame($listingCount, Ilan::count());
    }
}

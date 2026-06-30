<?php

namespace Tests\Unit;

use App\Domains\PropertySchema\PropertyTypeConfiguration;
use App\Domains\PropertySchema\Services\FeaturePackApplicator;
use App\Domains\PropertySchema\Services\TemplateSealingPolicy;
use App\Domains\PropertySchema\ValueObjects\SealedTemplateJson;
use App\Models\AltKategoriYayinTipi;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeatureCategory;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\UpsTemplate;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use App\Exceptions\PropertyHub\TemplateResolutionException;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * PropertyTypeConfiguration Aggregate Root + Domain Services Unit Test
 *
 * [SAB ENFORCEMENT]: Domain Finalization Verification
 * Context7 field naming + Aggregate Overgrowth prevention'i dogrular.
 */
class PropertyTypeConfigurationTest extends TestCase
{

    private PropertyTypeConfiguration $aggregate;
    private YayinTipiSablonu $yayinTipi;
    private IlanKategori $kategori;
    private AltKategoriYayinTipi $pivot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregate = app(PropertyTypeConfiguration::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->kategori = IlanKategori::create([
            'name' => 'Test Kategori',
            'seviye' => 0,
            'aktiflik_durumu' => true,
        ]);

        $this->yayinTipi = YayinTipiSablonu::create([
            'ad' => 'Test Yayin Tipi',
            'slug' => 'test-yayin-tipi',
            'aktiflik_durumu' => true,
            'kategori_id' => $this->kategori->id,
        ]);

        $this->pivot = AltKategoriYayinTipi::create([
            'alt_kategori_id' => $this->kategori->id,
            'yayin_tipi_id' => $this->yayinTipi->id,
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ]);
    }

    // ─── TemplateSealingPolicy Tests ────────────────────────

    public function test_seal_template_creates_new_version(): void
    {
        $upsJson = [
            'zorunlu_alanlar' => ['fiyat', 'baslik'],
            'opsiyonel_alanlar' => ['aciklama'],
            'gizli_alanlar' => [],
        ];

        $result = $this->aggregate->sealTemplate(
            junctionId: $this->yayinTipi->id,
            upsJson: $upsJson,
            shouldSeal: true,
            userId: auth()->id()
        );

        $this->assertFalse($result['is_duplicate']);
        $this->assertInstanceOf(UpsTemplate::class, $result['template']);
        $this->assertEquals(1, $result['template']->template_version);
        $this->assertEquals(\App\Enums\AktiflikDurumu::AKTIF, $result['template']->aktiflik_durumu);
        $this->assertNotNull($result['template']->sealed_at);
        $this->assertNotNull($result['template']->template_hash);
    }

    public function test_seal_template_detects_duplicate(): void
    {
        $upsJson = [
            'zorunlu_alanlar' => ['fiyat'],
            'opsiyonel_alanlar' => [],
            'gizli_alanlar' => [],
        ];

        // Ilk seal
        $this->aggregate->sealTemplate($this->yayinTipi->id, $upsJson);

        // Ayni icerikle tekrar seal
        $result = $this->aggregate->sealTemplate($this->yayinTipi->id, $upsJson);

        $this->assertTrue($result['is_duplicate']);
    }

    public function test_seal_template_validates_conflicts(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cakisma');

        $conflictingJson = [
            'zorunlu_alanlar' => ['fiyat', 'baslik'],
            'opsiyonel_alanlar' => ['fiyat'], // fiyat hem zorunlu hem opsiyonel
            'gizli_alanlar' => [],
        ];

        $this->aggregate->sealTemplate($this->yayinTipi->id, $conflictingJson);
    }

    // ─── FeaturePackApplicator Tests ────────────────────────

    public function test_assign_features_creates_assignments(): void
    {
        Event::fake();

        $category = FeatureCategory::create([
            'name' => 'Test FK',
            'slug' => 'test-fk',
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ]);

        $features = [];
        for ($i = 1; $i <= 3; $i++) {
            $features[] = Feature::create([
                'name' => "Feature {$i}",
                'slug' => "feature-{$i}",
                'type' => 'boolean',
                'feature_category_id' => $category->id,
                'aktiflik_durumu' => true,
            ]);
        }

        $featureIds = array_map(fn($f) => $f->id, $features);

        $assigned = $this->aggregate->assignFeatures($this->pivot->id, $featureIds);

        $this->assertCount(3, $assigned);
        $this->assertEquals(3, FeatureAssignment::where('assignable_id', $this->pivot->id)->count());

        Event::assertDispatched(\App\Domains\PropertySchema\Events\FeatureAssignedEvent::class);
    }

    public function test_assign_features_skips_duplicates(): void
    {
        $category = FeatureCategory::create([
            'name' => 'Test FK2',
            'slug' => 'test-fk2',
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ]);

        $feature = Feature::create([
            'name' => 'Dup Feature',
            'slug' => 'dup-feature',
            'type' => 'boolean',
            'feature_category_id' => $category->id,
            'aktiflik_durumu' => true,
        ]);

        // Ilk atama
        $this->aggregate->assignFeatures($this->pivot->id, [$feature->id]);

        // Tekrar atama — skip edilmeli
        $assigned = $this->aggregate->assignFeatures($this->pivot->id, [$feature->id]);

        $this->assertCount(0, $assigned);
        $this->assertEquals(1, FeatureAssignment::where('assignable_id', $this->pivot->id)->count());
    }

    public function test_unassign_features_removes_and_dispatches(): void
    {
        Event::fake();

        $category = FeatureCategory::create([
            'name' => 'Test FK3',
            'slug' => 'test-fk3',
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ]);

        $feature = Feature::create([
            'name' => 'Remove Feature',
            'slug' => 'remove-feature',
            'type' => 'boolean',
            'feature_category_id' => $category->id,
            'aktiflik_durumu' => true,
        ]);

        // Once ata
        FeatureAssignment::create([
            'feature_id' => $feature->id,
            'assignable_type' => AltKategoriYayinTipi::class,
            'assignable_id' => $this->pivot->id,
            'source_type' => 'manual',
            'aktiflik_durumu' => 1,
        ]);

        $deleted = $this->aggregate->unassignFeatures($this->pivot->id, [$feature->id]);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(0, FeatureAssignment::where('assignable_id', $this->pivot->id)->count());

        Event::assertDispatched(\App\Domains\PropertySchema\Events\FeatureAssignedEvent::class);
    }

    public function test_apply_feature_pack_merge_mode(): void
    {
        $category = FeatureCategory::create([
            'name' => 'Pack FK',
            'slug' => 'pack-fk',
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ]);

        $features = [];
        for ($i = 1; $i <= 3; $i++) {
            $features[] = Feature::create([
                'name' => "Pack Feature {$i}",
                'slug' => "pack-feature-{$i}",
                'type' => 'boolean',
                'feature_category_id' => $category->id,
                'aktiflik_durumu' => true,
            ]);
        }

        $pack = FeaturePack::create([
            'name' => 'Test Pack',
            'slug' => 'test-pack',
            'aktiflik_durumu' => true,
        ]);
        $pack->features()->attach(array_map(fn($f) => $f->id, $features));

        // Ilk feature'u onceden ata (merge'de skip edilmeli)
        FeatureAssignment::create([
            'feature_id' => $features[0]->id,
            'assignable_type' => AltKategoriYayinTipi::class,
            'assignable_id' => $this->pivot->id,
            'source_type' => 'manual',
            'aktiflik_durumu' => 1,
        ]);

        $result = $this->aggregate->applyFeaturePack(
            pivotId: $this->pivot->id,
            packId: $pack->id,
            mode: 'merge'
        );

        $this->assertEquals(2, $result['added_count']);
        $this->assertEquals(1, $result['skipped_count']);
    }

    public function test_apply_feature_pack_replace_mode(): void
    {
        $category = FeatureCategory::create([
            'name' => 'Replace FK',
            'slug' => 'replace-fk',
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ]);

        $oldFeature = Feature::create([
            'name' => 'Old Feature',
            'slug' => 'old-feature',
            'type' => 'boolean',
            'feature_category_id' => $category->id,
            'aktiflik_durumu' => true,
        ]);

        $newFeature = Feature::create([
            'name' => 'New Feature',
            'slug' => 'new-feature',
            'type' => 'boolean',
            'feature_category_id' => $category->id,
            'aktiflik_durumu' => true,
        ]);

        // Eski feature'u ata
        FeatureAssignment::create([
            'feature_id' => $oldFeature->id,
            'assignable_type' => AltKategoriYayinTipi::class,
            'assignable_id' => $this->pivot->id,
            'source_type' => 'manual',
            'aktiflik_durumu' => 1,
        ]);

        $pack = FeaturePack::create([
            'name' => 'Replace Pack',
            'slug' => 'replace-pack',
            'aktiflik_durumu' => true,
        ]);
        $pack->features()->attach([$newFeature->id]);

        $result = $this->aggregate->applyFeaturePack(
            pivotId: $this->pivot->id,
            packId: $pack->id,
            mode: 'replace'
        );

        $this->assertEquals(1, $result['added_count']);
        // Eski feature silinmeli
        $remaining = FeatureAssignment::where('assignable_id', $this->pivot->id)->get();
        $this->assertEquals(1, $remaining->count());
        $this->assertEquals($newFeature->id, $remaining->first()->feature_id);
    }

    public function test_sync_features_adds_and_removes(): void
    {
        $category = FeatureCategory::create([
            'name' => 'Sync FK',
            'slug' => 'sync-fk',
            'aktiflik_durumu' => true,
            'display_order' => 0,
        ]);

        $feature1 = Feature::create(['name' => 'Sync1', 'slug' => 'sync1', 'type' => 'boolean', 'feature_category_id' => $category->id, 'aktiflik_durumu' => true]);
        $feature2 = Feature::create(['name' => 'Sync2', 'slug' => 'sync2', 'type' => 'boolean', 'feature_category_id' => $category->id, 'aktiflik_durumu' => true]);
        $feature3 = Feature::create(['name' => 'Sync3', 'slug' => 'sync3', 'type' => 'boolean', 'feature_category_id' => $category->id, 'aktiflik_durumu' => true]);

        // Onceden feature1 ve feature2 atanmis
        FeatureAssignment::create(['feature_id' => $feature1->id, 'assignable_type' => AltKategoriYayinTipi::class, 'assignable_id' => $this->pivot->id, 'source_type' => 'manual', 'aktiflik_durumu' => 1]);
        FeatureAssignment::create(['feature_id' => $feature2->id, 'assignable_type' => AltKategoriYayinTipi::class, 'assignable_id' => $this->pivot->id, 'source_type' => 'manual', 'aktiflik_durumu' => 1]);

        // Sync: feature2 + feature3 (feature1 kaldirilir, feature3 eklenir)
        $result = $this->aggregate->syncFeatures($this->pivot->id, [$feature2->id, $feature3->id]);

        $this->assertEquals(1, $result['added']);
        $this->assertEquals(1, $result['removed']);

        $remaining = FeatureAssignment::where('assignable_id', $this->pivot->id)->pluck('feature_id')->toArray();
        $this->assertContains($feature2->id, $remaining);
        $this->assertContains($feature3->id, $remaining);
        $this->assertNotContains($feature1->id, $remaining);
    }

    public function test_resolve_template_throws_on_missing(): void
    {
        $this->expectException(TemplateResolutionException::class);

        // Var olmayan kategori ID ile cagir
        $this->aggregate->resolveTemplate(99999, 99999);
    }

    // ─── Value Object Tests ────────────────────────────────

    public function test_sealed_template_json_immutability(): void
    {
        $json = [
            'zorunlu_alanlar' => ['baslik', 'fiyat'],
            'opsiyonel_alanlar' => ['aciklama'],
            'gizli_alanlar' => [],
        ];

        $vo = new SealedTemplateJson($json);

        // Hash deterministik olmali
        $vo2 = new SealedTemplateJson($json);
        $this->assertTrue($vo->equalsHash($vo2->hash()));

        // Farkli JSON farkli hash
        $json2 = $json;
        $json2['zorunlu_alanlar'][] = 'adres';
        $vo3 = new SealedTemplateJson($json2);
        $this->assertFalse($vo->equalsHash($vo3->hash()));
    }
}

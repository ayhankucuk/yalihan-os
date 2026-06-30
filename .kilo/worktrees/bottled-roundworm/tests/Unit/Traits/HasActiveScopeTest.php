<?php

namespace Tests\Unit\Traits;

use App\Traits\HasActiveScope;
use App\Enums\IlanDurumu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HasActiveScope Trait Test Suite
 *
 * Context7 Standardı: C7-ACTIVE-SCOPE-TEST-2026-05-20
 * SAB Core v2.6 Uyumlu
 *
 * Test Coverage:
 * - scopeActive() backward compatibility
 * - scopeAktif() Context7 compliance
 * - Multi-field detection (yayin_durumu, aktiflik_durumu, aktif_mi, one_cikan)
 * - Schema-aware behavior
 */
class HasActiveScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Model with yayin_durumu field
     */
    protected function createYayinDurumuModel(): Model
    {
        return new class extends Model {
            use HasActiveScope;

            protected $table = 'test_yayin_durumu';
            protected $fillable = ['yayin_durumu'];
            public $timestamps = false;
        };
    }

    /**
     * Test Model with aktiflik_durumu field
     */
    protected function createAktiflikDurumuModel(): Model
    {
        return new class extends Model {
            use HasActiveScope;

            protected $table = 'test_aktiflik_durumu';
            protected $fillable = ['aktiflik_durumu'];
            public $timestamps = false;
        };
    }

    /**
     * Test Model with aktif_mi field (legacy)
     */
    protected function createAktifMiModel(): Model
    {
        return new class extends Model {
            use HasActiveScope;

            protected $table = 'test_aktif_mi';
            protected $fillable = ['aktif_mi'];
            public $timestamps = false;
        };
    }

    /**
     * Test Model with one_cikan field
     */
    protected function createOneCikanModel(): Model
    {
        return new class extends Model {
            use HasActiveScope;

            protected $table = 'test_one_cikan';
            protected $fillable = ['one_cikan'];
            public $timestamps = false;
        };
    }

    /**
     * Test Model with is_active field (legacy)
     */
    protected function createIsActiveModel(): Model
    {
        return new class extends Model {
            use HasActiveScope;

            protected $table = 'test_is_active';
            protected $fillable = ['is_active']; // context7-ignore: legacy compatibility test
            public $timestamps = false;
        };
    }

    /**
     * Setup test tables
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test tables
        Schema::create('test_yayin_durumu', function ($table) {
            $table->id();
            $table->string('yayin_durumu');
        });

        Schema::create('test_aktiflik_durumu', function ($table) {
            $table->id();
            $table->boolean('aktiflik_durumu')->default(true);
        });

        Schema::create('test_aktif_mi', function ($table) {
            $table->id();
            $table->boolean('aktif_mi')->default(true);
        });

        Schema::create('test_one_cikan', function ($table) {
            $table->id();
            $table->boolean('one_cikan')->default(false);
        });

        Schema::create('test_is_active', function ($table) {
            $table->id();
            $table->tinyInteger('is_active')->default(1); // context7-ignore: legacy compatibility test
        });
    }

    /**
     * Cleanup test tables
     */
    protected function tearDown(): void
    {
        Schema::dropIfExists('test_yayin_durumu');
        Schema::dropIfExists('test_aktiflik_durumu');
        Schema::dropIfExists('test_aktif_mi');
        Schema::dropIfExists('test_one_cikan');
        Schema::dropIfExists('test_is_active');

        parent::tearDown();
    }

    /** @test */
    public function it_filters_by_yayin_durumu_field()
    {
        $model = $this->createYayinDurumuModel();

        // Insert test data
        DB::table('test_yayin_durumu')->insert([
            ['yayin_durumu' => IlanDurumu::YAYINDA->value],
            ['yayin_durumu' => IlanDurumu::TASLAK->value],
            ['yayin_durumu' => 'Pasif'], // Any non-YAYINDA value
        ]);

        $results = $model->active()->get();

        $this->assertCount(1, $results);
        $this->assertEquals(IlanDurumu::YAYINDA->value, $results->first()->yayin_durumu);
    }

    /** @test */
    public function it_filters_by_aktiflik_durumu_field()
    {
        $model = $this->createAktiflikDurumuModel();

        // Insert test data
        DB::table('test_aktiflik_durumu')->insert([
            ['aktiflik_durumu' => true],
            ['aktiflik_durumu' => false],
            ['aktiflik_durumu' => true],
        ]);

        $results = $model->active()->get();

        $this->assertCount(2, $results);
        // Boolean stored as tinyint (1/0) in database
        $this->assertEquals(1, $results->first()->aktiflik_durumu);
    }

    /** @test */
    public function it_filters_by_aktif_mi_field_legacy()
    {
        $model = $this->createAktifMiModel();

        // Insert test data
        DB::table('test_aktif_mi')->insert([
            ['aktif_mi' => true],
            ['aktif_mi' => false],
        ]);

        $results = $model->active()->get();

        $this->assertCount(1, $results);
        // Boolean stored as tinyint (1/0) in database
        $this->assertEquals(1, $results->first()->aktif_mi);
    }

    /** @test */
    public function it_filters_by_one_cikan_field()
    {
        $model = $this->createOneCikanModel();

        // Insert test data
        DB::table('test_one_cikan')->insert([
            ['one_cikan' => true],
            ['one_cikan' => false],
            ['one_cikan' => true],
        ]);

        $results = $model->active()->get();

        $this->assertCount(2, $results);
        // Boolean stored as tinyint (1/0) in database
        $this->assertEquals(1, $results->first()->one_cikan);
    }

    /** @test */
    public function it_filters_by_is_active_field_legacy()
    {
        $model = $this->createIsActiveModel();

        // Insert test data
        DB::table('test_is_active')->insert([
            ['is_active' => 1],
            ['is_active' => 0],
            ['is_active' => 1],
        ]);

        $results = $model->active()->get();

        $this->assertCount(2, $results);
        $this->assertEquals(1, $results->first()->is_active);
    }

    /** @test */
    public function it_returns_unfiltered_when_no_active_field_exists()
    {
        $model = new class extends Model {
            use HasActiveScope;

            protected $table = 'test_no_active_field';
            public $timestamps = false;
        };

        Schema::create('test_no_active_field', function ($table) {
            $table->id();
            $table->string('name');
        });

        DB::table('test_no_active_field')->insert([
            ['name' => 'Test 1'],
            ['name' => 'Test 2'],
        ]);

        $results = $model->active()->get();

        $this->assertCount(2, $results);

        Schema::dropIfExists('test_no_active_field');
    }

    /** @test */
    public function scope_aktif_uses_aktiflik_durumu_when_available()
    {
        $model = $this->createAktiflikDurumuModel();

        // Insert test data
        DB::table('test_aktiflik_durumu')->insert([
            ['aktiflik_durumu' => true],
            ['aktiflik_durumu' => false],
        ]);

        $results = $model->aktif()->get();

        $this->assertCount(1, $results);
        // Boolean stored as tinyint (1/0) in database
        $this->assertEquals(1, $results->first()->aktiflik_durumu);
    }

    /** @test */
    public function scope_aktif_falls_back_to_scope_active()
    {
        $model = $this->createYayinDurumuModel();

        // Insert test data
        DB::table('test_yayin_durumu')->insert([
            ['yayin_durumu' => IlanDurumu::YAYINDA->value],
            ['yayin_durumu' => IlanDurumu::TASLAK->value],
        ]);

        $results = $model->aktif()->get();

        $this->assertCount(1, $results);
        $this->assertEquals(IlanDurumu::YAYINDA->value, $results->first()->yayin_durumu);
    }

    /** @test */
    public function it_respects_field_priority_order()
    {
        // Create model with multiple active fields
        $model = new class extends Model {
            use HasActiveScope;

            protected $table = 'test_priority';
            public $timestamps = false;
        };

        Schema::create('test_priority', function ($table) {
            $table->id();
            $table->string('yayin_durumu');
            $table->boolean('aktiflik_durumu')->default(true);
            $table->boolean('aktif_mi')->default(true);
        });

        DB::table('test_priority')->insert([
            [
                'yayin_durumu' => IlanDurumu::YAYINDA->value,
                'aktiflik_durumu' => false,
                'aktif_mi' => false,
            ],
            [
                'yayin_durumu' => IlanDurumu::TASLAK->value,
                'aktiflik_durumu' => true,
                'aktif_mi' => true,
            ],
        ]);

        // Should prioritize yayin_durumu
        $results = $model->active()->get();

        $this->assertCount(1, $results);
        $this->assertEquals(IlanDurumu::YAYINDA->value, $results->first()->yayin_durumu);

        Schema::dropIfExists('test_priority');
    }

    /** @test */
    public function it_works_with_query_builder_chaining()
    {
        $model = $this->createAktiflikDurumuModel();

        DB::table('test_aktiflik_durumu')->insert([
            ['id' => 1, 'aktiflik_durumu' => true],
            ['id' => 2, 'aktiflik_durumu' => false],
            ['id' => 3, 'aktiflik_durumu' => true],
        ]);

        $results = $model->active()
            ->where('id', '>', 1)
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(3, $results->first()->id);
    }

    /** @test */
    public function backward_compatibility_scope_active_still_works()
    {
        $model = $this->createAktiflikDurumuModel();

        DB::table('test_aktiflik_durumu')->insert([
            ['aktiflik_durumu' => true],
            ['aktiflik_durumu' => false],
        ]);

        // Old code using scopeActive should still work
        $results = $model->active()->get();

        $this->assertCount(1, $results);
        // Boolean stored as tinyint (1/0) in database
        $this->assertEquals(1, $results->first()->aktiflik_durumu);
    }
}

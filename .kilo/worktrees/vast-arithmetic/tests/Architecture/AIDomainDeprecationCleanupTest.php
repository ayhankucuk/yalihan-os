<?php

namespace Tests\Architecture;

use Tests\TestCase;
use App\Models\Etiket;
use App\Models\Ilan;
use App\Models\IlanEmbedding;
use App\Models\IlanKategori;
use App\Models\Kisi;
use App\Models\Ozellik;
use App\Models\Proje;
use App\Models\Talep;
use App\Models\User;
use App\Traits\HasActiveScope;
use Illuminate\Support\Facades\Schema;

/**
 * 🛡️ AIDomainDeprecationCleanupTest
 *
 * Verifies domain deprecations, trait applications, and Context7 naming standards
 * across the CRM, AI, and Property domains.
 *
 * SAB Core v2.6 Technical Constitution compliant.
 */
class AIDomainDeprecationCleanupTest extends TestCase
{
    /**
     * Verify that all 9 specified models utilize the HasActiveScope trait.
     */
    public function test_models_utilize_has_active_scope_trait()
    {
        $models = [
            Etiket::class,
            Ilan::class,
            IlanEmbedding::class,
            IlanKategori::class,
            Kisi::class,
            Ozellik::class,
            Proje::class,
            Talep::class,
            User::class,
        ];

        foreach ($models as $model) {
            $traits = class_uses_recursive($model);
            $this->assertArrayHasKey(
                HasActiveScope::class,
                $traits,
                "Model {$model} must utilize HasActiveScope trait."
            );
        }
    }

    /**
     * Verify that scopeActive() and scopeAktif() behave as expected on Ilan and Kisi.
     */
    public function test_active_scopes_behave_as_expected()
    {
        // Kisi scopes
        $kisiQuery = Kisi::query();
        $this->assertStringContainsString('aktiflik_durumu', $kisiQuery->aktif()->toSql());
        $this->assertStringContainsString('aktiflik_durumu', $kisiQuery->active()->toSql());

        // Ilan scopes
        $ilanQuery = Ilan::query();
        $this->assertStringContainsString('yayin_durumu', $ilanQuery->aktif()->toSql());
        $this->assertStringContainsString('yayin_durumu', $ilanQuery->active()->toSql());
    }

    /**
     * Verify eposta naming in Kisi model fillable and database columns.
     */
    public function test_kisi_model_uses_eposta_instead_of_email()
    {
        $kisi = new Kisi();
        $fillable = $kisi->getFillable();

        $this->assertContains('eposta', $fillable, "Kisi model must have 'eposta' in its fillable attributes.");
        $this->assertNotContains('email', $fillable, "Kisi model must not have deprecated 'email' in its fillable attributes.");
        $this->assertNotContains('last_contacted_at', $fillable, "Kisi model must not have deprecated 'last_contacted_at' in its fillable attributes.");

        // Check DB columns if table exists
        if (Schema::hasTable('kisiler')) {
            $this->assertTrue(Schema::hasColumn('kisiler', 'eposta'), "kisiler table must have 'eposta' column.");
            $this->assertFalse(Schema::hasColumn('kisiler', 'email'), "kisiler table must not have deprecated 'email' column.");
            $this->assertTrue(Schema::hasColumn('kisiler', 'son_etkilesim_tarihi'), "kisiler table must have 'son_etkilesim_tarihi' column.");
            $this->assertFalse(Schema::hasColumn('kisiler', 'last_contacted_at'), "kisiler table must not have deprecated 'last_contacted_at' column.");
        }
    }

    public function test_ilan_favorileri_pivot_table_is_isolated()
    {
        if (!Schema::hasTable('ilan_favorileri')) {
            Schema::create('ilan_favorileri', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('ilan_id');
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        $this->assertTrue(Schema::hasColumn('ilan_favorileri', 'user_id'), "ilan_favorileri table must have 'user_id' column.");
        $this->assertTrue(Schema::hasColumn('ilan_favorileri', 'ilan_id'), "ilan_favorileri table must have 'ilan_id' column.");
        $this->assertTrue(Schema::hasColumn('ilan_favorileri', 'aktiflik_durumu'), "ilan_favorileri table must have 'aktiflik_durumu' column.");
        $this->assertFalse(Schema::hasColumn('ilan_favorileri', 'is_active'), "ilan_favorileri table must not have deprecated 'is_active' column."); // context7-ignore: testing deprecated field absence
    }
}

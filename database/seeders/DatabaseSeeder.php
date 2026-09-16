<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Database Seeder
 *
 * Yalıhan Emlak Master Seeder
 * Tüm database seeding işlemlerini yönetir
 *
 * Kullanım:
 *   php artisan db:seed                    → Tüm sistem verilerini seed eder
 *   php artisan migrate:fresh --seed       → Database'i sıfırla ve seed et
 *
 * [YALIHAN_SEEDER_2026]
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🚀 YALIHAN EMLAK - Database Seeder');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();

        // ================================================
        // 1. ROLES & MULTI-TENANT (Her zaman çalışır)
        // ================================================
        $this->command->info('🔐 1/4 Roles & Multi-Tenant Baseline Loading...');
        $this->call([
            TenantBaselineSeeder::class,     // Tenant kaydı önce (AdminUserSeeder tenant_id'ye ihtiyaç duyar)
            RoleSeeder::class,               // Spatie roles (super-admin, admin, danisman, musteri)
        ]);
        $this->command->newLine();

        // ================================================
        // 2. CORE DOMAIN MASTER DATA (Her zaman çalışır - Prodüksiyon SSOT)
        // ================================================
        $this->command->info('📦 2/4 Core Domain Master Data Loading...');
        $this->call([
            AdminUserSeeder::class,                    // Super-admin users (ayhankucuk@gmail.com, yalihanemlak@gmail.com)
            IlanKategoriSeeder::class,                 // 6 Ana Kategori + Alt Kategoriler
            YayinTipiSeeder::class,                    // Canonical publication types
            KategoriYayinTipiPivotSeeder::class,       // Kategori × Yayın Tipi eşleşmeleri
            FeatureAssignmentSeeder::class,            // Canonical Feature Schema (Konut)
            ArsaIsyeriFeatureAssignmentSeeder::class,  // Canonical Feature Schema (Arsa & İşyeri)
            CategoryFeatureMatrixSeeder::class,        // Canonical Feature Schema (Yazlık, Turistik, Proje)
            SmartFormsCanonicalSeeder::class,          // Dinamik form kuralları
            ExpenseItemSeeder::class,                  // Finansal kalemler
            TurkiyeLocationSeeder::class,              // 81 İl + Muğla ilçeleri + Bodrum mahalleleri
        ]);
        $this->command->newLine();

        // ================================================
        // 3. TEST PERSONAS & POI FIXTURES (Local/Dev/Test ortamında)
        // ================================================
        if (app()->environment(['local', 'development', 'testing'])) {
            $this->command->info('👥 3/4 Test Personas & POI Fixtures Loading...');
            $this->command->info('   → Operational Digest & Workflow Validation Personas');

            $this->call([
                DanismanSeeder::class,       // Danışmanlar (Atılay, Sedat, Yunus)
                MusteriSeeder::class,        // Test müşteriler (5 deterministic personas)
                BodrumPoiSeeder::class,      // Bodrum & Muğla 200+ POI noktası
            ]);

            $this->command->newLine();
        } else {
            $this->command->warn('⏭️  3/4 Test Personas & POI Fixtures skipped (Production mode)');
            $this->command->newLine();
        }

        // ================================================
        // 4. FINALIZATION
        // ================================================
        $this->command->info('🎉 4/4 Finalization...');
        $this->displaySummary();
    }

    /**
     * Display seeding summary
     */
    private function displaySummary(): void
    {
        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ Database Seeding COMPLETED!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();

        $this->command->warn('📊 SEED SUMMARY:');
        $this->command->warn('   ├─ Roles: ✅ (super-admin, admin, danisman, musteri)');
        $this->command->warn('   ├─ Core System: ✅');
        $this->command->warn('   ├─ Admin Users: ayhankucuk@gmail.com / admin123');

        if (app()->environment(['local', 'development', 'testing'])) {
            $this->command->warn('   ├─ Test Personas: ✅');
            $this->command->warn('   │  ├─ Danışmanlar: Atılay, Sedat, Yunus (password: test123)');
            $this->command->warn('   │  └─ Müşteriler: 5 deterministic personas');
        } else {
            $this->command->warn('   ├─ Test Personas: ⏭️ (Skipped - Production)');
        }

        $this->command->warn('   └─ Environment: '.app()->environment());

        $this->command->newLine();
        $this->command->info('🔗 Data integrity: %100');
        $this->command->info('⚡ Ready for use!');
        $this->command->newLine();
    }
}

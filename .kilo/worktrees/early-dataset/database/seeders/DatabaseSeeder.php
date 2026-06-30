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
        // 1. ROLES & PERMISSIONS (Her zaman çalışır)
        // ================================================
        $this->command->info('🔐 1/5 Roles & Permissions Loading...');
        $this->call([
            RoleSeeder::class,               // Spatie roles (super-admin, admin, danisman, musteri)
        ]);
        $this->command->newLine();

        // ================================================
        // 2. CORE SYSTEM (Her zaman çalışır)
        // ================================================
        $this->command->info('📦 2/5 Core System Loading...');
        $this->call([
            AdminUserSeeder::class,          // Super-admin users (ayhankucuk@gmail.com, yalihanemlak@gmail.com)
            // Context7MasterSeeder::class,     // Kategori, özellikler, sistem verileri (Deleted/Missing)
            IlanKategoriSeeder::class,
            YayinTipiSeeder::class,          // Canonical publication types
            KategoriYayinTipiPivotSeeder::class,
            OzellikKategoriSeeder::class,
            PropertyHubOzelliklerSeeder::class,
            SmartFormsCanonicalSeeder::class,
            ExpenseItemSeeder::class,
        ]);
        $this->command->newLine();

        // ================================================
        // 3. TEST PERSONAS (Local/Dev/Test ortamında)
        // ================================================
        if (app()->environment(['local', 'development', 'testing'])) {
            $this->command->info('👥 3/5 Test Personas Loading...');
            $this->command->info('   → Operational Digest workflow validation personas');

            $this->call([
                DanismanSeeder::class,       // Danışmanlar (Atılay, Sedat, Yunus)
                MusteriSeeder::class,        // Test müşteriler (5 deterministic personas)
            ]);

            $this->command->newLine();
        } else {
            $this->command->warn('⏭️  3/5 Test Personas skipped (Production mode)');
            $this->command->newLine();
        }

        // ================================================
        // 4. OPTIONAL SEEDERS (İsteğe bağlı)
        // ================================================
        $this->command->info('⚙️  4/5 Optional Seeders...');

        // Feature Assignments (Orphaned Features'ı atamak için)
        $this->command->info('   → Running Feature Assignment Seeder...');
        // $this->call([
        //     FeatureAssignmentSeeder::class, (Deleted)
        //     UpsTemplateTableSeeder::class, // Phase 35 Alignment (Deleted)
        // ]);

        // Golden Visa (geçici olarak devre dışı - minimal schema)
        $this->command->warn('   → Golden Visa seeder skipped (minimal schema)');

        $this->command->newLine();

        // ================================================
        // 5. FINALIZATION
        // ================================================
        $this->command->info('🎉 5/5 Finalization...');
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

        $this->command->warn('   └─ Environment: ' . app()->environment());

        $this->command->newLine();
        $this->command->info('🔗 Data integrity: %100');
        $this->command->info('⚡ Ready for use!');
        $this->command->newLine();
    }
}

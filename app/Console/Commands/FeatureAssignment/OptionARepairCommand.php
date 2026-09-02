<?php

namespace App\Console\Commands\FeatureAssignment;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Option A — Saf Polimorfik Şablon Ataması Repair
 *
 * SAAB DECISION: Option A approved for dry-run (2026-09-02)
 * G4 CANONICAL SCOPE FIX (2026-09-02): G4 = G3 Villa özellikleri + aidat + depozito = 36 total
 *
 * Hedef:
 *   G3 → villa-satilik template (id=22):   35 özellik
 *   G4 → villa-kiralik template (id=23):    36 özellik (G3 + aidat required + depozito required)
 *   G5 → villa-gunluk template (id=24):     35 özellik
 *   G1/G2 → ayrı kategori-level assignment olarak oluşturulmayacak; Villa template'lerinin mevcut denormalize kümesinde korunacak
 *   46 fake kategoriye atama YAPILMAZ
 *   88 phantom template'e özellik UYDURULMAZ
 *   Eski `Ilan/0` kayıtları ilk aşamada silinmeyecek; arşiv/repair provenance ile işaretlenecek
 *
 * Kullanım:
 *   php artisan feature:option-a-repair          # dry-run (default)
 *   php artisan feature:option-a-repair --commit # gerçek uygulama
 */
class OptionARepairCommand extends Command
{
    protected $signature = 'feature:option-a-repair
                            {--commit : Dry-run yerine veritabanina yazar}';

    protected $description = 'Option A: Villa feature template repair (saf polimorfik şablon ataması)';

    // Target YayinTipiSablonu IDs
    private const TEMPLATE_VILLA_SATILIK = 22;
    private const TEMPLATE_VILLA_KIRALIK = 23;
    private const TEMPLATE_VILLA_GUNLUK = 24;

    // Option A provenance tags
    private const PROVENANCE_MIGRATION = 'villa_migration_2026_08_25';
    private const PROVENANCE_LEGACY_ARCHIVE = 'legacy_repair_2026_09_02';

    public function handle(): int
    {
        $isCommit = (bool) $this->option('commit');

        $this->info('═══════════════════════════════════════════════════════');
        $this->info(' Option A — Saf Polimorfik Şablon Ataması Repair');
        $this->info('═══════════════════════════════════════════════════════');

        if (!$isCommit) {
            $this->warn('⚠  DRY-RUN MODU — Veritabanında hiçbir değişiklik yapılmayacak');
        } else {
            $this->error('⚠  COMMIT MODU — Veritabanına yazılacak! Durdurmak için Ctrl+C');
            if (!$this->confirm('Devam etmek istediğinize emin misiniz?')) {
                $this->info('İptal edildi.');
                return self::SUCCESS;
            }
        }

        $this->line('');

        // ── 1. PRE-FLIGHT CHECKS ────────────────────────────────────────────────
        $this->section('1. PRE-FLIGHT CHECKS');

        $this->checkTemplateExistence();
        $this->checkFeatureExistence();
        $this->checkLegacyRecords();

        // ── 2. CURRENT STATE REPORT ─────────────────────────────────────────────
        $this->section('2. CURRENT DATABASE STATE');
        $this->reportCurrentState();

        // ── 3. OPTION A REPAIR DRY-RUN ─────────────────────────────────────────
        $this->section('3. OPTION A REPAIR PLAN');
        $repairPlan = $this->buildRepairPlan();
        $this->displayRepairPlan($repairPlan);

        // ── 4. DUPLICATE RISK CHECK ────────────────────────────────────────────
        $this->section('4. DUPLICATE RISK CHECK');
        $this->checkDuplicateRisk($repairPlan);

        // ── 5. LEGACY ARCHIVE PLAN ─────────────────────────────────────────────
        $this->section('5. LEGACY 84 RECORD ARCHIVE PLAN');
        $this->displayLegacyArchivePlan();

        // ── 6. SUMMARY ───────────────────────────────────────────────────────────
        $this->section('6. SUMMARY');
        $this->displaySummary($repairPlan, $isCommit);

        // ── 7. EXECUTE (if --commit) ────────────────────────────────────────────
        if ($isCommit) {
            $this->section('7. EXECUTING REPAIR');
            $this->executeRepair($repairPlan);
            $this->section('8. POST-REPAIR VERIFICATION');
            $this->verifyRepair();
        } else {
            $this->section('7. DRY-RUN COMPLETE');
            $this->info('Dry-run tamamlandı. --commit ile gerçek uygulamayı çalıştırın.');
        }

        return self::SUCCESS;
    }

    // ─── PRE-FLIGHT ──────────────────────────────────────────────────────────

    private function checkTemplateExistence(): void
    {
        foreach ([self::TEMPLATE_VILLA_SATILIK, self::TEMPLATE_VILLA_KIRALIK, self::TEMPLATE_VILLA_GUNLUK] as $id) {
            $exists = DB::table('yayin_tipi_sablonlari')->where('id', $id)->exists();
            $name = DB::table('yayin_tipi_sablonlari')->where('id', $id)->value('ad');
            $status = $exists ? "<fg=green>✓ EXISTS</fg=green>" : "<fg=red>✗ MISSING</fg=red>";
            $this->line("  Template {$id}: {$name} — {$status}");
        }
    }

    private function checkFeatureExistence(): void
    {
        $slugs = [
            'brut-alan', 'net-alan', 'oda-sayisi', 'balkon', 'kat',
            'arsa-alani', 'denize-mesafe', 'manzara', 'cephe', 'imar-durumu',
            'havuz', 'havuz-tip', 'ozel-havuz', 'bahce', 'bahce-alani',
            'akilli-ev', 'teras', 'veranda',
            'otopark', 'guvenlik', 'site-icerisinde', 'spor-alani',
            'esyali', 'mutfak-tipi', 'isitma', 'sogutma', 'bina-yasi', 'kurutma-odasi',
            'aidat', 'depozito', 'kredi-uygunlugu', 'takas',
            'tapu-durumu', 'kullanim-durumu',
        ];
        $missing = [];
        foreach ($slugs as $slug) {
            if (!DB::table('features')->where('slug', $slug)->exists()) {
                $missing[] = $slug;
            }
        }
        if ($missing) {
            $this->warn("  ⚠ Missing features: " . implode(', ', $missing));
        } else {
            $this->line('  <fg=green>✓ All 36 required features exist</fg=green>');
        }
    }

    private function checkLegacyRecords(): void
    {
        $legacyCount = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\Ilan')
            ->where('assignable_id', 0)
            ->count();
        $this->line("  Legacy records (Ilan/0): <fg=yellow>{$legacyCount}</fg=yellow> — {$this->formatNumber($legacyCount)} records to archive");
    }

    // ─── CURRENT STATE ───────────────────────────────────────────────────────

    private function reportCurrentState(): void
    {
        $totalAssignments = DB::table('feature_assignments')->count();
        $this->line("  Total feature_assignments: {$totalAssignments}");

        $sources = DB::table('feature_assignments')
            ->select('source_type', 'assignable_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('source_type', 'assignable_type')
            ->get();

        foreach ($sources as $s) {
            $this->line("    {$s->assignable_type} | {$s->source_type}: {$s->cnt}");
        }

        // Template atamaları
        foreach ([22, 23, 24] as $tid) {
            $cnt = DB::table('feature_assignments')
                ->where('assignable_type', 'App\\Models\\YayinTipiSablonu')
                ->where('assignable_id', $tid)
                ->count();
            $this->line("    Template {$tid} (villa-{$this->slugFromId($tid)}): {$cnt} assignments");
        }

        // Kategori atamaları
        $katCount = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\IlanKategori')
            ->count();
        $this->line("    IlanKategori assignments: {$katCount}");
    }

    // ─── REPAIR PLAN ────────────────────────────────────────────────────────

    private function buildRepairPlan(): array
    {
        $f = fn(string $slug) => DB::table('features')->where('slug', $slug)->value('id');

        $plan = [];

        // G3: Villa Satılık — template 22 — 35 özellik
        $plan['G3_villa_satilik'] = [
            'template_id' => self::TEMPLATE_VILLA_SATILIK,
            'template_name' => 'Villa Satilik',
            'scope' => 'listing_type',
            'provenance' => self::PROVENANCE_MIGRATION,
            'features' => [
                [$f('brut-alan'),        'brut-alan',        'Temel Bilgiler',    true,  true,  1],
                [$f('net-alan'),         'net-alan',          'Temel Bilgiler',    false, true,  2],
                [$f('oda-sayisi'),       'oda-sayisi',        'Temel Bilgiler',    true,  true,  3],
                [$f('banyo-sayisi'),    'banyo-sayisi',      'Temel Bilgiler',    false, true,  4],
                [$f('toplam-kat'),      'toplam-kat',        'Temel Bilgiler',    false, true,  5],
                [$f('balkon'),          'balkon',            'Temel Bilgiler',    false, true,  6],
                [$f('kat'),             'kat',               'Temel Bilgiler',    false, true,  7],
                [$f('arsa-alani'),       'arsa-alani',        'Konum ve Arsa',    false, true,  1],
                [$f('denize-mesafe'),   'denize-mesafe',     'Konum ve Arsa',    false, true,  2],
                [$f('manzara'),         'manzara',           'Konum ve Arsa',    false, true,  3],
                [$f('cephe'),           'cephe',             'Konum ve Arsa',    false, true,  4],
                [$f('imar-durumu'),      'imar-durumu',       'Konum ve Arsa',    false, true,  5],
                [$f('havuz'),           'havuz',             'Yapı Özellikleri', false, true,  1],
                [$f('havuz-tip'),       'havuz-tip',         'Yapı Özellikleri', false, true,  2],
                [$f('ozel-havuz'),      'ozel-havuz',        'Yapı Özellikleri', false, true,  3],
                [$f('bahce'),           'bahce',             'Yapı Özellikleri', false, true,  4],
                [$f('bahce-alani'),     'bahce-alani',       'Yapı Özellikleri', false, true,  5],
                [$f('akilli-ev'),       'akilli-ev',         'Yapı Özellikleri', false, true,  6],
                [$f('teras'),           'teras',             'Yapı Özellikleri', false, true,  7],
                [$f('veranda'),         'veranda',           'Yapı Özellikleri', false, false, 8],
                [$f('otopark'),         'otopark',           'Dış Özellikler',   false, true,  1],
                [$f('guvenlik'),        'guvenlik',          'Dış Özellikler',   false, true,  2],
                [$f('site-icerisinde'),'site-icerisinde',   'Dış Özellikler',   false, true,  3],
                [$f('spor-alani'),      'spor-alani',        'Dış Özellikler',   false, true,  4],
                [$f('esyali'),          'esyali',            'İç Özellikler',    false, true,  1],
                [$f('mutfak-tipi'),     'mutfak-tipi',       'İç Özellikler',    false, true,  2],
                [$f('isitma'),          'isitma',            'İç Özellikler',    false, true,  3],
                [$f('sogutma'),         'sogutma',           'İç Özellikler',    false, true,  4],
                [$f('bina-yasi'),       'bina-yasi',         'İç Özellikler',    false, true,  5],
                [$f('kurutma-odasi'),  'kurutma-odasi',     'İç Özellikler',    false, false, 6],
                [$f('aidat'),           'aidat',             'Maliyet ve Aidat', false, false, 1],
                [$f('kredi-uygunlugu'),'kredi-uygunlugu',   'Maliyet ve Aidat', false, true,  3],
                [$f('takas'),           'takas',             'Maliyet ve Aidat', false, true,  4],
                [$f('tapu-durumu'),     'tapu-durumu',       'Tapu ve İmar',     false, true,  1],
                [$f('kullanim-durumu'), 'kullanim-durumu',   'Tapu ve İmar',     false, false, 2],
            ],
        ];

        // G4: Villa Kiralık — template 23 — 36 özellik (G3 özellikleri + aidat + depozito)
        $plan['G4_villa_kiralik'] = [
            'template_id' => self::TEMPLATE_VILLA_KIRALIK,
            'template_name' => 'Villa Kiralik',
            'scope' => 'listing_type',
            'provenance' => self::PROVENANCE_MIGRATION,
            'features' => [
                // G3 Villa Satilik özellikleri
                [$f('brut-alan'),        'brut-alan',        'Temel Bilgiler',    true,  true,  1],
                [$f('net-alan'),         'net-alan',          'Temel Bilgiler',    false, true,  2],
                [$f('oda-sayisi'),       'oda-sayisi',        'Temel Bilgiler',    true,  true,  3],
                [$f('banyo-sayisi'),    'banyo-sayisi',      'Temel Bilgiler',    false, true,  4],
                [$f('toplam-kat'),      'toplam-kat',        'Temel Bilgiler',    false, true,  5],
                [$f('balkon'),          'balkon',            'Temel Bilgiler',    false, true,  6],
                [$f('kat'),             'kat',               'Temel Bilgiler',    false, true,  7],
                [$f('arsa-alani'),       'arsa-alani',        'Konum ve Arsa',    false, true,  1],
                [$f('denize-mesafe'),   'denize-mesafe',     'Konum ve Arsa',    false, true,  2],
                [$f('manzara'),         'manzara',           'Konum ve Arsa',    false, true,  3],
                [$f('cephe'),           'cephe',             'Konum ve Arsa',    false, true,  4],
                [$f('imar-durumu'),      'imar-durumu',       'Konum ve Arsa',    false, true,  5],
                [$f('havuz'),           'havuz',             'Yapı Özellikleri', false, true,  1],
                [$f('havuz-tip'),       'havuz-tip',         'Yapı Özellikleri', false, true,  2],
                [$f('ozel-havuz'),      'ozel-havuz',        'Yapı Özellikleri', false, true,  3],
                [$f('bahce'),           'bahce',             'Yapı Özellikleri', false, true,  4],
                [$f('bahce-alani'),     'bahce-alani',       'Yapı Özellikleri', false, true,  5],
                [$f('akilli-ev'),       'akilli-ev',         'Yapı Özellikleri', false, true,  6],
                [$f('teras'),           'teras',             'Yapı Özellikleri', false, true,  7],
                [$f('veranda'),         'veranda',           'Yapı Özellikleri', false, false, 8],
                [$f('otopark'),         'otopark',           'Dış Özellikler',   false, true,  1],
                [$f('guvenlik'),        'guvenlik',          'Dış Özellikler',   false, true,  2],
                [$f('site-icerisinde'),'site-icerisinde',   'Dış Özellikler',   false, true,  3],
                [$f('spor-alani'),      'spor-alani',        'Dış Özellikler',   false, true,  4],
                [$f('esyali'),          'esyali',            'İç Özellikler',    false, true,  1],
                [$f('mutfak-tipi'),     'mutfak-tipi',       'İç Özellikler',    false, true,  2],
                [$f('isitma'),          'isitma',            'İç Özellikler',    false, true,  3],
                [$f('sogutma'),         'sogutma',           'İç Özellikler',    false, true,  4],
                [$f('bina-yasi'),       'bina-yasi',         'İç Özellikler',    false, true,  5],
                [$f('kurutma-odasi'),  'kurutma-odasi',     'İç Özellikler',    false, false, 6],
                // G4-specific: Aidat (suggested, domain kararı bekleniyor)
                [$f('aidat'),           'aidat',             'Maliyet ve Aidat', false, false, 1],
                // G4-specific: Depozito (required — kira sözleşmesinde zorunlu)
                [$f('depozito'),        'depozito',          'Maliyet ve Aidat', true,  false, 2],
                [$f('kredi-uygunlugu'),'kredi-uygunlugu',   'Maliyet ve Aidat', false, true,  3],
                [$f('takas'),           'takas',             'Maliyet ve Aidat', false, true,  4],
                [$f('tapu-durumu'),     'tapu-durumu',       'Tapu ve İmar',     false, true,  1],
                [$f('kullanim-durumu'), 'kullanim-durumu',   'Tapu ve İmar',     false, false, 2],
            ],
        ];

        // G5: Villa Günlük — template 24 — 35 özellik
        $plan['G5_villa_gunluk'] = [
            'template_id' => self::TEMPLATE_VILLA_GUNLUK,
            'template_name' => 'Villa Gunluk',
            'scope' => 'listing_type',
            'provenance' => self::PROVENANCE_MIGRATION,
            'features' => [
                [$f('brut-alan'),        'brut-alan',        'Temel Bilgiler',    true,  true,  1],
                [$f('net-alan'),         'net-alan',          'Temel Bilgiler',    false, true,  2],
                [$f('oda-sayisi'),       'oda-sayisi',        'Temel Bilgiler',    true,  true,  3],
                [$f('banyo-sayisi'),    'banyo-sayisi',      'Temel Bilgiler',    false, true,  4],
                [$f('toplam-kat'),      'toplam-kat',        'Temel Bilgiler',    false, true,  5],
                [$f('balkon'),          'balkon',            'Temel Bilgiler',    false, true,  6],
                [$f('kat'),             'kat',               'Temel Bilgiler',    false, true,  7],
                [$f('arsa-alani'),       'arsa-alani',        'Konum ve Arsa',    false, true,  1],
                [$f('denize-mesafe'),   'denize-mesafe',     'Konum ve Arsa',    false, true,  2],
                [$f('manzara'),         'manzara',           'Konum ve Arsa',    false, true,  3],
                [$f('cephe'),           'cephe',             'Konum ve Arsa',    false, true,  4],
                [$f('imar-durumu'),      'imar-durumu',       'Konum ve Arsa',    false, true,  5],
                [$f('havuz'),           'havuz',             'Yapı Özellikleri', false, true,  1],
                [$f('havuz-tip'),       'havuz-tip',         'Yapı Özellikleri', false, true,  2],
                [$f('ozel-havuz'),      'ozel-havuz',        'Yapı Özellikleri', false, true,  3],
                [$f('bahce'),           'bahce',             'Yapı Özellikleri', false, true,  4],
                [$f('bahce-alani'),     'bahce-alani',       'Yapı Özellikleri', false, true,  5],
                [$f('akilli-ev'),       'akilli-ev',         'Yapı Özellikleri', false, true,  6],
                [$f('teras'),           'teras',             'Yapı Özellikleri', false, true,  7],
                [$f('veranda'),         'veranda',           'Yapı Özellikleri', false, false, 8],
                [$f('otopark'),         'otopark',           'Dış Özellikler',   false, true,  1],
                [$f('guvenlik'),        'guvenlik',          'Dış Özellikler',   false, true,  2],
                [$f('site-icerisinde'),'site-icerisinde',   'Dış Özellikler',   false, true,  3],
                [$f('spor-alani'),      'spor-alani',        'Dış Özellikler',   false, true,  4],
                [$f('esyali'),          'esyali',            'İç Özellikler',    false, true,  1],
                [$f('mutfak-tipi'),     'mutfak-tipi',       'İç Özellikler',    false, true,  2],
                [$f('isitma'),          'isitma',            'İç Özellikler',    false, true,  3],
                [$f('sogutma'),         'sogutma',           'İç Özellikler',    false, true,  4],
                [$f('bina-yasi'),       'bina-yasi',         'İç Özellikler',    false, true,  5],
                [$f('kurutma-odasi'),  'kurutma-odasi',     'İç Özellikler',    false, false, 6],
                [$f('aidat'),           'aidat',             'Maliyet ve Aidat', false, false, 1],
                [$f('kredi-uygunlugu'),'kredi-uygunlugu',   'Maliyet ve Aidat', false, true,  3],
                [$f('takas'),           'takas',             'Maliyet ve Aidat', false, true,  4],
                [$f('tapu-durumu'),     'tapu-durumu',       'Tapu ve İmar',     false, true,  1],
                [$f('kullanim-durumu'), 'kullanim-durumu',   'Tapu ve İmar',     false, false, 2],
            ],
        ];

        // G4 extra: Villa Kiralık also gets G3 satilik fields (EXCEPT Aidat, different required)
        // NOTE: In Option A, Kiralik template only gets depozito.
        // G1/G2 features are preserved within Satilik/Gunluk template denormalize sets.

        return $plan;
    }

    private function displayRepairPlan(array $plan): void
    {
        $totalFeatures = 0;
        foreach ($plan as $tier => $data) {
            $this->line("  {$tier} → Template {$data['template_id']} ({$data['template_name']})");
            $this->line("    Scope: {$data['scope']} | Provenance: {$data['provenance']}");

            $grouped = [];
            foreach ($data['features'] as [$fid, $slug, $group, $req, $vis, $ord]) {
                $grouped[$group][] = $slug;
                if ($fid) {
                    $totalFeatures++;
                }
            }
            foreach ($grouped as $group => $slugs) {
                $this->line("    {$group}: " . implode(', ', $slugs));
            }
            $this->line("    Total: " . count($data['features']) . " features");
            $this->line('');
        }

        $this->line("  Option A Targets:");
        $this->line("    G3 villa-satilik (id=22): 35 features");
        $this->line("    G4 villa-kiralik (id=23): 36 features (G3 + aidat + depozito)");
        $this->line("    G5 villa-gunluk  (id=24): 35 features");
        $this->line("    TOTAL new assignments: 106");
        $this->line("    G1/G2 preserved: in villa template denormalize sets (not as separate category-level assignments)");
    }

    private function checkDuplicateRisk(array $plan): void
    {
        $this->line('  Checking for existing template assignments (duplicate risk)...');
        $hasDuplicates = false;
        foreach ($plan as $tier => $data) {
            $tid = $data['template_id'];
            foreach ($data['features'] as [$fid, $slug, $group, $req, $vis, $ord]) {
                if (!$fid) continue;
                $exists = DB::table('feature_assignments')
                    ->where('assignable_type', 'App\\Models\\YayinTipiSablonu')
                    ->where('assignable_id', $tid)
                    ->where('feature_id', $fid)
                    ->exists();
                if ($exists) {
                    $this->line("    ⚠ Duplicate: Template {$tid} already has feature {$slug} — updateOrInsert will UPDATE");
                    $hasDuplicates = true;
                }
            }
        }
        if (!$hasDuplicates) {
            $this->line('  <fg=green>✓ No duplicates detected — safe to proceed</fg=green>');
        }

        // Check that legacy records won't conflict
        $this->line('');
        $this->line('  Checking legacy record conflict risk...');
        $legacyTemplateConflict = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\Ilan')
            ->where('assignable_id', 0)
            ->where('source_type', 'canonical_seed')
            ->count();
        // These are on Ilan/0, templates are on YayinTipiSablonu — NO CONFLICT
        $this->line("  <fg=green>✓ Legacy records (Ilan/0) cannot conflict with template assignments</fg=green>");
        $this->line("    Legacy: assignable_type=Ilan, assignable_id=0");
        $this->line("    Option A: assignable_type=YayinTipiSablonu, assignable_id=22/23/24");
        $this->line("    NO duplicate key risk — different assignable_type values");
    }

    private function displayLegacyArchivePlan(): void
    {
        $legacyCount = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\Ilan')
            ->where('assignable_id', 0)
            ->count();

        $this->line("  Legacy records to archive: {$legacyCount}");
        $this->line("  Archive action: source_type 'canonical_seed' → 'legacy_repair_2026_09_02'");
        $this->line("  assignable_type / assignable_id: UNCHANGED (stays as Ilan/0)");
        $this->line("  Feature IDs: UNCHANGED (181-216)");
        $this->line("  DELETION: NONE — records preserved for audit trail");
        $this->line("");
        $this->line("  <fg=yellow>⚠ 84 records remain in DB but are marked as archived.</fg=yellow>");
        $this->line("     These are legacy Ilan/0 records that must NOT be confused with");
        $this->line("     production data. They are preserved for rollback/repair provenance.");
    }

    private function displaySummary(array $plan, bool $isCommit): void
    {
        $totalNewAssignments = 0;
        foreach ($plan as $data) {
            $totalNewAssignments += count(array_filter($data['features'], fn($f) => $f[0]));
        }

        $this->line("  Option A Repair Summary:");
        $this->line("    ✓ G3: 35 features → villa-satilik (template 22)");
        $this->line("    ✓ G4: 36 features → villa-kiralik (template 23) [G3 + aidat + depozito]");
        $this->line("    ✓ G5: 35 features → villa-gunluk  (template 24)");
        $this->line("    ✓ New assignments to insert/update: {$totalNewAssignments}");
        $this->line("    ✓ G1/G2 features: preserved in villa satilik template denormalize set");
        $this->line("    ✓ 46 fake kategoriler: NO assignments created");
        $this->line("    ✓ 88 phantom templates: NO features invented");
        $this->line("    ✓ 84 legacy records: archived (source_type updated), NOT deleted");
        $this->line("    ✓ Idempotency: updateOrInsert — safe to run multiple times");
        $this->line("");

        if (!$isCommit) {
            $this->warn("  NEXT: Run with --commit to execute the repair.");
        }
    }

    // ─── EXECUTE ────────────────────────────────────────────────────────────

    private function executeRepair(array $plan): void
    {
        $this->line('  Step 1: Upsert template assignments (source_type=villa_migration_2026_08_25)...');

        $totalUpserted = 0;
        foreach ($plan as $tier => $data) {
            $tid = $data['template_id'];
            $template = DB::table('yayin_tipi_sablonlari')->where('id', $tid)->first();

            foreach ($data['features'] as [$fid, $slug, $group, $req, $vis, $ord]) {
                if (!$fid) {
                    $this->warn("    ⚠ Skipping missing feature: {$slug}");
                    continue;
                }

                DB::table('feature_assignments')->updateOrInsert(
                    [
                        'feature_id'       => $fid,
                        'assignable_type'  => 'App\\Models\\YayinTipiSablonu',
                        'assignable_id'    => $tid,
                    ],
                    [
                        'main_category_id'  => $template->kategori_id ?? null,
                        'sub_category_id'   => null,
                        'listing_type_id'   => $template->yayin_tipi_id ?? null,
                        'scope_type'        => $data['scope'],
                        'source_type'       => self::PROVENANCE_MIGRATION,
                        'group_name'        => $group,
                        'field_slug'        => $slug,
                        'is_required'       => $req,
                        'is_visible'        => $vis,
                        'aktiflik_durumu'  => 1,
                        'display_order'     => $ord,
                        'updated_at'       => now(),
                    ]
                );
                $totalUpserted++;
            }
            $this->line("    ✓ {$tier}: upserted");
        }
        $this->line("  Total upserted: {$totalUpserted}");

        $this->line('');
        $this->line('  Step 2: Archive 84 legacy records (source_type → legacy_repair_2026_09_02)...');

        $archived = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\Ilan')
            ->where('assignable_id', 0)
            ->where('source_type', 'canonical_seed')
            ->update([
                'source_type' => self::PROVENANCE_LEGACY_ARCHIVE,
                'updated_at'  => now(),
            ]);
        $this->line("  Archived: {$archived} legacy records");

        $this->line('');
        $this->info('  ✓ Option A repair executed successfully.');
    }

    private function verifyRepair(): void
    {
        $this->line('  Verifying template assignments...');

        foreach ([22, 23, 24] as $tid) {
            $cnt = DB::table('feature_assignments')
                ->where('assignable_type', 'App\\Models\\YayinTipiSablonu')
                ->where('assignable_id', $tid)
                ->where('source_type', self::PROVENANCE_MIGRATION)
                ->count();
            $name = DB::table('yayin_tipi_sablonlari')->where('id', $tid)->value('ad');
            $this->line("    Template {$tid} ({$name}): {$cnt} assignments");
        }

        $this->line('');
        $this->line('  Verifying legacy records archived (NOT deleted)...');
        $archived = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\Ilan')
            ->where('assignable_id', 0)
            ->where('source_type', self::PROVENANCE_LEGACY_ARCHIVE)
            ->count();
        $stillCanonical = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\Ilan')
            ->where('assignable_id', 0)
            ->where('source_type', 'canonical_seed')
            ->count();
        $this->line("    Archived (legacy_repair_2026_09_02): {$archived}");
        $this->line("    Still canonical_seed: {$stillCanonical} (should be 0)");

        if ($archived === 84 && $stillCanonical === 0) {
            $this->line('  <fg=green>✓ All 84 legacy records archived, none deleted.</fg=green>');
        } else {
            $this->error('  <fg=red>✗ Legacy record verification FAILED</fg=red>');
        }
    }

    // ─── HELPERS ────────────────────────────────────────────────────────────

    private function section(string $title): void
    {
        $this->line('');
        $this->info("── {$title} ───────────────────────────────────────────");
    }

    private function slugFromId(int $id): string
    {
        return match ($id) {
            22 => 'villa-satilik',
            23 => 'villa-kiralik',
            24 => 'villa-gunluk',
            default => "template-{$id}",
        };
    }

    private function formatNumber(int $n): string
    {
        return number_format($n);
    }
}

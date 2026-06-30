<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SystemMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:maintenance {action=cleanup-docs : The action to perform}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform system maintenance tasks like documentation cleanup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        if ($action === 'cleanup-docs') {
            $this->cleanupDocs();
        } else {
            $this->error("Unknown action: {$action}");
        }
    }

    /**
     * Consolidates and cleans up documentation files based on legacy scripts.
     */
    protected function cleanupDocs()
    {
        $docsDir = base_path('docs');
        $archiveDir = $docsDir . '/archive-2026-01-03';
        $consolidatedDir = $docsDir . '/consolidated';

        if (!File::exists($archiveDir)) {
            File::makeDirectory($archiveDir, 0755, true);
        }

        if (!File::exists($consolidatedDir)) {
            File::makeDirectory($consolidatedDir, 0755, true);
        }

        $this->info("Starting Documentation Cleanup...");

        // 1. Archive Phase Files (Cleanup-docs.sh logic)
        $phaseFiles = [
            "PHASE1_COMPLETE_RECAP.md", "PHASE2_FINAL_STATUS.md", "PHASE2_MARKET_INTELLIGENCE_IMPLEMENTATION.md",
            "PHASE6_PROGRESS.md", "PHASE_2_3_CONTEXT7_FAILURE_ANALYSIS_2026_01_03.md", "PHASE_2_3_FINAL_REPORT_2026_01_03.md",
            "PHASE_3_FINAL_GLOBAL_EXEMPTION_2026-01-02.md", "PHASE_3_FINAL_ULTIMATE_SEALED_2026-01-02.md",
            "PHASE_3_MUHURLEME_COMPLETE_2026-01-02.md", "PHASE_3_REALITY_CHECK_FINAL_2026-01-02.md",
            "PHASE_3_ULTIMATE_FINAL_LEGACY_SUPPRESSED_2026-01-02.md", "PHASE_3_V2_FIRST_FINAL_2026-01-02.md",
            "PHASE_4_COMPLETION_SUMMARY_2026-01-02.md", "PHASE_4_STEP_1_COMPLETION_2026-01-02.md",
            "PHASE_4_STEP_2_AUTHENTICATION_2026-01-02.md", "PHASE_4_STEP_3_DATABASE_TESTING_2026-01-02.md",
            "PHASE_4_TEMPLATE_FEATURE_INTEGRATION.md", "PHASE_5_COMPLETION_REPORT.md",
            "PHASE_6_COMPLETION_REPORT.md", "PHASE_6_DEPLOYMENT_SUMMARY.md",
            "PHASE_8_DEPLOYMENT_GUIDE.md", "PHASE_8_IMPLEMENTATION_SUMMARY.md",
            "PHASE_8_SMART_MATCHING_PLAN.md", "PHASE_9_12_ROADMAP.md"
        ];

        foreach ($phaseFiles as $file) {
            $this->moveToArchive($docsDir . '/' . $file, $archiveDir);
        }

        // 2. Delete Duplicates (Cleanup-docs.sh logic)
        $deleteIlanFiles = [
            "ILAN_AKSIYONLAR.md", "ILAN_MIMARI.md", "ILAN_OZET_RAPOR.md",
            "ILAN_YONETIMI_ANALIZ.md", "ILAN_YONETIMI_KOMPLE_ANALIZ.md",
            "ILAN_MANAGEMENT_ANALYSIS_2025-12-28.md"
        ];

        foreach ($deleteIlanFiles as $file) {
            if (File::exists($docsDir . '/' . $file)) {
                File::delete($docsDir . '/' . $file);
                $this->warn("Deleted duplicate: {$file}");
            }
        }

        // 3. Consolidated Files (Phase 3 logic)
        $this->consolidateSystemDocs($docsDir, $consolidatedDir, $archiveDir);

        $this->info("Cleanup completed successfully!");
    }

    protected function moveToArchive($path, $archiveDir)
    {
        if (File::exists($path)) {
            $filename = basename($path);
            File::move($path, $archiveDir . '/' . $filename);
            $this->comment("Archived: {$filename}");
        }
    }

    protected function consolidateSystemDocs($docsDir, $consolidatedDir, $archiveDir)
    {
        // Example consolidation: FULL_SYSTEM_DOCS.md
        $systemFiles = [
            "ADDRESS_SYSTEM_ANALYSIS.md",
            "BEKCI_SYSTEM_ANALYSIS_2026_01_03.md",
            "SYSTEM_STRUCTURE_AUDIT_2026-01-03.md"
        ];

        $content = "# 🏗️ YALIHAN EMLAK - FULL SYSTEM DOCUMENTATION\n\n";
        $content .= "**Tarih:** " . now()->toDateString() . "\n\n";

        foreach ($systemFiles as $file) {
            $path = $docsDir . '/' . $file;
            if (File::exists($path)) {
                $content .= "## 📄 SOURCE: {$file}\n\n";
                $content .= File::get($path) . "\n\n";
                $this->moveToArchive($path, $archiveDir);
            }
        }

        File::put($consolidatedDir . '/FULL_SYSTEM_DOCS.md', $content);
        $this->info("Created: FULL_SYSTEM_DOCS.md");
    }
}

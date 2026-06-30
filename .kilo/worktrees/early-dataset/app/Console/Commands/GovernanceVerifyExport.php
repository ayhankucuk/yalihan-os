<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\GovernanceCore\Export\HashChainValidator;
use Illuminate\Console\Command;

/**
 * Governance Export Verification Command
 *
 * Sprint 20: CLI tool for hash chain integrity validation.
 *
 * Usage:
 *   php artisan governance:verify-export storage/exports/TENANT-2026-02-18.json
 *
 * Validates:
 * - Export format version
 * - Timeline entry count
 * - Hash chain continuity
 * - Signature authenticity
 * - Snapshot SHA256 integrity
 *
 * Exit codes:
 * - 0: Verification passed
 * - 1: Verification failed or file invalid
 */
class GovernanceVerifyExport extends Command
{
    /**
     * Command signature.
     *
     * @var string
     */
    protected $signature = 'governance:verify-export
                            {file : Path to exported JSON file}
                            {--verbose : Show detailed validation steps}
                            {--replay : Generate replay log}';

    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'Verify integrity of exported governance timeline (hash chain + signatures)';

    /**
     * Execute the console command.
     *
     * @return int Exit code (0 = success, 1 = failure)
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            return 1;
        }

        $this->info("🔍 Verifying export: " . basename($filePath));
        $this->newLine();

        try {
            $validator = app(HashChainValidator::class);
            $result = $validator->verify($filePath, [
                'verbose' => $this->option('verbose'),
                'generate_replay' => $this->option('replay'),
            ]);

            // Display results
            $this->displayResults($result);

            // Generate replay log if requested
            if ($this->option('replay') && $result['replay_log']) {
                $this->info("📝 Replay Log: {$result['replay_log']}");
            }

            return $result['all_valid'] ? 0 : 1;

        } catch (\InvalidArgumentException $e) {
            $this->error("❌ Invalid Export Format: {$e->getMessage()}");
            return 1;

        } catch (\Exception $e) {
            $this->error("❌ Verification Failed: {$e->getMessage()}");
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Display verification results.
     *
     * @param array $result Verification result from HashChainValidator
     * @return void
     */
    private function displayResults(array $result): void
    {
        // Metadata
        $this->info("✅ Export Version: {$result['metadata']['version']}");
        $this->info("✅ Tenant ID: {$result['metadata']['tenant_id']}");
        $this->info("✅ Exported At: {$result['metadata']['exported_at']}");
        $this->info("✅ Exported By: {$result['metadata']['exported_by']}");
        $this->newLine();

        // Timeline validation
        $this->info("✅ Timeline Entries: {$result['entry_count']}");

        if ($this->option('verbose')) {
            $this->info("   ├─ First Entry: {$result['timeline_summary']['first_hash']}");
            $this->info("   └─ Last Entry: {$result['timeline_summary']['last_hash']}");
        }
        $this->newLine();

        // Hash chain validation
        $chainStatus = $result['chain_valid'] ? '✅ VALID' : '❌ INVALID';
        $this->line("📋 Hash Chain Integrity: {$chainStatus}");

        if (!$result['chain_valid'] && isset($result['chain_errors'])) {
            foreach ($result['chain_errors'] as $error) {
                $this->warn("   ⚠️  {$error}");
            }
        }
        $this->newLine();

        // Signature validation
        $signatureStatus = $result['signature_valid'] ? '✅ PASS' : '❌ FAIL';
        $this->line("🔐 Signature Verification: {$signatureStatus}");

        if ($this->option('verbose') && isset($result['signature_algorithm'])) {
            $this->info("   ├─ Algorithm: {$result['signature_algorithm']}");
            $this->info("   └─ Key Length: {$result['signature_key_length']} bits");
        }
        $this->newLine();

        // Snapshot integrity
        $snapshotStatus = $result['snapshot_integrity_valid'] ? '✅ VALID' : '❌ INVALID';
        $this->line("📦 Snapshot Integrity: {$snapshotStatus}");

        if (!$result['snapshot_integrity_valid'] && isset($result['snapshot_errors'])) {
            foreach ($result['snapshot_errors'] as $error) {
                $this->warn("   ⚠️  {$error}");
            }
        }
        $this->newLine();

        // Performance stats
        if (isset($result['validation_time_ms'])) {
            $this->info("⏱️  Total Validation Time: {$result['validation_time_ms']}ms");
        }

        // Overall verdict
        $this->newLine();
        if ($result['all_valid']) {
            $this->info('🎉 Export is VALID and untempered.');
        } else {
            $this->error('⚠️  Export validation FAILED. Do not trust this export.');
        }
    }
}

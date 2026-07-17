<?php

namespace App\Console\Commands\PropertyIntegrity;

use App\Domain\PropertyDocument\Models\PropertyDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ExpireDocumentsCommand
 *
 * Sprint 12D — Daily document expiry scheduler.
 *
 * Finds all ACTIVE documents where son_gecerlilik_tarihi < today
 * and marks them as SURESI_DOLMUS.
 *
 * Idempotent: Documents already marked as expired are skipped.
 *
 * Usage: php artisan property:expire-documents
 */
class ExpireDocumentsCommand extends Command
{
    protected $signature = 'property:expire-documents
                            {--dry-run : Show what would be expired without making changes}';

    protected $description = 'Mark expired PropertyDocuments as SURESI_DOLMUS';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $toExpire = PropertyDocument::query()
            ->where('durum', PropertyDocument::STATUS_AKTIF)
            ->whereNotNull('son_gecerlilik_tarihi')
            ->where('son_gecerlilik_tarihi', '<', now()->toDateString())
            ->get();

        if ($toExpire->isEmpty()) {
            $this->info('✅ No documents require expiry processing');
            return Command::SUCCESS;
        }

        $this->info("Found {$toExpire->count()} document(s) to expire:");

        $tableData = $toExpire->map(fn ($doc) => [
            $doc->id,
            $doc->property_id,
            $doc->dokuman_tipi,
            $doc->referans_no ?? '-',
            $doc->son_gecerlilik_tarihi?->toDateString(),
        ])->toArray();

        $this->table(
            ['ID', 'Property', 'Type', 'Reference', 'Expired On'],
            $tableData
        );

        if ($dryRun) {
            $this->warn('DRY RUN — no changes made');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($toExpire as $doc) {
            $doc->markExpired();
            $count++;

            Log::channel('daily')->info('PropertyDocumentExpired', [
                'document_id' => $doc->id,
                'property_id' => $doc->property_id,
                'dokuman_tipi' => $doc->dokuman_tipi,
                'son_gecerlilik_tarihi' => $doc->son_gecerlilik_tarihi?->toDateString(),
                'processed_at' => now()->toIso8601String(),
            ]);
        }

        $this->info("✅ Expired {$count} document(s)");
        return Command::SUCCESS;
    }
}

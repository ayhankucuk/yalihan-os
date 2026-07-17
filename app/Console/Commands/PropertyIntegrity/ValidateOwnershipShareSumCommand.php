<?php

namespace App\Console\Commands\PropertyIntegrity;

use App\Domain\Property\Models\Property;
use App\Domain\PropertyOwnership\Models\PropertyOwnership;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ValidateOwnershipShareSumCommand
 *
 * Sprint 12D — Daily integrity check.
 *
 * Verifies that for every Property, the sum of active ownership shares = 1.0000.
 * This is a monitoring/integrity mechanism — the primary protection is in
 * PropertyOwnershipService::validateShareSum() which runs within each transaction.
 *
 * This command detects data corruption that may have occurred through:
 * - Direct database modifications
 * - Race conditions (if not properly serialized)
 * - Migration errors
 *
 * Usage: php artisan property:validate-share-sums
 */
class ValidateOwnershipShareSumCommand extends Command
{
    protected $signature = 'property:validate-share-sums
                            {--property-id= : Validate only this property}
                            {--fix : Attempt to log and alert (read-only, no auto-fix)}';

    protected $description = 'Validate that all Properties have ownership shares summing to 1.0000';

    public function handle(): int
    {
        $propertyId = $this->option('property-id');
        $shouldFix = $this->option('fix');

        $query = PropertyOwnership::query()
            ->selectRaw('property_id, SUM(pay_orani) as total_share, COUNT(*) as owner_count')
            ->whereNull('bitis_tarihi')
            ->groupBy('property_id');

        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        $violations = $query->get()->filter(function ($row) {
            return bccomp($row->total_share, '1.0000', 4) !== 0;
        });

        if ($violations->isEmpty()) {
            $this->info('✅ All properties have valid ownership share sums (1.0000)');
            return Command::SUCCESS;
        }

        $this->error("❌ Found {$violations->count()} properties with invalid share sums:");
        $this->table(
            ['Property ID', 'Total Share', 'Owner Count', 'Status'],
            $violations->map(fn ($v) => [
                $v->property_id,
                $v->total_share,
                $v->owner_count,
                $v->total_share > 1 ? 'OVER (+' . bcsub($v->total_share, '1.0000', 4) . ')' : 'UNDER',
            ])->toArray()
        );

        foreach ($violations as $violation) {
            Log::channel('daily')->warning('PropertyOwnershipShareViolation', [
                'property_id' => $violation->property_id,
                'total_share' => $violation->total_share,
                'owner_count' => $violation->owner_count,
                'detected_at' => now()->toIso8601String(),
            ]);
        }

        $this->warn('This is a data integrity alert. Investigate and resolve manually.');
        return Command::FAILURE;
    }
}

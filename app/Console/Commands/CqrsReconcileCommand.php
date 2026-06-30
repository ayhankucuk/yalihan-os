<?php

namespace App\Console\Commands;

use App\Events\ListingCreated;
use App\Events\ListingUpdated;
use App\Listeners\ListingProjector;
use App\Models\Ilan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CqrsReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cqrs:reconcile {--rebuild : Truncate and rebuild all read models from scratch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🛡️ Verify and heal CQRS read model drift with write model';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rebuild = $this->option('rebuild');

        if ($rebuild) {
            $this->warn('⚠️ Rebuilding all listings projections from scratch...');
            DB::table('proj_listings')->truncate();
            DB::table('proj_event_offsets')->where('projector_name', ListingProjector::class)->delete();
            $this->info('proj_listings table truncated.');
        }

        $this->info('🔍 Scanning for write-to-read model drift...');

        $projector = new ListingProjector();

        $totalCount = 0;
        $missingCount = 0;
        $driftCount = 0;
        $syncedCount = 0;

        Ilan::chunk(100, function ($listings) use ($projector, &$totalCount, &$missingCount, &$driftCount, &$syncedCount) {
            foreach ($listings as $listing) {
                $totalCount++;

                $proj = DB::table('proj_listings')->where('ilan_id', $listing->id)->orderBy('id')->first();

                // Convert Ilan yayin_durumu to integer for the Event's strict typehint
                $yayinDurumuInt = 0;
                if ($listing->yayin_durumu instanceof \App\Enums\IlanDurumu) {
                    $yayinDurumuInt = $listing->yayin_durumu->isActive() ? 1 : 0;
                } elseif (is_string($listing->yayin_durumu)) {
                    $norm = \App\Enums\IlanDurumu::normalize($listing->yayin_durumu);
                    $yayinDurumuInt = ($norm && $norm->isActive()) ? 1 : 0;
                } elseif (is_numeric($listing->yayin_durumu)) {
                    $yayinDurumuInt = (int)$listing->yayin_durumu;
                }

                if (!$proj) {
                    $missingCount++;
                    $this->line("Listing #{$listing->id} is missing in read model. Re-projecting...", 'comment');

                    // Create event and project
                    $event = new ListingCreated(
                        $listing->id,
                        $listing->baslik ?? '',
                        $yayinDurumuInt,
                        (float)($listing->fiyat ?? 0),
                        $listing->para_birimi_id ?? 1,
                        $listing->danisman_id,
                        $listing->kategori_id,
                        $listing->il_id
                    );

                    $projector->handleListingCreated($event);

                } else {
                    // Check for drift in title, price, or status
                    $hasDrift = false;
                    $priceChanged = false;
                    $statusChanged = false;

                    if ($proj->baslik !== $listing->baslik) {
                        $hasDrift = true;
                    }
                    if ((float)$proj->fiyat !== (float)$listing->fiyat) {
                        $hasDrift = true;
                        $priceChanged = true;
                    }

                    // Compare yayin_durumu as string values
                    $listingStatusStr = $listing->yayin_durumu instanceof \App\Enums\IlanDurumu 
                        ? $listing->yayin_durumu->value 
                        : (string)$listing->yayin_durumu;

                    if (mb_strtolower((string)$proj->yayin_durumu) !== mb_strtolower($listingStatusStr)) {
                        $hasDrift = true;
                        $statusChanged = true;
                    }

                    if ($hasDrift) {
                        $driftCount++;
                        $this->line("Listing #{$listing->id} has drift in read model. Updating...", 'comment');

                        $event = new ListingUpdated(
                            $listing->id,
                            $listing->baslik,
                            $yayinDurumuInt,
                            (float)$listing->fiyat,
                            $listing->para_birimi_id ?? 1,
                            $listing->danisman_id,
                            $listing->kategori_id,
                            $listing->il_id,
                            $statusChanged,
                            $priceChanged,
                            false
                        );

                        $projector->handleListingUpdated($event);

                    } else {
                        $syncedCount++;
                    }
                }
            }
        });

        $this->info("\n📊 CQRS Reconciliation Complete:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Write Listings', $totalCount],
                ['Synced Listings', $syncedCount],
                ['Missing in Read Model (Healed)', $missingCount],
                ['Drift in Read Model (Healed)', $driftCount],
            ]
        );

        Log::info("🔄 CQRS Reconciled completed", [
            'total' => $totalCount,
            'missing' => $missingCount,
            'drift' => $driftCount
        ]);

        return 0;
    }
}

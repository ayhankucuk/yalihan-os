<?php

namespace App\Console\Commands;

use App\Models\Ilan;
use App\Enums\IlanDurumu;
use App\Services\Portfolio\TapuMatchService;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PortfolioImport extends Command
{
    protected $signature = 'portfolio:import {file} {--dry-run : Preview import without saving}';
    protected $description = 'Import portfolio listings from JSON file with EİDS tapu matching';

    protected TapuMatchService $tapuMatchService;
    protected IlanCrudService $ilanCrudService;

    public function __construct(TapuMatchService $tapuMatchService, IlanCrudService $ilanCrudService)
    {
        parent::__construct();
        $this->tapuMatchService = $tapuMatchService;
        $this->ilanCrudService = $ilanCrudService;
    }

    public function handle()
    {
        $filePath = $this->argument('file');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $data = json_decode(file_get_contents($filePath), true);
        if (!$data || !isset($data['listings'])) {
            $this->error('Invalid JSON format. Expected: {"import_metadata": {...}, "listings": [...]}');
            return 1;
        }

        // ✅ VALIDATION: Check JSON payload structure
        $metadata = $data['import_metadata'] ?? [];
        $listings = $data['listings'];

        // Validate each listing
        $errors = [];
        foreach ($listings as $idx => $listing) {
            $num = $idx + 1;

            if (empty($listing['external_ref'])) {
                $errors[] = "Listing #{$num}: Missing external_ref";
            }

            if (empty($listing['yayin_tipi'])) {
                $errors[] = "Listing #{$num}: Missing yayin_tipi";
            } elseif (!in_array($listing['yayin_tipi'], ['satilik', 'kiralik'])) {
                $errors[] = "Listing #{$num}: Invalid yayin_tipi '{$listing['yayin_tipi']}' (expected: satilik or kiralik)";
            }

            if (empty($listing['kategori'])) {
                $errors[] = "Listing #{$num}: Missing kategori";
            } elseif (!in_array($listing['kategori'], ['konut', 'arsa_arazi', 'villa_isyeri'])) {
                $errors[] = "Listing #{$num}: Invalid kategori '{$listing['kategori']}'";
            }

            if (empty($listing['alt_kategori'])) {
                $errors[] = "Listing #{$num}: Missing alt_kategori";
            }

            if (empty($listing['baslik'])) {
                $errors[] = "Listing #{$num}: Missing baslik";
            }
        }

        if (!empty($errors)) {
            $this->error("❌ Validation failed:");
            foreach ($errors as $error) {
                $this->line("   • {$error}");
            }
            return 1;
        }

        $this->info("📦 Portfolio Import");
        $this->line("   Source: {$filePath}");
        $this->line("   Date: " . ($metadata['import_date'] ?? 'N/A'));
        $this->line("   Approved By: " . ($metadata['approved_by'] ?? 'N/A'));
        $this->line("   Total Listings: " . count($listings));
        $this->line("   Dry Run: " . ($dryRun ? 'YES' : 'NO'));
        $this->line('');

        $created = 0;
        $updated = 0;
        $failed = 0;
        $tapuMatched = 0;
        $pendingTapu = 0;
        $reconciledTapu = 0;

        DB::beginTransaction();

        try {
            foreach ($listings as $idx => $listing) {
                $num = $idx + 1;
                $externalRef = $listing['external_ref'] ?? null;
                $baslik = $listing['baslik'] ?? 'Untitled';

                $this->line("🏠 [{$num}/" . count($listings) . "] {$baslik}");

                // Check if ilan exists (idempotent)
                $existingIlan = Ilan::where('external_ref', $externalRef)->first();
                $action = $existingIlan ? 'updated' : 'created';

                // Tapu matching
                $tapuMatch = null;
                $tapuId = null;
                if ($externalRef) {
                    $tapuMatch = $this->tapuMatchService->matchByExternalRef($externalRef);
                    $tapuId = $tapuMatch['tapu_id'] ?? null;

                    if ($tapuMatch['match_state'] === 'matched') {
                        $this->info("   ✅ Tapu matched: tapu_id={$tapuId} (confidence={$tapuMatch['confidence']})");
                        $tapuMatched++;
                    } elseif ($tapuMatch['match_state'] === 'reconciliation_required') {
                        $this->warn("   ⚠️  Multiple tapu candidates found (" . count($tapuMatch['candidates']) . "), reconciliation required");
                        $reconciledTapu++;
                    } else {
                        $this->comment("   ⏳ Tapu match pending (no records found)");
                        $pendingTapu++;
                    }
                }

                if ($dryRun) {
                    $this->line("   [DRY RUN] {$action} | external_ref={$externalRef} | tapu_state={$tapuMatch['match_state']} | tapu_id=" . ($tapuId ?? 'null'));
                    if ($action === 'created') {
                        $created++;
                    } else {
                        $updated++;
                    }
                    continue;
                }

                // ✅ CONTEXT7: Prepare ilan data with canonical schema fields
                $ilanData = [
                    // Core fields
                    'yayin_durumu' => IlanDurumu::YAYINDA->value,  // ✅ SAB: canonical publication state
                    'baslik' => $listing['baslik'],
                    'aciklama' => $listing['aciklama'],
                    'fiyat' => $listing['fiyat'],

                    // ✅ CONTEXT7: Canonical boolean fields
                    'aktiflik_durumu' => $listing['aktiflik_durumu'] ?? true,
                    'one_cikan' => $listing['one_cikan'] ?? false,
                    'display_order' => $listing['display_order'] ?? 0,

                    // Portfolio category fields
                    'kategori' => $listing['kategori'],           // konut/arsa_arazi/villa_isyeri
                    'alt_kategori' => $listing['alt_kategori'],   // daire/villa/arsa
                    'yayin_tipi' => $listing['yayin_tipi'],       // satilik/kiralik

                    // Location strings (for portfolio compatibility)
                    'il' => $listing['il'],
                    'ilce' => $listing['ilce'],
                    'mahalle' => $listing['mahalle'] ?? null,

                    // Property details
                    'brut_m2' => $listing['brut_m2'] ?? null,
                    'net_m2' => $listing['net_m2'] ?? null,
                    'oda_sayisi' => $listing['oda_sayisi'] ?? null,
                    'bina_yasi' => $listing['bina_yasi'] ?? null,
                    'kat' => $listing['kat'] ?? null,
                    'toplam_kat' => $listing['toplam_kat'] ?? null,
                    'isitma' => $listing['isitma'] ?? null,

                    // ✅ CONTEXT7: Coordinates (lat/lng, NOT latitude/longitude)
                    'lat' => $listing['lat'] ?? null,
                    'lng' => $listing['lng'] ?? null,

                    // Integration fields
                    'external_ref' => $externalRef,
                    'tapu_id' => $tapuId,

                    // ✅ CONTEXT7: Metadata JSON
                    'metadata' => array_merge(
                        $listing['metadata'] ?? [],
                        [
                            'tapu_match' => $tapuMatch,
                            'import_source' => basename($filePath),
                            'import_date' => $metadata['import_date'] ?? null,
                            'approved_by' => $metadata['approved_by'] ?? null,
                            'imported_at' => now()->toISOString()
                        ]
                    ),

                    'updated_at' => now()
                ];

                // ✅ IDEMPOTENT: Create or Update
                if ($existingIlan) {
                    // Update existing record via CrudService
                    $this->ilanCrudService->update($existingIlan, $ilanData);
                    $ilanId = $existingIlan->id;
                    $updated++;
                } else {
                    // Create new record via CrudService
                    $ilanData['slug'] = \Illuminate\Support\Str::slug($listing['baslik'] . '-' . uniqid());
                    $ilanData['created_at'] = now();

                    $ilan = $this->ilanCrudService->store($ilanData);
                    $ilanId = $ilan->id;
                    $created++;
                }

                // ✅ IDEMPOTENT: Sync etiketler (remove old, add new)
                if (!empty($listing['etiketler']) && is_array($listing['etiketler'])) {
                    // Remove existing pivot relations
                    DB::table('ilan_etiketler')->where('ilan_id', $ilanId)->delete();

                    foreach ($listing['etiketler'] as $etiket) {
                        // Convert etiket string to slug for lookup
                        $slug = \Illuminate\Support\Str::slug($etiket);

                        // Upsert etiket (slug unique, update name)
                        $etiketRecord = DB::table('etiketler')
                            ->where('slug', $slug)
                            ->first();

                        if (!$etiketRecord) {
                            // Create new etiket
                            $etiketId = DB::table('etiketler')->insertGetId([
                                'slug' => $slug,
                                'name' => $etiket,
                                'aktiflik_durumu' => true,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        } else {
                            // Update existing etiket name
                            DB::table('etiketler')
                                ->where('id', $etiketRecord->id)
                                ->update([
                                    'name' => $etiket,
                                    'updated_at' => now()
                                ]);
                            $etiketId = $etiketRecord->id;
                        }

                        // Link ilan to etiket
                        DB::table('ilan_etiketler')->insert([
                            'ilan_id' => $ilanId,
                            'etiket_id' => $etiketId,
                            'display_order' => 0,
                            'one_cikan' => false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                $this->info("   ✅ {$action} | ilan_id={$ilanId}");

                Log::info("PortfolioImport: {$action} ilan", [
                    'action' => $action,
                    'ilan_id' => $ilanId,
                    'external_ref' => $externalRef,
                    'tapu_id' => $tapuId,
                    'match_state' => $tapuMatch['match_state'] ?? 'n/a'
                ]);
            }

            if (!$dryRun) {
                DB::commit();
                $this->info("\n✅ Transaction committed");
            } else {
                DB::rollBack();
                $this->info("\n🔄 Dry run completed (no changes)");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Import failed: " . $e->getMessage());
            $this->error("   File: " . $e->getFile() . " Line: " . $e->getLine());
            Log::error("PortfolioImport failed", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        $this->line("\n📊 Portfolio Import Summary");
        $this->line(str_repeat('=', 50));
        $this->line("Total records: " . count($listings));
        $this->line("Created: {$created}");
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$failed}");
        $this->line("Tapu matched: {$tapuMatched}");
        $this->line("Tapu pending: {$pendingTapu}");
        $this->line("Tapu reconcile: {$reconciledTapu}");
        $this->line("Dry-run: " . ($dryRun ? 'YES' : 'NO'));

        return 0;
    }
}

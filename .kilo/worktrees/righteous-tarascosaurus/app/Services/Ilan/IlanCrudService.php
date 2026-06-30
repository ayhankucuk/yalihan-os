<?php

namespace App\Services\Ilan;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\IlanPriceHistory;
use App\Models\IlanKategori;
use App\Models\User;
use App\Services\IlanReferansService;
use App\Services\Listing\ListingStateMachine;
use App\Services\Utility\NumberToTextConverter;
use App\Services\Logging\LogService;
use App\Events\IlanCreated;
use App\Events\IlanUpdated;
use App\Events\IlanDeleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Ilan Crud Service
 *
 * Context7 Standardı: C7-ILAN-CRUD-SERVICE-2025-12-28
 *
 * Centralized service for creation and update of listings.
 * Ensures data integrity, reference locking, and price history mühürleme.
 *
 * Write Isolation: Agent requests are blocked at domain level via GuardsAgentWrites.
 */
class IlanCrudService
{
    use \App\Traits\GuardsAgentWrites;
    public function __construct(
        private IlanReferansService  $refService,
        private NumberToTextConverter $numberToText,
        private \App\Services\Listing\YalihanLifecycle $lifecycle,
    ) {}

    /**
     * Create a new listing (with full atomicity)
     *
     * @param array $data
     * @return Ilan
     * @throws \Exception
     */
    public function store(array $data): Ilan
    {
        $this->blockAgentWrite('store');

        $ilan = DB::transaction(function () use ($data) {
            // 1. Initialize Ilan with basic data
            $ilan = new Ilan();

            // 2. Map core data
            $this->mapCoreData($ilan, $data);

            // 3. Handle Pricing (Price and History)
            $this->handlePricing($ilan, $data, true);

            // 4. Handle Location & Categories
            $this->handleCategories($ilan, $data);
            $this->handleLocation($ilan, $data);

            // 5. Save initial record to get ID
            $ilan->save();

            // 🆕 AUTO-DETAILER: Ensure detail records exist (Prevents Cortex 0%)
            $ilan->ensureDetailTableExists();

            // 6. Record Price History (Needs ID)
            $this->recordPriceHistory($ilan, $data, true);

            // 7. Seal Reference & File Name
            $this->handleReference($ilan);

            // 8. Sync Features & Media & Vertical Details & Feeds
            $this->handleVerticalDetails($ilan, $data);
            $this->syncFeatures($ilan, $data);

            // 🆕 Authority Transition: TASLAK (Create)
            $targetEnum = IlanDurumu::normalize($data['yayin_durumu'] ?? $data['status'] ?? 'taslak');
            $this->lifecycle->transition($ilan, $targetEnum, null, ['source' => 'crud_store']);

            return $ilan;
        });

        // Event dispatch AFTER successful commit — prevents listener execution on rollback
        event(new IlanCreated($ilan));

        return $ilan;
    }

    /**
     * Update an existing listing
     *
     * @param Ilan $ilan
     * @param array $data
     * @return Ilan
     * @throws \Exception
     */
    public function update(Ilan $ilan, array $data): Ilan
    {
        $this->blockAgentWrite('update');

        $ilan = DB::transaction(function () use ($ilan, $data) {
            // 1. Map core data
            $this->mapCoreData($ilan, $data);

            // 2. Handle Pricing (History check)
            $this->handlePricing($ilan, $data, false);

            // 3. Handle Location & Categories
            $this->handleCategories($ilan, $data);
            $this->handleLocation($ilan, $data);

            // 4. Save updates
            $ilan->save();

            // 🆕 AUTO-DETAILER: Ensure detail records exist (Prevents Cortex 0%)
            $ilan->ensureDetailTableExists();

            // 5. Record Price History
            $this->recordPriceHistory($ilan, $data, false);

            // 6. Re-seal SEO names if needed
            $this->handleReference($ilan);

            // 7. Sync Features & Vertical Details & Calendar Feeds
            $this->handleVerticalDetails($ilan, $data);
            $this->syncFeatures($ilan, $data);

            // 🆕 Authority Transition: Update State if requested
            if (isset($data['yayin_durumu']) || isset($data['status'])) {
                $targetEnum = IlanDurumu::normalize($data['yayin_durumu'] ?? $data['status']);
                if ($targetEnum) {
                    $this->lifecycle->transition($ilan, $targetEnum, null, ['source' => 'crud_update']);
                }
            }

            return $ilan->fresh();
        });

        // Event dispatch AFTER successful commit
        event(new IlanUpdated($ilan));

        return $ilan;
    }

    /**
     * Delete a listing (soft delete)
     *
     * @param Ilan $ilan
     * @return bool
     */
    public function destroy(Ilan $ilan): bool
    {
        $this->blockAgentWrite('destroy');

        $ilanClone = clone $ilan;

        $res = DB::transaction(function () use ($ilan) {
            $ilanId = $ilan->id;
            $result = $ilan->delete();

            LogService::action('ilan_deleted', 'ilan', $ilanId, [
                'ref' => $ilan->referans_no
            ]);

            return $result;
        });

        // Event dispatch AFTER successful commit (use clone — original may be trashed)
        event(new IlanDeleted($ilanClone->id));

        return $res;
    }

    /**
     * PRIVATE: Map core listing fields
     */
    private function mapCoreData(Ilan $ilan, array $data): void
    {
        $ilan->baslik = $data['baslik'];
        $ilan->aciklama = $data['aciklama'] ?? null;
        // SAB §5: State Machine enforcement
        // İlan durumu doğrudan set edilmez, akışın sonunda YalihanLifecycle kullanılır.
        // Ham veri burada sadece yetki kontrolü veya başlangıç değeri için saklanabilir.
        $ilan->danisman_id = $data['danisman_id'] ?? Auth::id();
        $ilan->ilan_sahibi_id = $data['ilan_sahibi_id'] ?? null;
        $ilan->danisman_id = $data['danisman_id'] ?? Auth::id();
        $ilan->crm_only = $data['crm_only'] ?? false;

        // ======================================================================
        // RENTAL ENGINE FIELDS — guarded by schema check to prevent column-not-found
        // ======================================================================
        $rentalTable = $ilan->getTable();
        if (Schema::hasColumn($rentalTable, 'min_stay_nights')) {
            if (isset($data['rental_enabled'])) {
                $ilan->rental_enabled = (bool) $data['rental_enabled'];
            }
            $ilan->min_stay_nights = $data['min_stay_nights'] ?? $data['min_konaklama'] ?? $ilan->min_stay_nights ?? 1;
            $ilan->max_stay_nights = $data['max_stay_nights'] ?? $data['maximum_stay'] ?? $ilan->max_stay_nights ?? 30;
            $ilan->checkin_time = $data['checkin_time'] ?? $data['check_in_time'] ?? $ilan->checkin_time ?? '14:00';
            $ilan->checkout_time = $data['checkout_time'] ?? $data['check_out_time'] ?? $ilan->checkout_time ?? '11:00';
            $ilan->max_guests = $data['max_guests'] ?? $data['max_misafir'] ?? $ilan->max_guests;
            $ilan->base_guest_count = $data['base_guest_count'] ?? $ilan->base_guest_count ?? 1;
            $ilan->extra_guest_fee = $data['extra_guest_fee'] ?? $ilan->extra_guest_fee ?? 0;
            $ilan->cleaning_fee = $data['cleaning_fee'] ?? $data['temizlik_ucreti'] ?? $ilan->cleaning_fee ?? 0;
            $ilan->security_deposit = $data['security_deposit'] ?? $data['deposit_amount'] ?? $ilan->security_deposit ?? 0;
            $ilan->booking_type = $data['booking_type'] ?? $ilan->booking_type ?? 'instant';
            $ilan->cancellation_policy = $data['cancellation_policy'] ?? $ilan->cancellation_policy ?? 'flexible';
            // Backward compat aliases (varchar(5) ← time format: truncate to HH:MM)
            $ilan->minimum_stay = $ilan->min_stay_nights;
            $ilan->check_in_time = substr((string) $ilan->checkin_time, 0, 5);
            $ilan->check_out_time = substr((string) $ilan->checkout_time, 0, 5);
        }



        // Property specifics (Still in main table as legacy or common fields)
        $propertyFields = [
            'oda_sayisi', 'banyo_sayisi', 'salon_sayisi', 'brut_m2', 'net_m2', 'kat',
            'toplam_kat', 'bina_yasi', 'isinma_tipi', 'ada_no', 'parsel_no'
        ];

        foreach ($propertyFields as $field) {
            // Check both provided field name and potentially mapped name
            $value = $data[$field] ?? null;

            // Legacy mapping for m2
            if ($field === 'brut_m2' && isset($data['brut_alan'])) $value = $data['brut_alan'];
            if ($field === 'brut_m2' && $value === null && isset($data['brut-metrekare'])) $value = $data['brut-metrekare'];
            if ($field === 'net_m2' && isset($data['net_alan'])) $value = $data['net_alan'];
            if ($field === 'net_m2' && $value === null && isset($data['net-metrekare'])) $value = $data['net-metrekare'];

            if ($value !== null) {
                $ilan->{$field} = $value;
            }
        }
    }

    /**
     * PRIVATE: Handle pricing and text conversion
     */
    private function handlePricing(Ilan $ilan, array $data, bool $isNew): void
    {
        $newPrice = (float)($data['fiyat_raw'] ?? (isset($data['fiyat']) ? str_replace('.', '', $data['fiyat']) : 0));
        $currency = $data['para_birimi'] ?? 'TRY';

        $ilan->fiyat = $newPrice;
        $ilan->para_birimi = $currency;
        $ilan->price_text = $this->numberToText->convertToText($newPrice, $currency);
    }

    /**
     * PRIVATE: Record price history if changed
     */
    private function recordPriceHistory(Ilan $ilan, array $data, bool $isNew): void
    {
        $oldPrice = $isNew ? 0 : $ilan->getOriginal('fiyat');
        $newPrice = $ilan->fiyat;
        $currency = $ilan->para_birimi;

        if ($isNew || $oldPrice != $newPrice) {
            IlanPriceHistory::create([
                'ilan_id' => $ilan->id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'currency' => $currency,
                'changed_by' => Auth::id(),
                'change_reason' => $isNew ? 'İlk ilan oluşturma' : ($data['price_change_reason'] ?? 'Güncelleme'),
            ]);
        }
    }

    /**
     * PRIVATE: Handle category mapping (3-level standard)
     */
    private function handleCategories(Ilan $ilan, array $data): void
    {
        $ilan->ana_kategori_id = $data['ana_kategori_id'] ?? null;
        $ilan->alt_kategori_id = $data['alt_kategori_id'] ?? null;
        $ilan->yayin_tipi_id = $data['yayin_tipi_id'] ?? null;
    }

    /**
     * PRIVATE: Handle location and SEO slug
     */
    private function handleLocation(Ilan $ilan, array $data): void
    {
        // Legacy text location parity (bulk import path may still provide string labels).
        $ilan->il = $data['il'] ?? $ilan->il ?? null;
        $ilan->ilce = $data['ilce'] ?? $ilan->ilce ?? null;
        $ilan->mahalle = $data['mahalle'] ?? $ilan->mahalle ?? null;

        $ilan->il_id = $data['il_id'] ?? null;
        $ilan->ilce_id = $data['ilce_id'] ?? null;
        $ilan->mahalle_id = $data['mahalle_id'] ?? null;
        $ilan->lat = $data['lat'] ?? $data['enlem'] ?? null;
        $ilan->lng = $data['lng'] ?? $data['boylam'] ?? null;
        $ilan->adres = $data['adres'] ?? $data['adres_detay'] ?? null;

        // Geometry support (point vs polygon)
        if (!empty($data['boundary_geojson'])) {
            $geojson = is_string($data['boundary_geojson'])
                ? json_decode($data['boundary_geojson'], true)
                : $data['boundary_geojson'];

            if ($geojson) {
                $ilan->geometry_type = 'polygon';
                $ilan->geometry = $geojson;

                // Extract centroid for MIE if lat/lng not set
                if (empty($ilan->lat) && !empty($geojson['coordinates'])) {
                    $centroid = $this->calculateCentroid($geojson);
                    if ($centroid) {
                        $ilan->lat = $centroid['lat'];
                        $ilan->lng = $centroid['lng'];
                    }
                }
            }
        } elseif ($ilan->lat && $ilan->lng) {
            $ilan->geometry_type = 'point';
        }

        if (empty($ilan->slug) || $ilan->isDirty('baslik')) {
            $ilan->slug = Str::slug($ilan->baslik);
        }
    }

    /**
     * Calculate centroid from GeoJSON polygon coordinates
     */
    private function calculateCentroid(array $geojson): ?array
    {
        $coords = $geojson['coordinates'][0] ?? null;
        if (!$coords || count($coords) < 3) {
            return null;
        }

        $latSum = 0;
        $lngSum = 0;
        $count = count($coords);

        foreach ($coords as $point) {
            $lngSum += $point[0]; // GeoJSON: [lng, lat]
            $latSum += $point[1];
        }

        return [
            'lat' => round($latSum / $count, 8),
            'lng' => round($lngSum / $count, 8),
        ];
    }

    /**
     * PRIVATE: Seal reference and file name
     */
    private function handleReference(Ilan $ilan): void
    {
        // Reference No generating needs the ID and categories
        if (!$ilan->referans_no) {
            $ilan->referans_no = $this->refService->generateReferansNo($ilan);
        }

        // SEO friendly file name (Ref No + Title)
        $ilan->dosya_adi = $this->refService->generateDosyaAdi($ilan);

        $ilan->saveQuietly();
    }

    /**
     * PRIVATE: Handle vertical domain details (Arsa, Turizm, etc.)
     *
     * Context7: 2025-12-23 Refactoring support
     */
    private function handleVerticalDetails(Ilan $ilan, array $data): void
    {
        $kategoriSlug = strtolower($ilan->anaKategori->slug ?? '');

        // 1. Turizm (Yazlık) Details
        if ($kategoriSlug === 'yazlık' || $kategoriSlug === 'yazlik') {
            \App\Models\Dikey\IlanTurizmDetail::updateOrCreate(
                ['ilan_id' => $ilan->id],
                [
                    'check_in_saati' => $data['check_in_saati'] ?? $data['check_in_time'] ?? null,
                    'check_out_saati' => $data['check_out_saati'] ?? $data['check_out_time'] ?? null,
                    'min_konaklama' => $data['min_konaklama'] ?? $data['minimum_stay'] ?? null,
                    'max_misafir' => $data['max_misafir'] ?? $data['max_guests'] ?? null,
                    'gunluk_fiyat' => $data['gunluk_fiyat'] ?? $ilan->fiyat ?? null,
                    'temizlik_ucreti' => $data['temizlik_ucreti'] ?? $data['cleaning_fee'] ?? null,
                    'havuz_var' => $data['havuz_var'] ?? $data['havuz'] ?? false,
                    'sezon_baslangic' => $data['sezon_baslangic'] ?? null,
                    'sezon_bitis' => $data['sezon_bitis'] ?? null,
                ]
            );
        }

        // 2. Arsa Details (slug: arsa or arsa-arazi)
        if (str_starts_with($kategoriSlug, 'arsa')) {
            \App\Models\Dikey\IlanArsaDetail::updateOrCreate(
                ['ilan_id' => $ilan->id],
                [
                    'ada_no' => $data['ada_no'] ?? null,
                    'parsel_no' => $data['parsel_no'] ?? null,
                    'imar_durumu' => $data['imar_durumu'] ?? $data['imar-durumu'] ?? null,
                    'kaks' => $data['kaks'] ?? null,
                    'taks' => $data['taks'] ?? null,
                ]
            );
        }
    }

    /**
     * Update portal IDs for a listing.
     *
     * @param  Ilan  $ilan
     * @param  array<string, string|null>  $portalIds
     * @return array<string>  List of updated column names
     */
    public function updatePortalIds(Ilan $ilan, array $portalIds): array
    {
        $updates = [];
        foreach ($portalIds as $column => $value) {
            if (is_string($column) && Schema::hasColumn('ilanlar', $column)) {
                $updates[$column] = $value;
            }
        }

        if (!empty($updates)) {
            $ilan->update($updates);
        }

        return array_keys($updates);
    }

    /**
     * PRIVATE: Sync property features
     *
     * Maps schema-driven feature slugs to:
     * 1. Direct ilan columns (slug → column canonical mapping)
     * 2. Feature pivot table (slug → feature_id resolution)
     * 3. Logs any unmapped slugs for observability
     *
     * FIX (SAB Sprint 2026-04-15): Resolves silent data loss where features[]
     * was dropped by validated() and slug→storage mapping was missing.
     */
    private function syncFeatures(Ilan $ilan, array $data): void
    {
        if (!isset($data['features']) || !is_array($data['features'])) {
            return;
        }

        $features = $data['features'];

        // =====================================================================
        // 1. SLUG → DIRECT COLUMN MAPPING (canonical)
        // Schema-driven slugs that map to ilanlar table columns
        // =====================================================================
        $slugToColumn = [
            'brut-metrekare'  => 'brut_m2',
            'net-metrekare'   => 'net_m2',
            'oda-sayisi'      => 'oda_sayisi',
            'banyo-sayisi'    => 'banyo_sayisi',
            'bina-yasi'       => 'bina_yasi',
            'kat'             => 'kat',
            'isitma'          => 'isinma_tipi',
            'esyali'          => 'esyali',
            'aidat'           => 'aidat',
        ];

        $directUpdates = [];
        $pivotData = [];
        $unmappedSlugs = [];

        foreach ($features as $slug => $value) {
            if (!is_string($slug) || $value === null || $value === '') {
                continue;
            }

            // Direct column mapping
            if (isset($slugToColumn[$slug])) {
                $directUpdates[$slugToColumn[$slug]] = $value;
                continue;
            }

            // Attempt pivot sync: resolve slug → feature_id
            $featureId = $this->resolveFeatureId($slug);
            if ($featureId) {
                $pivotData[$featureId] = ['value' => is_array($value) ? json_encode($value) : (string) $value];
                continue;
            }

            // Unmapped → log for observability
            $unmappedSlugs[$slug] = $value;
        }

        // Apply direct column updates
        if (!empty($directUpdates)) {
            $ilan->forceFill($directUpdates);
            $ilan->saveQuietly();
        }

        // Sync pivot table (only if there are resolved feature IDs)
        if (!empty($pivotData)) {
            $ilan->features()->syncWithoutDetaching($pivotData);
        }

        // =====================================================================
        // 2. VERTICAL DETAIL MAPPING
        // Slugs that belong to vertical detail tables (arsa, turizm, etc.)
        // =====================================================================
        $this->syncFeaturesToVerticalDetails($ilan, $features);

        // =====================================================================
        // 3. OBSERVABILITY: Log unmapped slugs to detect future silent drops
        // =====================================================================
        if (!empty($unmappedSlugs)) {
            Log::channel('sab')->warning('syncFeatures: unmapped feature slugs detected', [
                'ilan_id' => $ilan->id,
                'unmapped' => $unmappedSlugs,
            ]);
        }
    }

    /**
     * Resolve a feature slug to its feature_id in the features table.
     * Returns null if the features table has no matching entry.
     */
    private function resolveFeatureId(string $slug): ?int
    {
        static $cache = null;
        if ($cache === null) {
            $cache = DB::table('features')
                ->whereNull('deleted_at')
                ->pluck('id', 'slug')
                ->toArray();
        }

        return $cache[$slug] ?? null;
    }

    /**
     * Map feature slugs to vertical detail tables (arsa, turizm, etc.)
     */
    private function syncFeaturesToVerticalDetails(Ilan $ilan, array $features): void
    {
        $kategoriSlug = strtolower($ilan->anaKategori->slug ?? '');

        // Arsa detail slug → column mapping
        if (str_starts_with($kategoriSlug, 'arsa')) {
            $arsaMapping = [
                'imar-durumu'  => 'imar_durumu',
                'tapu-durumu'  => null, // No column in ilan_arsa_details; stored as pivot if feature exists
            ];

            $arsaUpdates = [];
            foreach ($arsaMapping as $slug => $column) {
                if ($column && isset($features[$slug]) && $features[$slug] !== '') {
                    $arsaUpdates[$column] = $features[$slug];
                }
            }

            if (!empty($arsaUpdates)) {
                \App\Models\Dikey\IlanArsaDetail::updateOrCreate(
                    ['ilan_id' => $ilan->id],
                    $arsaUpdates
                );
            }
        }
    }
}

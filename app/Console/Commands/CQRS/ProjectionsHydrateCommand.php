<?php

namespace App\Console\Commands\CQRS;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class ProjectionsHydrateCommand
 *
 * SAB Enforced Idempotent Projection Hydration Engine.
 * Reconstructs and synchronizes `ilanlar_read_model` and `listing_search_projection`
 * from the master `ilanlar` dataset.
 *
 * @package App\Console\Commands\CQRS
 */
class ProjectionsHydrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projections:hydrate
                            {--tenant= : Sadece belirli bir tenant ID için çalıştır}
                            {--truncate : Önceden okuma modeli tablolarını temizle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ana ilanlar tablosundaki tüm aktif verileri CQRS okuma modellerine (ilanlar_read_model, listing_search_projection) aktarır.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== [SAB CQRS] Projection Hydration Engine Active ===');

        $tenantFilter = $this->option('tenant');
        $shouldTruncate = (bool) $this->option('truncate');

        try {
            if ($shouldTruncate) {
                $this->comment('--> Truncating projection tables...');
                DB::table('ilanlar_read_model')->truncate();
                DB::table('listing_search_projection')->truncate();
            }

            $query = DB::table('ilanlar as i')
                ->whereNull('i.deleted_at')
                ->orderBy('i.id', 'asc');

            if ($tenantFilter !== null) {
                $query->where('i.tenant_id', (int) $tenantFilter);
            }

            $total = $query->count();
            $this->comment("--> Found {$total} listings to hydrate.");

            $hydratedReadModel = 0;
            $hydratedSearchProjection = 0;

            $query->chunk(100, function ($listings) use (&$hydratedReadModel, &$hydratedSearchProjection) {
                foreach ($listings as $listing) {
                    $tenantId = (int) ($listing->tenant_id ?? 1);
                    $listingId = (int) $listing->id;

                    // Kapak resmi çözümleme
                    $featuredPhoto = DB::table('ilan_fotograflari')
                        ->where('ilan_id', $listingId)
                        ->where('kapak_fotografi', 1)
                        ->orderBy('id', 'asc')
                        ->first();

                    if (!$featuredPhoto) {
                        $featuredPhoto = DB::table('ilan_fotograflari')
                            ->where('ilan_id', $listingId)
                            ->orderBy('display_order', 'asc')
                            ->orderBy('id', 'asc')
                            ->first();
                    }

                    $kapakResmi = $featuredPhoto->dosya_yolu ?? $listing->kapak_resmi ?? null;

                    // Özellikler (Feature list)
                    $features = DB::table('ilan_feature as ife')
                        ->join('features as f', 'f.id', '=', 'ife.feature_id')
                        ->where('ife.ilan_id', $listingId)
                        ->orderBy('f.id', 'asc')
                        ->pluck('f.slug')
                        ->toArray();

                    // Kategori belirleme
                    $mainCatId = $listing->parent_kategori_id ?? $listing->kategori_id ?? null;
                    $subCatId = $listing->kategori_id ?? null;
                    if ($mainCatId === $subCatId && $subCatId !== null) {
                        $catRow = DB::table('ilan_kategorileri')->where('id', $subCatId)->orderBy('id', 'asc')->first();
                        if ($catRow && $catRow->parent_id) {
                            $mainCatId = (int) $catRow->parent_id;
                        }
                    }

                    // 1. ilanlar_read_model Upsert
                    DB::table('ilanlar_read_model')->updateOrInsert(
                        [
                            'tenant_id' => $tenantId,
                            'ilan_id' => $listingId,
                        ],
                        [
                            'ulke_id' => $listing->ulke_id ?? null,
                            'baslik' => $listing->baslik ?? '',
                            'aciklama' => $listing->aciklama ?? null,
                            'yayin_durumu' => $listing->yayin_durumu ?? 'yayinda',
                            'aktiflik_durumu' => (int) ($listing->aktiflik_durumu ?? 1),
                            'one_cikan' => (int) ($listing->one_cikan ?? 0),
                            'kapak_resmi' => $kapakResmi,
                            'ana_kategori_id' => $mainCatId,
                            'alt_kategori_id' => $subCatId,
                            'il' => $listing->il ?? null,
                            'ilce' => $listing->ilce ?? null,
                            'mahalle' => $listing->mahalle ?? null,
                            'lat' => $listing->lat ?? null,
                            'lng' => $listing->lng ?? null,
                            'fiyat' => $listing->fiyat ?? null,
                            'doviz_birimi' => $listing->para_birimi ?? 'TRY',
                            'oda_sayisi' => $listing->oda_sayisi ?? null,
                            'banyo_sayisi' => $listing->banyo_sayisi ?? null,
                            'brut_alan_m2' => $listing->brut_m2 ?? $listing->alan_m2 ?? null,
                            'net_alan_m2' => $listing->alan_m2 ?? null,
                            'bina_yasi' => $listing->bina_yasi ?? null,
                            'bulundugu_kat' => $listing->bulundugu_kat ?? null,
                            'sahip_id' => $listing->ilan_sahibi_id ?? $listing->kisi_id ?? null,
                            'sorumlu_danisman_id' => $listing->sorumlu_danisman_id ?? null,
                            'display_order' => (int) ($listing->display_order ?? 0),
                            'slug' => $listing->slug ?? null,
                            'goruntulenme_sayisi' => (int) ($listing->goruntulenme ?? 0),
                            'favori_sayisi' => (int) ($listing->favori_sayisi ?? 0),
                            'iletisim_sayisi' => 0,
                            'ilan_olusturulma_tarihi' => $listing->created_at ?? now(),
                            'son_guncelleme_tarihi' => $listing->updated_at ?? now(),
                            'son_islenen_sira_numarasi' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $hydratedReadModel++;

                    // 2. listing_search_projection Upsert
                    $hasTenantCol = \Illuminate\Support\Facades\Schema::hasColumn('listing_search_projection', 'tenant_id');
                    $searchMatch = ['listing_id' => $listingId];
                    if ($hasTenantCol) {
                        $searchMatch['tenant_id'] = $tenantId;
                    }

                    $searchData = [
                        'title' => $listing->baslik ?? '',
                        'city' => $listing->il ?? '',
                        'district' => $listing->ilce ?? '',
                        'price' => $listing->fiyat ?? 0,
                        'room_count' => (int) ($listing->oda_sayisi ?? 0),
                        'property_type' => (string) ($mainCatId ?? 'konut'),
                        'features' => json_encode($features),
                        'portfolio_health' => (int) ($listing->completion_score ?? 100),
                        'seo_score' => (int) ($listing->seo_score ?? 85),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    DB::table('listing_search_projection')->updateOrInsert(
                        $searchMatch,
                        $searchData
                    );
                    $hydratedSearchProjection++;
                }
            });

            $this->info("✅ [SUCCESS] Hydrated {$hydratedReadModel} records into ilanlar_read_model.");
            $this->info("✅ [SUCCESS] Hydrated {$hydratedSearchProjection} records into listing_search_projection.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::critical('SAB HYDRATION ERROR: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('🚨 CRITICAL: Projection hydration failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}

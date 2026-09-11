<?php

namespace App\Observers;

use App\Jobs\AITranslation\TranslateListingJob;
use App\Models\Ilan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class IlanObserver
{
    /**
     * Handle the Ilan "saved" event.
     */
    public function saved(Ilan $ilan): void
    {
        // Yalnızca başlık veya açıklama değiştiyse veya yeni kayıt ise çeviri tetikle
        if ($ilan->wasRecentlyCreated || $ilan->wasChanged(['baslik', 'aciklama'])) {
            TranslateListingJob::dispatch($ilan);
        }

        // CQRS Projeksiyonlarını Eşzamanla
        $this->syncReadModels($ilan);
    }

    /**
     * Handle the Ilan "deleted" event.
     */
    public function deleted(Ilan $ilan): void
    {
        /** @sab-ignore-catch */
        try {
            DB::table('ilanlar_read_model')
                ->where('ilan_id', $ilan->id)
                ->delete();

            DB::table('listing_search_projection')
                ->where('listing_id', $ilan->id)
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('CQRS Read Model Delete Error: ' . $e->getMessage(), ['ilan_id' => $ilan->id]);
        }
    }

    /**
     * Sync Ilan read models (ilanlar_read_model and listing_search_projection)
     */
    private function syncReadModels(Ilan $ilan): void
    {
        /** @sab-ignore-catch */
        try {
            $tenantId = (int) ($ilan->tenant_id ?? 1);
            $listingId = (int) $ilan->id;

            // Kapak resmi
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

            $kapakResmi = $featuredPhoto->dosya_yolu ?? $ilan->kapak_resmi ?? null;

            // Features list
            $features = DB::table('ilan_feature as ife')
                ->join('features as f', 'f.id', '=', 'ife.feature_id')
                ->where('ife.ilan_id', $listingId)
                ->orderBy('f.id', 'asc')
                ->pluck('f.slug')
                ->toArray();

            // Kategori
            $mainCatId = $ilan->parent_kategori_id ?? $ilan->kategori_id ?? null;
            $subCatId = $ilan->kategori_id ?? null;
            if ($mainCatId === $subCatId && $subCatId !== null) {
                $catRow = DB::table('ilan_kategorileri')->where('id', $subCatId)->orderBy('id', 'asc')->first();
                if ($catRow && $catRow->parent_id) {
                    $mainCatId = (int) $catRow->parent_id;
                }
            }

            // 1. ilanlar_read_model
            DB::table('ilanlar_read_model')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'ilan_id' => $listingId,
                ],
                [
                    'ulke_id' => $ilan->ulke_id ?? null,
                    'baslik' => $ilan->baslik ?? '',
                    'aciklama' => $ilan->aciklama ?? null,
                    'yayin_durumu' => $ilan->yayin_durumu ?? 'yayinda',
                    'aktiflik_durumu' => (int) ($ilan->aktiflik_durumu ?? 1),
                    'one_cikan' => (int) ($ilan->one_cikan ?? 0),
                    'kapak_resmi' => $kapakResmi,
                    'ana_kategori_id' => $mainCatId,
                    'alt_kategori_id' => $subCatId,
                    'il' => $ilan->il ?? null,
                    'ilce' => $ilan->ilce ?? null,
                    'mahalle' => $ilan->mahalle ?? null,
                    'lat' => $ilan->lat ?? null,
                    'lng' => $ilan->lng ?? null,
                    'fiyat' => $ilan->fiyat ?? null,
                    'doviz_birimi' => $ilan->para_birimi ?? 'TRY',
                    'oda_sayisi' => $ilan->oda_sayisi ?? null,
                    'banyo_sayisi' => $ilan->banyo_sayisi ?? null,
                    'brut_alan_m2' => $ilan->brut_m2 ?? $ilan->alan_m2 ?? null,
                    'net_alan_m2' => $ilan->alan_m2 ?? null,
                    'bina_yasi' => $ilan->bina_yasi ?? null,
                    'bulundugu_kat' => $ilan->bulundugu_kat ?? null,
                    'sahip_id' => $ilan->ilan_sahibi_id ?? $ilan->kisi_id ?? null,
                    'sorumlu_danisman_id' => $ilan->sorumlu_danisman_id ?? null,
                    'display_order' => (int) ($ilan->display_order ?? 0),
                    'slug' => $ilan->slug ?? null,
                    'goruntulenme_sayisi' => (int) ($ilan->goruntulenme ?? 0),
                    'favori_sayisi' => (int) ($ilan->favori_sayisi ?? 0),
                    'iletisim_sayisi' => 0,
                    'ilan_olusturulma_tarihi' => $ilan->created_at ?? now(),
                    'son_guncelleme_tarihi' => $ilan->updated_at ?? now(),
                    'son_islenen_sira_numarasi' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // 2. listing_search_projection
            $hasTenantCol = Schema::hasColumn('listing_search_projection', 'tenant_id');
            $searchMatch = ['listing_id' => $listingId];
            if ($hasTenantCol) {
                $searchMatch['tenant_id'] = $tenantId;
            }

            DB::table('listing_search_projection')->updateOrInsert(
                $searchMatch,
                [
                    'title' => $ilan->baslik ?? '',
                    'city' => $ilan->il ?? '',
                    'district' => $ilan->ilce ?? '',
                    'price' => $ilan->fiyat ?? 0,
                    'room_count' => (int) ($ilan->oda_sayisi ?? 0),
                    'property_type' => (string) ($mainCatId ?? 'konut'),
                    'features' => json_encode($features),
                    'portfolio_health' => (int) ($ilan->completion_score ?? 100),
                    'seo_score' => (int) ($ilan->seo_score ?? 85),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('CQRS Read Model Sync Error: ' . $e->getMessage(), ['ilan_id' => $ilan->id]);
        }
    }
}

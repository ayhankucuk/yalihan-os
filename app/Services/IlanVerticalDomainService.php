<?php

namespace App\Services;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Context7 Standard: Vertical Domain Separation Service
 *
 * Bu service, dikey alan ayrıştırma mimarisinde ilanları yönetir.
 * Tüm sorgular eager loading ile optimize edilmiştir (N+1 problemi yok).
 *
 * Naming Convention:
 * - name (ad yerine)
 * - display_order (order yerine)
 * - status boolean (status yerine)
 *
 * @version 1.0.0
 * @since 2025-12-23
 */
class IlanVerticalDomainService
{
    /**
     * Turizm/Yazlık ilanlarını detaylarıyla getir
     *
     * Context7: Eager Loading zorunlu (N+1 yok)
     *
     * @param  array  $filters  Filtre kriterleri
     * @return Collection
     */
    public function getTurizmIlanlari(array $filters = []): Collection
    {
        return Ilan::query()
            ->with([
                'turizmDetail',
                'anaKategori',
                'altKategori',
                'il',
                'ilce',
                'mahalle',
                'fotograflar',
                'ozellikler',
            ])
            ->whereHas('turizmDetail')
            ->when(isset($filters['havuz_var']), function (Builder $query) use ($filters) {
                $query->whereHas('turizmDetail', function (Builder $q) use ($filters) {
                    $q->where('havuz_var', $filters['havuz_var']);
                });
            })
            ->when(isset($filters['min_gunluk_fiyat']), function (Builder $query) use ($filters) {
                $query->whereHas('turizmDetail', function (Builder $q) use ($filters) {
                    $q->where('gunluk_fiyat', '>=', $filters['min_gunluk_fiyat']);
                });
            })
            ->when(isset($filters['sezon_aktif']), function (Builder $query) use ($filters) {
                if ($filters['sezon_aktif']) {
                    $now = now();
                    $query->whereHas('turizmDetail', function (Builder $q) use ($now) {
                        $q->where('sezon_baslangic', '<=', $now)
                            ->where('sezon_bitis', '>=', $now);
                    });
                }
            })
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->get();
    }

    /**
     * Arsa/Arazi ilanlarını detaylarıyla getir
     *
     * Context7: Eager Loading zorunlu (N+1 yok)
     *
     * @param  array  $filters  Filtre kriterleri
     * @return Collection
     */
    public function getArsaIlanlari(array $filters = []): Collection
    {
        return Ilan::query()
            ->with([
                'arsaDetail',
                'anaKategori',
                'altKategori',
                'il',
                'ilce',
                'mahalle',
                'fotograflar',
            ])
            ->whereHas('arsaDetail')
            ->when(isset($filters['imar_durumu']), function (Builder $query) use ($filters) {
                $query->whereHas('arsaDetail', function (Builder $q) use ($filters) {
                    $q->where('imar_durumu', $filters['imar_durumu']);
                });
            })
            ->when(isset($filters['min_alan_m2']), function (Builder $query) use ($filters) {
                $query->whereHas('arsaDetail', function (Builder $q) use ($filters) {
                    $q->where('alan_m2', '>=', $filters['min_alan_m2']);
                });
            })
            ->when(isset($filters['altyapi_tam']), function (Builder $query) use ($filters) {
                if ($filters['altyapi_tam']) {
                    $query->whereHas('arsaDetail', function (Builder $q) {
                        $q->where('elektrik_var', true)
                            ->where('su_var', true)
                            ->where('dogalgaz_var', true);
                    });
                }
            })
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->get();
    }

    /**
     * Ticari/İşyeri ilanlarını detaylarıyla getir
     *
     * Context7: Eager Loading zorunlu (N+1 yok)
     *
     * @param  array  $filters  Filtre kriterleri
     * @return Collection
     */
    public function getTicariIlanlari(array $filters = []): Collection
    {
        return Ilan::query()
            ->with([
                'ticariDetail',
                'anaKategori',
                'altKategori',
                'il',
                'ilce',
                'mahalle',
                'fotograflar',
            ])
            ->whereHas('ticariDetail')
            ->when(isset($filters['isyeri_tipi']), function (Builder $query) use ($filters) {
                $query->whereHas('ticariDetail', function (Builder $q) use ($filters) {
                    $q->where('isyeri_tipi', $filters['isyeri_tipi']);
                });
            })
            ->when(isset($filters['ruhsat_aktif']), function (Builder $query) use ($filters) {
                if ($filters['ruhsat_aktif']) {
                    $query->whereHas('ticariDetail', function (Builder $q) {
                        $q->whereIn('ruhsat_durumu', ['aktif', 'var', 'mevcut']);
                    });
                }
            })
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->get();
    }

    /**
     * Portal senkronize ilanları getir
     *
     * Context7: Eager Loading zorunlu (N+1 yok)
     *
     * @param  string|null  $portal  Belirli portal için filtreleme (opsiyonel)
     * @return Collection
     */
    public function getPortalSyncIlanlari(?string $portal = null): Collection
    {
        return Ilan::query()
            ->with([
                'portalSync',
                'anaKategori',
                'altKategori',
                'il',
                'ilce',
            ])
            ->whereHas('portalSync', function (Builder $query) use ($portal) {
                if ($portal) {
                    $portalField = strtolower($portal).'_id';
                    $query->whereNotNull($portalField);
                }
            })
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->get();
    }

    /**
     * İlanın tüm detaylarını domain'e göre yükle
     *
     * Context7: Polymorphic Eager Loading (tek sorgu)
     *
     * @param  int  $ilanId
     * @return Ilan|null
     */
    public function getIlanWithAllDetails(int $ilanId): ?Ilan
    {
        return Ilan::query()
            ->with([
                // Core relationships
                'anaKategori',
                'altKategori',
                'yayinTipi',
                'il',
                'ilce',
                'mahalle',
                'danisman',
                'ilanSahibi',

                // Media & content
                'fotograflar',
                'ozellikler',
                // 'demirbaslar', // DISABLED: ilan_demirbas pivot tablosu henüz oluşturulmadı
                'etiketler',

                // Vertical domains (eager load hepsi, varsa yüklenecek)
                'turizmDetail',
                'arsaDetail',
                'ticariDetail',
                'portalSync',
            ])
            ->find($ilanId);
    }

    /**
     * İlanın domain'ini tespit et ve uygun detayları yükle
     *
     * Context7: Smart Loading (gereksiz eager load yok)
     *
     * @param  int  $ilanId
     * @return array ['ilan' => Ilan, 'domain' => string, 'detail' => Model]
     */
    public function getIlanByDomain(int $ilanId): array
    {
        $ilan = Ilan::with([
            'anaKategori',
            'altKategori',
            'il',
            'ilce',
            'mahalle',
        ])->find($ilanId);

        if (! $ilan) {
            return [
                'ilan' => null,
                'domain' => null,
                'detail' => null,
            ];
        }

        // Domain'i tespit et (kategori bazlı veya relationship varlığı)
        $domain = $this->detectDomain($ilan);

        // Domain'e özel detayı yükle
        $detail = match ($domain) {
            'turizm' => $ilan->turizmDetail()->with([])->first(),
            'arsa' => $ilan->arsaDetail()->with([])->first(),
            'ticari' => $ilan->ticariDetail()->with([])->first(),
            default => null,
        };

        return [
            'ilan' => $ilan,
            'domain' => $domain,
            'detail' => $detail,
        ];
    }

    /**
     * İlanın domain'ini tespit et
     *
     * @param  Ilan  $ilan
     * @return string|null 'turizm', 'arsa', 'ticari' veya null
     */
    protected function detectDomain(Ilan $ilan): ?string
    {
        // Kategori bazlı tespit (öncelikli)
        $kategoriName = strtolower($ilan->anaKategori?->name ?? '');

        if (str_contains($kategoriName, 'yazlık') || str_contains($kategoriName, 'turizm')) {
            return 'turizm';
        }

        if (str_contains($kategoriName, 'arsa') || str_contains($kategoriName, 'arazi')) {
            return 'arsa';
        }

        if (str_contains($kategoriName, 'işyeri') || str_contains($kategoriName, 'ticari')) {
            return 'ticari';
        }

        // Relationship varlığı kontrolü (fallback)
        if ($ilan->relationLoaded('turizmDetail') && $ilan->turizmDetail) {
            return 'turizm';
        }

        if ($ilan->relationLoaded('arsaDetail') && $ilan->arsaDetail) {
            return 'arsa';
        }

        if ($ilan->relationLoaded('ticariDetail') && $ilan->ticariDetail) {
            return 'ticari';
        }

        return null;
    }

    /**
     * Bulk ilanları domain'lerine göre grupla
     *
     * Context7: Efficient Batch Processing
     *
     * @param  Collection  $ilanlar
     * @return array ['turizm' => Collection, 'arsa' => Collection, 'ticari' => Collection, 'other' => Collection]
     */
    public function groupByDomain(Collection $ilanlar): array
    {
        $grouped = [
            'turizm' => collect(),
            'arsa' => collect(),
            'ticari' => collect(),
            'other' => collect(),
        ];

        foreach ($ilanlar as $ilan) {
            $domain = $this->detectDomain($ilan);

            if ($domain && isset($grouped[$domain])) {
                $grouped[$domain]->push($ilan);
            } else {
                $grouped['other']->push($ilan);
            }
        }

        return $grouped;
    }

    /**
     * 🤖 Cortex ROI Engine Integration
     *
     * İlan için AI-powered ROI hesapla ve additional_metadata'ya kaydet
     *
     * Context7: Service pattern + AI integration
     *
     * @param  int  $ilanId
     * @return array|null
     */
    public function calculateAndSaveROI(int $ilanId): ?array
    {
        // Eager load ile ilan getir
        $ilan = Ilan::with(['turizmDetail', 'arsaDetail', 'fotograflar'])
            ->find($ilanId);

        if (! $ilan) {
            Log::warning('Cortex ROI: İlan bulunamadı', ['ilan_id' => $ilanId]);

            return null;
        }

        // Cortex Engine'i başlat
        $cortexEngine = app(\App\Services\CortexROIEngine::class);

        // ROI hesapla ve kaydet
        $success = $cortexEngine->saveToMetadata($ilan);

        if ($success) {
            // Güncel metadata'yı döndür
            return $ilan->fresh()->additional_metadata['cortex_ai'] ?? null;
        }

        return null;
    }

    /**
     * 🤖 Toplu Cortex ROI Hesaplama
     *
     * Birden fazla ilan için ROI hesapla (chunk ile)
     *
     * Context7: Batch processing + eager loading
     *
     * @param  array  $ilanIds
     * @return array
     */
    public function batchCalculateROI(array $ilanIds): array
    {
        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'processed_ids' => [],
            'failed_ids' => [],
        ];

        $cortexEngine = app(\App\Services\CortexROIEngine::class);

        // Chunk ile işle (N+1 yok)
        Ilan::whereIn('id', $ilanIds)
            ->with(['turizmDetail', 'arsaDetail', 'fotograflar'])
            ->chunk(50, function ($ilanlar) use ($cortexEngine, &$results) {
                foreach ($ilanlar as $ilan) {
                    $success = $cortexEngine->saveToMetadata($ilan);

                    if ($success) {
                        $results['success_count']++;
                        $results['processed_ids'][] = $ilan->id;
                    } else {
                        $results['failed_count']++;
                        $results['failed_ids'][] = $ilan->id;
                    }
                }
            });

        Log::info('Cortex ROI toplu işlem tamamlandı', $results);

        return $results;
    }
}

<?php

namespace App\Services\AI\Copilot;

use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Talep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContextCollector
{
    protected array $routeContextMap = [
        // Dashboard
        'admin.dashboard' => 'dashboard',
        'admin.dashboard.index' => 'dashboard',
        'admin.dashboard.agent' => 'dashboard',
        'admin.dashboard.investor' => 'dashboard',

        // İlan Detail / Edit
        'admin.ilanlar.show' => 'ilan-detail',
        'admin.ilanlar.edit' => 'ilan-edit',
        'admin.ilanlar.create' => 'ilan-create',
        'admin.ilanlar.create-wizard' => 'wizard',
        'admin.wizard.create' => 'wizard',
        'admin.wizard.index' => 'wizard',
        'admin.ilanlarim.index' => 'ilan-list',
        'admin.ilanlar.index' => 'ilan-list',

        // CRM
        'admin.kisiler.index' => 'crm-list',
        'admin.kisiler.show' => 'crm-detail',
        'admin.kisiler.edit' => 'crm-edit',
        'admin.kisiler.create' => 'crm-create',
        'admin.crm.dashboard' => 'crm-dashboard',

        // Talepler
        'admin.talepler.index' => 'talep-list',
        'admin.talepler.show' => 'talep-detail',
        'admin.talepler.edit' => 'talep-edit',
        'admin.talepler.create' => 'talep-create',

        // Eşleştirmeler
        'admin.eslesmeler.index' => 'eslesme-list',
        'admin.eslesmeler.show' => 'eslesme-detail',
        'admin.eslesmeler.create' => 'eslesme-list',

        // Property Hub
        'admin.property-hub.index' => 'property-hub',
        'admin.property-hub.features.index' => 'property-hub-features',
        'admin.property-hub.features.edit' => 'property-hub-feature-edit',
        'admin.property-hub.templates.index' => 'property-hub-templates',
        'admin.property-hub.templates.edit' => 'property-hub-template-edit',

        // AI Sistem
        'admin.ai.dashboard' => 'ai-dashboard',
        'admin.ai-monitor.index' => 'ai-monitor',

        // Analitik
        'admin.analitik.istatistikler.index' => 'analytics',
        'admin.cortex' => 'analytics',

        // Danışman
        'admin.danismanlar.index' => 'danisman-list',
        'admin.danismanlar.show' => 'danisman-detail',
    ];

    public function collect(string $routeName, ?int $entityId = null): array
    {
        $contextType = $this->resolveContextType($routeName);

        $context = [
            'tip' => $contextType,
            'route' => $routeName,
            'entity_id' => $entityId,
            'timestamp' => now()->toIso8601String(),
            'data' => [],
            'meta' => [],
        ];

        try {
            $context['data'] = match ($contextType) {
                'dashboard' => $this->collectDashboard(),
                'ilan-detail', 'ilan-edit' => $this->collectIlanDetail($entityId),
                'ilan-create' => $this->collectIlanCreate(),
                'wizard' => $this->collectWizard($entityId),
                'ilan-list' => $this->collectIlanList(),
                'crm-detail', 'crm-edit' => $this->collectCrmDetail($entityId),
                'crm-list', 'crm-dashboard' => $this->collectCrmList(),
                'crm-create' => $this->collectCrmCreate(),
                'talep-detail', 'talep-edit' => $this->collectTalepDetail($entityId),
                'talep-list' => $this->collectTalepList(),
                'property-hub', 'property-hub-features', 'property-hub-templates' => $this->collectPropertyHub(),
                'eslesme-list', 'eslesme-detail' => $this->collectEslesme(),
                default => $this->collectGeneric($routeName),
            };
        } catch (\Exception $e) {
            Log::warning('Copilot ContextCollector error', [
                'context_type' => $contextType,
                'error' => $e->getMessage(),
            ]);
        }

        return $context;
    }

    protected function resolveContextType(string $routeName): string
    {
        if (isset($this->routeContextMap[$routeName])) {
            return $this->routeContextMap[$routeName];
        }

        // Fuzzy matching for wildcards
        foreach ($this->routeContextMap as $pattern => $type) {
            if (str_starts_with($routeName, rtrim($pattern, '.*'))) {
                return $type;
            }
        }

        return 'generic';
    }

    protected function collectDashboard(): array
    {
        return [
            'toplam_ilan' => DB::table('ilanlar')->count(),
            'aktif_ilan' => DB::table('ilanlar')->where('yayin_durumu', 1)->count(),
            'taslak_ilan' => DB::table('ilanlar')->where('yayin_durumu', 0)->count(),
            'toplam_kisi' => DB::table('kisiler')->count(),
            'acik_talep' => DB::table('talepler')->where('talep_durumu', 'acik')->count(),
            'bu_hafta_ilan' => DB::table('ilanlar')
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'fotosuz_ilan' => DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->whereRaw('(SELECT COUNT(*) FROM ilan_fotograflari WHERE ilan_fotograflari.ilan_id = ilanlar.id) = 0')
                ->count(),
            'fiyatsiz_ilan' => DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->where(function ($q) {
                    $q->whereNull('fiyat')->orWhere('fiyat', 0);
                })
                ->count(),
        ];
    }

    protected function collectIlanDetail(?int $ilanId): array
    {
        if (!$ilanId) {
            return [];
        }

        $ilan = Ilan::with(['photos', 'kategori', 'il', 'ilce'])->find($ilanId);
        if (!$ilan) {
            return [];
        }

        $photoCount = $ilan->photos ? $ilan->photos->count() : 0;

        return [
            'ilan' => [
                'id' => $ilan->id,
                'baslik' => $ilan->baslik,
                'fiyat' => $ilan->fiyat,
                'yayin_durumu' => $ilan->yayin_durumu,
                'aktiflik_durumu' => $ilan->aktiflik_durumu,
                'ana_kategori_id' => $ilan->ana_kategori_id,
                'alt_kategori_id' => $ilan->alt_kategori_id,
                'yayin_tipi_id' => $ilan->yayin_tipi_id,
                'il_id' => $ilan->il_id,
                'ilce_id' => $ilan->ilce_id,
                'mahalle_id' => $ilan->mahalle_id,
                'aciklama' => $ilan->aciklama,
                'oda_sayisi' => $ilan->oda_sayisi,
                'banyo_sayisi' => $ilan->banyo_sayisi,
                'net_m2' => $ilan->net_m2,
                'brut_m2' => $ilan->brut_m2,
                'kat' => $ilan->kat,
                'toplam_kat' => $ilan->toplam_kat,
                'bina_yasi' => $ilan->bina_yasi,
                'created_at' => $ilan->created_at?->toIso8601String(),
                'photo_count' => $photoCount,
                'lat' => $ilan->lat,
                'lng' => $ilan->lng,
            ],
            'photo_count' => $photoCount,
            'has_description' => !empty($ilan->aciklama) && mb_strlen($ilan->aciklama) > 50,
            'has_price' => !empty($ilan->fiyat) && $ilan->fiyat > 0,
            'has_location' => !empty($ilan->il_id) && !empty($ilan->ilce_id),
            'has_coordinates' => !empty($ilan->lat) && !empty($ilan->lng),
            'has_category' => !empty($ilan->ana_kategori_id),
            'days_since_creation' => $ilan->created_at ? now()->diffInDays($ilan->created_at) : null,
        ];
    }

    protected function collectIlanCreate(): array
    {
        return [
            'kategori_count' => DB::table('ilan_kategorileri')->where('aktiflik_durumu', 1)->count(),
            'template_count' => DB::table('yayin_tipi_sablonlari')->where('aktiflik_durumu', 1)->count(),
            'feature_count' => DB::table('features')->where('aktiflik_durumu', 1)->count(),
        ];
    }

    protected function collectIlanList(): array
    {
        $ilanlar = DB::table('ilanlar');

        return [
            'toplam' => $ilanlar->count(),
            'aktif' => DB::table('ilanlar')->where('yayin_durumu', 1)->count(),
            'taslak' => DB::table('ilanlar')->where('yayin_durumu', 0)->count(),
            'fotosuz' => DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->whereRaw('(SELECT COUNT(*) FROM ilan_fotograflari WHERE ilan_fotograflari.ilan_id = ilanlar.id) = 0')
                ->count(),
            'fiyatsiz' => DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->where(function ($q) {
                    $q->whereNull('fiyat')->orWhere('fiyat', 0);
                })
                ->count(),
            'son_7_gun' => DB::table('ilanlar')
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];
    }

    protected function collectCrmDetail(?int $kisiId): array
    {
        if (!$kisiId) {
            return [];
        }

        $kisi = DB::table('kisiler')->find($kisiId);
        if (!$kisi) {
            return [];
        }

        $talepCount = DB::table('talepler')->where('kisi_id', $kisiId)->count();
        $acikTalep = DB::table('talepler')->where('kisi_id', $kisiId)->where('talep_durumu', 'acik')->count();

        return [
            'kisi' => [
                'id' => $kisi->id,
                'ad_soyad' => $kisi->ad_soyad ?? ($kisi->ad . ' ' . ($kisi->soyad ?? '')),
                'telefon' => $kisi->telefon ?? null,
                'email' => $kisi->email ?? null,
                'kisi_tipi' => $kisi->kisi_tipi ?? null,
            ],
            'talep_count' => $talepCount,
            'acik_talep' => $acikTalep,
            'has_phone' => !empty($kisi->telefon),
            'has_email' => !empty($kisi->email),
        ];
    }

    protected function collectCrmList(): array
    {
        return [
            'toplam_kisi' => DB::table('kisiler')->count(),
            'toplam_talep' => DB::table('talepler')->count(),
            'acik_talep' => DB::table('talepler')->where('talep_durumu', 'acik')->count(),
            'bu_hafta_kisi' => DB::table('kisiler')
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
        ];
    }

    protected function collectCrmCreate(): array
    {
        return [
            'toplam_kisi' => DB::table('kisiler')->count(),
        ];
    }

    protected function collectTalepDetail(?int $talepId): array
    {
        if (!$talepId) {
            return [];
        }

        $talep = DB::table('talepler')->find($talepId);
        if (!$talep) {
            return [];
        }

        $eslesmeCount = DB::table('eslesmeler')->where('talep_id', $talepId)->count();

        return [
            'talep' => [
                'id' => $talep->id,
                'talep_durumu' => $talep->talep_durumu ?? null,
                'kisi_id' => $talep->kisi_id ?? null,
            ],
            'eslesme_count' => $eslesmeCount,
            'has_kisi' => !empty($talep->kisi_id),
        ];
    }

    protected function collectTalepList(): array
    {
        return [
            'toplam' => DB::table('talepler')->count(),
            'acik' => DB::table('talepler')->where('talep_durumu', 'acik')->count(),
            'kapali' => DB::table('talepler')->where('talep_durumu', 'kapali')->count(),
            'eslesmesiz' => DB::table('talepler')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('eslesmeler')
                        ->whereColumn('eslesmeler.talep_id', 'talepler.id');
                })
                ->count(),
        ];
    }

    protected function collectPropertyHub(): array
    {
        return [
            'feature_count' => DB::table('features')->count(),
            'aktif_feature' => DB::table('features')->where('aktiflik_durumu', 1)->count(),
            'template_count' => DB::table('yayin_tipi_sablonlari')->count(),
            'assignment_count' => DB::table('feature_assignments')->count(),
            'category_count' => DB::table('feature_categories')->count(),
        ];
    }

    protected function collectGeneric(string $routeName): array
    {
        return [
            'info' => 'Bu ekran için henüz özel context tanımlanmamış.',
        ];
    }

    /**
     * §6 Wizard context collector
     */
    protected function collectWizard(?int $entityId): array
    {
        $data = $this->collectIlanCreate();

        // If editing existing ilan via wizard
        if ($entityId) {
            $ilan = Ilan::find($entityId);
            if ($ilan) {
                $data['current_values'] = [
                    'baslik' => $ilan->baslik,
                    'fiyat' => $ilan->fiyat,
                    'ana_kategori_id' => $ilan->ana_kategori_id,
                    'alt_kategori_id' => $ilan->alt_kategori_id,
                    'yayin_tipi_id' => $ilan->yayin_tipi_id,
                    'aciklama' => $ilan->aciklama,
                    'il_id' => $ilan->il_id,
                    'lat' => $ilan->lat,
                    'lng' => $ilan->lng,
                ];
                $data['selected_kategori_id'] = $ilan->ana_kategori_id;
                $data['selected_yayin_tipi_id'] = $ilan->yayin_tipi_id;

                // Get category name for geo mode detection
                if ($ilan->ana_kategori_id) {
                    $kategori = DB::table('ilan_kategorileri')
                        ->where('id', $ilan->ana_kategori_id)
                        ->first();
                    $data['selected_kategori_adi'] = $kategori->name ?? null;
                }
            }
        }

        return $data;
    }

    /**
     * §9.2 Eşleşme context collector
     */
    protected function collectEslesme(): array
    {
        return [
            'toplam_eslesme' => DB::table('eslesmeler')->count(),
            'bekleyen_eslesme' => DB::table('eslesmeler')
                ->whereNull('sonuc')
                ->count(),
            'onaylanan' => DB::table('eslesmeler')
                ->where('sonuc', 'onaylandi')
                ->count(),
            'reddedilen' => DB::table('eslesmeler')
                ->where('sonuc', 'reddedildi')
                ->count(),
        ];
    }
}

<?php

namespace App\Services\AI\Copilot;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CopilotAuditEngine
{
    public function audit(array $context): array
    {
        $checks = match ($context['type']) {
            'dashboard' => $this->auditDashboard($context),
            'ilan-detail', 'ilan-edit' => $this->auditIlan($context),
            'ilan-create', 'wizard' => $this->auditWizard($context),
            'ilan-list' => $this->auditIlanList($context),
            'crm-detail', 'crm-edit' => $this->auditCrmDetail($context),
            'crm-list', 'crm-dashboard' => $this->auditCrmList($context),
            'talep-detail', 'talep-edit' => $this->auditTalep($context),
            'talep-list' => $this->auditTalepList($context),
            'property-hub' => $this->auditPropertyHub($context),
            'property-hub-templates' => $this->auditTemplates($context),
            'property-hub-features' => $this->auditFeatures($context),
            'eslesme-list', 'eslesme-detail' => $this->auditEslesme($context),
            default => [],
        };

        // Always run global checks
        $globalChecks = $this->auditGlobal();

        // Run deep system checks for dashboard context
        if ($context['type'] === 'dashboard') {
            $globalChecks = array_merge($globalChecks, $this->auditLocationData());
            $globalChecks = array_merge($globalChecks, $this->auditCrmDeep());
        }

        $allChecks = array_merge($checks, $globalChecks);

        // Sort by severity
        $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3, 'info' => 4];
        usort($allChecks, fn($a, $b) =>
            ($severityOrder[$a['severity']] ?? 99) <=> ($severityOrder[$b['severity']] ?? 99)
        );

        return $allChecks;
    }

    protected function auditGlobal(): array
    {
        $checks = [];

        try {
            // Check: Active listings without photos
            $fotosuzAktif = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('ilan_fotograflari')
                        ->whereColumn('ilan_fotograflari.ilan_id', 'ilanlar.id');
                })
                ->count();

            if ($fotosuzAktif > 0) {
                $checks[] = [
                    'code' => 'GLOBAL_FOTOSUZ_AKTIF',
                    'severity' => 'high',
                    'title' => $fotosuzAktif . ' aktif ilan fotoğrafsız yayında',
                    'description' => 'Bu ilanlar müşteriye kötü deneyim sunar. Fotoğraf ekleyin veya yayından çekin.',
                    'category' => 'data_quality',
                    'fixable' => false,
                ];
            }

            // Check: Active listings without price
            $fiyatsizAktif = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->where(function ($q) {
                    $q->whereNull('fiyat')->orWhere('fiyat', 0);
                })
                ->count();

            if ($fiyatsizAktif > 0) {
                $checks[] = [
                    'code' => 'GLOBAL_FIYATSIZ_AKTIF',
                    'severity' => 'critical',
                    'title' => $fiyatsizAktif . ' aktif ilan fiyatsız',
                    'description' => 'Fiyatsız ilanlar aramalarda görünmez. Acil müdahale gerekli.',
                    'category' => 'data_quality',
                    'fixable' => false,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine global check failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

    protected function auditDashboard(array $context): array
    {
        $checks = [];

        try {
            // Check: Templates without assignments
            $emptyTemplates = DB::table('yayin_tipi_sablonlari')
                ->where('aktiflik_durumu', 1)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('feature_assignments')
                        ->where('feature_assignments.assignable_type', 'App\\Models\\YayinTipiSablonu')
                        ->whereColumn('feature_assignments.assignable_id', 'yayin_tipi_sablonlari.id');
                })
                ->count();

            if ($emptyTemplates > 0) {
                $checks[] = [
                    'code' => 'DASH_BOS_SABLON',
                    'severity' => 'high',
                    'title' => $emptyTemplates . ' aktif şablon boş',
                    'description' => 'Bu şablonlar wizard\'da alan üretmiyor. Özellik atanmalı.',
                    'category' => 'configuration',
                    'fixable' => true,
                    'fix_url' => '/admin/property-hub/templates',
                ];
            }

            // Check: Features not assigned to any template
            $orphanFeatures = DB::table('features')
                ->where('aktiflik_durumu', 1)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('feature_assignments')
                        ->whereColumn('feature_assignments.feature_id', 'features.id');
                })
                ->count();

            if ($orphanFeatures > 0) {
                $checks[] = [
                    'code' => 'DASH_ORPHAN_FEATURE',
                    'severity' => 'medium',
                    'title' => $orphanFeatures . ' özellik hiçbir şablona atanmamış',
                    'description' => 'Bu özellikler tanımlı ama kullanılmıyor — atayın ya da arşivleyin.',
                    'category' => 'configuration',
                    'fixable' => true,
                    'fix_url' => '/admin/property-hub/features',
                ];
            }

            // Check: Stale listings (>120 days active, no updates)
            $staleCount = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->where('updated_at', '<', now()->subDays(120))
                ->count();

            if ($staleCount > 0) {
                $checks[] = [
                    'code' => 'DASH_STALE_ILAN',
                    'severity' => 'low',
                    'title' => $staleCount . ' ilan 120+ gündür güncellenmedi',
                    'description' => 'Uzun süredir güncellenmemiş ilanlar fiyat/bilgi açısından güncelliğini kaybetmiş olabilir.',
                    'category' => 'data_freshness',
                    'fixable' => false,
                ];
            }

            // Check: Open requests without matches
            $unmatchedTalep = DB::table('talepler')
                ->where('talep_durumu', 'acik')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('eslesmeler')
                        ->whereColumn('eslesmeler.talep_id', 'talepler.id');
                })
                ->count();

            if ($unmatchedTalep > 0) {
                $checks[] = [
                    'code' => 'DASH_UNMATCHED_TALEP',
                    'severity' => 'medium',
                    'title' => $unmatchedTalep . ' açık talep eşleştirme bekliyor',
                    'description' => 'AI eşleştirme ile bu talepleri uygun ilanlarla eşleyin.',
                    'category' => 'operational',
                    'fixable' => true,
                    'fix_url' => '/admin/eslesmeler',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine dashboard audit failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

    protected function auditIlan(array $context): array
    {
        $checks = [];
        $data = $context['data'] ?? [];
        $ilan = $data['ilan'] ?? [];

        if (empty($ilan)) {
            return $checks;
        }

        // Check: Listing has category but no publication type
        if (!empty($ilan['ana_kategori_id']) && empty($ilan['yayin_tipi_id'])) {
            $checks[] = [
                'code' => 'ILAN_YAYIN_TIPI_YOK',
                'severity' => 'high',
                'title' => 'Yayın tipi seçilmemiş',
                'description' => 'Kategori var ama yayın tipi yok. Şablon özellikleri ve wizard alanları yüklenemez.',
                'category' => 'data_completeness',
                'fixable' => true,
                'fix_url' => '/admin/ilanlar/' . $ilan['id'] . '/edit',
            ];
        }

        // Check: Listing with coordinates but no il/ilce
        if (!empty($ilan['lat']) && !empty($ilan['lng']) && empty($ilan['il_id'])) {
            $checks[] = [
                'code' => 'ILAN_KOORDINAT_IL_UYUMSUZ',
                'severity' => 'medium',
                'title' => 'Koordinat var ama il bilgisi yok',
                'description' => 'Reverse geocode ile il/ilçe otomatik doldurulabilir.',
                'category' => 'data_consistency',
                'fixable' => true,
            ];
        }

        // Check: il/ilce set but no coordinates
        if (!empty($ilan['il_id']) && !empty($ilan['ilce_id']) && (empty($ilan['lat']) || empty($ilan['lng']))) {
            $checks[] = [
                'code' => 'ILAN_IL_KOORDINAT_EKSIK',
                'severity' => 'low',
                'title' => 'İl/İlçe bilgisi var ama harita koordinatı yok',
                'description' => 'Koordinat eklenirse harita gösterimi ve yakınlık sorguları çalışır.',
                'category' => 'data_completeness',
                'fixable' => true,
            ];
        }

        // Check: Draft listing older than 30 days
        if (($ilan['yayin_durumu'] ?? null) == 0) {
            $daysSinceCreation = $data['days_since_creation'] ?? 0;
            if ($daysSinceCreation > 30) {
                $checks[] = [
                    'code' => 'ILAN_STALE_TASLAK',
                    'severity' => 'low',
                    'title' => 'Bu taslak ' . $daysSinceCreation . ' gündür bekliyor',
                    'description' => 'Taslakları tamamlayıp yayınlayın veya gereksizleri temizleyin.',
                    'category' => 'data_freshness',
                    'fixable' => false,
                ];
            }
        }

        // Check: Description too short for active listing
        if (($ilan['yayin_durumu'] ?? null) == 1 && !empty($data['has_description'])) {
            $aciklama = $ilan['aciklama'] ?? '';
            if (mb_strlen($aciklama) < 150 && mb_strlen($aciklama) > 0) {
                $checks[] = [
                    'code' => 'ILAN_KISA_ACIKLAMA',
                    'severity' => 'medium',
                    'title' => 'Açıklama çok kısa (' . mb_strlen($aciklama) . ' karakter)',
                    'description' => 'SEO ve müşteri güveni için en az 150 karakter önerilir. AI ile genişletebilirsiniz.',
                    'category' => 'data_quality',
                    'fixable' => true,
                ];
            }
        }

        return $checks;
    }

    protected function auditIlanList(array $context): array
    {
        $checks = [];
        $data = $context['data'] ?? [];

        // Check: High ratio of drafts vs active
        $toplam = $data['toplam'] ?? 0;
        $taslak = $data['taslak'] ?? 0;
        if ($toplam > 10 && $taslak > 0) {
            $ratio = $taslak / max($toplam, 1);
            if ($ratio > 0.3) {
                $checks[] = [
                    'code' => 'LISTE_YUKSEK_TASLAK_ORANI',
                    'severity' => 'medium',
                    'title' => 'Taslak oranı %' . round($ratio * 100),
                    'description' => 'İlanların önemli kısmı taslak. Tamamlayıp yayına alın veya temizleyin.',
                    'category' => 'operational',
                    'fixable' => false,
                ];
            }
        }

        return $checks;
    }

    protected function auditCrmDetail(array $context): array
    {
        $checks = [];
        $data = $context['data'] ?? [];
        $kisi = $data['kisi'] ?? [];

        // Check: Contact without any requests
        if (($data['talep_count'] ?? 0) === 0 && !empty($kisi['id'])) {
            $checks[] = [
                'code' => 'CRM_TALEPSIZ_KISI',
                'severity' => 'info',
                'title' => 'Bu kişinin talebi yok',
                'description' => 'Talep kaydı olmadan eşleştirme yapılamaz. İhtiyaçlarını kaydedin.',
                'category' => 'data_completeness',
                'fixable' => true,
                'fix_url' => '/admin/talepler/create',
            ];
        }

        // Check: Contact missing both phone and email
        if (!($data['has_phone'] ?? false) && !($data['has_email'] ?? false)) {
            $checks[] = [
                'code' => 'CRM_ILETISIM_YOK',
                'severity' => 'critical',
                'title' => 'Telefon ve e-posta — ikisi de yok',
                'description' => 'Bu kişiye hiçbir şekilde ulaşılamaz. İletişim bilgisi ekleyin.',
                'category' => 'data_quality',
                'fixable' => true,
            ];
        }

        return $checks;
    }

    protected function auditCrmList(array $context): array
    {
        $checks = [];

        try {
            // Check: Contacts without any contact info
            $noContact = DB::table('kisiler')
                ->where(function ($q) {
                    $q->whereNull('telefon')->orWhere('telefon', '');
                })
                ->where(function ($q) {
                    $q->whereNull('email')->orWhere('email', '');
                })
                ->count();

            if ($noContact > 0) {
                $checks[] = [
                    'code' => 'CRM_ILETISIMSIZ_KISILER',
                    'severity' => 'high',
                    'title' => $noContact . ' kişi iletişim bilgisi eksik',
                    'description' => 'Telefon ve e-posta olmadan bu kişilere ulaşılamaz.',
                    'category' => 'data_quality',
                    'fixable' => false,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine CRM list audit failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

    protected function auditTalep(array $context): array
    {
        $checks = [];
        $data = $context['data'] ?? [];

        if (!($data['has_kisi'] ?? true)) {
            $checks[] = [
                'code' => 'TALEP_SAHIPSIZ',
                'severity' => 'high',
                'title' => 'Talep bir kişi kaydına bağlı değil',
                'description' => 'Sahipsiz talepleri bir kişi kaydıyla ilişkilendirin.',
                'category' => 'data_integrity',
                'fixable' => true,
            ];
        }

        if (($data['eslesme_count'] ?? 0) === 0) {
            $checks[] = [
                'code' => 'TALEP_ESLESMESIZ',
                'severity' => 'medium',
                'title' => 'Eşleşme üretilmemiş',
                'description' => 'AI eşleştirme kullanarak uygun ilanları bulun.',
                'category' => 'operational',
                'fixable' => true,
                'fix_url' => '/admin/eslesmeler',
            ];
        }

        return $checks;
    }

    protected function auditTalepList(array $context): array
    {
        $checks = [];
        $data = $context['data'] ?? [];

        $eslesmesiz = $data['eslesmesiz'] ?? 0;
        $acik = $data['acik'] ?? 0;

        if ($acik > 0 && $eslesmesiz > 0 && ($eslesmesiz / max($acik, 1)) > 0.5) {
            $checks[] = [
                'code' => 'TALEP_YARI_ESLESMESIZ',
                'severity' => 'high',
                'title' => 'Açık taleplerin %' . round(($eslesmesiz / max($acik, 1)) * 100) . '\'i eşleşmemiş',
                'description' => 'Toplu AI eşleştirme ile bu oranı hızla düşürebilirsiniz.',
                'category' => 'operational',
                'fixable' => true,
                'fix_url' => '/admin/eslesmeler',
            ];
        }

        return $checks;
    }

    protected function auditPropertyHub(array $context): array
    {
        $checks = [];
        $data = $context['data'] ?? [];

        try {
            // Check: Templates with 0 assignments
            $templateCount = $data['template_count'] ?? 0;
            $assignmentCount = $data['assignment_count'] ?? 0;

            if ($templateCount > 0 && $assignmentCount === 0) {
                $checks[] = [
                    'code' => 'PHUB_SIFIR_ATAMA',
                    'severity' => 'critical',
                    'title' => 'Hiçbir şablona özellik atanmamış',
                    'description' => 'Wizard alan üretemiyor. Şablonlara özellik atayın.',
                    'category' => 'configuration',
                    'fixable' => true,
                    'fix_url' => '/admin/property-hub/templates',
                ];
            }

            // Check: Features exist but no categories
            $featureCount = $data['feature_count'] ?? 0;
            $categoryCount = $data['category_count'] ?? 0;

            if ($featureCount > 0 && $categoryCount === 0) {
                $checks[] = [
                    'code' => 'PHUB_KATEGORISIZ_OZELLIK',
                    'severity' => 'high',
                    'title' => 'Özellik kategorisi tanımlanmamış',
                    'description' => 'Kategoriler olmadan özellikler gruplanamaz.',
                    'category' => 'configuration',
                    'fixable' => true,
                    'fix_url' => '/admin/ozellikler/kategoriler',
                ];
            }

            // Check: Orphan assignments (feature_id references deleted feature)
            $orphanAssignments = DB::table('feature_assignments')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('features')
                        ->whereColumn('features.id', 'feature_assignments.feature_id');
                })
                ->count();

            if ($orphanAssignments > 0) {
                $checks[] = [
                    'code' => 'PHUB_ORPHAN_ASSIGNMENT',
                    'severity' => 'high',
                    'title' => $orphanAssignments . ' atama silinmiş özelliğe referans veriyor',
                    'description' => 'Bu kayıtlar veri bütünlüğü sorunu oluşturur. Temizlenmeli.',
                    'category' => 'data_integrity',
                    'fixable' => true,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine property-hub audit failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

    protected function auditTemplates(array $context): array
    {
        return $this->auditPropertyHub($context);
    }

    protected function auditFeatures(array $context): array
    {
        $checks = [];

        try {
            // Check: Inactive features still referenced in assignments
            $inactiveAssigned = DB::table('features')
                ->where('aktiflik_durumu', 0)
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('feature_assignments')
                        ->whereColumn('feature_assignments.feature_id', 'features.id');
                })
                ->count();

            if ($inactiveAssigned > 0) {
                $checks[] = [
                    'code' => 'FEAT_PASIF_AMA_ATANMIS',
                    'severity' => 'medium',
                    'title' => $inactiveAssigned . ' pasif özellik hâlâ şablonlara atanmış',
                    'description' => 'Pasif özellikler wizard\'da görünmez ama atama kalıntısı kalır.',
                    'category' => 'data_consistency',
                    'fixable' => true,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine features audit failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

    /**
     * §13 Wizard Audit Kontrolleri
     * Field render, dependency validation, template invalidation
     */
    protected function auditWizard(array $context): array
    {
        $checks = [];

        try {
            // Check: Active templates without any feature assignments
            $emptyActiveTemplates = DB::table('yayin_tipi_sablonlari')
                ->where('aktiflik_durumu', 1)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('feature_assignments')
                        ->where('feature_assignments.assignable_type', 'App\\Models\\YayinTipiSablonu')
                        ->whereColumn('feature_assignments.assignable_id', 'yayin_tipi_sablonlari.id');
                })
                ->count();

            if ($emptyActiveTemplates > 0) {
                $checks[] = [
                    'code' => 'WIZARD_BOS_SABLON',
                    'severity' => 'critical',
                    'title' => $emptyActiveTemplates . ' aktif şablon wizard\'da alan üretmiyor',
                    'description' => 'Boş şablonlar wizard form adımında hiç özellik alanı göstermez. Özellik atayın.',
                    'category' => 'configuration',
                    'fixable' => true,
                    'fix_url' => '/admin/property-hub/templates',
                ];
            }

            // Check: Categories without publication types (no wizard path)
            $kategorilerWithoutYayinTipi = DB::table('ilan_kategorileri as parent')
                ->where('parent.seviye', 0) // Ana kategoriler
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('ilan_kategorileri as child')
                        ->where('child.seviye', 2)
                        ->whereColumn('child.parent_id', 'parent.id');

                    // Also check grandchildren
                    $q->orWhereExists(function ($q2) {
                        $q2->select(DB::raw(1))
                            ->from('ilan_kategorileri as mid')
                            ->whereColumn('mid.parent_id', 'ilan_kategorileri.id')
                            ->where('mid.seviye', 1)
                            ->whereExists(function ($q3) {
                                $q3->select(DB::raw(1))
                                    ->from('ilan_kategorileri as leaf')
                                    ->whereColumn('leaf.parent_id', 'mid.id')
                                    ->where('leaf.seviye', 2);
                            });
                    });
                })
                ->count();

            if ($kategorilerWithoutYayinTipi > 0) {
                $checks[] = [
                    'code' => 'WIZARD_KATEGORISIZ_YAYIN_TIPI',
                    'severity' => 'high',
                    'title' => $kategorilerWithoutYayinTipi . ' ana kategori yayın tipi tanımsız',
                    'description' => 'Bu kategorilerdeki ilanlar için wizard şablon bulamaz.',
                    'category' => 'configuration',
                    'fixable' => true,
                    'fix_url' => '/admin/ilan-kategorileri',
                ];
            }

            // Check: Duplicate feature assignments (same feature assigned multiple times to same template)
            $duplicateAssignments = DB::table('feature_assignments')
                ->select('assignable_type', 'assignable_id', 'feature_id')
                ->groupBy('assignable_type', 'assignable_id', 'feature_id')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            if ($duplicateAssignments > 0) {
                $checks[] = [
                    'code' => 'WIZARD_DUPLIKE_ATAMA',
                    'severity' => 'medium',
                    'title' => $duplicateAssignments . ' mükerrer özellik ataması',
                    'description' => 'Aynı özellik bir şablona birden fazla kez atanmış. Wizard\'da çift alan çıkar.',
                    'category' => 'data_integrity',
                    'fixable' => true,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine wizard audit failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

    /**
     * §10 Location / Geo Data Audit
     */
    protected function auditLocationData(): array
    {
        $checks = [];

        try {
            // Check: Active listings with lat/lng outside Turkey bounds
            $outOfBounds = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->where(function ($q) {
                    $q->where('lat', '<', 35.8)
                        ->orWhere('lat', '>', 42.1)
                        ->orWhere('lng', '<', 25.6)
                        ->orWhere('lng', '>', 44.8);
                })
                ->count();

            if ($outOfBounds > 0) {
                $checks[] = [
                    'code' => 'LOC_SINIR_DISI_KOORDINAT',
                    'severity' => 'high',
                    'title' => $outOfBounds . ' ilan Türkiye sınırları dışında koordinata sahip',
                    'description' => 'Bu ilanların koordinatları hatalı. Haritada yanlış gösteriliyor.',
                    'category' => 'data_quality',
                    'fixable' => true,
                ];
            }

            // Check: Active listings with il_id but lat/lng at exact zero
            $zeroCoords = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->where(function ($q) {
                    $q->where('lat', 0)->orWhere('lng', 0);
                })
                ->whereNotNull('il_id')
                ->count();

            if ($zeroCoords > 0) {
                $checks[] = [
                    'code' => 'LOC_SIFIR_KOORDINAT',
                    'severity' => 'medium',
                    'title' => $zeroCoords . ' ilan sıfır koordinata sahip',
                    'description' => 'lat/lng 0 olan ilanlar haritada Afrika\'yı gösterir. Koordinat düzeltin.',
                    'category' => 'data_quality',
                    'fixable' => true,
                ];
            }

            // Check: Active listings with coordinates but no il/ilce (reverse geocode needed)
            $coordsNoIl = DB::table('ilanlar')
                ->where('yayin_durumu', 1)
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->where('lat', '!=', 0)
                ->where('lng', '!=', 0)
                ->whereNull('il_id')
                ->count();

            if ($coordsNoIl > 0) {
                $checks[] = [
                    'code' => 'LOC_REVERSE_GEOCODE_GEREKLI',
                    'severity' => 'medium',
                    'title' => $coordsNoIl . ' ilan koordinatlı ama il bilgisi yok',
                    'description' => 'Reverse geocode ile il/ilçe otomatik doldurulabilir.',
                    'category' => 'data_completeness',
                    'fixable' => true,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine location audit failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

    /**
     * §8 CRM Deep Audit — Duplicate contacts, stale leads
     */
    protected function auditCrmDeep(): array
    {
        $checks = [];

        try {
            // Check: Potential duplicate contacts (same phone number)
            $duplicatePhones = DB::table('kisiler')
                ->select('telefon')
                ->whereNotNull('telefon')
                ->where('telefon', '!=', '')
                ->groupBy('telefon')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            if ($duplicatePhones > 0) {
                $checks[] = [
                    'code' => 'CRM_DUPLIKE_TELEFON',
                    'severity' => 'high',
                    'title' => $duplicatePhones . ' telefon numarası birden fazla kişide',
                    'description' => 'Aynı numaraya kayıtlı kişiler birleştirilmeli. Veri kirliliği riski.',
                    'category' => 'data_integrity',
                    'fixable' => false,
                ];
            }

            // Check: Contacts with no activity (no talepler, not linked to any ilan) — stale leads
            $staleContacts = DB::table('kisiler')
                ->where('aktiflik_durumu', 1)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('talepler')
                        ->whereColumn('talepler.kisi_id', 'kisiler.id');
                })
                ->where('created_at', '<', now()->subDays(90))
                ->count();

            if ($staleContacts > 0) {
                $checks[] = [
                    'code' => 'CRM_BAYAT_LEAD',
                    'severity' => 'low',
                    'title' => $staleContacts . ' kişi 90+ gündür hareketsiz',
                    'description' => '3 aydan uzun süredir talebi olmayan aktif kişiler. İletişime geçin veya pasife alın.',
                    'category' => 'data_freshness',
                    'fixable' => false,
                ];
            }

            // Check: Open requests (talepler) older than 60 days without match
            $staleTalepler = DB::table('talepler')
                ->where('talep_durumu', 'acik')
                ->where('created_at', '<', now()->subDays(60))
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('eslesmeler')
                        ->whereColumn('eslesmeler.talep_id', 'talepler.id');
                })
                ->count();

            if ($staleTalepler > 0) {
                $checks[] = [
                    'code' => 'CRM_BAYAT_TALEP',
                    'severity' => 'medium',
                    'title' => $staleTalepler . ' açık talep 60+ gündür eşleşmemiş',
                    'description' => 'Uzun süredir karşılanmayan talepler — müşteriyle iletişime geçin veya kriteri genişletin.',
                    'category' => 'data_freshness',
                    'fixable' => false,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine CRM deep audit failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

    /**
     * §9.2 Eşleşme Audit — matching quality
     */
    protected function auditEslesme(array $context): array
    {
        $checks = [];

        try {
            // Check: Old unresolved matches
            $unresolvedCount = DB::table('eslesmeler')
                ->whereNull('sonuc')
                ->where('created_at', '<', now()->subDays(14))
                ->count();

            if ($unresolvedCount > 0) {
                $checks[] = [
                    'code' => 'ESLESME_CEVAPSIZ',
                    'severity' => 'high',
                    'title' => $unresolvedCount . ' eşleşme 14+ gündür cevaplanmamış',
                    'description' => 'Eşleşmelere zamanında dönüş yapmak müşteri güvenini korur.',
                    'category' => 'operational',
                    'fixable' => false,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('CopilotAuditEngine eslesme audit failed', ['error' => $e->getMessage()]);
        }

        return $checks;
    }

}

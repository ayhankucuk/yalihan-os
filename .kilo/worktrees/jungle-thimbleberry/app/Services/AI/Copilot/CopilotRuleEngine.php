<?php

namespace App\Services\AI\Copilot;

use Illuminate\Support\Facades\DB;

class CopilotRuleEngine
{
    public function evaluate(array $context): array
    {
        $rules = match ($context['tip']) {
            'dashboard' => $this->dashboardRules($context['data']),
            'ilan-detail', 'ilan-edit' => $this->ilanDetailRules($context['data']),
            'ilan-create', 'wizard' => $this->wizardRules($context['data']),
            'ilan-list' => $this->ilanListRules($context['data']),
            'crm-detail', 'crm-edit' => $this->crmDetailRules($context['data']),
            'crm-list', 'crm-dashboard' => $this->crmListRules($context['data']),
            'talep-detail', 'talep-edit' => $this->talepDetailRules($context['data']),
            'talep-list' => $this->talepListRules($context['data']),
            'property-hub', 'property-hub-features', 'property-hub-templates' => $this->propertyHubRules($context['data']),
            'eslesme-list', 'eslesme-detail' => $this->eslesmeRules($context['data']),
            default => [],
        };

        // Sort by priority (1 = highest)
        usort($rules, fn($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));

        return $rules;
    }

    protected function dashboardRules(array $data): array
    {
        $rules = [];

        if (($data['fotosuz_ilan'] ?? 0) > 0) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'FOTOSUZ_ILAN',
                'title' => $data['fotosuz_ilan'] . ' ilan fotoğrafsız',
                'description' => 'Fotoğrafsız ilanlar %70 daha az görüntülenme alır. Hemen fotoğraf ekleyin.',
                'priority' => 1,
                'icon' => 'camera',
                'action' => ['label' => 'İlanları Gör', 'url' => route('admin.ilanlar.index') . '?filter=fotosuz'],
            ];
        }

        if (($data['fiyatsiz_ilan'] ?? 0) > 0) {
            $rules[] = [
                'tip' => 'critical',
                'code' => 'FIYATSIZ_ILAN',
                'title' => $data['fiyatsiz_ilan'] . ' ilan fiyatsız',
                'description' => 'Fiyatsız ilanlar aramalarda görünmez. Fiyat bilgisi ekleyin.',
                'priority' => 1,
                'icon' => 'currency',
                'action' => ['label' => 'Düzelt', 'url' => route('admin.ilanlar.index') . '?filter=fiyatsiz'],
            ];
        }

        if (($data['taslak_ilan'] ?? 0) > 3) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'COK_TASLAK',
                'title' => $data['taslak_ilan'] . ' taslak ilan bekliyor',
                'description' => 'Taslak ilanları tamamlayıp yayına alın veya gereksizleri silin.',
                'priority' => 3,
                'icon' => 'document',
            ];
        }

        if (($data['acik_talep'] ?? 0) > 0) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'ACIK_TALEP',
                'title' => $data['acik_talep'] . ' açık talep var',
                'description' => 'Bekleyen müşteri talepleri eşleştirme bekliyor.',
                'priority' => 2,
                'icon' => 'users',
                'action' => ['label' => 'Talepleri Gör', 'url' => route('admin.talepler.index')],
            ];
        }

        if (($data['toplam_ilan'] ?? 0) === 0) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'ILK_ILAN',
                'title' => 'Henüz ilan yok',
                'description' => 'İlk ilanınızı AI destekli wizard ile oluşturun.',
                'priority' => 1,
                'icon' => 'plus',
                'action' => ['label' => 'İlan Oluştur', 'url' => route('admin.ilanlar.create')],
            ];
        }

        return $rules;
    }

    protected function ilanDetailRules(array $data): array
    {
        $rules = [];
        $ilan = $data['ilan'] ?? [];

        if (!($data['has_price'] ?? false)) {
            $rules[] = [
                'tip' => 'critical',
                'code' => 'FIYAT_EKSIK',
                'title' => 'Fiyat bilgisi eksik',
                'description' => 'Fiyatsız ilanlar aramalarda görünmez ve müşteri güveni düşer.',
                'priority' => 1,
                'icon' => 'currency',
                'action' => $this->editAction($ilan['id'] ?? null),
            ];
        }

        if (($data['photo_count'] ?? 0) === 0) {
            $rules[] = [
                'tip' => 'critical',
                'code' => 'FOTO_EKSIK',
                'title' => 'Fotoğraf yok',
                'description' => 'Fotoğrafsız ilanlar %70 daha az görüntülenme alır. En az 5 fotoğraf önerilir.',
                'priority' => 1,
                'icon' => 'camera',
                'action' => $this->editAction($ilan['id'] ?? null),
            ];
        } elseif (($data['photo_count'] ?? 0) < 5) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'AZ_FOTO',
                'title' => 'Yetersiz fotoğraf (' . $data['photo_count'] . '/5)',
                'description' => '5+ fotoğraflı ilanlar %40 daha fazla ilgi görür.',
                'priority' => 3,
                'icon' => 'camera',
                'action' => $this->editAction($ilan['id'] ?? null),
            ];
        }

        if (!($data['has_description'] ?? false)) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'ACIKLAMA_EKSIK',
                'title' => 'Açıklama yetersiz',
                'description' => 'Detaylı açıklama (en az 150 karakter) SEO ve müşteri güveni için gerekli.',
                'priority' => 2,
                'icon' => 'document-text',
                'action' => $this->editAction($ilan['id'] ?? null),
            ];
        }

        if (!($data['has_location'] ?? false)) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'KONUM_EKSIK',
                'title' => 'İl/İlçe bilgisi eksik',
                'description' => 'Konum bilgisi olmadan ilan doğru bölgede aranamaz.',
                'priority' => 2,
                'icon' => 'map-pin',
                'action' => $this->editAction($ilan['id'] ?? null),
            ];
        }

        if (!($data['has_coordinates'] ?? false)) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'KOORDINAT_EKSIK',
                'title' => 'Harita koordinatı yok',
                'description' => 'Koordinat eklenen ilanlar haritada gösterilir ve %25 daha fazla tıklanır.',
                'priority' => 4,
                'icon' => 'globe',
                'action' => $this->editAction($ilan['id'] ?? null),
            ];
        }

        if (!($data['has_category'] ?? false)) {
            $rules[] = [
                'tip' => 'critical',
                'code' => 'KATEGORI_EKSIK',
                'title' => 'Kategori seçilmemiş',
                'description' => 'Kategorisiz ilanlar doğru şekilde filtrelenmez.',
                'priority' => 1,
                'icon' => 'tag',
                'action' => $this->editAction($ilan['id'] ?? null),
            ];
        }

        // Stale listing check
        $daysSinceCreation = $data['days_since_creation'] ?? 0;
        if ($daysSinceCreation > 90 && ($ilan['yayin_durumu'] ?? 0) == 1) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'ESKI_ILAN',
                'title' => $daysSinceCreation . ' gündür yayında',
                'description' => 'Uzun süredir yayında olan ilanların fiyatını veya açıklamasını güncellemeyi düşünün.',
                'priority' => 4,
                'icon' => 'clock',
            ];
        }

        // Missing rooms/area for residential
        if (!empty($ilan['ana_kategori_id'])) {
            if (empty($ilan['oda_sayisi']) && empty($ilan['net_m2'])) {
                $rules[] = [
                    'tip' => 'warning',
                    'code' => 'TEKNIK_BILGI_EKSIK',
                    'title' => 'Oda sayısı ve m² bilgisi eksik',
                    'description' => 'Oda sayısı ve metrekare filtre kriterlerinin başında gelir.',
                    'priority' => 2,
                    'icon' => 'home',
                    'action' => $this->editAction($ilan['id'] ?? null),
                ];
            }
        }

        // §9.2 Deterministic Backbone — Publish Readiness (blocking rules)
        $publishBlockers = $this->publishReadinessCheck($data);
        if (!empty($publishBlockers)) {
            $rules = array_merge($rules, $publishBlockers);
        }

        // §10 Location Intelligence Rules
        $locationRules = $this->locationRules($data);
        if (!empty($locationRules)) {
            $rules = array_merge($rules, $locationRules);
        }

        return $rules;
    }

    /**
     * §9.2 Deterministic Backbone — Publish Readiness Check
     * Returns blocking rules that prevent successful publication.
     */
    protected function publishReadinessCheck(array $data): array
    {
        $rules = [];
        $ilan = $data['ilan'] ?? [];

        // Only check readiness for drafts
        if (($ilan['yayin_durumu'] ?? null) != 0) {
            return $rules;
        }

        $blockers = [];

        if (!($data['has_price'] ?? false)) {
            $blockers[] = 'Fiyat';
        }
        if (!($data['has_category'] ?? false)) {
            $blockers[] = 'Kategori';
        }
        if (($data['photo_count'] ?? 0) === 0) {
            $blockers[] = 'Fotoğraf';
        }
        if (empty($ilan['baslik'])) {
            $blockers[] = 'Başlık';
        }

        if (!empty($blockers)) {
            $rules[] = [
                'tip' => 'critical',
                'code' => 'YAYIN_HAZIR_DEGIL',
                'title' => 'Yayınlamak için ' . count($blockers) . ' kritik eksik',
                'description' => 'Eksik alanlar: ' . implode(', ', $blockers) . '. Bu alanlar tamamlanmadan ilan yayınlanamaz.',
                'priority' => 1,
                'icon' => 'shield-exclamation',
                'action' => $this->editAction($ilan['id'] ?? null),
                'blocking' => true,
                'missing_fields' => $blockers,
            ];
        } else {
            // Draft is ready to publish
            $rules[] = [
                'tip' => 'tip',
                'code' => 'YAYIN_HAZIR',
                'title' => 'İlan yayınlanmaya hazır',
                'description' => 'Tüm zorunlu alanlar dolu. İlanı yayına alabilirsiniz.',
                'priority' => 2,
                'icon' => 'check-circle',
            ];
        }

        return $rules;
    }

    /**
     * §10 Location Intelligence Rules
     */
    protected function locationRules(array $data): array
    {
        $rules = [];
        $ilan = $data['ilan'] ?? [];

        // Coordinate validity check
        $lat = $ilan['lat'] ?? null;
        $lng = $ilan['lng'] ?? null;

        if ($lat !== null && $lng !== null) {
            // Turkey bounding box: lat 35.8-42.1, lng 25.6-44.8
            if ($lat < 35.8 || $lat > 42.1 || $lng < 25.6 || $lng > 44.8) {
                $rules[] = [
                    'tip' => 'warning',
                    'code' => 'KOORDINAT_TURKIYE_DISI',
                    'title' => 'Koordinat Türkiye sınırları dışında',
                    'description' => 'Girilen koordinatlar (' . round($lat, 4) . ', ' . round($lng, 4) . ') Türkiye dışına işaret ediyor. Kontrol edin.',
                    'priority' => 2,
                    'icon' => 'globe',
                    'action' => $this->editAction($ilan['id'] ?? null),
                ];
            }

            // Bodrum specific check (project focus area)
            $bodrumLat = 37.0344;
            $bodrumLng = 27.4305;
            $distance = $this->haversineKm($lat, $lng, $bodrumLat, $bodrumLng);

            if ($distance <= 50 && empty($ilan['mahalle_id'])) {
                $rules[] = [
                    'tip' => 'tip',
                    'code' => 'BODRUM_MAHALLE_ONERISI',
                    'title' => 'Bodrum bölgesi — mahalle bilgisi ekleyin',
                    'description' => 'Bodrum\'da mahalle detayı ilanın bulunabilirliğini önemli ölçüde artırır.',
                    'priority' => 3,
                    'icon' => 'map-pin',
                    'action' => $this->editAction($ilan['id'] ?? null),
                ];
            }
        }

        // Geometry data check for arsa/arazi
        if (!empty($ilan['geometry_type']) && empty($ilan['geometry'])) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'POLYGON_EKSIK',
                'title' => 'Geometri tipi belirli ama çizim yok',
                'description' => 'Parseli haritada çizerek alıcıya kesin sınırları gösterin.',
                'priority' => 3,
                'icon' => 'map',
            ];
        }

        return $rules;
    }

    /**
     * §9.2 Eşleştirme rules — Match readiness
     */
    protected function eslesmeRules(array $data): array
    {
        $rules = [];

        $totalEslesme = $data['toplam_eslesme'] ?? 0;
        $pendingEslesme = $data['bekleyen_eslesme'] ?? 0;

        if ($pendingEslesme > 0) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'BEKLEYEN_ESLESME',
                'title' => $pendingEslesme . ' eşleşme değerlendirilmedi',
                'description' => 'AI\'ın ürettiği eşleşmeleri onaylayın veya reddedin. Müşteriye dönüş hızı güven oluşturur.',
                'priority' => 1,
                'icon' => 'clock',
            ];
        }

        if ($totalEslesme === 0) {
            $rules[] = [
                'tip' => 'tip',
                'code' => 'ESLESME_BASLAT',
                'title' => 'Henüz eşleşme yok',
                'description' => 'AI eşleştirme motoru ile talepleri ilanlarla otomatik eşleştirin.',
                'priority' => 1,
                'icon' => 'sparkles',
                'action' => ['label' => 'Eşleştir', 'url' => route('admin.eslesmeler.create')],
            ];
        }

        return $rules;
    }

    /**
     * Haversine distance between two coordinates in km.
     */
    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    protected function ilanCreateRules(array $data): array
    {
        $rules = [];

        $rules[] = [
            'tip' => 'tip',
            'code' => 'WIZARD_TIP',
            'title' => 'AI Wizard kullanın',
            'description' => 'Yeni ilan oluştururken AI wizard başlık ve açıklama önerileri sunar.',
            'priority' => 1,
            'icon' => 'sparkles',
        ];

        if (($data['template_count'] ?? 0) > 0) {
            $rules[] = [
                'tip' => 'tip',
                'code' => 'SABLON_MEVCUT',
                'title' => $data['template_count'] . ' şablon hazır',
                'description' => 'Yayın tipi seçtiğinizde özellikler otomatik yüklenir.',
                'priority' => 3,
                'icon' => 'template',
            ];
        }

        return $rules;
    }

    /**
     * §6 Smart Wizard Intelligence — Wizard-specific rules
     * Category→field generation, template coupling, geo/polygon mode
     */
    protected function wizardRules(array $data): array
    {
        $rules = $this->ilanCreateRules($data);

        // §6.1 Category→Yayin Tipi uyumu
        if (!empty($data['selected_kategori_id']) && empty($data['selected_yayin_tipi_id'])) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'WIZARD_YAYIN_TIPI_SECILMEDI',
                'title' => 'Yayın tipi henüz seçilmedi',
                'description' => 'Yayın tipi seçilmeden özellik alanları yüklenemez. Kategori altındaki yayın tiplerinden birini seçin.',
                'priority' => 1,
                'icon' => 'template',
            ];
        }

        // §6.2 Template coupling — selected yayin tipi has no template
        if (!empty($data['selected_yayin_tipi_id'])) {
            $hasTemplate = DB::table('yayin_tipi_sablonlari')
                ->where('ilan_kategorisi_id', $data['selected_yayin_tipi_id'])
                ->where('aktiflik_durumu', 1)
                ->exists();

            if (!$hasTemplate) {
                $rules[] = [
                    'tip' => 'critical',
                    'code' => 'WIZARD_SABLON_YOK',
                    'title' => 'Bu yayın tipine ait şablon yok',
                    'description' => 'Şablon olmadan özellik alanları üretilemez. Property Hub\'dan şablon oluşturun.',
                    'priority' => 1,
                    'icon' => 'exclamation',
                    'action' => ['label' => 'Şablon Oluştur', 'url' => route('admin.property-hub.templates.create')],
                ];
            } else {
                // Check if template has features assigned
                $assignmentCount = DB::table('feature_assignments')
                    ->where('assignable_type', 'App\\Models\\YayinTipiSablonu')
                    ->whereIn('assignable_id', function ($q) use ($data) {
                        $q->select('id')
                            ->from('yayin_tipi_sablonlari')
                            ->where('ilan_kategorisi_id', $data['selected_yayin_tipi_id'])
                            ->where('aktiflik_durumu', 1);
                    })
                    ->count();

                if ($assignmentCount === 0) {
                    $rules[] = [
                        'tip' => 'warning',
                        'code' => 'WIZARD_SABLON_BOS',
                        'title' => 'Şablon mevcut ama özellik atanmamış',
                        'description' => 'Bu şablon henüz boş — wizard\'da özel alan gösterilmeyecek.',
                        'priority' => 2,
                        'icon' => 'template',
                        'action' => ['label' => 'Özellik Ata', 'url' => route('admin.property-hub.templates.index')],
                    ];
                }
            }
        }

        // §6.3 Geo/Polygon mode for arsa/arazi
        if (!empty($data['selected_kategori_adi'])) {
            $arsaKeywords = ['arsa', 'arazi', 'tarla', 'zeytinlik', 'bağ'];
            $kategoriAdi = mb_strtolower($data['selected_kategori_adi']);

            foreach ($arsaKeywords as $keyword) {
                if (str_contains($kategoriAdi, $keyword)) {
                    $rules[] = [
                        'tip' => 'tip',
                        'code' => 'WIZARD_POLYGON_MODU',
                        'title' => 'Arsa/arazi ilanı — harita çizimi önerilir',
                        'description' => 'Arsa ilanlarında parseli haritada çizmek alıcı güvenini artırır. Koordinat ekledikten sonra polygon çizimi yapabilirsiniz.',
                        'priority' => 2,
                        'icon' => 'map',
                    ];
                    break;
                }
            }
        }

        // §6.4 Required fields validation hint
        $requiredFields = ['baslik', 'fiyat', 'ana_kategori_id'];
        $missingRequired = [];
        foreach ($requiredFields as $field) {
            if (empty($data['current_values'][$field] ?? null)) {
                $missingRequired[] = $field;
            }
        }

        if (!empty($missingRequired) && !empty($data['current_values'])) {
            $fieldLabels = [
                'baslik' => 'Başlık',
                'fiyat' => 'Fiyat',
                'ana_kategori_id' => 'Kategori',
            ];
            $missingLabels = array_map(fn($f) => $fieldLabels[$f] ?? $f, $missingRequired);
            $rules[] = [
                'tip' => 'warning',
                'code' => 'WIZARD_ZORUNLU_EKSIK',
                'title' => count($missingRequired) . ' zorunlu alan eksik',
                'description' => 'Yayınlamak için şu alanlar gerekli: ' . implode(', ', $missingLabels),
                'priority' => 1,
                'icon' => 'alert',
            ];
        }

        return $rules;
    }

    protected function ilanListRules(array $data): array
    {
        $rules = [];

        if (($data['fotosuz'] ?? 0) > 0) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'LISTE_FOTOSUZ',
                'title' => $data['fotosuz'] . ' aktif ilan fotoğrafsız',
                'description' => 'Bu ilanları öncelikle tamamlayın — görünürlüğü ciddi etkiler.',
                'priority' => 1,
                'icon' => 'camera',
            ];
        }

        if (($data['fiyatsiz'] ?? 0) > 0) {
            $rules[] = [
                'tip' => 'critical',
                'code' => 'LISTE_FIYATSIZ',
                'title' => $data['fiyatsiz'] . ' aktif ilan fiyatsız',
                'description' => 'Fiyatsız ilanlar aramalarda görünmez.',
                'priority' => 1,
                'icon' => 'currency',
            ];
        }

        if (($data['taslak'] ?? 0) > 5) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'COK_TASLAK',
                'title' => $data['taslak'] . ' taslak bekliyor',
                'description' => 'Tamamlanmayan taslakları temizlemek portföyü düzenli tutar.',
                'priority' => 3,
                'icon' => 'document',
            ];
        }

        return $rules;
    }

    protected function crmDetailRules(array $data): array
    {
        $rules = [];
        $kisi = $data['kisi'] ?? [];

        if (!($data['has_phone'] ?? false)) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'TELEFON_EKSIK',
                'title' => 'Telefon numarası eksik',
                'description' => 'İletişim bilgisi olmadan müşteriyle temas kurulamaz.',
                'priority' => 1,
                'icon' => 'phone',
            ];
        }

        if (!($data['has_email'] ?? false)) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'EMAIL_EKSIK',
                'title' => 'E-posta adresi eksik',
                'description' => 'E-posta toplu bildirim ve pazarlama için gerekli.',
                'priority' => 3,
                'icon' => 'mail',
            ];
        }

        if (($data['acik_talep'] ?? 0) > 0) {
            $rules[] = [
                'tip' => 'info',
                'code' => 'ACIK_TALEP_MEVCUT',
                'title' => $data['acik_talep'] . ' açık talep var',
                'description' => 'Bu kişinin aktif talepleri eşleştirme bekliyor.',
                'priority' => 2,
                'icon' => 'clipboard',
                'action' => ['label' => 'Talepleri Gör', 'url' => route('admin.talepler.index') . '?kisi_id=' . ($kisi['id'] ?? '')],
            ];
        }

        if (($data['talep_count'] ?? 0) === 0) {
            $rules[] = [
                'tip' => 'tip',
                'code' => 'TALEP_OLUSTUR',
                'title' => 'Henüz talep kaydı yok',
                'description' => 'Müşteri ihtiyaçlarını talep olarak kaydedin, AI otomatik eşleştirir.',
                'priority' => 2,
                'icon' => 'plus-circle',
                'action' => ['label' => 'Talep Oluştur', 'url' => route('admin.talepler.create')],
            ];
        }

        return $rules;
    }

    protected function crmListRules(array $data): array
    {
        $rules = [];

        if (($data['acik_talep'] ?? 0) > 5) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'COK_ACIK_TALEP',
                'title' => $data['acik_talep'] . ' açık talep bekliyor',
                'description' => 'Bekleyen talepleri değerlendirin — AI eşleştirme ile hızlanın.',
                'priority' => 1,
                'icon' => 'users',
                'action' => ['label' => 'Eşleştir', 'url' => route('admin.eslesmeler.index')],
            ];
        }

        return $rules;
    }

    protected function talepDetailRules(array $data): array
    {
        $rules = [];

        if (($data['eslesme_count'] ?? 0) === 0) {
            $rules[] = [
                'tip' => 'tip',
                'code' => 'ESLESME_YOK',
                'title' => 'Henüz eşleşme yok',
                'description' => 'AI eşleştirme ile uygun ilanları otomatik bulun.',
                'priority' => 1,
                'icon' => 'sparkles',
                'action' => ['label' => 'AI Eşleştir', 'url' => route('admin.eslesmeler.index') . '?talep_id=' . ($data['talep']['id'] ?? '')],
            ];
        }

        if (!($data['has_kisi'] ?? false)) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'KISI_BAGLANTISI_YOK',
                'title' => 'Talep bir kişiye bağlı değil',
                'description' => 'Talebi bir kişi kaydına bağlamak takip süreçlerini kolaylaştırır.',
                'priority' => 2,
                'icon' => 'link',
            ];
        }

        return $rules;
    }

    protected function talepListRules(array $data): array
    {
        $rules = [];

        if (($data['eslesmesiz'] ?? 0) > 0) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'ESLESMESIZ_TALEP',
                'title' => $data['eslesmesiz'] . ' talep eşleştirme bekliyor',
                'description' => 'AI eşleştirme ile toplu eşleştirme yapabilirsiniz.',
                'priority' => 1,
                'icon' => 'link',
                'action' => ['label' => 'Toplu Eşleştir', 'url' => route('admin.eslesmeler.index')],
            ];
        }

        return $rules;
    }

    protected function propertyHubRules(array $data): array
    {
        $rules = [];

        $featureCount = $data['aktif_feature'] ?? $data['feature_count'] ?? 0;
        if ($featureCount === 0) {
            $rules[] = [
                'tip' => 'critical',
                'code' => 'OZELLIK_YOK',
                'title' => 'Özellik havuzu boş',
                'description' => 'İlan şablonları çalışması için özelliklerin tanımlı olması gerekir.',
                'priority' => 1,
                'icon' => 'exclamation',
                'action' => ['label' => 'Özellik Ekle', 'url' => route('admin.property-hub.features.create')],
            ];
        }

        if (($data['assignment_count'] ?? 0) === 0 && $featureCount > 0) {
            $rules[] = [
                'tip' => 'warning',
                'code' => 'ATAMA_YOK',
                'title' => 'Şablonlara özellik atanmamış',
                'description' => 'Özellikler var ama şablonlara atanmamış — wizard doğru çalışmaz.',
                'priority' => 1,
                'icon' => 'link',
                'action' => ['label' => 'Şablonlar', 'url' => route('admin.property-hub.templates.index')],
            ];
        }

        return $rules;
    }

    protected function editAction(?int $ilanId): ?array
    {
        if (!$ilanId) {
            return null;
        }

        return ['label' => 'Düzenle', 'url' => route('admin.ilanlar.edit', $ilanId)];
    }
}

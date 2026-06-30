<?php

/**
 * Menu Registry - Merkezi Menu Yönetim Sistemi
 *
 * Context7 Standard: C7-MENU-REGISTRY-2025-12-06
 *
 * Tüm menu item'ları merkezi config'de tanımlanır.
 * Permission-based menu filtering ve dinamik menu gösterimi.
 *
 * @version 1.1.0
 * @since 2025-12-06
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Sidebar Menu — 5-Layer Product Architecture
    |--------------------------------------------------------------------------
    | L1: Business — day-to-day operational product use
    | L2: Property Engine — schema, features, templates, rules
    | L3: Intelligence — Cortex (AI brain) + Governance (SAB decisions)
    | L4: Automation — execution, integrations, external channels
    | L5: System — infra, technical admin, settings
    |--------------------------------------------------------------------------
    | Refactored: 2026-04-03 (SAB: Production Sidebar Architecture v2.0)
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'sidebar' => [

            // ═══════════════════════════════════════════
            // L1: BUSINESS — Günlük Operasyonel Kullanım
            // ═══════════════════════════════════════════

            // 1️⃣ DASHBOARD
            [
                'id' => 'dashboard',
                'type' => 'link',
                'name' => 'Dashboard',
                'route' => 'admin.dashboard.index',
                'icon' => 'dashboard',
                'permission' => 'view-admin-panel',
                'display_order' => 1,
            ],

            // 2️⃣ İLANLAR & PORTFÖY
            [
                'id' => 'ilan-portfoy',
                'type' => 'group',
                'name' => 'İlanlar & Portföy',
                'icon' => 'listing',
                'permission' => 'manage-ilanlar',
                'display_order' => 2,
                'children' => [
                    [
                        'id' => 'my-listings',
                        'type' => 'link',
                        'name' => 'İlanlarım',
                        'route' => 'admin.ilanlarim.index',
                        'active' => 'admin.ilanlarim.*',
                        'icon' => 'home',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'ilanlar',
                        'type' => 'link',
                        'name' => 'Tüm İlanlar',
                        'route' => 'admin.ilanlar.index',
                        'icon' => 'list',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 2,
                    ],
                    [
                        'id' => 'ilanlar-create',
                        'type' => 'link',
                        'name' => 'Yeni İlan',
                        'route' => 'admin.ilanlar.create',
                        'icon' => 'plus',
                        'permission' => 'manage-ilanlar',
                        'badge' => 'AI',
                        'display_order' => 3,
                    ],
                    [
                        'id' => 'danisman',
                        'type' => 'link',
                        'name' => 'Danışmanlar',
                        'route' => 'admin.danisman.index',
                        'icon' => 'advisor',
                        'permission' => 'view-admin-panel',
                        'display_order' => 4,
                    ],
                ],
            ],

            // 3️⃣ CRM & MÜŞTERİ
            [
                'id' => 'crm-musteri',
                'type' => 'group',
                'name' => 'CRM & Müşteri',
                'icon' => 'crm',
                'permission' => 'view-admin-panel',
                'display_order' => 3,
                'children' => [
                    [
                        'id' => 'crm-dashboard',
                        'type' => 'link',
                        'name' => 'CRM Dashboard',
                        'route' => 'admin.crm.dashboard',
                        'icon' => 'dashboard',
                        'permission' => 'view-admin-panel',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'kisiler',
                        'type' => 'link',
                        'name' => 'Kişiler',
                        'route' => 'admin.kisiler.index',
                        'icon' => 'users',
                        'permission' => 'view-admin-panel',
                        'display_order' => 2,
                    ],
                    [
                        'id' => 'kisilerim',
                        'type' => 'link',
                        'name' => 'Kişilerim',
                        'route' => 'admin.kisilerim.index',
                        'icon' => 'users',
                        'permission' => 'view-admin-panel',
                        'display_order' => 3,
                    ],
                    [
                        'id' => 'talepler',
                        'type' => 'link',
                        'name' => 'Talepler',
                        'route' => 'admin.talepler.index',
                        'icon' => 'list',
                        'permission' => 'view-admin-panel',
                        'display_order' => 4,
                    ],
                    [
                        'id' => 'eslesmeler',
                        'type' => 'link',
                        'name' => 'Eşleştirmeler',
                        'route' => 'admin.eslesmeler.index',
                        'icon' => 'link',
                        'permission' => 'view-admin-panel',
                        'badge' => 'AI',
                        'display_order' => 5,
                    ],
                ],
            ],

            // 4️⃣ TAKIM & OPERASYON
            [
                'id' => 'takim-yonetimi',
                'type' => 'group',
                'name' => 'Takım & Operasyon',
                'icon' => 'users',
                'permission' => 'view-admin-panel',
                'display_order' => 4,
                'children' => [
                    [
                        'id' => 'takimlar',
                        'type' => 'link',
                        'name' => 'Takımlar',
                        'route' => 'admin.takim.takimlar.index',
                        'icon' => 'users',
                        'permission' => 'view-admin-panel',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'gorevler',
                        'type' => 'link',
                        'name' => 'Görevler',
                        'route' => 'admin.takim.gorevler.index',
                        'icon' => 'list',
                        'permission' => 'view-admin-panel',
                        'display_order' => 2,
                    ],
                    [
                        'id' => 'projeler',
                        'type' => 'link',
                        'name' => 'Projeler',
                        'route' => 'admin.takim.projeler.index',
                        'icon' => 'briefcase',
                        'permission' => 'view-admin-panel',
                        'display_order' => 3,
                    ],
                    [
                        'id' => 'kanban-board',
                        'type' => 'link',
                        'name' => 'Kanban Board',
                        'route' => 'admin.takim.board',
                        'icon' => 'dashboard',
                        'permission' => 'view-admin-panel',
                        'display_order' => 4,
                    ],
                ],
            ],

            // 5️⃣ FİNANS & SATIŞ
            [
                'id' => 'finans-satis',
                'type' => 'group',
                'name' => 'Finans & Satış',
                'icon' => 'finance',
                'permission' => 'view-admin-panel',
                'display_order' => 5,
                'children' => [
                    [
                        'id' => 'finans-islemler',
                        'type' => 'link',
                        'name' => 'Finansal İşlemler',
                        'route' => 'admin.finans.islemler.index',
                        'icon' => 'dollar',
                        'permission' => 'view-admin-panel',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'satislar',
                        'type' => 'link',
                        'name' => 'Satışlar',
                        'route' => 'admin.satislar.create',
                        'icon' => 'dollar',
                        'permission' => 'view-admin-panel',
                        'badge' => '💰',
                        'display_order' => 2,
                    ],
                ],
            ],

            // 6️⃣ BİLDİRİMLER
            [
                'id' => 'bildirimler',
                'type' => 'link',
                'name' => 'Bildirimler',
                'route' => 'admin.notifications.index',
                'icon' => 'notifications',
                'permission' => 'view-admin-panel',
                'display_order' => 6,
            ],

            // ═══════════════════════════════════════════
            // L2: PROPERTY ENGINE — Şema, Özellik, Şablon
            // ═══════════════════════════════════════════

            // 7️⃣ PROPERTY ENGINE
            [
                'id' => 'property-engine',
                'type' => 'group',
                'name' => 'Property Engine',
                'icon' => 'settings',
                'permission' => 'manage-ilanlar',
                'display_order' => 7,
                'children' => [
                    [
                        'id' => 'property-hub-dashboard',
                        'type' => 'link',
                        'name' => 'Dashboard',
                        'route' => 'admin.property-hub.index',
                        'icon' => 'dashboard',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'features',
                        'type' => 'link',
                        'name' => 'Özellik Havuzu',
                        'route' => 'admin.property-hub.features.index',
                        'icon' => 'tag',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 2,
                    ],
                    [
                        'id' => 'templates',
                        'type' => 'link',
                        'name' => 'Şablonlar',
                        'route' => 'admin.property-hub.templates.index',
                        'icon' => 'template',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 3,
                    ],
                    [
                        'id' => 'packs',
                        'type' => 'link',
                        'name' => 'Özellik Paketleri',
                        'route' => 'admin.property-hub.packs.index',
                        'icon' => 'list',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 4,
                    ],
                    [
                        'id' => 'ozellik-kategorileri',
                        'type' => 'link',
                        'name' => 'Özellik Kategorileri',
                        'route' => 'admin.ozellikler.kategoriler.index',
                        'icon' => 'list',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 5,
                    ],
                    [
                        'id' => 'property-types',
                        'type' => 'link',
                        'name' => 'Kategori Matrisi',
                        'route' => 'admin.property_types.index',
                        'icon' => 'briefcase',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 6,
                    ],
                    [
                        'id' => 'dependency-rules',
                        'type' => 'link',
                        'name' => 'Bağımlılık Kuralları',
                        'route' => 'admin.property-hub.dependency-rules.index',
                        'icon' => 'link',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 7,
                    ],
                    [
                        'id' => 'tkgm-parsel',
                        'type' => 'link',
                        'name' => 'TKGM Parsel',
                        'route' => 'admin.tkgm-parsel.index',
                        'icon' => 'map',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 8,
                    ],
                ],
            ],

            // ═══════════════════════════════════════════
            // L3: INTELLIGENCE — Cortex + Governance
            // ═══════════════════════════════════════════

            // 8️⃣ CORTEX — AI beyin katmanı
            [
                'id' => 'cortex',
                'type' => 'group',
                'name' => 'Cortex',
                'icon' => 'ai',
                'permission' => 'view-admin-panel',
                'display_order' => 8,
                'badge' => 'AI',
                'children' => [
                    [
                        'id' => 'ai-dashboard',
                        'type' => 'link',
                        'name' => 'AI Dashboard',
                        'route' => 'admin.ai.dashboard',
                        'icon' => 'dashboard',
                        'permission' => 'view-admin-panel',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'cortex-analytics',
                        'type' => 'link',
                        'name' => 'Cortex Analytics',
                        'route' => 'admin.cortex',
                        'icon' => 'finance',
                        'permission' => 'view-admin-panel',
                        'display_order' => 2,
                    ],
                    [
                        'id' => 'cortex-monitoring',
                        'type' => 'link',
                        'name' => 'Cortex Monitoring',
                        'route' => 'admin.ai-monitor.index',
                        'icon' => 'monitor',
                        'permission' => 'view-admin-panel',
                        'display_order' => 3,
                    ],
                    [
                        'id' => 'field-suggestions',
                        'type' => 'link',
                        'name' => 'AI Alan Önerileri',
                        'route' => 'admin.property-hub.field-suggestions.index',
                        'icon' => 'lightbulb',
                        'permission' => 'manage-ilanlar',
                        'display_order' => 4,
                    ],
                    [
                        'id' => 'ai-usage',
                        'type' => 'link',
                        'name' => 'Kullanım & Maliyet',
                        'route' => 'admin.ai.statistics',
                        'icon' => 'dollar',
                        'permission' => 'view-admin-panel',
                        'display_order' => 5,
                    ],
                    [
                        'id' => 'istatistikler',
                        'type' => 'link',
                        'name' => 'İstatistikler',
                        'route' => 'admin.analitik.istatistikler.index',
                        'icon' => 'list',
                        'permission' => 'view-admin-panel',
                        'display_order' => 6,
                    ],
                    [
                        'id' => 'raporlar',
                        'type' => 'link',
                        'name' => 'Tüm Raporlar',
                        'route' => 'admin.reports.index',
                        'icon' => 'reports',
                        'permission' => 'view-admin-panel',
                        'display_order' => 7,
                    ],
                    [
                        'id' => 'portfolio-doctor',
                        'type' => 'link',
                        'name' => 'Portfolio Doctor',
                        'route' => 'advisor.portfolio-doctor',
                        'icon' => 'health',
                        'permission' => 'view-admin-panel',
                        'badge' => 'AI',
                        'display_order' => 8,
                    ],
                ],
            ],

            // 9️⃣ GOVERNANCE — SAB karar motoru + denetim
            [
                'id' => 'governance',
                'type' => 'group',
                'name' => 'Governance',
                'icon' => 'briefcase',
                'permission' => 'view-admin-panel',
                'display_order' => 9,
                'badge' => 'SAB',
                'children' => [
                    [
                        'id' => 'governance-telemetry',
                        'type' => 'link',
                        'name' => 'Telemetri İzleme',
                        'route' => 'admin.governance.telemetry',
                        'icon' => 'monitor',
                        'permission' => 'view-admin-panel',
                        'badge' => 'LIVE',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'intelligence-center',
                        'type' => 'link',
                        'name' => 'AI Kontrol Merkezi',
                        'route' => 'admin.governance.intelligence-center',
                        'icon' => 'monitor',
                        'permission' => 'view-admin-panel',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'review-queue',
                        'type' => 'link',
                        'name' => 'Karar Kuyruğu',
                        'route' => 'admin.governance.review-queue',
                        'icon' => 'dashboard',
                        'permission' => 'view-admin-panel',
                        'display_order' => 2,
                    ],
                    [
                        'id' => 'sab-governance',
                        'type' => 'link',
                        'name' => 'Governance Dashboard',
                        'route' => 'admin.governance.dashboard',
                        'icon' => 'dashboard',
                        'permission' => 'view-admin-panel',
                        'display_order' => 3,
                    ],
                    [
                        'id' => 'feature-health',
                        'type' => 'link',
                        'name' => 'Özellik Sağlık Matrisi',
                        'route' => 'admin.governance.feature-health',
                        'icon' => 'health',
                        'permission' => 'view-admin-panel',
                        'display_order' => 4,
                    ],
                    [
                        'id' => 'ai-governance',
                        'type' => 'link',
                        'name' => 'AI Governance',
                        'route' => 'admin.analytics.ai-governance',
                        'icon' => 'monitor',
                        'permission' => 'view-admin-panel',
                        'display_order' => 5,
                    ],
                    [
                        'id' => 'audit-log',
                        'type' => 'link',
                        'name' => 'Denetim Kayıtları',
                        'route' => 'admin.ups.audit-log',
                        'icon' => 'history',
                        'permission' => 'view-admin-panel',
                        'display_order' => 6,
                    ],
                    [
                        'id' => 'autonomy-panel',
                        'type' => 'link',
                        'name' => 'Otonom Kontrol',
                        'route' => 'admin.governance.autonomy-panel',
                        'icon' => 'monitor',
                        'permission' => 'view-admin-panel',
                        'display_order' => 7,
                    ],
                    [
                        'id' => 'action-dashboard',
                        'type' => 'link',
                        'name' => 'Aksiyon Döngüsü',
                        'route' => 'admin.governance.action-dashboard',
                        'icon' => 'monitor',
                        'permission' => 'view-admin-panel',
                        'display_order' => 8,
                    ],
                    [
                        'id' => 'yalihan-bekci',
                        'type' => 'link',
                        'name' => 'Yalıhan Bekçi',
                        'route' => 'admin.yalihan-bekci.index',
                        'icon' => 'health',
                        'permission' => 'view-admin-panel',
                        'display_order' => 9,
                    ],
                ],
            ],

            // ═══════════════════════════════════════════
            // L4: AUTOMATION — Çalıştırma ve dış kanallar
            // ═══════════════════════════════════════════

            // 🔟 AUTOMATION HUB
            [
                'id' => 'automation-hub',
                'type' => 'group',
                'name' => 'Automation Hub',
                'icon' => 'link',
                'permission' => 'view-admin-panel',
                'display_order' => 10,
                'children' => [
                    [
                        'id' => 'telegram',
                        'type' => 'link',
                        'name' => 'Telegram Bot',
                        'route' => 'admin.telegram-bot.index',
                        'icon' => 'ai',
                        'permission' => 'view-admin-panel',
                        'badge' => '🤖',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'n8n-workflows',
                        'type' => 'link',
                        'name' => 'n8n Workflows',
                        'route' => 'admin.integrations.n8n-workflows',
                        'icon' => 'link',
                        'permission' => 'view-admin-panel',
                        'display_order' => 2,
                    ],
                    [
                        'id' => 'integrations',
                        'type' => 'link',
                        'name' => 'Entegrasyonlar',
                        'route' => 'admin.integrations.index',
                        'icon' => 'link',
                        'permission' => 'view-admin-panel',
                        'display_order' => 3,
                    ],
                    [
                        'id' => 'voice-search',
                        'type' => 'link',
                        'name' => 'Sesli Arama',
                        'route' => 'admin.voice-search.settings',
                        'icon' => 'ai',
                        'permission' => 'view-admin-panel',
                        'display_order' => 4,
                    ],
                ],
            ],

            // ═══════════════════════════════════════════
            // L5: SYSTEM — Altyapı ve teknik yönetim
            // ═══════════════════════════════════════════

            // 1️⃣1️⃣ SİSTEM
            [
                'id' => 'sistem',
                'type' => 'group',
                'name' => 'Sistem',
                'icon' => 'monitor',
                'permission' => 'view-admin-panel',
                'display_order' => 11,
                'children' => [
                    [
                        'id' => 'sistem-sagligi',
                        'type' => 'link',
                        'name' => 'Sistem Sağlığı',
                        'route' => 'admin.ups.health',
                        'icon' => 'health',
                        'permission' => 'view-admin-panel',
                        'display_order' => 1,
                    ],
                    [
                        'id' => 'telescope',
                        'type' => 'link',
                        'name' => 'Telescope',
                        'url' => '/telescope',
                        'icon' => 'monitor',
                        'permission' => 'view-admin-panel',
                        'display_order' => 2,
                    ],
                    [
                        'id' => 'horizon',
                        'type' => 'link',
                        'name' => 'Horizon',
                        'url' => '/horizon',
                        'icon' => 'list',
                        'permission' => 'view-admin-panel',
                        'display_order' => 3,
                    ],
                    [
                        'id' => 'kullanicilar',
                        'type' => 'link',
                        'name' => 'Kullanıcılar',
                        'route' => 'admin.kullanicilar.index',
                        'icon' => 'users',
                        'permission' => 'manage-users',
                        'display_order' => 4,
                    ],
                    [
                        'id' => 'ayarlar',
                        'type' => 'link',
                        'name' => 'Genel Ayarlar',
                        'route' => 'admin.ayarlar.index',
                        'icon' => 'settings',
                        'permission' => 'manage-settings',
                        'display_order' => 5,
                    ],
                    [
                        'id' => 'ai-settings',
                        'type' => 'link',
                        'name' => 'AI Ayarları',
                        'route' => 'admin.ai-settings.index',
                        'icon' => 'ai',
                        'permission' => 'view-admin-panel',
                        'display_order' => 6,
                    ],
                    [
                        'id' => 'adres-yonetimi',
                        'type' => 'link',
                        'name' => 'Adres Yönetimi',
                        'url' => '/admin/address-management',
                        'icon' => 'home',
                        'permission' => 'view-admin-panel',
                        'display_order' => 7,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon Mapping
    |--------------------------------------------------------------------------
    |
    | Icon isimlerini SVG path'lerine map eder
    |
    */

    'icons' => [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" /><rect x="14" y="3" width="7" height="5" /><rect x="14" y="12" width="7" height="9" /><rect x="3" y="16" width="7" height="5" />',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" />',
        'advisor' => '<circle cx="8.5" cy="7" r="4" /><polyline points="17,11 19,13 23,9" /><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />',
        'listing' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14,2 14,8 20,8" />',
        'list' => '<line x1="8" y1="6" x2="21" y2="6" /><line x1="8" y1="12" x2="21" y2="12" /><line x1="8" y1="18" x2="21" y2="18" />',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />',
        'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />',
        'crm' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2" />',
        'dollar' => '<line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />',
        'finance' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'ai' => '<path d="M12 2L2 7l10 5 10-5-10-5z" />',
        'settings' => '<circle cx="12" cy="12" r="3" /><path d="M12 1v6m0 6v6" />',
        'monitor' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2" />',
        'reports' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />',
        'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
        // ⭐ UPS Template Sistemi Icons
        'template' => '<rect x="3" y="3" width="18" height="18" rx="2" /><line x1="9" y1="9" x2="15" y2="9" /><line x1="9" y1="15" x2="15" y2="15" />',
        'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" />',
        'health' => '<circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" />',
        // ⚡ Template Advanced Icons
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="7 10 12 15 17 10" /><line x1="12" y1="15" x2="12" y2="3" />',
        'history' => '<circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" /><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />',
        'notifications' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" />',
        'lightbulb' => '<path d="M9 18h6" /><path d="M10 22h4" /><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z" />',
    ],
];

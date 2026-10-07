# YALIHAN OS — SITE ARCHITECTURE

**LAST_VERIFIED_HEAD:** 68f6cbba
**Evidence Level:** REPO_VERIFIED (Sidebar Analysis)
**Date:** 2026-10-07

---

## ADMIN SIDEBAR MENU STRUCTURE

```
┌─────────────────────────────────────────────────────────────────┐
│ DASHBOARD                                                        │
├─────────────────────────────────────────────────────────────────┤
│ KULLANICILAR                                                     │
├─────────────────────────────────────────────────────────────────┤
│ ILAN İŞLEMLERİ ▼                                                │
│   ├── Tüm İlanlar          → admin.ilanlar.index              │
│   ├── Yeni İlan (AI)       → admin.ilanlar.create-wizard      │
│   └── İlan Özellikleri     → admin.ups.features.index         │
├─────────────────────────────────────────────────────────────────┤
│ SİSTEM YÖNETİMİ ▼                                               │
│   ├── Kategoriler           → admin.ilan-kategorileri.index    │
│   ├── Yayın Tipleri         → admin.property_types.index       │
│   └── Özellikler            → admin.ups.features.index         │
├─────────────────────────────────────────────────────────────────┤
│ UPS GELİŞMİŞ ▼                                                  │
│   ├── LifeCycle & Governance → admin.governance.feature-health  │
│   ├── Template Manager       → admin.property-hub.yayin-tipi...  │
│   ├── Feature Packs         → admin.ups.packs.index            │
│   ├── Audit Log             → admin.ups.audit-log             │
│   └── System Health         → admin.ups.health                 │
├─────────────────────────────────────────────────────────────────┤
│ DANIŞMANLAR                                                      │
├─────────────────────────────────────────────────────────────────┤
│ CRM YÖNETİMİ ▼                                                   │
│   ├── CRM Dashboard         → admin.crm.dashboard              │
│   ├── Kişiler               → admin.kisiler.index             │
│   ├── Talepler               → admin.talepler.index            │
│   ├── Eşleştirmeler         → admin.eslesmeler.index          │
│   └── Talep-Portföy          → admin.talep-portfolyo.index    │
├─────────────────────────────────────────────────────────────────┤
│ FİNANS YÖNETİMİ ▼                                               │
│   ├── Finansal İşlemler     → admin.finans.islemler.index     │
│   ├── Yeni İşlem            → admin.finans.islemler.create    │
│   ├── Komisyonlar           → admin.finans.komisyonlar.index  │
│   └── Yeni Komisyon         → admin.finans.komisyonlar.create │
├─────────────────────────────────────────────────────────────────┤
│ YAZLIK KİRALAMA ▼                                               │
│   ├── Yazlık İlanları       → admin.yazlik-kiralama.index     │
│   ├── Takvim & Sezonlar     → admin.yazlik-kiralama.takvim... │
│   └── Rezervasyonlar        → admin.yazlik-kiralama.bookings  │
├─────────────────────────────────────────────────────────────────┤
│ RAPORLAR                                                         │
│ BİLDİRİMLER                                                      │
├─────────────────────────────────────────────────────────────────┤
│ AI SİSTEMİ ▼                                                     │
│   ├── AI Command Center     → admin.ai.dashboard              │
│   ├── AI Ayarları           → admin.ai-settings.index         │
│   ├── AI Analytics          → admin.ai-settings.analytics     │
│   └── AI Monitoring         → admin.ai-monitor.index           │
├─────────────────────────────────────────────────────────────────┤
│ TAKIM YÖNETİMİ ▼                                                 │
│   ├── Takım Üyeleri         → admin.takim-yonetimi.index      │
│   ├── Görevler              → admin.takim.gorevler.index     │
│   └── Performans            → admin.takim.performans          │
├─────────────────────────────────────────────────────────────────┤
│ ANALYTICS ▼                                                      │
│   ├── Genel Analytics       → admin.analytics.index            │
│   └── Raporlar              → admin.reports.index            │
├─────────────────────────────────────────────────────────────────┤
│ GOVERNANCE ▼                                                     │
│   ├── SAB Dashboard         → admin.governance.dashboard       │
│   ├── İnceleme Kuyruğu      → admin.governance.review-queue   │
│   ├── AI Kontrol Merkezi    → admin.governance.intelligence... │
│   ├── Otonom Kontrol        → admin.governance.autonomy-panel │
│   ├── Karar Geçmişi         → admin.governance.decision-hist.. │
│   ├── Feature Health        → admin.governance.feature-health  │
│   └── Bastırma Kuralları    → admin.governance.suppression... │
├─────────────────────────────────────────────────────────────────┤
│ AI OTO MASYON ▼                                                 │
│   ├── n8n Workflows         → admin.integrations.n8n-workflows │
│   ├── Telegram Bot           → admin.telegram-bot.index        │
│   ├── Voice Search          → admin.voice-search.settings      │
│   ├── Bildirim Sistemi      → admin.notifications.settings     │
│   └── Entegrasyon Ayarları  → admin.integrations.index         │
├─────────────────────────────────────────────────────────────────┤
│ BLOG YÖNETİMİ ▼                                                 │
│   ├── Yazılar               → admin.blog.posts.index          │
│   ├── Kategoriler           → admin.blog.categories.index      │
│   └── Yorumlar              → admin.blog.comments.index       │
├─────────────────────────────────────────────────────────────────┤
│ ADRES YÖNETİMİ ▼                                                │
│   ├── Adres Yönetimi        → admin.adres-yonetimi.index      │
│   └── Wikimapia Arama       → admin.wikimapia-search.index    │
├─────────────────────────────────────────────────────────────────┤
│ İLANLARIM ✦                                                      │
│ HARİTA                                                            │
├─────────────────────────────────────────────────────────────────┤
│ PAZAR İSTİHBARATI ▼                                              │
│   ├── Dashboard             → admin.market-intelligence.dash... │
│   ├── Bölge Ayarları       → admin.market-intelligence.set... │
│   ├── Fiyat Karşılaştırma  → admin.market-intelligence.comp.. │
│   └── Piyasa Trendleri     → admin.market-intelligence.tre... │
├─────────────────────────────────────────────────────────────────┤
│ SYSTEM TOOLS ▼                                                   │
│   ├── Horizon (Queue)       → /horizon                        │
│   ├── Telescope (Debug)     → /telescope                      │
│   └── Sentry (Errors)       → sentry.io                       │
├─────────────────────────────────────────────────────────────────┤
│ AYARLAR                                                          │
└─────────────────────────────────────────────────────────────────┘
```

---

## MENU → CONTROLLER MAPPING

### İLANLAR & PORTFÖY

| Menu Item | Route Name | Controller | Model | Domain |
|-----------|-----------|------------|-------|--------|
| Tüm İlanlar | admin.ilanlar.index | IlanCrudController@index | Ilan | ILAN |
| Yeni İlan | admin.ilanlar.create-wizard | IlanCrudController@createWizard | Ilan | ILAN |
| İlan Özellikleri | admin.ups.features.index | FeatureController | Ozellik | ILAN |
| İlanlarım | admin.ilanlarim.index | IlanlarimController | Ilan | ILAN |

**Workflow:**
```
createWizard (5 step)
  → Step 1: Kategori seçimi
  → Step 2: Özellikler
  → Step 3: Medya (Fotoğraf/Video)
  → Step 4: Adres/Konum
  → Step 5: Önizleme → TASLAK veya YAYINDA
```

---

### CRM & MÜŞTERİ

| Menu Item | Route Name | Controller | Model | Domain |
|-----------|-----------|------------|-------|--------|
| CRM Dashboard | admin.crm.dashboard | CrmDashboardController | - | CRM |
| Kişiler | admin.kisiler.index | KisiController | Kisi | CRM |
| Talepler | admin.talepler.index | TalepController | Talep | CRM |
| Eşleştirmeler | admin.eslesmeler.index | EslesmeController | - | CRM |
| Talep-Portföy | admin.talep-portfolyo.index | TalepPortfolyoController | Talep | CRM |

**Workflow:**
```
Kisi (CRM süreci)
  → Talep oluşturma
  → Matching/Eşleştirme
  → Görüşme (KisiEtkilesim)
  → Gorev atama
```

---

### DANIŞMANLAR

| Menu Item | Route Name | Controller | Model | Domain |
|-----------|-----------|------------|-------|--------|
| Danışmanlar | admin.danisman.index | DanismanController | User | DANISMAN |

**User Roles:** SUPERADMIN, DANISMAN, EDITOR

---

### FİNANS YÖNETİMİ

| Menu Item | Route Name | Controller | Model | Domain |
|-----------|-----------|------------|-------|--------|
| Finansal İşlemler | admin.finans.islemler.index | FinansController | FinansalIslem | FINANCE |
| Yeni İşlem | admin.finans.islemler.create | FinansController@create | FinansalIslem | FINANCE |
| Komisyonlar | admin.finans.komisyonlar.index | KomisyonController | FinansalIslem | FINANCE |

**⚠️ ISSUE:** FinansalIslem lacks tenant_id

---

### YAZLIK KİRALAMA

| Menu Item | Route Name | Controller | Model | Domain |
|-----------|-----------|------------|-------|--------|
| Yazlık İlanları | admin.yazlik-kiralama.index | YazlikIlanController | Ilan | RESERVATION |
| Takvim & Sezonlar | admin.yazlik-kiralama.takvim.index | YazlikTakvimController | - | RESERVATION |
| Rezervasyonlar | admin.yazlik-kiralama.bookings | BookingController | property_reservations | RESERVATION |

---

### AI SİSTEMİ

| Menu Item | Route Name | Controller | Service | Domain |
|-----------|-----------|------------|---------|--------|
| AI Command Center | admin.ai.dashboard | AIController | AIOrchestrator | AI |
| AI Ayarları | admin.ai-settings.index | AISettingsController | AIProviderManager | AI |
| AI Analytics | admin.ai-settings.analytics | AIAnalyticsController | AICostService | AI |
| AI Monitoring | admin.ai-monitor.index | AIMonitorController | AiLog | AI |

---

### DASHBOARD & ANALYTICS

| Menu Item | Route Name | Controller | Domain |
|-----------|-----------|------------|--------|
| Dashboard | admin.dashboard.index | DashboardController | - |
| Analytics | admin.analytics.index | AnalyticsController | ANALYTICS |
| Raporlar | admin.reports.index | ReportsController | ANALYTICS |

---

### GOVERNANCE

| Menu Item | Route Name | Controller | Domain |
|-----------|-----------|------------|--------|
| SAB Dashboard | admin.governance.dashboard | GovernanceDashboardController | GOVERNANCE |
| İnceleme Kuyruğu | admin.governance.review-queue | ReviewQueueController | GOVERNANCE |
| AI Kontrol Merkezi | admin.governance.intelligence-center | IntelligenceCenterController | GOVERNANCE |
| Otonom Kontrol | admin.governance.autonomy-panel | AutonomyPanelController | GOVERNANCE |
| Karar Geçmişi | admin.governance.decision-history | DecisionHistoryController | GOVERNANCE |
| Feature Health | admin.governance.feature-health | FeatureHealthController | GOVERNANCE |
| Bastırma Kuralları | admin.governance.suppression-list | SuppressionListController | GOVERNANCE |

---

### TAKIM YÖNETİMİ

| Menu Item | Route Name | Controller | Model | Domain |
|-----------|-----------|------------|-------|--------|
| Takım Üyeleri | admin.takim-yonetimi.index | TakimController | User | TEAM |
| Görevler | admin.takim.gorevler.index | GorevController | Gorev | TEAM |
| Performans | admin.takim.performans | PerformansController | - | TEAM |

---

### AI OTOMASYON

| Menu Item | Route Name | Controller | Domain |
|-----------|-----------|------------|--------|
| n8n Workflows | admin.integrations.n8n-workflows | N8nWorkflowController | INTEGRATION |
| Telegram Bot | admin.telegram-bot.index | TelegramBotController | INTEGRATION |
| Voice Search | admin.voice-search.settings | VoiceSearchController | INTEGRATION |
| Bildirim Sistemi | admin.notifications.settings | NotificationSettingsController | INTEGRATION |
| Entegrasyon Ayarları | admin.integrations.index | IntegrationController | INTEGRATION |

---

### PAZAR İSTİHBARATI

| Menu Item | Route Name | Controller | Domain |
|-----------|-----------|------------|--------|
| Dashboard | admin.market-intelligence.dashboard | MarketIntelDashboardController | MARKET_INTEL |
| Bölge Ayarları | admin.market-intelligence.settings | MarketIntelSettingsController | MARKET_INTEL |
| Fiyat Karşılaştırma | admin.market-intelligence.compare | PriceCompareController | MARKET_INTEL |
| Piyasa Trendleri | admin.market-intelligence.trends | TrendsController | MARKET_INTEL |

---

### SİSTEM ARAÇLARI

| Menu Item | URL | Purpose |
|-----------|-----|---------|
| Horizon (Queue) | /horizon | Queue monitoring |
| Telescope (Debug) | /telescope | Request debugging |
| Sentry (Errors) | sentry.io | Error tracking |

---

## MENU CATEGORIES SUMMARY

| Category | Items | Main Domains |
|----------|-------|--------------|
| **İlan Yönetimi** | 4 | ILAN |
| **CRM & Müşteri** | 5 | CRM |
| **Finans** | 4 | FINANCE |
| **Yazlık Kiralama** | 3 | RESERVATION |
| **AI Sistemi** | 4 | AI |
| **Governance** | 7 | GOVERNANCE |
| **AI Otomasyon** | 5 | INTEGRATION |
| **Takım Yönetimi** | 3 | TEAM |
| **Pazar İstihbaratı** | 4 | MARKET_INTEL |
| **Sistem** | 8 | VARIOUS |
| **Blog** | 3 | BLOG |

**Total Menu Items:** ~60+

---

## PAGE HIERARCHY

```
/admin
├── dashboard                    → Dashboard
├── kullaniciar                  → UserController
├── ilanlarim                    → IlanlarimController
├── ilanlar                      → IlanCrudController
│   ├── /index                  → Liste
│   ├── /create-wizard          → Yeni (5-step)
│   ├── /{id}/edit             → Düzenle
│   └── /{id}                   → Detay
├── crm
│   ├── dashboard               → CrmDashboardController
│   ├── kisiler                 → KisiController
│   ├── talepler                → TalepController
│   ├── eslesmeler              → EslesmeController
│   └── talep-portfolyo         → TalepPortfolyoController
├── finans
│   ├── islemler               → FinansController
│   └── komisyonlar             → KomisyonController
├── yazlik-kiralama
│   ├── index                   → YazlikIlanController
│   ├── takvim                  → TakvimController
│   └── bookings                → BookingController
├── ai
│   ├── dashboard               → AIController
│   ├── monitor                 → AIMonitorController
│   └── ...
├── ai-settings                 → AISettingsController
├── takim-yonetimi              → TakimController
├── analytics                   → AnalyticsController
├── reports                     → ReportsController
├── governance
│   ├── dashboard               → SAB Dashboard
│   ├── review-queue            → Review Queue
│   ├── intelligence-center     → AI Control
│   ├── autonomy-panel          → Autonomy
│   ├── decision-history        → Decisions
│   ├── feature-health          → Feature Health
│   └── suppression-list        → Rules
├── integrations
│   ├── n8n-workflows          → n8n
│   ├── telegram-bot            → Telegram
│   └── ...
├── market-intelligence
│   ├── dashboard               → Intel Dashboard
│   ├── compare                 → Compare
│   └── trends                  → Trends
├── blog
│   ├── posts                   → Posts
│   ├── categories              → Categories
│   └── comments                → Comments
├── adres-yonetimi              → AddressController
├── map                         → MapController
├── notifications               → NotificationController
├── ayarlar                     → SettingsController
└── ...
```

---

## LEGACY/DEPRECATED

| Menu | Status | Notes |
|------|--------|-------|
| Telegram Bot (standalone) | Legacy | Merge to AI Otomasyon |
| Yazlık Modülü | Partial | property_reservations kullanılıyor |

---

## UI FRAMEWORK

| Component | Framework | Notes |
|-----------|-----------|-------|
| Admin Layout | Custom Blade | Tailwind CSS |
| Components | Blade + Alpine.js | Interactive dropdowns |
| Icons | Heroicons | SVG inline |
| Charts | Chart.js / Custom | Analytics pages |

---

## EXTERNAL SERVICES LINKED

| Service | Menu | Purpose |
|---------|------|---------|
| Horizon | System Tools | Queue monitoring |
| Telescope | System Tools | Debugging |
| Sentry | System Tools | Error tracking |
| n8n | AI Otomasyon | Workflow automation |
| Telegram | AI Otomasyon | Bot integration |

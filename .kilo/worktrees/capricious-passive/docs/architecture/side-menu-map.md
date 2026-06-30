# Side Menu Map

> STATUS: REFERENCE ONLY — NOT SSOT
> Kaynak: `config/menus.php` (5-Layer Sidebar Architecture v2.0)

---

## L1: BUSINESS — Günlük Operasyonel Kullanım

### 1️⃣ Dashboard
**Purpose:** Sistem giriş noktası — ilan, müşteri, görev özetleri

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| Ana Dashboard | `admin.dashboard.index` | Genel özet paneli |
| Agent Dashboard | `admin.dashboard.agent` | Danışman üretkenlik metrikleri |
| Investor Dashboard | `admin.dashboard.investor` | Yatırımcı CQRS read model görünümü |

---

### 2️⃣ İlanlar & Portföy
**Purpose:** İlan yaşam döngüsü — oluşturma, düzenleme, yayınlama

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| İlanlarım | `admin.ilanlarim.index` | Danışmanın kendi ilanları |
| Tüm İlanlar | `admin.ilanlar.index` | İlan CRUD listesi |
| Yeni İlan | `admin.ilanlar.create` | 3 adımlı Wizard 🏷️ AI |
| Danışmanlar | `admin.danisman.index` | Danışman CRUD + performans |

---

### 3️⃣ CRM & Müşteri
**Purpose:** Müşteri, lead, talep yönetimi — pipeline ve eşleştirme

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| CRM Dashboard | `admin.crm.dashboard` | CRM ana paneli |
| Kişiler | `admin.kisiler.index` | Kişi CRUD + AI analiz |
| Kişilerim | `admin.kisilerim.index` | Danışmanın kendi kişileri |
| Talepler | `admin.talepler.index` | Alıcı talepleri |
| Eşleştirmeler | `admin.eslesmeler.index` | Talep-İlan eşleştirme 🏷️ AI |

---

### 4️⃣ Takım & Operasyon
**Purpose:** Takım iş akışları, görev dağılımı, proje yönetimi

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| Takımlar | `admin.takim.takimlar.index` | Takım CRUD + üye yönetimi |
| Görevler | `admin.takim.gorevler.index` | Görev CRUD + toplu atama |
| Projeler | `admin.takim.projeler.index` | Proje CRUD |
| Kanban Board | `admin.takim.board` | Görev Kanban panosu |

---

### 5️⃣ Finans & Satış
**Purpose:** Finansal işlemler, komisyon, prim yönetimi

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| Finansal İşlemler | `admin.finans.islemler.index` | İşlem CRUD + AI analiz |
| Satışlar | `admin.satislar.create` | Satış istatistikleri |

---

### 6️⃣ Bildirimler
**Purpose:** Bildirim yönetimi — okundu/okunmadı, test

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| Bildirimler | `admin.notifications.index` | Bildirim listesi |

---

## L2: PROPERTY ENGINE — Şema, Özellik, Şablon

### 7️⃣ Property Engine
**Purpose:** İlan özellik şeması — feature tanımları, template atamaları, kategori matrisi

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| Dashboard | `admin.property-hub.index` | Property Hub ana paneli |
| Özellik Havuzu | `admin.property-hub.features.index` | Feature CRUD + toggle/archive |
| Şablonlar | `admin.property-hub.templates.index` | Şablon listesi + AI design |
| Özellik Paketleri | `admin.property-hub.packs.index` | Feature Pack CRUD |
| Özellik Kategorileri | `admin.ozellikler.kategoriler.index` | Semantik özellik grupları |
| Kategori Matrisi | `admin.property_types.index` | Kategori → Yayın Tipi → Feature |
| Bağımlılık Kuralları | `admin.property-hub.dependency-rules.index` | visible_if, required_if kuralları |
| TKGM Parsel | `admin.tkgm-parsel.index` | TKGM parsel sorgulama |

---

## L3: INTELLIGENCE — Cortex + Governance

### 8️⃣ Cortex (AI Beyin Katmanı)
**Purpose:** AI öneri üretimi, analitik, izleme, maliyet takibi

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| AI Dashboard | `admin.ai.dashboard` | AI ana paneli |
| Cortex Analytics | `admin.cortex` | Cortex gelir/analiz |
| Cortex Monitoring | `admin.ai-monitor.index` | AI sistem izleme |
| AI Alan Önerileri | `admin.property-hub.field-suggestions.index` | AI alan önerisi üretme/onaylama |
| Kullanım & Maliyet | `admin.ai.statistics` | AI kullanım istatistikleri |
| İstatistikler | `admin.analitik.istatistikler.index` | Genel/İlan/Satış istatistikleri |
| Tüm Raporlar | `admin.reports.index` | Raporlama paneli |
| Portfolio Doctor | `advisor.portfolio-doctor` | AI portföy sağlık kontrolü 🏷️ AI |

---

### 9️⃣ Governance (SAB Karar Motoru)
**Purpose:** AI kararlarının onaylanması, reddi, rollback, denetim

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| AI Kontrol Merkezi | `admin.governance.intelligence-center` | Multi-agent intelligence center |
| Karar Kuyruğu | `admin.governance.review-queue` | Onay/red bekleyen kararlar |
| Governance Dashboard | `admin.governance.dashboard` | Governance ana paneli |
| Özellik Sağlık Matrisi | `admin.governance.feature-health` | Feature health matrix + AI proposal |
| AI Governance | `admin.analytics.ai-governance` | AI Prompt Governance telemetrisi |
| Denetim Kayıtları | `admin.ups.audit-log` | UPS audit log + export |
| Otonom Kontrol | `admin.governance.autonomy-panel` | Otonom seviye, pause/resume, dry-run |
| Aksiyon Döngüsü | `admin.governance.action-dashboard` | Decision → Action → Feedback loop |
| Yalıhan Bekçi | `admin.yalihan-bekci.index` | Bekçi monitoring + run check |

---

## L4: AUTOMATION — Çalıştırma ve Dış Kanallar

### 🔟 Automation Hub
**Purpose:** Dış kanal entegrasyonları, bot yönetimi, otomasyon

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| Telegram Bot | `admin.telegram-bot.index` | Bot yönetimi, webhook, test 🤖 |
| n8n Workflows | `admin.integrations.n8n-workflows` | n8n workflow yönetimi |
| Entegrasyonlar | `admin.integrations.index` | Entegrasyon CRUD + test |
| Sesli Arama | `admin.voice-search.settings` | Sesli arama ayarları |

---

## L5: SYSTEM — Altyapı ve Teknik Yönetim

### 1️⃣1️⃣ Sistem
**Purpose:** Altyapı, kullanıcı yönetimi, sistem ayarları

| Alt Sayfa | Route | Amaç |
|-----------|-------|------|
| Sistem Sağlığı | `admin.ups.health` | UPS sağlık + repair |
| Telescope | `/telescope` (external) | Laravel Telescope |
| Horizon | `/horizon` (external) | Laravel Horizon |
| Kullanıcılar | `admin.kullanicilar.index` | Kullanıcı CRUD |
| Genel Ayarlar | `admin.ayarlar.index` | Sistem ayarları + dil/para birimi |
| AI Ayarları | `admin.ai-settings.index` | AI provider/model yönetimi |
| Adres Yönetimi | `/admin/address-management` | İl/İlçe/Mahalle canonical SSOT |

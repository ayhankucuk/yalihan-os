# YALIHAN OS — Enterprise 7-Layer Architecture Taxonomy

**Tarih:** 2026-09-12  
**Statü:** CANONICAL / SSOT MENTAL MODEL  
**Otorite:** SAAB v11.1 / Yalıhan Bekçi Governance  

---

## 🏛️ 1. Genel Mimari Ağaç (The 7 Pillars)

```text
YALIHAN OS
│
├── 1. CORE             ← Anayasa + Sistem Kuralları + Güvenlik
│
├── 2. DATA             ← Tek Gerçeklik Kaynağı (SSOT Aggregates & State)
│   ├── Properties      ← Fiziksel Mülkler & İlanlar
│   ├── Guests          ← Misafirler & Mülk Sahipleri
│   ├── Reservations    ← Rezervasyonlar & Takvim
│   ├── CRM             ← Talepler, Müşteriler, Eşleşmeler
│   └── Finance         ← Çift Taraflı Muhasebe Defteri (Append-Only Ledger)
│
├── 3. CAPABILITIES     ← Sistem Ne Yapabilir? (Domain Use Cases & Driven Ports)
│
├── 4. WORKFLOWS        ← İşler Nasıl Yürür? (Hermes Event Bus & Sagas)
│
├── 5. AI               ← Yapay Zeka Modelleri & LLM Soyutlaması (Cortex)
│   ├── Cortex          ← Yerel Model Orkestrasyonu & Puanlama
│   ├── GPT             ← OpenAI Motoru
│   ├── Claude          ← Anthropic Motoru (Vision/Copywriting)
│   └── Diğer Modeller  ← DeepSeek vb.
│
├── 6. INTEGRATIONS     ← Dış Dünya Adaptörleri (Hexagonal Driving/Driven Adapters)
│   ├── Airbnb / Booking / Channex
│   ├── Telegram / WhatsApp
│   ├── Google Places / TurkiyeAPI
│   └── Canva / Harici Medya
│
└── 7. ARCHIVE          ← Karantina / Eski / Geçersiz Kayıtlar
```

---

## 🔍 2. Katman Tanımları ve Sorumluluk Sınırları

### 1. CORE (Çekirdek & Anayasa)
- **Kapsam:** `.sab/authority.json`, `docs/SAB.md`, `TenantScope`, Multi-tenant izolasyonu, Fail-Closed güvenlik politikaları.
- **Kural:** Sistemin diğer katmanları CORE kurallarını çiğneyemez.

### 2. DATA (Tek Gerçeklik Kaynağı — SSOT)
- **Kapsam:** `App\Domain\Property`, `App\Domain\Ilan`, `App\Domain\Kisi`, `App\Domain\Reservation`, `App\Domain\CRM`, `App\Domain\Finance`.
- **Kural:** Veri mutasyonları yalnızca yetkili Use Case ve Repository otoritesi zincirinden geçer.

### 3. CAPABILITIES (Yetenekler & Domain Use Cases)
- **Kapsam:** `FindNearbyPoisUseCase`, `WizardSchemaResolver`, `CreateTalepUseCase`, `LeadScoringService`.
- **Kural:** Saf iş mantığı içerir, dış bağımlılıklara Port (Interface) üzerinden bağlanır.

### 4. WORKFLOWS (İş Akışları & Olay Koordinatörü)
- **Kapsam:** `App\Domain\Hermes`, `app/Listeners/Wizard/`, `ActionCenterService` (SLA Görevleri).
- **Kural:** Asenkron, kuyruklu ve event-driven çalışır; ana işlemi bloklamaz.

### 5. AI (Yapay Zeka Katmanı)
- **Kapsam:** `CortexProviderInterface`, `YalihanCortex`, `OllamaDriver`, `OpenAiDriver`, `DeepSeekDriver`.
- **Kural:** Yapay zeka servisleri domain sözleşmesiyle soyutlanır; model değişimi sıfır domain etkisi yaratır.

### 6. INTEGRATIONS (Entegrasyonlar & Adaptörler)
- **Kapsam:** `App\Infrastructure\Adapters`, Channel Manager OTA Ingest, Telegram, Webhook'lar.
- **Kural:** Dış sistemlerin JSON formatları domain'e sızamaz; adaptör içinde DTO'ya dönüştürülür.

### 7. ARCHIVE (Arşiv & Karantina)
- **Kapsam:** `database/seeders/legacy/`, SoftDeletes, Telemetry geçmişi.
- **Kural:** Emekliye ayrılan kodlar ve kayıtlar canlı akıştan izole edilir.

---

## 📜 3. Bekçi Uyumluluk Mührü
Bu belge, tüm Antigravity ve Kilo oturumlarında **mimari hizalanma referansı (SSOT)** olarak kabul edilmiştir.

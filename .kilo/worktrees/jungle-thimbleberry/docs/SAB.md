# 🧱 STANDART UYGULAMA BLOĞU (SAB — PRODUCTION SEAL)
Version: 24.4.0 (Phase 14: Property Workspace v8.1 — Intent First)

SAB, projenin bağlayıcı teknik anayasasıdır.
Uygulanmadan hiçbir iş "Done" kabul edilmez.
SAB = Mimari Onay + Cognitive Guard + Drift Protection + Monetization Fortress.

---

## 1️⃣ BAĞLAYICI ANAYASA

1. Core (Ledger / CRM Write DB) IMMUTABLE'dir.
2. Core'a doğrudan write yasaktır. Mutation yalnızca Service katmanından yapılır.
3. Observer bypass yasaktır.
4. Silent catch yasaktır (Fail-Fast zorunlu). AST (Bekçi v2.1) ile denetlenir.
5. Raw DB write yasaktır (migration hariç).
6. Projection tabloları yalnızca Read Model içindir.
7. Integration katmanı Advisory Only'dir.
8. Context7 ihlal toleransı = 0.
9. DLQ zorunludur ve replay doğrulanmış olmalıdır.
10. Event işleme idempotent olmak zorundadır.
11. Core üzerinde ağır join/analytics çalıştırılamaz.
12. SAB yalnızca delta ile güncellenir.
13. Governance bypass eden değişiklik merge edilemez.
14. Bilişsel Muhafız (AST) bypass yasaktır.
15. Yaşayan Bellek (Learned Patterns) regresyonu bloklayıcıdır.
16. **[Phase 12] Multi-Tenant Financial Scoping:** Finansal query'lerde `tenant_id` zorunludur.
17. **[Phase 12] AI Circuit Breaker:** Her AI operasyonu `AiBudgetGuard` kontrolüne tabidir.
18. **[Phase 12] Financial Integrity:** Bakiye mutasyonları sadece `recordDoubleEntry` ile yapılabilir.

---

## 2️⃣ DOMAIN & SSOT KURALLARI

- Canonical alan adları authority.json tarafından belirlenir.
- ENUM yasaktır.
- Durum alanları tinyint olmalıdır.
- Migration ↔ Model $fillable %100 uyumlu olmalıdır.
- Ghost field üretilemez.

Dokümantasyonda literal yasaklı kelimeler alias ile anılır:
`[s-word]`, `[a-word]`, `[o-word]`.

---

## 3️⃣ KATMAN İZOLASYONU

Controller:

- İş mantığı içermez.

Service:

- Tüm iş kuralları burada.
- Shortcut yok.

Model:

- $fillable birebir eşleşir.
- Relationship eksiksiz.
- Lazy loading yok.

---

## 4️⃣ TEST SENKRONİZASYONU

Değişiklik varsa:

- Factory güncellendi
- Seeder güncellendi
- Assertion canonical alanla uyumlu
- Skip test yok
- Forbidden alan taraması yapıldı

---

## 5️⃣ OPERASYONEL SAĞLIK

Aşağıdakiler PASS olmalıdır:

```bash
php artisan projection:rebuild
php artisan projection:dlq:replay
php artisan projection:health
php artisan optimize:clear
php artisan sab:integrity-scan
php artisan bekci:run
php artisan test
```

---

## 6️⃣ GATE KRİTERLERİ

- Direct DB write: YOK
- Observer bypass: YOK
- Silent catch: YOK
- Context7: 0
- DLQ: AKTİF
- Worker restart: Idempotent

---

## 7️⃣ REGISTRY ZORUNLULUĞU (ENTERPRISE)

Her teknik değişiklikte:

- `docs/ai-logs` güncellendi
- `docs/registry/ai-decision-index.md` güncellendi
- `docs/registry/phase-history.md` güncellendi
- `docs/registry/architecture-timeline.md` güncellendi
- Governance verify PASS

**Registry güncellenmeden merge yapılamaz.**

---

## 8️⃣ SAB CHECKSUM & DRIFT PROTECTION (ZORUNLU)

SAB dosyası: `docs/SAB.md`
Checksum SSOT: `docs/SAB.sha256` (Regenerated)

Kurallar:

1. `SAB.md` değiştiyse `SAB.sha256` yeniden üretilmelidir.
2. Checksum eşleşmiyorsa commit FAIL.
3. CI pipeline drift varsa FAIL.
4. `SAB.sha256` tek başına değiştirilemez.
5. Drift = Merge Block.

Drift detection script'i CI ve pre-commit içinde zorunludur.

---

## 9️⃣ DEFINITION OF DONE

Bir iş Done sayılır ancak:

- Context7 PASS (0)
- Governance PASS
- Drift Detection PASS
- Test PASS
- Registry güncel
- Teknik borç oluşmadı

---

## 🔒 PRODUCTION SEAL

Aşağıdaki koşullar sağlanıyorsa:

- CQRS sağlıklı
- Projection HEALTHY
- DLQ doğrulandı
- Governance 0 ihlal
- SAB drift yok

**PRODUCTION SEAL: ACTIVE**

---

## SON İLKE

Yama yok.
Kural gevşemez.
SSOT dışına çıkılmaz.
Drift tolere edilmez.
Governance üstündür.

---

## 7️⃣ MALİ SUÇLAR VE FİNANSAL DENETİM (Phase 12)

Aşağıdaki eylemler "Mimari Suç" kabul edilir ve Bekçi tarafından bloklanır:

1. **Authority Leakage (Yetki Sızıntısı):** `tenant_id` filtresi olmayan her türlü finansal veri erişimi.
2. **Canonical Drift (Kanonik Sapma):** Finansal modellerde `amount`, `type`, `tur` gibi yasaklı (legacy) terimlerin kullanımı.
3. **Ghost Transaction (Hayalet İşlem):** Ledger dışında bakiye değiştiren her türlü otonom işlem.
4. **Budget Guard Bypass:** `AiBudgetGuard::canExecute()` kontrolü olmadan AI servisi çalıştırmak.

---

## 🔟 BİLGİ VARLIK İLKELERİ (Knowledge as Business Asset — ERA IV)

Aşağıdaki ilkeler YALIHAN Platform'un dördüncü katmanı olan Knowledge Platform'u yönetir:

```
K-1. Bilgi, Workspace kadar stratejik bir varlıktır.
     Platformun kendi bilgisini yönetmesi, saklaması ve
     kullanması ERA IV hedefinin temelidir.

K-2. Her önemli iş kararı aranabilir bilgiye dönüşmelidir.
     ADR, memory veya Drive'a kaydedilmeyen kararlar
     kaybolmuş sayılır.

K-3. Bilgi tek bir yerde yönetilir:
     Knowledge Platform = Corporate Memory
                      + Technical Knowledge
                      + Institutional Knowledge

K-4. Her bilgi parçasının bir sahibi, bir yaşam döngüsü
     ve bir gözden geçirme tarihi olmalıdır.
     Sahipsiz bilgi = bakımsız bilgi = kaybolmuş bilgi.

K-5. Bilgi sürümlenir ve değişiklikleri izlenir.
     Version history olmayan doküman = güvenilmez doküman.

K-6. Bilgi üçe ayrılır ve her biri farklı yönetilir:
     ├─ Corporate Memory   (memory/)     → Agent oturum belleği, süresiz saklanır
     ├─ Technical Knowledge (docs/ + NotebookLM) → Mimari, kod standartları
     └─ Institutional Knowledge (Google Drive) → Müşteri, hukuk, operasyon
```

**Bilgi Yaşam Döngüsü:**
```
DOĞUM ──► KULLANIM ──► BAKIM ──► GÖZDEN GEÇİRME ──► ARŞİV
   │          │           │            │                │
   ▼          ▼           ▼            ▼                ▼
memory/   Tüm katmanlar  6 ayda     12 ayda        36 ay sonra
           kullanılır     bir         bir              Drive/
                         bakım       gözden          Archive
                                     geçir
```

**Saklama Süreleri:**
| Bilgi Tipi | Saklama | Arşiv |
|------------|---------|-------|
| SAB + ADR | Süresiz | Asla silinmez |
| memory/ (core) | Süresiz | Asla silinmez |
| memory/daily/ | 30 gün | Drive/06-ARCHIVE |
| memory/weekly/ | 12 hafta | Drive/06-ARCHIVE |
| Drive/01-GOVERNANCE | Süresiz | Asla silinmez |
| Drive/03-CLIENTS | 7 yıl | KVKK uyumu |
| Drive/06-ARCHIVE | 10+ yıl | Asla silinmez |

---
*Bu anayasa Yalıhan AI OS'un teknik onurunu ve ticari geleceğini korur.*

## 🔒 BİLGİ YÖNETİMİ UYGULAMASI (ERA IV — Knowledge Engine)

Knowledge Platform aşağıdaki Sprint'lerde inşa edilir:

```
Sprint 5.1 → Corporate Memory Index
Sprint 5.2 → Knowledge Search (arama + aranabilirlik)
Sprint 5.3 → NotebookLM Sync Engine (6 notebook)
Sprint 5.4 → Drive Sync Engine (6-klasör yapısı)
Sprint 5.5 → Embedding & Vector Search (semantic arama)
Sprint 5.6 → Knowledge Verification (bilgi tazelik + gap detection)
```

**Bilgi Sahipliği Matrisi:**
| Bilgi Tipi | Birincil Sahip | Gözden Geçirme |
|------------|----------------|----------------|
| SAB anayasası | CTO | Her sprint |
| ADR'ler | Architect | Gerektiğinde |
| memory/* | Chief AI | Oturum sonu |
| NotebookLM | AI Coordinator | Aylık |
| Drive/01-02 | Product Owner | Sprint sonu |
| Drive/03-CLIENTS | CFO | Çeyreklik |
| Drive/06-ARCHIVE | CKO | Yıllık |

**Kaynak:** `docs/knowledge/KNOWLEDGE_BLUEPRINT.md` — Resmi bilgi mimarisi planı
**ADR:** `docs/adr/2026-07-05-knowledge-platform-adoption.md` — Board kararı

---

## 🔷 SAAB v8.0 — Location Intelligence Domain

Bu bölüm ERA IV Location Intelligence altyapısını tanımlar.

### Rule 8 — Property Owns Geographic Truth

Tüm ham coğrafi gerçek Property/Ilan üzerinde tutulur.

**Ham veri (Property üzerinde):**
- `ada`, `parsel`, `pafta`
- `coordinate` (lat/lng)
- `polygon` (GeoJSON)
- `tkgm_metadata` (KAKS, TAKS, gabari, imar durumu)
- `parcel_area` (alan_m2)
- `neighborhood_id`, `mahalle_id`

**Hesaplanmış zeka değerleri (ayrı tutulur):**
- `neighborhood_score`
- `location_signal_score`
- `investment_score`
- `accessibility_score`

**Yasak:** Coğrafi veri asla Talep'e yazılmaz. Talep sadece müşteri arama kriteri depolar.

### Rule 9 — Location Verification Pipeline

Konum doğrulaması sıralı pipeline olarak çalışır:

```
Danışman → Adres → Koordinat → TKGM/Parsel → Polygon → POI → Scores → Timeline → Ready
```

Her aşama `LocationStepUpdated` event fırlatır. UI bu event'leri dinleyerek ilerleme gösterir.

### Rule 10 — TKGM Is Data Provider Only

TKGM business logic sahibi değildir. TKGM sadece normalize edilmiş parsel verisi döner.

```
TKGM Provider → Location Intelligence → Property/Ilan
```

TKGM değişirse veya farklı bir CBS servisi eklenirse sistem değişmez. Provider pattern uygulanır.

### Rule 11 — Property Template Owns Visibility

Konum görünürlüğü Property Template tarafından kontrol edilir ve yayın kanalına göre farklılık gösterebilir.

**Örnek kanal bazlı görünürlük:**

| Kanal | Ada | Parsel | Polygon | Tam Koordinat |
|-------|-----|--------|---------|---------------|
| Website | show | show | hide | yaklaşık |
| Sahibinden | show | show | hide | hide |
| Airbnb | hide | hide | hide | sadece mahalle |
| CRM | show | show | show | show |

Hiçbir provider görünürlük kararı vermez.

### Rule 12 — Location Intelligence Is One Capability

Tüm mekansal operasyonlar Location Intelligence capability'si altındadır:

- Geocoding / Reverse geocoding
- TKGM parsel sorgusu
- POI analizi
- Polygon işleme
- Harita skoru hesaplama
- Mahalle skoru
- Provider fallback
- Provider caching

Bağımsız service, Location Intelligence bypass ederek domain write yapamaz.

### Rule 13 — Location Data Is Immutable After Publish

Yayınlanmış konum verisi sessizce değiştirilemez.

Koordinat, ada/parsel, polygon veya parsel kimliği değişirse:

1. İlan draft/review durumuna alınır
2. Verification pipeline yeniden çalışır
3. Publishing readiness yeniden hesaplanır
4. Visibility kuralları reset edilir
5. Timeline değişikliği kaydeder

### Rule 14 — Provider Cannot Access Domain

Provider'lar doğrudan Ilan, Talep, Workspace veya Publishing modellerine yazamaz.

**İzin verilen akış:**

```
Provider → Location Intelligence → Domain Service → Property/Ilan
```

Provider çıktısı ham veridir. Location Intelligence yorumlama sahibidir. Domain Service mutation sahibidir.

---

**ADR:** `docs/adr/2026-07-06-location-intelligence-domain.md` — Detaylı mimari kararları

---

## 🔷 SAAB v8.1 — Property Workspace Architecture

### Rule 15 — Property Workspace Mental Model

Wizard Property Workspace Creator'dır, Listing Form değil. Kullanıcı iş niyeti seçer, database kategorisi değil.

```
Seçim akışı:
Intent → Workspace Created → Template Attached → Capabilities Activated
```

"Ne yaratıyorsunuz?" sorusu database yapısını değil, iş niyetini yansıtır:
- "Villa Satılık"
- "Villa Kiralık (Günlük)"
- "Arsa Satılık"
- "Ticari Gayrimenkul"

### Rule 16 — Template-Owned AI Hooks

AI aksiyonları Property Template tarafından aktive edilir. Her template hangi AI hook'ların kullanılabileceğini tanımlar.

```
Template: Luxury Villa Sales
├── enabled_ai:
│   ├── title_generation: true
│   ├── description_generation: true
│   ├── price_suggestion: true
│   ├── location_description: true
│   └── seo_optimization: true
│   └── reservation_optimization: false (satılık değil)
```

Template'in aktif etmediği AI hook UI'da görünmez.

### Rule 17 — Reservation Is Business Capability

Reservation sadece calendar değildir. Reservation şunları kapsar:

- Availability (Takvim değil, kullanılabilirlik yönetimi)
- Pricing Rules (Sezon, hafta sonu, uzun konaklama)
- Guest Info (Misafir bilgileri, kimlik, tercihler)
- Financial Events (Tahsilat, iade, komisyon)
- Calendar Sync (iCal, Airbnb, Booking.com)

Calendar bu capability'nin sadece bir görünümüdür.

### Rule 18 — Channel Visibility Is Template-Owned

Publishing visibility kuralları Property Template'e aittir. Kod değil, template karar verir.

```
Template: Villa Sale
├── channels:
│   ├── website:
│   │   ├── location.ada: show
│   │   └── location.coordinates: approximate
│   ├── sahibinden:
│   │   ├── location.ada: show
│   │   └── location.coordinates: hide
│   └── crm:
│       └── location.all: show
```

### Rule 19 — Documents Gate Publishing

Required belgeler doğrulanmadan publishing başlayamaz. Template property type'a göre gerekli/koşullu/opsiyonel belgeleri tanımlar.

```
Arsa Template:
├── documents:
│   ├── required: [tapu, imar_durumu]
│   ├── conditional: [zemin_etudu] # if alan > 1000m²
│   └── optional: [degerleme_raporu]
```

### Rule 20 — Workspace Owns Timeline

Tüm önemli aksiyonlar Workspace Timeline'da kaydedilir. Audit log değil, Workspace History'dir.

```
Timeline Events:
- workspace.created
- location.address_entered
- location.coordinates_verified
- location.tkgm_verified
- location.polygon_generated
- location.poi_analyzed
- location.scores_calculated
- media.uploaded
- media.ai_analyzed
- document.uploaded
- document.verified
- ai.title_generated
- ai.description_generated
- pricing.suggested
- pricing.confirmed
- publishing.channel_enabled
- publishing.listing_created
```

---

**Reference:** `docs/architecture/PROPERTY_WORKSPACE_ARCHITECTURE.md` — Kapsamlı referans dokümanı
**ADR:** `docs/adr/2026-07-06-property-workspace-model.md` — Property Workspace mimari kararı

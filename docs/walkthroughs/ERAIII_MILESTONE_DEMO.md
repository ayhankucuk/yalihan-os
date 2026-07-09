# ERA III — Milestone Demo Blueprint

> **Hedef:** ERA III capability zincirini uçtan uca, kesintisiz bir senaryo ile canlı olarak ispatlamak.

---

## Mimari Zincir (Mevcut Durum)

```
┌──────────────────────────────────────────────────────────────────────────┐
│  ERA III Certified Capability Chain                                     │
│                                                                          │
│  Sprint 6.1  Workspace Runtime        ✅ CERTIFIED                     │
│  Sprint 6.2  Location Intelligence    ✅ CERTIFIED                     │
│  Sprint 6.3  Media Intelligence      ✅ CERTIFIED                     │
│  Sprint 6.4  AI Vision Intelligence   ✅ CERTIFIED                     │
│  Sprint 6.5  Publishing Intelligence  ✅ CERTIFIED                     │
│                                                                          │
│  Sprint 6.6  Channel Execution        ⏳ NEXT                          │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Uçtan Uca Demo Senaryosu

### Actor
**Emre** — Bodrum merkezli gayrimenkul danışmanı, Yalıhan Emlak

### Senaryo Başlığı
**"Yeni İlan: Deniz Manzaralı Gündoğan Villası"**

### Adım Adım Akış

```
┌─────────────────────────────────────────────────────────────────────────┐
│ ADIM 1 — Workspace Oluşturma                                           │
│                                                                          │
│   Emre, admin panelden "Yeni İlan" butonuna tıklar                      │
│                                                                          │
│   → Workspace oluşturulur (Sprint 6.1)                                 │
│   → TenantScope korunur                                                 │
│   → Workspace state: DRAFT                                               │
│                                                                          │
│   Beklenen: "Gündoğan Deniz Manzaralı Villa" workspace'i açılır        │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ ADIM 2 — Konum Bilgisi                                                 │
│                                                                          │
│   Emre, haritadan Gündoğan'ı seçer                                     │
│                                                                          │
│   → Location Intelligence devreye girer (Sprint 6.2)                   │
│   → il / ilce / mahalle zinciri otomatik çözülür                       │
│   → coords → lat/lng kümelenir                                         │
│                                                                          │
│   Beklenen: Konum "Gündoğan, Bodrum, Muğla" olarak görünür             │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ ADIM 3 — Fotoğraf Yükleme                                              │
│                                                                          │
│   Emre, 8 fotoğraf yükler (havuz, manzara, salon, mutfak, 4 oda)       │
│                                                                          │
│   → Media Intelligence devreye girer (Sprint 6.3)                      │
│   → Oda türleri algılanır (10 oda tipi)                                │
│   → Kalite analizi yapılır (blur, brightness, coverage)                  │
│   → Kapak fotoğrafı seçilir                                            │
│                                                                          │
│   Beklenen: 8 fotoğraf listelenir, 1 kapak fotoğrafı işaretlenir        │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ ADIM 4 — AI Vision Analizi                                              │
│                                                                          │
│   Emre, "AI Analiz" butonuna tıklar                                    │
│                                                                          │
│   → AI Vision Intelligence devreye girer (Sprint 6.4)                   │
│   → Her fotoğraf GPT-4o ile analiz edilir                               │
│   → title_hints, detected_amenities, detected_luxury_features          │
│   → vision_score hesaplanır (AI confidence + rule fusion)                │
│                                                                          │
│   Beklenen: Her fotoğraf için AI analiz sonucu görünür                  │
│            "Havuz Alanı", "Jakuzi", "Deniz Manzarası" tespit edilir      │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ ADIM 5 — Publish Kararı                                                 │
│                                                                          │
│   Publish karar mekanizması çalışır                                      │
│                                                                          │
│   → PublishDecisionAgent karar verir (approved / needs_review / rejected) │
│   → quality_tier hesaplanır (premium / standard / low)                  │
│   → overall_score hesaplanır                                             │
│                                                                          │
│   Beklenen: "approved" kararı, 3 kanal hedefi seçilir                  │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ ADIM 6 — Publishing Intelligence                                         │
│                                                                          │
│   Emre, "Kanallara Yayınla" butonuna tıklar                            │
│                                                                          │
│   → PublishingIntelligenceOrchestrator çalışır (Sprint 6.5)             │
│   → Her kanal için ayrı payload üretilir:                               │
│                                                                          │
│     AIRBNB         SAHIBINDEN       HEPSIEMLAK                          │
│     ──────────     ──────────      ──────────                          │
│     50-char title  80-char title   100-char title                        │
│     summary+desc    tek parça       tek parça                            │
│     amenities→airbnb amenities    özellikler                            │
│     space_type      tip/oda/kategori tip/oda/kategori                    │
│                                                                          │
│   → ChannelReadinessDTO üretilir (hangi kanallar hazır)                  │
│   → PublishingPackageReady event fırlatılır                               │
│                                                                          │
│   Beklenen: 3 ayrı kanal payload'ı üretilir, 3/3 kanal hazır            │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ ADIM 7 — Dashboard Görüntüleme (Sprint 6.6 Sonrası)                     │
│                                                                          │
│   Emre, ilan detay sayfasında "Yayın Durumu" kontrol eder               │
│                                                                          │
│   → Her kanal için ayrı status kartı:                                  │
│                                                                          │
│     Kanal        Durum        Payload           readiness_score         │
│     ──────────   ──────────   ───────────────   ──────────────────     │
│     Airbnb       Hazır        ✅ Üretildi       95/100                  │
│     Sahibinden   Hazır        ✅ Üretildi       88/100                  │
│     Hepsiemlak   Hazır        ✅ Üretildi       82/100                  │
│                                                                          │
│   Beklenen: 3 kanal "publish ready" olarak görünür                      │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Başarı Kriterleri

### MUST PASS (Demo için Zorunlu)

| # | Kriter | Nasıl Doğrulanır |
|---|--------|-----------------|
| 1 | Workspace oluşturulabilir | Admin → Yeni İlan → Form açılır |
| 2 | Konum bilgisi otomatik çözülür | ilce seç → il/mahalle otomatik dolar |
| 3 | Fotoğraf yüklenebilir | 1+ fotoğraf → Media Intelligence tetiklenir |
| 4 | AI Vision analizi çalışır | vision_data kolonu dolar, score ≥ 0 |
| 5 | Publish kararı üretilir | decision alanı null olmaz |
| 6 | 3 kanal payload'ı üretilir | airbnb/sahibinden/hepsiemlak ayrı ayrı mevcut |
| 7 | Tenant veri izolasyonu korunur | Farklı tenant'ın ilanları görünmez |

### SHOULD PASS (Release Quality)

| # | Kriter | Nasıl Doğrulanır |
|---|--------|-----------------|
| 1 | Tüm testler yeşil | `php artisan test` → 0 fail |
| 2 | SAB integrity korunur | `php artisan sab:integrity-scan` → 0 blocking |
| 3 | Pipeline süresi makul | Orchestrator < 5 saniye |
| 4 | Hata durumları graceful | Eksik fotoğraf → partial success, not crash |

---

## Demo Ortamı

### Local Stack
```
Laravel:      http://127.0.0.1:8000
Vite HMR:     http://localhost:5174
MySQL:        yalihanai_test (test DB)
Queue:        sync (demo için)
Vision API:    OpenAI GPT-4o (mock yok, gerçek)
```

### Test Kullanıcıları
| Kullanıcı | Rol | Tenant |
|-----------|-----|--------|
| admin@yalihan.com | Admin | Yalıhan Emlak |
| emre@yalihan.com | Danışman | Yalıhan Emlak |

### Demo Verisi
- 1 test ilan (Gündoğan Deniz Manzaralı Villa)
- 8 test fotoğraf (havuz, manzara, salon, mutfak, 4 oda)
- Vision analizi için OpenAI API key gerekli

---

## Release Stabilization Checklist

### P0 — Blocker Bugs

| Bug | Öncelik | Durum |
|-----|---------|-------|
| `ups_templates.json` bağımlılığı — Property Hub | 🔴 P0 | ⏳ |
| Kategori zinciri — alt_kategori null | 🔴 P0 | ⏳ |
| Workspace state transition hatası | 🔴 P0 | ⏳ |

### P1 — High

| Issue | Öncelik | Durum |
|-------|---------|-------|
| AI Wallet yetersiz bakiye senaryosu | 🟠 P1 | ⏳ |
| Vision job timeout retry | 🟠 P1 | ⏳ |

---

## Sprint 6.6 Hand-off İçin Notlar

Publishing Intelligence pipeline'ı hazır. Sprint 6.6 Channel Execution şunları ekleyecek:

1. **ChannelApiClient katmanı** — Real HTTP çağrıları
2. **Retry + rate-limit mekanizması**
3. **Publish sonrası state geçişi** — workspace → published
4. **Dashboard paneli** — kanal bazlı readiness UI

### Dependency Graph

```
PublishingIntelligenceOrchestrator
         │
         ▼
PublishingPackageReady Event
         │
         ▼ (Sprint 6.6)
ChannelApiClient (HTTP)
         │
         ▼
Airbnb API / Sahibinden API / Hepsiemlak API
         │
         ▼
Workspace state → published
         │
         ▼
Dashboard bildirimi
```

---

## Sonraki Adım

1. Release Stabilization → P0 hataları gider
2. ERA III Demo senaryosu çalıştırılır
3. Sprint 6.6 Discovery başlar

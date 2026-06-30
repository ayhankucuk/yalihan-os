# PRODUCT CAPABILITY MAP — SAAB v6
**Hazırlayan:** Chief Product Officer — Yalıhan AI OS  
**Versiyon:** 1.0.0  
**Tarih:** 2026-06-28  
**Sistem:** Yalıhan Emlak AI Operating System  
**Lokasyon:** Bodrum — Lüks Gayrimenkul Portföyü  

---

## Yönetici Özeti

Yalıhan Platform, Bodrum merkezli lüks gayrimenkul operasyonlarını yönetmek için inşa edilmiş AI-destekli bir emlak işletim sistemidir. Sistem şu anda **%35-40 yetenek olgunluğunda** — güçlü teknik altyapı, zayıf iş yetenekleri.

**Mevcut Durum:**
- ✅ Property Domain: İlan CRUD, medya, fiyatlandırma — **PRODUCTION**
- ✅ CRM Domain: Kişi modeli, etkileşim kaydı — **PRODUCTION**
- ✅ AI Domain: 94 servis, Cortex orkestratörü — **PRODUCTION**
- ✅ Governance: SAB karar motoru, denetim — **PRODUCTION**
- ✅ Finance Domain: Ledger temeli, döviz kuru — **EARLY STAGE**
- ✅ Location Domain: Canonical SSOT — **PRODUCTION**
- 🔄 Owner Portal: Rapor ihracat tabloları var, UI yok — **CONCEPT**
- 🔄 Airbnb Domain: iCal feed + takvim sync modelleri var, sync motoru yok — **CONCEPT**
- 🔄 Lead Management: Lead modeli + AI scoring var, pipeline automation yok — **EARLY STAGE**
- 🔄 Calendar: Takvim sync modelleri var, unified calendar view yok — **CONCEPT**
- 🔄 Notifications: Bildirim modeli + Telegram entegrasyonu var, workflow-based triggers yok — **EARLY STAGE**
- 🔄 Dashboard: Analytics modelleri var, dashboard UI yok — **CONCEPT**
- 🔄 AI Workforce: AgentRun + AgentMemory modelleri var, agent orchestration yok — **CONCEPT**

---

## Domain Haritası (Mevcut)

```
┌──────────────────────────────────────────────────────────────────────┐
│                    YALIHAN AI OS — CAPABILITY MAP                     │
├────────────────┬─────────────┬──────────────┬────────────────────────┤
│    PROPERTY    │     CRM     │  AI DOMAIN   │     FINANCE            │
│   (Production) │ (Production)│ (Production) │     (Early Stage)      │
├────────────────┼─────────────┼──────────────┼────────────────────────┤
│ Listings       │ Lead Mgmt   │ AI Workforce │ Calendar               │
│ Portfolio      │ CRM         │ Cortex       │ Notifications          │
│ Airbnb         │ Owner Portal│ AI Services  │ Dashboard              │
└────────────────┴─────────────┴──────────────┴────────────────────────┘
```

---

## 1. CRM (Müşteri İlişkileri Yönetimi)

### 1.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Kişi Modeli | ✅ Production | `Kisi` — 18K satır kod, tenant-scoped |
| Etkileşim Kaydı | ✅ Production | `KisiEtkilesim`, `KisiAktivite` |
| Talep Eşleştirme | ✅ Production | `Talep`, `Eslesme`, `IlanTalepEslesmeleri` |
| Lead Scoring | ✅ AI-Powered | `AILeadScore` — DeepSeek destekli |
| Çoklu Kişi Türü | ⚠️ Legacy Risk | Müşteri + CRM V1/V2 çakışması var |

### 1.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Pipeline Automation | 🔴 P0 | Lead'leri aşamalara göre otomatik taşıma |
| Lead Nurturing Sequences | 🔴 P0 | Otomatik besleme zincirleri (e-posta, Telegram, SMS) |
| CRM Segmentation | 🟠 P1 | Müşterileri davranışa göre otomatik segmentlere ayırma |
| Workflow Rules | 🟠 P1 | Tetikleyici tabanlı aksiyon (örn: ilan görüntüle → 3 gün sonra e-posta) |
| Duplicate Detection | 🟡 P2 | Aynı kişi birden fazla kayıtta |
| Customer 360 View | 🟡 P2 | Tek panelde tüm müşteri bilgisi |
| Lifetime Value Tracking | 🟡 P2 | Müşteri yaşam boyu değeri hesaplama |

### 1.3 Bağımlılıklar

```
CRM
├── Property Domain (ilan-talep eşleştirme için)
├── AI Domain (lead scoring, segmentation AI)
├── Finance Domain (müşteri bazlı gelir takibi)
└── Notifications (etkileşim sonrası bildirim)
```

### 1.4 Önerilen UX

```
┌─────────────────────────────────────────────────────────┐
│  CRM DASHBOARD                                           │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │ 📊 Pipeline  │  │ 🎯 Lead Score │  │ 📈 Conversion│  │
│  │ Yeni → 5     │  │ Hot: 12      │  │ Rate: 23%    │  │
│  │ Görüşüldü → 8│  │ Warm: 34     │  │ Avg: 18 days │  │
│  │ Teklif → 3   │  │ Cold: 67     │  │              │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
│                                                          │
│  ┌─────────────────────────────────────────────────────┐│
│  │ LEAD PIPELINE                                       ││
│  │ [Yeni]──►[Görüşüldü]──►[Teklif]──►[Kapalı]         ││
│  │  ●●●●●  →  ●●●●●●●●  →  ●●●  →  ●                 ││
│  └─────────────────────────────────────────────────────┘│
│                                                          │
│  ┌─────────────────────────────────────────────────────┐│
│  │ 🔥 AI RECOMMENDATIONS                               ││
│  │ • 3 leads need follow-up (last contact >7 days)    ││
│  │ • Lead #1247: High intent, recommend call today    ││
│  │ • Segment "Luxury Buyers" has 5 new entries        ││
│  └─────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────┘
```

---

## 2. Portfolio (Portföy Yönetimi)

### 2.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Yazlık Detay | ✅ Production | `YazlikDetail`, `YazlikFiyatlandirma` |
| Fiyatlandırma | ✅ Production | `PropertySeasonalRate`, sezon bazlı fiyat |
| Müsaitlik | ✅ Production | `PropertyAvailability` |
| Abonelik | ✅ Production | `PropertySubscription` |
| Portföy Sağlığı | ✅ AI-Powered | `PortfolioDoctor` servisi |
| Büyüme Projeksiyonu | ✅ AI-Powered | `PropertyGrowthProjection` |

### 2.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Portfolio Performance Dashboard | 🔴 P0 | Tüm portföy KPI tek panelde |
| Occupancy Rate Tracking | 🔴 P0 | Doluluk oranı günlük/aylık |
| Revenue per Listing | 🟠 P1 | İlan bazlı gelir analizi |
| Portfolio Health Score | 🟠 P1 | AI destekli portföy sağlık skoru |
| Competitive Market Analysis | 🟠 P1 | Bölge bazlı karşılaştırma |
| Acquisition Recommendations | 🟠 P1 | AI: Hangi mülkler alınmalı? |
| Disposition Recommendations | 🟡 P2 | AI: Hangi mülkler satılmalı? |
| Cap Rate Calculator | 🟡 P2 | Net operasyonel gelir / mülk değeri |

### 2.3 Bağımlılıklar

```
Portfolio
├── Property Domain (listing data)
├── Finance Domain (gelir/gider verisi)
├── AI Domain (Portfolio Doctor, Market Valuation)
└── Dashboard (KPI visualization)
```

### 2.4 Önerilen UX

```
┌─────────────────────────────────────────────────────────┐
│  PORTFOLIO COMMAND CENTER                               │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │ 🏠 Toplam    │  │ 📅 Doluluk   │  │ 💰 Ortalama  │  │
│  │ Mülk: 47    │  │ Bu Ay: 78%   │  │ Fiyat: €2.4M │  │
│  │ +3 bu ay    │  │ Geçen: 71%   │  │ +12% YoY    │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
│                                                          │
│  🏆 PORTFOLIO HEALTH: 87/100                            │
│  ━━━━━━━━━━━━━━━━━━━━━━━━░░░░░░░░                       │
│  AI: "Portföy sağlığı iyi. Bodrum merkez mülkler        │
│  daha yüksek getiri sağlıyor. Yalık avg +18% performans"│
└─────────────────────────────────────────────────────────┘
```

---

## 3. Listings (İlan Yönetimi)

### 3.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| İlan CRUD | ✅ Production | `IlanCrudService` — tek write authority |
| Çoklu Yayın Tipi | ✅ Production | Satılık, Kiralık, Devre Mülk |
| Medya Yönetimi | ✅ Production | `IlanResim`, `IlanFotografi`, `Photo` |
| Fiyat Geçmişi | ✅ Production | `IlanPriceHistory` |
| Çeviri / Çoklu Dil | ✅ AI-Powered | `ListingTranslation`, TR/EN/RU/AR/DE/FR |
| State Machine | ✅ Production | `ListingStateMachine` |
| AI Açıklama | ✅ AI-Powered | `CortexVoiceService` + YalihanCortex |
| Vektör Arama | ✅ AI-Powered | `IlanEmbedding` |

### 3.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Bulk Listing Operations | 🔴 P0 | Toplu güncelleme, toplu yayınlama |
| Listing Versioning | 🟠 P1 | Değişiklik geçmişi, diff, rollback |
| A/B Testing for Listings | 🟠 P1 | Farklı açıklamaları test et |
| Automated Listing Quality Score | 🟠 P1 | AI: Her ilan için kalite skoru |
| SEO Automation | 🟠 P1 | Meta tag, structured data, sitemap |
| Listing Performance Analytics | 🟠 P1 | Görüntülenme → lead dönüşüm hunisi |
| Scheduled Publishing | 🟡 P2 | Belirli tarihte otomatik yayın |
| Virtual Tour Integration | 🟡 P2 | 360° tur embedding |

### 3.3 Bağımlılıklar

```
Listings
├── Feature/Template Domain (özellik ataması)
├── Location Domain (il/ilçe/mahalle)
├── AI Domain (açıklama, fiyat önerisi)
├── CRM Domain (lead eşleştirme)
└── Dashboard (performans metrikleri)
```

---

## 4. Lead Management

### 4.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Lead Modeli | ✅ Production | `Lead`, `LeadActivity`, `LeadMessage` |
| AI Lead Scoring | ✅ AI-Powered | `AILeadScore` — DeepSeek |
| Lead Eşleştirme | ✅ AI-Powered | `BuyerMatchEngine`, `BuyerMatchLog` |
| Otomatik Nurturing | ❌ Eksik | Yok |
| Pipeline Automation | ❌ Eksik | Manuel aşama geçişleri |
| Lead Import | ❌ Eksik | CSV/API ile toplu import |

### 4.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Multi-Channel Lead Capture | 🔴 P0 | Web form, Airbnb, telefon, Telegram |
| Automated Nurturing Sequences | 🔴 P0 | Davranışa göre otomatik besleme |
| Lead Distribution Rules | 🟠 P1 | Otomatik danışmana atama |
| Lead Qualification AI | 🟠 P1 | AI: Bu lead gerçekten alıcı mı? |
| Lead Source Attribution | 🟠 P1 | Hangi kanal hangi lead'i getirdi? |
| Re-engagement Campaigns | 🟡 P2 | Dormant lead'lere yeniden ulaşma |

### 4.3 Bağımlılıklar

```
Lead Management
├── CRM (Kişi, Talep)
├── AI (AILeadScore, BuyerMatchEngine)
├── Notifications (nurturing sequence notifications)
└── Dashboard (lead funnel analytics)
```

---

## 5. Owner Portal (Mal Sahibi Portali)

### 5.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Rapor Tabloları | ✅ DB Ready | `OwnerReportExport`, `OwnerReportMetric`, `OwnerReportRow` |
| Anahtar Yönetimi | ✅ Model Var | `AnahtarYonetimi` tablosu mevcut |
| Portal UI | ❌ Yok | Hiçbir şey yok |
| Self-Service Login | ❌ Yok | Yok |
| Gerçek Zamanlı Dashboard | ❌ Yok | Yok |

### 5.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Owner Portal UI | 🔴 P0 | Mal sahibi girişi + özel dashboard |
| Real-time Performance | 🔴 P0 | Anlık görüntülenme, talep, gelir |
| Document Upload | 🟠 P1 | Tapu, sigorta, bakım belgeleri |
| Maintenance Requests | 🟠 P1 | Mal sahibinden bakım talebi |
| Payment History | 🟠 P1 | Kiralama gelir ödemeleri |
| Availability Calendar (Owner) | 🟠 P1 | Mal sahibine özel takvim |
| Dynamic Pricing Suggestions | 🟡 P2 | AI: Bu sezon ne fiyat vermeli? |

### 5.3 Bağımlılıklar

```
Owner Portal
├── Portfolio (mülk verileri)
├── Finance (ödeme geçmişi)
├── Calendar (müsaitlik takvimi)
├── Reservation (kira kayıtları)
└── Notifications (mal sahibi bildirimleri)
```

---

## 6. Airbnb (Airbnb Entegrasyonu)

### 6.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| iCal Feed | ✅ Model Var | `PropertyCalendarFeed` |
| Takvim Senkronizasyonu | ✅ Model Var | `IlanTakvimSync` |
| Fiyat Sync | ❌ Yok | Airbnb fiyatları ile senkron yok |
| Yönetici Paneli | ❌ Yok | Airbnb host dashboard yok |
| Mesaj Sync | ❌ Yok | Airbnb mesajları çekilmiyor |
| Değerlendirme Sync | ❌ Yok | Airbnb yorumları çekilmiyor |
| Smart Pricing | ❌ Yok | Dinamik fiyat önerisi yok |

### 6.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Airbnb API Entegrasyonu | 🔴 P0 | Airbnb Channel Manager API |
| Two-Way Calendar Sync | 🔴 P0 | Airbnb ↔ Yalıhan takvim senkronizasyonu |
| Listing Sync | 🟠 P1 | İlan bilgisi Airbnb'ye otomatik push |
| Review Management | 🟠 P1 | Airbnb değerlendirmelerini yönet |
| Pricing Sync | 🟠 P1 | Yalıhan fiyat ↔ Airbnb fiyat |
| Smart Pricing AI | 🟠 P1 | AI: Rekabetçi fiyat önerisi |
| Multi-OTA Support | 🟡 P2 | Booking.com, Vrbo, Expedia |

### 6.3 Bağımlılıklar

```
Airbnb
├── Calendar (müsaitlik verisi)
├── Portfolio (listing data)
├── Finance (gelir takibi)
└── AI (smart pricing engine)
```

---

## 7. Reservation (Rezervasyon Yönetimi)

### 7.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Rezervasyon Modeli | ✅ Production | `YazlikRezervasyon`, `IlanReservation` |
| Müsaitlik Yönetimi | ✅ Production | `PropertyAvailability` |
| Sezon Tanımları | ✅ Production | `Season`, `PropertySeasonalRate` |
| Çakışma Kontrolü | ❌ Eksik | AI yok, manuel kontrol |
| Otomatik Fiyat Hesaplama | ⚠️ Kısmi | Sezon bazlı var, dinamik yok |
| Misafir Kaydı | ❌ Eksik | Yok |
| Check-in/Check-out | ❌ Eksik | Yok |

### 7.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Reservation Workflow Engine | 🔴 P0 | Talep → Onay → Ödeme → Check-in → Check-out |
| Conflict Resolution AI | 🔴 P0 | Çakışan rezervasyonları AI çözsün |
| Dynamic Pricing Engine | 🟠 P1 | Rekabet, talep, mevsim bazlı otomatik fiyat |
| Guest Pre-boarding | 🟠 P1 | Misafir bilgi formu, kimlik kaydı |
| Housekeeping Integration | 🟠 P1 | Check-out → temizlik bildirimi |
| Payment Collection | 🟠 P1 | Online ödeme, depozito |
| Cancellation Policy Engine | 🟡 P2 | Kurallara göre otomatik iade hesaplama |

### 7.3 Bağımlılıklar

```
Reservation
├── Calendar (müsaitlik kontrolü)
├── Finance (ödeme, depozito)
├── Airbnb (OTA senkronizasyonu)
├── Owner Portal (mal sahibi bildirimi)
└── Notifications (rezervasyon bildirimleri)
```

---

## 8. Finance (Finans Yönetimi)

### 8.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Ledger (Muhasebe) | ✅ Early Stage | `LedgerEntry`, `LedgerAccount`, `LedgerBalance` |
| Döviz Kuru | ✅ Production | `FxRate`, `TCMBCurrencyService` |
| Gelir Kalemleri | ✅ Early Stage | `RentalGelirKalemi` |
| Gider Kalemleri | ✅ Early Stage | `RentalGiderKalemi`, `PropertyExpense` |
| Finansal Raporlama UI | ❌ Eksik | Sadece modeller var |
| Kasa Yönetimi | ❌ Eksik | Yok |
| Bütçe Planlama | ❌ Eksik | Yok |
| Otomatik Muhasebe | ❌ Eksik | Rezervasyon → otomatik kayıt yok |

### 8.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Financial Dashboard | 🔴 P0 | Gelir/gider/kar dashboard |
| Automated Booking | 🔴 P0 | Rezervasyon → finansal işlem otomasyonu |
| Owner Payment Processing | 🔴 P0 | Mal sahibine otomatik ödeme hesaplama |
| Invoice Generation | 🟠 P1 | AI fatura üretimi |
| Expense Tracking | 🟠 P1 | Mülk giderleri otomatik takibi |
| Tax Reporting | 🟠 P1 | KDV, stopaj raporları |
| Cash Flow Forecasting | 🟠 P1 | AI: Gelecek 3 ay nakit akışı |
| Profit & Loss per Property | 🟡 P2 | İlan bazlı kar/zarar |

### 8.3 Bağımlılıklar

```
Finance
├── Reservation (gelir kaynağı)
├── Owner Portal (mal sahibi ödemeleri)
├── Portfolio (mülk bazlı giderler)
└── Calendar (sezon bazlı gelir)
```

---

## 9. Calendar (Takvim Yönetimi)

### 9.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Takvim Sync Model | ✅ Production | `IlanTakvimSync` |
| iCal Feed | ✅ Model Var | `PropertyCalendarFeed` |
| Müsaitlik Tablosu | ✅ Production | `PropertyAvailability` |
| Unified Calendar View | ❌ Yok | Yok |
| Multi-Property View | ❌ Yok | Yok |
| Google Calendar Sync | ❌ Yok | Yok |
| Team Calendar | ❌ Yok | Yok |

### 9.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Unified Calendar UI | 🔴 P0 | Tüm mülkler tek takvimde |
| Drag-Drop Reservations | 🔴 P0 | Rezervasyonları sürükle-bırak ile yönet |
| Availability Matrix | 🟠 P1 | Tüm mülkler × tüm tarihler matris görünümü |
| Conflict Alerts | 🟠 P1 | Çakışma uyarıları |
| Team Task Calendar | 🟡 P2 | Danışman görevleri takvimi |
| External Calendar Import | 🟡 P2 | Google, Outlook import |

### 9.3 Bağımlılıklar

```
Calendar
├── Reservation (rezervasyon verisi)
├── Airbnb (iCal senkronizasyonu)
├── Owner Portal (mal sahibi takvimi)
└── Portfolio (çoklu mülk görünümü)
```

---

## 10. Notifications (Bildirim Sistemi)

### 10.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Bildirim Modeli | ✅ Production | `Notification` |
| Telegram Entegrasyonu | ✅ Production | `TelegramNotification` |
| AI Önceliklendirme | ✅ AI-Powered | `CortexNotificationService` |
| Bildirim UI | ❌ Eksik | Sadece veritabanı var, UI yok |
| Bildirim Kuralları | ❌ Yok | Workflow tabanlı trigger yok |
| Push Notification | ❌ Yok | Mobil push yok |
| E-posta Template | ⚠️ Kısmi | Var ama yönetilebilir değil |

### 10.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Notification Center UI | 🔴 P0 | Tüm bildirimlerin merkezi yeri |
| Smart Notification Routing | 🔴 P0 | Doğru kişiye, doğru kanal, doğru zaman |
| Workflow-based Triggers | 🟠 P1 | "X olunca Y bildirimi gönder" kuralları |
| Notification Preferences | 🟠 P1 | Danışman bazlı tercihler |
| Escalation Rules | 🟠 P1 | Yanıtsız bildirim → yükseltme |
| Batch Digest | 🟡 P2 | Günlük/haftalık özet bildirimler |

### 10.3 Bağımlılıklar

```
Notifications
├── CRM (müşteri iletişim tercihleri)
├── Lead Management (lead aksiyonları)
├── Reservation (check-in/out bildirimleri)
├── Finance (ödeme bildirimleri)
└── Owner Portal (mal sahibi bildirimleri)
```

---

## 11. Dashboard (Yönetim Paneli)

### 11.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| Analitik Modeller | ✅ DB Ready | `AnalyticsDashboardFilter`, `AnalyticsMetric`, `AnalyticsReport` |
| Proje Sağlık Anlık Görüntüsü | ✅ Production | `ProjectHealthSnapshot` |
| AI Dashboard | ✅ Production | `GovernanceDashboardService` |
| Dashboard UI | ❌ Çok Sınırlı | Sadece temel admin stats |
| KPI Framework | ❌ Eksik | Standart KPI tanımları yok |
| Rapor Şablonları | ❌ Eksik | Yok |

### 11.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| Executive Dashboard | 🔴 P0 | C-level tek panel: tüm KPI |
| Realtime Metrics | 🔴 P0 | Şu an görüntülenme, anlık rezervasyon |
| Custom Report Builder | 🟠 P1 | Sürükle-bırak rapor oluşturucu |
| Comparison Reports | 🟠 P1 | Bu ay vs geçen ay, bu yıl vs geçen yıl |
| AI Insights Panel | 🟠 P1 | "Sistem ne yapmalı?" önerileri |
| White-label Reports | 🟡 P2 | Mal sahibi için özel raporlar |

### 11.3 Bağımlılıklar

```
Dashboard
├── Tüm domainler (veri kaynağı)
├── AI (insight generation)
└── Notifications (alert threshold)
```

---

## 12. AI Workforce (Yapay Zeka İş Gücü)

### 12.1 Mevcut Durum

| Bileşen | Durum | Detay |
|---------|-------|-------|
| AI Agent Run Tracking | ✅ Production | `AgentRun`, `AgentMemory` |
| Copilot Action Log | ✅ Production | `CopilotActionLog` |
| Buyer Match Engine | ✅ Production | 94 AI servisi içinde |
| Deal Prediction Engine | ✅ Production | `DealPredictionLog`, `DealPredictionSnapshot` |
| AI Prompt Yönetimi | ✅ Production | `AiPromptLog`, `AiExperiment` |
| AI Learning Signals | ✅ Production | `AiOgrenmeSinyali`, `SystemLearningTransaction` |
| Agent Orchestration | ❌ Konsept | Sadece modeller var |
| AI Audit Trail UI | ⚠️ Kısmi | `GovernanceDecision` var, UI yok |

### 12.2 Eksik Yetenekler

| Eksik | Öncelik | İş Değeri |
|-------|---------|-----------|
| AI Agent Dashboard | 🔴 P0 | Tüm AI ajanlarının merkezi yönetimi |
| Agent Task Assignment | 🔴 P0 | Görev atama, öncelik, durum takibi |
| AI Output Quality Scoring | 🟠 P1 | Her AI çıktısının doğruluk skoru |
| Autonomous Decision Framework | 🟠 P1 | AI hangi kararları tek başına alabilir? |
| Human-in-the-Loop Rules | 🟠 P1 | Hangi AI kararları insan onayı gerektirir? |
| AI Cost Tracking | 🟡 P2 | AI provider kullanım maliyeti |

### 12.3 Bağımlılıklar

```
AI Workforce
├── Tüm domainler (AI tüketici + üretici)
├── Governance (karar onay/red)
├── Finance (AI maliyet takibi)
└── Notifications (AI alert'leri)
```

---

## Özet Matrisi

| Capability | Mevcut Olgunluk | Eksik Kritik | İş Değeri | Öncelik | Sprint |
|------------|-----------------|--------------|----------|---------|--------|
| **CRM** | Early Stage | Pipeline Automation, Nurturing Sequences | Çok Yüksek | P0 | Sprint 7 |
| **Portfolio** | Early Stage | Performance Dashboard, Occupancy Tracking | Çok Yüksek | P0 | Sprint 6 |
| **Listings** | Production | Bulk Operations, Versioning, A/B Testing | Yüksek | P1 | Sprint 7 |
| **Lead Management** | Early Stage | Multi-Channel Capture, Nurturing | Çok Yüksek | P0 | Sprint 7 |
| **Owner Portal** | Concept | Portal UI, Real-time Dashboard | Çok Yüksek | P0 | Sprint 8 |
| **Airbnb** | Concept | API Integration, Two-Way Sync | Yüksek | P0 | Sprint 9 |
| **Reservation** | Early Stage | Workflow Engine, Conflict AI | Yüksek | P0 | Sprint 8 |
| **Finance** | Early Stage | Dashboard, Automated Booking | Çok Yüksek | P0 | Sprint 6 |
| **Calendar** | Concept | Unified Calendar UI, Drag-Drop | Yüksek | P1 | Sprint 8 |
| **Notifications** | Early Stage | Notification Center UI, Smart Routing | Yüksek | P1 | Sprint 7 |
| **Dashboard** | Concept | Executive Dashboard, Realtime Metrics | Çok Yüksek | P0 | Sprint 6 |
| **AI Workforce** | Concept | Agent Dashboard, Orchestration | Çok Yüksek | P1 | Sprint 9 |

**Sistem Olgunluk Skoru:** ~35% — Güçlü altyapı, zayıf iş yetenekleri UI/automation

---

*Document: PRODUCT_CAPABILITY_MAP.md — SAAB v6 Product Office*
*Generated: 2026-06-28*
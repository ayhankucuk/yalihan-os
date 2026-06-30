# CAPABILITY BACKLOG v2 — SAAB v6
**Hazırlayan:** Chief Product Officer — Yalıhan AI OS  
**Versiyon:** 2.0.0  
**Tarih:** 2026-06-28  
**Durum:** ACTIVE  

---

## Versiyon Geçmişi

| Versiyon | Tarih | Değişiklik |
|----------|-------|-----------|
| v1.0 | 2026-06-25 | İlk taslak — Sprint 3-6 odaklı |
| v2.0 | 2026-06-28 | SAAB v6 Product Office — 12 yetenek, 3 sprint grubu |

---

## Backlog Özeti

| Öncelik | Sayı | Toplam İş |
|---------|------|-----------|
| 🔴 P0 | 28 | Kritik — 3 ayda bitmeli |
| 🟠 P1 | 35 | Yüksek — 6 ayda bitmeli |
| 🟡 P2 | 42 | Orta — 12 ayda bitmeli |
| ⚪ P3 | 18 | Düşük — iyileştirme |
| **TOPLAM** | **123** | |

---

## GRUP A — ÜST ÖNCELİKLİ (Sprint 6-7)

### A1. Executive Dashboard 🔴 P0

**Neden Kritik:** Yönetim kararı için tüm KPI tek panelde görünmeli. Bu yoksa sistem profesyonel yönetilemez.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| A1.1 | Executive KPI Panel | Portföy, gelir, doluluk, lead — tek panel | Tüm domainler | 8 gün |
| A1.2 | Realtime Metrics | Anlık görüntülenme, rezervasyon, gelir | Analytics models | 5 gün |
| A1.3 | Comparison Engine | Bu ay vs geçen ay, YoY, MoM | Analytics models | 5 gün |
| A1.4 | AI Insights Panel | "Sistem ne yapmalı?" öneri panosu | AI Domain | 6 gün |
| A1.5 | Alert Thresholds | KPI eşik değeri aşılınca bildirim | Notifications | 3 gün |

**Business Value:** Yönetim karar hızı +40%, veriye dayalı strateji

### A2. Financial Dashboard + Automated Booking 🔴 P0

**Neden Kritik:** Finansal şeffaflık olmadan mal sahibi güveni kazanılamaz. Otomasyon olmadan operasyonel maliyet çok yüksek.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| A2.1 | Financial Dashboard | Gelir/gider/kar, nakit akışı | Finance models | 8 gün |
| A2.2 | Automated Booking | Rezervasyon → otomatik ledger kaydı | Reservation | 6 gün |
| A2.3 | Owner Payment Engine | Mal sahibi ödeme hesaplama + bildirim | Owner Portal | 8 gün |
| A2.4 | Cash Flow Forecast | AI: Gelecek 3 ay nakit akışı tahmini | Finance AI | 5 gün |
| A2.5 | Invoice Generation | AI destekli fatura üretimi | Finance | 5 gün |
| A2.6 | Tax Reporting | KDV, stopaj raporları | Finance | 4 gün |

**Business Value:** Manuel muhasebe süresi -70%, mal sahibi memnuniyeti +35%

### A3. Portfolio Performance Dashboard 🔴 P0

**Neden Kritik:** Portföy performansı görünür değilse, acquisition/disposition kararları sezgiye kalır.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| A3.1 | Portfolio KPI Dashboard | Tüm mülkler tek panelde | Portfolio models | 8 gün |
| A3.2 | Occupancy Rate Tracking | Günlük/aylık doluluk oranı | Reservation | 5 gün |
| A3.3 | Revenue per Listing | İlan bazlı gelir analizi | Finance | 5 gün |
| A3.4 | Portfolio Health Score | AI destekli sağlık skoru (0-100) | PortfolioDoctor | 6 gün |
| A3.5 | Competitive Analysis | Bölge bazlı piyasa karşılaştırması | Intelligence | 8 gün |
| A3.6 | Cap Rate Calculator | NOI / mülk değeri hesaplayıcı | Finance | 3 gün |

**Business Value:** Portföy getirisi +15%, acquisition başarısı +25%

### A4. CRM Pipeline Automation 🔴 P0

**Neden Kritik:** Lead'ler kaybediliyor çünkü takip manuel. Her 24 saat gecikme conversion oranını -8% düşürüyor.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| A4.1 | Pipeline Automation | Lead aşama geçişleri otomatik | CRM models | 8 gün |
| A4.2 | Lead Nurturing Sequences | Davranışa göre otomatik besleme | Notifications | 10 gün |
| A4.3 | Multi-Channel Capture | Web, Airbnb, telefon, Telegram | CRM, Notifications | 8 gün |
| A4.4 | Lead Distribution Rules | Otomatik danışmana atama | CRM, User | 5 gün |
| A4.5 | Lead Qualification AI | AI: Bu lead gerçekten alıcı mı? | AILeadScore | 6 gün |
| A4.6 | Source Attribution | Hangi kanal hangi lead'i getirdi? | CRM | 4 gün |
| A4.7 | Re-engagement Campaigns | Dormant lead'lere yeniden ulaşma | CRM, Notifications | 6 gün |

**Business Value:** Lead conversion +30%, takip süresi -60%

### A5. Owner Portal UI 🔴 P0

**Neden Kritik:** Mal sahibi portali yok = müşteri kaybı. Rakipler sahip oldukları mülklerini 7/24 izleyebiliyor.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| A5.1 | Owner Authentication | Self-service login + yetkilendirme | User, Kisi | 6 gün |
| A5.2 | Property Performance Dashboard | Mal sahibine özel KPI | Portfolio, Finance | 8 gün |
| A5.3 | Real-time Notifications | Anlık gelir, müsaitlik bildirimleri | Notifications | 5 gün |
| A5.4 | Document Vault | Tapu, sigorta, bakım belgeleri upload | Property | 6 gün |
| A5.5 | Maintenance Requests | Mal sahibinden bakım talebi | CRM | 5 gün |
| A5.6 | Payment History | Kiralama gelir ödemeleri | Finance | 5 gün |
| A5.7 | Availability Calendar | Mal sahibine özel takvim | Calendar | 5 gün |
| A5.8 | Dynamic Pricing Suggestions | AI: Bu sezon ne fiyat vermeli? | AI Domain | 6 gün |

**Business Value:** Mal sahibi memnuniyeti +50%, retention +25%

### A6. Reservation Workflow Engine 🔴 P0

**Neden Kritik:** Manuel rezervasyon yönetimi hata oranı çok yüksek. Çakışma = müşteri kaybı + itibar riski.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| A6.1 | Reservation State Machine | Talep → Onay → Ödeme → Check-in → Check-out | Reservation models | 8 gün |
| A6.2 | Conflict Resolution AI | Çakışan rezervasyonları AI çözsün | AI Domain | 6 gün |
| A6.3 | Dynamic Pricing Engine | Rekabet, talep, mevsim bazlı otomatik fiyat | AI Domain | 10 gün |
| A6.4 | Guest Pre-boarding | Misafir bilgi formu, kimlik kaydı | CRM | 6 gün |
| A6.5 | Housekeeping Integration | Check-out → temizlik bildirimi | Notifications, n8n | 5 gün |
| A6.6 | Payment Collection | Online ödeme, depozito, остаток | Finance | 8 gün |
| A6.7 | Cancellation Policy Engine | Kurallara göre otomatik iade | Finance | 5 gün |

**Business Value:** Rezervasyon hatası -90%, müşteri memnuniyeti +40%

---

## GRUP B — ORTA ÖNCELİKLİ (Sprint 8-9)

### B1. Airbnb Two-Way Integration 🔴 P0

**Neden Kritik:** Airbnb en büyük gelir kanalı. Manuel sync = takvim hataları = double-booking = felaket.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| B1.1 | Airbnb API Integration | Airbnb Channel Manager API | Airbnb credentials | 15 gün |
| B1.2 | Two-Way Calendar Sync | Airbnb ↔ Yalıhan takvim senkronizasyonu | Calendar | 10 gün |
| B1.3 | Listing Push | İlan bilgisi Airbnb'ye otomatik | Listings | 8 gün |
| B1.4 | Review Management | Airbnb değerlendirmelerini yönet | CRM | 6 gün |
| B1.5 | Pricing Sync | Yalıhan fiyat ↔ Airbnb fiyat | Finance | 8 gün |
| B1.6 | Smart Pricing AI | AI: Rekabetçi fiyat önerisi | AI Domain | 8 gün |
| B1.7 | Multi-OTA Support | Booking.com, Vrbo, Expedia | Calendar | 15 gün |

**Business Value:** Airbnb geliri +35%, hata oranı -95%

### B2. Unified Calendar UI 🟠 P1

**Neden Kritik:** Tüm mülklerin müsaitliğini görmek için Excel'e bakılıyor. Bu kabul edilemez.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| B2.1 | Multi-Property Calendar | Tüm mülkler tek takvimde | Reservation, Portfolio | 10 gün |
| B2.2 | Drag-Drop Reservations | Sürükle-bırak ile rezervasyon yönetimi | Reservation | 8 gün |
| B2.3 | Availability Matrix | Tüm mülkler × tüm tarihler | Reservation | 8 gün |
| B2.4 | Conflict Detection | Çakışma uyarıları | AI Domain | 5 gün |
| B2.5 | Team Task Calendar | Danışman görevleri takvimi | CRM | 6 gün |
| B2.6 | External Calendar Import | Google, Outlook, iCal import | Calendar | 5 gün |

**Business Value:** Operasyonel verimlilik +50%, hata oranı -80%

### B3. Notification Center + Smart Routing 🟠 P1

**Neden Kritik:** Bildirimler e-postaya gidiyor, kimse takip etmiyor. Merkezi yönetim yok.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| B3.1 | Notification Center UI | Tüm bildirimlerin merkezi yeri | Notifications model | 8 gün |
| B3.2 | Smart Routing Engine | Doğru kişiye, doğru kanal, doğru zaman | CRM | 6 gün |
| B3.3 | Workflow Triggers | "X olunca Y bildirimi gönder" kuralları | AI Domain | 8 gün |
| B3.4 | Notification Preferences | Danışman bazlı tercihler | User | 4 gün |
| B3.5 | Escalation Rules | Yanıtsız bildirim → yükseltme | Notifications | 5 gün |
| B3.6 | Batch Digest | Günlük/haftalık özet bildirimler | Notifications | 4 gün |
| B3.7 | Analytics Dashboard | Bildirim etkileşim analitiği | Analytics | 4 gün |

**Business Value:** Takip oranı +60%, yanıt süresi -40%

### B4. Listings Bulk Operations + Versioning 🟠 P1

**Neden Kritik:** 47 mülkü tek tek güncellemek 4 saat sürüyor. İlan değişiklik geçmişi yok.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| B4.1 | Bulk Update Operations | Toplu fiyat, durum, kategori güncelleme | Listings | 8 gün |
| B4.2 | Listing Versioning | Değişiklik geçmişi, diff, rollback | Listings | 8 gün |
| B4.3 | A/B Testing Framework | Farklı açıklamaları test et | AI Domain | 10 gün |
| B4.4 | Automated Quality Score | AI: Her ilan için kalite skoru | AI Domain | 6 gün |
| B4.5 | SEO Automation | Meta tag, structured data, sitemap | Listings | 8 gün |
| B4.6 | Performance Analytics | Görüntülenme → lead dönüşüm hunisi | Analytics | 6 gün |
| B4.7 | Scheduled Publishing | Belirli tarihte otomatik yayın | Listings | 5 gün |

**Business Value:** Operasyonel süre -75%, listing kalitesi +40%

### B5. AI Workforce Command Center 🟠 P1

**Neden Kritik:** 94 AI servisi var ama yönetimi yok. Hangi ajan ne yapıyor, kim kontrol ediyor?

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| B5.1 | Agent Dashboard | Tüm AI ajanlarının merkezi yönetimi | AgentRun, AgentMemory | 10 gün |
| B5.2 | Task Assignment Engine | Görev atama, öncelik, durum takibi | AI Domain | 8 gün |
| B5.3 | Output Quality Scoring | Her AI çıktısının doğruluk skoru | AI Domain | 6 gün |
| B5.4 | Decision Framework | AI hangi kararları tek başına alabilir? | Governance | 8 gün |
| B5.5 | Human-in-the-Loop Rules | Hangi AI kararları insan onayı gerektirir? | Governance | 6 gün |
| B5.6 | AI Cost Tracking | AI provider kullanım maliyeti | Finance | 5 gün |
| B5.7 | Multi-Provider UI | DeepSeek/Ollama/OpenAI yönetimi | AI Domain | 8 gün |

**Business Value:** AI güvenilirliği +50%, maliyet kontrolü +35%

### B6. CRM Segmentation + Customer 360 🟠 P1

**Neden Kritik:** Müşteri segmentasyonu yok. Herkese aynı mesaj gidiyor. Conversion düşük.

| ID | Yetenek | Açıklama | Bağımlılık | Tahmin |
|----|---------|----------|------------|--------|
| B6.1 | Behavioral Segmentation | AI: Müşterileri davranışa göre segmentlere ayır | AI Domain | 8 gün |
| B6.2 | Customer 360 View | Tek panelde tüm müşteri bilgisi | CRM | 10 gün |
| B6.3 | Lifetime Value Scoring | Müşteri yaşam boyu değeri hesaplama | AI Domain | 6 gün |
| B6.4 | Workflow Rules Engine | Tetikleyici tabanlı aksiyon | AI Domain | 8 gün |
| B6.5 | Duplicate Detection | Aynı kişi birden fazla kayıtta | CRM | 5 gün |
| B6.6 | Interaction Templates | Standart etkileşim şablonları | CRM | 4 gün |

**Business Value:** Marketing ROI +45%, müşteri retention +30%

---

## GRUP C — DÜŞÜK ÖNCELİKLİ (Sprint 10+)

### C1. Lead Management Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C1.1 | Lead Triage AI | AI: Hangi lead önce? | 6 gün |
| C1.2 | Lead Import/Export | CSV/API ile toplu import | 5 gün |
| C1.3 | Lead Scoring Calibration | AI lead scoring doğruluğu tuning | 4 gün |
| C1.4 | Lead Activity Timeline | Tüm etkileşimlerin zaman çizelgesi | 5 gün |

### C2. Listings Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C2.1 | Listing Clone | Mevcut ilandan yeni oluşturma | 3 gün |
| C2.2 | QR Code Generation | İlan için dinamik QR kod | 3 gün |
| C2.3 | Virtual Tour Integration | 360° tur embedding | 8 gün |
| C2.4 | Listing Comparison | En fazla 4 ilanı yan yana karşılaştır | 5 gün |

### C3. Portfolio Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C3.1 | Acquisition Recommendations | AI: Hangi mülkler alınmalı? | 8 gün |
| C3.2 | Disposition Recommendations | AI: Hangi mülkler satılmalı? | 8 gün |
| C3.3 | Portfolio Diversification | Segment/coğrafi dağılım analizi | 6 gün |
| C3.4 | Portfolio Benchmarking | Sektör ortalaması ile karşılaştırma | 5 gün |

### C4. Finance Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C4.1 | Multi-currency Handling | TL/EUR/USD otomatik çevrim | 6 gün |
| C4.2 | P&L per Property | İlan bazlı kar/zarar raporu | 5 gün |
| C4.3 | Budget Planning | Sezon/bütçe planlama | 8 gün |
| C4.4 | Financial Forecasting | AI: 12 ay finansal tahmin | 10 gün |

### C5. Owner Portal Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C5.1 | Owner Communication Hub | Doğrudan mesajlaşma | 6 gün |
| C5.2 | Dynamic Pricing Dashboard | AI fiyat önerisi arayüzü | 5 gün |
| C5.3 | Expense Sharing Reports | Mal sahibi/gayrimenkul gider dağılımı | 6 gün |
| C5.4 | Tax Document Generation | Mal sahibi için vergi belgeleri | 5 gün |

### C6. AI Workforce Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C6.1 | AI Learning Loop | Sistem deneyimlerden öğrensin | 15 gün |
| C6.2 | AI Self-Healing | Sistem kendi kendini düzeltsin | 20 gün |
| C6.3 | AI Experiment Tracking | A/B test sonuçları izleme | 8 gün |

### C7. Notifications Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C7.1 | Push Notifications | Mobil push notification | 6 gün |
| C7.2 | SMS Integration | SMS bildirim kanalı | 5 gün |
| C7.3 | WhatsApp Integration | WhatsApp bildirim kanalı | 8 gün |
| C7.4 | Notification Templates | Görsel bildirim şablonları | 4 gün |

### C8. Dashboard Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C8.1 | Custom Report Builder | Sürükle-bırak rapor oluşturucu | 12 gün |
| C8.2 | Mobile Dashboard | Mobil-öncelikli KPI görünümü | 8 gün |
| C8.3 | White-label Reports | Mal sahibi için özel raporlar | 8 gün |
| C8.4 | Scheduled Reports | Otomatik dönemsel raporlar | 5 gün |

### C9. Calendar Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C9.1 | Availability API | Web sitesi için müsaitlik API | 6 gün |
| C9.2 | Team Availability | Takım müsaitlik planlaması | 5 gün |
| C9.3 | Google Calendar Sync | Google Calendar two-way sync | 8 gün |

### C10. Airbnb Enhancements 🟡 P2

| ID | Yetenek | Açıklama | Tahmin |
|----|---------|----------|--------|
| C10.1 | Guest Communication | Airbnb mesajlarını tek panelden yönet | 10 gün |
| C10.2 | Occupancy Analytics | Airbnb özel analitik | 6 gün |
| C10.3 | Pricing Optimization | AI: Fiyat optimizasyonu | 8 gün |

---

## Sprint Planı

### Sprint 6 (Ay 1-2) — Foundation Sprint
```
HAFTA 1-4:
├── A1: Executive Dashboard (21 gün)
├── A2: Financial Dashboard + Automated Booking (26 gün)
└── A3: Portfolio Performance Dashboard (25 gün)

ÇIKTI: Yönetim kararları için tek kaynak
```

### Sprint 7 (Ay 2-3) — Revenue Sprint
```
HAFTA 5-8:
├── A4: CRM Pipeline Automation (37 gün)
├── A5: Owner Portal UI (35 gün)
├── A6: Reservation Workflow Engine (33 gün)
└── B3: Notification Center + Smart Routing (29 gün)

ÇIKTI: Operasyonel otomasyon tamamlandı
```

### Sprint 8 (Ay 3-4) — Integration Sprint
```
HAFTA 9-12:
├── B1: Airbnb Two-Way Integration (52 gün)
├── B2: Unified Calendar UI (36 gün)
└── B4: Listings Bulk Operations (35 gün)

ÇIKTI: Airbnb + takvim entegrasyonu tamamlandı
```

### Sprint 9 (Ay 4-5) — Intelligence Sprint
```
HAFTA 13-16:
├── B5: AI Workforce Command Center (41 gün)
├── B6: CRM Segmentation + Customer 360 (31 gün)
└── A4.5: Lead Qualification AI (6 gün)

ÇIKTI: AI yönetimi ve segmentasyon tamamlandı
```

### Sprint 10+ (Ay 5+) — Enhancement Sprint
```
HAFTA 17+:
├── C1-C10: Düşük öncelikli iyileştirmeler
└── Backlog refinement: Yeni yetenek keşfi
```

---

## Kaynak Planı

| Rol | Sprint 6-7 | Sprint 8-9 | Sprint 10+ |
|-----|-----------|-----------|-----------|
| Senior Backend (Laravel) | 1 | 1 | 1 |
| Frontend (Blade/Alpine) | 1 | 1 | 0.5 |
| AI Engineer | 0.5 | 1 | 0.5 |
| QA | 0.5 | 1 | 0.5 |
| Product Owner | 0.5 | 0.5 | 0.25 |

---

## Başarı Kriterleri

### Sprint 6 Sonu
- [ ] Executive Dashboard: Tüm KPI tek panelde ✅
- [ ] Financial Dashboard: Gelir/gider/kar görünür ✅
- [ ] Portfolio Dashboard: Doluluk, gelir, sağlık skoru görünür ✅
- [ ] 0 P0 item açık

### Sprint 7 Sonu
- [ ] Owner Portal: Mal sahibi giriş yapabiliyor ✅
- [ ] CRM Pipeline: Lead otomatik takip ediliyor ✅
- [ ] Reservation Engine: Çakışma yok, workflow tam ✅
- [ ] Notification Center: Merkezi bildirim yönetimi ✅
- [ ] 0 P0 item açık

### Sprint 9 Sonu
- [ ] Airbnb sync: İki yönlü takvim senkronizasyonu ✅
- [ ] Calendar: Tüm mülkler tek takvimde ✅
- [ ] AI Workforce: Agent dashboard çalışıyor ✅
- [ ] CRM Segmentation: Otomatik segmentasyon ✅
- [ ] Sistem Olgunluk Skoru: 35% → 70%+

---

## Risk Register

| Risk | Olasılık | Etki | Önlem |
|------|----------|------|-------|
| Airbnb API değişikliği | Orta | Yüksek | Abstraction layer + mock testing |
| AI hallucination | Orta | Orta | Human-in-the-loop + governance |
| Veritabanı migration karmaşıklığı | Yüksek | Yüksek | Incremental migration + rollback plan |
| AI maliyet sınırı aşımı | Orta | Orta | Budget alert + cost tracking |
| Çoklu OTA senkron hatası | Orta | Yüksek | Conflict detection + manual override |

---

*Document: CAPABILITY_BACKLOG_v2.md — SAAB v6 Product Office*
*Generated: 2026-06-28*
*Next Review: 2026-07-28*
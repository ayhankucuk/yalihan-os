# CAPABILITY DIRECTORY — SAAB v6
**Hazırlayan:** Product Office — SAAB v6  
**Versiyon:** 1.0.0  
**Tarih:** 2026-06-28  
**Domain Sayısı:** 17  
**Tamamlanma:** 2027-06-30  

---

## DOMAIN YETENEK MATRISI

| # | Domain | Kısaltma | Sahip Ofis | Öncelik | Sprint | Durum |
|---|--------|----------|------------|---------|--------|-------|
| 1 | Listings | LIST | Product Office | 🔴 P0 | 7 | PRODUCTION |
| 2 | Properties | PROP | Product Office | 🔴 P0 | 6 | PRODUCTION |
| 3 | Leads | LEAD | Sales Office | 🔴 P0 | 7 | EARLY_STAGE |
| 4 | Contacts | CONT | CRM Office | 🔴 P0 | 7 | PRODUCTION |
| 5 | Customers | CUST | CRM Office | 🟠 P1 | 8 | EARLY_STAGE |
| 6 | Owners | OWNR | Owner Office | 🔴 P0 | 7 | CONCEPT |
| 7 | Tasks | TASK | Operations Office | 🟠 P1 | 10 | EARLY_STAGE |
| 8 | Calendar | CAL | Operations Office | 🔴 P0 | 8 | CONCEPT |
| 9 | Documents | DOC | Operations Office | 🟠 P1 | 9 | CONCEPT |
| 10 | Contracts | CONT | Legal Office | 🟠 P1 | 10 | CONCEPT |
| 11 | Finance | FIN | Finance Office | 🔴 P0 | 6 | EARLY_STAGE |
| 12 | AI Workspace | AIW | AI Office | 🟠 P1 | 9 | CONCEPT |
| 13 | Dashboard | DASH | Analytics Office | 🔴 P0 | 6 | CONCEPT |
| 14 | Notifications | NOTF | Product Office | 🟠 P1 | 7 | EARLY_STAGE |
| 15 | Search | SCH | Product Office | 🟠 P1 | 9 | EARLY_STAGE |
| 16 | Reports | REPT | Analytics Office | 🟠 P1 | 10 | CONCEPT |
| 17 | Integration | INTG | Engineering Office | 🔴 P0 | 8 | CONCEPT |

---

## YETENEK DETAYLARI

---

### 1. LISTINGS (İlan Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | LIST |
| **Amaç** | Gayrimenkul ilanlarının yaşam döngüsü — oluşturma, düzenleme, yayınlama, arşivleme |
| **Sahip Ofis** | Product Office |
| **Business Value** | Ana ürün — tüm gelir kaynaklarının başlangıç noktası |
| **Birincil Kullanıcılar** | Danışmanlar, Admin, AI Sistemi |
| **Girdiler** | Özellik şeması, lokasyon, fiyat, medya, AI önerileri |
| **Çıktılar** | Yayınlanmış ilan, çeviriler, embedding vektörleri, fiyat geçmişi |
| **AI Fırsatları** | Açıklama üretimi, fiyat önerisi, kalite skoru, A/B test |
| **İnsan Onay Gereksinimleri** | Yayınlama, fiyat değişikliği >%10, silme |
| **Bağımlılıklar** | Feature/Template Domain, Location Domain, AI Domain |
| **Gelecek Genişleme** | VR tur entegrasyonu, drone görüntüleri, video ilan |

**Mevcut Durum:**
- ✅ IlanCrudService — tek write authority
- ✅ ListingStateMachine — state geçişleri
- ✅ ListingTranslation — 6 dil
- ✅ IlanEmbedding — vektör arama
- 🔄 Bulk Operations — eksik
- 🔄 Versioning — eksik
- 🔄 A/B Testing — eksik

---

### 2. PROPERTIES (Mülk Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | PROP |
| **Amaç** | Fiziksel mülklerin master data yönetimi — detaylar, müsaitlik, fiyatlandırma |
| **Sahip Ofis** | Product Office |
| **Business Value** | Portföy yönetimi ve performans izleme |
| **Birincil Kullanıcılar** | Portföy yöneticileri, Owner Portal, AI |
| **Girdiler** | İlan verileri, sezon tanımları, rakip verileri |
| **Çıktılar** | Mülk profili, müsaitlik takvimi, sezonluk fiyatlar |
| **AI Fırsatları** | Portfolio Doctor, acquisition/disposition önerisi, dinamik fiyat |
| **İnsan Onay Gereksinimleri** | Fiyat değişikliği, mülk silme, özellik ekleme |
| **Bağımlılıklar** | Listings, Finance, Calendar, AI Domain |
| **Gelecek Genişleme** | IoT entegrasyonu, enerji yönetimi, bakım tahmini |

**Mevcut Durum:**
- ✅ YazlikDetail, YazlikFiyatlandirma — master data
- ✅ PropertyAvailability — müsaitlik
- ✅ PropertySeasonalRate — sezonluk fiyat
- 🔄 Portfolio Dashboard — eksik
- 🔄 Occupancy Tracking — eksik
- 🔄 Competitive Analysis — eksik

---

### 3. LEADS (Potansiyel Müşteri Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | LEAD |
| **Amaç** | Potansiyel alıcıların keşfi, puanlanması, beslenmesi ve dönüştürülmesi |
| **Sahip Ofis** | Sales Office |
| **Business Value** | Gelir hunisinin en üstü — conversion'un başlangıcı |
| **Birincil Kullanıcılar** | Satış ekibi, AI Sistemi |
| **Girdiler** | Web form, Airbnb mesaj, telefon, Telegram, SavedSearch |
| **Çıktılar** | Puanlanmış lead, nurturing sequence, danışman ataması |
| **AI Fırsatları** | Lead scoring, qualification, nurturing automation, intent prediction |
| **İnsan Onay Gereksinimleri** | Manuel arama, özel teklif, görüşme planlama |
| **Bağımlılıkler** | Contacts, CRM Intelligence, Notifications, AI Domain |
| **Gelecek Genişleme** | Predictive lead scoring, intent signals, social listening |

**Mevcut Durum:**
- ✅ Lead, LeadActivity, LeadMessage — modeller
- ✅ AILeadScore — AI puanlama
- 🔄 Pipeline Automation — eksik
- 🔄 Nurturing Sequences — eksik
- 🔄 Multi-Channel Capture — eksik

---

### 4. CONTACTS (Kişi Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | CONT |
| **Amaç** | Tüm kişi verilerinin tek kaynaktan yönetimi |
| **Sahip Ofis** | CRM Office |
| **Business Value** | Müşteri bilgisi tek kaynak — veri tutarlılığı |
| **Birincil Kullanıcılar** | Tüm ofisler |
| **Girdiler** | Lead dönüşümü, manuel giriş, import |
| **Çıktılar** | Kişi profili, etkileşim geçmişi, segmentasyon |
| **AI Fırsatları** | Duplicate detection, behavioral scoring, sentiment analysis |
| **İnsan Onay Gereksinimleri** | Kişi birleştirme, silme, segment değişikliği |
| **Bağımlılıklar** | Leads, Customers, Communications, AI Domain |
| **Gelecek Genişleme** | Kişi veri zenginleştirme (external APIs), social profiles |

**Mevcut Durum:**
- ✅ Kisi modeli — 18K satır kod
- ✅ KisiEtkilesim, KisiAktivite — etkileşim kaydı
- ⚠️ CRM V1/V2 çakışması — legacy risk
- 🔄 Customer 360 View — eksik
- 🔄 Segmentation — eksik

---

### 5. CUSTOMERS (Müşteri Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | CUST |
| **Amaç** | Satın almış veya kiralama yapmış müşterilerin yaşam boyu yönetimi |
| **Sahip Ofis** | CRM Office |
| **Business Value** | Repeat business, referral, lifetime value |
| **Birincil Kullanıcılar** | CRM ekibi, Owner Portal |
| **Girdiler** | Reservation, Talep, ödeme kayıtları |
| **Çıktılar** | Müşteri profili, lifetime value, tercihler |
| **AI Fırsatları** | Churn prediction, upsell/cross-sell, preference learning |
| **İnsan Onay Gereksinimleri** | Özel indirim, hesap değişikliği, şikayet |
| **Bağımlılıklar** | Contacts, Reservation, Finance, AI Domain |
| **Gelecek Genişleme** | Loyalty program, referral engine, VIP program |

**Mevcut Durum:**
- ⚠️ Kisi + Lead birleşik — ayrım net değil
- 🔄 Customer 360 View — eksik
- 🔄 Lifetime Value — eksik
- 🔄 Churn Prediction — eksik

---

### 6. OWNERS (Mal Sahibi Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | OWNR |
| **Amaç** | Mal sahiplerinin mülk performansı, ödemeler ve iletişim yönetimi |
| **Sahip Ofis** | Owner Office |
| **Business Value** | Owner retention, referral, portfolio growth |
| **Birincil Kullanıcılar** | Mal sahipleri, Owner Office ekibi |
| **Girdiler** | Property performansı, finansal veriler, takvim |
| **Çıktılar** | Owner dashboard, ödeme bildirimleri, raporlar |
| **AI Fırsatları** | Dynamic pricing suggestion, market insights, churn prevention |
| **İnsan Onay Gereksinimleri** | Fiyat kararı, sözleşme değişikliği, ödeme onayı |
| **Bağımlılıklar** | Properties, Finance, Calendar, Notifications |
| **Gelecek Genişleme** | Self-service portal v2, mobile app, AI advisor |

**Mevcut Durum:**
- ✅ OwnerReport tabloları — veritabanı hazır
- ✅ AnahtarYonetimi — model mevcut
- ❌ Portal UI — yok
- ❌ Real-time dashboard — yok
- ❌ Self-service login — yok

---

### 7. TASKS (Görev Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | TASK |
| **Amaç** | Ekip görevlerinin atanması, takibi ve tamamlanması |
| **Sahip Ofis** | Operations Office |
| **Business Value** | Ekip verimliliği, accountability, deadline yönetimi |
| **Birincil Kullanıcılar** | Tüm ekip üyeleri, yöneticiler |
| **Girdiler** | Workflow tetikleyicileri, manuel atama, AI önerileri |
| **Çıktılar** | Atanmış görev, ilerleme, tamamlama |
| **AI Fırsatları** | Priority scoring, deadline prediction, workload balancing |
| **İnsan Onay Gereksinimleri** | Görev atama, deadline değişikliği, görev silme |
| **Bağımlılıklar** | Users, Notifications, Calendar |
| **Gelecek Genişleme** | Gantt view, Kanban board, time tracking |

**Mevcut Durum:**
- ✅ Gorev modeli — mevcut
- ✅ TakimUyesi — takım yapısı
- 🔄 Task Dashboard — eksik
- 🔄 Automation rules — eksik
- 🔄 Dependencies — eksik

---

### 8. CALENDAR (Takvim Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | CAL |
| **Amaç** | Tüm mülklerin müsaitlik takvimi ve rezervasyon koordinasyonu |
| **Sahip Ofis** | Operations Office |
| **Business Value** | Double-booking önleme, müsaitlik optimizasyonu |
| **Birincil Kullanıcılar** | Operations, Owner Portal, Airbnb |
| **Girdiler** | Reservations, Airbnb iCal, manuel bloklar |
| **Çıktılar** | Müsaitlik durumu, çakışma uyarıları, iCal feed |
| **AI Fırsatları** | Smart pricing based on availability, gap detection |
| **İnsan Onay Gereksinimleri** | Manuel blok, çakışma çözümü |
| **Bağımlılıklar** | Properties, Reservation, Airbnb, AI Domain |
| **Gelecek Genişleme** | Google Calendar sync, team calendar, booking engine |

**Mevcut Durum:**
- ✅ IlanTakvimSync, PropertyCalendarFeed — modeller
- ✅ PropertyAvailability — müsaitlik
- ❌ Unified Calendar UI — yok
- ❌ Drag-Drop — yok
- ❌ Conflict Detection — yok

---

### 9. DOCUMENTS (Doküman Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | DOC |
| **Amaç** | Tüm iş dokümanlarının depolanması, sınıflandırılması ve erişimi |
| **Sahip Ofis** | Operations Office |
| **Business Value** | Uyumluluk, doküman bulunabilirliği, audit trail |
| **Birincil Kullanıcılar** | Legal, Operations, Owner Portal |
| **Girdiler** | Upload, template generation, AI summary |
| **Çıktılar** | Sınıflandırılmış doküman, versiyonlanmış dosya |
| **AI Fırsatları** | Document summarization, translation, classification |
| **İnsan Onay Gereksinimleri** | Hukuki doküman onayı, paylaşım yetkisi |
| **Bağımlılıklar** | Properties, Owners, Contracts, AI Domain |
| **Gelecek Genişleme** | E-signature, OCR scanning, automated filing |

**Mevcut Durum:**
- ❌ Document domain — yok
- 🔄 Document upload — bekleniyor
- 🔄 Template engine — bekleniyor
- 🔄 AI document processing — bekleniyor

---

### 10. CONTRACTS (Sözleşme Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | CONT |
| **Amaç** | Sözleşmelerin oluşturulması, imzalanması ve takibi |
| **Sahip Ofis** | Legal Office |
| **Business Value** | Hukuki uyumluluk, iş güvenliği, renewal yönetimi |
| **Birincil Kullanıcılar** | Legal, Sales, Owner Office |
| **Girdiler** | Template, taraflar, mülk bilgisi, AI önerileri |
| **Çıktılar** | İmzalanmış sözleşme, renewal tarihi, uyarılar |
| **AI Fırsatları** | Contract draft generation, risk analysis, renewal prediction |
| **İnsan Onay Gereksinimleri** | Tüm sözleşme onayları, değişiklikler |
| **Bağımlılıklar** | Properties, Owners, Customers, Documents |
| **Gelecek Genişleme** | E-signature, smart clauses, blockchain notary |

**Mevcut Durum:**
- ❌ Contract domain — yok
- 🔄 Contract templates — bekleniyor
- 🔄 AI generation — bekleniyor
- 🔄 Renewal tracking — bekleniyor

---

### 11. FINANCE (Finans Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | FIN |
| **Amaç** | Tüm finansal işlemlerin kaydedilmesi, raporlanması ve analizi |
| **Sahip Ofis** | Finance Office |
| **Business Value** | Finansal şeffaflık, karar desteği, uyumluluk |
| **Birincil Kullanıcılar** | Finance ekibi, Owner Portal, Yönetim |
| **Girdiler** | Reservations, expenses, payments, exchange rates |
| **Çıktılar** | Ledger entries, reports, forecasts |
| **AI Fırsatları** | Cash flow forecasting, anomaly detection, tax optimization |
| **İnsan Onay Gereksinimleri** | Büyük ödemeler, manuel düzeltmeler, bütçe değişikliği |
| **Bağımlılıklar** | Reservation, Properties, Owners, AI Domain |
| **Gelecek Genişleme** | Multi-currency, banking API, payment automation |

**Mevcut Durum:**
- ✅ LedgerEntry, LedgerAccount, LedgerBalance — muhasebe
- ✅ FxRate, TCMBCurrencyService — döviz
- ✅ RentalGelirKalemi, RentalGiderKalemi — gelir/gider
- 🔄 Financial Dashboard — eksik
- 🔄 Automated Booking — eksik
- 🔄 Owner Payment Engine — eksik

---

### 12. AI WORKSPACE (AI Çalışma Alanı)

| Alan | Değer |
|------|-------|
| **Kısaltma** | AIW |
| **Amaç** | AI sistemlerinin yönetimi, izlenmesi ve kontrolü |
| **Sahip Ofis** | AI Office |
| **Business Value** | AI güvenilirliği, maliyet kontrolü, çıktı kalitesi |
| **Birincil Kullanıcılar** | AI Office, Product Office, Engineering |
| **Girdiler** | AI prompt'ları, experiment tanımları, feedback |
| **Çıktılar** | AI önerileri, karar logları, maliyet raporları |
| **AI Fırsatları** | Self-learning, self-healing, autonomous decisions |
| **İnsan Onay Gereksinimleri** | Yüksek etkili AI kararları, model değişikliği |
| **Bağımlılıklar** | Tüm domainler, Governance, Finance |
| **Gelecek Genişleme** | Multi-model orchestration, custom fine-tuning |

**Mevcut Durum:**
- ✅ AgentRun, AgentMemory — izleme modelleri
- ✅ CopilotActionLog — aksiyon logları
- ✅ 94 AI servisi — altyapı hazır
- ❌ Agent Dashboard — yok
- ❌ Orchestration UI — yok
- ❌ Self-healing — yok

---

### 13. DASHBOARD (Yönetim Paneli)

| Alan | Değer |
|------|-------|
| **Kısaltma** | DASH |
| **Amaç** | Tüm iş KPI'larının tek panelden izlenmesi |
| **Sahip Ofis** | Analytics Office |
| **Business Value** | Hızlı karar, durum görünürlüğü, anomali tespiti |
| **Birincil Kullanıcılar** | Yönetim, tüm ofis müdürleri |
| **Girdiler** | Tüm domain verileri, AI analizleri |
| **Çıktılar** | KPI panosu, alert'ler, trendler |
| **AI Fırsatları** | Anomaly detection, prediction, recommendation |
| **İnsan Onay Gereksinimleri** | Alert eşik değişikliği, rapor düzenleme |
| **Bağımlılıklar** | Tüm domainler, AI Workspace, Notifications |
| **Gelecek Genişleme** | Mobile dashboard, customizable widgets |

**Mevcut Durum:**
- ✅ AnalyticsDashboardFilter, AnalyticsMetric — modeller
- ✅ ProjectHealthSnapshot — sistem sağlığı
- ✅ GovernanceDashboard — AI dashboard
- ❌ Executive Dashboard — yok
- ❌ Realtime Metrics — yok
- ❌ Custom Report Builder — yok

---

### 14. NOTIFICATIONS (Bildirim Sistemi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | NOTF |
| **Amaç** | Tüm bildirimlerin merkezi yönetimi ve yönlendirmesi |
| **Sahip Ofis** | Product Office |
| **Business Value** | Bilgi akışı, takip oranı, müşteri memnuniyeti |
| **Birincil Kullanıcılar** | Tüm kullanıcılar |
| **Girdiler** | Sistem olayları, AI önerileri, workflow tetikleyicileri |
| **Çıktılar** | Bildirimler (Telegram, e-posta, in-app) |
| **AI Fırsatları** | Smart routing, timing optimization, personalization |
| **İnsan Onay Gereksinimleri** | Bildirim kuralı oluşturma, toplu bildirim |
| **Bağımlılıklar** | Tüm domainler, CRM, AI Workspace |
| **Gelecek Genişleme** | SMS, WhatsApp, push notifications |

**Mevcut Durum:**
- ✅ Notification model — veritabanı hazır
- ✅ TelegramNotification — Telegram entegrasyonu
- ✅ CortexNotificationService — AI önceliklendirme
- ❌ Notification Center UI — yok
- ❌ Smart Routing — yok
- ❌ Workflow Triggers — yok

---

### 15. SEARCH (Arama Motoru)

| Alan | Değer |
|------|-------|
| **Kısaltma** | SCH |
| **Amaç** | Gayrimenkul ve müşteri verilerinde hızlı, semantik arama |
| **Sahip Ofis** | Product Office |
| **Business Value** | Kullanıcı deneyimi, conversion, verimlilik |
| **Birincil Kullanıcılar** | Danışmanlar, web sitesi ziyaretçileri |
| **Girdiler** | Kullanıcı sorguları, filtre parametreleri |
| **Çıktılar** | Arama sonuçları, öneriler, analytics |
| **AI Fırsatları** | Semantic search, voice search, intent understanding |
| **İnsan Onay Gereksinimleri** | Filtre kuralı değişikliği, sonuç sıralama |
| **Bağımlılıklar** | Listings, Contacts, AI Domain |
| **Gelecek Genişleme** | Voice search, image search, conversational search |

**Mevcut Durum:**
- ✅ IlanEmbedding — vektör arama altyapısı
- 🔄 Search UI — eksik
- 🔄 Filter Builder — eksik
- 🔄 Semantic search — eksik
- 🔄 Search analytics — eksik

---

### 16. REPORTS (Raporlama)

| Alan | Değer |
|------|-------|
| **Kısaltma** | REPT |
| **Amaç** | Standart ve özel raporların oluşturulması ve dağıtımı |
| **Sahip Ofis** | Analytics Office |
| **Business Value** | Performans izleme, müşteri raporları, uyumluluk |
| **Birincil Kullanıcılar** | Yönetim, ofis müdürleri, mal sahipleri |
| **Girdiler** | Tüm domain verileri, rapor şablonları |
| **Çıktılar** | Raporlar (PDF, Excel, in-app) |
| **AI Fırsatları** | Automated insight generation, natural language queries |
| **İnsan Onay Gereksinimleri** | Rapor şablonu değişikliği, dağıtım ayarları |
| **Bağımlılıklar** | Tüm domainler, Dashboard |
| **Gelecek Genişleme** | Ad-hoc query builder, data API |

**Mevcut Durum:**
- ✅ OwnerReport tabloları — ihracat hazır
- ✅ AnalyticsMetric, AnalyticsReport — modeller
- ❌ Report Builder — yok
- ❌ Scheduled reports — yok
- ❌ AI insights in reports — yok

---

### 17. INTEGRATION (Entegrasyon Yönetimi)

| Alan | Değer |
|------|-------|
| **Kısaltma** | INTG |
| **Amaç** | Üçüncü taraf sistemlerle (Airbnb, Booking, TKGM) veri senkronizasyonu |
| **Sahip Ofis** | Engineering Office |
| **Business Value** | Kanal genişlemesi, veri tutarlılığı, otomasyon |
| **Birincil Kullanıcılar** | Operations, Engineering |
| **Girdiler** | External API data, user actions |
| **Çıktılar** | Sync logs, conflict alerts, data mappings |
| **AI Fırsatları** | Smart conflict resolution, pricing sync optimization |
| **İnsan Onay Gereksinimleri** | Yeni entegrasyon, büyük veri değişikliği |
| **Bağımlılıklar** | Calendar, Properties, Finance, AI Domain |
| **Gelecek Genişleme** | Real-time sync, webhook management, ETL pipelines |

**Mevcut Durum:**
- ✅ N8N entegrasyonu — aktif
- ✅ TKGM entegrasyonu — aktif
- ✅ PropertyCalendarFeed — iCal hazır
- 🔄 Airbnb API — eksik
- 🔄 Multi-OTA — eksik
- 🔄 Conflict Resolution — eksik

---

## YETENEK SAHİPLİK MATRİSİ

```
                    PRODUCT  SALES  CRM   OWNER  OPS    FINANCE  AI     LEGAL  ANALYTICS  ENGINEERING
Listings           [██████] ────── ───── ───── ───── ───── ───── ───── ───── ───── ─────
Properties         [██████] ────── ───── ───── ───── ───── ───── ───── ───── ───── ─────
Leads              ────── [██████] ───── ───── ───── ───── ───── ───── ───── ───── ─────
Contacts           ────── ────── [██████] ───── ───── ───── ───── ───── ───── ───── ─────
Customers          ────── ────── [██████] ───── ───── ───── ───── ───── ───── ───── ─────
Owners             ────── ────── ────── [██████] ───── ───── ───── ───── ───── ───── ─────
Tasks              ────── ────── ────── ───── [██████] ───── ───── ───── ───── ───── ─────
Calendar           ────── ────── ────── ───── [██████] ───── ───── ───── ───── ───── ─────
Documents          ────── ────── ────── ───── [██████] ───── ───── ───── ───── ───── ─────
Contracts          ────── ────── ────── ───── ───── ───── ───── [██████] ───── ───── ─────
Finance            ────── ────── ────── ───── ───── [██████] ───── ───── ───── ───── ─────
AI Workspace       ────── ────── ────── ───── ───── ───── [██████] ───── ───── ───── ─────
Dashboard          ────── ────── ────── ───── ───── ───── ───── ───── [██████] ───── ─────
Notifications      [██████] ────── ────── ───── ───── ───── ───── ───── ───── ───── ─────
Search             [██████] ────── ────── ───── ───── ───── ───── ───── ───── ───── ─────
Reports            ────── ────── ────── ───── ───── ───── ───── ───── [██████] ───── ─────
Integration        ────── ────── ────── ───── ───── ───── ───── ───── ───── ───── [██████]
```

---

*Document: CAPABILITY_DIRECTORY.md — Product Office*
*Generated: 2026-06-28*
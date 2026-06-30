# YALIHAN OS — Domain Model

> ⚠️ **BU ARTIK PROJENİN EN KRİTİK DOKÜMANIDIR.**
> 
> SAB teknik kuralları yönetir.
> Domain Model iş nesnelerini ve ilişkilerini yönetir.
> Bundan sonraki tüm geliştirmeler bu model üzerinden büyüyecek.

---

## Temel Prensip

> "Bu modül tek başına satılabilir mi?"
> 
> Her yeni modül eklemeden önce bu soru sorulmalı.

| Modül | Tek Başına Satılabilir Mi? |
|-------|---------------------------|
| AI İlan Asistanı | ✅ Evet |
| CRM | ✅ Evet |
| Airbnb Entegrasyonu | ✅ Evet |
| Takvim | ✅ Evet |
| Muhasebe | ⚠️ Belki (modüler tasarlanmalı) |

---

## Domain Entities — Temel Nesneler

```
┌─────────────────────────────────────────────────────────────┐
│                      DOMAIN ENTITIES                        │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   TENANT     │  │  PROPERTY    │  │  LISTING     │     │
│  │  (Kiracı)   │  │   (Mülk)     │  │  (İlan)      │     │
│  │              │  │              │  │              │     │
│  │ tenant_id   │  │ property_id  │  │ listing_id  │     │
│  │ name        │  │ tenant_id   │  │ property_id │     │
│  │ plan        │  │ type        │  │ status      │     │
│  │ status      │  │ address     │  │ platform_ids│     │
│  │ credits     │  │ owner_id   │  │ ai_analysis │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│         │                   │                   │             │
│         │    ┌─────────────┼─────────────┐    │             │
│         │    │             │             │    │             │
│         ▼    ▼             ▼             ▼    ▼             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    LISTING                           │   │
│  │  Property üzerinde bir platform (Airbnb/Booking)    │   │
│  │  yayını. Her property birden fazla listing         │   │
│  │  oluşturabilir (çoklu platform stratejisi).      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  CUSTOMER   │  │RESERVATION  │  │    TASK      │     │
│  │   (Müşteri) │  │(Rezervasyon) │  │   (Görev)    │     │
│  │              │  │              │  │              │     │
│  │ customer_id  │  │ res_id      │  │ task_id     │     │
│  │ tenant_id   │  │ listing_id │  │ tenant_id   │     │
│  │ type        │  │ customer_id│  │ related_type│     │
│  │ segment     │  │ check_in   │  │ related_id  │     │
│  │ preferences │  │ check_out  │  │ assignee_id│     │
│  │ tags       │  │ status     │  │ status     │     │
│  └──────────────┘  │ source     │  │ due_date   │     │
│         │          │ total_price│  └──────────────┘     │
│         │          └──────────────┘          │             │
│         │                 │                  │             │
│         │    ┌────────────┼────────────┐     │             │
│         │    │            │            │     │             │
│         ▼    ▼            ▼            ▼     ▼             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   PAYMENT   │  │CALENDAR_EVT │  │  DOCUMENT    │     │
│  │  (Ödeme)    │  │ (Etkinlik)  │  │  (Belge)     │     │
│  │              │  │              │  │              │     │
│  │ payment_id  │  │ event_id   │  │ document_id │     │
│  │ tenant_id   │  │ tenant_id  │  │ tenant_id   │     │
│  │ customer_id│  │ property_id│  │ related_type│     │
│  │ res_id    │  │ res_id     │  │ related_id  │     │
│  │ amount    │  │ type       │  │ type        │     │
│  │ currency  │  │ start_dt   │  │ url         │     │
│  │ status    │  │ end_dt     │  │ ai_summary  │     │
│  │ method    │  │ status     │  └──────────────┘     │
│  └──────────────┘  └──────────────┘                    │
│         │                 │                               │
│         ▼                 ▼                               │
│  ┌──────────────────────────────────────────────────┐    │
│  │              CONTRACT (Sözleşme)                  │    │
│  │  Tenant ↔ Customer arasındaki anlaşmalar         │    │
│  │  Kira sözleşmesi, hizmet sözleşmesi              │    │
│  └──────────────────────────────────────────────────┘    │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │AI_CONVERSATION│  │AUTOMATION_JOB│  │  AI_ANALYSIS│  │
│  │(AI Sohbet)    │  │(Otomasyon)    │  │ (AI Analiz) │  │
│  │              │  │              │  │              │  │
│  │ conv_id      │  │ job_id       │  │ analysis_id │  │
│  │ tenant_id   │  │ tenant_id   │  │ tenant_id   │  │
│  │ customer_id │  │ trigger_type│  │ entity_type │  │
│  │ messages    │  │ payload     │  │ entity_id  │  │
│  │ ai_model   │  │ status      │  │ result      │  │
│  │ summary    │  │ result      │  │ model       │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## Entity İlişki Matrisi

```
ENTITIES →    Tenant  Property  Listing  Customer  Reservation  Payment  Task  Document  Contract  AI_Conv  Auto_Job  AI_Analysis
                                                                                                                                    
Tenant          —       ✓         ✓        ✓          ✓          ✓      ✓       ✓         ✓         ✓        ✓          ✓
Property        ✓        —         ✓        ✗          ✗          ✗      ✓       ✓         ✗         ✗        ✗          ✗
Listing         ✓        ✓         —        ✗          ✓          ✗      ✓       ✓         ✗         ✗        ✗          ✓
Customer        ✓        ✗         ✗        —          ✓          ✓      ✓       ✓         ✓         ✓        ✗          ✓
Reservation     ✓        ✗         ✓        ✓          —          ✓      ✓       ✗         ✗         ✗        ✗          ✗
Payment         ✓        ✗         ✗        ✓          ✓          —      ✗       ✗         ✗         ✗        ✗          ✗
Task            ✓        ✓         ✓        ✓          ✓          ✗      —       ✓         ✓         ✗        ✓          ✗
Document        ✓        ✓         ✓        ✓          ✗          ✗       ✓        —         ✓         ✗        ✗          ✗
Contract        ✓        ✗         ✗        ✓          ✗          ✗      ✗        ✓         —         ✗        ✗          ✗
AI_Conversation ✓       ✗         ✗        ✓          ✗          ✗      ✗        ✗         ✗         —        ✗          ✗
Automation_Job  ✓        ✗         ✗        ✗          ✗          ✗      ✓        ✗         ✗         ✗         —          ✗
AI_Analysis    ✓        ✗         ✓        ✓          ✗          ✗      ✗        ✗         ✗         ✗         ✗          —

✓ = Doğrudan ilişki var (foreign key)
✗ = Dolaylı ilişki yok veya yok
```

---

## Entity Detayları

### 1. TENANT (Kiracı / Müşteri Şirket)

```
Ana Nesne: YALIHAN OS'yi kullanan işletme

İlişkili:
├── Properties[]     → Bir tenant'ın birden fazla mülkü olur
├── Listings[]     → Bir tenant'ın birden fazla ilanı olur
├── Customers[]    → Bir tenant'ın birden fazla müşterisi olur
├── Users[]         → Tenant kullanıcıları (admin, danışman, sahip)
├── AI_Credits      → AI kullanım kredisi
└── Subscription    → Plan, dönem, ödeme durumu

Tek Başına Satılabilir: N/A (sistemin kendisi)
```

### 2. PROPERTY (Mülk)

```
Ana Nesne: Fiziksel gayrimenkul (villa, apart, butik)

İlişkili:
├── owner_id (Customer)   → Mülk sahibi
├── Listings[]          → Platform bazlı ilanlar
├── Reservations[]      → Bu mülke ait rezervasyonlar
├── Tasks[]             → Bakım, temizlik görevleri
├── Documents[]         → Tapu, sigorta, ruhsat
└── Calendar_Events[] → Operasyonel etkinlikler

Tek Başına Satılabilir: Hayır (Portföy yönetimi gerektirir)
```

### 3. LISTING (İlan)

```
Ana Nesne: Platform bazlı ilan (Airbnb, Booking, websitesi)

İlişkili:
├── property_id         → Hangi mülk
├── platform_id        → Airbnb ID, Booking ID
├── status              → yayında / yayında değil
├── ai_analysis        → AI analiz sonuçları
├── reservations[]      → Bu ilan üzerinden gelen rezervasyonlar
└── tasks[]            → İlan bakım görevleri

Tek Başına Satılabilir: ✅ Evet (AI İlan Asistanı)
```

### 4. CUSTOMER (Müşteri)

```
Ana Nesne: İş ilişkisi olan kişi veya kurum

Tipleri:
├── mulk_sahibi     → Villası olan, yönetim hizmeti alan
├── danisman        → Emlak danışmanı
├── misafir         → Kiralama müşterisi
├── tedarikci       → Temizlik, bakım şirketi
└── potansiyel      → Lead, henüz müşteri değil

İlişkili:
├── reservations[]      → Yapılmış rezervasyonlar
├── payments[]          → Ödemeler (gelir veya gider)
├── documents[]         → Sözleşmeler, kimlikler
├── contracts[]         → Kira sözleşmeleri
├── ai_conversations[]   → AI ile sohbet geçmişi
├── ai_analyses[]       → AI müşteri analizleri
├── tasks[]             → Bu müşteriye atanmış görevler
└── tags                → Segment, VIP, potansiyel, vs.

Tek Başına Satılabilir: ✅ Evet (CRM modülü)
```

### 5. RESERVATION (Rezervasyon)

```
Ana Nesne: Bir konaklama rezervasyonu

Durumları:
├── onay_bekliyor     → Talep geldi, onay bekliyor
├── onaylandi         → Ödeme bekleniyor veya alındı
├── checkin_yapildi   → Misafir mülkte
├── tamamlandi        → Konaklama bitti
└── iptal             → İptal edildi

Kaynakları:
├── airbnb            → Airbnb API'den
├── booking           → Booking.com'dan
├── telefon           → Manuel
├── websitesi         → Direk rezervasyon
└── diger             → Y渠道

İlişkili:
├── customer_id           → Misafir
├── listing_id           → Hangi ilan üzerinden
├── property_id (dolaylı)→ Hangi mülk
├── payments[]            → Bu rezervasyondan gelen ödemeler
├── tasks[]               → Check-in, check-out, temizlik görevleri
└── calendar_events[]     → Takvim etkinlikleri

Tek Başına Satılabilir: ✅ Evet (Takvim modülü)
```

### 6. PAYMENT (Ödeme)

```
Ana Nesne: Tüm finansal işlemler

Tipleri:
├── GELIR    → Misafir ödemesi, depozito
└── GIDER    → Temizlik, komisyon, bakım, maaş

İlişkili:
├── tenant_id         → Hangi tenant
├── customer_id       → Kim (müşteri veya tedarikçi)
├── reservation_id    → İlgili rezervasyon (varsa)
├── property_id       → İlgili mülk (varsa)
├── invoice_id        → Fatura (varsa)
└── category          → Temizlik, komisyon, vs.

Tek Başına Satılabilir: ⚠️ Belki (Finans modülü — karmaşık)
```

### 7. TASK (Görev)

```
Ana Nesne: Operasyonel görev

Tipleri:
├── temizlik    → Check-out sonrası temizlik
├── bakim       → Planlı veya acil bakım
├── kontrol     → Kontrol turu
├── checkin     → Check-in hazırlığı
├── checkout    → Check-out işlemleri
└── diger       → Genel görev

Durumları:
├── planlandi    → Atandı, henüz başlamadı
├── devam_ediyor  → Yapılıyor
├── tamamlandi    → Bitti
└── iptal         → İptal

İlişkili:
├── tenant_id         → Hangi tenant
├── property_id       → Hangi mülk
├── assignee_id        → Kime atandı (User veya Customer)
├── reservation_id     → İlgili rezervasyon (varsa)
├── related_type      → ilişkili entity tipi
├── related_id        → İlişkili entity ID
└── due_date         → Bitiş tarihi

Tek Başına Satılabilir: ✅ Evet (Operasyon modülü)
```

### 8. DOCUMENT (Belge)

```
Ana Nesne: Tüm dokümanlar

Tipleri:
├── sozlesme      → Kira sözleşmesi
├── kimlik         → TC kimlik, pasaport
├── fatura         → Alış veya satış faturası
├── makbuz         → Ödeme makbuzu
├── tapu           → Tapu senedi
├── ruhsat         → İşletme ruhsatı
├── sigorta         → Konut sigortası
├── fotoraf         → Hasar fotoğrafları, villa fotoğrafları
└── diger          → Diğer

İlişkili:
├── tenant_id         → Hangi tenant
├── uploaded_by_id     → Kim yükledi
├── related_type       → Hangi entity ile ilişkili
├── related_id         → İlişkili entity ID
├── ai_summary        → AI belge özeti
└── ai_extracted_data → AI çıkarılmış veri

Tek Başına Satılabilir: ⚠️ Belki (Doküman yönetimi — iş süreçlerine bağlı)
```

### 9. CONTRACT (Sözleşme)

```
Ana Nesne: Kira ve hizmet sözleşmeleri

Tipleri:
├── kira_sozlesmesi     → Mülk sahibi ile
├── hizmet_sozlesmesi   → Yönetim hizmeti için
├── tedarikci_sozlesmesi → Temizlik, bakım şirketi ile

İlişkili:
├── tenant_id         → Hangi tenant
├── customer_id        → Taraf (mülk sahibi veya tedarikçi)
├── property_id        → İlgili mülk (varsa)
├── documents[]        → Sözleşme PDF, ekleri
├── start_date        → Başlangıç
├── end_date          → Bitiş
└── status            → aktif / pasif / süresi dolmuş

Tek Başına Satılabilir: ⚠️ Belki (Sözleşme modülü)
```

### 10. AI_CONVERSATION (AI Sohbet)

```
Ana Nesne: AI ile yapılan sohbetler

Kullanım Alanları:
├── crm_chatbot      → Müşteri soruları, AI destekli cevaplar
├── ilan_asistani    → İlan oluşturma, iyileştirme
├── rapor_olusturma  → Otomatik rapor生成
└── danisman_asistani → AI danışman desteği

İlişkili:
├── tenant_id         → Hangi tenant
├── customer_id        → İlgili müşteri (varsa)
├── user_id            → Kullanan kullanıcı
├── messages[]         → Konuşma mesajları (JSON)
├── ai_model           → DeepSeek / OpenAI / Ollama
├── summary            → AI konuşma özeti
└── tokens_used       → Kullanılan AI kredisi

Tek Başına Satılabilir: ✅ Evet (AI Asistan modülü)
```

### 11. AUTOMATION_JOB (Otomasyon İşi)

```
Ana Nesne: n8n ve benzeri otomasyon sistemlerinin işleri

Tetikleyicileri:
├── reservation_created    → Yeni rezervasyon
├── reservation_confirmed   → Rezervasyon onaylandı
├── checkin_reminder       → T-3 gün hatırlatma
├── checkout_created       → Check-out tetiklendi
├── cleaning_triggered     → Temizlik bildirimi
├── payment_received       → Ödeme geldi bildirimi
└── manual                 → Manuel tetikleme

İlişkili:
├── tenant_id         → Hangi tenant
├── triggered_by       → Kim/k ne tetikledi
├── payload            → Tetikleyici verileri (JSON)
├── status             → başarılı / başarısız / beklemede
├── result             → İşlem sonucu
└── next_retry        → Yeniden deneme zamanı

Tek Başına Satılabilir: ⚠️ Hayır (Altyapı — kullanıcı görmez)
```

### 12. AI_ANALYSIS (AI Analiz)

```
Ana Nesne: AI tarafından üretilen analizler

Kullanım Alanları:
├── listing_quality      → İlan kalitesi analizi
├── customer_behavior    → Müşteri davranış analizi
├── pricing_suggestion  → Fiyat önerisi
├── market_comparison   → Pazar karşılaştırması
├── occupancy_forecast   → Doluluk tahmini
├── revenue_forecast     → Gelir tahmini
├── damage_detection     → Hasar tespiti
└── sentiment_analysis  → Müşteri duygu analizi

İlişkili:
├── tenant_id         → Hangi tenant
├── entity_type        → Analiz edilen entity tipi
├── entity_id          → Analiz edilen entity ID
├── analysis_type     → Analiz kategorisi
├── result            → AI analiz sonucu (JSON)
├── confidence        → AI güven skoru
├── model             → Kullanılan model
└── cost              → AI maliyeti

Tek Başına Satılabilir: ✅ Evet (AI Analytics modülü — Business Intelligence)
```

---

## İlişki Şeması (ER Diagram)

```
┌─────────────┐         ┌─────────────┐
│   TENANT    │─────────│    USER     │
└──────┬──────┘         └─────────────┘
       │
       │ 1:N
       ▼
┌─────────────────────────────────────────────┐
│                  PROPERTY                    │
│   (Fiziksel Mülk — Villa, Apart, Butik)   │
└──────────────────────┬────────────────────┘
                       │
                       │ 1:N
                       ▼
┌─────────────────────────────────────────────┐
│                  LISTING                     │
│   (Platform Bazlı İlan — Airbnb, Booking)  │
└──────────────────────┬────────────────────┘
                       │
       ┌───────────────┼───────────────┐
       │               │               │
       ▼               ▼               ▼
┌───────────┐  ┌───────────┐  ┌───────────┐
│ RESERVAT. │  │    TASK    │  │CALENDAR_  │
│  (Rez.)   │  │  (Görev)  │  │  EVENT    │
└─────┬─────┘  └─────┬─────┘  └───────────┘
      │                │
      │                │ N:M
      │                ▼
      │         ┌───────────┐
      │         │ CUSTOMER  │
      │         │ (Müşteri)│
      │         └─────┬─────┘
      │               │
      │               │ N:M
      │               ▼
      │         ┌───────────┐
      │         │ PAYMENT   │
      │         │ (Ödeme)   │
      │         └─────┬─────┘
      │               │
      │               │ N:M
      │               ▼
      │         ┌───────────┐
      └────────▶│CONTRACT   │
                 │(Sözleşme) │
                 └───────────┘
```

---

## AI Her Modülün İçinde

```
┌─────────────────────────────────────────────────────────┐
│                      MODÜL YAPISI                      │
│                                                         │
│  ┌─────────────────────────────────────────────────┐  │
│  │                    CRM                           │  │
│  │  Customer ← AI → AI_Conversation              │  │
│  │             ← AI → AI_Analysis                │  │
│  └─────────────────────────────────────────────────┘  │
│                          │                              │
│  ┌─────────────────────────────────────────────────┐  │
│  │              YAZLIK KİRALAMA                     │  │
│  │  Listing ← AI → AI_Analysis (ilan kalitesi)   │  │
│  │  Property ← AI → AI_Analysis (fiyat önerisi)   │  │
│  └─────────────────────────────────────────────────┘  │
│                          │                              │
│  ┌─────────────────────────────────────────────────┐  │
│  │                  TAKVİM                          │  │
│  │  Reservation ← AI → AI_Analysis (doluluk)       │  │
│  │  Task ← AI → Otomasyon (Automation_Jobs)       │  │
│  └─────────────────────────────────────────────────┘  │
│                          │                              │
│  ┌─────────────────────────────────────────────────┐  │
│  │                  FİNANS                          │  │
│  │  Payment ← AI → AI_Analysis (gelir/gider)      │  │
│  │  Customer ← AI → AI_Analysis (ödeme riski)      │  │
│  └─────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### AI Kullanım Şekli

| Modül | AI Yeteneği | Nasıl Kullanılır |
|-------|-------------|-------------------|
| **CRM** | Müşteri analizi, segmentasyon, sohbet | Müşteri davranışını öğren, otomatik not oluştur |
| **Yazlık** | İlan kalitesi, fiyat önerisi, rakip analizi | İlanı analiz et, fiyat öner, eksikleri bul |
| **Takvim** | Doluluk tahmini, temizlik planlama | Optimal temizlik zamanı öner, fiyat stratejisi belirle |
| **Finans** | Gelir/gider tahmini, anomali tespiti | Gelecek ay geliri tahmin et, beklenmedik giderleri yakala |
| **Operasyon** | Hasar tespiti, belge okuma | Fotoğraftan hasarı analiz et, fatura PDF'sinden veri çıkar |

---

## MVP v1.0 Kapsamı

```
YALIHAN OS v1.0 — İlk Kullanılabilir Sürüm
═════════════════════════════════════════════

✅ Dashboard       — Günlük özet, kritik bildirimler
✅ CRM            — Müşteri, iletişim, notlar, segmentasyon
✅ Portföy        — Villa yönetimi, mülk sahibi ilişkisi
✅ AI İlan Asistanı — Fotoğraftan ilan oluşturma, açıklama
✅ Takvim         — Rezervasyon yönetimi, takvim
✅ Airbnb         — API entegrasyonu, rezervasyon sync
✅ Telegram       — Bildirimler, anlık raporlar

─────────────────────────────────────────────

⏳ İleriki Aşamada:
• Finans/ Muhasebe
• Booking.com entegrasyonu
• AI Business Intelligence
• OpenClaw agent entegrasyonu
• NotebookLM derin entegrasyonu
```

---

## Proje Evreleri — Genişletilmiş

```
┌─────────────────────────────────────────────────────────┐
│                    YALIHAN OS EVRELERİ                   │
└─────────────────────────────────────────────────────────┘

Faz 1 ✅ — Engineering Foundation
─────────────────────────────────────────────
Repository, Git, Test, SAB, Memory, Audit, Runtime

Faz 2 🚀 — Product Foundation
─────────────────────────────────────────────
MVP geliştirme: v1.0 kapsamındaki 7 modül

Faz 3 ⏳ — Business Intelligence Foundation
─────────────────────────────────────────────
AI karar destek sistemi:
• Portföy analizi
• Fiyat tahmini
• Doluluk tahmini
• Gelir tahmini
• Müşteri davranış analizi
• Operasyon optimizasyonu

İşte bu noktada YALIHAN OS:
"Bir CRM veya emlak yazılımı olmaktan çıkar;
AI destekli bir Gayrimenkul İşletim Sistemi haline gelir."
```

---

## Referanslar

| Doküman | Açıklama |
|---------|-----------|
| `docs/YALIHAN_OS_SYSTEMS_ARCHITECTURE.md` | İş sistemleri mimarisi |
| `docs/YALIHAN_OS_DOMAIN_MODEL.md` | **Bu dosya** — Entity ilişkileri |
| `docs/SAB.md` | Teknik anayasa |

---

*Son güncelleme: 2026-06-27*
*Oturum: 44*
*Durum: Domain Model tamamlandı*

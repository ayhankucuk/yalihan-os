# YALIHAN OS — Domain Model

> ⚠️ **PROJENİN EN KRİTİK DOKÜMANI.**
>
> Bu doküman, YALIHAN OS'nin ortak dilini tanımlar.
> Kod değil — ürünün kalbini tanımlar.
> Bundan sonra yazılacak her migration, model, servis, API ve AI ajanı bu belgeye bakılarak tasarlanır.
> Yıllar sonra bile yeni bir geliştirici sisteme katıldığında ilk okuyacağı doküman budur.

---

## 1. Core Business Objects — Temel İş Nesneleri

> Bu nesneler değişmez. UI değişebilir, AI değişebilir ama bunlar ürünün temelini oluşturur.

```
                    TENANT (Kiracı / İşletme)
                    ─────────────────────────────
                    YALIHAN OS'yi kullanan işletme
                    │
                    ├── Office (Şube / Lokasyon)
                    ├── User (Kullanıcı)
                    │       ├── role (admin | danisman | sahip)
                    │       └── permissions
                    └── Settings (Konfigürasyon)
                            └── tenant_id + config JSON

                            ─────────────────────────────────

                            CUSTOMER (Müşteri)
                            ─────────────────
                            İş ilişkisi olan kişi veya kurum
                            │
                            ├── Contact (İletişim Bilgisi)
                            │       ├── email
                            │       ├── telefon
                            │       └── adres
                            │
                            ├── Lead (Potansiyel Müşteri)
                            │       └── kaynak (referans | web | telefon)
                            │
                            ├── Opportunity (Fırsat)
                            │       ├── property_id (ilgilendiği mülk)
                            │       ├── stage (ilk_gorusme | teklif | pazarlik | kapali)
                            │       └── beklenen_deger
                            │
                            └── Notes (Notlar)
                                    ├── icerik
                                    ├── tur (gorusme | telefon | email | not)
                                    └── tarih

                            ─────────────────────────────────

                            PROPERTY (Mülk)
                            ─────────────────
                            Fiziksel gayrimenkul (villa, apart, butik)
                            │
                            ├── Listing (İlan)
                            │       ├── platform (airbnb | booking | websitesi)
                            │       ├── status (taslak | yayinda | yayinda_degil)
                            │       ├── airbnb_id
                            │       └── booking_id
                            │
                            ├── Owner (Mülk Sahibi) → Customer
                            │       └── sahiplik_orani
                            │
                            ├── Media (Medya)
                            │       ├── resimler []
                            │       └── videolar []
                            │
                            ├── Documents (Belge)
                            │       ├── tapu
                            │       ├── ruhsat
                            │       └── sigorta
                            │
                            ├── Features (Özellikler)
                            │       ├── kapasite, oda, banyo
                            │       ├── havuz, jakuzi, sauna
                            │       └── klima, wifi, tv
                            │
                            ├── Pricing (Fiyatlandırma)
                            │       ├── yaz_sezonu
                            │       ├── kis_sezonu
                            │       ├── dongu_sezonu
                            │       ├── temizlik_ucreti
                            │       └── depozito
                            │
                            └── Location (Konum)
                                    ├── il, ilce, mahalle
                                    └── lat, lng

                            ─────────────────────────────────

                            RESERVATION (Rezervasyon)
                            ─────────────────────────
                            Bir konaklama rezervasyonu
                            │
                            ├── Guest (Misafir) → Customer
                            │       └── misafir_sayisi
                            │
                            ├── Calendar (Takvim)
                            │       ├── check_in_tarihi
                            │       ├── check_out_tarihi
                            │       ├── gece_sayisi
                            │       └── durum (onay_bekliyor | onaylandi | aktif | tamamlandi | iptal)
                            │
                            ├── Payments (Ödemeler)
                            │       ├── toplam_tutar
                            │       ├── odeme_durumu (odenmedi | kismi | tam | iade)
                            │       ├── depozito
                            │       └── odeme_detaylari []
                            │
                            └── Operations (Operasyon)
                                    ├── kaynak (airbnb | booking | telefon | web)
                                    ├── temizlik_tarihi
                                    ├── check_in_saati
                                    └── ozel_notlar

                            ─────────────────────────────────

                            TASK (Görev)
                            ─────────────
                            Operasyonel görev
                            │
                            ├── Assigned User → User
                            │       └── atanan_kisi
                            │
                            ├── Property → Property
                            │       └── ilgili_mulk
                            │
                            ├── Customer → Customer (varsa)
                            │       └── ilgili_musteri
                            │
                            ├── Reservation → Reservation (varsa)
                            │       └── ilgili_rezervasyon
                            │
                            └── Status
                                    ├── durum (planlandi | devam_ediyor | tamamlandi | iptal)
                                    ├── oncelik (dusuk | normal | yuksek | kritik)
                                    └── son_tarih
```

---

## 2. Relationship Matrix — İlişki Matrisi

> Yeni geliştirici için inanılmaz yol gösterici.

| Entity | Owns (Sahiplenir) | References (Bağlantı) | AI Uses (AI Kullanır) |
|--------|--------------------|------------------------|-----------------------|
| **Tenant** | Users, Properties, Customers, Tasks | — | Analiz + Rapor |
| **User** | Tasks | Tenant, Property | AI Asistan |
| **Customer** | Reservations, Opportunities | Tenant | Davranış analizi |
| **Property** | Listings, Media, Documents, Features | Owner (Customer) | Fiyat + Kalite analizi |
| **Listing** | Reservations | Property, Platform | Kalite + Açıklama |
| **Reservation** | Tasks, Payments | Property, Guest (Customer), Listing | Doluluk + Tahmin |
| **Task** | — | Property, User, Customer, Reservation | Öneri + Planlama |
| **Payment** | — | Customer, Reservation, Property | Gelir/Gider analizi |
| **Document** | — | Property, Customer | AI Okuma + Özetleme |

---

## 3. Single Source of Truth — Tek Doğruluk Kaynağı

> Aynı veri iki farklı yerde tutulmaz. Her verinin bir sahibi vardır.

| Veri | Tek Sahibi | Açıklama |
|------|-----------|-----------|
| **Mülk bilgileri** | `Property` | Adres, oda sayısı, alan — başka yerde çoğaltılmaz |
| **Müşteri bilgileri** | `Customer` | İletişim, segment, tercihler |
| **Takvim** | `Reservation` | Check-in/out tarihleri — başka yerde tutulmaz |
| **Finans** | `Payment` | Tüm gelir/gider kayıtları |
| **İlan içeriği** | `Listing` | Açıklama, başlık, özellikler |
| **AI analiz sonuçları** | `AI_Analysis` | AI çıktıları — üzerine yazılabilir |
| **Kullanıcı yetkileri** | `User` | Role + permissions |
| **Belge içeriği** | `Document` | PDF, görsel — AI analiz sonu ayrı tutulur |
| **Sözleşme metni** | `Contract` | Hukuki metin — PDF + AI özet ayrı |
| **Otomasyon logları** | `Automation_Job` | Event geçmişi — append only |

### Sahiplik Kuralları

```
1. Her verinin bir ve yalnız bir sahibi vardır.
2. Sahibi olmayan entity'ler o veriyi sadece REFERENCES eder.
3. AI analiz sonuçları asla "kaynak veri" yerine geçmez.
4. Aynı veri iki yerde tutulursa = data inconsistency = BUG.
```

---

## 4. AI Touchpoints — AI'ın Dokunuş Noktaları

> AI hiçbir verinin sahibi değildir. Sadece analiz eder ve öneri üretir.

```
┌─────────────────────────────────────────────────────┐
│                  AI SERVIS katmanı                   │
│                                                      │
│  AI Asla Sahiplenmez — Sadece Analiz Eder           │
└─────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │  Property    │
                    └──────┬───────┘
                           │ AI Used
                           ▼
              ┌────────────────────────┐
              │ AI Fiyat Önerisi       │
              │ AI İlan Kalitesi       │
              │ AI Rakip Analizi       │
              │ AI Hasar Tespiti       │
              └────────────────────────┘

                    ┌──────────────┐
                    │  Customer    │
                    └──────┬───────┘
                           │ AI Used
                           ▼
              ┌────────────────────────┐
              │ AI Davranış Analizi    │
              │ AI Segmentasyonu       │
              │ AI Ödeme Riski        │
              │ AI Tercih Öğrenme     │
              └────────────────────────┘

                    ┌──────────────┐
                    │ Reservation  │
                    └──────┬───────┘
                           │ AI Used
                           ▼
              ┌────────────────────────┐
              │ AI Doluluk Tahmini      │
              │ AI Fiyat Optimizasyonu  │
              │ AI Check-in Önerisi     │
              │ AI Temizlik Zamanlaması │
              └────────────────────────┘

                    ┌──────────────┐
                    │   Finance    │
                    └──────┬───────┘
                           │ AI Used
                           ▼
              ┌────────────────────────┐
              │ AI Gelir Tahmini        │
              │ AI Gider Anomali       │
              │ AI Villa Karlılık      │
              │ AI Fatura Okuma        │
              └────────────────────────┘

                    ┌──────────────┐
                    │  Document    │
                    └──────┬───────┘
                           │ AI Used
                           ▼
              ┌────────────────────────┐
              │ AI PDF/Tap Okuma       │
              │ AI Hasar Tutanağı      │
              │ AI Sözleşme Özeti     │
              └────────────────────────┘
```

### AI Mimari Kuralı

```
┌─────────────────────────────────────────────────────┐
│                  AI KURALI                         │
│                                                      │
│  1. AI asla veri SAHİBİ DEĞİLDİR                  │
│  2. AI sadece OKUR, ANALİZ EDER, ÖNERİ ÜRETİR    │
│  3. AI sonucu ayrı bir tabloya yazılır            │
│  4. AI sonucu asla kaynak veriyi DEĞİŞTİRMEZ      │
└─────────────────────────────────────────────────────┘
```

---

## 5. Event Flow — Olay Zincirleri

> Her iş süreci bir event zinciri olarak tanımlanır.
> Gelecekte n8n ve OpenClaw için temel oluşturur.

### Event Chain 1: Yeni Mülk Ekleme

```
Property Created
        │
        ▼
Generate AI Description (AI_Analysis oluştur)
        │
        ▼
Create Listing (taslak)
        │
        ▼
AI Eksik Bilgi Kontrolü
        │
        ├── Eksik var → Bildirim
        └── Eksik yok → Devam et
        │
        ▼
Publish Airbnb
        │
        ▼
Notify Telegram (yeni ilan bildirimi)
        │
        ▼
Log Activity (Automation_Job log)
```

### Event Chain 2: Airbnb Rezervasyon

```
Airbnb Webhook (n8n)
        │
        ▼
Validate Reservation (çakışma, fiyat kontrolü)
        │
        ▼
Create Reservation (onay_bekliyor)
        │
        ▼
Notify Property Owner (Telegram)
        │
        ▼
Owner Onay → reservation.confirmed
        │
        ▼
Create Task: Temizlik (otomatik)
        │
        ▼
Create Task: Check-in Hazırlığı
        │
        ▼
T-3 Gün: Guest Reminder (Telegram)
        │
        ▼
T-1 Gün: Check-in Detayları Gönder
        │
        ▼
Check-in Günü: Task tamamlandı
        │
        ▼
Checkout: Task tamamlandı
        │
        ▼
Create Task: Temizlik (otomatik)
        │
        ▼
Temizlik tamam → Reservation.tamamlandi
        │
        ▼
Generate AI Summary (konaklama analizi)
        │
        ▼
Log Payment (gelir kaydı)
```

### Event Chain 3: AI İlan Asistanı

```
User: "Bu villanın ilanını oluştur"
        │
        ▼
AI_Conversation başlat
        │
        ▼
Fetch Property Data
        │
        ▼
AI Generate Description
        │
        ▼
AI Suggest Photos (eksik tespit)
        │
        ▼
AI Suggest Pricing
        │
        ▼
AI Create SEO Title
        │
        ▼
User Onayla
        │
        ▼
Update Listing
        │
        ▼
AI_Analysis kaydet (kalite skoru)
        │
        ▼
Log Activity
```

---

## 6. Modül Bağımlılıkları

> Sprint planlamasını kolaylaştırır.
> Bir modül ancak bağımlı olduğu modüller tamamlandıktan sonra başlar.

```
┌─────────────────────────────────────────────────────┐
│                    MODÜL HARİTASI                     │
└─────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │  Dashboard   │
                    └──────┬───────┘
                           │ veri çeker
              ┌─────────────┼─────────────┐
              ▼             ▼             ▼
       ┌──────────┐  ┌──────────┐  ┌──────────┐
       │   CRM     │  │ Property │  │ Airbnb   │
       └─────┬────┘  └─────┬────┘  └─────┬────┘
             │              │              │
      ┌──────┼──────┐      │      ┌──────┼──────┐
      ▼             ▼      │      ▼             ▼
┌──────────┐  ┌──────────┐ │ ┌──────────┐  ┌──────────┐
│ Customer │  │  Task   │ │ │ Listing  │  │Cleaning │
│          │  │         │ │ │          │  │         │
└──────────┘  └────┬────┘ │ └────┬─────┘  └────┬────┘
                    │       │      │              │
                    ▼       │      │              ▼
             ┌──────────┐  │      │       ┌──────────┐
             │ Calendar │  │      │       │Calendar  │
             │          │  │      │       │(Check-in│
             └────┬─────┘  │      │       │ Checkout)│
                  │        │      │       └────┬─────┘
                  ▼        │      │              │
           ┌──────────┐  │      │              ▼
           │ Reservation│◀─┘      │       ┌──────────┐
           │           │         │       │Payment   │
           └────┬─────┘         │       │(Gelir)   │
                │               │       └──────────┘
                ▼               ▼
           ┌──────────┐  ┌──────────┐
           │ Payment  │  │ AI       │
           │ (Finans) │  │ Service  │
           └──────────┘  └──────────┘
```

### Bağımlılık Tablosu

| Modül | Bağımlı Olduğu | Bağımlı Olan |
|-------|----------------|---------------|
| **Dashboard** | Tüm modüller | — |
| **CRM** | — | Task, Calendar |
| **Property** | — | Listing, Task, Document |
| **Airbnb** | Listing | Reservation, Cleaning, Payment |
| **Task** | User, Property | Calendar |
| **Calendar** | Reservation, Task | — |
| **Payment** | Reservation, Customer | — |
| **AI Service** | Tüm modüller | — |

---

## 7. AI Servis Entegrasyonu — Derinlemesine

### AI Servis Mimarisi

```
┌─────────────────────────────────────────────────────┐
│              AI ORCHESTRATOR (YalihanCortex)          │
│                                                      │
│  Gelen İstek                                         │
│      │                                                │
│      ▼                                                │
│  ┌──────────────────────────────────────────────┐   │
│  │         Agent Seçimi                            │   │
│  │  ├── PropertyAnalysisAgent                    │   │
│  │  ├── ListingGeneratorAgent                    │   │
│  │  ├── CustomerInsightAgent                    │   │
│  │  ├── PricingAgent                           │   │
│  │  ├── CalendarOptimizationAgent               │   │
│  │  └── DocumentIntelligenceAgent               │   │
│  └──────────────────────────────────────────────┘   │
│      │                                                │
│      ▼                                                │
│  ┌──────────────────────────────────────────────┐   │
│  │         Model Seçimi                           │   │
│  │  ├── DeepSeek (varsayılan)                   │   │
│  │  ├── OpenAI (yedek)                           │   │
│  │  └── Ollama (yerel, offline)                 │   │
│  └──────────────────────────────────────────────┘   │
│      │                                                │
│      ▼                                                │
│  Sonuç + AI_Analysis kaydı                          │
└─────────────────────────────────────────────────────┘
```

### Agent Tanımları

| Agent | Giriş | Çıkış | Kullandığı Entity |
|-------|-------|--------|-------------------|
| **PropertyAnalysisAgent** | Property + Fotoğraflar | Eksikler listesi, kalite skoru | Property, AI_Analysis |
| **ListingGeneratorAgent** | Property + AI_Analysis | Airbnb açıklaması, SEO başlık | Listing, AI_Analysis |
| **CustomerInsightAgent** | Customer + Notlar | Tercih analizi, segment önerisi | Customer, AI_Analysis |
| **PricingAgent** | Property + Bölge verisi | Fiyat aralığı önerisi | Property, AI_Analysis |
| **CalendarOptimizationAgent** | Reservation + Takvim | Optimal fiyat, temizlik zamanı | Reservation, AI_Analysis |
| **DocumentIntelligenceAgent** | Belge (PDF/foto) | Çıkarılmış veri, özet | Document, AI_Analysis |

---

## 8. Tenant Isolation — Çoklu Kiracı Güvenliği

```
┌─────────────────────────────────────────────────────┐
│                   TENANT ISOLATION                    │
│                                                      │
│  Her sorgu otomatik olarak tenant_id scope'unda      │
│                                                      │
│  KURAL: Cross-tenant erişim KESİNLİKLE yasaktır     │
└─────────────────────────────────────────────────────┘

Tenant A sorgusu:
─────────────────
SELECT * FROM customers
WHERE tenant_id = 'tenant_A'
──→ Sadece Tenant A'nın müşterileri döner

Yanlış Sorgu:
─────────────────
SELECT * FROM customers
──→ ❌ HATA: tenant_id scope eksik

Çözüm: GlobalScope otomatik eklenir:
─────────────────
TenantScope global scope olarak uygulanır.
Tüm modeller otomatik olarak tenant_id = auth()->user()->tenant_id
koşulunu alır.
```

---

## Referanslar

| Doküman | Klasör | Açıklama |
|---------|--------|-----------|
| **YALIHAN_OS_DOMAIN_MODEL.md** | `01-domain/` | ⬅️ Bu dosya — Entity ve ilişkiler |
| `SYSTEM_ARCHITECTURE.md` | `02-architecture/` | Teknik altyapı |
| `AI_ARCHITECTURE.md` | `02-architecture/` | AI pipeline |
| `SAB.md` | Kök | Teknik anayasa |

---

*Son güncelleme: 2026-06-27*
*Oturum: 44*
*Durum: Domain Model tamamlandı*
*Yazar: Kilo (Kullanıcı rehberliği ile)*

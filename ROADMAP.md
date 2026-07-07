# YALIHAN OS — Resmi Yol Haritası

> **Versiyon:** 1.1
> **Tarih:** 2026-07-03
> **Oturum:** 66 — Sprint 4.1 Kapanışı
> **Durum:** FAZ 2 — ÜRÜN AŞAMASI | TEAM HERMES
> **Son Güncelleme:** 2026-07-03 16:18

---

## YALIHAN OS Faz 2 Manifestosu

> Oturum 45 açılışında okunacak tek metin.

```
YALIHAN OS'nin amacı bir yazılım geliştirmek değil;
gayrimenkul operasyonlarını yapay zekâ ile hızlandıran
bir işletim sistemi oluşturmaktır.

Her sprint sonunda cevaplanacak tek soru:
"YALIHAN Emlak ekibi yarın sabah hangi yeni özelliği gerçekten kullanabilecek?"
```

---

## YALIHAN OS Vizyonu

```
YALIHAN OS, gayrimenkul profesyonellerinin günlük işlerini
yapay zekâ ile hızlandıran, modüler ve ajan destekli bir işletim sistemidir.
```

### Alt Tanım

```
YALIHAN OS, AI destekli bir Gayrimenkul İşletim Sistemi'dir.

CRM        → Modül
İlan       → Modül
Airbnb      → Modül
Takvim      → Modül
Finans      → Modül

AI          → ortak Zekâ Katmanı (her modülün içinde)
OpenClaw   → motor (ürün değil)
n8n        → otomasyon motoru
Telegram   → bildirim motoru
```

---

## Faz 1 — Engineering Foundation ✅ TAMAMLANDI

**Amaç:** Güvenilir, sürdürülebilir ve test edilebilir geliştirme platformu oluşturmak.

**Bu fazın çıktısı:** Platform.

### Teslimatlar

| # | Teslimat | Durum |
|---|----------|-------|
| 1 | Repository Recovery | ✅ |
| 2 | Git Governance | ✅ |
| 3 | SAB v5 LTS | ✅ |
| 4 | Domain Model | ✅ |
| 5 | System Architecture | ✅ |
| 6 | Test Altyapısı | ✅ |
| 7 | Tenant Mimarisi Doğrulaması | ✅ |
| 8 | Dokümantasyon Hiyerarşisi | ✅ |

---

## Faz 2 — Product Foundation 🚀 BAŞLIYOR

**Amaç:** YALIHAN Emlak ekibinin her gün kullanacağı gerçek ürün özelliklerini geliştirmek.

### Başarı Ölçütü Değişti

| Eski Soru | Yeni Soru |
|-----------|------------|
| "Kod doğru mu?" | "Bu özellik iş akışını hızlandırıyor mu?" |

### Altın Kural

> **Her sprint sonunda çalışan bir kullanıcı senaryosu teslim edilir.**

| Önemli | Değil |
|--------|--------|
| Kod satırı sayısı | Önemli değil |
| Commit sayısı | Önemli değil |
| Doküman sayısı | Önemli değil |
| **Çalışan kullanıcı senaryosu** | **Önemli olan bu** |

---

## Sprint 3.4 — AI İlan Asistanı (İlk Ürün)

### Sprint 3.4 Hedefi

> "Yeni bir portföy, 5 dakika içinde AI destekli olarak yayına hazır taslağa dönüşebilmeli."

### Sprint 3.4.1 ✅ Portföy Oluştur
- Yeni portföy formu
- Mülk sahibi bağlama
- Temel bilgiler

### Sprint 3.4.2 ✅ Fotoğraf Yükle
- Medya yükleme (sürükle-bırak)
- Fotoğraf sıralama

### Sprint 3.4.3 ✅ AI Analiz
- Eksik alanlar tespiti
- Fotoğraf kalite kontrolü
- Eksik oda / metrekare
- Eksik konum
- Eksik belge

### Sprint 3.4.4 ✅ AI İlan Oluştur
- Airbnb formatında açıklama
- SEO başlığı
- Öne çıkan özellikler
- Sahibinden formatı
- Hepsiemlak formatı

### Sprint 3.4.5 ✅ Yayına Hazır Skoru

```
┌──────────────────────────────────────┐
│         Hazırlık Skoru               │
│           92 / 100                   │
├──────────────────────────────────────┤
│ Eksikler:                            │
│ □ Tapu fotoğrafı                     │
│ □ Enerji Kimlik Belgesi               │
│ □ Havuz ölçüsü                       │
│ □ Drone fotoğrafı                     │
├──────────────────────────────────────┤
│ [ Taslak Kaydet ]                    │
└──────────────────────────────────────┘
```

---

## Sprint 3.5 — Yayınlama

- Airbnb API entegrasyonu
- Sahibinden entegrasyonu
- Hepsiemlak entegrasyonu
- Web sitesi yayını

---

## OpenClaw ve Platform Bileşenleri

### Roller

| Bileşen | Rol | YALIHAN OS mü? |
|----------|-----|----------------|
| **OpenClaw** | Agent Runtime / Workflow Executor | ❌ Hayır — motor |
| **OpenAI** | AI Model | ❌ Hayır — motor |
| **Gemini** | AI Model | ❌ Hayır — motor |
| **NotebookLM** | Bilgi Saklama | ❌ Hayır — bileşen |
| **n8n** | Otomasyon | ❌ Hayır — motor |
| **Telegram** | Bildirim | ❌ Hayır — bileşen |

**OpenClaw YALIHAN OS DEĞİLDİR.** YALIHAN OS'nin içinde çalışan motorlardan biridir.

Motor — ürün ayrımı korunmalı. Bu uzun vadede sistemin tutarlılığını sağlar.

### AI Mimari

```
                 YALIHAN OS
           Business Workflows
                   │
             AI Orchestrator
         ┌─────────┼─────────┐
         │         │         │
     OpenClaw   OpenAI    Gemini
         │
        n8n
         │
Telegram • Drive • Gmail • Calendar
```

---

## v1.0 İlk Ekran — Wireframe

```
┌──────────────────────────────────────────────────────┐
│              YENİ PORTFÖY                              │
├──────────────────────────────────────────────────────┤
│ İlan Adı          [________________________________] │
│ Kategori          [Villa                           ] │
│ Adres             [________________________________] │
│ Fotoğraflar      [+] [+] [+] [+]                   │
├──────────────────────────────────────────────────────┤
│                    [ AI ANALİZ ET ]                   │
├──────────────────────────────────────────────────────┤
│ AI SONUCU                                            │
│ ✔ Açıklama hazır                                    │
│ ✔ SEO hazır                                          │
│ ✔ Başlık hazır                                      │
│ ⚠ Eksik:                                            │
│    • Drone fotoğrafı                                 │
│    • Tapu                                            │
│    • Havuz ölçüsü                                   │
├──────────────────────────────────────────────────────┤
│              Hazırlık Skoru: 92%                     │
│                 [ TASLAK KAYDET ]                    │
└──────────────────────────────────────────────────────┘
```

**Eğer bu ekran gerçekten çalışıyorsa, YALIHAN OS ilk kez ÜRÜN olmuş olur.**

---

## Faz 2 KPI'ları

> Sadece teknik KPI değil — ürün KPI'ları.

| KPI | Hedef | Ölçüm |
|-----|-------|--------|
| İlk ilan hazırlama süresi | ≤ 5 dakika | Kullanıcı testi |
| AI'nın otomatik doldurduğu alan oranı | ≥ %80 | Sistem metrik |
| Kullanıcının manuel düzenleme ihtiyacı | ≤ %20 | Kullanıcı testi |

---

## İlk Demo Senaryosu — v1.0 Kilometre Taşı

> Bu senaryo çalışıyorsa, YALIHAN OS artık demo değil — GERÇEK ÜRÜN'dür.

```
1. Danışman giriş yapar.
2. Yeni Portföy oluşturur.
3. Fotoğrafları sürükleyip bırakır.
4. AI analiz başlar.
5. Sistem eksik bilgileri listeler.
6. AI başlık oluşturur.
7. AI açıklama oluşturur.
8. AI özellikleri çıkarır.
9. Hazırlık skoru hesaplanır.
10. Taslak kaydedilir.
```

**Eğer bu senaryo çalışıyorsa:**

"Ayhan bugün sabah yeni bir villa ekledi ve 5 dakika sonra AI tarafından hazırlanmış bir ilan taslağı oluştu."

Yeni mimari çizilmemiş olsa bile — ürün işe yarıyor. **Bu sprint başarılıdır.**

---

## Faz 2 Pusulası

> "Bu sprint sonunda YALIHAN Emlak ekibi ertesi sabah gerçekten kullanabileceği yeni bir yetenek kazanıyor mu?"

**Evet ise = Doğru yoldasınız.**

---

## Doküman Hiyerarşisi

```
docs/
├── ROADMAP.md                 ← Bu dosya
├── 01-domain/
│   ├── YALIHAN_OS_DOMAIN_MODEL.md
│   ├── PHASE_1_CLOSED.md
│   ├── SPRINT_3.4_REFINED.md
│   ├── FOUNDATION.md
│   └── OTURUM_44_CLOSING.md
├── 02-architecture/
│   └── SYSTEM_ARCHITECTURE.md
├── SAB.md
├── README.md
└── BEKCI_CHANGELOG.md
```

---

## Geliştirme Zinciri

```
İş Problemi
      ↓
Domain Model
      ↓
Use Case
      ↓
Acceptance Criteria
      ↓
Implementation
      ↓
Test
      ↓
Demo
```

---

## Sprint Definition of Done

| # | Madde |
|---|-------|
| 1 | Domain Model güncellendi (gerekiyorsa) |
| 2 | Kod tamamlandı |
| 3 | Testler geçti |
| 4 | Demo senaryosu çalıştı |
| 5 | **Gerçek kullanıcı kullandı** ⬅️ |

---

## YALIHAN OS Avantajı

> Onu geliştiren kişi aynı zamanda onu gerçek işinde kullanacak olan kişidir.

```
Kullanıcı ↔ Geliştirici = Sıfır mesafe
```

Doğru odak korunursa, ürün çok daha hızlı olgunlaşabilir.

---

## Oturum 44 — Stratejik Kapanış

### Dört Katman Modeli

```
                YALIHAN OS
      1. Business Vision
             │
      2. Domain Model
             │
      3. Engineering Platform
             │
      4. Product Features
```

**Faz 1:** Katman 3 güçlendirildi.
**Faz 2:** Odak Katman 4 olmalı.

---

### Backlog Yönetimi — ROADMAP vs Product Backlog

**ROADMAP.md** → vizyon belgesi (sabit kalır)
**Product Backlog** → yaşayan doküman (güncellenir)

| Epic | Sprint | Durum |
|------|--------|-------|
| AI İlan Asistanı | 3.4 | 🟡 |
| Portföy Merkezi | 3.5 | ⏳ |
| CRM Pipeline | 3.6 | ⏳ |
| Airbnb Operasyon | 3.7 | ⏳ |

---

### Sprint 3.4 Tek Başarı Cümlesi

> "Boş bir portföy kaydını AI destekli yayına hazır ilan taslağına dönüştür."

Bu cümle: iş hedefi + kullanıcı değeri + AI rolü + sprint kapsamını aynı anda tanımlıyor.

---

### Faz 2 Mimari Prensibi — Üç Soru

> Her yeni özellik şu üç sorudan geçmeli:

1. **Hangi iş problemini çözüyor?**
2. **Hangi Domain Entity'leri kullanıyor?**
3. **Kullanıcı bunu ertesi gün kullanabilecek mi?**

Üçünün de cevabı net değilse → geliştirme başlamamalı.

---

### YALIHAN OS v1.0-alpha — Kilometre Taşı

> Bu noktaya ulaşılınca ilk gerçek kullanıcı demosu yapılabilir.

| # | Koşul | Durum |
|---|-------|-------|
| 1 | AI İlan Asistanı uçtan uca çalışıyor | ⏳ |
| 2 | Portföy oluşturulabiliyor | ⏳ |
| 3 | AI analiz yapıyor | ⏳ |
| 4 | İlan taslağı oluşturuluyor | ⏳ |
| 5 | Hazırlık skoru hesaplanıyor | ⏳ |

---

### Oturum 44 — Sayısal

| Kategori | Sayı |
|----------|------|
| Commit | 19 |
| Yeni Dosya | 6 |
| Push Edilen | 19/19 |
| Faz | 1 → 2 |

---

## Oturum 44 — Final Karar

> **En büyük çıktı:**
> "Nasıl geliştireceğiz?" sorusundan "Kullanıcıya hangi değeri sunacağız?" sorusuna geçiş.

Bu değişim, herhangi bir commit'ten veya dokümandan daha değerlidir.

---

## Faz 2 Sprint Zinciri — Ürün Aşaması

> Her sprint sonunda sorulacak tek soru:
> **"Bugün kullanıcı veya AI ajanı dün yapamadığı hangi işi artık gerçekten yapabiliyor?"**

### Sprint DoD (Definition of Done)

```
Kod
  ↓
Test
  ↓
Playwright
  ↓
Commit
  ↓
Production
```

Doküman → yalnızca gerekiyorsa.

---

### Sprint 4.2 — Real CRUD Certification 🔄 BAŞLADI

**Sprint Hedefi:** Tüm CRUD operasyonları tamamen doğrulanmış, üretim kalitesinde.

| Op | Database | Audit | Tenant | Auth | Playwright |
|----|----------|-------|--------|------|------------|
| Create | ✅ | ✅ | ✅ | ✅ | ✅ |
| Read | ✅ | ✅ | ✅ | ✅ | ✅ |
| Update | ✅ | ✅ | ✅ | ✅ | ✅ |
| Archive | ✅ | ✅ | ✅ | ✅ | ✅ |
| Restore | ✅ | ✅ | ✅ | ✅ | ✅ |
| Soft Delete | ✅ | ✅ | ✅ | ✅ | ✅ |

**Başarı Kriteri:** Her operasyon uçtan uca test edilmiş + Playwright ile görsel doğrulama yapılmış.

---

### Sprint 4.3 — İlk AI Workforce Zinciri

**Sprint Hedefi:** İlk gerçek end-to-end AI ajan zinciri çalışıyor.

```
Yeni İlan
    ↓
PortfolioCreated Event
    ↓
Hermes (Event Broker)
    ↓
Photo Agent (görsel analiz)
    ↓
Description Agent (içerik üretimi)
    ↓
Notification Agent (bildirim)
    ↓
Dashboard (sonuç görünümü)
    ↓
Telegram (opsiyonel bildirim)
```

**Başarı Kriteri:** Bir ilan oluşturulduğunda, AI ajanları otomatik olarak sırayla çalışıyor ve sonuç dashboard'da görünüyor.

---

### Sprint 4.4 — Dashboard + Event Monitoring

**Sprint Hedefi:** Gerçek zamanlı AI agent activity dashboard.

- Event log görünümü
- Agent başarı/başarısızlık metrikleri
- İş akışı görselleştirme

---

### Sprint 4.5 — Telegram Entegrasyonu

**Sprint Hedefi:** AI ajanları Telegram üzerinden bildirim gönderebiliyor.

- Danışman → Telegram'a AI analiz sonucu
- Mülk sahibi → Yeni mesaj/ilan bildirimi

---

### Sprint 5.0 — İlk Canlı Müşteri Pilotu

**Sprint Hedefi:** Gerçek kullanıcı ile pilot deploy.

- Üretim ortamına deploy
- Sınırlı kullanıcı grubu ile test
- Geri bildirim toplama
- Sprint 5.1'e backlog temizleme

---

## Faz 2 KPI'ları (Güncellenmiş)

> Sadece teknik KPI değil — ürün KPI'ları.

| KPI | Hedef | Ölçüm |
|-----|-------|-------|
| İlk ilan hazırlama süresi | ≤ 5 dakika | Kullanıcı testi |
| AI'nın otomatik doldurduğu alan oranı | ≥ %80 | Sistem metrik |
| Kullanıcının manuel düzenleme ihtiyacı | ≤ %20 | Kullanıcı testi |
| **CRUD test coverage** | ≥ %95 | Playwright + Feature test |
| **AI Workforce zinciri çalışıyor mu?** | Evet/Hayır | Sprint 4.3 sonu |
| **Pilot kullanıcı memnuniyeti** | ≥ %80 | Anket |

---

## Faz 2 İlerleme Tablosu

| Sprint | Hedef | Durum |
|--------|-------|-------|
| Sprint 3.x (Hermes Foundation) | ✅ Tamamlandı | ✅ |
| SAB Tasarım Fazı | ✅ Tamamlandı | ✅ |
| Office Dokümantasyonu | ✅ Tamamlandı | ✅ |
| Hermes Core | ✅ Tamamlandı | ✅ |
| **Sprint 4.1 Alpine Stabilization** | ✅ Tamamlandı | ✅ |
| **Sprint 4.2 Real CRUD Certification** | 🔄 **BAŞLADI** | ⏳ |
| Sprint 4.3 AI Workforce Zinciri | — | ⏳ |
| Sprint 4.4 Dashboard + Monitoring | — | ⏳ |
| Sprint 4.5 Telegram Entegrasyonu | — | ⏳ |
| Sprint 5.0 İlk Canlı Pilot | — | ⏳ |

---

*Versiyon 1.1 — 2026-07-03*
*Oturum 66 — Sprint 4.1 Kapanış*
*Faz 2 — Ürün Aşaması*
*Artık kod üretiyoruz, değer üretiyoruz.*

# YALIHAN OS — Resmi Yol Haritası

> **Versiyon:** 3.0
> **Tarih:** 2026-07-05
> **Era:** ERA IV — Ürün Zamanı
> **Durum:** AKTİF — Sprint 5.0 Başlıyor

---

## ERA IV Manifestosu

```
YALIHAN OS artık sadece bir yazılım değil.

Üçüncü ürün:
  YALIHAN OS    → Platform
  YSOS          → Engineering
  SAAB          → Governance
  Knowledge       → Bilgi Yönetimi

Dördüncü ürün:
  Knowledge Platform — ERA IV ile birlikte
  platformun birinci sınıf vatandaşı oldu.

Artık "platform geliştirme" dönemi bitti.
"Ürün üretme" dönemi başlıyor.
```

---

## Sprint 5.x'in Amacı (SAAB Board Kararı)

> **Sprint 5.x'in amacı yeni modül geliştirmek değil;
> mevcut platformu kullanarak gerçek bir emlak danışmanının
> ilk portföyünü uçtan uca otomatik oluşturmasını sağlamaktır.**

Bu cümle, Sprint 5.x'in her kararının pusulasıdır.
Yeni mimari katman eklemek = DUR.
Mevcut platformu iş akışına dönüştürmek = DEVAM.

---

## Platform Tamamlandı — Mimaride Yeni Katman YOK

| Katman | Durum | Not |
|--------|-------|-----|
| SAAB v8 | ✅ | Governance, quality, drift protection |
| YSOS | ✅ | Engineering craftsmanship |
| Digital Twin | ✅ | Portföy ↔ Workspace döngü |
| Execution Engine | ✅ | n8n + OpenClaw otomasyonu |
| Workspace Integration | ✅ | Drive + NotebookLM + Telegram |
| Knowledge Platform | ✅ | Blueprint + Drive + NotebookLM + Corporate Memory |
| Replay | ✅ | CQRS projection replay |
| PRR | ✅ | Performance Regression Registry |

**Sonraki adım:** Yeni platform katmanı YAZMA. Mevcut platformu KULLAN.

---

## Sprint 5.0 — First Advisor Experience

**Versiyon:** 5.0.0
**Öncelik:** 🔴 P0
**User Story:** YALIHAN OS'un ilk gerçek kullanıcısı bir emlak danışmanıdır.

### Tek User Story

> **Yeni Portföy Oluştur**

Başka hiçbir şey. Hiçbir ek özellik. Hiçbir mimari katman.

---

### Bu User Story'nin Akışı

```
Danışman
    │
    ▼
[Yeni Portföy Oluştur]
    │
    ├─── Workspace Bootstrap ────────────────────► Drive Klasörleri (otomatik)
    │                                                │
    ├─── Knowledge Bootstrap ───────────────────────► Corporate Memory (otomatik)
    │                                                │
    ├─── Photo + AI ────────────────────────────────► Vision + Description + Score
    │                                                │
    ├─── Publishing Bootstrap ────────────────────► CRM + Airbnb + Sahibinden
    │                                                │
    └─── Dashboard Ready ───────────────────────────► READY
                                                        │
                                                        ▼
                                              Danışman sadece
                                              [Onayla] der.
```

---

### Sprint 5.0 Epics

#### Epic 1: Workspace Bootstrap

```
Yeni Portföy
    │
    ▼
Workspace Oluştur
    │
    ▼
Drive Klasörleri
(01-CLIENTS/[KOD]/)
    │
    ▼
Folder Structure
├── CONTRACTS/
├── DOCUMENTS/
├── PHOTOS/
├── FINANCIAL/
└── NOTES/
    │
    ▼
Corporate Memory
Portföy notu yazılır
    │
    ▼
READY
```

#### Epic 2: Knowledge Bootstrap

```
Workspace
    │
    ▼
NotebookLM
Yeni notebook oluştur
    │
    ▼
Knowledge Index
Portföy, konum, fiyat
knowledge base'e eklenir
    │
    ▼
Corporate Memory
memory/ güncellenir
    │
    ▼
READY
```

#### Epic 3: AI Bootstrap

```
Fotoğraflar Yüklendi
    │
    ▼
Photo Agent
→ Vision AI → Analiz
    │
    ▼
Description Agent
→ Airbnb açıklaması
→ SEO başlığı
→ Özellik listesi
    │
    ▼
Readiness Score
    │
    ▼
READY
```

#### Epic 4: Publishing Bootstrap

```
AI Bootstrap Tamamlandı
    │
    ▼
CRM Agent
→ Müşteri kaydı oluştur
→ Pipeline'a ekle
    │
    ▼
Publishing Agent
→ Airbnb formatı hazırla
→ Sahibinden formatı hazırla
→ Hepsiemlak formatı hazırla
    │
    ▼
Telegram Bildirimi
Danışmana bilgi mesajı at
    │
    ▼
Dashboard
READY
```

---

## Sprint 5.0 — Başarı Ekranı

> **Bu ekranı gördüğün gün YALIHAN OS doğmuş olur.**

```
┌──────────────────────────────────────────────────────────┐
│           YENİ PORTFÖY — BODRUM VILLA          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Workspace        ✅ READY                           │
│  Drive           ✅ READY  (03-CLIENTS/YVH-001/)   │
│  Knowledge       ✅ READY  (NotebookLM synced)      │
│  Photos          ✅ READY  (12 fotoğraf, AI analiz)  │
│  AI              ✅ READY  (açıklama + başlık üretildi)│
│  Publishing      ✅ READY  (3 platform hazır)        │
│  Telegram        ✅ READY  (danışmana bildirim atıldı) │
│  Score           ✅ 96/100                           │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│              [ ONAYLA VE YAYINLA ]                      │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

**Bu ekran çalışıyorsa:** YALIHAN OS gerçek bir emlak operasyonunu baştan sona otomatik tamamlamıştır.

---

## Sprint 5.x — Yeni Başarı KPI'ları

### KPI 1: Time to Ready

```
Yeni Portföy Oluştur
        │
        ▼
Workspace Ready
kaç saniye?

Başlangıç hedefi:  3 dakika
Orta hedef:         1 dakika
Hedef:              30 saniye
```

### KPI 2: Advisor Click Count

```
Danışmanın tıklama sayısı

Başlangıç:    37 tıklama
Orta:         18 tıklama
Hedef:         5 tıklama
Olgun:         1 tıklama (sadece Onayla)
```

### KPI 3: Business Automation Index

```
Sistem tarafından otomatik tamamlanan iş oranı

Başlangıç:   %20 (sadece taslak)
Orta:        %45 (Workspace + Drive)
Hedef:       %70 (Workspace + Drive + AI + CRM)
Olgun:       %95 (tam otomasyon)
```

### KPI 4: Time to Ready Metrik Tablosu

| Metrik | Sprint 5.0 | Sprint 5.1 | Sprint 5.2 |
|--------|-----------|-----------|-----------|
| Time to Ready | ≤ 3 dk | ≤ 1 dk | ≤ 30 sn |
| Advisor Click Count | ≤ 37 | ≤ 18 | ≤ 5 |
| Business Automation % | %20 | %45 | %70 |
| İlan hazırlık süresi (eski) | 60 dk | 30 dk | 10 dk |

---

## Sprint 5.x — Ürün Serisi

```
Sprint 5.0 ──► Sprint 5.1 ──► Sprint 5.2 ──► Sprint 5.3 ──► Sprint 5.4 ──► Sprint 6.0
   │              │              │              │              │              │
First Advisor   Workspace     Knowledge     AI + CRM      Full           Commercial
Experience     Expansion     Engine       Automation    Production     Release
(🔴 P0)         (🟠 P1)      (🔴 P0)      (🟠 P1)      (🟢 P2)       (🚀)
```

### Sprint 5.0: First Advisor Experience
**Odak:** Tek user story — Yeni Portföy Oluştur
**Epic 1:** Workspace Bootstrap
**Epic 2:** Knowledge Bootstrap
**Epic 3:** AI Bootstrap
**Epic 4:** Publishing Bootstrap
**KPI:** Time to Ready ≤ 3 dk, Click ≤ 37, Automation %20

### Sprint 5.1: Knowledge Engine
**Odak:** Drive + NotebookLM + Corporate Memory canlı hale gelir
**KPI:** Time to Ready ≤ 1 dk, Automation %45

### Sprint 5.2: Workspace Expansion
**Odak:** Agent Capability Registry + Workspace detay
**KPI:** Click ≤ 18, Automation %70

### Sprint 5.3: AI + CRM Automation
**Odak:** Tam ajan zinciri — Workspace → AI → CRM → Publish
**KPI:** Time to Ready ≤ 30 sn, Click ≤ 5

### Sprint 5.4: Full Production
**Odak:** 3 danışman, 3 portföy, 1 ay aktif kullanım
**KPI:** Memnuniyet ≥ 4/5

### Sprint 6.0: Commercial Release
**Odak:** İlk ücretli müşteri
**KPI:** SLA tanımlı, fiyatlandırma hazır

---

## Sprint 5.x — Workflow Sprint Olduğu İçin

**Sprint 5.x'te yazılmayacak:**
- ❌ Yeni mimari katman
- ❌ Yeni domain model
- ❌ Yeni platform bileşeni
- ❌ Yeni AI motoru

**Sprint 5.x'te yazılacak:**
- ✅ Mevcut bileşenleri iş akışına bağlayan workflow kod
- ✅ Workspace → Drive otomasyonu
- ✅ Event → Agent tetikleme
- ✅ Dashboard READY ekranı
- ✅ Telegram notification pipeline

---

## SAAB Sprint Sonu Sorusu (ERA IV)

Her sprint sonunda Chief AI şu üç soruyu sorar:

> **1. Time to Ready ne kadar?**
> Danışmanın yeni portföy oluşturması kaç saniye sürüyor?

> **2. Advisor Click Count kaç?**
> Danışman kaç tıklama yaptı?

> **3. Business Automation % ne?**
> Sistemin otomatik tamamladığı iş oranı ne?

---

## Eski KPI'lar → Yeni KPI'lar

| Eski KPI | Yeni KPI | Neden |
|----------|----------|-------|
| Test Coverage | Time to Ready | Ürün hızı |
| Integrity Violations | Advisor Click Count | Kullanıcı emeği |
| Code Quality | Business Automation % | Otomasyon değeri |

---

## Faz 2 → Faz 3 Geçiş Kriteri

> **Faz 2 (Product Foundation) tamamlandığında:**
> Time to Ready ≤ 1 dakika AND Advisor Click Count ≤ 5

Faz 3 (Commercial): Bu kriter sağlandığında başlar.

---

## Era Geçiş Tablosu

| Era | Odak | KPI |
|-----|-------|-----|
| ERA I — Foundation | Repository kurtarmak | 0 blocking error |
| ERA II — MVP | İlan oluşturmak | İlan kaydedilebiliyor |
| ERA III — Platform | Mimari kurmak | bekci:health ≥ 90% |
| ERA IV — Product | İş akışı kurmak | **Time to Ready** |

---

## Era Geçiş Kronolojisi

```
ERA I  ──► ERA II ──► ERA III ──────────► ERA IV
(Foundation)   (MVP)    (Platform)    (Autonomous)
                              │
                    Knowledge Platform
                    (2026-07-05)
                              │
                              ▼
                    Ürün Zamanı
                    Sprint 5.0
                    First Advisor Experience
```

---

## Doküman Hiyerarşisi

```
docs/
├── ROADMAP.md                          ← Bu dosya — ERA IV
├── SAB.md                              ← K-1...K-6 Bilgi Varlık İlkeleri
├── BEKCI_CHANGELOG.md                ← System changelog
│
├── knowledge/                         ← ERA IV
│   ├── KNOWLEDGE_BLUEPRINT.md
│   ├── NOTEBOOKLM_STRUCTURE.md       ← NB-6 Market Intelligence
│   ├── DRIVE_STRUCTURE.md             ← 6-klasör (01...06)
│   ├── CORPORATE_MEMORY.md
│   ├── KNOWLEDGE_GAP_REPORT.md
│   └── README.md
│
└── adr/
    └── 2026-07-05-knowledge-platform-adoption.md  ← ADR-022
```

---

*Versiyon 3.0 — 2026-07-05*
*Era: ERA IV — Ürün Zamanı*
*Sprint 5.0 — First Advisor Experience*
*Tek User Story: Yeni Portföy Oluştur*
*Başarı Ekranı: Workspace READY + Drive READY + AI READY + Score 96*

# AI GOVERNANCE v1.0 — YALIHAN OS

> Kurumsal AI Yönetişim Yapısı
> Chief Engineer Direktifi — 2026-07-07
> Era III: Execution & Working Capabilities

---

## TEMEL İLKE

```
AI Governance v1.0

Üç AI birbirinin işini yapmaz.

Her AI'ın yetki alanı net.
Her AI'ın çıktısı tanımlı.
Her AI bağımsız çalışır.
```

---

## ÜÇ OFIS YAPISI

```
╔═══════════════════════════════════════════════════════════════════╗
║                    YALIHAN OS — AI GOVERNANCE                   ║
╠═══════════════════════════════════════════════════════════════════╣
║                                                                   ║
║   🏛 SAAB (ChatGPT)          💻 VS CODE AI          🔬 ANTIGRAVITY ║
║   Architecture Office        Engineering Office      Research Office ║
║                                                                   ║
║   STRATEGY                   EXECUTION                 QUALITY       ║
║   ↓                          ↓                         ↓             ║
║   Ne yapılacağına            Nasıl yapılacağını        Doğru yapılıp  ║
║   karar verir                implement eder            yapılmadığını   ║
║                                                     araştırır       ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

## 🏛 SAAB — Architecture Office

> **Yetki:** Stratejik Mimari Kararları
> **Kod:** YAZMAZ
> **Model:** ChatGPT (SAB, ADR, Sprint Charter)

### Yetki Alanı

| Alan | Açıklama |
|------|----------|
| Platform Architecture | Sistemin genel yapısı |
| DDD | Domain Driven Design kararları |
| Workspace Model | Property Workspace mimarisi |
| Capability Design | Özellik tasarımı |
| Sprint Planning | Sprint hedefleri ve öncelikleri |
| ADR Approval | Mimari kararların onayı |
| Quality Gates | Kalite eşikleri |
| KPI | Başarı metrikleri |
| Certification | Sprint çıktılarının sertifikasyonu |
| Roadmap | Ürün yol haritası |

### SAAB Çıktıları

| Çıktı | Format | Açıklama |
|-------|--------|----------|
| Sprint Charter | `SPRINT_X_CHARTER.md` | Sprint hedefleri ve kapsamı |
| ADR | `ADR-XXX.md` | Mimari karar kaydı |
| Architecture Review | Markdown | Sprint sonu mimari değerlendirmesi |
| Capability Roadmap | Markdown | Özellik yol haritası |
| Quality Gate | JSON/YAML | Geçiş kriterleri |
| Certification | Markdown | Sprint tamamlama sertifikası |

### SAAB Kuralları

```
1. SAAB kod YAZMAZ — sadece yön verir
2. SAAB implementasyon YAPMAZ — karar verir
3. SAAB test YAZMAZ — kalite kriteri belirler
4. SAAB research YAPMAZ — strateji üretir
```

---

## 💻 VS CODE AI — Engineering Office

> **Yetki:** Üretken Geliştirme
> **Kod:** YAZAR
> **Model:** Claude Code / Cursor / Cline

### Yetki Alanı

| Alan | Açıklama |
|------|----------|
| Laravel | Framework implementasyonu |
| PHP | Backend kod |
| Vue / Livewire / Alpine | Frontend kod |
| Tests | Unit ve Feature testleri |
| Refactor | Mevcut kodu iyileştirme |
| Bug Fix | Hata düzeltme |
| Migration | Veritabanı göçü |
| Performance | Optimizasyon |

### VS Code Çıktıları

| Çıktı | Açıklama |
|-------|----------|
| Code | Production kod |
| Pull Request | Git çıktısı |
| Tests | Test suite |
| Migration | DB migration |
| Implementation | Özellik implementasyonu |
| Refactor | Kod iyileştirme |

### VS Code Kuralları

```
1. VS Code mimari DEĞİŞTİREMEZ — SAAB kararlarını uygular
2. VS Code yetki alanı DIŞINA ÇIKAMAZ — sadece kod yazar
3. VS Code yeni domain EKLEYEMEZ — SAAB onayı gerekir
4. VS Code kural İHLAL EDEMEZ — SAB kurallarına uyar
```

---

## 🔬 ANTIGRAVITY — Research Office

> **Yetki:** Bağımsız Araştırma ve Doğrulama
> **Kod:** YAZMAZ
> **Model:** Kilo Agent

### Yetki Alanı

| Alan | Açıklama |
|------|----------|
| Repository Audit | Kod analizi |
| Security Office | Güvenlik incelemesi |
| Performance Office | Performans analizi |
| Technology Research | Teknoloji araştırması |
| Competitive Research | Rakip analizi |
| Architecture Drift | Mimari uyum kontrolü |

### Antigravity Görevleri

#### 1. Repository Audit

```
Repository'yi tara
Bul:
  ☐ Dead code
  ☐ Cyclic dependency
  ☐ Service explosion
  ☐ Hidden coupling
  ☐ Duplicate logic
  ☐ Architecture drift
  ☐ Anti-pattern
```

#### 2. Security Office

```
İncele:
  ☐ Webhook doğrulama
  ☐ OAuth flow
  ☐ Queue tenant isolation
  ☐ JWT handling
  ☐ Google Drive API
  ☐ Telegram bot
  ☐ n8n entegrasyonu
  ☐ API authentication
```

#### 3. Performance Office

```
Analiz et:
  ☐ N+1 query problemleri
  ☐ Memory leak
  ☐ Cache kullanımı
  ☐ Slow query
  ☐ Endpoint latency
  ☐ Profiling
  ☐ Benchmark
```

#### 4. Technology Research

```
Araştır:
  ☐ TKGM API
  ☐ Google Places
  ☐ PostGIS
  ☐ OpenStreetMap
  ☐ OCR
  ☐ Gemini Vision
  ☐ Vector DB (Qdrant)
  ☐ OpenSearch
```

#### 5. Competitive Research

```
Benchmark yap:
  ☐ Airbnb
  ☐ Booking
  ☐ Hostaway
  ☐ Guesty
  ☐ AppFolio
  ☐ Buildium
  ☐ Channex
  ☐ Salesforce
```

#### 6. Architecture Drift

```
Her sprint sonunda kontrol et:
  ☐ SAAB ile kod uyumlu mu?
  ☐ DDD bozulmuş mu?
  ☐ Workspace aggregate korunuyor mu?
  ☐ Capability yanlış yerde mi?
  ☐ Yeni service explosion oluşmuş mu?
  ☐ Layer violation var mı?
```

### Antigravity Çıktıları

| Çıktı | Format | Açıklama |
|-------|--------|----------|
| Audit Report | Markdown | Repository bulguları |
| Security Report | Markdown | Güvenlik analizi |
| Performance Report | Markdown | Performans bulguları |
| Tech Research | Markdown | Teknoloji araştırması |
| Competitive Analysis | Markdown | Rakip analizi |
| Architecture Drift Report | Markdown | Mimari uyum raporu |

### Antigravity Kuralları

```
1. Antigravity mimariyi DEĞİŞTİREMEZ — sadece raporlar
2. Antigravity uygulama KAPSAMINI GENİŞLETEMEZ — araştırır
3. Antigravity kod YAZAMAZ — sadece analiz eder
4. Antigravity karar ALAMAZ — SAAB'a öneri sunar
```

---

## OPERASYONEL KURAL

```
╔═════════════════════════════════════════════════════════════════════╗
║               RESEARCH OFFICE OPERASYONEL KURALI                    ║
╠═════════════════════════════════════════════════════════════════════╣
║                                                                     ║
║  Antigravity hiçbir zaman:                                          ║
║                                                                     ║
║  ✗ Mimariyi değiştirmez                                             ║
║  ✗ Uygulama kapsamını genişletmez                                   ║
║  ✗ Yeni domain eklemez                                              ║
║  ✗ ADR yazmaz                                                      ║
║  ✗ Sprint Charter yazmaz                                            ║
║                                                                     ║
║  Antigravity sadece:                                                 ║
║                                                                     ║
║  ✓ Bağımsız doğrulama yapar                                         ║
║  ✓ Teknik araştırma yapar                                           ║
║  ✓ Güvenlik analizi yapar                                           ║
║  ✓ Performans analizi yapar                                          ║
║  ✓ Benchmark üretir                                                  ║
║  ✓ SAAB'a öneri sunar                                                ║
║                                                                     ║
║  Mimari kararlar YALNIZCA SAAB tarafından alınır.                   ║
║  Implementasyon YALNIZCA Engineering Office tarafından yapılır.       ║
╚═════════════════════════════════════════════════════════════════════╝
```

---

## SPRINT DÖNGÜSÜ

```
╔═══════════════════════════════════════════════════════════════════════╗
║                       SPRINT DÖNGÜSÜ — AI GOVERNANCE                ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║   1. SAAB                                                               ║
║      └── Sprint Charter yazar                                          ║
║      └── ADR onaylar (gerekirse)                                       ║
║      └── Quality Gate belirler                                         ║
║      └── Kapasite ataması yapar                                         ║
║                                                                       ║
║   2. Engineering Office (VS Code)                                       ║
║      └── Implementasyon yapar                                           ║
║      └── Test yazar                                                    ║
║      └── PR açar                                                       ║
║                                                                       ║
║   3. Antigravity (Kilo)                                                 ║
║      └── Independent Audit                                              ║
║      └── Security Review                                                ║
║      └── Performance Analysis                                          ║
║      └── Architecture Drift Check                                       ║
║                                                                       ║
║   4. SAAB                                                               ║
║      └── Sprint Review                                                  ║
║      └── Certification                                                  ║
║      └── Bir sonraki Sprint Charter                                     ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝
```

---

## ROL ATAMALARI — AI AGENTS

| Agent | Office | Rol |
|-------|--------|-----|
| ChatGPT | 🏛 SAAB | Architecture Office |
| Claude Desktop | 💻 Engineering | Code Implementation |
| Cursor | 💻 Engineering | Code Implementation |
| Cline | 💻 Engineering | CI/CD & Quality |
| Windsurf | 💻 Engineering | Framework Migration |
| Kilo | 🔬 Antigravity | Research Office |

---

## KALİTE GATE

### Sprint Geçiş Kriterleri

```
Sprint X → Sprint X+1 Geçişi İçin:

┌─────────────────────────────────────────────────────────────┐
│ GEREKLİ (Hepsi sağlanmalı)                                  │
├─────────────────────────────────────────────────────────────┤
│ ☑ Tüm testler geçiyor (veya explicitly skipped)             │
│ ☑ SAAB Sprint Review tamamlandı                            │
│ ☑ Antigravity Audit Report mevcut                           │
│ ☑ Mimari ihlal yok (sab:integrity-scan PASS)                │
│ ☑ Tenant isolation testleri yeşil                          │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ONSESİZ (Sprint devam edebilir ama kalite düşer)            │
├─────────────────────────────────────────────────────────────┤
│ ☐ Performance benchmark meet edildi                          │
│ ☐ Security audit R11-R15 clean                              │
│ ☐ Documentation complete                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## ERA III VİZYONU İLE UYUM

```
ERA I ────────────── ERA II ────────────── ERA III
Mimari Tasarım         Altyapı             Çalışan Capability
   ↑                      ↑                     ↑
SAAB oluştu          Bekçi oluştu       AI Governance v1.0
                    MCP aktif
                    Chief AI layer

ERA III Hedefi:
  ═══════════════════════════════════════════════════════════
  ▸ Çalışan özellik üretmek
  ▸ Mimari değil, değer üretmek
  ▸ Net governance ile sürdürülebilir geliştirme
  ═══════════════════════════════════════════════════════════
```

---

## BAŞARI KRİTERLERİ

```
AI Governance v1.0 Başarılı mı?

┌─────────────────────────────────────────────────────────────┐
│ METRİK                    HEDEF         DURUM              │
├─────────────────────────────────────────────────────────────┤
│ Rol belirsizliği           0             [ ]               │
│ Çakışan yetki alanı        0             [ ]               │
│ SAAB kararı olmadan kod    0             [ ]               │
│ Antigravity kod yazması     0             [ ]               │
│ Sprint döngüsü takibi     %100          [ ]               │
│ Certification oranı        %100          [ ]               │
└─────────────────────────────────────────────────────────────┘
```

---

## VERSİYON GEÇMİŞİ

| Versiyon | Tarih | Değişiklik | Yazar |
|----------|-------|------------|-------|
| v1.0 | 2026-07-07 | İlk yayın — AI Governance yapısı | Chief Engineer |

---

## REFERANSLAR

- `chief-ai/sprint-backlog.md` — Sprint iş listesi
- `chief-ai/risk-register.md` — Risk kaydı
- `chief-ai/executive-dashboard.md` — Sistem durumu
- `docs/SAB.md` — Teknik anayasa
- `memory/CHANGELOG_AGENT.md` — Agent değişiklik kaydı

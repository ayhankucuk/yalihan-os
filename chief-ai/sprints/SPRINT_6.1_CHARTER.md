# Sprint 6.1 Charter

> SAAB — Architecture Office Çıktısı
> Sprint Hedefleri ve Kapsamı

---

## SPRINT BİLGİLERİ

| Alan | Değer |
|------|-------|
| **Sprint** | Sprint 6.1 |
| **Başlangıç** | 2026-07-07 |
| **Bitiş** | TBD |
| **Sahip** | 🏛 SAAB |
| **Engineering** | 💻 VS Code AI |
| **Quality** | 🔬 Antigravity |
| **Status** | 🔄 ACTIVE |

---

## SPRINT HEDEFİ

**Odak:** Template Engine MVP

Property Workspace mimarisi üzerine ilk dinamik alan motorunu inşa etmek.

---

## KAPSAM

### Dahil Olanlar

| # | Özellik | Açıklama | Öncelik |
|---|---------|----------|----------|
| 1 | Template Engine MVP | Intent → Template → Field mapping | P1 |
| 2 | Dynamic Field Engine | Template'a göre form alanları oluşturma | P1 |
| 3 | Readiness Rules | Alan doğrulama kuralları | P2 |
| 4 | AI Hook Registry | AI capability hook'ları | P2 |

### Dahil Olmayanlar (Explicit Scope Exclusion)

| # | Özellik | Açıklama | Neden |
|---|---------|----------|-------|
| 1 | TKGM Service | Harici API entegrasyonu | Sprint 6.2'de |
| 2 | AI Copilot | Chat interface | Sprint 6.4'te |
| 3 | Publishing | İlan yayınlama akışı | Sprint 6.3'te |

---

## ÇALIŞAN CAPABILITY HEDEFLERİ

```
Sprint 6.1 Sonunda:

┌─────────────────────────────────────────────────────────────┐
│ Property Workspace                                          │
│                                                             │
│  Intent Seçimi → Template Yükle → Alan Doldur              │
│       ↓              ↓              ↓                       │
│  [Sprint 6.0]   [Template Engine]  [Dynamic Fields]        │
│                                                             │
│  Kullanıcı hangi intent'i seçerse,                        │
│  o intent'e uygun form alanları otomatik gelsin.          │
└─────────────────────────────────────────────────────────────┘
```

---

## QUALITY GATE

### Geçiş Kriterleri (Sprint → Sprint+1)

```
┌─────────────────────────────────────────────────────────────┐
│ GEREKLİ (Hepsi sağlanmalı)                                  │
├─────────────────────────────────────────────────────────────┤
│ ☐ Tüm testler geçiyor (veya explicitly skipped)             │
│ ☐ SAAB Sprint Review tamamlandı                            │
│ ☐ Antigravity Audit Report mevcut                           │
│ ☐ Mimari ihlal yok (sab:integrity-scan PASS)                │
│ ☐ Tenant isolation testleri yeşil                          │
│ ☐ Template Engine 3 intent destekliyor                     │
│ ☐ Readiness scoring çalışıyor                               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ONSESİZ                                                    │
├─────────────────────────────────────────────────────────────┤
│ ☐ Performance benchmark meet edildi                        │
│ ☐ Security audit temiz (R11-R15 öncesi baseline)           │
│ ☐ Documentation complete                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## RİSKLER

| Risk | Öncelik | Mitigasyon |
|------|---------|------------|
| Template Engine MVP scope creep | 🟠 Orta | Kapsam dışı = Sprint 6.2 |
| Dynamic Field karmaşıklığı | 🟡 Düşük | MVP = sadece 3 intent |

---

## SPRINT ÇIKTILARI

| Çıktı | Format | Sahip |
|--------|--------|-------|
| Template Engine | PHP Service | 💻 VS Code |
| Dynamic Field Blade | Blade Component | 💻 VS Code |
| Readiness Rules | Service + Config | 💻 VS Code |
| AI Hook Registry | Service + Config | 💻 VS Code |
| Unit Tests | PHPUnit | 💻 VS Code |
| E2E Tests | PHPUnit Feature | 💻 VS Code |
| Sprint Review | Markdown | 🏛 SAAB |
| Sprint Certification | Markdown | 🏛 SAAB |
| Audit Report | Markdown | 🔬 Antigravity |

---

## ENGAGEMENT PLANI

### Sprint 6.1 Sprint Döngüsü

```
1. SAAB → Sprint Charter ✓ (Bu dosya)

2. VS Code → Implementation
   └── Template Engine MVP
   └── Dynamic Field Engine
   └── Readiness Rules
   └── AI Hook Registry

3. Antigravity → Independent Audit
   └── Architecture Drift Check
   └── Security Review (R11-R15 baseline)
   └── Performance Analysis

4. SAAB → Sprint Review + Certification
```

---

## SPRINT 6.1 RETRO

> Sprint sonunda doldurulacak

### Ne İyi Gitti?

-

### Ne İyileştirilebilir?

-

### Bir Sonraki Sprint İçin Öneriler

-

---

## SPRINT 6.2'YE DEVREDİLEN

| # | Özellik | Açıklama | Neden |
|---|---------|----------|-------|
| 1 | TKGM Service | Harici API entegrasyonu | Kapsam dışı |
| 2 | Location Intelligence | Maps, POI | Kapsam dışı |
| 3 | Security Hardening | R11-R15 | Ayrı izleme hattı |

---

## ONAY

| Rol | Onaylayan | Tarih |
|-----|-----------|-------|
| 🏛 SAAB | ChatGPT | 2026-07-07 |
| 💻 Engineering | VS Code AI | TBD |
| 🔬 Quality | Antigravity | TBD |

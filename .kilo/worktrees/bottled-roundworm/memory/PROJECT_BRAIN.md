# PROJECT BRAIN

> Yalıhan Emlak AI OS — Merkezi Bilgi Kaynağı
> Son güncelleme: 2026-06-25

---

## Proje Kimliği

| Alan | Değer |
|------|-------|
| **İsim** | Yalıhan Emlak — AI Real Estate Operating System |
| **Lokasyon** | Bodrum merkezli lüks gayrimenkul |
| **Stack** | Laravel 10 / PHP 8.2+ / MySQL / Tailwind / Alpine.js / Vite |
| **Framework** | Modular Monolith |
| **AI** | YalihanCortex (DeepSeek v4, Ollama, OpenAI) |

---

## Doğrulanmış Metrikler (2026-06-25)

| Metrik | Değer | Kaynak |
|--------|-------|--------|
| Model sayısı | **211** | `find app/Models -name "*.php" \| wc -l` |
| Toplam Service | **384** | `grep "^class.*Service" app/Services/ --include="*.php" \| wc -l` |
| AI Service | **94** | `grep "^class.*Service" app/Services/AI/ --include="*.php" \| wc -l` |
| Domain sayısı | **8** | `docs/architecture/domains.md` |
| Route sayısı | **195+** | `php artisan route:list \| wc -l` |
| bekci:health | **91.85%** (MCP 100%, KB 100%) | `php artisan bekci:health` |
| Project Health | **59.25%** | Naming Authority ihlalleri |

---

## Sistem Durumu

| Komponent | Durum | Not |
|-----------|-------|-----|
| SAB v24.2 | ✅ ACTIVE | Production Seal |
| Global Seal | ✅ SUCCESS | Phase 11 tamamlandı |
| MCP Server | ✅ ÇALIŞIYOR | PID 9568, TypeScript Bridge |
| CI Pipeline | ✅ STABLE | Gold Line |
| bekci:health | ✅ 91.85% | MCP 100%, KB 100%, PH 59.25% |

---

## Aktif Sprint

| Sprint | Durum | Öncelik |
|--------|-------|---------|
| Sprint 1-2 | ✅ TAMAMLANDI | 2026-05-10 / 2026-06-15 |
| **Sprint 3** | 🔄 DEVAM | 89 fail test, Context7, Naming Authority |
| Sprint 4 | 📋 PLANLANDI | Hetzner deploy (P0), JSONB göçü (P1) |

---

## Açık Riskler

| # | Risk | Öncelik | Durum |
|---|------|---------|-------|
| 1 | 89 fail test | 🔴 P0 | Sprint 3 devam |
| 2 | Hetzner deploy (SSH bloker) | 🔴 P0 | Sprint 4 planlandı |
| 3 | Context7 Naming violations (175) | 🟠 P1 | Sprint 3 devam |
| 4 | Project Health 59.25% | 🟠 P1 | Naming Authority cleanup gerekli |
| 5 | JSONB göçü (T-UPS-V2-FULL) | 🔴 P1 | Sprint 4 planlandı |

---

## Mimari Yapı

```
8 Domain:
├── Property Domain       (Ilan, Photo, Price, Category)
├── CRM Domain          (Kisi, Lead, Talep, Matching)
├── Feature/Template    (Feature, Template, FeaturePack)
├── AI Domain           (YalihanCortex, AI Providers)
├── Governance Domain   (Decision, Rollback, Suppression)
├── Finance Domain      (Ledger, FxRate, Currency, Rental)
├── Intelligence Domain (CQRS Projections, Market)
└── Location Domain     (Il, Ilce, Mahalle) — Canonical SSOT
```

---

## AI Motorları (94 Service)

- **YalihanCortex**: Merkezi AI beyin
- **Opportunity Engine**: Fırsat tespiti
- **Buyer Match Engine**: Alıcı eşleştirme
- **Deal Radar**: Satış tahmini
- **Portfolio Doctor**: Portföy sağlığı
- **Market Valuation**: Piyasa değerleme
- **Owner Discovery**: Mal sahibi keşfi
- **Translation Pipeline**: Çoklu dil desteği (TR/EN/RU/AR/DE/FR)

---

## External Entegrasyonlar

| Servis | URL | Durum |
|--------|-----|-------|
| N8N | https://n8n.yalihanemlak.com.tr | ✅ AKTIF |
| Panel | https://panel.yalihanemlak.com.tr | ⏳ Deploy bekliyor |
| Telegram | AI Bot + Notifications | ✅ Entegre |
| TKGM | Tapu Kadastro | ✅ Entegre |

**Sunucu**: Hetzner CX33 — 157.180.116.63

---

## Güvenlik Kuralları

- Tenant isolation: **ZORUNLU** (tenant_id her query'de)
- Thin Controller: **ZORUNLU** (Controller iş mantığı YASAK)
- Silent Catch: **YASAK** (Fail-Fast)
- Context7 Naming: **0 ihlal toleransı**
- CQRS: Projection'a direkt yazma YASAK

---

## AI Workspace Yapısı

```
yalihan2026/
├── agents/          → Agent instruction dosyaları (5 dosya)
├── prompts/         → AI prompt & template (3 dosya)
├── knowledge/       → Konsolide bilgi tabanı
├── memory/          → Oturum hafızası (bu dosyalar)
├── workflows/       → Automasyon workflow'ları
├── audits/          → Audit raporları
├── mcp/             → TypeScript MCP Bridge
├── mcp-servers/     → JavaScript MCP Server
├── yalihan-bekci/   → Learning & knowledge base
└── chief-ai/        → Chief AI yönetim katmanı (7 dosya)
```

## Chief AI Management Layer

| Dosya | Ne İçin | Son Güncelleme |
|-------|---------|----------------|
| `chief-ai/README.md` | Chief AI rol tanımı | 2026-06-25 |
| `chief-ai/sprint-backlog.md` | Sprint iş listesi | 2026-06-25 |
| `chief-ai/risk-register.md` | Risk puanları ve durum | 2026-06-25 |
| `chief-ai/technical-debt.md` | Teknik borç envanteri | 2026-06-25 |
| `chief-ai/agent-assignments.md` | Görev atama matrisi | 2026-06-25 |
| `chief-ai/gap-analysis.md` | Sistem açıkları | 2026-06-25 |
| `chief-ai/decision-log.md` | Mimari kararlar | 2026-06-25 |

**Chief AI Kuralları:**
- Kod YAZMAZ — sadece okur ve yönetir
- Risk 7+ = sprint durdurma yetkisi
- Agent ataması = tek görev/agent
- SAB.md, authority.json, IlanCrudService, YalihanCortex DEĞİŞTİREMEZ

---

## Proje Durumu: KENDI KENDINI BELGELEYEN AI İŞLETİM SİSTEMİ

Yalıhan2026 sadece bir Laravel projesi değil — kendi kendini belgeleyen ve yöneten bir AI işletim sistemidir.

Her oturumda güncellenir: `memory/CHANGELOG_AGENT.md`

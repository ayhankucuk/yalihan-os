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

## AI WORKFORCE (Capability 2)

> Design: 2026-06-28 — SAAB APPROVED 9.97/10
> **Status:** Platform Core — All future capabilities build on this

### Sprint Status

| Sprint | Status | Score |
|--------|--------|-------|
| Sprint 3.5 | ✅ CLOSED | 9.97/10 |
| Sprint 3.6 | 🆕 OPEN | — |

### Board Documents

| Document | Location |
|----------|----------|
| BOARD_RESOLUTION.md | docs/ |
| EXECUTIVE_DIRECTIVES.md | docs/ |
| IMPLEMENTATION_PRIORITY.md | docs/ |
| SPRINT_3_6_MASTER_PLAN.md | docs/ |

### Sprint 3.6 P0

1. Corporate Ontology Finalization
2. Hermes Core
3. Event Bus
4. First 4 Agents

**Total Estimate:** ~22 days

### Strategic Shift

| From | To |
|------|-----|
| Feature | **Business Capability** |
| Agent Registry | **AI Workforce HR** |
| Event Catalog | **Event Bus as Platform Core** |
| Documentation | **Corporate Memory** |

### SAAB Decision

> "Artık bundan sonra geliştirilecek her Capability bunun üzerine oturacak."
> "CRM → AI CRM → AI Platform → Digital Company"

---

## SPRINT 3.6 — CODE-FIRST

### Success Metric: WORKING VERTICAL SLICES

| ❌ Eski | ✅ Yeni |
|---------|---------|
| Doküman sayısı | İş akışı sayısı |
| Office tamamlama | Çalışan ajan |

**Definition:** Event → Hermes → Agent → Persist → Dashboard → Notify

### First Demo (End of Sprint 3.6)

Portföy → PortfolioCreated → Hermes → Portfolio/Photo/Description/Notification Agent → Dashboard → Telegram

### Phase Transition

| Phase | Status |
|-------|--------|
| Architecture Design | ✅ COMPLETE |
| Implementation | 🆕 ACTIVE |

### Sprint 3.6 Priorities

| Epic | Focus | Duration |
|------|-------|----------|
| 1 | Hermes Core | Week 1 |
| 2 | Corporate Ontology | Week 1-2 |
| 3 | First 4 Agents | Week 2-3 |
| 4 | Dashboard | Week 3-4 |

### Execution Rules

- %80 Kod, %15 Test, %5 Doc
- No new design documents
- SAAB in oversight mode
- Success = working code

### Key Files

- `docs/SPRINT_3_6_EXECUTION_PLAN.md` — Sprint execution plan
- `docs/BOARD_RESOLUTION.md` — Sprint approval

### Design Documents

| Document | Status | Location |
|----------|--------|----------|
| AI Workforce Design | ✅ SAAB Approved | `docs/AI_WORKFORCE_DESIGN.md` |
| Corporate Ontology | ✅ P0 Next Sprint | `docs/CORPORATE_ONTOLOGY.md` |
| AI Workforce HR | ✅ Personnel Files | `docs/AI_WORKFORCE_HR.md` |

### Key SAAB Recommendations

1. **Corporate Memory** — Drive = Corporate Memory, not file system
2. **NotebookLM Corporate** — 11-category knowledge structure
3. **Agent Personnel Files** — Every agent has HR documentation
4. **Event Bus Core** — Platform center, OpenClaw monitors
5. **Standard Agent Contract** — Input → Context → Knowledge → Decision → Output → Event
6. **AI Workforce Dashboard** — Real-time workforce monitoring

### Test Question

> "Bu karar, YALIHAN PLATFORM'un dijital şirket vizyonunu güçlendiriyor mu?"

### Organizasyon

```
HERMES (Chief Orchestrator)
    │
    ├── Knowledge Division      (Drive, Knowledge, Research)
    ├── Market Intelligence     (Scanner, Price Analytics, Trend)
    ├── Listing Division        (Photo, Description, Readiness, Portfolio)
    ├── CRM Division            (Lead Intake, Matching, Follow-up)
    ├── Publishing Division     (Channel, Calendar)
    └── Operations Division     (Notification, Finance)
```

### Agent Registry (15 Agents)

| Agent | Division | Priority | Key Events |
|-------|----------|----------|------------|
| Hermes | Orchestrator | Critical | All |
| Portfolio Agent | Listing | Critical | portfolio.created |
| Photo Agent | Listing | High | photo.uploaded |
| Description Agent | Listing | Critical | listing.ready.for.description |
| Readiness Agent | Listing | High | listing.updated |
| Market Scanner | Market Intel | Critical | market.scan.scheduled |
| Price Analytics | Market Intel | High | listing.created |
| Lead Intake | CRM | Critical | lead.received |
| Matching Agent | CRM | Critical | lead.created |
| Follow-up Agent | CRM | High | followup.due |
| Drive Agent | Knowledge | High | document.stored |
| Knowledge Agent | Knowledge | High | knowledge.query |
| Channel Agent | Publishing | Critical | listing.approved |
| Notification Agent | Operations | High | notification.requested |
| Finance Agent | Operations | High | transaction.completed |

### Event Catalog (25+ Events)

**Portfolio:** `portfolio.created`, `portfolio.updated`, `portfolio.analytics.updated`
**Listing:** `listing.created`, `listing.ready.for.description`, `listing.draft.completed`, `listing.pending.review`, `listing.approved`, `listing.published`
**Photo:** `photo.uploaded`, `photo.processed`, `photo.watermarked`, `gallery.ready`
**Market:** `market.scan.scheduled`, `market.listing.detected`, `market.price.changed`, `valuation.completed`
**CRM:** `lead.received`, `lead.created`, `match.found`, `match.recommended`, `followup.due`
**System:** `human.escalation.required`, `agent.assigned`, `agent.completed`, `agent.failed`

### Design Documents

- `docs/AI_WORKFORCE_DESIGN.md` — Complete design spec (v1.0.0)

### Implementation Roadmap

| Phase | Timeline | Focus |
|-------|----------|-------|
| Phase 1 | Sprint 4-5 | Hermes + 4 core agents |
| Phase 2 | Sprint 6-7 | CRM integration |
| Phase 3 | Sprint 8-9 | Market intelligence |
| Phase 4 | Sprint 10-11 | Knowledge layer |
| Phase 5 | Sprint 12-13 | Publishing automation |
| Phase 6 | Sprint 14+ | Advanced orchestration |

---

## Proje Durumu: KENDI KENDINI BELGELEYEN AI İŞLETİM SİSTEMİ

Yalıhan2026 sadece bir Laravel projesi değil — kendi kendini belgeleyen ve yöneten bir AI işletim sistemidir.

Her oturumda güncellenir: `memory/CHANGELOG_AGENT.md`

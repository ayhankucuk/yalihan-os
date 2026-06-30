# 🏛️ SAAB BOARD PRIORITY MATRIX — YALIHAN AI OS
## Enterprise Initiative Prioritization

---

**Date:** 2026-06-28
**Session:** Board of Directors — Final Governance Review
**Framework:** Eisenhower Matrix + MoSCoW

---

## Priority Legend

| Tier | Definition | SLA |
|------|------------|-----|
| **P0** | Critical — Blocks all progress | Immediate |
| **P1** | High — Blocks major feature | 2 weeks |
| **P2** | Medium — Significant value | 1 month |
| **P3** | Low — Nice to have | 1 quarter |

---

## P0 — Critical (Blocks All Progress)

| # | Initiative | Owner | Status | Blocker |
|---|-----------|-------|--------|---------|
| P0-1 | **89 Failing Tests Resolution** | Engineering | ⏳ ACTIVE | Production deploy blocked |
| P0-2 | **Hetzner Production Deploy** | DevOps | ⏳ BLOCKED | SSH known-hosts issue |
| P0-3 | **JSONB Full Migration** | Engineering | ⏳ PHASE 1 DONE | Read path incomplete |

### P0 Detail: Test Resolution

**Current State:** 89 tests failing
**Target:** 0 failures, Gold Line CI
**Approach:**
1. Categorize by failure type
2. Fix P0 (runtime errors) first
3. Then P1 (assertion failures)
4. Then P2 (mock mismatches)
5. Enforce no new failures

---

## P1 — High Priority (Blocks Major Features)

| # | Initiative | Owner | Sprint | Value |
|---|-----------|-------|--------|-------|
| P1-1 | **Naming Authority Cleanup** | Engineering | Sprint 3.1 | Governance compliance |
| P1-2 | **Tenant Isolation Audit** | Security | Sprint 4 | Data protection |
| P1-3 | **AI Budget Guard Activation** | AI Office | Sprint 4 | Cost control |
| P1-4 | **Chief AI Sprint Planning** | Chief AI | Sprint 3 | Orchestration |
| P1-5 | **n8n Workflow Integration** | Integration | Sprint 3.5 | Automation |
| P1-6 | **Telegram Bot Activation** | Integration | Sprint 4 | Customer comms |

### P1 Detail: Naming Authority Cleanup

**Current State:** 175 violations
**Target:** <50 violations
**Approach:**
1. Category 1: DB columns (ZORUNLU)
2. Category 2: Model $fillable (ZORUNLU)
3. Category 3: Prompt strings (context7-ignore)
4. Category 4: Local variables (context7-ignore)

---

## P2 — Medium Priority (Significant Value)

| # | Initiative | Owner | Quarter | Value |
|---|-----------|-------|--------|-------|
| P2-1 | **AI Listing Assistant v1** | Product | Q3 2026 | Customer-facing AI |
| P2-2 | **Portfolio Management v1** | Product | Q3 2026 | Core workflow |
| P2-3 | **CRM Operations Enhancement** | Product | Q3 2026 | User efficiency |
| P2-4 | **Airbnb Operations Panel** | Product | Q3 2026 | Revenue protection |
| P2-5 | **Projection Health Dashboard** | Operations | Q3 2026 | Data integrity |
| P2-6 | **MCP Server Enhancement** | Integration | Q3 2026 | Developer experience |
| P2-7 | **NotebookLM Auto-Sync** | Knowledge | Q3 2026 | Knowledge currency |

### P2 Detail: AI Listing Assistant v1

**Components:**
- Photo analysis (Vision AI)
- Readiness calculation
- Airbnb description generation
- SEO title optimization
- Price suggestion
- Checklist creation

**User Value:** "Create listing in minutes, not hours"

---

## P3 — Low Priority (Nice to Have)

| # | Initiative | Owner | Quarter | Value |
|---|-----------|-------|--------|-------|
| P3-1 | **Chief AI Full Implementation** | Chief AI | Q4 2026 | Autonomous operations |
| P3-2 | **Enterprise Tier Pricing** | Business | Q4 2026 | Revenue model |
| P3-3 | **Multi-Region Expansion** | Infrastructure | Q4 2026 | Scale |
| P3-4 | **Voice Interface** | Product | Q4 2026 | Accessibility |
| P3-5 | **Mobile App MVP** | Product | Q4 2026 | Mobile access |

---

## Priority Matrix (Eisenhower View)

```
                    URGENT                          NOT URGENT
            ┌─────────────────────┐        ┌─────────────────────┐
HIGH        │ P0-1: 89 Tests     │        │ P2-1: AI Listing   │
IMPACT      │ P0-2: Deploy       │        │ P2-2: Portfolio    │
            │ P0-3: JSONB        │        │ P1-4: Chief AI     │
            └─────────────────────┘        └─────────────────────┘
            ┌─────────────────────┐        ┌─────────────────────┐
LOW         │ P1-2: Tenant Audit  │        │ P3-1: Chief AI     │
IMPACT      │ P1-3: Budget Guard │        │ Full                │
            │ P1-5: n8n           │        │ P3-2: Enterprise   │
            └─────────────────────┘        └─────────────────────┘
```

---

## MoSCoW Breakdown

### Must Have (P0)
- 89 tests to GREEN
- Hetzner production deploy
- JSONB complete migration

### Should Have (P1)
- Naming Authority compliance
- Tenant isolation audit
- AI Budget activation
- Chief AI Sprint Planning
- n8n workflow integration

### Could Have (P2)
- AI Listing Assistant v1
- Portfolio Management v1
- CRM Operations
- Airbnb Panel
- Projection Dashboard

### Won't Have (Deferred)
- Chief AI Full Implementation (Q4)
- Enterprise pricing (Q4)
- Multi-region (Q4)
- Voice interface (Q4)
- Mobile app (Q4)

---

## Dependency Graph

```
SPRINT 3.4 (Current)
├── P0-1: 89 Tests ──────────────────┐
│   └── Prerequisites:               │
│       └── P1-1: Naming Authority   │
├── P1-4: Chief AI Planning          │
└── P2-1: AI Listing Assistant ──────┼── BLOCKED BY P0-1, P0-2

SPRINT 4 (Next)
├── P0-2: Hetzner Deploy ─────────────┼── DEPENDS ON P0-1
├── P1-2: Tenant Isolation Audit
├── P1-3: AI Budget Activation
└── P1-6: Telegram Bot ───────────────┼── DEPENDS ON P0-2

SPRINT 3.5 (Parallel)
├── P1-5: n8n Integration
├── P2-5: Projection Dashboard
└── P2-6: MCP Enhancement
```

---

## Resource Allocation

| Priority | Focus | Allocation |
|----------|-------|------------|
| P0 | Unblock | 50% Engineering |
| P1 | Foundation | 30% Engineering |
| P2 | Value Delivery | 15% Engineering |
| P3 | Future | 5% Engineering |

---

## Board Approval

| Tier | Initiatives | Total |
|------|-------------|-------|
| P0 | 3 | Must complete before Phase 4 |
| P1 | 6 | Must complete before production |
| P2 | 7 | Target Q3 2026 |
| P3 | 5 | Target Q4 2026 |

**Total Initiatives:** 21

---

*Priority matrix approved by Board of Directors.*
*Re-prioritization requires Board resolution.*

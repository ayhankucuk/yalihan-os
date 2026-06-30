# SPRINT 3.6 — CODE-FIRST EXECUTION PLAN

**Date:** 2026-06-28
**Mode:** Code Only
**Philosophy:** %80 Kod, %15 Test, %5 Dokümantasyon

---

## SPRINT CONTEXT

### What We Built (Sprint 3.5)

| Office | Deliverable | Status |
|--------|-------------|--------|
| Architecture | AI Workforce Foundation Design | ✅ |
| Research | Technology Assessment | ✅ |
| Business | Capability Framework | ✅ |
| Knowledge | Corporate Ontology v1.0 | ✅ |
| Operations | AI Workforce HR | ✅ |
| Board | BOARD_RESOLUTION.md | ✅ |

### What We Stop Building

- ❌ New markdown files
- ❌ New governance documents
- ❌ New Office structures
- ❌ New design patterns

### What We Start Building

- ✅ Working code
- ✅ Event Bus
- ✅ Hermes Core
- ✅ First 4 Agents
- ✅ Dashboard

---

## SPRINT 3.6 — EPIC SEQUENCE

### EPIC 1: Hermes Core (Week 1)

**Goal:** Platform core infrastructure

```
Day 1-2: Event Bus
├── Laravel Event Service Provider
├── Event base class
├── Event dispatcher
└── Redis transport layer

Day 3-4: Event Registry
├── Event catalog as code
├── Event schema validation
└── Event documentation generator

Day 5-7: Hermes Orchestrator
├── Hermes class
├── Event routing logic
├── Agent registry service
└── Escalation workflow
```

**Deliverable:** Event Bus operational, Hermes responds to events

---

### EPIC 2: Corporate Ontology (Week 1-2)

**Goal:** Align code with canonical names

```
Day 8-9: Entity Mapping
├── Map entities to models
├── Update service signatures
└── Create ontology service

Day 10-11: Capability Mapping
├── Map capabilities to services
├── Document event vocabulary
└── Update authority.json
```

**Deliverable:** All code uses canonical entity names

---

### EPIC 3: First 4 Agents (Week 2-3)

**Goal:** Working digital employees

```
Day 12-14: Portfolio Agent
├── Agent class extending base
├── portfolio.created handler
├── Analytics update logic
└── Event emission

Day 15-16: Photo Agent
├── Photo processing pipeline
├── Quality assessment
├── Watermark integration
└── Gallery management

Day 17-19: Description Agent
├── YalihanCortex integration
├── Multi-language support
├── SEO optimization
└── Draft workflow

Day 20-21: Notification Agent
├── Telegram integration
├── Template system
├── Escalation notifications
└── Delivery tracking
```

**Deliverable:** 4 agents respond to events and complete tasks

---

### EPIC 4: AI Workforce Dashboard (Week 3-4)

**Goal:** Real-time visibility

```
Day 22-24: Core Dashboard
├── Agent status cards
├── Active jobs counter
├── Error rate display
├── Queue status

Day 25: Cost Tracking
├── AI cost monitor
├── Budget alerts
└── Usage charts
```

**Deliverable:** Real-time workforce monitoring

---

## SUCCESS METRICS

### Code Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Event Bus uptime | >99% | System uptime |
| Hermes routing latency | <100ms | P50 response |
| Agent response time | <5s | Average across agents |
| Event throughput | >100/hr | Peak load |

### Functional Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Agents operational | 4/4 | Test suite |
| Events handled | >50 | Daily events |
| Escalations working | 100% | Telegram alerts |
| Dashboard accuracy | >95% | Real-time sync |

### Quality Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Test coverage | >80% | New code |
| Lint passes | 100% | CI pipeline |
| Type errors | 0 | PHPStan |
| Integration tests | 10+ | E2E scenarios |

---

## CODE STANDARDS

### What We Write

- Production-grade PHP code
- Unit tests for every new class
- Integration tests for workflows
- Inline documentation (docblocks)
- Type hints everywhere

### What We Don't Write

- Long design documents
- Architecture diagrams (code IS the architecture)
- Meeting notes
- Status reports (code is status)

### Validation Commands

```bash
# Before every commit
php artisan sab:integrity-scan
php artisan test
php artisan bekci:health

# After major changes
./scripts/tools/antigravity-full-gate.sh
```

---

## SPRINT EXIT CRITERIA

### Must Have (Go/No-Go)

- [ ] Event Bus operational
- [ ] Hermes routing events
- [ ] 4 agents responding
- [ ] Telegram escalation working
- [ ] Dashboard showing real data

### Nice to Have

- [ ] Full test coverage
- [ ] Documentation complete
- [ ] Performance optimized

---

## SPRINT 3.6 — FIRST DEMO (End of Sprint)

### Demo Flow: Portföy Oluşturma Zinciri

```
┌─────────────┐
│ Yeni Portföy │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│PortfolioCreated  │  Event
│    Event        │
└──────┬──────────┘
       │
       ▼
┌─────────────┐
│   Hermes    │  Orchestrator
└──────┬──────┘
       │
       ├──► Portfolio Agent ──► Analytics Update
       │
       ├──► Photo Agent ──► Process Photos
       │
       ├──► Description Agent ──► Generate Draft
       │
       ├──► Notification Agent ──► Alert Advisor
       │
       ▼
┌─────────────┐
│  Dashboard  │  Real-time Status
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Telegram   │  Log Message
└─────────────┘
```

### Demo Success Criteria

| Step | Component | Success |
|------|-----------|---------|
| 1 | PortfolioCreated event fires | ✅ Event logged |
| 2 | Hermes routes to agents | ✅ Routing visible |
| 3 | Portfolio Agent responds | ✅ Analytics calculated |
| 4 | Photo Agent processes | ✅ Gallery ready |
| 5 | Description Agent drafts | ✅ AI description generated |
| 6 | Notification sent | ✅ Telegram message |
| 7 | Dashboard updates | ✅ Real-time status |

### What We Don't Need for Demo

- ❌ Airbnb integration
- ❌ Google Drive upload
- ❌ CRM lead creation
- ❌ Full market intelligence

**Focus:** Event chain working end-to-end

---

## SUCCESS METRIC: WORKING VERTICAL SLICES

### ❌ Old Metrics

- Kaç doküman yazıldı?
- Kaç Office tamamlandı?
- Kaç diagram oluşturuldu?

### ✅ New Metric

**Kaç uçtan uca çalışan iş akışı var?**

| Sprint | Working Workflows | Demo |
|--------|-----------------|------|
| Sprint 3.6 | 1 (Portfolio chain) | Portföy → Event → Agents → Notification |
| Future | N | Each capability |

### Definition of "Working Vertical Slice"

```
Working Vertical Slice =
  Event fires →
  Hermes routes →
  Agent(s) respond →
  Result persists →
  Dashboard shows →
  Human notified
```

### Measurement

| Week | Metric | Target |
|------|--------|--------|
| Week 1 | Hermes routing | Events route correctly |
| Week 2 | Single agent | Agent completes task |
| Week 3 | Agent chain | 2+ agents in sequence |
| Week 4 | Full demo | All 4 agents + dashboard |

---

## SPRINT PRINCIPLES

### Three Preservations

1. **No new Offices** (unless critical)
2. **No new documents** (only what code needs)
3. **Working demo at sprint end** (every sprint)

### Code IS Documentation

```
Good: Self-documenting PHP code
Better: Tests that serve as documentation
Best: Working demo at sprint end
```

---

## FINAL SPRINT 3.6 TARGET

**By end of Sprint 3.6:**

1 Agent chain working end-to-end
4 agents responding to events
Real-time dashboard showing status
Telegram notifications working

**The value of YALIHAN PLATFORM is measured by:**
- Working agent count
- Working workflow count
- Not document count

---

*Document Version: 1.1.0*
*Updated: 2026-06-28*
*Success Metric: Working Vertical Slices*

---

## SAAB OVERSIGHT MODE

During Sprint 3.6, SAAB operates in OVERSIGHT MODE:

**SAAB does NOT:**
- ❌ Generate daily work
- ❌ Create new documents
- ❌ Define new offices
- ❌ Change governance

**SAAB DOES:**
- ✅ Quarterly Architecture Review
- ✅ Sprint Review (end of sprint)
- ✅ ADR (Architecture Decision Record) when needed
- ✅ Governance change when critical
- ✅ New platform standards when needed

**SAAB Triggers:**
| Trigger | Action |
|---------|--------|
| Major architectural decision | Create ADR |
| Sprint end | Sprint Review |
| Quarterly | Architecture Review |
| Governance breach | Emergency session |
| New capability | Board approval |

---

## EXECUTION PRIORITY

### Week 1: Foundation

1. Event Bus (2 days)
2. Hermes Core (3 days)
3. Agent base class (2 days)

### Week 2: First Agents

4. Portfolio Agent (2 days)
5. Photo Agent (2 days)
6. Description Agent (3 days)

### Week 3: Integration

7. Notification Agent (2 days)
8. Telegram integration (2 days)
9. End-to-end testing (3 days)

### Week 4: Dashboard

10. Dashboard UI (2 days)
11. Real-time metrics (2 days)
12. Sprint review prep (1 day)

---

## KEY PRINCIPLE

> "The documentation is the code. The code is the documentation."

Every class we write should be self-documenting.
Every test should serve as documentation.
Every commit message should explain the why.

---

## FINAL NOTE

Sprint 3.6 is about transformation:

FROM: "We designed an AI Workforce"
TO: "Our AI Workforce is working"

The best sign of success:
- Not new markdown files
- Not new diagrams
- But working agents that respond to events
- And end-to-end workflows that complete tasks

---

*Document Version: 1.1.0*
*Mode: CODE-FIRST*
*Philosophy: Ship working code, not documents*
*Success Metric: Working Vertical Slices*
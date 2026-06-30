# IMPLEMENTATION PRIORITY

**Sprint 3.6 — Implementation Phase**  
**Date: 2026-06-28**

---

## P0 — MUST BUILD IMMEDIATELY

These items block all other work. No exceptions.

```
├── Corporate Ontology Finalization
│   └── Canonical entity definitions for all domains
├── Hermes Core (Event Bus + Routing)
│   └── Agent communication backbone
├── Event Bus Infrastructure
│   └── Laravel events + Redis transport
└── First 4 Agents
    ├── Portfolio Agent
    ├── Photo Agent
    ├── Description Agent
    └── Notification Agent
```

**Total: 4 epics, blocking everything else**

---

## P1 — BUILD DURING SPRINT 3.6

These items complete the core platform.

```
├── Agent Personnel Files (Digital)
│   └── Database schema for agent HR
├── AI Workforce Dashboard
│   └── Real-time monitoring wireframe
├── Standard Agent Contract
│   └── Agent base class implementation
├── Telegram Escalation
│   └── Human approval workflow
├── Event Catalog Implementation
│   └── Event definitions in code
└── n8n Workflow Connectors
    └── Webhook handlers for workflows
```

**Total: 6 epics for Sprint 3.6 completion**

---

## P2 — LATER

These items extend the platform capabilities.

```
├── Presentation Studio
│   └── Multi-format content pipeline
├── Market Intelligence
│   ├── Market Scanner Agent
│   ├── Price Analytics Agent
│   ├── Trend Analysis Agent
│   └── Investment Insights Agent
├── CRM Division
│   ├── Lead Intake Agent
│   ├── Lead Matching Agent
│   └── Lead Follow-up Agent
├── Channel Agent
│   └── Airbnb + Web publishing
├── Knowledge Graph
│   └── SOP + Cross-reference system
├── NotebookLM Integration
│   └── 11-category knowledge structure
└── Corporate Memory Expansion
    └── Drive structure growth
```

**Total: 7 capability areas, expandable**

---

## DEPENDENCY GRAPH

```
                    ┌─────────────────────┐
                    │ Corporate Ontology  │ P0
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │    Hermes Core      │ P0
                    └──────────┬──────────┘
                               │
          ┌────────────────────┼────────────────────┐
          │                    │                    │
┌─────────▼─────────┐ ┌────────▼────────┐ ┌─────────▼─────────┐
│  Event Bus        │ │ Standard Agent  │ │   First 4 Agents  │
│  Infrastructure   │ │    Contract     │ │  (Portfolio, etc) │
└─────────┬─────────┘ └────────┬────────┘ └─────────┬─────────┘
          │                    │                    │
          └────────────────────┼────────────────────┘
                               │
                    ┌──────────▼──────────┐
                    │  P1 Items Open      │
                    │  (Dashboard, etc)   │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  P2 Items Open      │
                    │  (Market, CRM, etc) │
                    └─────────────────────┘
```

---

## COMPLETE CATEGORIES

These categories are **COMPLETE** — no additional items will be added:

| Category | Status | Items |
|----------|--------|-------|
| Platform Core | ✅ Complete | Corporate Ontology, Hermes Core, Event Bus |
| Core Agents | ✅ Complete | Portfolio, Photo, Description, Notification |
| Infrastructure | ✅ Complete | Standard Contract, Dashboard, Escalation |
| Integration | ✅ Complete | Event Catalog, n8n Connectors |
| Market Intelligence | ✅ Complete | Scanner, Price, Trend, Investment |
| Portfolio Management | ✅ Complete | Portfolio, Analytics, Recommendations |
| CRM Division | ✅ Complete | Lead Intake, Matching, Follow-up |
| Publishing | ✅ Complete | Channel Agent, Content Studio, Presentation |
| Knowledge | ✅ Complete | Knowledge Agent, Knowledge Graph, NotebookLM |

**Total: 15 agents across 5 divisions**

---

## NOTHING ELSE

This document represents the complete implementation scope.

No additional categories will be created.

No additional agents will be added without Board approval.

No scope creep beyond these items.

---

*Priority is non-negotiable. P0 must complete before P1 begins.*
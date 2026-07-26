# EIOS Versioning Roadmap — Enterprise Intelligence Operating System

**Version:** 2.0
**Status:** ACTIVE — M1 FOUNDATION LOCKED
**Ratified:** 2026-07-15T10:47:00+03:00
**M1 Certification:** 2026-07-15T11:02:00+03:00
**Framework:** EIOS is a framework — YALIHAN is the reference implementation

---

## Vision

EIOS is no longer a project. EIOS is a **framework**.

Like Laravel provides:
- Service Container
- Routing
- Events
- Queue
- Scheduler

EIOS provides:
- Bootstrap
- Runtime
- Registry
- Plugins
- Profiles
- Knowledge
- Certification
- Evidence
- Metrics

---

## Architecture Levels

```
EIOS Framework
│
├── Core Specification (Stable)
├── Runtime
├── Registry
├── Versioning
├── Bootstrap
└── AGENTS.md
        │
        ▼
YALIHAN OS (Reference Implementation)
```

**YALIHAN is the product. EIOS is the platform.**

---

## Versioning Philosophy

| Principle | Description |
|-----------|-------------|
| **Semantic Versioning** | MAJOR.MINOR.PATCH |
| **MAJOR** | Breaking changes to Core Specification |
| **MINOR** | New capabilities, plugins, or profiles |
| **PATCH** | Bug fixes, clarifications, documentation |

### Version Stability

| Component | Stability |
|-----------|-----------|
| Core Specification | HIGH — Changes require Board Resolution |
| Registry | MEDIUM — Additions allowed, modifications reviewed |
| Plugins | LOW — Additions encouraged |
| Profiles | LOW — Additions encouraged |
| Domain Implementation | LOW — Project-specific |

---

## Document Lifecycle

New ideas follow this path:

```
RFC (Request for Comments)
        ↓
ADR (Architecture Decision Record)
        ↓
Implementation
        ↓
Evidence
        ↓
Certification
        ↓
Core Specification (if mature enough)
```

| Document | Lifecycle | Description |
|---------|-----------|-------------|
| **RFC** | Experimental | New idea, not yet a rule |
| **ADR** | Active | Decision made, awaiting implementation |
| **Core Spec** | Stable | Proven by time and usage |

**Never add directly to Core Specification without RFC → ADR → Evidence cycle.**

---

## Version Roadmap

| Version | Focus | Status | Target |
|---------|-------|--------|--------|
| **v1** | Foundation | ✅ ACTIVE | 2026-07-15 |
| **v2** | Plugin SDK | Planned | Sprint 11+ |
| **v3** | Knowledge Engine | Future | 2026-Q4 |
| **v4** | Certification Engine | Future | 2027 |
| **v5** | Autonomous Runtime | Future | 2027+ |

---

## Strategic Triangle

```
            Human
              │
              ▼
      SAAB (Governance)
              │
              ▼
   EIOS (Execution Platform)
              │
              ▼
 Profiles + Plugins + Runtime
              │
              ▼
     Reference Application
         (YALIHAN OS)
```

---

## EIOS v1 — Foundation (Current)

### Core Constitution Files
- AGENTS.md (Entry Gate)
- EIOS-BOOTSTRAP.md
- EIOS-RUNTIME.md
- REGISTRY.md
- REGISTRY-INDEX.md
- EIOS-PROFILE-SPEC.md
- EIOS-VERSIONING.md
- CORE-CONSTITUTION.md

### Status
✅ FOUNDATION FREEZE — No direct modifications. RFC → ADR required.

---

## EIOS v2 — Plugin SDK

### Vision
Runtime extensions that can be loaded/unloaded dynamically.

### Components
- [ ] Plugin Interface Definition
- [ ] Plugin Registry
- [ ] Plugin Loader
- [ ] Plugin Sandboxing
- [ ] Plugin Metrics

### Plugins
```
SAAB Plugin
Red Team Plugin
Knowledge Curator Plugin
Technology Plugin
UI Plugin
Certification Plugin
```

### Target
Sprint 11+

---

## EIOS v3 — Knowledge Runtime

### Vision
Automated knowledge graph, historian, and drift detection.

### Components
- [ ] Knowledge Graph Engine
- [ ] historian Automation
- [ ] Duplicate Detection
- [ ] Architecture Drift Detection
- [ ] Archive Management

### Target
2026-Q4

---

## EIOS v4 — Certification Runtime

### Vision
Automated certification pipeline with compliance reporting.

### Components
- [ ] Certification Pipeline
- [ ] Certificate Schema
- [ ] Certificate Storage
- [ ] Compliance Reporting
- [ ] Certificate Verification

### Target
2027

---

## EIOS v5 — Autonomous Platform

### Vision
Self-healing, self-optimizing AI engineering platform.

### Components
- [ ] Self-Diagnosis
- [ ] Self-Recovery
- [ ] Self-Optimization
- [ ] Autonomous Decision Making
- [ ] Multi-Domain Support

### Target
2027+

---

## Three-Level Assessment

| Level | Component | Status |
|-------|-----------|--------|
| 1. Platform | EIOS Framework | ✅ Born |
| 2. Product | YALIHAN OS | 🚧 In Development |
| 3. Domain | Real Estate | 🚧 In Development |

---

## Sprint 10-15 Focus

The foundation is complete. The focus now is **proof of value**.

```
Sprint 10 ── Registry First
Sprint 11 ── Property Aggregate
Sprint 12 ── State Machine
Sprint 13 ── Replay Engine
Sprint 14 ── BAI Metrics
Sprint 15 ── Runtime Console
```

### Runtime Commands to Build
- `eios doctor` — Health check
- `eios audit` — Architecture audit
- `eios certify` — Certification
- `eios knowledge` — Knowledge status
- `eios runtime` — Runtime status

**A framework's value is proven in daily use, not in documentation.**

---

## EIOS ↔ SAAB v11 Alignment

| Roadmap Item | SAAB v11 Reference |
|-------------|---------------------|
| EIOS v1 Foundation | §1-§21 (Entire document) |
| EIOS v2 Plugin SDK | §8 AI Workforce |
| EIOS v3 Knowledge Engine | §9 Registry, §14 Enterprise Memory |
| EIOS v4 Certification Engine | §2 Chain: Evidence → Certification |
| EIOS v5 Autonomous Runtime | §17 Milestones (M1-M5) |

---

## Framework Distribution (Future)

### Installation Methods

```bash
# Composer
composer create-project eios/framework

# CLI
eios init

# Git
git clone https://github.com/eios/framework
```

### Project Structure

```
eios-framework/
├── bootstrap/
├── runtime/
├── registry/
├── plugins/
├── profiles/
│   ├── base/
│   └── template/
├── docs/
└── README.md
```

---

## Three-Level Assessment

| Level | Component | Status |
|-------|-----------|--------|
| 1. Platform | EIOS Framework | ✅ Born |
| 2. Product | YALIHAN OS | 🚧 In Development |
| 3. Domain | Real Estate | 🚧 In Development |

---

## Strategic Triangle

```
┌─────────────────────────────────────────┐
│                                         │
│   SAAB v11                              │
│   (Governance Framework)                │
│                                         │
└─────────────────────────────────────────┘
                    ↑
                    │
┌─────────────────────────────────────────┐
│                                         │
│   Core Specification                    │
│   (Technical Constitution)               │
│                                         │
└─────────────────────────────────────────┘
                    ↑
                    │
┌─────────────────────────────────────────┐
│                                         │
│   AGENTS.md                             │
│   (Entry Contract)                      │
│                                         │
└─────────────────────────────────────────┘
                    ↑
                    │
┌─────────────────────────────────────────┐
│                                         │
│   YALIHAN OS                            │
│   (Reference Implementation)             │
│                                         │
└─────────────────────────────────────────┘
```

**This triangle must remain stable. New ideas go through RFC → ADR → Evidence → Certification → Core Spec.**

---

*EIOS Versioning Roadmap v2.0 — Framework Evolution Plan*

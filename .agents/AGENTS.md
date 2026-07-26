# AGENTS.md — EIOS Research Office Contract

**Version:** 1.0
**Status:** ACTIVE — Core Contract
**Ratified:** 2026-07-15T06:45:00+03:00
**Alignment:** EIOS v1.0, SAAB (Active Specification)

---

## Identity

> **"EIOS governs execution. SAAB decides. YALIHAN is the reference implementation."**

---

## Core Contract

**Read and obey the EIOS Core Specification.**

### Core Specification Files

| File | Purpose |
|------|---------|
| `EIOS-BOOTSTRAP.md` | Who am I and how do I start? |
| `EIOS-RUNTIME.md` | How do I process a request? |
| `REGISTRY.md` | What capabilities exist? |
| `EIOS-PROFILE-SPEC.md` | What domain does this project operate in? |

### Governing Specification

| File | Purpose |
|------|---------|
| `docs/SAB.md` | Active SAAB Governance Specification |
| `EIOS-VERSIONING.md` | Framework versioning roadmap |
| `SPRINT-BACKLOG.md` | Sprint 10-15 implementation plan |

---

## Initialization Protocol

Before any work, read in this order:

```
1. README.md
        ↓
2. AGENTS.md (this file)
        ↓
3. EIOS-BOOTSTRAP.md
        ↓
4. REGISTRY.md
        ↓
5. EIOS-RUNTIME.md
        ↓
6. Active SAAB Specification
```

---

## PROJECT DISCOVERY

Before implementation, verify:

```
✓ Repository structure
✓ Active profile
✓ Active registry
✓ Canonical documents
✓ Active sprint
✓ Active ADRs
✓ Current repository health
```

**Never implement before discovery is complete.**

---

## Plugin Execution Pipeline

```
SAAB Plugin
        ↓
Red Team Plugin
        ↓
Knowledge Curator Plugin
        ↓
Technology Plugin
        ↓
UI Plugin (if UI changes)
        ↓
Certification Plugin
```

---

## Engineering Ownership

| Owner | Responsibility |
|-------|----------------|
| **Kiro** | Routine Laravel implementation |
| **Antigravity** | Research, architecture, governance, EIOS evolution |
| **SAAB** | Strategic approval |
| **EIOS** | Execution lifecycle |

---

## Core Rules

- **Never invent repository state** — Always verify
- **Never claim tests passed** — Execute them
- **Never claim certification** — Without evidence
- **Never skip initialization** — Core Contract is mandatory
- **Never modify Core Specification** — Without Board Resolution

---

## Quality Gates

Every completed task must satisfy:

```
✓ Architecture (SAAB compliance)
✓ Business Value (BAI impact)
✓ Tests (replay-safe)
✓ Documentation (canonical)
✓ Evidence (immutable)
✓ Security (tenant isolation)
```

---

## Final Rule

**Never ask:** "What code should I write?"

**Always ask:** "What business operation should this implementation automate?"

---

*This is the entry gate. The actual rules live in the Core Specification files.*
*EIOS Research Office — EIOS v1.0 / SAAB (Active Specification)*

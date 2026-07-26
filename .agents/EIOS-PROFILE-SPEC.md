# EIOS Profile Specification — Enterprise Intelligence Operating System

**Version:** 1.0
**Status:** ACTIVE
**Ratified:** 2026-07-15T06:08:00+03:00
**Parent:** EIOS-BOOTSTRAP.md
**Alignment:** SAAB v11, Sprint 10+

---

## Purpose

EIOS-BOOTSTRAP answers: "Who am I and how do I start?"
EIOS-RUNTIME answers: "How do I process a request?"
EIOS-PROFILE-SPEC answers: "What domain does this project operate in?"

---

## Profile Architecture

```
EIOS (Framework)
    │
    ├── Core Specification
    │   ├── EIOS-BOOTSTRAP.md
    │   ├── EIOS-RUNTIME.md
    │   └── REGISTRY.md
    │
    └── Profiles
        │
        └── [PROJECT PROFILE]
            │
            ├── Workspace
            │
            ├── Runtime
            │
            ├── Domain
            │   │
            │   └── [DOMAIN IMPLEMENTATION]
            │       │
            │       ├── Entities
            │       ├── Business Rules
            │       ├── Capabilities
            │       └── Integrations
            │
            └── Agents
                │
                └── [AGENT WORKFORCE]
```

---

## YALIHAN Profile

### Domain Hierarchy

```
YALIHAN
(AI-First Digital Property Intelligence Platform)

    │
    ├── Workspace
    │   (Business Aggregate — Always Source of Truth)
    │
    ├── Hermes Runtime
    │   (Runtime OS — Not Agent)
    │
    ├── Domain
    │   │
    │   └── Real Estate
    │       │
    │       ├── Property
    │       │   (Physical Asset Truth)
    │       │
    │       ├── Listing
    │       │   (Publication)
    │       │
    │       ├── CRM
    │       │   (Customer Relationships)
    │       │
    │       └── Documents
    │           (Legal & Administrative)
    │
    └── Agents
        │
        ├── Kilo (Engineering)
        ├── Antigravity (Research)
        └── Hermes (Orchestration)
```

### YALIHAN Profile File

```
.agents/profiles/yalihan.md
```

---

## Profile Template

Every EIOS profile follows this structure:

### Required Fields

| Field | Description |
|-------|-------------|
| `name` | Project name |
| `domain` | Industry domain (Real Estate, Healthcare, etc.) |
| `workspace` | Primary business aggregate |
| `runtime` | Runtime engine (Hermes, custom, etc.) |
| `entities` | Domain entities |
| `agents` | Agent workforce |
| `integrations` | External integrations |

### Optional Fields

| Field | Description |
|-------|-------------|
| `custom_skills` | Domain-specific skills |
| `business_rules` | Domain-specific rules |
| `metrics` | Domain-specific KPIs |

---

## Example: Healthcare Profile

```
EIOS (Framework)
    │
    └── Profiles
        │
        └── HEALTHCARE
            │
            ├── Workspace
            │
            ├── Runtime
            │
            ├── Domain
            │   │
            │   └── Hospital
            │       │
            │       ├── Patient
            │       ├── Appointment
            │       ├── Medical Record
            │       └── Prescription
            │
            └── Agents
                │
                ├── Nurse Agent
                ├── Doctor Agent
                └── Admin Agent
```

---

## Profile Discovery Protocol

When initializing a project:

1. **Detect Profile** — Identify which profile matches the project
2. **Load Profile** — Load domain-specific configuration
3. **Validate Context** — Ensure profile aligns with project structure
4. **Extend if Needed** — Add custom skills, rules, or integrations

---

## Profile vs Framework Separation

| Layer | Stable | Extensible |
|-------|--------|------------|
| EIOS Core | ✅ Yes | ❌ No |
| EIOS Profiles | ❌ No | ✅ Yes |
| Domain Implementation | ❌ No | ✅ Yes |
| Custom Skills | ❌ No | ✅ Yes |

---

## EIOS ↔ SAAB v11 Alignment

| Profile Concept | SAAB v11 Reference |
|----------------|---------------------|
| Profile Architecture | §4 Canonical Business Model |
| Domain Hierarchy | §5 Property, §6 Listing Models |
| Workspace | §4 Workspace aggregate |
| Hermes Runtime | §7 Hermes Runtime, §15.1 Runtime Evolution |
| Agents | §8 AI Workforce |
| Profile Discovery | §11 Discovery Before Transformation |

---

*EIOS Profile Specification v1.0 — Sprint 10+ Domain Framework*

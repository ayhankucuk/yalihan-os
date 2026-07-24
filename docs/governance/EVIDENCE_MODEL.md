# Evidence Layer Model

**Ratified By:** SAAB
**Version:** 1.2
**Ratified:** 2026-07-24
**Status:** ACTIVE — Locked at v1.2. No new mandatory fields. Changes follow semantic versioning: patch (v1.2.x) for cosmetics/examples, minor (v1.3) for backward-compatible additions, major (v2.0) for breaking changes.

---

## Overview

Every output in YALIHAN OS must pass through all four evidence layers. No layer may certify the output of another layer.

---

## Layer 1 — Implementation

| | |
|---|---|
| **Owner** | Claude Sonnet / Kilo Agent |
| **Question** | "Is the code correct?" |
| **Output** | Production code, tests, migrations |
| **Standard** | SAAB coding rules, thin controllers, service layer, strong typing, replay-safe events |

---

## Layer 2 — Execution Evidence

| | |
|---|---|
| **Owner** | CI (PHPUnit) |
| **Question** | "Does the code actually run?" |
| **Output** | PHPUnit results, coverage, build logs, runtime evidence |
| **Standard** | All Sprint-critical tests must pass. 0 regressions on certified code. |

---

## Layer 3 — Documentation

| | |
|---|---|
| **Owner** | Antigravity |
| **Question** | "Is the documentation correct?" |
| **Output** | Changelog, Progress Tracker, Certification Debt Register, ADR consistency, governance audit |
| **Standard** | Every completed sprint produces a session record in BEKCI_CHANGELOG.md. CD items are closed with evidence references. |

---

## Layer 4 — Certification

| | |
|---|---|
| **Owner** | SAAB |
| **Question** | "Can certification be granted based on this evidence?" |
| **Output** | `APPROVED` / `CERTIFIED` / `REJECTED` |
| **Standard** | All mandatory exit criteria met. All required evidence present. |

---

## Certification States

| State | Meaning |
|---|---|
| `APPROVED BASED ON REPORTED EVIDENCE` | Report is internally consistent; independent evidence not yet verified by SAAB |
| `CERTIFIED` | All four layers passed; SAAB has verified evidence |
| `REJECTED` | One or more layers failed; remediation required |

---

## Rule

> **No layer may certify the output of another layer.**

- Layer 1 (Code) cannot claim Layer 2 (CI) passed
- Layer 3 (Docs) cannot claim Layer 2 (CI) passed
- Layer 4 (SAAB) must independently evaluate all layers before certifying
- Agents report to their layer. SAAB judges across all layers.

---

## Evidence Requirements by Sprint Type

### Stabilization Sprint (e.g. Sprint 20)
- Layer 1: Test fixes, no new capability
- Layer 2: PHPUnit — all Sprint-critical tests pass, regression count = 0
- Layer 3: CD items closed in CERTIFICATION_DEBT_REGISTER.md, BEKCI_CHANGELOG.md updated
- Layer 4: SAAB reviews and certifies

### Feature Sprint
- Layer 1: New capability implemented
- Layer 2: New tests written and passing, all existing tests still passing
- Layer 3: New ADR if architecture changed, BEKCI_CHANGELOG.md updated, Charter closed
- Layer 4: SAAB reviews and certifies

---

## Sprint Packaging Standard (v1)

**Mandatory for all sprint delivery packages from Sprint 21 onward.**

### 1. Implementation Evidence

**Content:**
- Git commit hash(es)
- Branch name
- List of changed files
- Scope summary

**Purpose:** Clearly shows what changed.

---

### 2. Execution Evidence

**Content:**
- Executed command (e.g., `php artisan test --filter=Sprint21`)
- Raw PHPUnit output
- Total test count
- Total assertion count
- Skip / Fail count
- CI output when applicable

**Purpose:** Proves the code actually runs.

---

### 3. Documentation Evidence

**Content:**
- BEKCI_CHANGELOG.md update (session record)
- Certification Debt Register changes
- ADR updates
- Governance document changes
- Sprint reports

**Purpose:** Proves documentation is in sync with code.

---

### 4. Certification Package

**Content:**
- Open risks
- Closed risks
- Certification scope
- Decision Authority: SAAB
- Final decision

**Decision must be exactly one of:**
- `APPROVED`
- `CERTIFIED`
- `REJECTED`

---

## Evidence Principles

- No layer may certify the output of another layer.
- Raw evidence must be preserved.
- Documentation does not replace execution evidence.
- Certification is distinct from reporting.
- All sprints use the same packaging standard.

---

## Why This Matters

This structure ensures:
- Sprint comparisons are easy
- Audit trail is strong
- Certification process is consistent
- Future Quality Gates can be automated

Compatible with the SAAB v8 lifecycle: Evidence → Testing → Certification → Handoff.

---

## Revision History

| Version | Date | Change |
|---------|------|--------|
| 1.2 | 2026-07-24 | F1: Added Decision Authority field to Certification Package. F2: Added Executed Command field to Execution Evidence. Fixed cosmetic duplicate `---`. |
| 1.1 | 2026-07-24 | Added Sprint Packaging Standard (v1) as mandatory section |
| 1.0 | 2026-07-24 | Initial — Ratified by SAAB |

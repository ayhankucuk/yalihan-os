# EIOS Runtime — Enterprise Intelligence Operating System

**Version:** 1.0
**Status:** ACTIVE — Core Specification
**Ratified:** 2026-07-15T06:03:00+03:00
**Parent:** EIOS-BOOTSTRAP.md
**Alignment:** SAAB v11, Sprint 10+

---

## Purpose

EIOS-BOOTSTRAP answers: "Who am I and how do I start?"
EIOS-RUNTIME answers: "How do I process a request?"

---

## Request Lifecycle

Every request follows this pipeline:

```
1. REQUEST
   User/Agent input received

        ↓

2. PARSING
   Task type identified
   Scope determined
   Context extracted

        ↓

3. DISCOVERY
   Project state assessed
   Canonical documents located
   Active ADRs reviewed

        ↓

4. SKILL DISCOVERY
   Required skills identified
   Dependencies resolved
   Execution order determined

        ↓

5. EXECUTION
   Skills run in order
   Each skill validates output
   Fail-fast on error

        ↓

6. VALIDATION
   All quality gates pass
   Business value confirmed
   Architecture compliance verified

        ↓

7. EVIDENCE
   Tests documented
   Metrics recorded
   Change log updated

        ↓

8. CERTIFICATION
   Evidence package complete
   SAAB certification issued
   Handoff to next phase
```

---

## Skill Discovery Protocol

When a request is received:

### Step 1: Identify Required Skills

| Task Type | Required Skills |
|----------|-----------------|
| New feature | SAAB → Red Team → Tech Reviewer → UI (if UI) → Cert |
| Bug fix | SAAB → Tech Reviewer → Cert |
| Architecture change | SAAB → Red Team → Knowledge Curator → Cert |
| UI change | SAAB → UI Designer → Cert |
| Documentation | SAAB → Knowledge Curator → Cert |
| Research | SAAB → Knowledge Curator → Cert |

### Step 2: Check Dependencies

```
Skill A requires Skill B?
        ↓
Skill B output available?
        ↓
If no → Execute B first
If yes → Continue
```

### Step 3: Determine Execution Order

Skills execute in dependency order:
1. SAAB (always first)
2. Red Team (if risk assessment needed)
3. Knowledge Curator (if documentation validation needed)
4. Technology Reviewer (if code changes)
5. UI/UX Designer (if UI changes)
6. Certification (always last)

---

## Dependency Resolution

### Skill Dependency Map

```
SAAB
  ↑
  ├── Red Team (requires SAAB approval)
  ├── Knowledge Curator (requires SAAB alignment)
  ├── Technology Reviewer (requires SAAB alignment)
  ├── UI/UX Designer (requires SAAB alignment)
  └── Certification (requires all gates)
```

### Resolution Rules

1. **Parallel skills** may run concurrently if no shared dependencies
2. **Sequential skills** must wait for previous output
3. **Blocked skills** wait until dependencies resolve
4. **Failed dependencies** block all dependent skills

---

## Execution Context

### Context Elements

| Element | Description |
|---------|-------------|
| Project Profile | Domain, stack, architecture |
| Active Sprint | Current milestone and goals |
| SAAB Version | Governing architecture version |
| ADRs | Active architecture decisions |
| Registry | Known capabilities and components |
| History | Previous implementations and learnings |

### Context Rules

1. **Context is disposable** — conversation history is not corporate memory
2. **Corporate memory is canonical** — only approved artifacts are authoritative
3. **Context budget applies** — respect token limits per session

---

## Failure Recovery

### Failure Types

| Failure | Response |
|---------|----------|
| Skill execution fails | Fail-fast, report error, stop pipeline |
| Quality gate fails | Report which gate failed, halt certification |
| Dependency unavailable | Attempt resolution, escalate if blocked |
| Architecture drift detected | Halt, report to SAAB |
| Business value unclear | Halt, ask for clarification |

### Recovery Protocol

```
1. Failure detected
        ↓
2. Identify failure type
        ↓
3. Apply recovery action
        ↓
4. If recoverable → retry
   If not recoverable → report and halt
        ↓
5. Document failure in evidence
        ↓
6. Update changelog
```

---

## Certification Flow

### Pre-Certification Checklist

- [ ] Architecture: SAAB compliance verified
- [ ] Business Value: BAI impact confirmed
- [ ] Tests: All pass (unit + integration)
- [ ] Documentation: Updated and canonical
- [ ] Evidence: Immutable records created
- [ ] Security: Auth and permissions verified
- [ ] Tenant Isolation: Zero cross-tenant access
- [ ] Knowledge Integrity: No drift, no duplication

### Certification Output

```
CERTIFIED
├── Timestamp: [ISO 8601]
├── Task: [description]
├── Gates Passed: [list]
├── Evidence: [references]
├── SAAB Version: [v11]
└── EIOS Runtime: [v1.0]
```

### Certification States

```
PENDING → IN_PROGRESS → CERTIFIED → ARCHIVED
                              ↓
                         REJECTED
```

---

## Evidence Generation

### Evidence Requirements

Every implementation produces:

| Evidence Type | Format | Storage |
|--------------|--------|---------|
| Tests | PHPUnit/Laravel Test | `tests/` directory |
| Metrics | JSON/YAML | `docs/evidence/` |
| Changelog | Markdown | `docs/BEKCI_CHANGELOG.md` |
| Registry | JSON | `.sab/proposals/` |
| Certification | JSON | `docs/certifications/` |

### Evidence Rules

1. **Evidence is immutable** — once recorded, never changed
2. **Corrections are append-only** — new evidence corrects old
3. **Evidence is traceable** — links to source artifacts
4. **Evidence is searchable** — indexed by task, date, author

---

## Runtime Configuration

### Default Settings

| Setting | Value |
|---------|-------|
| Max context tokens | 150,000 |
| Warning threshold | 120,000 |
| Freeze threshold | 150,000 |
| Max concurrent skills | 4 |
| Fail-fast | enabled |
| Evidence retention | permanent |

### Runtime Variables

| Variable | Source |
|----------|--------|
| `EIOS_VERSION` | This file |
| `SAAB_VERSION` | `docs/SAB.md` |
| `PROJECT_PROFILE` | `README.md` |
| `SKILL_REGISTRY` | `.agents/REGISTRY.md` |

---

## EIOS ↔ SAAB v11 Alignment

| Runtime Concept | SAAB v11 Reference |
|----------------|---------------------|
| Request Lifecycle | §2 Governance Hierarchy chain |
| Skill Discovery | §16 Quality Gates |
| Dependency Resolution | §2 Governance Hierarchy |
| Execution Context | §14 Enterprise Memory |
| Failure Recovery | §16 Quality Gates + §15 Runtime Principles |
| Certification Flow | §2: Evidence → Certification |
| Evidence Generation | §13 Evidence First |

---

*EIOS Runtime v1.0 — Sprint 10+ Request Processing Engine*

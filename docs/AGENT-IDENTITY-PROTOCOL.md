# Agent Identity Protocol v1.0

**Effective Date:** 2026-08-07
**Version:** 1.0
**Status:** ACTIVE

---

## Rationale

Multiple agents operating on the same capability (EX-002) caused confusion when different agents referenced different code states. WenOX reported "implementation complete" at `fdc794f`, while Klio reviewed at `b8d74a9` — creating contradictory assessments of the same codebase.

The solution: every technical message MUST carry an identity header that makes 5 questions answerable at a glance:

1. Who is speaking?
2. What role are they operating in?
3. Which mission/capability?
4. **Which exact code state** are they referencing?
5. Are they deciding, reviewing, or implementing?

---

## Agent Registry

| Agent | Identity | Roles |
|-------|----------|-------|
| WenOX | Execution Agent | Full implementation, remediation, delivery |
| Klio | Architecture Review Agent | Code inspection, quality gates, blocker identification |
| Kilo | Software Engineering Agent | SAAB Board, governance, certification decisions |
| Ayhan | Operations & Executive Review | Business outcome, pilot evidence, BAI validation |

---

## Mandatory Message Header

Every technical message MUST start with:

```markdown
Agent: [Name]
Identity: [Permanent identity]
Current Role: [Current role in this message]
Mission: EX-[XXX]
Branch: [git branch]
Based On:
  Type: [Commit | Working Tree | Review]
  Reference: [commit hash or "unstaged changes"]
Timestamp: [YYYY-MM-DD HH:MM]
```

---

## Based On — Decision Tree

| Situation | Based On |
|-----------|---------|
| Agent reporting current work | `Working Tree` + most recent commit |
| Klio reviewing a WenOX delivery | `Reviewed Commit: <WenOX commit>` |
| SAAB Board making a decision | `Decision Basis: Klio report + WenOX commit` |
| Ayhan evaluating pilot evidence | `Evidence: <pilot run ID>` |
| Comparing old vs new state | Two separate messages with different `Based On` |

---

## Example Headers

### WenOX — Remediation delivery
```markdown
Agent: WenOX
Identity: Execution Agent
Current Role: Execution Agent
Mission: EX-002
Branch: feature/ex-002-finance-agent
Based On:
  Type: Commit
  Reference: a3f9c12
Timestamp: 2026-08-07 14:45
```

### Klio — Architecture re-review
```markdown
Agent: Klio
Identity: Architecture Review Agent
Current Role: Architecture Review
Mission: EX-002
Based On:
  Type: Reviewed Commit
  Reference: a3f9c12 (WenOX remediation delivery)
Timestamp: 2026-08-07 15:10
```

### Kilo — SAAB decision
```markdown
Agent: Kilo
Identity: Software Engineering Agent
Current Role: SAAB Board Agent
Mission: EX-002
Based On:
  Type: Decision Basis
  Basis: Klio re-review a3f9c12 + WenOX commit a3f9c12
Timestamp: 2026-08-07 16:00
```

### Ayhan — Business review
```markdown
Agent: Ayhan
Identity: Operations & Executive Review
Current Role: SAAB / Operations Review
Mission: EX-001
Based On:
  Type: Evidence
  Reference: Pilot Run #1 — 2026-08-07
  Operational Evidence: 8-point package received
Timestamp: 2026-08-07 17:00
```

---

## Review Gate — Protocol

When Klio reviews WenOX:

1. WenOX announces delivery with `Based On: Commit <hash>`
2. Klio states: `Reviewed Commit: <hash>` in header
3. SAAB Board compares: If `WenOX commit ≠ Klio Reviewed Commit` → rejection
4. SAAB only decides when: WenOX delivery commit = Klio reviewed commit

This prevents the fdc794f / b8d74a9 confusion from recurring.

---

## Current Active Missions

| Mission | WenOX | Klio | Kilo/SAAB | Ayhan |
|---------|-------|------|-----------|-------|
| EX-001 | — | — | ⏳ | 🟡 Pilot evidence |
| EX-002 | 🔄 Remediation | ⏳ | ⏸ | — |
| EX-003 | ⏸ | ⏸ | ⏸ | — |

---

## Decision Authority Matrix

| Decision Type | Authority | Requires |
|---------------|----------|---------|
| Implementation delivery | WenOX | Commit hash in header |
| Architecture BLOCKER | Klio | Reviewed commit in header |
| Certification gate | SAAB Board | Klio report + WenOX commit match |
| Business outcome | Ayhan | Pilot evidence |
| Priority ranking | SAAB Board | All reviews complete |

---

*Protocol v1.0 — Active 2026-08-07*

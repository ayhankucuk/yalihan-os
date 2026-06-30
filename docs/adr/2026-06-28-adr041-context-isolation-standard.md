# ADR-041: Context Isolation Standard

**Date:** 2026-06-28
**Status:** IMPLEMENTED
**Owner:** Architecture Office
**Authority:** SAB v6
**Priority:** P0 (Mandatory)

---

## Problem

Long-running AI Office sessions eventually exceed the model context window.

This creates:
- Context overflow
- Higher token cost
- Lower reasoning quality
- Non-deterministic behaviour
- Session instability

The issue is architectural, not operational.

---

## Decision

Architecture Office establishes Context Isolation as a mandatory enterprise architecture rule.

Every AI Office is an independent bounded context.

Conversation history is not corporate memory.

Corporate memory exists only in approved artifacts.

---

## Mandatory Inputs

An Office may consume only:
- Approved ADRs
- Architecture Decisions
- Previous Office Reports
- Executive Summaries
- Corporate Ontology
- Capability Specifications

An Office must not reload complete historical conversations.

---

## Session Lifecycle

1. Open Office Session
2. Produce Deliverables
3. Publish Documents
4. Close Session
5. Archive Conversation
6. Continue from Documents only

---

## Maximum Context Budget

| Context Size | Status |
|---|---|
| 0–80K tokens | Normal |
| 80–120K | Warning |
| 120–150K | Freeze |
| >150K | Archive & New Session |

---

## Session Policy

| Threshold | Action |
|---|---|
| Soft limit (80K) | Warning — consider archiving |
| Archive after (120K) | Freeze — publish and close |
| Hard stop (150K) | New session mandatory |

---

## Enterprise Principle

```
Corporate Memory ≠ Conversation History

Corporate Memory =
  • Reports
  • ADRs
  • Ontology
  • Capability Documents
  • Architecture Specifications

Conversation history is disposable.
```

---

## Compliance

This rule applies to:
- Architecture Office
- Research Office
- Business Office
- Operations Office
- Knowledge Office
- Integration Office
- Future AI Offices
- Hermes Orchestrator

---

## Expected Benefits

- Unlimited organizational scaling
- Predictable AI behaviour
- Lower AI cost
- Better reasoning quality
- Independent Office execution
- Support for 500+ Digital Employees

---

## Implementation Evidence

- `docs/adr/2026-06-28-adr041-context-isolation-standard.md` — ADR file created
- `docs/SAB.md` — Rule 17 (Phase 15) added
- `.sab/authority.json` — context_isolation configuration block added
- `docs/SAB.sha256` — checksum regenerated

---

## Board Resolution

Architecture Office approves Context Isolation as a permanent SAAB architectural standard.

**Decision:** APPROVED → IMPLEMENTED

**Owner:** Architecture Office

**Effective:** 2026-06-28

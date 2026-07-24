# Architectural Decision Records (ADR)
**Ratified Charter:** SAAB v11.1 (Governance Frozen)  
**Status:** ACTIVE  
**Last Updated:** 2026-07-24  

---

## Purpose

Architectural Decision Records (ADRs) document significant architectural decisions to prevent context drift and enable self-protecting governance. They capture the context, alternatives considered, decision details, consequences, and primary codebase evidence.

---

## Status Lifecycle

Every ADR transitions through the following lifecycle states:

```
Proposed ➔ Accepted ➔ Implemented ➔ Certified ➔ Active ➔ Superseded / Rejected / Deprecated
```

*   **Proposed:** The ADR has been drafted and is under review by the SAAB.
*   **Accepted:** The decision has been approved by the SAAB, and implementation is authorized.
*   **Implemented:** The decision has been coded and merged into the repository.
*   **Certified:** The implementation has passed all automated quality gates and tests.
*   **Active:** The decision is currently in effect across the codebase.
*   **Superseded:** Replaced by a newer ADR (must reference the successor ADR).
*   **Rejected:** The proposal was reviewed and declined by the SAAB.
*   **Deprecated:** Kept for historical reference, but no longer enforced or active.

---

## Naming Convention

ADRs are stored in this directory (`docs/adr/`) and follow the naming format:
```
NNN-kebab-case-title.md
```
Where `NNN` is a zero-padded, three-digit sequential integer (e.g. `001-workspace-business-aggregate.md`).

---

## Enforcement & Code Integration

*   **SAAB Approval Requirement:** No ADR can transition to `Accepted` or `Active` without a recorded Board Resolution and approval check.
*   **Relationship to Code & Tests:** Every `Accepted` or `Active` ADR must reference concrete codebase classes, database columns, or feature tests that demonstrate compliance.
*   **Relationship to ARCHITECTURE.md:** When an ADR changes state, [ARCHITECTURE.md](../ARCHITECTURE.md) must be updated to reflect the new component states.
*   **Superseding Process:** Historical records must not be edited or deleted. When a decision changes, a new ADR is created, and the old ADR is updated to `Superseded by ADR-NNN`.

---

## Standard Template

All future ADRs must be written using the following template:

```markdown
# ADR-NNN: Title

## Status
[Proposed | Accepted | Implemented | Certified | Active | Superseded by ADR-YYY | Deprecated | Rejected]

## Date
YYYY-MM-DD

## Context
[What is the problem or opportunity? What constraints, options, or legacy issues exist?]

## Decision
[What is the chosen design or pattern? Be specific and clear.]

## Alternatives Considered
### Option 1: [Name]
*   **Pros:** [Benefits]
*   **Cons:** [Drawbacks]
*   **Reason for Rejection:** [Why not chosen]

### Option 2: [Name]
*   **Pros:** [Benefits]
*   **Cons:** [Drawbacks]
*   **Reason for Rejection:** [Why not chosen]

## Consequences
### Positive
*   [Expected improvements]

### Negative
*   [Trade-offs and costs]

### Risks
*   [Known risks and mitigations]

## Evidence
*   **Classes/Interfaces:** [FQCN path]
*   **Database Migrations:** [Migration file names]
*   **Automated Tests:** [Test names/paths]

## Related Decisions
*   [ADR-XXX](XXX-title.md)

## Supersedes
*   [ADR-YYY]

## Superseded By
*   [ADR-ZZZ]

## SAAB Approval
*   **Board Resolution:** [BR-YYYYMMDD-ID]
*   **Status:** [APPROVED | AWAITING REVIEW]
```

---

## Existing ADR Registry

| # | File | Title | Date | Status |
|---|---|---|---|---|
| 001 | [context7-canonical-turkish-fields](2026-02-15-context7-canonical-turkish-fields.md) | Context7 Kanonik Türkçe Alan Adları | 2026-02-15 | ✅ Active |
| 002 | [performance-regression-ci-gate](2026-02-15-performance-regression-ci-gate.md) | Performance Regression CI Gate | 2026-02-15 | ✅ Active |
| 003 | [no-raw-fetch-policy](2026-02-15-no-raw-fetch-policy.md) | Ham Fetch Yasağı Politikası | 2026-02-15 | ✅ Active |
| 004 | [governance-simplification-analysis](2026-02-15-governance-simplification-analysis.md) | Governance Basitleştirme Analizi | 2026-02-15 | ✅ Active |
| 005 | [api-contract-freeze](2026-02-15-api-contract-freeze.md) | API Kontrat Dondurma | 2026-02-15 | ✅ Active |
| 006 | [feature-assignments-architectural-freeze](2026-02-21-feature-assignments-architectural-freeze.md) | Feature Assignment Mimari Dondurma | 2026-02-21 | ✅ Active |
| 007 | [governance-enforcement-layer](2026-02-21-governance-enforcement-layer.md) | Governance Uygulama Katmanı | 2026-02-21 | ✅ Active |
| 008 | [ssot-determinism-constitution](2026-02-21-ssot-determinism-constitution.md) | SSOT Determinizm Anayasası | 2026-02-21 | ✅ Active |
| 009 | [controller-mutation-delegation-batch5](2026-03-02-controller-mutation-delegation-batch5.md) | Controller Mutation Delegation | 2026-03-02 | ✅ Active |
| 010 | [sab-production-seal-v1](2026-03-03-sab-production-seal-v1.md) | SAB Production Seal v1 | 2026-03-03 | ✅ Active |
| 011 | [ai-decision-engine](2026-04-03-ai-decision-engine.md) | AI Karar Motoru | 2026-04-03 | ✅ Active |
| 012 | [sidebar-5-layer-architecture](2026-04-03-sidebar-5-layer-architecture.md) | Sidebar 5 Katmanlı Mimari | 2026-04-03 | ✅ Active |
| 013 | [sab4-multi-agent-orchestration](2026-04-04-sab4-multi-agent-orchestration.md) | SAB4 Multi-Agent Orkestrasyon | 2026-04-04 | ✅ Active |
| 014 | [sab8-decision-action-feedback-loop](2026-04-04-sab8-decision-action-feedback-loop.md) | SAB8 Karar-Aksiyon Döngüsü | 2026-04-04 | ✅ Active |
| 015 | [env-drift-guard-contract](2026-04-10-env-drift-guard-contract.md) | Env Drift Guard Kontratı | 2026-04-10 | ✅ Active |
| 016 | [h1-ledger-legacy-import-migration](2026-04-21-h1-ledger-legacy-import-migration.md) | Ledger Legacy Import Migration | 2026-04-21 | ✅ Active |
| 017 | [h4-testing-environment-schema-authority](2026-04-21-h4-testing-environment-schema-authority.md) | Test Ortamı Schema Otoritesi | 2026-04-21 | ✅ Active |
| 018 | [h7-problem-analyzer-v1-pack-p0](2026-04-21-h7-problem-analyzer-v1-pack-p0.md) | Problem Analyzer v1 Pack P0 | 2026-04-21 | ✅ Active |
| 019 | [bekci-v2-1-cognitive-guardian-ast](2026-05-15-bekci-v2-1-cognitive-guardian-ast.md) | Bekçi v2.1 Bilişsel Muhafız AST | 2026-05-15 | ✅ Active |
| 020 | [governance-diff-viewer-cli-read-model](020-governance-diff-viewer-cli-read-model.md) | Governance Diff Viewer CLI Read Model | — | ✅ Active |
| 021 | [sprint2-architecture-decisions](2026-06-15-sprint2-architecture-decisions.md) | Sprint 2 Mimari Kararları (#19,#28,#58,#60) | 2026-06-15 | ✅ Active |
| 041 | [context-isolation-standard](2026-06-28-adr041-context-isolation-standard.md) | Context Isolation Standard | 2026-06-28 | ✅ Active |
| 042 | [adr042-property-aggregate-root-design](2026-07-15-adr042-property-aggregate-root-design.md) | Property Aggregate Root Design | 2026-07-15 | ✅ Active |

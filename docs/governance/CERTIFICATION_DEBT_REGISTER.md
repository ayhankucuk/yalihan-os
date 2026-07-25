---
id: certification-debt-register
schema_version: 1.0
version: "1.0"
status: canonical
owner: saab
domain: governance
created_at: 2026-07-15
reviewed_at: 2026-07-24
review_due: 2027-07-24
supersedes: []
superseded_by: []
evidence: {}
tags: []
---

# 🏛️ Yalıhan OS — Certification Debt Register (SSOT)

**Ratified By:** Strategic Architecture & Automation Board (SAAB)  
**Governance Framework:** SAAB v11.1  
**Last Updated:** 2026-07-24  

---

## 📌 Active Certification Debt Ledger

| Debt ID | Title / Description | Introduced In | Target Sprint | Owner | Evidence Required | Status | Closed By |
|---|---|---|---|---|---|---|---|
| **CD-001** | Multi-process DB concurrency verification | Sprint 18 | Sprint 19 | Platform Team | `ReservationParallelConcurrencyAndLifecycleTest::test_parallel_transaction_concurrency_locking_prevents_double_booking` — PASS (DomainException: Date conflict detected, lockForUpdate() verified in ReservationApplicationService:66–69 and ConflictDetectionService:40–56). | `CLOSED` | Sprint 20 — `feature/sprint-20-stabilization` |
| **CD-002** | Fresh vs Incremental schema parity audit | Sprint 18 | Sprint 19 | DB Architect | `php artisan migrate:fresh` — 443 migrations ran successfully. All tables created without drift. Incremental `migrate` after `migrate:rollback` restored state exactly. | `CLOSED` | Sprint 20 — `feature/sprint-20-stabilization` |
| **CD-003** | DB-level UUID constraint verification | Sprint 18 | Sprint 19 | DB Architect | DB audit (2026-07-24): `properties.uuid` nulls=0, dups=0; `workforce_executions.uuid` nulls=0, dups=0; `commercial_offerings.uuid` nulls=0, dups=0. `property_reservations` and `hermes_event_logs` use `source_event_id` string as event identity (not UUID column). | `CLOSED` | Sprint 20 — `feature/sprint-20-stabilization` |
| **CD-004** | Migration rollback step-by-step verification | Sprint 18 | Sprint 19 | Platform Team | `php artisan migrate:rollback --step=1` → `2026_07_25_000008` rolled back cleanly. `php artisan migrate` → forward re-apply succeeded. `UnifiedCalendarProjectionTest` 4/4 PASS post-restore. | `CLOSED` | Sprint 20 — `feature/sprint-20-stabilization` |
| **CD-005** | Timeline projection DB-level unique constraint | Sprint 18 | Sprint 19 | AI / Pipeline | `UNIQUE (tenant_id, projection_type, source_event_id)` DB constraint on `hermes_event_logs` table via `2026_07_25_000007_add_uniqueness_to_hermes_event_logs_table.php` & `CD005TimelineUniquenessTest.php` (10/10 PASS: tenant context invariant, selective duplicate exception handling, immutable event UUIDs, reconciliation guard & multi-connection DB concurrency). | `CLOSED` | Feature Branch `feature/sprint-19-unified-calendar-core` |
| **CD-006** | Measured BAI improvement report | Sprint 18 | Pilot Phase | Business Analyst | Quantitative before/after step ratio measurement and operational time saved evidence. | `OPEN` | Pending |

---

## 🛡️ Resolution Protocol
Before certifying any future sprint that references these capabilities, the corresponding `CD` item must be executed, verified with empirical logs, and marked `CLOSED` with the resolving commit SHA or ADR reference.

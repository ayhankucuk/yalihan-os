# 🏛️ Yalıhan OS — Certification Debt Register (SSOT)

**Ratified By:** Strategic Architecture & Automation Board (SAAB)  
**Governance Framework:** SAAB v11.1  
**Last Updated:** 2026-07-24  

---

## 📌 Active Certification Debt Ledger

| Debt ID | Title / Description | Introduced In | Target Sprint | Owner | Evidence Required | Status | Closed By |
|---|---|---|---|---|---|---|---|
| **CD-001** | Multi-process DB concurrency verification | Sprint 18 | Sprint 19 | Platform Team | Multi-connection DB barrier test output proving 1 success / 1 conflict under concurrent execution. | `OPEN` | Pending |
| **CD-002** | Fresh vs Incremental schema parity audit | Sprint 18 | Sprint 19 | DB Architect | Automated `migrate:fresh` vs incremental schema diff report producing 0 column/index drift. | `OPEN` | Pending |
| **CD-003** | DB-level UUID constraint verification | Sprint 18 | Sprint 19 | DB Architect | DB audit query output showing `null count = 0`, `duplicate count = 0`, and active `NOT NULL` DB constraint. | `OPEN` | Pending |
| **CD-004** | Migration rollback step-by-step verification | Sprint 18 | Sprint 19 | Platform Team | Safe `php artisan migrate:rollback` execution logs and forward-fix verification. | `OPEN` | Pending |
| **CD-005** | Timeline projection DB-level unique constraint | Sprint 18 | Sprint 19 | AI / Pipeline | `UNIQUE (tenant_id, source_event_id, projection_type)` DB constraint on Timeline table. | `OPEN` | Pending |
| **CD-006** | Measured BAI improvement report | Sprint 18 | Pilot Phase | Business Analyst | Quantitative before/after step ratio measurement and operational time saved evidence. | `OPEN` | Pending |

---

## 🛡️ Resolution Protocol
Before certifying any future sprint that references these capabilities, the corresponding `CD` item must be executed, verified with empirical logs, and marked `CLOSED` with the resolving commit SHA or ADR reference.

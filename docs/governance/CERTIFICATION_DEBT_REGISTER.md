# 🏛️ Yalıhan OS — Certification Debt Register (SSOT)

**Ratified By:** Strategic Architecture & Automation Board (SAAB)  
**Governance Framework:** SAAB v11.1  
**Last Updated:** 2026-07-24  

---

## 📌 Active Certification Debt Ledger

| ID | Title | Target Sprint | Category | Owner | Status | Evidence Criteria |
|---|---|---|---|---|---|---|
| **CD-001** | True Multi-Process Concurrency Evidence | Sprint 19 | Concurrency & Concurrency Lock | Platform Team | `OPEN` | Multi-connection DB barrier test output proving 1 success / 1 conflict under concurrent execution. |
| **CD-002** | Fresh vs Incremental Schema Parity Report | Sprint 19 | Database Governance | DB Architect | `OPEN` | Automated `migrate:fresh` vs incremental schema diff report producing 0 column/index drift. |
| **CD-003** | UUID Constraint Verification | Sprint 19 | Data Integrity | DB Architect | `OPEN` | DB audit query output showing `null count = 0`, `duplicate count = 0`, and active `NOT NULL` DB constraint. |
| **CD-004** | Migration Rollback Verification | Sprint 19 | Migration Governance | Platform Team | `OPEN` | Safe `php artisan migrate:rollback` execution logs and forward-fix verification. |
| **CD-005** | Timeline Projection DB Uniqueness | Sprint 19 | Event Bus & Projections | AI / Pipeline Team | `OPEN` | `UNIQUE (tenant_id, source_event_id, projection_type)` DB constraint on Timeline table. |
| **CD-006** | Measured BAI Improvement Report | Pilot Phase | Business Automation Index | Business Analyst | `OPEN` | Quantitative before/after step ratio measurement and operational time saved evidence. |

---

## 🛡️ Resolution Protocol
Before certifying any future sprint that references these capabilities, the corresponding `CD` item must be executed, verified with empirical logs, and marked `RESOLVED`.

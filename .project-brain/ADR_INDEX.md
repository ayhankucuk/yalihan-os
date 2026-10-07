# Architecture Decision Index

## Rules

- Use `docs/adr/ADR-TEMPLATE.md` for every material architecture decision.
- One decision per ADR; do not hide alternatives or consequences.
- Record the Git baseline and verification evidence.
- A proposed decision is not an implementation authorization.
- Superseded decisions remain for historical traceability.

## Active Decisions

| ADR | Topic | Status | Physical Artifact |
|---|---|---|---|
| ADR-006 | Channel Manager Provider Architecture | ACCEPTED | `docs/adr/ADR-006-Channel-Manager-Provider-Architecture.md` |
| ADR-007 | Channel Manager Webhook Ingest | ACCEPTED | `docs/adr/ADR-007-Channel-Manager-Webhook-Ingest.md` |
| ADR-008 | Channex Reservation Lifecycle | ACCEPTED | `docs/adr/ADR-008-Channex-Reservation-Lifecycle.md` |
| ADR-009 | Booking.com Reservation Provider Architecture | ACCEPTED | `docs/adr/ADR-009-Booking.com-Reservation-Provider-Architecture.md` |
| ADR-010 | Production Frontend Asset Ownership | PROPOSED | `docs/adr/ADR-010-Production-Frontend-Asset-Ownership.md` |
| **ADR-044** | **Emlak Proje / Team Proje Bounded Context Separation** | **ACCEPTED** | **`docs/adr/2026-09-17-adr044-emlak-proje-bounded-context.md`** |

## Known Historical Numbering Drift

> WARNING: The following historical ADR numbers are documented in repository artifacts but have gaps or conflicting references. OUT OF SCOPE for ADR_CANONICAL_CONVERGENCE_01.

| ADR | Status | Note |
|---|---|---|---|
| ADR-002 | KNOWN HISTORICAL COLLISION | Out of scope |
| ADR-003 | KNOWN HISTORICAL COLLISION | Out of scope |
| ADR-021 | KNOWN HISTORICAL COLLISION | Out of scope |

ADR numbers are NOT required to be contiguous. Index reflects actual artifacts only.

## Decision Log Linkage

Short operational decisions stay in `.project-brain/DECISION_LOG.md`; durable architecture decisions belong here and in `docs/adr/`.

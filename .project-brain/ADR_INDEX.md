# Architecture Decision Index

## Rules

- Use `docs/adrs/ADR-TEMPLATE.md` for every material architecture decision.
- One decision per ADR; do not hide alternatives or consequences.
- Record the Git baseline and verification evidence.
- A proposed decision is not an implementation authorization.
- Superseded decisions remain for historical traceability.

## Existing decisions

| ADR | Topic | Status | Note |
|---|---|---|---|
| ADR-006 | Channel Manager provider architecture | Repository document | Supporting/historical status must be checked against ERA V |
| ADR-007 | Channel Manager webhook ingest | Repository document | Channel integration lineage |
| ADR-008 | Channex reservation lifecycle | Repository document | Reservation event lifecycle |
| ADR-009 | Booking.com provider architecture | ACCEPTED | Provider protocol and acknowledgement invariant |
| ADR-010 | Production frontend asset ownership | PROPOSED | Requires explicit decision before production fix |

## Decision log linkage

Short operational decisions stay in `.project-brain/DECISION_LOG.md`; durable architecture decisions belong here and in `docs/adrs/`.

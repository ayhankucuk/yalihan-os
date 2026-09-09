# Drift Audit Historical Reports

## Purpose

This index separates historical audit snapshots from current evidence. Historical reports are retained for auditability and must not override newer repository or live VPS evidence.

## Superseded snapshots

- `LATEST.md` and `PHASE1_SENTINEL.md` — generated before the Phase 2 production repair and seeder execution.
- `drift-audit-2026-09-02T13-*.md` — local pre-repair snapshots.
- `drift-audit-2026-09-02T17-42-17.md` — local pre-repair snapshot; its 87 findings are not a current production count.

## Current evidence source

Use `.project-brain/EVIDENCE_INDEX.md` for the post-`17aba4b` production evidence:

- 106 template assignments: 35 / 36 / 35
- 144 `canonical_seed` assignments
- 84 `legacy_repair_2026_09_02` records, preserved and not deleted
- G4 `aidat` suggested (`required=false`), `depozito` required (`required=true`)

## Evidence rule

Do not delete historical reports. When a newer report supersedes an older one, label the older artifact `HISTORICAL / SUPERSEDED` and link the current evidence instead.

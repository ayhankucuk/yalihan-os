# 03_DECISIONS.md — Sprint 4.6

## Architectural Decisions

### Decision 1: Read from Existing Models, Don't Duplicate

**Context:** Where does the Dashboard get its data?

**Decision:** The Dashboard reads from existing models and tables. No new tables.
- Ilan → `ilanlar` table
- Timeline → `hermes_event_logs` table (already populated by AI agents)
- Health → Calculated from existing data + event log presence
- Documents → `documents` table or filesystem

**Rationale:** Avoids data duplication and sync issues.

---

### Decision 2: Health Score Is Calculated, Not Stored

**Context:** Should the Health Score be calculated on-the-fly or stored?

**Decision:** Calculated on-the-fly in `WorkspaceHealthService`.
No `workspace_health` table or cached value.

**Rationale:**
- Score depends on real-time data (photos, AI completion)
- No stale data risk
- Calculation is fast (simple count/sum queries)

---

### Decision 3: Hermes Event Logs as Timeline Source

**Context:** What is the source of truth for the Timeline?

**Decision:** `hermes_event_logs` table.
Each AI agent already logs events there via Hermes event dispatch.

**Rationale:** Existing infrastructure, already populated, chronological.

---

### Decision 4: Dashboard Is Admin-Only

**Context:** Should the owner see the Workspace Dashboard too?

**Decision:** Admin-only for Sprint 4.6.
Owner sees their portal view (Sprint 4.2).

**Rationale:** Keep scope tight. Owner Dashboard is a separate sprint.

---

### Decision 5: Tailwind + Alpine.js (same as existing admin UI)

**Context:** What UI stack?

**Decision:** Same as existing admin UI — Tailwind CSS + Alpine.js.

**Rationale:** Consistent with existing codebase. No new dependencies.

---

## Rejected Approaches

| Approach | Reason for Rejection |
|----------|----------------------|
| Separate Workspace table | Creates sync complexity |
| Stored/cached health score | Stale data risk |
| Livewire component | New stack, out of scope |
| Vue/React SPA | Out of scope |
| Owner-accessible Dashboard | Separate sprint |

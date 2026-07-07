# Sprint 4.6 — Property Digital Twin Cockpit

## Charter

**Sprint:** 4.6
**Title:** Property Digital Twin Cockpit
**Start:** 2026-07-04
**Status:** ACTIVE
**SAAB Status:** APPROVED (SAAB Board Resolution)
**Branch:** main

---

## Mission

> "Build the first production-grade Property Digital Twin Cockpit. The Dashboard becomes the operational center for every Workspace — displaying Lifecycle State, AI Completion, Agent Status, Drive, CRM, Publishing, and Timeline without opening multiple screens."

---

## Scope

### In Scope
- [ ] Workspace Dashboard (main view)
- [ ] Workspace Timeline Component (chronological event history)
- [ ] Workspace Health Score Component
- [ ] Workspace Metrics API endpoint
- [ ] Workspace Summary API endpoint
- [ ] Workspace Event API endpoint
- [ ] Dashboard Tests
- [ ] All quality gates pass

### Out of Scope (Explicitly Excluded)
- [ ] Telegram integration
- [ ] Async Queue / Event Replay Engine
- [ ] Drive Sync
- [ ] New AI Agents
- [ ] Google Drive / Google Docs integration
- [ ] Architectural redesign
- [ ] New business features

---

## Definition of Done

| # | Criterion | Method |
|---|-----------|--------|
| 1 | Workspace Dashboard renders | Browser test |
| 2 | Timeline shows chronological events | Unit test |
| 3 | Health Score calculates correctly | Unit test |
| 4 | All APIs return valid JSON | API test |
| 5 | Tests pass | `php artisan test` |
| 6 | sab:dirty scan clean | `php artisan sab:integrity-scan --dirty` |

---

## Exit Criteria

Sprint closes when:
- [ ] Workspace Dashboard visible at `/admin/workspace/{id}`
- [ ] Timeline component shows all Hermes events
- [ ] Health Score displays per-category breakdown
- [ ] All API endpoints return valid responses
- [ ] Dashboard tests pass
- [ ] Evidence collected
- [ ] Certification signed
- [ ] Handoff generated
- [ ] All commits pushed

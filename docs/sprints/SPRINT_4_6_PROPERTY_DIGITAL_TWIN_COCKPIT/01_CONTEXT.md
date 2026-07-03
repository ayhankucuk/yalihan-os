# 01_CONTEXT.md — Sprint 4.6

## How We Got Here

### Previous Sprint Closing (Sprint 4.2)
Sprint 4.2 (Real CRUD Certification) closed with:
- Owner Portal CRUD fully functional
- 12/15 OwnerIlanCrudTest passing (3 pre-existing SQLite failures)
- SAAB v7 + YSOS v1.0 adopted as Engineering Constitution

### What Needs to Happen Next

**ERA III — Digital Property Intelligence** has officially started.
The next logical step is the **Property Digital Twin Cockpit** — the operational
center for every Workspace.

From ROADMAP.md Phase Roadmap:
```
Phase B: Workspace Dashboard — Property Digital Twin Cockpit ← Sprint 4.6
Phase A: YSOS Automation ← Phase C
```

---

## Why This Sprint Exists

The Workspace is now the canonical business aggregate (SAAB v7 Article II).
But there is no single view to see the complete state of a Workspace.

Today:
- Owner sees their ilan in owner portal
- Admin sees ilan in admin panel
- AI events are logged in Hermes
- Documents are in Drive
- CRM data is scattered

**The Dashboard doesn't exist.**

---

## Technical Context

### What is a Property Digital Twin?

A **Property Digital Twin** is the complete digital representation of a property:
- All lifecycle events (created, updated, published)
- All AI analysis results (photos, descriptions, scores)
- All documents (title deeds, energy certificates)
- All CRM data (owner, inquiries)
- All publishing status (Airbnb, Booking, Sahibinden)
- Health score calculated from completeness

### Where Does Data Live?

| Domain | Location |
|--------|----------|
| Ilan metadata | `ilanlar` table |
| AI events | `hermes_event_logs` table |
| Documents | `drive_files` / filesystem |
| CRM | `kisiler` + `talepler` tables |
| Publishing | `portal_baglantisi` or similar |
| Timeline | `hermes_event_logs` |

### Existing Dashboard Infrastructure

| Component | Status |
|-----------|--------|
| Hermes Event Bus | ✅ Operational |
| Hermes Analytics | ✅ Exists (`HermesAnalytics` model) |
| Ilan Analytics | ✅ `IlanAnalyticsService` |
| Event Timeline | ✅ Partially exists |
| Health Score | ⚠️ Does not exist yet |

---

## Sprint Boundary

**What belongs to Sprint 4.6:**
- Building the Property Digital Twin Cockpit view
- Timeline component (from Hermes events)
- Health Score calculation
- Workspace Metrics/Summary/Events API endpoints
- Dashboard tests

**What does NOT belong:**
- Telegram integration (Sprint 4.9)
- Async Queue / Event Replay (Sprint 4.7)
- Drive Sync (Sprint 4.8)
- New AI Agents
- Architecture changes

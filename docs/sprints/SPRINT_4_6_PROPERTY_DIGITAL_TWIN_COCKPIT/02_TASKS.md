# 02_TASKS.md — Sprint 4.6

## Task List

### P0 — Must Have

#### Task 1: Workspace Dashboard View
**Priority:** P0
**Type:** Feature Implementation
**Files:** `resources/views/admin/workspace/`, route, controller

**Implementation:**
```
Route: GET /admin/workspace/{id}
Controller: WorkspaceDashboardController@show
View: resources/views/admin/workspace/dashboard.blade.php
Layout: @extends('layouts.admin')
```

**Dashboard Sections:**
- Header: Property info (baslik, il/ilce, fiyat)
- Status badge: yayin_durumu enum label
- Timeline: Chronological events
- Health Score: Visual breakdown
- Quick stats: Photos, Documents, AI completion

---

#### Task 2: Workspace Timeline Component
**Priority:** P0
**Type:** Feature Implementation
**Files:** `resources/views/admin/workspace/components/timeline.blade.php`, service

**Implementation:**
```
Timeline shows all Hermes events for this ilan
Order: chronological (oldest first)
Each event: icon + event name + timestamp + agent name
Event types: created, updated, ai_photo_analysis, ai_description, published, etc.
```

---

#### Task 3: Workspace Health Score
**Priority:** P0
**Type:** Feature Implementation
**Files:** `WorkspaceHealthService` (new), blade component

**Health Categories:**
| Category | Weight | Scoring |
|----------|---------|---------|
| Documents | 20% | Title deed + Energy cert |
| Media | 20% | Photos ≥ 5 |
| AI | 20% | Photo analysis + Description |
| CRM | 15% | Owner + Contact |
| Publishing | 15% | Portal status |
| Compliance | 10% | Required fields |

**Score: 0-100%**

---

#### Task 4: Workspace API Endpoints
**Priority:** P0
**Type:** Feature Implementation
**Files:** `WorkspaceApiController`, routes

**Endpoints:**
```
GET /api/workspace/{id}/summary     → Property summary JSON
GET /api/workspace/{id}/events      → Timeline events JSON
GET /api/workspace/{id}/health     → Health score JSON
GET /api/workspace/{id}/metrics    → Detailed metrics JSON
```

---

#### Task 5: Dashboard Tests
**Priority:** P0
**Type:** Test
**Files:** `tests/Feature/WorkspaceDashboardTest.php`

**Test Cases:**
- Dashboard renders for valid workspace
- Dashboard returns 404 for invalid workspace
- Timeline shows events in chronological order
- Health score calculates correctly
- API endpoints return valid JSON

---

### P1 — Should Have

#### Task 6: Workspace Navigation (breadcrumb + sidebar)
**Priority:** P1
**Link from:** Ilan detail page → Workspace Dashboard

---

## Task Execution Order

```
Task 3 (Health Service)    ← Infrastructure
       ↓
Task 4 (API Endpoints)    ← Data layer
       ↓
Task 1 (Dashboard View)   ← UI layer
       ↓
Task 2 (Timeline)         ← Within dashboard
       ↓
Task 6 (Navigation)        ← Integration
       ↓
Task 5 (Tests)            ← Verification
       ↓
Quality Gates             ← Sprint close
```

---

## Verification Plan

| Task | Verification Method | Expected Result |
|------|---------------------|-----------------|
| Dashboard | Browser / screenshot | Renders without error |
| Timeline | Unit test | Events in order |
| Health Score | Unit test | Score 0-100% |
| API | `php artisan test --filter=WorkspaceApi` | 100% pass |
| All gates | `php artisan ysos:sprint:validate` | All pass |

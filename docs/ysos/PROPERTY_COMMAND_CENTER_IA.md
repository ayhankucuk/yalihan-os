# YALIHAN OS — Property Command Center Information Architecture v1.0

> Document: `docs/ysos/PROPERTY_COMMAND_CENTER_IA.md`  
> Generated: 2026-07-25  
> Status: READ-ONLY Discovery + IA Blueprint  
> Sprint: Sprint 16 Program A  

---

## 1. Entry Point & Workspace Model

### VERIFIED — Existing Entry Point

**Current State (from `resources/views/admin/property-command-center/index.blade.php`):**

```
GET /admin/property-command-center
  → admin.property-command-center.index (273 lines, Alpine.js)
  → Tenant selector + BAI Summary Banner + Property List Table
```

**Workspace Navigation Model:**
- Route `/admin/property-command-center/{propertyId}` → `show()` action
- Route `/admin/property/{property}/command-center/{tab?}` → tabbed navigation
- Valid tabs defined in route constraint: `executions|timeline|listings|summary`

**Property Workspace Relationship:**
- `PropertyWorkspace` model links to `Property` via `property_id`
- States: `STATE_WORKSPACE_CREATED`, `STATE_DRAFT`, `STATE_READY_FOR_REVIEW`, `STATE_PUBLISHED`, `STATE_ARCHIVED`
- User arrives via property list selection → workspace

### PROPOSED — Workspace Entry Pattern

```
Property List (index)
  ↓ user clicks property row
Property Command Center (/property-command-center/{id})
  ↓ user selects tab
Workspace with 9 tabs
```

**Entry Point Responsibilities:**
1. Show BAI Summary Banner (total properties, success rate, recovery queue, failed executions)
2. Display searchable property list with columns: Mülk, Durum, İlanlar, Son Execution, BAI, İşlem
3. Each row links to `/admin/property-command-center/{id}`

---

## 2. Tab Inventory (9 Tabs)

### VERIFIED — Existing Tab Capabilities

Based on `PropertyCommandCenterQueryService` and routes:

| Tab Name | Route Param | API Endpoint | Data Source |
|----------|-------------|--------------|-------------|
| Summary | default | `/api/{id}/summary` | Property + Listing summary + Execution metrics + Tenant BAI |
| Executions | `executions` | `/api/{id}/executions` | WorkforceExecution filtered by aggregate_type=Property |
| Timeline | `timeline` | `/api/{id}/timeline` | ListingStateTransition + WorkforceExecution merged |

### PROPOSED — 9 Tab Architecture

| # | Tab | Icon | Content | Access |
|---|-----|------|---------|--------|
| 1 | **Overview** | grid | Property metadata, BAI score, quick stats | All roles |
| 2 | **Listings** | list | Ilan listesi, yayın durumu, actions | All roles |
| 3 | **Executions** | play-circle | WorkforceExecution history | Admin, Operator |
| 4 | **Timeline** | clock | Unified event stream | Admin, Operator |
| 5 | **Commercial** | currency | CommercialOffering + pricing | Admin |
| 6 | **Reservations** | calendar | PropertyReservation data | Admin, Sales |
| 7 | **Financial** | chart-bar | Revenue, expenses, projections | Admin |
| 8 | **Availability** | calendar-check | PropertyAvailability blocks | Admin, Operator |
| 9 | **Settings** | cog | Workspace config, templates | Admin |

**Evidence Base:**
- `Property` has relations: `listings()`, `commercialOfferings()`
- `CommercialOffering` has: `offering_type`, `fiyat`, `para_birimi`, `komisyon_orani`, `depozito`
- `PropertyReservation` has: `start_date`, `end_date`, `guest_*`, `islem_tutari`, `finansal_durum`
- `PropertyAvailability` and `PropertyAvailabilityBlock` models exist

---

## 3. Primary Actions

### VERIFIED — Existing Recovery Action

From `PropertyCommandCenterController::recover()`:

```php
POST /admin/property-command-center/api/{propertyId}/recover/{uuid}
  → RecoveryEngineService::recover(failedExecutionUuid, actorId, actorType, recoveryReason)
  → Requires: execution_status === FAILED
  → Guard: tenant isolation check
```

### PROPOSED — Primary Actions by Tab

| Priority | Action | Location | Behavior |
|----------|--------|----------|----------|
| P1 | **Property Guncelle** | Overview header | Open edit drawer/modal |
| P1 | **Listing Yayinla** | Listings tab | State transition: Draft → ReadyForReview → Published |
| P1 | **Execution Replay** | Executions row | POST /api/{id}/recover/{uuid} |
| P2 | **Yeni Ilan Olustur** | Listings tab | Create new Ilan linked to this Property |
| P2 | **Fiyat Guncelle** | Commercial tab | Edit CommercialOffering drawer |
| P2 | **Availability Block Ekle** | Availability tab | Calendar modal for blocking dates |
| P3 | **Export Data** | Any tab header | Export current tab data as CSV/JSON |
| P3 | **Workspace Archive** | Settings tab | Archive workspace with confirmation |

---

## 4. Secondary Actions

### PROPOSED — Contextual Actions

| Context | Action | Type |
|---------|--------|------|
| Property row | BAI detay gor | Link to full analytics |
| Listing row | Ilani duzenle | Edit modal |
| Listing row | Ilani gizle/ac | Toggle yayin_durumu |
| Execution row | Detay gor | Expand inline or drawer |
| Execution row | Replay zinciri gor | GET /api/{id}/replay-chain/{uuid} |
| Reservation row | Rezervasyon detay | View drawer |
| Commercial row | Teklif olustur | Create from existing offering |

---

## 5. Tertiary Actions

### PROPOSED — Bulk & Utility Actions

| Action | Location | Description |
|--------|----------|-------------|
| Filter by status | Executions, Listings | Dropdown filter (yayinda, taslak, pasif) |
| Sort columns | All tables | Click column header to sort |
| Search | All tabs | Cmd+K command palette |
| Refresh data | All tabs | Manual refresh button |
| Help/docs | Tab header | Tooltip with tab documentation |
| Keyboard shortcuts | Global | Ctrl+/ to show shortcuts |

---

## 6. Role-Based Visibility

### VERIFIED — User Model Relationship

Based on `PropertyCommandCenterController`:
- `actor_type` in WorkforceExecution: `User`, `Hermes`, `Agent`, `System`
- Tenant isolation via `resolveTenantId()`

### PROPOSED — Role Matrix

| Tab | Admin | Operator | Sales | Viewer |
|-----|-------|----------|-------|--------|
| Overview | Full | Full | Read | Read |
| Listings | Full | Full | Read+Create | None |
| Executions | Full | Full | None | None |
| Timeline | Full | Full | None | None |
| Commercial | Full | None | Read | None |
| Reservations | Full | Read | Full | None |
| Financial | Full | None | Read | None |
| Availability | Full | Full | None | None |
| Settings | Full | None | None | None |

**Implementation Note:** Use Laravel Gate/Policy for role checks. Tab visibility via `x-show` Alpine directive conditional on role.

---

## 7. Command Bar & Global Search

### VERIFIED — Existing Keyboard Shortcuts

From `resources/views/admin/property-hub/partials/keyboard-shortcuts.blade.php`:

| Shortcut | Action |
|----------|--------|
| Ctrl/Cmd + K | Quick search (Command Palette) |
| Ctrl/Cmd + N | New feature |
| Ctrl/Cmd + S | Save |
| Ctrl/Cmd + / | Show shortcuts help |
| G + H | Navigate home |
| G + F | Navigate features |
| G + T | Navigate templates |
| Esc | Close modals |

### PROPOSED — Command Bar for Property Command Center

| Shortcut | Action | Scope |
|----------|--------|-------|
| Ctrl/Cmd + K | Command palette | Global |
| Ctrl/Cmd + P | Go to property | Global |
| Ctrl/Cmd + 1-9 | Switch tabs | Property CC |
| Ctrl/Cmd + N | New listing | Listings tab |
| Ctrl/Cmd + R | Refresh | Any tab |
| Ctrl/Cmd + E | Export current | Any tab |

**Command Palette Actions:**
- Search properties by name/ID
- Jump to specific tab
- Quick action: "New Listing", "View BAI", "Go to Timeline"
- Recent properties (last 5 visited)

---

## 8. Drawer / Modal Patterns

### VERIFIED — Existing Modal Pattern

From `property-hub/index.blade.php`:
- Apply Pack Modal (x-show, Alpine transitions)
- Import Modal (form with file upload)
- Transitions: `ease-out duration-200` → `ease-in duration-150`

### PROPOSED — Drawer/Modal Inventory

| Pattern | Use Case | Size | Animation |
|---------|----------|------|-----------|
| **Side Drawer** | Property edit, Listing edit | 480px width | Slide from right |
| **Modal (sm)** | Confirm dialogs, small forms | max-w-md | Fade + scale |
| **Modal (lg)** | Replay chain view, detailed logs | max-w-4xl | Fade + scale |
| **Full-screen overlay** | Onboarding wizard | Full viewport | Fade in |
| **Inline expand** | Execution detail, Listing row | N/A | Height expand |

**Drawer Content:**
- PropertyEditDrawer: Basic info, location, specs, images
- ListingEditDrawer: Title, description, features, pricing
- AvailabilityBlockDrawer: Calendar picker, reason, duration
- CommercialOfferDrawer: Price, currency, commission, terms

**Modal Content:**
- ReplayChainModal: Full execution chain visualization
- ExecutionDetailModal: UUID, status, duration, error, metadata
- ConfirmArchiveModal: Warning + confirmation
- NewListingModal: Quick create form

---

## 9. Information Density Decisions

### VERIFIED — Current Density (index.blade.php)

```
Overview: 4-column BAI grid + table
Table columns: Mülk | Durum | İlanlar | Son Execution | BAI | İşlem
```

### PROPOSED — Density Rules by Context

| Context | Density | Rationale |
|---------|---------|-----------|
| Overview header | High | Quick stats, immediate value |
| Listings table | High | Scan-friendly, status badges |
| Executions table | Medium | Detail needed, expandable rows |
| Timeline | Medium | Chronological, grouped by date |
| Commercial | Low | Complex data, forms/drawers |
| Financial | Low | Charts + tables, filtering |
| Settings | Low | Forms, sparse layout |

**Density Controls:**
- Collapsible sections per tab
- "Show more" for lists > 10 items
- Sticky headers on scroll
- Responsive: stack columns on mobile

---

## 10. Verification: Repository Evidence

### VERIFIED — Code Evidence

| Claim | Evidence | Source |
|-------|----------|--------|
| Property list endpoint | `apiPropertiesList()` returns properties with summary | `PropertyCommandCenterController:71-82` |
| Summary endpoint | `apiSummary()` returns property + listing + execution + BAI | `PropertyCommandCenterQueryService:102-153` |
| Timeline endpoint | Merges ListingStateTransition + WorkforceExecution | `PropertyCommandCenterQueryService:200-255` |
| Recovery action | POST to `/recover/{uuid}`, guards tenant + status | `PropertyCommandCenterController:149-193` |
| Replay chain | GET `/replay-chain/{uuid}` chains by `replay_of_uuid` | `PropertyCommandCenterQueryService:267-285` |
| PropertyWorkspace states | `STATE_DRAFT`, `STATE_READY_FOR_REVIEW`, `STATE_PUBLISHED` | `PropertyWorkspace:87` |
| CommercialOffering | Links Property → pricing with `offering_type`, `fiyat`, `komisyon_orani` | `CommercialOffering:30-38` |
| PropertyReservation | Full booking data with `finansal_durum`, `depozito` | `PropertyReservation:16-44` |
| WorkforceExecution | Statuses: REQUESTED, RUNNING, COMPLETED, FAILED, CANCELLED | `WorkforceExecution:67-79` |
| Keyboard shortcuts | Cmd+K, Cmd+N, Cmd+S, Cmd+/, G+key patterns | `keyboard-shortcuts.blade.php:265-318` |
| Command palette | Search modal with results list | `keyboard-shortcuts.blade.php:134-238` |

### PROPOSED — Future Verification Checklist

- [ ] PropertyCommandCenterController show() renders tabbed view (currently just returns view)
- [ ] 9-tab Blade component exists
- [ ] CommercialOffering CRUD endpoints exist
- [ ] PropertyReservation endpoints exist
- [ ] Role-based tab visibility implemented
- [ ] Command palette includes Property CC actions
- [ ] ReplayChainModal component exists

---

## Appendix A: Route Map

```
GET  /admin/property-command-center
     → index() — List all properties with BAI summary

GET  /admin/property-command-center/{propertyId}
     → show() — Property workspace (9 tabs)

GET  /admin/property-command-center/api/properties-list
     → apiPropertiesList() — JSON list

GET  /admin/property-command-center/api/{propertyId}/summary
     → apiSummary() — Property + Listing + Execution + BAI summary

GET  /admin/property-command-center/api/{propertyId}/executions
     → apiExecutions() — Execution history

GET  /admin/property-command-center/api/{propertyId}/timeline
     → apiTimeline() — Unified event stream

GET  /admin/property-command-center/api/{propertyId}/replay-chain/{uuid}
     → apiReplayChain() — Replay visualization data

POST /admin/property-command-center/api/{propertyId}/recover/{uuid}
     → recover() — Manual recovery action

# Short-form routes (for tab navigation)
GET  /admin/property/{property}/command-center
     → show() — Default tab

GET  /admin/property/{property}/command-center/{tab}
     → show() — Named tab (executions|timeline|listings|summary)
```

---

## Appendix B: Model Relationships

```
Property (1)
  ├── listings() → Ilan (N) — marketing channels
  ├── commercialOfferings() → CommercialOffering (N) — pricing terms
  │     └── reservations() → PropertyReservation (N) — bookings
  ├── PropertyWorkspace (1) — workspace state machine
  └── WorkforceExecution (N) — runtime history
        ├── ListingStateTransition (N) — domain events
        └── PropertyAvailability (N) — calendar blocks
```

---

## Appendix C: Design Tokens (Reference Only)

> These belong to YDS Step 3. Listed here for reference during implementation.

| Token | Value | Usage |
|-------|-------|-------|
| `--navy` | `#0A1628` | Primary backgrounds, headers |
| `--gold` | `#C9A84C` | Accents, highlights, BAI badges |
| `--cream` | `#F8F6F1` | Surface backgrounds |
| `--cream-text` | `#F5F0E8` | Text on dark backgrounds |

---

*Document Status: READY FOR IMPLEMENTATION*  
*Next Step: Create Blade component for 9-tab layout in Sprint 17*

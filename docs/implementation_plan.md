# S6.1-E07: Capability-based Workspace Runtime & Metrics Integration — Technical Design

## 1. Sprint Objective
Integrate real-time capability health scoring, template-based dynamic field editing, and the Business Automation Index (BAI) into the Workspace Dashboard Cockpit. Provide advisors with an E2E visible automation pipeline showing why their listing is or isn't ready for publishing, and highlight how much of the work has been automated.

---

## 2. Value Proposition

### Business Value
* **Efficiency Visualization:** Proves the ROI of AI automation to the business by calculating how many tasks were performed autonomously vs manually.
* **Reduced Time-to-Publish:** Identifies bottlenecks (missing documents, failed AI jobs, missing fields) immediately, reducing listing prep time.

### Product Value
* **Clear Advisor Guidance:** Advisors see exactly what is missing to move a listing to `ready_for_publish`.
* **Dynamic Interaction:** Allows inline editing of missing fields directly on the digital twin cockpit dashboard.

---

## 3. Architecture & Domain Model Design

### Capability-based Workspace Runtime Model
The system defines 6 core capabilities, each evaluated dynamically during cockpit rendering:
1. **Workspace Capability:** Measures drive folder connectivity and event-sourcing integrity.
2. **Template Capability:** Evaluates field schema validation completeness.
3. **Publishing Capability:** Checks external channel credentials and sync readiness.
4. **CRM Capability:** Checks if contact relationships (`ilan_sahibi`, `ilgili_kisi`) are mapped.
5. **Reservation Capability:** Validates calendar synchronization status and verifies zero double-booking locks.
6. **AI Capability:** Monitors AI worker execution completion rates.

#### Capability Health States
Each capability returns a health DTO containing:
* `score`: `0–100`
* `status`: `healthy` (>=90) | `warning` (50-89) | `critical` (<50)
* `issues`: Array of human-readable issues.

```
+-------------------------------------------------------------------------------+
|                        Workspace Cockpit Top Bar                              |
|  [ Workspace Health: 85% ]   [ Listing Readiness: 65% ]   [ Automation: 82% ] |
+-------------------------------------------------------------------------------+
```

### Business Automation Index (BAI) Telemetry
Calculates the automation ratio over the last 30 days:
$$BAI = \frac{E_{auto}}{E_{auto} + E_{manual}} \times 100$$
Where:
* $E_{auto}$ is the sum of system-triggered domain events (null `user_id` in `etki_alani_olaylari`) and system-triggered updates (null `causer_id` in `activity_log`).
* $E_{manual}$ is the sum of manual agent events (non-null `user_id` in `etki_alani_olaylari`) and manual form saves (non-null `causer_id` in `activity_log` for `Ilan` and related models).

To enforce **Tenant Isolation (Rule 1)**, all telemetry queries are strictly filtered by the active tenant ID:
* `etki_alani_olaylari` → `where('tenant_id', $tenantId)`
* `activity_log` → `where('subject_type', 'App\Models\Ilan')->whereIn('subject_id', fn($q) => $q->select('id')->from('ilanlar')->where('tenant_id', $tenantId))`

---

## 4. Proposed Files and Classes

### Services & Telemetry

#### `[NEW] App\Services\Workspace\AutomationTelemetryService`
* **Purpose:** Computes the Business Automation Index (BAI) and telemetry data under tenant scope.
* **Methods:**
  * `calculateBusinessAutomationIndex(?int $tenantId): int`

#### `[MODIFY] App\Services\Workspace\WorkspaceSummaryService`
* Inject `AutomationTelemetryService`.
* Add `telemetry` key to the returned payload containing the calculated BAI score.

### Controller Integration

#### `[MODIFY] App\Http/Controllers/Admin/WorkspaceDashboardController`
* In `show(int $id)`:
  * Retrieve the resolved template for the workspace intent.
  * Construct the `$workspaceData` canonical values from the `Ilan` model.
  * Pass `templateFields` and `workspaceData` to the view.

### View & UI Component

#### `[MODIFY] resources/views/admin/workspace/cockpit.blade.php`
* **Metrics Banner Update:** Displays `Workspace Health`, `Listing Readiness`, and `Automation Index` side-by-side using three SVG-based circular progress bars.
* **Dynamic Edit Panel:** Appends a full-width **Yayın Hazırlık Formu (Dinamik Alanlar)** panel. Instantiates `<x-workspace.dynamic-fields>` with resolved template fields, current workspace values, and missing fields highlights.

---

## 5. E2E Advisor Scenario Automation Flow

1. **Portfolio Creation:** Advisor uploads a new property. System initializes `PropertyWorkspace` in state `draft`.
2. **Drive Hook Sync:** File upload to Google Drive triggers `DriveWebhookJob`, which processes files and populates subfolders ($E_{auto}$).
3. **AI Workforce Trigger:** Queue executes photo analysis and description generation ($E_{auto}$).
4. **Metrics Evaluation:** Dashboard cockpit updates the Readiness Score. Missing fields are highlighted in red.
5. **Advisor Action:** Advisor edits missing fields directly on the dashboard. Submitting the form updates `Ilan` ($E_{manual}$) and triggers state re-evaluation.
6. **State Transition:** If score reaches threshold, aggregate transitions to `ready_for_review`.

---

## 6. Test & Quality Gates Plan

### Automated Tests
* **`[NEW] Tests\Unit\Services\Workspace\AutomationTelemetryServiceTest`**
  * Assert index is 100% when no logs exist.
  * Assert correct mathematical ratio calculation with mixed events.
  * Assert strict tenant isolation (no cross-tenant leakage).
* **`[MODIFY] Tests\Unit\Services\Workspace\WorkspaceSummaryServiceTest`**
  * Assert summary payload contains `telemetry` block.

### Quality Gates
* Run Pint linter: `composer lint`
* Scan code compliance: `php artisan sab:integrity-scan`
* Validate quality gates: `./scripts/tools/antigravity-full-gate.sh --quick`

---

## 7. Risks & Mitigation
* **Performance Overhead:** Running count queries over 30-day logs during page load.
  * *Mitigation:* Ensure indexes exist on `created_at` and `tenant_id`/`causer_id`. Cache telemetry results for 5 minutes using tenant-keyed cache tags.
* **Spatie Activity Log Absence:** `activity_log` table missing in clean test databases.
  * *Mitigation:* Wrap DB queries in `Schema::hasTable('activity_log')` checks to prevent crashes.

---

## 8. DoD & Certification Checklist
- [ ] `AutomationTelemetryService` implemented with tenant isolation.
- [ ] Telemetry integrated into `WorkspaceSummaryService` output.
- [ ] Dashboard cockpit renders three metrics circles (Health, Readiness, BAI).
- [ ] Dynamic form component `<x-workspace.dynamic-fields>` integrated and working with POST saves.
- [ ] All automated tests pass successfully.
- [ ] `sab:integrity-scan` passes with zero new violations.
- [ ] `antigravity-full-gate` passes.

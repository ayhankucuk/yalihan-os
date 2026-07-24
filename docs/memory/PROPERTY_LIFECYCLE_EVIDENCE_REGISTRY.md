# Yalıhan OS — Property Lifecycle Evidence Registry
**Sponsor:** Strategic Architecture & Automation Board (SAAB)  
**Date:** 2026-07-24  
**Status:** PROPOSED (Awaiting SAAB Review)  

---

## 1. Verified Core Claims Registry

This directory contains verified codebase proofs for all automation claims made in the Property Lifecycle Baseline.

### Claim 1: Workspace Drive Folder Scaffolding
*   **Exact File Path:** [DriveWorkspaceService.php](../../app/Services/Drive/DriveWorkspaceService.php)
*   **Class & Method:** `App\Services\Drive\DriveWorkspaceService@scaffoldFolders`
*   **Production Entry Point:** Triggered when binding a workspace folder via `App\Http\Controllers\Admin\WorkspaceController@bindDrive`.
*   **Persistence Result:** Google Drive API requests create a structural directory skeleton.
*   **Test Evidence:** Verified in `tests/Feature/AI/TenantIsolationTest.php` mocks.
*   **Classification Rationale:** `PRODUCTION` (Fully functional directory scaffolding is called in active workflows).

### Claim 2: AI Description Generation
*   **Exact File Path:** [DescriptionDraftService.php](../../app/Services/AI/Description/DescriptionDraftService.php)
*   **Class & Method:** `App\Services\AI\Description\DescriptionDraftService@draft`
*   **Production Entry Point:** Invoked in the Listing administration cockpit via `App\Http\Controllers\Admin\ListingController@generateDescription`.
*   **Persistence Result:** Writes generated description text directly to `ilanlar` model fields.
*   **Test Evidence:** `tests/Feature/AI/DescriptionGenerationTest.php`
*   **Classification Rationale:** `PARTIAL` (It is tested and functional, but requires human click-triggering in the Admin UI; it does not execute autonomously in the background).

### Claim 3: Double-Entry Financial Ledger
*   **Exact File Path:** [FinancialLedgerService.php](../../app/Services/FinancialLedgerService.php)
*   **Class & Method:** `App\Services\FinancialLedgerService@recordDoubleEntry`
*   **Production Entry Point:** Triggered during SaaS billing runs or payouts via `App\Services\Finance\TransactionService@recordTransaction`.
*   **Persistence Result:** Creates matching debit and credit entries in the `ledger_entries` table.
*   **Test Evidence:** `tests/Feature/Rental/EnterpriseMoneyTest.php`
*   **Classification Rationale:** 
    *   *Ledger Posting:* `PRODUCTION` (Double-entry calculation and posting are automated).
    *   *Payout Reconciliation:* `MANUAL` (Matching bank payouts to bookings is manual).
    *   *Owner Settlement:* `MANUAL` (Payments execution is manual).

### Claim 4: Channel Calendar Sync Service
*   **Exact File Path:** [CalendarSyncService.php](../../app/Services/CalendarSyncService.php)
*   **Class & Method:** `App\Services\CalendarSyncService@syncCalendar`
*   **Production Entry Point:** Triggered by `App\Http\Controllers\Admin\CalendarSyncController@manualSync` and scheduled CLI runs in `App\Console\Commands\CalendarSyncCommand@handle`.
*   **Persistence Result:** Updates `ilan_takvim_syncs` table.
*   **Test Evidence:** Mock assertions in `tests/Feature/Admin/PropertyHubAIAuthorityBridgeTest.php`.
*   **Classification Rationale:**
    *   *Execution Call Path:* `ACTIVE`
    *   *Internal State Persistence:* `PARTIAL`
    *   *External Channel Integration:* `MOCK` (Methods return hardcoded true stubs).
    *   *Business Outcome:* `NOT AUTOMATED`

### Claim 5: YazlikRezervasyon Write Paths
*   **Exact File Path:** [YazlikKiralamaService.php](../../app/Services/YazlikKiralamaService.php)
*   **Class & Method:** `App\Services\YazlikKiralamaService@createReservation`
*   **Production Entry Point:** `App\Http\Controllers\Admin\YazlikKiralamaController@store`
*   **Persistence Result:** Eloquent database insert into `yazlik_rezervasyonlar`.
*   **Test Evidence:** Checked in `tests/Feature/ListingLifecycle/ListingLifecycleFinalSealTest.php`.
*   **Classification Rationale:** `MANUAL` (Saves booking parameters directly. It has **no domain events, observers, or listeners** to trigger operations or financial projections).

### Claim 6: Task Observer Notification Triggering
*   **Exact File Path:** [GorevObserver.php](../../app/Observers/GorevObserver.php)
*   **Class & Method:** `App\Observers\GorevObserver@created` / `updated`
*   **Production Entry Point:** Eloquent model saves via `App\Modules\TakimYonetimi\Controllers\Admin\GorevController@store`.
*   **Persistence Result:** Dispatches `GorevCreated` and `GorevDurumChanged` event notifications.
*   **Test Evidence:** `tests/Feature/TaskAuthorityTest.php`
*   **Classification Rationale:** `PARTIAL` (Observers only dispatch notification events. No automated business engine exists to create tasks based on reservation checkouts).

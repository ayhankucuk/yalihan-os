# Yalıhan OS — Security Hardening Verification (R11-R15)

**Role:** Research Office (Antigravity)  
**Classification:** Confidential / Security Audit & Hardening Verification  
**Status:** Completed  
**Date:** 2026-07-07  

---

## 📊 Risk Status Summary

| ID | Risk Name | Class | Status | Target File / Line Reference | Severity |
|----|-----------|-------|--------|------------------------------|----------|
| **R11** | Google Drive Webhook Authentication Bypass | **CONFIRMED P0** | **ACTIVE BLOCKER** | [`app/Services/Drive/DriveWebhookService.php:267-272`](file:///Users/macbookpro/dev/yalihan2026/app/Services/Drive/DriveWebhookService.php#L265-L272) | 🔴 Critical |
| **R12** | Drive Webhook Event Log `tenant_id` NULL | **CONFIRMED P0** | **ACTIVE BLOCKER** | [`app/Services/Drive/DriveWebhookService.php:393-401`](file:///Users/macbookpro/dev/yalihan2026/app/Services/Drive/DriveWebhookService.php#L393-L401) | 🔴 Critical |
| **R14** | `RestoreTenantContext` Not Used by Queue Jobs | **CONFIRMED P0** | **ACTIVE BLOCKER** | [`app/Queue/Middleware/RestoreTenantContext.php`](file:///Users/macbookpro/dev/yalihan2026/app/Queue/Middleware/RestoreTenantContext.php) & [`app/Jobs/`](file:///Users/macbookpro/dev/yalihan2026/app/Jobs) | 🔴 Critical |
| **R13** | TKGM Single-Threaded Deadlock & 404 Route | **P1** | **ACTIVE DEBT** | [`app/Services/Integrations/TKGMService.php:559-566`](file:///Users/macbookpro/dev/yalihan2026/app/Services/Integrations/TKGMService.php#L559-L566) | 🟠 High |
| **R15** | `OutboxService` Exists but is Unused | **P2** | **ACTIVE DEBT** | [`app/Services/Reliability/OutboxService.php:8-44`](file:///Users/macbookpro/dev/yalihan2026/app/Services/Reliability/OutboxService.php#L8-L44) | 🟢 Low |

---

## 🚫 Confirmed Blockers (P0)

### 🔴 R11 — Google Drive Webhook Authentication Bypass
* **Finding:** Omiting the `X-Goog-Channel-Token` header skips token matching, permitting unauthorized POST requests to pass as valid.
* **Evidence:** 
  ```php
  // app/Services/Drive/DriveWebhookService.php:267
  if ($channelToken && $channelToken !== $expectedToken) { ... }
  ```
  If `$channelToken` is null, the condition evaluates to false, skipping token verification.
* **Reproduction Steps:**
  1. Retrieve a valid `channel_id` representing any workspace.
  2. Send an HTTP POST to `/api/v1/geo/geocode` or any webhook processor without passing the `X-Goog-Channel-Token` header.
  3. The request succeeds and triggers business logic.
* **Recommended Fix:** Change the verification check to reject requests where the token is missing or incorrect.
* **Suggested Tests:**
  `tests/Feature/Drive/DriveWebhookSecurityTest.php` asserting that webhook hits with missing tokens fail with a 401 response code.

---

### 🔴 R12 — Drive Webhook Event Log `tenant_id` NULL
* **Finding:** The payload array built inside `emitDriveEvent()` does not include the `'tenant_id'` key, which causes `writeHermesEvent()` to write `null` to the `hermes_event_logs` table.
* **Evidence:**
  ```php
  // Payload definition in emitDriveEvent() L393
  $payload = [
      'workspace_id'   => $workspace->id,
      'ilan_id'       => $workspace->ilan_id,
      ...
  ];
  ```
  `writeHermesEvent()` then executes:
  `$log->tenant_id = $payload['tenant_id'] ?? null;`
* **Reproduction Steps:**
  1. Trigger a drive file change webhook.
  2. Query `hermes_event_logs` table.
  3. Observe that the event log record is saved with `tenant_id = NULL`.
* **Recommended Fix:** Pass `'tenant_id' => $workspace->tenant_id` into the payload inside `emitDriveEvent()`.
* **Suggested Tests:**
  Assert that after executing the webhook, the created `HermesEventLog` record contains the workspace's `tenant_id`.

---

### 🔴 R14 — `RestoreTenantContext` Not Used by Queue Jobs
* **Finding:** The queue hardening infrastructure (`RestoreTenantContext` middleware and `TenantAwareJobInterface`) is completely implemented but zero jobs under `app/Jobs/` implement it. This allows jobs to run without tenant scoping, leading to cross-tenant data exposure on tenant-scoped queries.
* **Evidence:**
  `grep -r "RestoreTenantContext" app/Jobs/` returns zero results.
* **Reproduction Steps:**
  1. Dispatch `DailySnapshotsJob` or `OwnerReportExportJob`.
  2. During execution, the active tenant context remains `null`.
  3. Tenant-scoped models (e.g., `Ilan`, `Kisi`, `Talep`) bypass global tenant filters and retrieve records from all tenants.
* **Recommended Fix:** Implement `TenantAwareJobInterface` and return `[new RestoreTenantContext(app(TenantContextService::class))]` in the `middleware()` method of all tenant-scoped jobs.
* **Suggested Tests:**
  Mock a queue worker run and assert that context bleeding does not occur between consecutive jobs.

---

## 🔍 Active Debt & Risks (P1 / P2)

### 🟠 R13 — TKGM Single-Threaded Deadlock & 404 Route (P1)
* **Finding:** `TKGMService` makes a synchronous HTTP POST call to `config('app.url') . '/api/geo/geocode'`. This has two severe bugs:
  1. **Deadlock:** On single-threaded servers (e.g., local `php artisan serve`), this blocks the handler loop indefinitely.
  2. **404 Route Error:** The endpoint `/api/geo/geocode` does not exist. The correct route is `/api/v1/geo/geocode`.
* **Evidence:**
  ```php
  // app/Services/Integrations/TKGMService.php:559
  $geocodeUrl = config('app.url') . '/api/geo/geocode';
  ```
* **Reproduction Steps:**
  Run the address geocoding resolver locally under `php artisan serve`. The server blocks and times out after 5 seconds.
* **Recommended Fix:** Call the underlying geocoding logic (e.g., Nominatim search) directly in-process or via dependency injection instead of loopback HTTP calls.

---

### 🟢 R15 — `OutboxService` Exists but is Unused (P2)
* **Finding:** `OutboxService` is fully written but orphan. No part of the application writes events to the transactional outbox table.
* **Evidence:**
  `grep_search` shows `OutboxService` is never referenced in `app/`.
* **Recommended Fix:** Wire up domain events to publish via `OutboxService::publish`, or delete if deprecated.

---

## ❌ False Positives
* None of the R11-R15 issues are false positives. They are all verified and confirmed as real security/integration gaps in the current codebase.

---

## 💻 VS Code Implementation Prompt for Confirmed P0 Fixes

```markdown
Please implement the following security fixes for Yalıhan OS:

1. **Fix Google Drive Webhook Authentication (R11):**
   In `app/Services/Drive/DriveWebhookService.php` within `validateNotification()`, ensure the token is strictly validated:
   ```php
   if (empty($channelToken) || $channelToken !== $expectedToken) {
       Log::warning('[DriveWebhookService] Channel token missing or mismatch', ...);
       return ['valid' => false, 'workspace_id' => null, 'error' => 'Unauthorized'];
   }
   ```
   Also restrict the missing channel ID fallback to local environment only.

2. **Propagate tenant_id to Hermes Event Log (R12):**
   In `app/Services/Drive/DriveWebhookService.php` within `emitDriveEvent()`, add `'tenant_id' => $workspace->tenant_id` to the `$payload` array before calling `writeHermesEvent()`.

3. **Hardening Multi-Tenant Queue Isolation (R14):**
   Implement `App\Queue\Contracts\TenantAwareJobInterface` on the following jobs:
   - `App\Jobs\AI\DailySnapshotsJob`
   - `App\Jobs\OwnerReport\OwnerReportExportJob`
   - `App\Jobs\NotifyN8nAboutIlanPriceChange`
   - `App\Jobs\TalepTopluAnalizJob`
   - `App\Jobs\TKGMAutoFillJob`
   - `App\Jobs\GenerateListingReportJob`
   - `App\Jobs\UpdateListingVisibilityScore`
   - `App\Jobs\ReverseMatchJob`
   - `App\Jobs\SendNotificationJob`
   - `App\Jobs\HandleUrgentMatch`
   
   Define a `middleware()` method in each returning `[new \App\Queue\Middleware\RestoreTenantContext(app(\App\Services\SaaS\TenantContextService::class))]`.
```

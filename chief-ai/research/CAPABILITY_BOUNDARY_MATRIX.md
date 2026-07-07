# Yalıhan OS — Capability Boundary Audit & Matrix (Sprint 6.1)

**Role:** Chief Research Office (Antigravity)  
**Classification:** Bounded Context / Domain Architecture Audit  
**Status:** Completed  
**Date:** 2026-07-07  

---

## 🏛️ 1. Executive Summary
This audit validates the alignment of all system services and models against the Bounded Context and Bounded Capability model mandated by SAAB v8.1. 
Our primary objective is to detect cross-domain dependencies, duplicated business logic, and capability leaks (where services cross their domain boundaries).

---

## 📐 2. Bounded Capability Ownership Matrix

The table below maps the system capabilities, their canonical namespaces, and their designated boundaries:

| Capability | Bounded Domain / Namespace | SSOT Models | Primary Responsibility |
|------------|----------------------------|-------------|------------------------|
| **Location** | `App\Domain\Location`, `App\Modules\Emlak` | `Il`, `Ilce`, `Mahalle` | Geocoding, coordinates, POI calculations, OpenStreetMap proxy. |
| **Media** | `App\Services\Drive` | `PortfolioDriveWorkspace` | File storage, Google Drive sync, webhook parsing, photo uploads. |
| **Pricing** | `App\Domain\Ilan` | `Ilan` (price fields) | Price history, dynamic pricing templates. |
| **Publishing** | `App\Domain\Ilan\Services` | `Ilan` (yayin_durumu) | Portal publishing, readiness rules enforcement. |
| **Reservation**| `App\Domain\PropertyHub` | `PropertyReservation`, `PropertyAvailability` | Short-term rental booking, pricing calendar, availability blockages. |
| **Finance** | `App\Services\Finance`, `App\Modules\Finans` | `LedgerAccount`, `LedgerEntry`, `Bonus` | Financial ledger, double-entry bookkeeping, commission/bonus splits. |
| **CRM** | `App\Domain\CRM`, `App\Modules\Crm` | `Kisi`, `Talep`, `Gorev` | Lead tracking, adviser assignment, client matching. |
| **AI** | `App\Domain\AI`, `App\Domain\Hermes` | `AiLog`, `Telemetry` | Cortex AI Orchestration, prompt templating, metadata parsing. |
| **Timeline** | `App\Domain\PropertyHub\Observability` | `PropertyConfigVersion` | Config history, version lineage, drift detection. |
| **Health** | `App\Services\Governance` | `GovernanceEvent` | Bekci health status, architecture guards. |

---

## 🚨 3. Bounded Context & Capability Violations

### Violation 1: TKGM Service Bypass to API Layer (Location Context Leak)
* **File:** [`app/Services/Integrations/TKGMService.php:559-566`](file:///Users/macbookpro/dev/yalihan2026/app/Services/Integrations/TKGMService.php#L559-L566)
* **Description:** The `TKGMService` (Integration capability) calls `config('app.url') . '/api/geo/geocode'` via loopback HTTP POST. This leaks controller/route dependencies into an integration class and bypasses the `GeoProxyController`'s internal caching.
* **Architecture Risk:** High. Single-threaded deadlock risk locally; performance degradation in production due to nested loopback network requests.

### Violation 2: Drive Webhook Direct Hermes Event Log Writing (Media-AI Coupling)
* **File:** [`app/Services/Drive/DriveWebhookService.php:463-470`](file:///Users/macbookpro/dev/yalihan2026/app/Services/Drive/DriveWebhookService.php#L463-L470)
* **Description:** `DriveWebhookService` (Media capability) directly instantiates and writes to `\App\Models\Hermes\HermesEventLog`. This tightly couples file storage notifications with the Hermes AI Workforce domain.
* **Architecture Risk:** Medium. Changes in the Hermes logging model will break Drive webhook sync. Webhooks should publish neutral events via a generic event bus or outbox, which Hermes then subscribes to.

### Violation 3: Finance Bonus Calculator Domain String Coupling (Finance-Listing Coupling)
* **File:** [`app/Services/Finance/BonusCalculator.php`](file:///Users/macbookpro/dev/yalihan2026/app/Services/Finance/BonusCalculator.php)
* **Description:** Checking string literals like `'Satıldı'` to trigger financial calculations.
* **Architecture Risk:** Medium. Changes in listing state workflows directly impact financial bonus calculations. Statuses should be checked via Enum classes (`IlanDurumu`) rather than raw strings.

### Violation 4: CRM Weight Optimizer Bypassing CRM Repository (CRM Context Leak)
* **File:** [`app/Services/Matching/MatchingWeightsOptimizer.php`](file:///Users/macbookpro/dev/yalihan2026/app/Services/Matching/MatchingWeightsOptimizer.php)
* **Description:** Accessing and writing queries on `Talep` directly inside internal matching loops instead of delegating database access to a dedicated CRM Repository or Service.
* **Architecture Risk:** Low. Creates database tight-coupling and prevents caching layer optimization inside the CRM domain.

---

## 🛠️ 4. Refactoring Suggestions for VS Code AI

1. **In-Process Geocoding Resolution:**
   Refactor `TKGMService` to inject `GeoProxyController` or a shared `GeocodingService` directly and call the query in-process:
   ```php
   // Replace HTTP call with in-process call
   $geocoder = app(App\Http\Controllers\Api\V1\GeoProxyController::class);
   $result = $geocoder->fetchGeocode($query);
   ```

2. **Decouple Webhook from Hermes:**
   Configure `DriveWebhookService` to fire a Laravel event `App\Events\DriveFileChanged` and let `Hermes` handle it asynchronously via an Event Listener.

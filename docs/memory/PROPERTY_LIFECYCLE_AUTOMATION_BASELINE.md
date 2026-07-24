# Yalıhan OS — Property Lifecycle Automation Baseline
**Sponsor:** Strategic Architecture & Automation Board (SAAB)  
**Date:** 2026-07-24  
**Status:** PROPOSED (Awaiting SAAB Review)  

---

## 1. Executive Summary

This baseline document audits the 12 lifecycle stages of a Property in Yalıhan OS, tracing it from initial acquisition to payout reconciliation. It categorizes the current automation status of each stage using codebase evidence, and identifies where human intervention remains the primary driver.

---

## 2. 12 Lifecycle Stages Audit

### 1. Acquisition and Legal Mandate
*   **Classification:** `MANUAL`
*   **Description:** Onboarding the property owner and executing legal listing authority mandates.
*   **Entry Point:** None. Process occurs offline or via email.
*   **Automation Executed:** None.
*   **Human Steps:** Draft contract, obtain signature, scan document, and upload manually.
*   **Evidence Paths:** `app/Models/Kisi.php` (for owner records) | `app/Models/Belge.php` (generic document uploads).
*   **Metrics:** Frequency: Low (1-2 times/month per property) | Manual Minutes: 60 mins | Failure Risk: Low | Revenue Impact: High.

### 2. Property Onboarding
*   **Classification:** `PARTIAL`
*   **Description:** Registering physical coordinates, tapuada/parsel parameters, and specification structures.
*   **Entry Point:** `POST /admin/properties`
*   **Call Path:** `PropertyController@store` ➔ `PropertyCrudService@create` ➔ `EloquentPropertyRepository@save`
*   **Automation Executed:** Workspace verification and UUID generation.
*   **Human Steps:** Manually fill in the property registration wizard.
*   **Evidence Paths:** [PropertyCrudService.php](../../app/Services/Property/PropertyCrudService.php) | [EloquentPropertyRepository.php](../../app/Repositories/EloquentPropertyRepository.php)
*   **Metrics:** Frequency: Low | Manual Minutes: 15 mins | Failure Risk: Medium (typo risks) | Revenue Impact: Medium.

### 3. Drive Provisioning
*   **Classification:** `PRODUCTION`
*   **Description:** Scaffolding the workspace Google Drive directory structure.
*   **Entry Point:** Event trigger `WorkspaceBound` or manual cockpit trigger.
*   **Call Path:** `WorkspaceController@bindDrive` ➔ `DriveWorkspaceService@scaffoldFolders`
*   **Automation Executed:** Triggers Drive API requests to create folder hierarchies (`01_Tapu`, `02_Fotograflar`, `06_Airbnb` etc.).
*   **Human Steps:** None. Click "Bind Drive" button in cockpit.
*   **Evidence Paths:** [DriveWorkspaceService.php](../../app/Services/Drive/DriveWorkspaceService.php)
*   **Metrics:** Frequency: Low | Manual Minutes: 0 mins | Failure Risk: Low | Revenue Impact: Low.

### 4. Media Intelligence
*   **Classification:** `TARGET DESIGN`
*   **Description:** Automatic photo sorting, duplicates filtering, and room classification.
*   **Entry Point:** None.
*   **Automation Executed:** None.
*   **Human Steps:** Sort photos in local directory, upload to Drive or upload manually in listing wizard.
*   **Evidence Paths:** None.
*   **Metrics:** Frequency: High | Manual Minutes: 45 mins | Failure Risk: High | Revenue Impact: High.

### 5. Content Generation (Description and Slogans)
*   **Classification:** `PRODUCTION` (AI-Assisted)
*   **Description:** AI generation of listing descriptions and slogans from property attributes.
*   **Entry Point:** `POST /admin/listings/{id}/generate-description`
*   **Call Path:** `ListingController@generateDescription` ➔ `DescriptionDraftService@draft` ➔ `YalihanCortex@generateDescription` ➔ `AIOrchestrator@orchestrateAI`
*   **Automation Executed:** Sends mapped property features to LLM provider (DeepSeek/OpenAI), validates JSON shape, and returns draft.
*   **Human Steps:** Click "Generate AI Description" button, review generated draft, and approve.
*   **Evidence Paths:** [DescriptionDraftService.php](../../app/Services/AI/Description/DescriptionDraftService.php) | [DescriptionReviewModalTest.php](../../tests/Feature/AI/DescriptionReviewModalTest.php)
*   **Metrics:** Frequency: Medium | Manual Minutes: 3 mins | Failure Risk: Low | Revenue Impact: Medium.

### 6. Commercial Offering
*   **Classification:** `TARGET DESIGN` / `LEGACY`
*   **Description:** Specifying satılık vs kiralık contracts and commissions separate from the listing publication.
*   **Entry Point:** None (Currently pricing fields live on Listings table).
*   **Automation Executed:** None.
*   **Human Steps:** Fill price columns directly in `Ilan` tables.
*   **Evidence Paths:** [Ilan.php](../../app/Models/Ilan.php) (`fiyat` and `lansman_fiyati` properties).
*   **Metrics:** Frequency: Low | Manual Minutes: 5 mins | Failure Risk: Low | Revenue Impact: High.

### 7. Pricing (Dynamic Revenue Management)
*   **Classification:** `MANUAL`
*   **Description:** Setting nightly or seasonal prices.
*   **Entry Point:** None.
*   **Automation Executed:** None.
*   **Human Steps:** Manually input pricing schedules into the database/listing sheets.
*   **Evidence Paths:** [Ilan.php](../../app/Models/Ilan.php) | `tests/Feature/Admin/AI/PriceSuggestionTest.php` (Uses mocks, no actual pricing engine).
*   **Metrics:** Frequency: High | Manual Minutes: 20 mins | Failure Risk: High | Revenue Impact: High.

### 8. Publishing (Listing Activation)
*   **Classification:** `MANUAL` (Mock Integration)
*   **Description:** Distributing and activating listings on channels (Airbnb, Booking.com, Sahibinden).
*   **Entry Point:** None.
*   **Automation Executed:** None. `CalendarSyncService` stubs bypass external API calls.
*   **Human Steps:** Manually copy listing specs and create listings on each channel portal.
*   **Evidence Paths:** `app/Services/CalendarSyncService.php` (Stubs only).
*   **Metrics:** Frequency: Medium | Manual Minutes: 90 mins | Failure Risk: High | Revenue Impact: High.

### 9. Reservation Intake
*   **Classification:** `MANUAL`
*   **Description:** Capturing booking data from external channels or direct clients.
*   **Entry Point:** `POST /admin/reservations`
*   **Call Path:** `YazlikKiralamaController@store` ➔ `YazlikKiralamaService@createReservation` ➔ `YazlikRezervasyon::create`
*   **Automation Executed:** Saves booking data in database transaction.
*   **Human Steps:** Advisor manually receives client call/email, checks availability, and fills booking form.
*   **Evidence Paths:** [YazlikKiralamaService.php](../../app/Services/YazlikKiralamaService.php)
*   **Metrics:** Frequency: High | Manual Minutes: 10 mins | Failure Risk: High | Revenue Impact: High.

### 10. Availability Locking
*   **Classification:** `MANUAL`
*   **Description:** Locking calendars across platforms to prevent double bookings.
*   **Entry Point:** None. No observers or event listeners trigger on reservation creation.
*   **Automation Executed:** None.
*   **Human Steps:** Advisor manually updates calendars on other channels.
*   **Evidence Paths:** No event bindings found on `YazlikRezervasyon` model.
*   **Metrics:** Frequency: High | Manual Minutes: 15 mins | Failure Risk: Critical (Double booking risk) | Revenue Impact: High.

### 11. Turnover Operations
*   **Classification:** `MANUAL` (Manual Tasking)
*   **Description:** Cleaning and key-delivery scheduling.
*   **Entry Point:** None.
*   **Automation Executed:** None.
*   **Human Steps:** Operations team reviews check-out list manually and creates tasks for cleaning staff.
*   **Evidence Paths:** `app/Modules/TakimYonetimi/Models/Gorev.php`
*   **Metrics:** Frequency: High | Manual Minutes: 15 mins | Failure Risk: High | Revenue Impact: Medium.

### 12. Finance Events and Payout Reconciliation
*   **Classification:** `PARTIAL`
*   **Description:** Recording transactions and reconciliating bank statements.
*   **Otomatik Olanlar:** `FinancialLedgerService` automatically posts debit/credit entries to the ledger for recorded payments.
*   **Manuel Olanlar:** Reconciling bank statements, owner payout transfers, and invoice mapping is done manually.
*   **Evidence Paths:** [FinancialLedgerService.php](../../app/Services/FinancialLedgerService.php) | [LedgerEntry.php](../../app/Models/Finance/LedgerEntry.php)
*   **Metrics:** Frequency: High | Manual Minutes: 30 mins | Failure Risk: Medium | Revenue Impact: High.

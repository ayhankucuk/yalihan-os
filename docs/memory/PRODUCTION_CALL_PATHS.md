# Yalıhan OS — Production Call Paths Directory
**Sponsor:** Strategic Architecture & Automation Board (SAAB)  
**Date:** 2026-07-24  
**Status:** PROPOSED (Awaiting SAAB Review)  

---

## 1. CalendarSyncService Call Graph

The `CalendarSyncService` handles takvim sync actions. It is called by both manual controllers and scheduled CLI commands.

### Call Path 1: Manual Trigger via Admin Controller
```
HTTP POST /admin/listings/{ilan_id}/sync
  └─► App\Http\Controllers\Admin\CalendarSyncController@manualSync
        └─► App\Services\CalendarSyncService@syncCalendar
              ├─► App\Services\CalendarSyncService@pushToExternalPlatform
              │     ├─► App\Services\CalendarSyncService@pushToAirbnb (Stub returns true)
              │     ├─► App\Services\CalendarSyncService@pushToBookingCom (Stub returns true)
              │     └─► App\Services\CalendarSyncService@pushToGoogleCalendar (Stub returns true)
              └─► App\Models\IlanTakvimSync@markAsSynced / markAsFailed (Writes DB record)
```

### Call Path 2: Scheduled Command Handler (Cron)
```
Artisan calendar:sync
  └─► App\Console\Commands\CalendarSyncCommand@handle
        └─► App\Services\CalendarSyncService@syncAllCalendars
              └─► App\Services\CalendarSyncService@syncCalendar
```

---

## 2. YazlikRezervasyon Write Paths

The creation and modification of bookings are processed through the `YazlikKiralamaService`.

### Call Path: Manual Booking Creation
```
HTTP POST /admin/reservations
  └─► App\Http\Controllers\Admin\YazlikKiralamaController@store
        └─► App\Services\YazlikKiralamaService@createReservation
              └─► App\Models\YazlikRezervasyon::create (Eloquent DB Insert)
```
*Note on Event Hooking:* No Eloquent Observers, Listeners, or Jobs are bound to `YazlikRezervasyon::created` event. This is a critical gap preventing automated task scheduling.

---

## 3. Gorev Automation Triggers

The task management model `Gorev` has event listeners registered, but currently only dispatches notify events on manual edits.

### Call Path: Task Mutated
```
Gorev Mutated (Insert/Update)
  └─► App\Observers\GorevObserver@created / updated
        ├─► App\Events\GorevCreated
        ├─► App\Events\GorevDurumChanged
        ├─► App\Events\GorevDeadlineYaklasiyor
        └─► App\Events\GorevGecikti
```
*Note on Operations Automation:* Task creation is initiated manually via `GorevController@store`. No automated triggers link reservations to task generation.

---

## 4. YalihanCortex & PublishDecisionAgent Execution Paths

The AI pipeline maps content generation and publishing decisions through the `AIOrchestrator`.

### Call Path 1: Description Generation
```
HTTP POST /admin/listings/{id}/generate-description
  └─► App\Http\Controllers\Admin\ListingController@generateDescription
        └─► App\Services\AI\Description\DescriptionDraftService@draft
              └─► App\Services\AI\YalihanCortex@generateDescription
                    └─► App\Services\AI\AIOrchestrator@orchestrateAI
                          └─► App\Services\AI\Providers\DeepSeekCortexProvider@execute
                                └─► External DeepSeek API (HTTP Request)
```

### Call Path 2: Publishing Decision Agent
```
Hermes Workflow Loop
  └─► App\Services\Hermes\Handlers\Workflow\PublishDecisionAgent@handle
        └─► App\Services\AI\YalihanCortex@evaluateListingPublishing
```

---

## 5. FinancialLedgerService Production Usage

The Double-Entry Ledger is triggered during transaction postings or SaaS wallet updates.

### Call Path: Treasury Transaction Posting
```
SaaS Wallet Charge / Manual Transaction Posting
  └─► App\Services\Finance\TransactionService@recordTransaction
        └─► App\Services\FinancialLedgerService@recordDoubleEntry
              └─► App\Models\Finance\LedgerEntry::create (Two balanced DB inserts)
```
*Note on Reconciliations:* Audit confirms no active calls or schedulers exist to import bank statements or platform payout reports into `FinancialLedgerService`.

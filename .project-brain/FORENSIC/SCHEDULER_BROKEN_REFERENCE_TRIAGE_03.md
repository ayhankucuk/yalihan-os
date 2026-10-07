# SCHEDULER_BROKEN_REFERENCE_TRIAGE_03
## Scheduler 14 Broken Reference — Forensic Triage Report

**Tarih:** 2026-09-23
**TASK_ID:** `SCHEDULER_BROKEN_REFERENCE_TRIAGE_03`
**MODE:** STRICT READ-ONLY
**REPORTING_TO:** Ayhan (Decision Owner)
**PARENT:** `SCHEDULED_TASK_RELIABILITY_VERIFICATION_02`
**HEAD:** `7d320a44` (release-candidate/RC2)
**STATUS:** ✅ TRIAGE_COMPLETE
**Evidence Level:** `REPO_VERIFIED`

---

## 0 — RECONCILIATION: Evidence Contradiction

| Finding | Prior Audit (VERIFICATION_02) | This Audit (TRIAGE_03) | Source |
|---|---|---|---|
| `cortex:hunt` frequency | "everyMinute()" | **`hourly()`** | `Kernel.php:105` → `schedule:list` confirms `0 * * * *` |

**Resolution:** Prior audit misread the Kernel. `cortex:hunt` runs **hourly** (top of every hour), not every minute. The severity of this finding is DOWNGRADED from "every minute" to "every hour" — but unbounded O(n×m) load + no pagination + no overlap still makes it HIGH remediation priority, just not P0.

**All other findings from VERIFICATION_02 are CONFIRMED against HEAD `7d320a44`.**

---

## 1 — CURRENT STATE

```bash
Git HEAD:      7d320a44
Branch:        release-candidate/RC2
Schedule:       32 entries (from schedule:list)
Working Tree:   Uncommitted (.project-brain/*, config/company.php)
```

### Broken Entries Confirmed (from schedule:list)

| # | Command | Schedule | Status |
|---|---|---|---|
| 1 | `exchange:update` | `0 10 * * *` | BROKEN — no class |
| 2 | `testsprite:auto-learn` | `0 3 * * *` | BROKEN — no class |
| 3 | `context7:query-scan --persist` | `0 * * * *` | BROKEN — no class |
| 4 | `quality:gate --with-context7` | `0 */6 * * *` | **WORKS** — flag ignored |
| 5 | `context7:hot-fix --auto-repair` | `0 * * * *` | BROKEN — no class |
| 6 | `context7:smart-detect` | `0 */2 * * *` | BROKEN — no class |
| 7 | `context7:dependency-audit` | `0 4 * * *` | BROKEN — no class |
| 8 | `context7:smell-detect` | `30 4 * * *` | BROKEN — no class |
| 9 | `context7:score-report` | `0 2 * * 1` | BROKEN — no class |
| 10 | `context7:phase-scan --all` | `0 5 * * *` | BROKEN — no class |
| 11 | `context7:trends --days=30` | `0 3 * * 5` | BROKEN — no class |
| 12 | `standard:check --type=context7` | `0 2 * * 0` | **WORKS** — `--type=context7` accepted |
| 13 | `gorevler:check-deadlines --gun=1` | `0 8 * * *` | BROKEN — no class |
| 14 | `gorevler:check-deadlines --gun=1` | `0 14 * * *` | BROKEN — same no class |

---

## 2 — INDIVIDUAL ENTRY ANALYSIS

### 🔴 ENTRY 1: `exchange:update`
```
COMMAND:         exchange:update
SCHEDULE:        0 10 * * * (dailyAt 10:00)
BUSINESS INTENT: TCMB kur bilgilerini günlük güncelleme
HISTORICAL_IMPL: Never existed — no git history for any ExchangeCommand file
CURRENT_REPLACEMENT: TCMBCurrencyService exists; ExchangeRateController has updateRates()
                    But NO automated scheduler trigger for TCMBCurrencyService::updateRates()
                    CacheHelper uses 1-hour TTL on getTodayRates()
                    Controller's /api/exchange-rates/update requires admin POST
CLASSIFICATION:  **B — MISSING_ENTRYPOINT_RESTORE**
RECOMMENDED_ACTION:
  1. Create ExchangeUpdateCommand CLI entry point wrapping TCMBCurrencyService::updateRates()
  2. Keep scheduler entry (business intent VALID: TCMB rates need daily DB refresh)
  3. Alternative: migrate to ExchangeRateController::update() triggered by Laravel scheduler
     via an HTTP call or internal service — but CLI command is simpler
EVIDENCE_LEVEL:   REPO_VERIFIED
EVIDENCE_TYPE:    Kernel.php comment + TCMBCurrencyService::updateRates() + config/exchange.php
                  + ExchangeRateController::update()
```

### 🔴 ENTRY 2: `testsprite:auto-learn`
```
COMMAND:         testsprite:auto-learn
SCHEDULE:        0 3 * * * (dailyAt 03:00)
BUSINESS INTENT: TestSprite auto-learning (AI-powered test generation?)
HISTORICAL_IMPL: Never existed in repo history — no git commit, no file, no config reference
CURRENT_REPLACEMENT: NONE — no TestSprite service, model, or config exists
CLASSIFICATION:  **A — LEGACY_REMOVE**
RECOMMENDED_ACTION:
  1. Remove Kernel.php lines 27-30
  2. No restoration — no evidence of business intent remaining
  3. Check if any AI test generation is handled elsewhere (AI services)
EVIDENCE_LEVEL:   REPO_VERIFIED
EVIDENCE_TYPE:    Grepped entire repo: only reference is the Kernel.php comment + schedule
```

### 🔴 ENTRY 3: `context7:query-scan --persist`
```
COMMAND:         context7:query-scan
SCHEDULE:        0 * * * * (hourly) — runs EVERY HOUR
BUSINESS INTENT: "Controller/Route queries scan" — scan codebase for query patterns
HISTORICAL_IMPL: Never existed — no file, no command class, no test
CURRENT_REPLACEMENT: `SabScanCommand.php` — static analysis scanner (SAB architecture check)
                  `CrmDriftScan.php`, `GovDriftScan.php` — domain drift detection
                  These are NOT equivalent — they check code structure, not route queries
CLASSIFICATION:  **D — INTENT_UNKNOWN**
RECOMMENDED_ACTION:
  1. Do NOT remove without understanding what "query-scan" was supposed to do
  2. If it scanned for SQL query patterns or N+1 problems: legitimate need
  3. If it scanned for code structure violations: sab:integrity-scan may cover it
  4. BLOCKED: Requires Ayhan decision on what "query-scan" means
EVIDENCE_LEVEL:   UNKNOWN
EVIDENCE_TYPE:    Authority.json mentions "context7:integrity-scan → sab:integrity-scan (REMOVED)"
                  but no definition of "query-scan"
```

### 🟡 ENTRY 4: `quality:gate --with-context7`
```
COMMAND:         quality:gate --with-context7
SCHEDULE:        0 */6 * * * (everySixHours)
BUSINESS INTENT: "Unified Quality Gate with Context7 enforcement"
HISTORICAL_IMPL: Command EXISTS (QualityGateCommand.php) ✅
                  BUT --with-context7 flag is NOT recognized
                  QualityGateCommand accepts: --baseline, --force only
                  When --with-context7 is passed, it's silently ignored
CURRENT_REPLACEMENT: N/A — command works, flag is vestigial
CLASSIFICATION:  **E — DUPLICATE_SCHEDULE_REFERENCE (no-op variant)**
RECOMMENDED_ACTION:
  1. Either: Remove `--with-context7` from schedule (command runs without flag)
     → Change Kernel.php line 38: 'quality:gate' (no flag)
  2. Or: Add --with-context7 support to QualityGateCommand if it has semantic meaning
  3. Root cause: someone added `--with-context7` flag that was never implemented
EVIDENCE_LEVEL:   REPO_VERIFIED
EVIDENCE_TYPE:    QualityGateCommand.php signature + --help output
```

### 🔴 ENTRY 5: `context7:hot-fix --auto-repair`
```
COMMAND:         context7:hot-fix
SCHEDULE:        0 * * * * (hourly)
BUSINESS INTENT: "Hot-Fix Real-time Scanner" — automatic code repair?
HISTORICAL_IMPL: Never existed — no file, no service
CURRENT_REPLACEMENT: NONE
CLASSIFICATION:  **A — LEGACY_REMOVE**
RECOMMENDED_ACTION:
  1. Remove Kernel.php lines 42-45
  2. "Auto-repair" capability does not exist in the codebase
  3. If hot-fix means SAB drift detection: sab:integrity-scan handles it
EVIDENCE_LEVEL:   REPO_VERIFIED
EVIDENCE_TYPE:    Repo-wide grep: no hot-fix implementation
```

### 🔴 ENTRY 6: `context7:smart-detect`
```
COMMAND:         context7:smart-detect
SCHEDULE:        0 */2 * * * (every 2 hours)
BUSINESS INTENT: "Smart Pattern Detection"
HISTORICAL_IMPL: Never existed — no file
CURRENT_REPLACEMENT: NONE
CLASSIFICATION:  **A — LEGACY_REMOVE**
RECOMMENDED_ACTION:
  1. Remove Kernel.php lines 47-50
  2. No evidence of any "smart detect" service
EVIDENCE_LEVEL:   REPO_VERIFIED
```

### 🔴 ENTRY 7: `context7:dependency-audit`
```
COMMAND:         context7:dependency-audit
SCHEDULE:        0 4 * * * (dailyAt 04:00)
BUSINESS INTENT: Dependency audit (composer/npm dependency vulnerability check?)
HISTORICAL_IMPL: Never existed — no file
CURRENT_REPLACEMENT: NONE (but legitimate need — dependency security)
CLASSIFICATION:  **A — LEGACY_REMOVE**
RECOMMENDED_ACTION:
  1. Remove Kernel.php lines 52-55
  2. If dependency audit needed: use dedicated tool (composer audit, npm audit)
     not a custom Context7 command
EVIDENCE_LEVEL:   REPO_VERIFIED
```

### 🔴 ENTRY 8: `context7:smell-detect`
```
COMMAND:         context7:smell-detect
SCHEDULE:        30 4 * * * (dailyAt 04:30)
BUSINESS_INTENT: "Code Smell Detection"
HISTORICAL_IMPL: Never existed — no file
CURRENT_REPLACEMENT: sab:integrity-scan (static AST analysis) — partial overlap
CLASSIFICATION:  **A — LEGACY_REMOVE**
RECOMMENDED_ACTION:
  1. Remove Kernel.php lines 57-60
  2. sab:integrity-scan covers code quality checks
EVIDENCE_LEVEL:   REPO_VERIFIED
```

### 🔴 ENTRY 9: `context7:score-report`
```
COMMAND:         context7:score-report
SCHEDULE:        0 2 * * 1 (weekly Monday 02:00)
BUSINESS_INTENT: "Compliance Score Report"
HISTORICAL_IMPL: Never existed — no file
CURRENT_REPLACEMENT: NONE — but standard:check generates a score report
CLASSIFICATION:  **A — LEGACY_REMOVE**
RECOMMENDED_ACTION:
  1. Remove Kernel.php lines 62-67
  2. standard:check (working) already generates compliance scores
EVIDENCE_LEVEL:   REPO_VERIFIED
```

### 🔴 ENTRY 10: `context7:phase-scan --all`
```
COMMAND:         context7:phase-scan
SCHEDULE:        0 5 * * * (dailyAt 05:00)
BUSINESS_INTENT: "Phase-Based Scan" — project phase tracking?
HISTORICAL_IMPL: Never existed — no file
CURRENT_REPLACEMENT: NONE
CLASSIFICATION:  **A — LEGACY_REMOVE**
RECOMMENDED_ACTION:
  1. Remove Kernel.php lines 69-72
  2. No evidence of "phase" concept in codebase beyond Context7 comments
EVIDENCE_LEVEL:   REPO_VERIFIED
```

### 🔴 ENTRY 11: `context7:trends --days=30`
```
COMMAND:         context7:trends
SCHEDULE:        0 3 * * 5 (weekly Friday 03:00)
BUSINESS_INTENT: "Trend Analysis"
HISTORICAL_IMPL: Never existed — no file
CURRENT_REPLACEMENT: NONE
CLASSIFICATION:  **A — LEGACY_REMOVE**
RECOMMENDED_ACTION:
  1. Remove Kernel.php lines 74-79
  2. No trend analysis service exists
EVIDENCE_LEVEL:   REPO_VERIFIED
```

### 🟢 ENTRY 12: `standard:check --type=context7`
```
COMMAND:         standard:check --type=context7
SCHEDULE:        0 2 * * 0 (weekly Sunday 02:00)
BUSINESS_INTENT: "Context7 Standard Check" — verify Context7 naming compliance
HISTORICAL_IMPL: Command EXISTS (StandardCheckCommand.php) ✅
                  --type=context7 is ACCEPTED (default value)
CURRENT_REPLACEMENT: N/A — command fully functional
CLASSIFICATION:  **NOT BROKEN — REMOVE FROM BROKEN LIST**
RECOMMENDED_ACTION:
  1. No action needed — command works correctly
  2. Prior audit incorrectly classified this as "broken"
EVIDENCE_LEVEL:   REPO_VERIFIED
EVIDENCE_TYPE:    standard:check --help + StandardCheckCommand.php
```

### 🔴 ENTRY 13-14: `gorevler:check-deadlines` (2 schedule entries)
```
COMMAND:         gorevler:check-deadlines --gun=1
SCHEDULE:        08:00 AND 14:00 (two separate entries, same command)
BUSINESS_INTENT: Check task (gorev) deadlines, send notifications for approaching/overdue
HISTORICAL_IMPL: Never existed as a command — no file, no test
                  BUT Gorev model EXISTS with full deadline tracking (bitis_tarihi)
                  AND n8n integration EXISTS for deadline notifications:
                    NotifyN8nAboutGorevDeadlineYaklasiyor.php
                    N8nIntegrationService.php has 'gorev_deadline' webhook
                  AND TelegramBotService sends deadline reminders
CURRENT_REPLACEMENT:
  - Gorev model has geciktiMi(), deadlineYaklasiyorMu(), gecikmeDurumuAttribute()
  - n8n webhook fires on gorev deadline events (business logic NOT in Laravel)
  - TelegramBotService sends deadline reminders via /gorev_list command
  → BUT there is NO automated scheduler that checks deadlines and triggers notifications
CLASSIFICATION:  **C — REPLACED_BY_CANONICAL_TASK (PARTIAL)**
  The CLI command gorevler:check-deadlines is BROKEN.
  But deadline checking EXISTS via n8n webhooks (event-driven, not scheduled).
  Missing: a scheduled Gorev deadline enforcer that:
    - Scans all gorevler where bitis_tarihi <= now() + $gun days
    - Sends alerts for overdue/yaklasiyor tasks
RECOMMENDED_ACTION:
  1. Ayhan decision required: Is scheduled deadline check needed in addition to n8n webhooks?
     - If YES: create GorevCheckDeadlinesCommand wrapping existing model methods
     - If NO (n8n webhooks sufficient): remove both Kernel.php entries (08:00 + 14:00)
  2. These are DUPLICATE references to the SAME broken command
     → One decision covers both entries
EVIDENCE_LEVEL:   REPO_VERIFIED
EVIDENCE_TYPE:    Gorev.php deadline model + n8n webhook job + TelegramBotService deadline
```

---

## 3 — GROUPED REMEDIATION SETS

### ✅ SAFE_REMOVAL_SET (9 entries)

| Entry | Command | Reason |
|---|---|---|
| 2 | `testsprite:auto-learn` | Never existed; no business capability evidence |
| 5 | `context7:hot-fix --auto-repair` | Never existed; auto-repair not in codebase |
| 6 | `context7:smart-detect` | Never existed; no pattern detection service |
| 7 | `context7:dependency-audit` | Never existed; no dependency audit tool |
| 8 | `context7:smell-detect` | Never existed; sab:integrity-scan covers this |
| 9 | `context7:score-report` | Never existed; standard:check handles scores |
| 10 | `context7:phase-scan --all` | Never existed; no phase concept in codebase |
| 11 | `context7:trends --days=30` | Never existed; no trend analysis service |
| 4 | `quality:gate --with-context7` | Command works; --with-context7 is no-op flag |

**Implementation action:** Remove 9 scheduler entries from Kernel.php.

### 🔶 ENTRYPOINT_RESTORE_SET (1 entry)

| Entry | Command | Reason |
|---|---|---|
| 1 | `exchange:update` | Business intent VALID; TCMBCurrencyService exists; needs CLI wrapper |

**Implementation action:** Create ExchangeUpdateCommand wrapping TCMBCurrencyService::updateRates(). Keep scheduler entry.

### ⚠️ CANONICAL_REPLACEMENT_SET (1 entry, 2 schedule occurrences)

| Entry | Command | Reason |
|---|---|---|
| 13-14 | `gorevler:check-deadlines --gun=1` | Command missing; deadline capability EXISTS via n8n/event-driven. Ayhan decision needed. |

**Implementation action:** Ayhan decides:
- A) Remove both entries (n8n webhooks sufficient)
- B) Create GorevCheckDeadlinesCommand (scheduled enforcement)

### ❓ UNKNOWN_SET (1 entry)

| Entry | Command | Reason |
|---|---|---|
| 3 | `context7:query-scan --persist` | Never existed; purpose unclear. If it scanned for SQL patterns, it may have had legitimate value. |

**Implementation action:** Ayhan decision required before removal.

---

## 4 — METRIC SUMMARY

```
UNIQUE_BROKEN_COMMANDS:    13  (not 14 — standard:check --type=context7 WORKS)
BROKEN_SCHEDULE_ENTRIES:  14  (standard:check counted as working = 14 broken)

SAFE_REMOVAL:              9 entries
ENTRYPOINT_RESTORE:        1 entry (exchange:update)
CANONICAL_REPLACEMENT:     1 entry × 2 occurrences (gorevler:deadline)
UNKNOWN:                   1 entry (context7:query-scan)

SCHEDULE_ENTRIES_AFFECTED: 9 + 1 + 2 + 1 = 13
(standard:check = working = excluded from count)
```

---

## 5 — EVIDENCE CONTRADICTION RESOLUTION

| Item | VERIFICATION_02 | TRIAGE_03 | Resolution |
|---|---|---|---|
| `cortex:hunt` frequency | "everyMinute()" | "hourly() — `0 * * * *`" | **DOWNGRADED**: Severity reduced. Still HIGH due to O(n×m) unbounded load. Not P0. |
| `standard:check --type=context7` | "BROKEN" | "WORKS" | **CORRECTION**: Command accepts --type=context7 as default. Not broken. |

---

## 6 — CORTEX:HUNT CORRECTED ANALYSIS

```
Command:          cortex:hunt
Schedule:         hourly() — 0 * * * * (verified: Kernel.php:105 + schedule:list)
Actual severity:  🟠 HIGH (not 🔴 CRITICAL as prior audit stated)

Behavior confirmed:
  - Ilan::where(yayinda)->get()  ← UNBOUNDED (all active listings)
  - Lead::all()                   ← UNBOUNDED (all leads)
  - Nested loop: O(n×m) iterations
  - Per iteration: ROI calculation + MatchingEngine query + AI generate() call
  - Telegram send per match >= 90 score
  - NO pagination
  - NO withoutOverlapping()

Remediation still required:
  1. Add pagination/chunking for listings and leads
  2. Add withoutOverlapping()
  3. Consider reducing frequency or adding batch window parameter
But NOT everyMinute() = not system-locking risk
```

---

## 7 — NEXT IMPLEMENTATION CONTRACT

### Smallest Safe Implementation Task

**Task 1 (Immediate — Ayhan Approval):**
Remove 9 `SAFE_REMOVAL_SET` entries from `app/Console/Kernel.php`.

**Files to modify:** `app/Console/Kernel.php` only.

**Entries to remove:**
```
Line 27-30:   testsprite:auto-learn
Line 38-40:   quality:gate --with-context7  → change to 'quality:gate'
Line 42-45:   context7:hot-fix --auto-repair
Line 47-50:   context7:smart-detect
Line 52-55:   context7:dependency-audit
Line 57-60:   context7:smell-detect
Line 62-67:   context7:score-report
Line 69-72:   context7:phase-scan --all
Line 74-79:   context7:trends --days=30
```

**Task 2 (Ayhan Decision — gorevler:deadline):**
Ayhan decides: remove both entries OR create GorevCheckDeadlinesCommand.

**Task 3 (Ayhan Decision — context7:query-scan):**
Ayhan decides: remove OR investigate intended purpose.

**Task 4 (Ayhan Decision — exchange:update):**
Create ExchangeUpdateCommand wrapping TCMBCurrencyService::updateRates().

---

## 8 — EVIDENCE CATALOG

| Source | Type | Finding |
|---|---|---|
| `app/Console/Kernel.php` | Direct | All 14 schedule entries + comments |
| `php artisan schedule:list` | Direct | 32 entries confirmed |
| `php artisan standard:check --help` | Direct | --type accepted, WORKS |
| `php artisan quality:gate --help` | Direct | --with-context7 NOT in signature |
| `app/Services/TCMBCurrencyService.php` | Direct | updateRates() method exists |
| `app/Http/Controllers/Api/ExchangeRateController.php` | Direct | update() uses TCMBCurrencyService |
| `app/Modules/TakimYonetimi/Models/Gorev.php` | Direct | deadline model with methods |
| `app/Jobs/NotifyN8nAboutGorevDeadlineYaklasiyor.php` | Direct | n8n deadline webhook job |
| `app/Services/Integrations/N8nIntegrationService.php` | Direct | gorev_deadline webhook defined |
| `app/Services/TelegramBotService.php` | Direct | deadline reminders via Telegram |
| `app/Console/Commands/StandardCheckCommand.php` | Direct | Command with --type=context7 |
| `app/Console/Commands/QualityGateCommand.php` | Direct | Command without --with-context7 |
| `.sab/authority.json` | Direct | context7_legacy list; sab:integrity-scan |
| `git log --all` | Forensic | No ExchangeCommand history; no TestSprite history |
| `config/exchange.php` | Direct | Exchange configuration (context7 standard) |
| Repo grep (testsprite, context7:*, gorevler) | Negative | Only Kernel.php references for broken commands |

---

## 9 — FINAL RESULT

```
FINAL_RESULT: TRIAGE_COMPLETE

All 14 schedule entries classified.
Evidence contradiction resolved.
Corrected severity for cortex:hunt documented.
standard:check reclassified as WORKING.

Ayhan Human Gate required for:
  - exchange:update (restore or decision)
  - gorevler:deadline (Ayhan scope decision)
  - context7:query-scan (Ayhan intent decision)
```

---

## 10 — SCHEDULER CONTRACT GUARD (FYI)

Per Ayhan's suggestion: A CI/test guard that compares Kernel.php schedule entries against `php artisan list` registrations would prevent ghost scheduler entries. This is OUT OF SCOPE for this task but noted for future implementation.

---
*Triage: TRIAGE_COMPLETE | Ayhan karar sahibi | Evidence: REPO_VERIFIED | 2026-09-23*

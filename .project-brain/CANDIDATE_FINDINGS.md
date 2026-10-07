# YALIHAN OS — Candidate Finding Inbox

> **SSOT Policy:** Append-only candidate inbox for automated audit detectors (Watchdog, Drift Hunter).
> **Role:** Unverified observations are NOT canonical truth. They become actionable only after Morning Triage revalidation.
> **Lifecycle:** Detector appends (`STATUS: NEW`) → Morning Triage audits (`DISPOSITION: ...`) → V1 Remediation Queue.

---

## 📋 Inbox Protocol & Invariants

1. **Append-Only for Detectors:** 
   Watchdog (22:00) and Canonical Drift Hunter (23:00) have write authority **ONLY** to append new records to this file. They MUST NEVER edit existing entries, delete records, or modify canonical state files (`PROJECT_STATE.md`, `KNOWN_ISSUES.md`, `DECISION_LOG.md`, `EVIDENCE_INDEX.md`).
2. **Provenance Preservation:** 
   `OBSERVED_AT_HEAD` and `SOURCE` are immutable once written. Records are never deleted.
3. **Morning Triage In-Place Disposition:** 
   Morning Triage (09:00) reads all entries where `STATUS: NEW`, revalidates each claim against the current working HEAD, and records the outcome under `TRIAGE:`.
4. **Valid Dispositions:**
   - `CURRENT_FINDING`: Verified on current HEAD; eligible for V1 Remediation Queue.
   - `STALE_FINDING`: Disproved, already resolved, or no longer reproducible on current HEAD.
   - `DUPLICATE`: Redundant with an existing backlog or inbox claim.
   - `UNKNOWN`: Insufficient evidence to prove or disprove; requires targeted check.
   - `BLOCKED_DECISION`: Requires Ayhan architectural or business decision before remediation.

---

## 🧾 Candidate Finding Records

<!-- NEW CANDIDATE ENTRIES APPENDED BELOW THIS LINE -->

```yaml
# Example Record Template:
# CANDIDATE_ID: CF-2026-10-05-001
# SOURCE: WATCHDOG # [WATCHDOG | DRIFT_HUNTER | OTHER]
# OBSERVED_AT: 2026-10-05T22:00:00Z
# OBSERVED_AT_HEAD: 3b1f0453fa6b677d3f2e3a533cb7bb56df128a54
# CLAIM: "Short description of the potential contract drift or defect"
# EVIDENCE_LEVEL: INFERRED # [INFERRED | REPO_VERIFIED | TEST_VERIFIED]
# EVIDENCE_TYPE: SOURCE_DIFF # [SOURCE_DIFF | AST_INVARIANT | DATABASE_SCHEMA | RUNTIME_TEST]
# STATUS: NEW # [NEW | PROCESSED]
# TRIAGE:
#   REVALIDATED_AT_HEAD:
#   DISPOSITION: # [CURRENT_FINDING | STALE_FINDING | DUPLICATE | UNKNOWN | BLOCKED_DECISION]
#   RELATED_CLAIM:
```

```yaml
CANDIDATE_ID: CF-2026-10-05-DRYRUN-001
SOURCE: WATCHDOG
OBSERVED_AT: 2026-10-05T10:30:00Z
OBSERVED_AT_HEAD: 3b1f0453fa6b677d3f2e3a533cb7bb56df128a54
CLAIM: "Dry run validation of detector-to-triage candidate handoff protocol"
EVIDENCE_LEVEL: TEST_VERIFIED
EVIDENCE_TYPE: CONFIGURATION
STATUS: PROCESSED
TRIAGE:
  REVALIDATED_AT_HEAD: 9baf60211c9938cf8a4f3b07a91bd8d116f85b50
  DISPOSITION: CURRENT_FINDING
  RELATED_CLAIM: SCHEDULED_FINDING_HANDOFF_ACTIVATION_01
```

```yaml
CANDIDATE_ID: CF-2026-10-05-001
SOURCE: DRIFT_HUNTER
OBSERVED_AT: 2026-10-05T23:17:00+03:00
OBSERVED_AT_HEAD: 02fcf94d87bc39fda76d525298c77c4c6dfe4dfe
CLAIM: "DemandMatchingEngine area scoring reads non-existent Ilan::$metrekare instead of canonical brut_m2 / alan_m2, permanently degrading area match scores to 50%"
EVIDENCE_LEVEL: INFERRED
EVIDENCE_TYPE: AST_INVARIANT
STATUS: NEW
TRIAGE:
  REVALIDATED_AT_HEAD: 495ac6b6
  DISPOSITION: STALE_FINDING
  REASON: Already fixed. DemandMatchingEngineAreaScoreTest.php tests confirm alan_m2 usage. Tests: 4/4 PASS.
RELATED_CLAIM:
```

```yaml
CANDIDATE_ID: CF-2026-10-05-002
SOURCE: DRIFT_HUNTER
OBSERVED_AT: 2026-10-05T23:17:00+03:00
OBSERVED_AT_HEAD: 02fcf94d87bc39fda76d525298c77c4c6dfe4dfe
CLAIM: "DemandMatchingEngine reverse and bulk matching queries Talep without withoutTenant(), triggering TenantScope fail-closed 1=0 in background and cross-tenant execution"
EVIDENCE_LEVEL: INFERRED
EVIDENCE_TYPE: AST_INVARIANT
STATUS: NEW
TRIAGE:
  REVALIDATED_AT_HEAD: 495ac6b6
  DISPOSITION: STALE_FINDING
  REASON: withoutTenant() is already used. Line 438: DemandMatchingEngine uses Ilan::withoutTenant() for global corpus search.
RELATED_CLAIM:
```

```yaml
CANDIDATE_ID: CF-2026-10-05-003
SOURCE: DRIFT_HUNTER
OBSERVED_AT: 2026-10-05T23:17:00+03:00
OBSERVED_AT_HEAD: 02fcf94d87bc39fda76d525298c77c4c6dfe4dfe
CLAIM: "Dual route registration for admin/kullanicilar binds GET admin/kullanicilar/{id} to AuthController@show which crashes on non-existent view auth::users.show"
EVIDENCE_LEVEL: INFERRED
EVIDENCE_TYPE: SOURCE_DIFF
STATUS: NEW
TRIAGE:
  REVALIDATED_AT_HEAD: 495ac6b6
  DISPOSITION: STALE_FINDING
  REASON: Route admin/kullanicilar/{id} → AuthController@show not found. Admin users use UserController::class via routes/admin.php.
RELATED_CLAIM:
```

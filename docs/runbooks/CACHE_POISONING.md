# RUNBOOK: Cache Poisoning / Inconsistency (Priority: HIGH)

## 🚨 Trigger
**Governance Alert**: `CACHE_MISMATCH`
**Observation**: UI shows different values than DB/API.
**Severity**: HIGH (User Trust Issue)

## 🔍 Diagnosis
1.  **Compare L1 vs L2**:
    -   Is the issue verifiable in `redis-cli`?
    -   Does `php artisan tinker` show the correct value (bypassing specific cache keys)?

## 🛠️ Resolution

1.  **Purge Affected Tenant**:
    Do NOT run global flush if possible.
    ```bash
    php artisan gov:cache:purge --tenant={tenant_id}
    ```

2.  **Deterministic Warming**:
    Re-populate cache to prevent thundering herd.
    ```bash
    php artisan gov:cache:warm --tenant={tenant_id}
    ```

3.  **Verify Parity**:
    Run the parity check command (if available) or manual spot check.

## 📝 Prevention
-   Ensure all cache keys use `gov_v2:{tenant_id}:{source}:{hash}` format.
-   Check `UpsDeterminismTest` for regression in cache logic.

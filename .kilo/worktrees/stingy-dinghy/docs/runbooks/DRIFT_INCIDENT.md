# RUNBOOK: Configuration Drift Incident (Priority: HIGH)

## 🚨 Trigger
**Governance Alert**: `DRIFT DETECTED`
**Source**: `DriftDetectionService` / `UpsDriftCheck`
**Severity**: HIGH (Potential data inconsistency)

## 🔍 Triage
1.  **Identify Tenant**: Check alert for `tenant_id`.
2.  **Verify Active Version**:
    ```bash
    php artisan gov:drift:scan --tenant={tenant_id}
    ```
3.  **Analyze Diff**:
    -   Compare `active_version` hash with `database_state`.
    -   Identify drifting fields (e.g., `feature_assignments`, `template_rules`).

## 🛠️ Resolution Statuses

### Scenario A: Legitimate Change (False Positive)
*The database change is intentional but wasn't propagated via Governance.*
1.  **Action**: Create a new Version to match the database state.
    ```bash
    php artisan gov:baseline:init --tenant={tenant_id} --reason="Sync with DB"
    ```
2.  **Verify**: Run drift scan again. Should return cleans.

### Scenario B: Unauthorized Change (True Positive)
*The database was modified directly, bypassing Governance.*
1.  **Action**: Enforce the Active Version (Overwrite DB).
    ```bash
    php artisan property:enforce-config --tenant={tenant_id} --force
    ```
2.  **Investigate**: Check database logs for the source of the change.

## 📝 Escalation
If drift persists after enforcement:
1.  **Freeze Tenant**: `php artisan gov:tenant:freeze --tenant={tenant_id}`
2.  **Escalate**: Contact System Architect.

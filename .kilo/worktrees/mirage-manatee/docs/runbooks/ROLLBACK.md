# RUNBOOK: Configuration Rollback (Priority: HIGH)

## 🚨 Trigger
**Operational Request**: Bad configuration deployed (e.g., wrong pricing rules).
**Severity**: HIGH (Business Impact)

## 🛑 Pre-Requisites
-   Previous version must be `APPROVED` or `ARCHIVED`.
-   Rollback must be authorized by an Admin.

## 🛠️ Execution

1.  **Identify Target Version**:
    List versions history.
    ```bash
    php artisan property:config-history --limit=5
    ```

2.  **Dry Run**:
    Simulate the activation to check for conflicts.
    ```bash
    php artisan gov:activation:dry-run --version={version_hash}
    ```

3.  **Execute Rollback**:
    ```bash
    php artisan property:activate-version {version_hash} --rollback
    ```

4.  **Verify**:
    -   Check the UI for expected changes.
    -   Verify `gov_v2:{tenant_id}:active_version` cache is updated.

## 📝 Audit
All rollbacks are recorded in `governance_audit_log` with `action_type=ROLLBACK`.

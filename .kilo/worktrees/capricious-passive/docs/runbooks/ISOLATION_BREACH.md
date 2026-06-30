# RUNBOOK: Multi-Tenant Isolation Breach (Priority: CRITICAL)

## 🚨 Trigger
**Governance Alert**: `ISOLATION_LEAK`
**Observation**: Tenant A sees Tenant B's data/configuration.
**Severity**: CRITICAL (Legal/Privacy Violation)

## 🛑 Automated Response
If detected by middleware, the request is aborted.

## 🛠️ Resolution

1.  **IMMEDIATE FREEZE**:
    Lock down both the Victim and the Suspect tenants.
    ```bash
    php artisan gov:tenant:freeze --tenant={tenant_a_id}
    php artisan gov:tenant:freeze --tenant={tenant_b_id}
    ```

2.  **Drain Connections**:
    Kill active sessions for these tenants.

3.  **Root Cause Analysis**:
    -   Check Global Scopes in Models.
    -   Check Cache Key generation logic.
    -   Check `ResolveTenant` middleware.

4.  **Patch & Verify**:
    -   Apply fix.
    -   Run `tests/Feature/Governance/MultiTenantIsolationTest.php`.

5.  **Unfreeze**:
    Only after QA sign-off.
    ```bash
    php artisan gov:tenant:unfreeze --tenant={tenant_id}
    ```

# RUNBOOK: Signature Mismatch / Tamper (Priority: CRITICAL)

## 🚨 Trigger
**Governance Alert**: `SIGNATURE MISMATCH` / `TAMPER DETECTED`
**Source**: `ActiveConfigRegistry` / `GovernanceIncidentService`
**Severity**: CRITICAL (Security Breach)

## 🛑 Automated Response
The system typically triggers a **Hard Lock** (`Safe Lock`) automatically for the affected tenant or SYSTEM.

## 🔍 Diagnosis
1.  **Check Incident Log**:
    ```sql
    SELECT * FROM governance_incidents WHERE olay_tipi = 'signature_mismatch' ORDER BY id DESC LIMIT 1;
    ```
2.  **Verify Disk State**:
    Check `storage/app/governance/snapshots/{version_hash}.json` for modification time.

## 🛠️ Resolution

1.  **Confirm Breach**:
    If the snapshot on disk does not match the signature in DB, the file system is compromised.

2.  **Emergency Rotation**:
    -   Rotate Application Keys.
    -   Rotate Database Credentials.

3.  **Restore Trust**:
    -   Re-deploy the last known good snapshot from backup/Git.
    -   **Force New Baseline**:
        ```bash
        php artisan gov:baseline:init --force
        ```

4.  **Unlock System**:
    ```bash
    php artisan gov:tenant:unfreeze --tenant={tenant_id}
    php artisan cache:clear
    ```

## 📝 Forensic Preservation
Do NOT delete the tampered snapshot file. Move it to a quarantined S3 bucket for analysis.

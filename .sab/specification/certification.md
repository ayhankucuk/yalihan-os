# 📜 SAB Certification Specification v1.0

**Document Standard:** `SAB-CERT-SPEC-v1.0`  
**Governance Authority:** Strategic AI Architecture Board (SAAB)  
**Applicability:** Universal (All Yalıhan OS Sprints, Refactoring Tasks & Engineering PRs)  

---

## 🎯 1. Purpose & Core Philosophy

This specification defines the mandatory governance standard for engineering task certifications within **Yalıhan OS**.

The certification architecture decouples **Structural Validation (Schema)** from **Quality Gate Business Rules (Policy)** and **Auditable Outcomes (Manifest & Report)**:

```text
.sab/
  specification/
    SAB_CERTIFICATION_SPECIFICATION_V1.md   # Architectural Standard Definition
  schema/
    certification.schema.json               # Structural Type & Format Validation
  policy/
    certification.policy.json               # Quality Gate Business Rules (0 Regressions, PASS)
  certification/
    <TASK_ID>.json                          # Machine-Readable Output Manifest
  archive/
    <TASK_ID>.cert/                         # Immutable Bundle Archive
docs/
  reports/
    <TASK_ID>-EVIDENCE.md                   # 10-Section Human-Readable Evidence Report
```

### Governance Certification Lifecycle:
```text
generate  ➔  validate  ➔  evaluate  ➔  approve  ➔  sign  ➔  verify  ➔  archive
```

### Core Certification Levels:
1. `FULL_PASS`: Zero failures, zero errors across 100% of repository tests.
2. `CERTIFIED_WITHIN_EXISTING_BASELINE`: Zero target bug family failures, zero new regressions introduced, with pre-existing baseline technical debt documented and isolated in Section 1.1.
3. `HOLD / REJECTED`: Target failures unresolved or new regressions detected.

---

## 📋 2. Mandatory 10-Section Markdown Report Schema

All certification evidence documents stored in `docs/reports/` MUST strictly follow this exact 10-section layout:

```markdown
# 🛡️ SAB Standard Engineering Certification Evidence

**Report Version:** 1.0
**Task ID:** <TASK_ID>
**Date:** <YYYY-MM-DD>
**Status:** <CERTIFICATION_LEVEL>

## 1. Scope
## 1.1 Assumptions, Limitations & Out of Scope
## 2. Root Cause
## 3. Applied Fix
## 4. Verification Evidence
## 5. Baseline Comparison
## 6. Regression Assessment
## 7. Evidence Sources & Audit Metadata
## 8. Certification Decision
## 9. Approval History
## 10. Sign-off
```

---

## 🤖 3. Machine-Readable JSON Manifest Schema & Source Mapping

Every certified task must produce a JSON manifest placed in `.sab/certification/<TASK_ID>.json` compiled from ampirik (empirical) runtime sources.

### 3.1 Ampirik (Empirical) Source Mapping Table

| Manifest Field | Ampirik Kaynak / Kabuk Komutu |
|----------------|----------------------------------|
| `audit.head_sha` | `git rev-parse HEAD` |
| `audit.branch` | `git rev-parse --abbrev-ref HEAD` |
| `audit.repository` | `git config --get remote.origin.url` |
| `audit.preflight_status` | `./scripts/tools/antigravity-preflight.sh` exit code |
| `environment.php_version` | `php -r "echo PHP_VERSION;"` |
| `environment.runner` | `uname -s -m` |
| `verification.total_tests` | PHPUnit CLI / log output parser |
| `verification.assertions` | PHPUnit CLI / log output parser |
| `approval.status` | Set explicitly via `sab-cert approve <TASK_ID>` |

---

## 🔐 3.2 Cryptographic Integrity Standards

To preserve integrity without misleading cryptography terminology:

1. **Local Development / Pilot (`SHA256-DIGEST`):** Computed SHA-256 digest over JSON payload to ensure untampered local state.
2. **Internal CI (`HMAC-SHA256`):** Keyed hash using CI secret key (`SAB_SIGNING_SECRET`).
3. **Release Gate (`Ed25519`):** Digital signature with asymmetric key pair.

---

## 📦 3.3 Certification Bundle Architecture

`sab-cert archive <TASK_ID>` packages the certification into an immutable bundle directory `.sab/archive/<TASK_ID>.cert/`:

```text
.sab/archive/<TASK_ID>.cert/
  ├── manifest.json         # SHA256-DIGEST signed manifest
  ├── policy-result.json    # Quality gate policy engine evaluation result
  ├── report.md             # 10-Section markdown evidence report
  ├── verification.json     # Verification audit proof snapshot
  └── bundle-metadata.json  # Archive metadata & timestamps
```

---

## 🚦 4. Quality Gate Enforcement Rules

1. **Governance Order:** `generate ➔ validate ➔ evaluate ➔ approve ➔ sign ➔ verify ➔ archive`.
2. **Zero New Regressions:** `verification.new_regressions` MUST equal `0`.
3. **Preflight Rule:** `audit.preflight_status` MUST equal `"PASS"`.
4. **Approval Rule:** `approval.status` MUST equal `"APPROVED_FOR_MERGE"`.
5. **Schema & Policy Validation:** Manifest must pass `.sab/schema/certification.schema.json` and `.sab/policy/certification.policy.json`.

---

## 🎯 5. Pilot Exit Criteria & Operational Validation

To transition from `Architecturally Ready for Pilot Adoption` to `Operationally Validated for CI Adoption`:

1. **5 Consecutive Sprint Certifications:** Successfully certify 5 consecutive real engineering sprints using `sab-cert`.
2. **Zero False Positives:** Produce zero false `READY_FOR_MERGE` quality gate decisions.
3. **100% Deterministic Compilation:** Produce identical manifest hashes from identical clean workspace states.
4. **Automated CI Runner Validation:** Verify end-to-end execution in GitHub Actions CI runner environment.
5. **Negative Integrity Verification:** Confirm that tampered manifests trigger `exit code 2` (REJECTED) during `sab-cert verify`.
6. **Operational SLAs:**
   - Manifest Compilation Duration: `< 30 seconds`
   - Verification & Policy Evaluation Duration: `< 5 seconds`

---

## 🎛️ 6. System Compatibility Matrix

| Component | Standard Version | Status | Backwards Compatibility Contract |
|-----------|------------------|--------|----------------------------------|
| **Manifest Schema** | `1.0` | 🟢 Active | Strict schema validation against `certification.schema.json` |
| **Policy Engine** | `1.0` | 🟢 Active | Declarative JSON rule evaluation via `certification.policy.json` |
| **Bundle Format** | `1.0` | 🟢 Active | Portable `.sab/archive/<TASK_ID>.cert` bundle layout |
| **`sab-cert` CLI SDK** | `1.4` | 🟢 Active | 7-command governance lifecycle CLI |



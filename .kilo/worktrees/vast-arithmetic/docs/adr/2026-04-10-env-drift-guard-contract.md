# ADR: EnvDriftGuard Production Contract

## Date

2026-04-10

## Status

Accepted

## Context

The `system:env-drift-guard` command evolved from a simple `.env` checker to a multi-layer governance scanner (env, DB, schema, model, migration, enum). Without a formal contract:

- CI behavior depends on implementation details, not declared policy
- `--fix` capability was removed (v2.0) due to undefined mutation boundaries
- Different agents interpret check results differently
- Severity classification is hardcoded, not configurable

The system is now in **governance hardening** phase (L5: Self-Protecting). The guard needs a production-grade contract that separates *policy* from *implementation*.

## Decision

### 1. Contract Configuration

All guard policies are externalized to `config/env-drift-guard.php`:

- Each check declares: severity (fail/warn), fixability, inspected files, and fail reasoning
- CI fail policy is deterministic: `fail` = blocks pipeline, `warn` = annotation only
- `--strict` mode overrides all severity to `fail` (CI fail-fast)
- Policy SSOT is the config file; implementation MUST obey it

### 2. CI Fail Policy

| Check | Default Severity | CI Action | Rationale |
|-------|---------|-----------|-----------|
| `env_testing` | **FAIL** | Block | Test env undefined = all tests unreliable |
| `db_connectivity` | **FAIL** | Block | DB unreachable = test suite will fail entirely |
| `schema_mysql` | **FAIL** | Block | SSOT missing = no authority for validation |
| `schema_testing` | WARN | Annotate | Optional supplementary schema |
| `schema_diff` | **FAIL** | Block | Schema files diverged = unreliable test DB |
| `fillable_alignment` | WARN | Annotate | Ghost fields need manual review |
| `migration_parity` | WARN | Annotate | Divergence is informational |
| `enum_drift` | WARN | Annotate | Legacy values need coordinated migration |
| `schema_checksum` | **FAIL** | Block | SSOT integrity — unauthorized schema changes |
| `relation_integrity` | **FAIL** | Block | Broken FK = runtime constraint violations |
| `orphan_tables` | WARN | Annotate | Manual DB drift or abandoned experiments |

### 3. Safe Fix Boundaries

`--fix` is re-introduced with strict boundaries:

**ALLOWED (safe, reversible, no data mutation):**

- `config:clear` — clear cached config
- `route:clear` — clear cached routes
- `view:clear` — clear cached views

**FORBIDDEN (absolute prohibition, enforced in code + config):**

- DB schema mutation (ALTER/DROP/CREATE TABLE)
- Migration file generation
- Production `.env` modification
- `.env.testing` auto-editing
- Service layer code modification
- Model `$fillable` modification
- Enum class file modification
- Schema SQL file modification

**RULE:** `--fix` is BLOCKED when `--strict` is active. CI pipelines MUST fail-fast, never auto-fix.

### 4. Exit Code Contract

| Condition | Exit Code | Meaning |
|-----------|-----------|---------|
| All checks pass | 0 | GREEN |
| Warnings only (no `--strict`) | 0 | GREEN with annotations |
| Any failure | 1 | RED — pipeline blocked |
| `--strict` + any warning | 1 | RED — strict enforcement |
| `--fix` + `--strict` | 1 | REJECTED — mutually exclusive |

### 5. JSON Output Contract

```json
{
  "guard": "env-drift-guard",
  "contract_version": "3.0.0",
  "mode": "read-only | fix",
  "strict": true,
  "checks": [
    {
      "check": "env_testing",
      "durum": "pass | warn | fail",
      "severity": "fail | warn",
      "mesaj": "description string",
      "fixable": false
    }
  ],
  "ozet": {
    "toplam": 11,
    "basarili": 8,
    "uyari": 2,
    "hata": 1
  },
  "basarili": false,
  "fix_actions": []
}
```

### 6. Gold Line Integration

The guard runs in Gate 5 (tests job) with `--strict --json`:

- stdout → JSON report file (`/tmp/env-drift-report.json`)
- stderr → GitHub Actions annotations (`::error` / `::warning`)
- Any failure → pipeline exit 1
- Report displayed as `::group::` collapsible section

### 7. Config Modification Rules

| Change Type | Safety | Requirement |
|-------------|--------|-------------|
| `warn` → `fail` (tighten) | Safe | No approval needed |
| `fail` → `warn` (loosen) | **Risky** | ADR update + PR approval |
| Add `fix_forbidden` entry | Safe | No approval needed |
| Remove `fix_forbidden` entry | **Risky** | ADR update + PR approval |
| Add new check | Safe | Must define all fields |
| Remove check | **Risky** | ADR update + PR approval |

## Consequences

- CI behavior is fully deterministic — no personal interpretation needed
- `--fix` is safe by design — can never mutate DB, env, or code
- Severity changes require config update, not code change
- Tightening (warn→fail) is safe; loosening (fail→warn) requires ADR review
- JSON output consumers (CI, dashboards) have a stable contract
- Guard versioning (contract_version) enables backward-compat detection

## Alternatives Considered

1. **Keep `--fix` removed entirely** — Rejected: cache clearing is genuinely useful for local dev
2. **Hardcode all policy in PHP** — Rejected: policy changes require code deployment
3. **Make all checks FAIL severity** — Rejected: informational checks (migration parity, enum drift) would break CI unnecessarily before migration is complete
4. **No config file, use .env flags** — Rejected: `.env` is runtime config, guard policy is governance

---

## Addendum: v3.1.0 (2026-04-11) — Enterprise Extension

### New Checks Added

| Check | Severity | Purpose |
|-------|----------|---------|
| `schema_checksum` | **FAIL** | SHA-256 integrity check for mysql-schema.sql. Detects unauthorized modifications. Baseline stored in `.sab/schema-checksum.sha256`. |
| `relation_integrity` | **FAIL** | Validates all FK REFERENCES in schema point to existing tables and columns. Catches broken FKs from renames/drops. |
| `orphan_tables` | WARN | Compares live DB tables against SSOT. Detects manually created tables, test remnants, forgotten experiments. |

### SSOT Protection Strategy

- `schema_checksum` creates a baseline on first run (`.sab/schema-checksum.sha256`)
- Subsequent runs compare file hash against stored baseline
- Intentional schema changes require updating the checksum file
- CI with `--strict` will block any unauthorized SSOT change

### Extensibility Rules

**Safe to add:**
- New read-only checks with defined severity in config
- New `orphan_ignore_tables` entries
- New `enum_checks` definitions

**Requires ADR review:**
- Changing existing check severity from fail → warn
- Adding write operations to `fix_allowed`
- Removing any `fix_forbidden` entry

---

## Addendum: v3.2.0 (2026-04-11) — Policy Lock + Role-Based Override + Token-Based Bypass

### Overview

Guard evolves from a check-and-report tool to a **policy-governed enterprise enforcement engine**:
- Policy config itself is integrity-checked (policy lock)
- Execution context determines enforcement level (role system)
- Controlled override via pre-approved bypass contracts (token-based)
- Full audit trail for all governance events

### New Check

| Check | Severity | Bypassable | Purpose |
|-------|----------|------------|---------|
| `policy_lock` | **FAIL** | No | Config file integrity via SHA-256. Detects unauthorized severity loosening. |

### New CLI Options

| Option | Purpose |
|--------|---------|
| `--role=ci\|local\|emergency` | Set execution role explicitly (auto-detected if omitted) |
| `--bypass-token=BYP-xxx` | Activate a pre-approved bypass contract by ID |
| `--policy-validate` | Validate policy integrity only (lock, completeness, bypass format) |

### Role Enforcement Matrix

| Role | Strict | Fix | Bypass | Auto-detect |
|------|--------|-----|--------|-------------|
| `ci` | implicit | ❌ blocked | ✅ via token only | `GITHUB_ACTIONS`, `CI`, `GITLAB_CI`, `CIRCLECI`, `JENKINS_URL` |
| `local` | opt-in | ✅ allowed | ✅ via token | default (no CI env vars) |
| `emergency` | off | ❌ blocked | ✅ via token | explicit `--role=emergency` only |

### Governance Role Matrix (contract creation, not CLI)

| Role | Permissions |
|------|-------------|
| `developer` | Run guard, get JSON, use local `--fix` |
| `maintainer` | Tighten severity (WARN→FAIL), propose ignore list entries |
| `governance_admin` | Loosen severity (requires ADR), approve bypass contracts, policy unlock |
| `ci_system` | Enforce only, cannot modify policy |

### Token-Based Bypass Contract System

**Contract File:** `storage/governance/env-drift-bypass.json`

**Contract Structure:**
```json
[
  {
    "bypass_id": "BYP-2026-04-10-001",
    "approved_by": "governance_admin",
    "reason": "Legacy enum cleanup scheduled next sprint",
    "checks": ["enum_drift"],
    "scope": "ci_only",
    "expires_at": "2026-04-17T23:59:59Z",
    "created_at": "2026-04-10T14:00:00Z",
    "ticket": "ADR-2026-04-10-enum-drift-bypass"
  }
]
```

**Validation Rules:**
1. Token must exist in contract file
2. Contract must have all required fields (bypass_id, approved_by, reason, checks, expires_at)
3. Contract must not be expired
4. Contract duration must not exceed `max_duration_days` (default: 7)
5. No non-bypassable checks in contract

**Non-Bypassable Checks (NEVER bypassed):**
- `policy_lock`, `env_testing`, `db_connectivity`, `schema_mysql`, `schema_diff`, `schema_checksum`, `relation_integrity`

**Bypassable Checks (governance debt class only):**
- `orphan_tables`, `enum_drift`, `migration_parity`, `fillable_alignment`, `schema_testing`

### Mutual Exclusion Rules

| Combination | Result |
|-------------|--------|
| `--fix` + `--strict` | ❌ exit 1 |
| `--fix` + `--bypass-token` | ❌ exit 1 |
| `--bypass-token` (expired) | ❌ exit 1 |
| `--bypass-token` (non-bypassable check) | ❌ exit 1 |

### Exit Code Contract (v3.2)

| Condition | Exit Code |
|-----------|-----------|
| All checks pass | 0 |
| Warnings only (no `--strict`) | 0 |
| Any failure | 1 |
| `--strict` + any warning | 1 |
| `--fix` + `--strict` | 1 |
| `--fix` + `--bypass-token` | 1 |
| Bypass active, all non-bypassed checks pass | **0** |
| Bypass active, non-bypassable check fails | **1** |
| Bypass token expired | **1** |
| Bypass token not found | **1** |
| `--policy-validate` pass | 0 |
| `--policy-validate` fail | 1 |

### JSON Output Contract (v3.2)

```json
{
  "guard": "env-drift-guard",
  "contract_version": "3.2.0",
  "status": "pass|warn|fail|bypass",
  "role": "local",
  "mode": "read-only",
  "policy": {
    "locked": true,
    "strict": false,
    "override_used": false,
    "bypass_used": true
  },
  "ozet": {
    "toplam": 12,
    "basarili": 8,
    "uyari": 3,
    "hata": 0,
    "bypass": 1
  },
  "bypass": {
    "id": "BYP-2026-04-10-001",
    "checks": ["enum_drift"],
    "approved_by": "governance_admin",
    "reason": "Legacy enum cleanup",
    "expires_at": "2026-04-17T23:59:59Z",
    "ticket": "ADR-2026-04-10-enum-drift-bypass"
  },
  "checks": [],
  "basarili": true
}
```

### Audit Trail

**Audit file:** `storage/governance/env-drift-audit.log`
**Security log channel:** `security`

**Logged events:**
- `BYPASS_USED` — bypass token activated, includes check list and reason
- `BYPASS_TOKEN_NOT_FOUND` — invalid token attempt
- `BYPASS_EXPIRED` — expired token attempt
- `BYPASS_NON_BYPASSABLE_ATTEMPT` — attempt to bypass critical checks

### Policy Lock Strategy

- SHA-256 of `config/env-drift-guard.php` stored in `.sab/policy-lock.sha256`
- First run: records baseline (auto)
- Subsequent runs: compares hash against stored lock
- Config change without lock update = FAIL severity
- Validates: `--policy-validate` checks lock integrity + config completeness

### Governance File Layout

```
config/env-drift-guard.php         # Policy SSOT
.sab/policy-lock.sha256            # Policy integrity lock
.sab/schema-checksum.sha256        # SSOT integrity lock
storage/governance/env-drift-bypass.json   # Bypass contracts
storage/governance/env-drift-audit.log     # Governance audit trail
docs/adr/2026-04-10-env-drift-guard-contract.md  # This ADR
```

### Security Considerations

- Non-bypassable checks prevent bypassing critical system integrity
- Bypass contracts are pre-approved (not created at runtime)
- Token validation is strict: missing fields, expiry, non-bypassable checks all reject
- Audit log is append-only (guard never deletes entries)
- `--fix` in CI role is rejected (prevents auto-remediation in pipeline)
- `--fix` + `--bypass-token` is rejected (bypass mode is read-only)
- Policy lock prevents "quiet loosening" of severity without lockfile update
- All governance events logged to both audit file and security log channel

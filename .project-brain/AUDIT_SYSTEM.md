# Yalıhan OS — Layered Audit System

## Overview

Katmanlı denetim sistemi, mevcut 43+ denetim komutlarını tek bir orkestratör altında organize eder. Amaç, kod tekrarını önlemek, tutarlı JSON rapor çıktısı sağlamak ve CI entegrasyonunu standartlaştırmaktır.

## Architecture

```
yalihan:check (Orchestrator)
├── drift (Schema/Migration/Git/Config)
│   ├── schema    → system:audit-schema-alignment
│   ├── ghost     → model:drift-scan
│   ├── migration → yalihan:drift-audit --checks=missing_migrations
│   ├── git       → yalihan:drift-audit --checks=git_state
│   └── config    → system:env-drift-guard
├── architecture (SAB)
│   └── sab       → sab:guard
├── runtime (Bekçi)
│   ├── health    → governance:health-check
│   └── audit     → bekci:audit
└── domain (Domain-specific)
    ├── security   → guard:security
    ├── tenant     → TenantIsolationSafetyTest
    ├── api        → guard:routes:v2
    └── crm        → crm:drift-scan
```

## NOT Included (Design Decision)

- **Repair/mutation commands** — ayrı tutulur, denetim değil
- **Migration/seed execution** — denetim sistemi salt-okunurdur
- **Bekçi + Drift Sentinel birleşimi** — ayrı katmanlar
- **SAB + production repair** — farklı sorumluluklar

## Commands

### yalihan:check

Main orchestrator command.

```bash
# Run all checks
php artisan yalihan:check

# Run specific layer
php artisan yalihan:check drift
php artisan yalihan:check architecture
php artisan yalihan:check runtime
php artisan yalihan:check domain

# Run specific check
php artisan yalihan:check drift schema
php artisan yalihan:check drift git
php artisan yalihan:check architecture sab

# JSON output for CI
php artisan yalihan:check --json

# Strict mode (warnings = failure)
php artisan yalihan:check --strict

# Save report to file
php artisan yalihan:check --report=reports/audit-$(date +%Y%m%d).json
```

### Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Pass (no blockers) |
| 1 | Fail (blockers found, or --strict with warnings) |
| 2 | System error |

## Report Contract

All audit commands produce reports conforming to `AuditReportContract`.

### JSON Schema

```json
{
  "command": "yalihan:check drift schema",
  "version": "1.0.0",
  "status": "pass|warn|fail",
  "success": true|false,
  "summary": {
    "total_checks": 5,
    "passed": 4,
    "warnings": 1,
    "failures": 0
  },
  "checks": [
    {
      "check": "ghost_tables",
      "status": "pass",
      "label": "REPO_VERIFIED",
      "severity": "info",
      "message": "No ghost tables detected.",
      "finding_count": 0,
      "findings": []
    }
  ],
  "evidence_label": "REPO_VERIFIED",
  "generated_at": "2026-09-03T12:00:00+03:00",
  "git_commit": "abc1234",
  "duration_ms": 150
}
```

### Evidence Labels

| Label | Meaning |
|-------|---------|
| `REPO_VERIFIED` | Code/repo static analysis passed |
| `TEST_VERIFIED` | Automated tests provide evidence |
| `LOCAL_RUNTIME_VERIFIED` | Local SQLite/MySQL runtime verified |
| `PRODUCTION_VERIFIED` | Live production evidence captured |
| `INFERRED` | Conclusion from indirect evidence |
| `BLOCKED_NEEDS_FIX` | Blocker found; cannot proceed |
| `NEEDS_REVIEW` | Human review required |

## Files

### Contracts

- `app/Support/Governance/Audit/Contracts/AuditReportContract.php` — Interface
- `app/Support/Governance/Audit/Contracts/AuditCheckContract.php` — Check interface

### DTOs

- `app/Support/Governance/Audit/DTO/AuditReport.php` — Main report DTO
- `app/Support/Governance/Audit/DTO/AuditCheckResult.php` — Individual check DTO

### Enums

- `app/Support/Governance/Audit/Enums/AuditResult.php` — PASS/FAIL/WARN/SKIP
- `app/Support/Governance/Audit/Enums/AuditSeverity.php` — critical/high/medium/low/info
- `app/Support/Governance/Audit/Enums/EvidenceLabel.php` — Evidence taxonomy

### Services

- `app/Services/Audit/CommonReportFormatter.php` — Output formatting

### Commands

- `app/Console/Commands/YalihanCheckCommand.php` — Main orchestrator

## Migration Notes

### Converting Existing Commands

To conform to the new contract, existing commands should:

1. Use `AuditReport` and `AuditCheckResult` DTOs
2. Return exit codes: 0 = pass, 1 = fail, 2 = system error
3. Support `--json` flag for machine-readable output
4. Include `evidence_label` field in JSON output

### Legacy Command Support

The orchestrator can parse JSON output from legacy commands:

- Pattern A: `{drift_detected: bool, violations: []}`
- Pattern B: `{basarili: bool, ozet: {...}}`
- Pattern C: `{status: 'pass'|'fail', checks: []}`
- Pattern D: `{has_blockers: bool, ...}`
- Pattern E: `{new_violations_count: int, baseline_violations_count: int}`

## CI Integration

### GitHub Actions

```yaml
- name: Run Audit Checks
  run: php artisan yalihan:check --json --strict
  continue-on-error: false

- name: Upload Report
  if: always()
  run: |
    echo '${{ toJson(steps.audit.outputs.report) }}' > audit-report.json
    # Or save to artifacts
```

### GitLab CI

```yaml
audit:
  script:
    - php artisan yalihan:check --json --report=audit-report.json
  artifacts:
    reports:
      junit: audit-report.json
    when: always
```

## Next Steps

1. [ ] Migrate existing commands to use shared DTOs
2. [ ] Add parallel execution support to orchestrator
3. [ ] Implement reviewdog integration for PR comments
4. [ ] Add baseline/compare mode for CI delta detection

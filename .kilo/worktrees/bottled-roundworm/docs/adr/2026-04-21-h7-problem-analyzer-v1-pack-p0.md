# ADR: H7 Problem Analyzer v1 — Pack-P0 Delivery

**Date:** 2026-04-21
**Status:** Pack-P0 Delivered
**Risk:** LOW (read-only, advisory-only)
**Scope:** New read-only governance analyzer, isolated from existing guards

---

## Context

The repository already has multiple independent governance guards (SAB integrity scan, Bekçi wizard contract, Context7 query scanner, OpenClaw agent safety, quality gate). They run as separate pipelines and emit heterogeneous output. During the H2+H3 closure and H4 preflight work on 21 Nisan 2026, two recurring friction points surfaced:

1. Findings are **not classified consistently** across guards — risk level, confidence, layer, and evidence format differ per tool.
2. There is **no single orchestrator** that answers "what are the current governance debts of this repository, in one normalized report."

The need is not a new SSOT. The need is a read-only, advisory-only analyzer that orchestrates discovery across existing guards plus new focused detectors, normalizes the output, and stops short of any fix.

## Decision

Deliver `php artisan governance:analyze` as a thin CLI backed by a Runner + Detector + Reporter composition in `app/Support/Governance/Analyze/`. Pack-P0 ships two detectors that cover the highest-value drift classes observed in recent slices.

### Pack-P0 Deliverables

| Component | Path | Purpose |
|---|---|---|
| CLI | `app/Console/Commands/Governance/GovernanceAnalyzeCommand.php` | Thin, options parsing, delegates to runner |
| Runner | `app/Support/Governance/Analyze/AnalysisRunner.php` | Orchestrates detectors, applies filters |
| Context | `app/Support/Governance/Analyze/AnalysisContext.php` | Immutable run inputs |
| Result | `app/Support/Governance/Analyze/AnalysisResult.php` | Aggregate output |
| Finding | `app/Support/Governance/Analyze/Finding.php` | Immutable advisory record |
| Evidence | `app/Support/Governance/Analyze/Evidence.php` | Per-finding file/line proof |
| Enums | `Enums/{RiskLevel,FindingType,Confidence}.php` | Mechanical classification |
| Contracts | `Contracts/{Detector,Reporter}.php` | Extension points |
| Detector 1 | `Detectors/RouteAuthorityDetector.php` | Duplicate route name authority |
| Detector 2 | `Detectors/Context7ForbiddenFieldDetector.php` | Forbidden field in where clause |
| Reporter 1 | `Reporters/JsonReporter.php` | Machine-readable output |
| Reporter 2 | `Reporters/TableReporter.php` | Human-readable output |
| Tests | `tests/Unit/Governance/Analyze/*Test.php` | 14 pure PHPUnit tests |

### Behavioral Guarantees (ADR-locked)

- **No file mutation.** The command never writes, never migrates, never installs, never stashes.
- **No autofix.** Every `Finding` has `autofix = false` hard-coded in the DTO.
- **Exit code 0 on completed analysis**, regardless of finding count. Exit 1 only on internal analyzer failure.
- **Confidence + evidence are required** on every finding. A bare risk level is not a valid finding.
- **Self-protection.** Detectors skip their own namespace so the analyzer does not flag itself.
- **Noise reduction built in.** Route detector filters leaf-only names (`index`, `show`) and trailing-dot group prefixes (`api.`, `admin.`) to avoid false positives from Laravel route grouping semantics.

### CLI Surface (v1)

```
php artisan governance:analyze                  # default table report
php artisan governance:analyze --format=json    # machine output
php artisan governance:analyze --only=routes    # single detector
php artisan governance:analyze --only=context7
php artisan governance:analyze --risk=high      # severity filter
php artisan governance:analyze --output=path    # write to file
```

Reserved for later packs (accepted but no-op in Pack-P0): `--include-env`, `--baseline`.

### Finding Contract (canonical)

```json
{
    "id": "ROUTE_DUPLICATE_NAME_EXPORT_EXCEL",
    "title": "Duplicate route name authority: export.excel",
    "type": "authority_conflict",
    "risk": "high",
    "confidence": "high",
    "layer": "route",
    "status": "open",
    "summary": "Route name 'export.excel' is declared in 2 locations...",
    "evidence": [
        { "file": "routes/admin.php", "line": 937, "snippet": "..." },
        { "file": "routes/admin/ilanlar.php", "line": 73, "snippet": "..." }
    ],
    "impact": ["runtime behavior ambiguity", "dead authority risk", "governance drift"],
    "safe_action": "Identify the canonical active route via route:list, preserve it, and remove the dead duplicate declarations...",
    "autofix": false,
    "tags": ["routes", "authority", "runtime"],
    "detector": "App\\Support\\Governance\\Analyze\\Detectors\\RouteAuthorityDetector"
}
```

## Consequences

### Positive

- Single normalized report for route authority drift and Context7 field drift — two classes that actually fired during H2+H3 remediation.
- Advisory-only stance preserves the "no second authority" rule: the analyzer suggests, it does not decide.
- Detector + Reporter interfaces give Pack-P1/P2 a stable extension point without Command churn.
- Pure PHPUnit tests (no DB, no framework boot) keep the analyzer testable even while H4 schema authority work is in flight.
- Self-protection rule prevents the analyzer from becoming its own worst finding.

### Negative / Risks

- Regex-based detection has known false-positive classes (dynamic route name composition, array-key fills, raw `DB::table`). Mitigated by v1 filtering rules and documented as Pack-P1/P2 scope.
- Two new Artisan verbs in the `governance:` namespace — adds discoverability cost. Mitigated by consistent ADR and CLI help text.
- Finding volume on first run is high (27 findings on this repo). Not a flaw — surfaces real debt. Risk is that the report is read once and ignored.

### Neutral

- Zero application code touched outside the analyzer namespace.
- Zero schema change, zero config change.
- SAB integrity baseline unchanged (223 → 223), confirming additive-only integration.

## Alternatives Considered

### A. Embed findings into the existing SAB integrity scan

Rejected. SAB is a compliance gate; analyze is advisory. Conflating them would either weaken SAB (by adding advisory noise) or strip advisory nuance (by coercing everything to pass/fail).

### B. Build a separate orchestrator binary outside Laravel

Rejected. All target data (routes, controllers, services) is already reachable via Laravel autoloader and `base_path()`. A separate binary would duplicate bootstrap machinery with zero gain.

### C. Start with five detectors in one pack

Rejected. The planning conversation explicitly phased P0 → P1 → P2. Shipping all detectors at once would trade delivery discipline for surface area. Pack-P0 deliberately picks the two highest-value cases (route authority and Context7 fields) observed in recent H-slices.

### D. Add autofix to Pack-P0 for "safe" cases

Rejected. The governance doctrine from the H2+H3 closure is explicit: advisory tools do not auto-mutate. If the tool applies fixes, it becomes a second authority next to Bekçi and SAB. v1 must not cross that boundary.

## Verification Evidence

- **PHPUnit:** 14 tests pass, 32 assertions, 0 errors, 0 warnings
- **Syntax:** `php -l` clean on all 15 new PHP files
- **SAB integrity:** `PASS: System compliant (with 223 known baseline violations)` — no new violations attributable to the analyzer namespace
- **Smoke run:** `php artisan governance:analyze` produces `total=27 (high=27)` on the current repository state, all findings have full evidence arrays
- **Artisan registration:** `governance:analyze` appears in `php artisan list` under the `governance` group

## Observability Muscle (per Rehabilitation Doctrine)

Per the 21 Nisan doctrine, every slice must leave the system better able to detect its own disfunction. H7 Pack-P0 contributes:

1. **Route authority drift is now visible by command**, not by accidental 500 in production.
2. **Context7 forbidden field usage is enumerable by command**, not only by full SAB runs.
3. **Normalized finding contract becomes a seed schema** for future governance dashboards and CI artifacts.
4. **Self-protection pattern (skip own namespace)** becomes a reusable principle for future detectors, avoiding recursive false positives.

## Pack-P1 / Pack-P2 Preview (not in this ADR scope)

- **Pack-P1:** `OrphanReferenceDetector` (single-reference classes, unused provider bindings), `DeprecatedSurfaceDetector` (`@deprecated` with active callers)
- **Pack-P2:** `EnvironmentBlockerDetector` (distinguishes code-pass / env-blocked), `MarkdownReporter`, real `--baseline` diffing

## References

- `app/Console/Commands/Governance/GovernanceAnalyzeCommand.php`
- `app/Support/Governance/Analyze/` (namespace root)
- `tests/Unit/Governance/Analyze/` (test root)
- 21 Nisan rehabilitation doctrine (session memory)
- `.sab/authority.json` (Context7 canonical field source)
- `docs/adr/2026-04-21-h4-testing-environment-schema-authority.md` (sibling slice)

---

## Pack-T0 Addendum — Coverage Measurement Infrastructure (2026-04-21)

**Status:** CLOSED — CONFIG READY / DRIVER DEFERRED
**Scope:** Measurement infrastructure only. No production code, no test code, no mutation of application logic.

### Context

Pack-P0 delivered 14 passing unit tests with 32 assertions. However, when coverage was measured against the full `app/` include path inherited from `phpunit.xml`, the reported percentage was misleading: the payda was thousands of files irrelevant to the H7 scope. Pack-T1/T2/T3/T4 coverage targets (%3 → %30) only become meaningful if the measurement payda is narrowed to actual H7 sources first. Pack-T0 is that narrowing.

### Decision

Introduce a **separate, scoped coverage configuration** and a **deterministic launcher script**, while leaving `phpunit.xml` unchanged except for a single new testsuite entry.

### Deliverables

| Artifact | Path | Purpose |
|---|---|---|
| Testsuite entry | `phpunit.xml` (`<testsuite name="Governance">`) | Isolated run target |
| Override config | `phpunit.governance.xml` | Scoped `<source>` + coverage reports |
| Launcher | `scripts/governance-coverage.sh` | Preflight + run + deterministic summary |
| Reports dir | `reports/coverage/governance/` | Output container (gitignored except baseline) |
| Gitignore rule | `.gitignore` (H7 section) | HTML/clover/text.txt excluded, baseline.txt tracked |

### Behavioral Guarantees (ADR-locked)

- **Scoped payda.** Coverage include path is `app/Support/Governance/Analyze/` + `app/Console/Commands/Governance/GovernanceAnalyzeCommand.php`. Nothing else.
- **Driver preflight.** Launcher exits with code `2` and a clear message if neither PCOV nor XDebug is installed. No silent zero-coverage runs.
- **Deterministic summary.** Script emits `summary.txt` with generated timestamp, git SHA, config path, test result line, and coverage excerpt. Human copy-paste is no longer part of the loop.
- **Baseline opt-in.** `--save-baseline` flag overwrites `reports/coverage/governance/baseline.txt`. Without the flag, baseline is never silently mutated.
- **Repo hygiene.** Only `.gitkeep` and `baseline.txt` are tracked; HTML, clover, text, and stdout artifacts are local-only.

### Verification Evidence

- `php vendor/bin/phpunit --testsuite=Governance --no-coverage` → **14/14 PASS, 32 assertions** (suite isolation confirmed)
- `bash scripts/governance-coverage.sh` (without driver) → **exit 2 with actionable message** (preflight confirmed)
- `php artisan sab:integrity-scan` → **PASS, 0 new violations** (baseline 223 intact; Pack-T0 added no PHP source)
- `phpunit.governance.xml` source include: exactly 2 paths (directory + file), confirmed
- `.gitignore` H7 block: baseline.txt whitelisted via `!` rule, confirmed

### Deferred (Out of Pack-T0 Scope)

- **Coverage driver installation** (`pecl install pcov` or `pecl install xdebug`) is a system-level mutation that exceeds the repo/config boundary of this ADR. It will be performed as a separate, explicitly approved action before Pack-T1 execution. Until then, `baseline.txt` remains unwritten and the launcher correctly short-circuits on exit 2.

### Consequences

**Positive**
- Future coverage deltas are comparable because the payda is stable.
- Pack-T1/T2/T3/T4 success criteria become empirical, not subjective.
- Analyzer namespace self-containment is preserved (override config touches nothing outside H7).

**Negative / Risks**
- Two phpunit config files now exist (`phpunit.xml` + `phpunit.governance.xml`). Mitigated by scope comment at the top of the override file and this ADR entry.
- Coverage cannot be measured until a driver is installed. Accepted tradeoff: the preflight surfaces this clearly, and the measurement infrastructure is ready when the driver arrives.

### Alternatives Considered

- **A. Modify `phpunit.xml` in place to narrow `<source>` globally.** Rejected: would break coverage for every other suite in the repo.
- **B. Install PCOV as part of Pack-T0.** Rejected: system-level mutation outside the ADR scope. Kept as a separate decision.
- **C. Use `--coverage-filter` CLI flag instead of override config.** Rejected: less durable, less discoverable, and harder for CI to consume.

### Pack-T0 Exit Criteria (all met except noted)

| Criterion | Status |
|---|---|
| Governance suite isolated | ✅ |
| Override config scoped | ✅ |
| Reports directory tracked with gitignore hygiene | ✅ |
| Launcher script executable and deterministic | ✅ |
| Driver preflight validated | ✅ |
| Baseline captured | 🟡 DEFERRED (driver required) |
| ADR addendum recorded | ✅ (this section) |

### Reactivation Path

When a coverage driver is installed on a developer or CI machine:

```bash
bash scripts/governance-coverage.sh --save-baseline
```

This single command completes T0.6 and writes `reports/coverage/governance/baseline.txt`. Pack-T1 planning can then begin with an empirical reference point.

### References (Pack-T0)

- `phpunit.governance.xml`
- `scripts/governance-coverage.sh`
- `reports/coverage/governance/.gitkeep`
- `.gitignore` (H7 block, 2026-04-21)


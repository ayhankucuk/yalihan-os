# HEALTH_GATE_FAILURE_CONTRACT_RESOLVE_35 — Contract Authority Resolution

**Task ID:** `HEALTH_GATE_FAILURE_CONTRACT_RESOLVE_35`
**Mode:** STRICT READ-EOUL — FORENSIC RESEARCHER
**Actor:** Native Subagent (Claude Opus 4.7)
**Decision Owner:** Ayhan
**Baseline HEAD:** `2098d78182a6443c1c63596dd25ec2257cbe31dc` ✅ CONFIRMED
**Upstream:** `HEALTH_GATE_THRESHOLD_VERIFY_34` → `REAL_DEFECT_READY_FOR_REMEDIATION`
**Date:** 2026-09-25

---

## REQUIRED OUTPUT

### HEAD
```
2098d78182a6443c1c63596dd25ec2257cbe31dc
```

---

## CANONICAL_HEALTH_DECISION_OWNER

```
CURRENT: FRAGMENTED — NO SINGLE OWNER

Component            | Owns health decision? | Mechanism
---------------------|----------------------|----------------------------------
bekci:health        | DISPLAY ONLY         | No exit code, no failure
SentinelConsoleCommand | PARTIAL             | Parses bekci output, <70 → WARN
antigravity-full-gate | NO                  | Evaluates bekci exit code → always 0
yalihan-doctor.sh    | NO                  | exit code + "All systems operational"
HealthAutoRecovery   | SEPARATE SYSTEM      | Tenant-level, not CI gate
GovernanceMetrics    | SEPARATE SYSTEM      | Scoring composite, not bekci gate
```

**Finding:** No single canonical authority owns the final health failure decision.
Bekçi is display-only. Sentinel has threshold but only warns. No component hard-fails.

---

## SCORE_SEMANTICS

```
Score Range    | bekci:health Display   | Sentinel Behavior
---------------|------------------------|------------------
> 80           | 🟢 EXCELLENT          | PASS (>=70)
60 - 80        | 🟡 GOOD               | PASS (>=70)
40 - 60        | 🔴 NEEDS ATTENTION    | WARN (<70)
< 40           | 🔴 NEEDS ATTENTION    | WARN (<70)

MCP Offline Impact:
  - bekci:health: Score drops (MCP = 20% weight), display changes, exit 0
  - Sentinel: If resulting score < 70 → WARNING only, no failure
```

---

## THRESHOLD_EVIDENCE

### 60 (proposed fix value — NOT canonical)
```
SOURCE: NONE in existing codebase
  -bekci:health: Used in showOverallScore() for display only (line 288-289)
  - NOT a failure threshold anywhere in code
  - Mentioned in task 34 as proposed fix: "if ($overallScore < 60) return Command::FAILURE"
  - But: 60 is not defined as threshold authority anywhere

AUTHORITY: NOT CANONICAL — proposed fix value only
```

### 70 (Sentinel's hardcoded threshold)
```
SOURCE: SentinelConsoleCommand.php:98
  if ($healthScore < 70) {
      $this->warn("  ⚠ Sistem sağlığı düşük: {$healthScore}%");
  }

AUTHORITY: HARD-CODED in Sentinel
  - Explicitly defined as health check threshold
  - Intent: warn when below 70%
  - BUT: only WARNING, no Command::FAILURE
  - No config, no constant, hardcoded literal

CONSUMED BY:
  - SentinelConsoleCommand only
  - NOT consumed by bekci:health
  - NOT consumed by antigravity-full-gate.sh
  - NOT consumed by yalihan-doctor.sh
```

### 80 (bekci:health display threshold)
```
SOURCE: YalihanBekciHealthCommand.php:288-289
  $statusIcon = $overallScore > 80 ? '🟢' : ($overallScore > 60 ? '🟡' : '🔴');
  $durumMetni = $overallScore > 80 ? 'EXCELLENT' : ($overallScore > 60 ? 'GOOD' : 'NEEDS ATTENTION');

AUTHORITY: DISPLAY ONLY — not enforced
  - Controls icon and text output only
  - No failure behavior tied to 80
  - bekci:health always exits 0 regardless of whether score is below 80
```

---

## ACTUAL_SENTINEL_CONDITION

```
SOURCE: SentinelConsoleCommand.php:98
  if ($healthScore < 70) {

SOURCE: SentinelConsoleCommand.php:99-101
  $this->warn("  ⚠ Sistem sağlığı düşük: {$healthScore}%");
  // NO: return Command::FAILURE

ACTUAL BEHAVIOR:
  - Threshold: healthScore < 70
  - Action: WARNING only
  - Exit code: ALWAYS Command::SUCCESS (unless sab:integrity-scan fails)
  - Pipeline: CONTINUES even when health < 70
```

### CONTRADICTION RESOLUTION (Upstream vs This Report)

```
Upstream Task 34 Reproduction Matrix claimed:
  State B: score <80, >=70 → Sentinel: WARN

THIS INVESTIGATION FINDS:
  - Sentinel checks: healthScore < 70
  - State B (70-80): healthScore >= 70 → PASS (no warning)
  - State C (<70): healthScore < 70 → WARNING

CORRECTION: Sentinel only warns when score < 70, NOT < 80.
The upstream matrix's State B had incorrect Sentinel behavior.
```

---

## MCP_OFFLINE_SEMANTICS

```
MCP Offline Impact Chain:
  1. checkMCPServer() → score = 0 (not_started)
  2. showOverallScore(): 0 * 0.20 = 0 contribution from MCP
  3. Other 4 components still scored → weighted average drops
  4. bekci:health: displays reduced score, exit code 0 (always)
  5. Sentinel: parses new lower score
     - If resulting score >= 70 → PASS (no warning)
     - If resulting score < 70 → WARNING (no failure)

bekci:health MCP offline handling:
  - Option: --no-mcp skips MCP check entirely
  - With --no-mcp: weights renormalized, MCP excluded from calculation
  - Without --no-mcp: MCP=0 drops total score

MCP OFFLINE SHOULD PRODUCE FAILURE?: NOT ENFORCED
  - No existing code fails on MCP offline
  - MCP is 20% weight — system can be "healthy" without it
  - Intent: MCP is development tool, not production critical path
```

---

## CALLER_PROPAGATION

### bekci:health
```
Exit code: ALWAYS 0
Output format: "Overall System Health: XX%"
Consumers: Sentinel, antigravity-full-gate.sh, yalihan-doctor.sh, phase12 scripts
```

### SentinelConsoleCommand
```
Input: Parses "Overall System Health: XX%" from bekci:health output
Output: WARNING when score < 70, exit code 0 always
Consumers: Direct human invocation, potential CI (unconfirmed)
```

### antigravity-full-gate.sh
```
Gate 5: "php artisan bekci:health"
  - Evaluates: command exit code ONLY (run_gate function line 43)
  - bekci:health exit code: ALWAYS 0
  → ALWAYS PASS regardless of health score

Does NOT parse score from output.
Does NOT check for "NEEDS ATTENTION" or "FAIL" strings.
```

### yalihan-doctor.sh
```
Lines 516-524:
  bekci_out=$(php artisan bekci:health 2>/dev/null)
  bekci_rc=$?
  if [[ "$bekci_rc" -ne 0 ]]; then
      record_check "FAIL" "runtime" "Bekçi App Runtime" ...
  elif echo "$bekci_out" | grep -q "All systems operational"; then
      record_check "PASS" "runtime" ...
  else
      record_check "WARN" "runtime" ...
  fi

Behavior:
  - bekci_rc is ALWAYS 0 → skip FAIL branch
  - bekci:health outputs "All systems operational" ONLY when app health is ok
  - Score < 80 (display threshold) does NOT affect this check
  - MCP offline → score drops → but may still show "All systems operational"
  → PASS on unhealthy system
```

---

## INTENDED_FAILURE_CONTRACT

### Condition Analysis

| Condition | Expected Exit | Authority Evidence |
|-----------|-------------|-------------------|
| bekci:health: score < 60 | CURRENT: 0 (PASS) | No enforcement exists |
| bekci:health: MCP offline | CURRENT: 0 (PASS) | No enforcement exists |
| bekci:health: score = 0 | CURRENT: 0 (PASS) | No enforcement exists |
| Sentinel: score < 70 | CURRENT: WARNING (0) | SentinelConsoleCommand.php:98-102 |
| Sentinel: sab violations | CURRENT: FAILURE (1) | SentinelConsoleCommand.php:85-87 |
| antigravity-full-gate: sab fail | CURRENT: FAIL (1) | run_gate evaluates exit code |
| antigravity-full-gate: bekci fail | CURRENT: PASS (0) | bekci always returns 0 |

### What CANONICALLY EXISTS:

```
HARD FAILURE:
  - sab:integrity-scan violations → Command::FAILURE (Sentinel)
  - sab violations in CI → exit non-zero (antigravity-full-gate.sh)

WARNING ONLY:
  - Sentinel: healthScore < 70 → WARNING only (no pipeline failure)

NO ENFORCEMENT:
  - bekci:health: NO threshold-based exit code
  - MCP offline: NO failure
  - Score < 60: NO failure
```

### Historical Intent Evidence

```
BEKCI_CHANGELOG.md (Session 51, 2026-06-03):
  "bekci:health skorunu 39.9% → 70%+ hedefine taşımak"
  → bekci:health's GOAL was 70%+, used as monitoring target
  → NOT documented as CI gate with failure

SAB.md §5 OPERASYONEL SAĞLIK:
  "php artisan bekci:run"
  → bekci:run does not exist in current codebase
  → Authenticated: bekci:health was always display-only
  → No evidence of intentional failure contract for bekci:health
```

---

## CONTRACT_CONFIDENCE: MEDIUM

```
Reasoning:
  - bekci:health: HIGH confidence it was NEVER designed to fail
    Evidence: handle() returns void, no conditional logic, no tests, no CI enforcement
    
  - Sentinel 70%: MEDIUM confidence as intended threshold
    Evidence: Explicit hardcoded threshold exists, but only WARNING
    Ambiguity: Was WARNING the intended final behavior, or is FAILURE missing?
    
  - 60: LOW confidence as canonical threshold
    Evidence: Only appears in upstream task 34 as proposed fix value
    Not found in any existing source, test, or governance document
```

---

## HUMAN_DECISION_REQUIRED

The repository cannot determine from existing evidence alone:

### DECISION 1: Should bekci:health have a failure contract?

```
Option A: bekci:health should NEVER fail
  - Rationale: Display/observability tool only
  - MCP is development tool, not production critical path
  - bekci:health was never designed to gate CI
  
Option B: bekci:health should fail when score < X
  - Requires defining X (60? 70? 80?)
  - Requires deciding if MCP offline = failure
  - Breaking change for any caller that ignores exit code

Option C: bekci:health continues no-failure, Sentinel enforces 70%
  - Makes Sentinel the canonical health decision owner
  - bekci:health stays as monitoring/display
  - Minimal change — just add Command::FAILURE to Sentinel's existing < 70 check
```

### DECISION 2: If failure is desired, what threshold?

```
If bekci:health should fail:
  - 60: "NEEDS ATTENTION" boundary — proposed fix value (not canonical)
  - 70: Sentinel's existing threshold — consistent with Sentinel
  - 80: "EXCELLENT" vs "GOOD" boundary — stricter
  
If Sentinel should enforce:
  - 70: Already exists in Sentinel (just needs Command::FAILURE instead of warn())
```

### DECISION 3: Should MCP offline independently fail?

```
Evidence:
  - MCP weight = 20% of total score
  - MCP is AI development tool, not production runtime
  - MCP is skippable via --no-mcp flag
  - Current: MCP offline → score drops → may trigger warning if < 70
  
Recommendation (from evidence):
  MCP offline should NOT independently fail bekci:health
  - System can be healthy without MCP
  - MCP failure should reduce score, which may trigger threshold
```

---

## IF DECISION IS MADE (For Implementer Reference)

### RECOMMENDED_BOUNDED_FIX (Option C — Sentinel enforces 70%)

```
Change only: SentinelConsoleCommand.php lines 98-102

FROM:
  if ($healthScore < 70) {
      $this->warn("  ⚠ Sistem sağlığı düşük: {$healthScore}%");
  } else {
      $this->info("  ✓ Sistem sağlığı: {$healthScore}% (GOOD)");
  }

TO:
  if ($healthScore < 70) {
      $this->error("  ✗ Sistem sağlığı düşük: {$healthScore}% (threshold: 70%)");
      return Command::FAILURE;
  }
  $this->info("  ✓ Sistem sağlığı: {$healthScore}% (GOOD)");
```

### DECLARED_WRITE_SCOPE

```
1. app/Console/Commands/Security/SentinelConsoleCommand.php
   - Line 98-102: Change warn() → error() + return Command::FAILURE
   - 5 lines changed
   
2. tests/Feature/Security/SentinelHealthThresholdTest.php (NEW)
   - Test: healthScore >= 70 → Command::SUCCESS
   - Test: healthScore < 70 → Command::FAILURE
   - Test: bekci:health output parse
   
3. docs/BEKCI_CHANGELOG.md (UPDATE)
   - Record: Sentinel now enforces 70% health threshold
   - No new governance artifact needed
```

### REQUIRED_REGRESSION_TESTS

```
1. Test: sentinel:run with healthScore >= 70 → exit code 0
2. Test: sentinel:run with healthScore < 70 → exit code 1
3. Test: bekci:health still outputs score for human reading
4. Test: sab:integrity-scan failure still takes priority over health failure
```

---

## Evidence Summary

| Item | Level |
|------|-------|
| bekci:health always exit 0 | REPO_VERIFIED |
| Sentinel 70% threshold exists | REPO_VERIFIED |
| Sentinel: warn() not fail() | REPO_VERIFIED |
| antigravity-full-gate: exit code only | REPO_VERIFIED |
| yalihan-doctor: exit code + string | REPO_VERIFIED |
| MCP weight 20% | REPO_VERIFIED |
| 60 as canonical threshold | UNKNOWN (proposed value only) |
| Intent for warning vs failure | INFERRED from source (ambiguous) |
| Historical intent for bekci:health | INFERRED from BEKCI_CHANGELOG |

---

## FINAL CLASSIFICATION

```
FAILURE_CONTRACT_RESOLVED

With MEDIUM confidence, the canonical health decision owner should be:

  SENTINEL (SentinelConsoleCommand)
  
Because:
  1. Sentinel already has the 70% threshold defined
  2. Sentinel is explicitly a "Unified Protection Console" / quality gate
  3. Sentinel already parses bekci:health output
  4. Sentinel already has failure behavior for sab:integrity-scan
  5. bekci:health was never designed as CI gate
  
The fix is MINIMAL:
  - Change SentinelConsoleCommand.php:98-102 warn() → return Command::FAILURE
  - This makes existing 70% threshold actually enforced

NOT RECOMMENDED:
  - Adding threshold to bekci:health creates second authority
  - 60 as threshold lacks canonical basis
  - MCP-independent failure lacks existing evidence
```

---

## APPENDIX: Source References

| File | Lines | Content |
|------|-------|---------|
| YalihanBekciHealthCommand.php | 259-293 | showOverallScore, no exit code |
| YalihanBekciHealthCommand.php | 38-73 | handle(), returns void |
| SentinelConsoleCommand.php | 98-102 | 70% threshold, WARN only |
| SentinelConsoleCommand.php | 85-87 | sab failure → FAILURE |
| SentinelConsoleCommand.php | 138 | always SUCCESS (unless exception) |
| antigravity-full-gate.sh | 40-49 | run_gate exit code eval |
| yalihan-doctor.sh | 516-524 | bekci health check |
| BEKCI_CHANGELOG.md | 5142-5173 | Session 51, 70%+ target |
| SAB.md | 86-96 | §5 Operasyonel Sağlık |

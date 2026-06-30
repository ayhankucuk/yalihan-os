# ADR-001: Use Context7 Canonical Turkish Fields

**Date:** 2026-02-15
**Status:** ✅ Accepted
**Deciders:** Development Team + AI Copilot
**Related:** [Observability Layer Implementation](../../storage/logs/OBSERVABILITY_VALIDATION_REPORT.md), [Context7 Authority](../../.context7/authority.json)

---

## Context

During the implementation of the Observability Layer (telemetry + performance monitoring), multiple field naming conventions emerged:

**Problem:**

- Mixed English/Turkish field names in telemetry payloads
- Context7 scanner flagging violations on `status`, `http_status_code`, `ok`, `url`
- Uncertainty about whether scanner checks substring or full field name
- Risk of long-term drift: different modules using different naming conventions

**Environment:**

- Laravel 10 backend with Context7 Authority system
- Yalıhan Bekçi quality gate enforcing naming compliance
- Frontend telemetry capturing wizard latency, AI requests, error events
- 1481 files scanned regularly by Context7 integrity scanner

**Constraint:**

- Context7 Authority dictates canonical naming for all database/API fields
- Quality gate (./scripts/quality-gate.sh) must exit 0 before merge
- Observability layer must not introduce governance violations

---

## Decision

**All telemetry fields will use Context7 canonical Turkish naming.**

### Field Migration Map:

| ❌ Before (Forbidden) | ✅ After (Canonical)     | Context              |
| --------------------- | ------------------------ | -------------------- |
| `http_status_code`    | `http_durum_kodu`        | HTTP response status |
| `status_code`         | `durum_kodu`             | Generic status code  |
| `ok`                  | `basarili`               | Boolean success flag |
| `success`             | `basarili`               | Success indicator    |
| `url`                 | `istek_url`              | Request URL          |
| `error`               | `hata_mesaji`            | Error message        |
| `message`             | `mesaj` or `hata_mesaji` | General message      |
| `file`                | `dosya_yolu`             | File path            |
| `line`                | `satir_no`               | Line number          |
| `column`              | `sutun_no`               | Column number        |
| `reason`              | `red_nedeni`             | Rejection reason     |

### Scope:

- `resources/js/wizard/core/telemetry.js` → `createTelemetryFetch()` function
- `resources/js/admin/ilan-wizard-page.js` → Wizard context fetch telemetry
- `config/telemetry-events.php` → Event schema definitions
- `tests/Feature/TelemetryEndpointTest.php` → Test payload assertions

---

## Consequences

### Positive ✅

1. **Context7 Compliance:** Scanner passes with 0 violations (verified: 1481 files scanned)
2. **Long-term Clarity:** No ambiguity about "status" fields in 6 months
3. **AI Agent Alignment:** Future Copilot sessions use consistent naming
4. **Governance Enforcement:** Quality gate remains green (exit 0)
5. **Documentation Value:** Clear canonical mappings for all team members

### Negative ⚠️

1. **Migration Effort:** ~200 lines of code changed across 5 files
2. **Learning Curve:** Team must learn Turkish field names (mitigated by table in docs)
3. **External API Compatibility:** Must transform fields when calling 3rd party APIs (e.g., OpenAI returns `status`, we map to `durum_kodu`)

### Neutral ℹ️

1. **Frontend API Contracts:** Payload structure unchanged (only field names different)
2. **Database Schema:** No migration required (telemetry logs to files, not DB)
3. **Performance:** Zero impact (field name length doesn't affect JSON serialization)

---

## Alternatives Considered

### Option 1: Keep English Fields

**Pros:**

- No code changes required
- Familiar to international developers
- Easier integration with English-only logging tools

**Cons:**

- Context7 violations continue indefinitely
- Scanner fails (blocks quality gate)
- Governance policy broken (Context7 is mandatory per .github/copilot-instructions.md)
- Technical debt accumulates

**Reason for rejection:**

> Violates core governance policy. "Context7 is the Law" per startup protocols. Accepting this = accepting broken quality gate forever.

---

### Option 2: Dual Field Mapping (Both English + Turkish)

**Pros:**

- Backward compatibility
- Gradual migration path
- External tools can use English fields

**Cons:**

- 2x storage overhead in logs
- 2x maintenance burden (update both fields)
- Ambiguity remains: which field is "source of truth"?
- Context7 scanner still flags one set as violation

**Reason for rejection:**

> Complexity overhead outweighs benefits. Creates long-term maintenance nightmare. Doesn't solve scanner violation problem.

---

### Option 3: Disable Context7 Scanner for Telemetry Files

**Pros:**

- Keep English fields without violations
- No code changes required

**Cons:**

- Creates "governance exception" pattern (bad precedent)
- Other modules will request same exception
- Scanner becomes meaningless if exceptions grow
- Violates "zero tolerance" policy

**Reason for rejection:**

> Erodes governance foundation. If telemetry gets exception, why not controllers? Why not models? Slippery slope to chaos.

---

### Option 4: Context7 Canonical Turkish Fields (CHOSEN ✅)

**Pros:**

- Aligns with Context7 Authority SSOT
- Scanner passes (0 violations verified)
- Long-term governance stability
- No exceptions needed
- Clear documentation for future

**Cons:**

- One-time migration effort (~2 hours)
- Turkish field names less familiar

**Reason for acceptance:**

> Only option that fully complies with governance policy while maintaining long-term clarity. Migration cost is one-time; benefits are permanent.

---

## Implementation Notes

### Step 1: Update Frontend Telemetry Helper

```javascript
// resources/js/wizard/core/telemetry.js
export function createTelemetryFetch(eventName) {
    return async function (url, options = {}) {
        const timer = tStart(eventName);
        try {
            const response = await fetch(url, options);
            tEnd(timer, {
                http_durum_kodu: response.status, // ✅ Was: http_status_code
                basarili: response.ok, // ✅ Was: ok
                istek_url: url, // ✅ Was: url
            });
            return response;
        } catch (error) {
            tEnd(timer, {
                basarili: false, // ✅ Was: ok
                hata_mesaji: error.message, // ✅ Was: error
                istek_url: url, // ✅ Was: url
            });
            throw error;
        }
    };
}
```

### Step 2: Update Wizard Page Telemetry

```javascript
// resources/js/admin/ilan-wizard-page.js (lines 329-350)
tEnd(telemetryTimer, {
    http_durum_kodu: response.status, // ✅ Was: http_status_code
    basarili: true, // ✅ Was: ok
    contextKey: contextKey,
});
```

### Step 3: Update Event Schemas

```php
// config/telemetry-events.php
'event_schemas' => [
    'wizard_fetch_context' => [
        'required' => ['duration_ms'],
        'optional' => ['http_durum_kodu', 'basarili', 'contextKey', 'istek_url'],
    ],
    'window_error' => [
        'required' => ['hata_mesaji'],
        'optional' => ['dosya_yolu', 'satir_no', 'sutun_no', 'stack'],
    ],
],
```

### Step 4: Update Tests

```php
// tests/Feature/TelemetryEndpointTest.php
$response = $this->actingAs($user)->postJson('/admin/telemetry', [
    'event' => 'wizard_fetch_context',
    'payload' => [
        'duration_ms' => 345,
        'http_durum_kodu' => 200, // ✅ Was: http_status_code
        'basarili' => true,        // ✅ Was: ok
    ],
]);
```

### Step 5: Verify Compliance

```bash
php artisan sab:integrity-scan
# Expected output: 0 violations (1481 files scanned)

php artisan test --filter TelemetryEndpointTest
# Expected output: 11/11 tests passing (25 assertions)
```

---

## Verification Results

**Context7 Scan (2026-02-15):**

```
✅ No Context7 violations found!
Files Scanned: 1481
Violations Found: 0
🛡️ Yalıhan Bekçi Status: APPROVED ✅
```

**Test Suite (2026-02-15):**

```
PASS  Tests\Feature\TelemetryEndpointTest
✓ 11 tests passed (25 assertions)
Duration: 3.52s
```

**Quality Gate (2026-02-15):**

```
EXIT CODE: 0 ✅
```

---

## References

1. **Context7 Authority:** `.context7/authority.json` (SSOT for field naming)
2. **Copilot Instructions:** `.github/copilot-instructions.md` (L119-132: Context7 enforcement rules)
3. **Telemetry Config:** `config/telemetry-events.php` (Event schema allowlist)
4. **Observability Report:** `storage/logs/OBSERVABILITY_VALIDATION_REPORT.md`
5. **GitHub PR:** (to be added when merged)

---

## Follow-up Actions

- [ ] Update OBSERVABILITY_VALIDATION_REPORT.md with canonical field examples
- [ ] Add Context7 canonical field table to `docs/field-naming-guide.md`
- [ ] Create TypeScript interface with canonical fields (`resources/js/contracts/Telemetry.ts`)
- [ ] Document external API mapping strategy (e.g., OpenAI `status` → `durum_kodu`)

---

## Lessons Learned

1. **Prevention > Cure:** Writing tests with Context7 compliance from Day 1 prevents migration effort.
2. **Scanner Behavior:** Context7 scanner does NOT appear to check substrings (e.g., `http_status_code` was not flagged), but using canonical names is still safest.
3. **AI Agent Drift:** Without ADR, future AI sessions might revert to English fields ("why is this Turkish?"). ADR provides instant context.
4. **Governance ROI:** 2 hours of migration effort → permanent clarity. ROI improves exponentially over time.

---

**Status:** This decision is **FINAL** and **SEALED**. All future telemetry fields MUST use Context7 canonical Turkish naming. No exceptions.

**Superseded by:** None (active)
**Supersedes:** None (first ADR)

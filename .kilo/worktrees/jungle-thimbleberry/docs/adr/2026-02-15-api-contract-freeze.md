# ADR-002: PH-AI-TEMPLATE Contract Freeze + TemplateContextResolver + Telemetry MVP

## Date

2026-02-15

## Status

Accepted (Frozen)

## Context

Property Hub AI Template Generation endpoint had inconsistent HTTP codes, ad-hoc response shapes, and duplicated resolution logic across controller methods. Telemetry had complex per-event schema validation and forbidden-field detection that caused continuous test breakage without clear benefit.

Three problems identified:

1. **HTTP code ambiguity:** "422 mi 404 mü?" döngüsü her test PR'ında tekrar ediyor.
2. **Resolution logic duplication:** Pivot lookup + kategori resolve + normalization controller'da inline.
3. **Telemetry complexity:** Per-event schemas, Context7 forbidden field detection — test maintenance cost > observability benefit.

## Decision

### 1. API Contract Freeze (PH-AI-TEMPLATE HTTP Code Matrix)

Every response from `aiGenerateTemplate` follows this locked contract:

| Scenario           | Code               | HTTP |
| ------------------ | ------------------ | ---- |
| Success            | —                  | 200  |
| Validation failed  | VALIDATION_FAILED  | 422  |
| Pivot not found    | PIVOT_NOT_FOUND    | 422  |
| Data integrity     | DATA_INTEGRITY     | 500  |
| AI provider fail   | AI_PROVIDER_FAILED | 502  |
| Forbidden (future) | FORBIDDEN          | 403  |

**Request:** URL param `templateId` (yayin_tipi_id), body param `alt_kategori_id`.

**Success shape (locked):**

```json
{
    "success": true,
    "data": { "...": "..." },
    "trace_id": "uuid-v4"
}
```

**Error shape (locked):**

```json
{
    "success": false,
    "code": "PIVOT_NOT_FOUND",
    "message": "Human-readable description",
    "trace_id": "uuid-v4"
}
```

Key: Success has NO `code`/`message`. Error has NO `data`.

Schema locked in `contracts/ai-generate-template-v1.json`. Breaking changes require V2.

### 2. TemplateContextResolver (Single Source of Truth)

Extracted into `App\Services\PropertyHub\TemplateContextResolver`:

- `resolve(int $altKategoriId, int $yayinTipiId): TemplateContext`
- Takes both params: `alt_kategori_id` from body, `yayin_tipi_id` from URL
- Validates pivot exists for exact (alt_kategori_id, yayin_tipi_id) pair → PIVOT_NOT_FOUND
- Validates parent kategori exists → DATA_INTEGRITY
- Throws `TemplateResolutionException` with baked-in contract code + HTTP status
- Returns immutable `TemplateContext` DTO with normalized UPS strings

Controller becomes thin: validate → resolve → generate → return.

### 3. Telemetry MVP (6 Core Fields)

Simplified from per-event schema validation to universal core schema:

1. `event` — required string (must be in allowlist)
2. `trace_id` — string (auto-generated if absent)
3. `basarili` — bool
4. `http_durum_kodu` — int
5. `duration_ms` — numeric
6. `context` — free-form object

Removed: per-event schema validation, Context7 forbidden field detection.
Kept: event allowlist (security gate).

## Consequences

### Positive

- HTTP codes are deterministic — tests never break from ambiguous status codes
- Single resolver eliminates duplicated pivot/category lookup logic
- Telemetry tests reduced from 13 complex cases to 12 simple cases
- `trace_id` on every response enables distributed tracing
- Contract JSON enables CI schema validation in the future

### Negative

- Removed Context7 forbidden field detection from telemetry (accepted risk: frontend is trusted code)
- Removed per-event payload validation (accepted risk: free-form context may accumulate drift)
- ApiResponse V2 shape is different from V1 (no existing callers affected)

## Alternatives Considered

1. **Keep per-event schemas + fix each test** — Rejected: maintenance cost exceeds benefit
2. **Use HTTP 400 for all client errors** — Rejected: 422 (validation) vs 404 (not found) distinction is valuable
3. **Keep Context7 forbidden field detection** — Rejected: adds complexity without proportional benefit for trusted frontend code

## Files Changed

- `app/Exceptions/PropertyHub/TemplateResolutionException.php` (new)
- `app/Services/PropertyHub/TemplateContextResolver.php` (new)
- `app/Services/PropertyHub/TemplateContext.php` (new)
- `app/Support/ApiResponse.php` (V2 contract shape)
- `app/Http/Controllers/Api/Concerns/ApiResponds.php` (signature update)
- `app/Http/Controllers/Admin/PropertyHubController.php` (thin aiGenerateTemplate)
- `app/Http/Controllers/Admin/AdminTelemetryController.php` (MVP rewrite)
- `config/telemetry-events.php` (core_schema replaces event_schemas)
- `contracts/ai-generate-template-v1.json` (new)
- `tests/Feature/TelemetryEndpointTest.php` (aligned to MVP)
- `tests/Feature/Admin/PropertyHubControllerTest.php` (AI contract tests added)

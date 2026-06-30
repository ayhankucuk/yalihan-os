# Architectural Decision Records (ADR)

**Purpose:** Document significant architectural decisions to prevent context drift and enable self-protecting governance.

**Status:** Mandatory for L5 (Self-Protecting System) maturity.

---

## Why ADRs?

**Without ADRs:**

- 6 months later: "Why did we do this?" → nobody remembers
- New developers: weeks to understand architecture
- AI agents: drift and make contradictory decisions
- Tech debt: accumulates silently

**With ADRs:**

- Instant answer to "why" questions
- Onboarding: days instead of weeks
- AI agent alignment: consistent with past decisions
- Tech debt: visible and trackable

---

## When to Write an ADR

**Required for:**

- Database schema changes (new tables, column renames)
- API contract changes (new endpoints, breaking changes)
- Framework upgrades (Laravel, Vite, Alpine.js)
- Architecture patterns (new module structure, service extraction)
- Security policies (auth flow changes, permission model)
- Performance optimizations (caching strategy, query optimization)
- Context7 rule additions (new canonical fields)

**Not required for:**

- Bug fixes (unless they reveal architectural issue)
- UI tweaks (color changes, spacing adjustments)
- Content updates (text changes, translations)
- Routine maintenance (dependency updates, log rotation)

---

## ADR Template

```markdown
# ADR-XXX: [Short Decision Title]

**Date:** YYYY-MM-DD
**Status:** [Proposed | Accepted | Deprecated | Superseded by ADR-YYY]
**Deciders:** [Names or roles]
**Related:** [Links to PRs, issues, other ADRs]

---

## Context

[What is the issue we're addressing? What is the current state? What constraints exist?]

Example:

> Multiple field naming conventions (English + Turkish) causing Context7 violations. Scanner fails on `status`, `http_status_code`, `ok` fields. Team debates whether to keep English or fully migrate to Turkish canonical naming.

---

## Decision

[What did we decide? Be clear and specific.]

Example:

> All telemetry fields will use Context7 canonical Turkish naming:
>
> - `http_status_code` → `http_durum_kodu`
> - `ok` / `success` → `basarili`
> - `url` → `istek_url`
> - `error` / `message` → `hata_mesaji`

---

## Consequences

### Positive

- [What benefits does this decision bring?]

### Negative

- [What trade-offs or costs does this decision have?]

### Neutral

- [What changes without clear benefit or cost?]

Example:
**Positive:**

- Context7 scanner passes consistently (0 violations)
- Long-term maintenance clarity (no "status" ambiguity)
- AI agents align with canonical naming

**Negative:**

- Migration effort: ~200 lines of code changed
- Team learning curve: Turkish field names

**Neutral:**

- Frontend API contracts unchanged (payload structure same)

---

## Alternatives Considered

### Option 1: [Alternative approach]

**Pros:** [Benefits]
**Cons:** [Drawbacks]
**Reason for rejection:** [Why we didn't choose this]

### Option 2: [Another alternative]

**Pros:** [Benefits]
**Cons:** [Drawbacks]
**Reason for rejection:** [Why we didn't choose this]

Example:
**Option 1: Keep English Fields**

- Pros: No code changes required
- Cons: Context7 violations continue, scanner fails
- Rejected: Technical debt grows, governance breaks

**Option 2: Dual Field Mapping**

- Pros: Backward compatibility
- Cons: 2x complexity, maintenance nightmare
- Rejected: Complexity overhead outweighs benefits

---

## Implementation Notes

[Step-by-step guidance for implementing this decision. What files need to change? What scripts to run?]

Example:

1. Update `resources/js/wizard/core/telemetry.js` → canonical fields
2. Update `resources/js/admin/ilan-wizard-page.js` → canonical fields
3. Update `config/telemetry-events.php` → schema definitions
4. Run `php artisan sab:integrity-scan` → verify 0 violations
5. Run `php artisan test --filter TelemetryEndpointTest` → verify tests pass

---

## References

- [Link to Context7 Authority](../../.sab/authority.json)
- [Related GitHub Issue #XXX](https://github.com/...)
- [Related PR #XXX](https://github.com/...)
- [External documentation](https://...)
```

---

## Existing ADRs

| #                                                      | Title                                 | Date       | Status      |
| ------------------------------------------------------ | ------------------------------------- | ---------- | ----------- |
| [001](2026-02-15-context7-canonical-turkish-fields.md) | Use Context7 Canonical Turkish Fields | 2026-02-15 | ✅ Accepted |
| [002](2026-02-15-performance-regression-ci-gate.md)    | Performance Regression CI Gate        | 2026-02-15 | ✅ Accepted |

---

## ADR Lifecycle

1. **Proposed:** ADR written, under review
2. **Accepted:** Team approved, implementation in progress
3. **Deprecated:** Decision no longer valid, kept for history
4. **Superseded:** Replaced by newer ADR (link to successor)

---

## Enforcement

**Quality Gate:** ADRs are checked during PR review. Structural changes without ADR reference = PR rejection.

**Automated Check:**

```bash
# .github/workflows/adr-check.yml
if [[ $(git diff --name-only | grep -E 'database|config|app/Http|routes') ]]; then
  if ! grep -q "ADR-" "$PR_BODY"; then
    echo "❌ Structural change detected without ADR reference"
    exit 1
  fi
fi
```

---

## Best Practices

1. **Write ADRs early:** Before implementation, not after.
2. **Be specific:** Avoid vague language like "improve performance."
3. **Document alternatives:** Show you considered trade-offs.
4. **Link everything:** PRs, issues, related ADRs.
5. **Update when superseded:** Don't delete old ADRs, mark as deprecated.
6. **Keep it short:** Target 1 page (300-500 words).
7. **Use examples:** Show concrete code snippets.

---

**Result:** With ADRs, the system becomes **self-documenting** and **drift-resistant**. Future engineers (and AI agents) can understand "why" instantly, not just "what."

# 03_DECISIONS.md — Sprint 4.2

## Architectural Decisions

### Decision 1: Blade Enum Display — Use `->label()` not `->value`

**Context:** `yayin_durumu` is cast as `IlanDurumu` enum in the `Ilan` model.
Blade templates were calling `ucfirst($ilan->yayin_durumu)` which throws `TypeError`
because `ucfirst()` requires `string`, not an enum instance.

**Decision:** Replace with `$ilan->yayin_durumu->label()`.

**Rationale:**
- `IlanDurumu` enum has a `label()` method returning the Turkish display text.
- Using `->label()` is consistent with the rest of the codebase.
- Using `->value` would work as string but gives lowercase value (e.g., 'taslak').
- Using `->label()` gives the display name (e.g., 'Taslak').

**Alternatives Considered:**
- `ucfirst($ilan->yayin_durumu->value)` — works but duplicates logic
- Cast to string in model accessor — over-engineered
- Use view composer to transform — unnecessary indirection

---

### Decision 2: Thin Controller Pattern for OwnerIlanController

**Context:** SAB requires write operations to go through the service layer:
`Controller → Service → IlanCrudService → Repository → DB`

**Decision:** OwnerIlanController follows thin controller pattern:
- Read operations (index, show, edit form): direct model access (acceptable for read-only)
- Write operations (store, update, destroy): delegate to `IlanService`
- `edit()` returns the form view only — no business logic
- `destroy()` delegates to `IlanService->deleteListing()`

**Rationale:** Consistent with existing `store()` implementation.

---

### Decision 3: Ownership Check Pattern

**Context:** All owner routes that access a specific ilan must verify the ilan's `user_id`
matches the authenticated user's ID.

**Decision:** Inline ownership check in each method using `where('user_id', $user->id)->firstOrFail()`.

**Rationale:**
- `firstOrFail()` returns 404 if not found or not owned — correct security signal
- Consistent with existing `show()` method implementation
- Simpler than extracting to a policy for this use case
- No additional service needed for read operations

---

### Decision 4: `readiness()` Route — Stub vs Full Implementation

**Context:** Route `owner.ilanlar.readiness` exists but no view/controller action.

**Decision:** Check if `owner.ilanlar.readiness` view exists.
- If yes: implement `readiness()` action
- If no: implement stub returning 501 or redirect to `show`

**Action:** Verify template existence before coding.

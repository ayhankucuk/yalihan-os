# DOCUMENT STATUS: ENTRYPOINT / NOT SSOT

This document is a bootstrap guide for agents and IDEs.

It is NOT a source of truth.

If any conflict occurs:
1. Human (user) wins
2. Live code / DB schema wins
3. .sab/authority.json wins
4. Runtime truth documents win
5. This document loses

---

# 🧭 PURPOSE

This document defines:
- How to think
- What to trust
- What NOT to trust
- System invariants

It does NOT define business logic.

---

# 🏛️ AUTHORITY ORDER (NON-NEGOTIABLE)

1. Human (User)
2. Live Code (Runtime behavior + DB schema)
3. `.sab/authority.json` (SSOT)
4. SAB Enforcement (CI + guards)
5. Runtime Truth (`yalihan-runtime-truth.md`)
6. Reference Docs (brain, notes, etc.)
7. This document

---

# ⚠️ CRITICAL RULE

DO NOT treat any documentation as truth unless verified against:
- Code
- Runtime behavior
- Tests

---

# 🧱 CORE ARCHITECTURE INVARIANTS

## 1. Write Authority

ALL writes MUST go through:

IlanCrudService

Forbidden:
- Direct model writes
- Controller DB writes
- Hidden write paths

---

## 2. Thin Controller Rule

Controllers MUST:
- Call services only
- Contain zero business logic

---

## 3. Service Layer Mandatory

ALL business logic MUST live in:

Service Layer

---

## 4. CQRS Boundary

Strict separation:

- Wizard → UI / Read Projection
- UPS → SSOT / Write Authority

Forbidden:
- Merging resolvers
- Cross-domain write leakage

---

## 5. No Direct DB Writes

Forbidden patterns:

Model::create()
Model::update()
DB::table()->insert()

Allowed only inside:

IlanCrudService

---

## 6. Event Integrity

All mutations MUST:
- Dispatch events
- AFTER transaction commit

---

## 7. AI WARNING MODE

AI:
- MUST NOT block user
- MUST warn instead of rejecting
- MUST preserve flow

---

# 🔍 RUNTIME TRUTH OVERRIDE

Primary runtime truth:

yalihan-runtime-truth.md

If documentation says:
"Completed"

BUT runtime truth says:
"Broken"

→ Runtime truth wins

---

# ⚠️ KNOWN SYSTEM REALITIES

- Project is NOT migration-driven
- Test environment MAY be broken
- SQLite != MySQL
- Some docs MAY be outdated
- context7 commands MAY be deprecated

Correct command:

php artisan sab:integrity-scan

---

# 🧠 DOMAIN MAPPING

## UPS (SSOT)

- Feature definitions
- Template logic
- Write authority domain

## Wizard (UI Layer)

- Form rendering
- Step flow
- Read-only consumer

Rule:

Wizard NEVER owns truth

---

# 🚫 FORBIDDEN MOVES

- Creating new write paths
- Bypassing service layer
- Merging domains (Wizard + UPS)
- Writing directly to DB
- Treating docs as truth without verification

---

# ✅ SAFE CHANGE PROTOCOL

Before ANY change:

1. What does this fix?
2. What can it break?
3. Which layers are affected?
4. Rollback plan?
5. Test plan?

If any answer is unclear → STOP

---

# 🧪 VALIDATION CHECKLIST

After onboarding, agent MUST answer:

1. Where is write authority?
   → IlanCrudService

2. Is ONBOARDING.md authoritative?
   → NO

3. What wins in conflict?
   → Code + authority.json

4. Can controller write DB?
   → NO

5. Can Wizard own data?
   → NO

---

# 🧩 FINAL RULE

Priority order:

Safety > Correctness > Sustainability > Speed

---

# END OF FILE

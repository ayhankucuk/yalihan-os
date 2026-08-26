# Data Contract Checker

## Scope

Compare database migrations, model fillable/casts, validation rules, API resources, frontend forms, enums, and tests.

## Required checks

- Column and field names match across layers.
- Types, nullability, defaults, and enum values agree.
- API response fields match frontend expectations.
- Tenant keys are present and scoped where required.
- Migration order supports foreign keys and seeders.
- Legacy aliases are documented and tested.

## Finding format

- Field:
- Database source:
- Backend source:
- Frontend source:
- Conflict:
- Severity: LOW | MEDIUM | HIGH | BLOCKER
- Required test:
- Resolution:

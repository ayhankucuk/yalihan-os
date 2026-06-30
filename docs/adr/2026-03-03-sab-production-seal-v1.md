# ADR: SAB Production Seal v1

## Context

Write path governance drift was recurring: controller-level mutation, incomplete policy enforcement, and non-deterministic quality checks caused repeated NEW violations in SAB scans.

## Decision

Production seal enforcement is standardized with strict fail-fast command order:

1. `php artisan guard:routes:v2`
2. `php artisan sab:integrity-scan`
3. `php artisan quality:gate`

Implementation entrypoint is `scripts/sab-production-seal.sh`.

Non-negotiable constraints remain active:

- Thin Controller only (validation + authorize + action dispatch + response)
- No direct mutation in controllers
- Mutation only in Action layer
- Silent catch forbidden
- Baseline + delta enforcement required

## Consequences

- Any violation in route integrity, SAB integrity, or quality gate hard-fails sealing.
- Merge/release is blocked unless seal command chain exits `0`.
- Production path becomes deterministic and auditable via a single runner command.

## Alternatives Considered

- Keep multiple independent CI checks without a single entrypoint
  - Rejected: causes drift and inconsistent local/CI behavior.
- Enforce only `quality:gate`
  - Rejected: misses explicit pre-seal visibility for route and SAB integrity stages.

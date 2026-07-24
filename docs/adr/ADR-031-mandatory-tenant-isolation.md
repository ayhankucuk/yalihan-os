# ADR-031: Tenant Isolation Is Mandatory

## 1. Title
ADR-031: Tenant Isolation Is Mandatory

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Core / Security

## 6. Context
Yalıhan OS operates as a Multi-Tenant SaaS platform. Multiple property management agencies, developers, and consultants use the system to manage distinct portfolios. Cross-tenant data leaks represent a critical business and security risk.

## 7. Problem Statement
Manual query filtering (e.g. adding `->where('tenant_id', ...)` to every controller query) is error-prone. A single missing filter can leak sensitive pricing, lead, or owner data to another tenant.

## 8. Repository Evidence
*   **BelongsToTenant Trait (IMPLEMENTATION):** [BelongsToTenant.php](../../app/Traits/BelongsToTenant.php) | Confidence: HIGH
*   **Tenant Isolation Validation (TEST):** [KomisyonControllerTenantIsolationTest.php](../../tests/Feature/Admin/KomisyonControllerTenantIsolationTest.php) | Confidence: HIGH
*   **Tenant Isolation Safety (TEST):** [TenantIsolationSafetyTest.php](../../tests/Feature/Security/TenantIsolationSafetyTest.php) | Confidence: HIGH

## 9. Decision
We establish mandatory tenant isolation at the database layer. All tenant-scoped models must apply the `BelongsToTenant` trait. This trait enforces a global Eloquent scope filtering all queries by the active tenant ID.

## 10. Architectural Boundaries
*   Eloquent models extending `BaseModel` that contain tenant data must use the `BelongsToTenant` trait.
*   Cross-tenant data queries are strictly forbidden. Any request that attempts to read or write data across tenant lines must throw a security exception.

## 11. Alternatives Considered
### Option 1: Manual Query Scoping
*   **Pros:** Simpler model code.
*   **Cons:** Extremely risky; prone to human error when writing new controllers.
*   **Reason for Rejection:** High security risk of data leaks.

## 12. Consequences
### Positive
*   Guaranteed tenant data isolation on all database queries.
*   Prevents accidental data leakage across tenant lines.
### Negative
*   Requires setting up a global tenant context prior to routing requests.
### Risks
*   Bypassing Eloquent (using raw `DB::table()` queries) can bypass this global scope and lead to tenant leakage.

## 13. Business Automation Impact
Enforces safe multi-tenant execution of background agent runs.

## 14. Tenant Isolation Impact
This is the foundational decision for all tenant data isolation.

## 15. Event and Replay Impact
Replay events must carry the tenant context to ensure that re-runs execute within the correct security boundaries.

## 16. Security Impact
Primary security gate protecting user data.

## 17. Migration or Adoption Plan
Audit all existing database models; any model that represents workspace-level data must incorporate the `BelongsToTenant` trait.

## 18. Validation Criteria
*   Ensure that `TenantIsolationSafetyTest.php` and `KomisyonControllerTenantIsolationTest.php` run and pass.
*   The preflight checks scan controllers and traits for any raw query bypasses.

## 19. Reversal Conditions
Reverting requires removing global scopes and handling tenant filters manually in every query.

## 20. Related ADRs
*   `ADR-022`: Workspace Is the Business Aggregate
*   `ADR-033`: Repository Is the Highest Operational Source of Truth

## 21. Open Questions
*   How should we handle global system administrators who require cross-tenant reports or operational analytics?

# ADR-022: Workspace Is the Business Aggregate

## 1. Title
ADR-022: Workspace Is the Business Aggregate

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
PropertyWorkspace

## 6. Context
In Yalıhan OS, data operations, external webhooks (such as Google Drive folder push notifications), and workforce executions were originally coupled to various disparate models. To establish clean bounded contexts and protect tenant boundaries, a central orchestrating container is required to hold physical assets, bookings, listings, and operational runs.

## 7. Problem Statement
Without a central business aggregate, database models (such as Properties, listings, and ledger entries) lack a single parent context. This causes loose scoping, tenant leakage risks, and makes tracking automation progress (via the Business Automation Index) difficult.

## 8. Repository Evidence
*   **PropertyWorkspace Model (IMPLEMENTATION):** [PropertyWorkspace.php](../../app/Models/PropertyWorkspace.php) | Confidence: HIGH
*   **Tenant Isolation Validation (TEST):** [TenantIsolationSafetyTest.php](../../tests/Feature/Security/TenantIsolationSafetyTest.php) | Confidence: HIGH

## 9. Decision
We establish `PropertyWorkspace` as the canonical Business Aggregate. All domain transactions, listings, executions, and file structures must belong to a Workspace. 

## 10. Architectural Boundaries
*   A Tenant owns multiple Workspaces.
*   A Workspace owns multiple Properties and Listings.
*   Workforce executions must run within the context of a Workspace.

## 11. Alternatives Considered
### Option 1: Direct Tenant Scoping (No Workspaces)
*   **Pros:** Simpler database schema.
*   **Cons:** Prevents granular multi-property workspace organization and isolates operations at too high a level.
*   **Reason for Rejection:** Real estate workflows operate at the property-group or workspace level, not at the entire tenant organization level.

## 12. Consequences
### Positive
*   Provides a clean structural container for files, executions, and assets.
*   Simplifies tenant scope isolation.
### Negative
*   Requires foreign key constraints and model relationships across listings, properties, and executions.
### Risks
*   Tightly coupling folders (Drive integration) to `PortfolioDriveWorkspace` might lead to integration drift if Drive structures shift.

## 13. Business Automation Impact
Organizing files and runs under Workspaces reduces manual folder setups. Automation steps are tracked directly against the active workspace.

## 14. Tenant Isolation Impact
The `BelongsToTenant` trait applies to workspaces, ensuring no workspace data is leaked across tenant lines.

## 15. Event and Replay Impact
Executions within a workspace publish immutable events, allowing replay of failing steps (e.g. folder creation retries).

## 16. Security Impact
Enforces robust workspace-level access checks on CRM and admin controllers.

## 17. Migration or Adoption Plan
Existing listings and properties must be backfilled with `workspace_id` linkages.

## 18. Validation Criteria
*   Unit tests in `TenantIsolationSafetyTest.php` must pass.
*   Workspace database queries must include global tenant scopes.

## 19. Reversal Conditions
Reverting workspace boundaries would require decomposing the database schema and routing all relationships directly to Tenant scopes.

## 20. Related ADRs
*   `ADR-031`: Mandatory Tenant Isolation
*   `ADR-023`: Property Is the Canonical Real-Estate Aggregate

## 21. Open Questions
*   Should legacy integer-based workspaces (`PortfolioDriveWorkspace`) be consolidated under the UUID-based `PropertyWorkspace`?

# ADR-027: Important State Changes Use Immutable Events

## 1. Title
ADR-027: Important State Changes Use Immutable Events

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Hermes / Core

## 6. Context
In Yalıhan OS, tracking listing status transitions, booking updates, and wallet balance changes was historically done by direct database updates. While this updated the model, it left no audit trail of *who* changed the state, *when* it was changed, or *why* a transition occurred.

## 7. Problem Statement
Direct state mutations without event logs make auditing difficult, break replay safety in automation loops, and prevent legal logging compliance (e.g. tracking when a listing was legally unpublished).

## 8. Repository Evidence
*   **ListingStateTransition Model (IMPLEMENTATION):** [ListingStateTransition.php](../../app/Models/ListingStateTransition.php) | Confidence: HIGH
*   **Listing State Machine (IMPLEMENTATION):** [ListingStateMachine.php](../../app/Services/Listing/ListingStateMachine.php) | Confidence: HIGH

## 9. Decision
We mandate that all critical state mutations in the system emit and persist immutable event records. An event record, once written, must never be updated or deleted.

## 10. Architectural Boundaries
*   Critical entities (such as Listings, Workspaces, Ledgers) must log transitions in corresponding history tables (e.g., `listing_state_transitions`).
*   State machine classes (`ListingStateMachine`) must write to these tables as an atomic transaction during state changes.

## 11. Alternatives Considered
### Option 1: Standard Laravel Log Files
*   **Pros:** Easy to write.
*   **Cons:** Files can be deleted, are difficult to query from the database, and cannot be used to trigger database-driven automated retries.
*   **Reason for Rejection:** Fails the structured query and transaction auditing requirements.

## 12. Consequences
### Positive
*   Complete, queryable audit trail for all critical status shifts.
*   Enables replay-safe automation.
### Negative
*   Increases write volumes and database table size.
### Risks
*   Failure to wrap state change and event write in a single database transaction could lead to unsynced model and history states.

## 13. Business Automation Impact
Enables self-healing workflows (such as retrying a failed API channel update by reading the last transition failure event).

## 14. Tenant Isolation Impact
All transition models must include workspace and tenant keys, ensuring audit logs are only queryable within the authorized tenant context.

## 15. Event and Replay Impact
Provides the logical foundation for EIOS replay queues. Replay runs read from the immutable event log.

## 16. Security Impact
Enforces a secure, tamper-proof operational record.

## 17. Migration or Adoption Plan
Refactor existing controllers and CRUD operations to route state changes through state machine service layers.

## 18. Validation Criteria
*   Verify that `ListingStateTransition` records are successfully created when running `ListingLifecycleFinalSealTest.php`.
*   Direct update of listing statuses without transition log entries is blocked in preflight.

## 19. Reversal Conditions
Reverting requires deleting transition tables and writing state modifications directly to models without audit logs.

## 20. Related ADRs
*   `ADR-026`: Listing Is a Publication Representation
*   `ADR-028`: Executions Are Auditable and Replay-Safe

## 21. Open Questions
*   Should we transition to full Event Sourcing for the entire repository, or limit event records to key business transition tables?

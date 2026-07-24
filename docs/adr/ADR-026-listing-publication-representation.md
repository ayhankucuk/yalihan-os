# ADR-026: Listing Is a Publication Representation

## 1. Title
ADR-026: Listing Is a Publication Representation

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Listing

## 6. Context
In Yalıhan OS, listings represent the commercial interface to marketing channels (Airbnb, Sahibinden, Hepsiemlak). Previously, listings were considered the core property models. With the introduction of the `Property` aggregate root, the role of listing must be explicitly defined as a publication target rather than the physical asset.

## 7. Problem Statement
Confusing listings with physical real estate aggregates leads to data duplication when synchronizing with multiple external portals, as each portal listing requires its own status, external IDs, and channel-specific description.

## 8. Repository Evidence
*   **Listing Model (IMPLEMENTATION):** [Ilan.php](../../app/Models/Ilan.php) | Confidence: HIGH
*   **Listing Lifecycle Validation (TEST):** [ListingLifecycleFinalSealTest.php](../../tests/Feature/ListingLifecycle/ListingLifecycleFinalSealTest.php) | Confidence: HIGH

## 9. Decision
We define Listing (`Ilan`) as a publication representation of a Property. A Property can map to multiple Listings. Listing states must reflect publication lifecycles (e.g. `taslak`, `yayinda`, `arsivlendi`).

## 10. Architectural Boundaries
*   A Listing must belong to a `Property` and a `PropertyWorkspace`.
*   Listing pricing (Satılık, Kiralık) is currently coupled in the listing table due to legacy schema constraints, but will transition to `CommercialOffering` in future programs.
*   Listing state transitions must be handled by `YalihanLifecycle` guards.

## 11. Alternatives Considered
### Option 1: Listings as Core Entity
*   **Pros:** Familiar Laravel pattern.
*   **Cons:** Prevents clean multi-channel listing sync and couples physical parameters to channel portals.
*   **Reason for Rejection:** Fails strategic multi-portal distribution requirements.

## 12. Consequences
### Positive
*   Supports portal-specific titles, descriptions, and rules.
*   Allows separate lifecycles for Satılık vs Kiralık options.
### Negative
*   Increases table count and relationship loading overhead.
### Risks
*   Drift in pricing logic until `CommercialOffering` is fully decoupled from Listings.

## 13. Business Automation Impact
Enables automated syndication channels (e.g., automatically posting a listing when a property is certified).

## 14. Tenant Isolation Impact
Listings inherit tenant filters through the global scope applied to their parent Workspace.

## 15. Event and Replay Impact
State changes emit listing events, allowing failed channel pushes to be queued and retried.

## 16. Security Impact
Restricts listing updates to authorized workspace agents.

## 17. Migration or Adoption Plan
Ensure all Listing operations verify property presence via `property_id`.

## 18. Validation Criteria
*   Listing lifecycle feature tests must pass cleanly.
*   Direct database writes to listing state without using `YalihanLifecycle` are blocked by linter rules.

## 19. Reversal Conditions
Reverting requires collapsing Listings and Properties into a single table.

## 20. Related ADRs
*   `ADR-023`: Property Is the Canonical Real-Estate Aggregate
*   `ADR-027`: Important State Changes Use Immutable Events

## 21. Open Questions
*   How should we handle listing descriptions in multiple languages for foreign portals?

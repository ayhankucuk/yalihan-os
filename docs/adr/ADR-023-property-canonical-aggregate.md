# ADR-023: Property Is the Canonical Real-Estate Aggregate

## 1. Title
ADR-023: Property Is the Canonical Real-Estate Aggregate

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Property

## 6. Context
In early Yalıhan OS implementations, physical property parameters (title deeds, coordinates, structural specifications) were directly coupled to listings inside the `ilanlar` database table. This coupling led to duplicate data and inconsistent physical properties when a single villa was listed across multiple portals or had both satılık (for sale) and kiralık (for rent) options.

## 7. Problem Statement
Physical attributes of real estate represent immutable facts (e.g., land parcels, square meters), while listings represent temporal commercial publications. Mixing physical characteristics and commercial listings leads to drift, redundant geocoding queries, and breaks double-entry allocation logic.

## 8. Repository Evidence
*   **Property Model (IMPLEMENTATION):** [Property.php](../../app/Models/Property.php) | Confidence: HIGH
*   **M2 Milestone Report (CERTIFICATION):** [M2-PROPERTY-RUNTIME.md](../../.sab/milestones/M2-PROPERTY-RUNTIME.md) | Confidence: HIGH

## 9. Decision
We establish `Property` as the canonical aggregate root for all physical real estate data. All physical attributes, locations, and title deed structures belong strictly to `Property`.

## 10. Architectural Boundaries
*   A `Property` must belong to a `PropertyWorkspace`.
*   A `Property` owns multiple value objects (`Location`, `TapuInfo`, `PhysicalSpecs`).
*   Listings (`Ilan`) reference `Property` via foreign key `property_id`.

## 11. Alternatives Considered
### Option 1: Legacy Couple inside Ilan Table
*   **Pros:** Fewer database tables.
*   **Cons:** High duplicate fields, inability to track multi-channel sync or separate sales/rental offerings.
*   **Reason for Rejection:** Fails the multi-channel listing requirement and creates transactional data inconsistency.

## 12. Consequences
### Positive
*   Single source of truth for physical attributes.
*   Simplifies geocoding cache and ROI calculations.
### Negative
*   Requires listing controllers to load related property relationships.
### Risks
*   Drift if legacy code bypasses the property layer and writes physical fields directly to Listings.

## 13. Business Automation Impact
Enables automated SEO description generation and automated commission calculations using the physical asset structure.

## 14. Tenant Isolation Impact
The `Property` model extends `BaseModel` and relies on `workspace_id` and global tenant filters to guarantee data isolation.

## 15. Event and Replay Impact
Property creation triggers idempotent events, preventing duplicate geocoding API requests.

## 16. Security Impact
Validates property access permissions at the workspace boundary.

## 17. Migration or Adoption Plan
All legacy listings must be migrated by extracting their physical parameters into new `Property` records and linking them via `property_id`.

## 18. Validation Criteria
*   Sprint 15 test suite results for Property validations must pass.
*   The `M2-PROPERTY-RUNTIME.md` milestone check must return clean compliance.

## 19. Reversal Conditions
Reverting requires merging property attributes back into listings and deleting the `properties` table.

## 20. Related ADRs
*   `ADR-022`: Workspace Is the Business Aggregate
*   `ADR-026`: Listing Is a Publication Representation

## 21. Open Questions
*   Should land plots and commercial units be modeled as different subclasses or details tables under `Property`?

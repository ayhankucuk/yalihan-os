# ADR-030: Monetary Values Avoid Floating-Point Arithmetic

## 1. Title
ADR-030: Monetary Values Avoid Floating-Point Arithmetic

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Finance

## 6. Context
In early Yalıhan OS code, monetary values (payouts, commissions, listing prices) were occasionally handled using PHP floats and stored in standard double or float database columns. This led to floating-point rounding errors during currency conversions or fee calculations.

## 7. Problem Statement
Floating-point math in financial allocations is non-deterministic. A rounding discrepancy of even a fraction of a cent can cause double-entry balancing queries to fail (e.g. debits not matching credits).

## 8. Repository Evidence
*   **Database Schema Decimal Fields (GOVERNANCE):** Migrations define pricing and ledger columns using `decimal(15,2)` or `decimal(19,4)` to enforce precision | Confidence: HIGH
*   **Enterprise Money Tests (TEST):** [EnterpriseMoneyTest.php](../../tests/Feature/Rental/EnterpriseMoneyTest.php) | Confidence: HIGH
*   *Note on Implementation:* Custom Value Object classes such as `Money` or `BasisPoints` do not currently exist in the PHP codebase. All financial values are currently handled using decimal database columns and standard PHP numeric primitives.

## 9. Decision
We establish a strict policy that all monetary values must avoid floating-point database types. Financial database columns must use `decimal(15, 2)` or `decimal(19, 4)`. We propose the future implementation of custom Value Objects (`Money` and `BasisPoints` classes) to encapsulate monetary operations at the application layer.

## 10. Architectural Boundaries
*   All price, fee, tax, and ledger columns in database migrations must use explicit `decimal` schemas.
*   Calculations of splits or discounts must use fixed-point arithmetic or round results to fixed decimal scales prior to writing to the ledger.

## 11. Alternatives Considered
### Option 1: PHP Float Arithmetic
*   **Pros:** Easy to write.
*   **Cons:** Non-deterministic rounding errors that violate ledger constraints.
*   **Reason for Rejection:** Rounding errors break double-entry accounting balances.

## 12. Consequences
### Positive
*   Zero rounding discrepancies in financial logs.
*   Deterministic payouts and ledger postings.
### Negative
*   Requires manual rounding operations in PHP code prior to ledger writes.
### Risks
*   Drift if developer code introduces float castings during allocation splits.

## 13. Business Automation Impact
Ensures that automated payment split systems execute with mathematical certainty.

## 14. Tenant Isolation Impact
Enforces accurate, audit-compliant financial reports for each tenant workspace.

## 15. Event and Replay Impact
Ledger events contain precise decimal string representations, preventing float parsing drift.

## 16. Security Impact
Prevents exploits where rounding exploits could steal fractions of a cent over repetitive runs.

## 17. Migration or Adoption Plan
Ensure all database migrations use `decimal` fields. Introduce custom `Money` and `BasisPoints` value objects in the next refactoring sprint.

## 18. Validation Criteria
*   Double-entry math assertions in `EnterpriseMoneyTest.php` must run without rounding discrepancies.
*   Linter scans block any new migrations containing `float()` or `double()` columns.

## 19. Reversal Conditions
Reverting requires allowing float columns in database schemas.

## 20. Related ADRs
*   `ADR-029`: Finance Uses Append-Only Financial Events

## 21. Open Questions
*   Should we use an integer-based cent model (storing amounts as cents in integer columns) instead of decimal columns?

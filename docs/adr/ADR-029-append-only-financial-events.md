# ADR-029: Finance Uses Append-Only Financial Events

## 1. Title
ADR-029: Finance Uses Append-Only Financial Events

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Finance

## 6. Context
Early system iterations recorded partner payouts and booking deposits by modifying numeric balance columns on the listings or reservations tables. This method failed to preserve a structured audit history of individual financial movements.

## 7. Problem Statement
Updating balance fields directly in database models violates basic accounting standards, makes reconciliation impossible, and prevents auditing of revenue splits between property owners, agencies, and consultants.

## 8. Repository Evidence
*   **Ledger Service (IMPLEMENTATION):** [FinancialLedgerService.php](../../app/Services/FinancialLedgerService.php) | Confidence: HIGH
*   **Ledger Entry Model (IMPLEMENTATION):** [LedgerEntry.php](../../app/Models/Finance/LedgerEntry.php) | Confidence: HIGH
*   **Enterprise Money Tests (TEST):** [EnterpriseMoneyTest.php](../../tests/Feature/Rental/EnterpriseMoneyTest.php) | Confidence: HIGH

## 9. Decision
We mandate a double-entry ledger architecture for all financial transactions. All balance modifications must be recorded as append-only entries in ledger tables. Direct modification of balances is strictly forbidden.

## 10. Architectural Boundaries
*   A `LedgerAccount` (e.g. asset, revenue, liability) belongs to a Workspace.
*   Every financial event must record matching debit and credit entries in `LedgerEntry` records.
*   The sum of all ledger entries for an account represents its current balance.

## 11. Alternatives Considered
### Option 1: Direct Column Mutations (Balances on Listings)
*   **Pros:** Simpler CRUD logic.
*   **Cons:** Non-auditable, prone to race conditions, and violates financial compliance rules.
*   **Reason for Rejection:** Fails double-entry accounting constraints.

## 12. Consequences
### Positive
*   Complete, auditable history of all financial transactions.
*   Accurate calculations of partner commission splits.
### Negative
*   Requires more complex transaction validation logic.
### Risks
*   Database lock contention during high-volume concurrent payouts.

## 13. Business Automation Impact
Enables automated payouts and invoice generation by reading verified ledger states.

## 14. Tenant Isolation Impact
The `FinancialLedgerService` enforces global tenant scoping, ensuring no tenant can read or post to another tenant's ledger accounts.

## 15. Event and Replay Impact
Ledger updates are executed in database transactions, publishing immutable events to ensure synchronization.

## 16. Security Impact
Enforces cryptographic ledger block validation through custom security helpers.

## 17. Migration or Adoption Plan
Legacy transactional models (`FinancialTransaction`) are deprecated and will be refactored to use the new `FinancialLedgerService`.

## 18. Validation Criteria
*   Verify that double-entry math assertions in `EnterpriseMoneyTest.php` pass.
*   Ensure that any ledger update without matching debit/credit entries throws an exception.

## 19. Reversal Conditions
Reverting requires deleting the ledger service and returning to direct column mutations on listing and reservation models.

## 20. Related ADRs
*   `ADR-030`: Monetary Values Avoid Floating-Point Arithmetic
*   `ADR-022`: Workspace Is the Business Aggregate

## 21. Open Questions
*   How should we handle multi-currency ledger balances and exchange rate locks over time?

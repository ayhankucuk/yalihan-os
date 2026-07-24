# ADR-036: SAAB Governs Strategic Architecture

## 1. Title
ADR-036: SAAB Governs Strategic Architecture

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Governance

## 6. Context
During early development phases, different developers and AI agents introduced architectural patterns, design frameworks, and database schema changes without strategic oversight. This led to fragmented structures, duplicate database tables (such as multiple execution models), and inconsistent naming conventions.

## 7. Problem Statement
Without central architectural governance, the monolith degrades, tenant boundaries are threatened, and AI agents face conflicting instructions across duplicate files.

## 8. Repository Evidence
*   **SAAB Constitution (GOVERNANCE):** [SAB.md](../SAB.md) | Confidence: HIGH
*   **Authority Invariants (GOVERNANCE):** [authority.json](../../.sab/authority.json) | Confidence: HIGH
*   **Progress Tracking SSOT (GOVERNANCE):** [PROGRESS-TRACKER.md](../PROGRESS-TRACKER.md) | Confidence: HIGH

## 9. Decision
We establish the Strategic Architecture & Automation Board (SAAB) as the sole governing authority for the system architecture. All architectural changes, database schema modifications, and design patterns require formal SAAB approval.

## 10. Architectural Boundaries
*   Changes to core directory structures require an approved SAAB resolution.
*   The `SAB.md` file serves as the system constitution.
*   Commit pre-conditions are governed by the rule checker (`antigravity-preflight.sh` and `sab:integrity-scan`).

## 11. Alternatives Considered
### Option 1: Ad-hoc Developer Autonomy
*   **Pros:** Fast feature implementation in early stages.
*   **Cons:** Led to severe code drift and 11 blocking AST violations.
*   **Reason for Rejection:** Fails the long-term reliability and SaaS scaling requirement.

## 12. Consequences
### Positive
*   Consistent logical structures across all code.
*   Strict enforcement of tenant boundaries and naming standards.
### Negative
*   Increases the administrative overhead of making database changes.
### Risks
*   Bypassing the preflight checks by changing pre-commit hooks.

## 13. Business Automation Impact
Provides a stable, predictable codebase for autonomous AI orchestrators to operate safely.

## 14. Tenant Isolation Impact
Enforces automatic code scans that block commits attempting to bypass tenant checks.

## 15. Event and Replay Impact
Guarantees that event-logging patterns remain uniform across all services.

## 16. Security Impact
Primary gate preventing unauthorized or untested changes from being deployed to production.

## 17. Migration or Adoption Plan
Align all active development streams to verify the preflight checks on local commit actions.

## 18. Validation Criteria
*   Verify that `antigravity-preflight.sh` returns `PASS` on local changes.
*   The `sab:integrity-scan` command must pass without blocking violations prior to production releases.

## 19. Reversal Conditions
Reverting requires dissolving the SAAB charter and allowing ad-hoc schema changes.

## 20. Related ADRs
*   `ADR-033`: Repository Is the Highest Operational Source of Truth
*   `ADR-037`: Repository Atlas Guides Workspace Rationalization

## 21. Open Questions
*   How should we automate the approval of minor refactorings to prevent board bottlenecks?

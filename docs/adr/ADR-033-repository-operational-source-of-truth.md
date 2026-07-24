# ADR-033: Repository Is the Highest Operational Source of Truth

## 1. Title
ADR-033: Repository Is the Highest Operational Source of Truth

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Governance

## 6. Context
In collaborative AI-human development environments, architectural designs, roadmap goals, and code implementations are written by different authors (developers, product managers, and AI agents). This can lead to discrepancies between what is written in documents (e.g. roadmap timelines, specifications) and what is actually implemented in the running code.

## 7. Problem Statement
Relying on conversation history, stale spreadsheets, or design mockups to determine the state of the platform creates drift. Implementing features based on inaccurate documentation leads to build failures and bugs.

## 8. Repository Evidence
*   **SAAB Core Constitution (GOVERNANCE):** [SAB.md](../SAB.md) Section 2 | Confidence: HIGH
*   **Git Commit History (GOVERNANCE):** Branch commit logs serve as immutable evidence of implemented states | Confidence: HIGH

## 9. Decision
We declare the repository code and tests as the highest operational source of truth. Documentation and roadmap claims must align with, and reference, actual codebase evidence. Speculative claims or designs that are not implemented in code are forbidden in canonical documents.

## 10. Architectural Boundaries
*   Roadmaps must reflect actual implemented milestones.
*   Architecture specifications must distinguish implemented structures from target structures using explicit status tags.
*   Unverified history or designs must be quarantined under "Open Historical Questions".

## 11. Alternatives Considered
### Option 1: Design-First Documentation
*   **Pros:** Easy to write plans ahead of time.
*   **Cons:** Led to massive documentation drift (e.g., claiming Channex was integrated when no code existed).
*   **Reason for Rejection:** Created false assurances and broke quality-gate checks.

## 12. Consequences
### Positive
*   Documentation is 100% accurate and verifiable.
*   Developers and agents start from a consistent reality.
### Negative
*   Requires continuous verification and updates to documents as code changes.
### Risks
*   Failure to run verification tools can allow documentation and code to drift.

## 13. Business Automation Impact
Provides a reliable baseline for AI agents to discover, understand, and modify the system without guessing.

## 14. Tenant Isolation Impact
Prevents security designs from being documented as "active" before they are coded and verified.

## 15. Event and Replay Impact
Ensures that replay logic matches actual database capabilities.

## 16. Security Impact
Reduces vulnerabilities by preventing developers from relying on undocumented security assumptions.

## 17. Migration or Adoption Plan
Analyze existing documentation files and apply status legends (`IMPLEMENTED`, `PARTIAL`, `TARGET`, `LEGACY`).

## 18. Validation Criteria
*   Antigravity preflight check script runs on commits.
*   SAB integrity scans check for architectural consistency.

## 19. Reversal Conditions
Reverting requires allowing documentation to represent future intents as active systems without evidence.

## 20. Related ADRs
*   `ADR-036`: SAAB Governs Strategic Architecture
*   `ADR-037`: Repository Atlas Guides Workspace Rationalization

## 21. Open Questions
*   How should we automate the extraction of repository facts into markdown documentation to prevent manual updates?

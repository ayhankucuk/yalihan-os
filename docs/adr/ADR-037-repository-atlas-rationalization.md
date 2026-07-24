# ADR-037: Repository Atlas Guides Workspace Rationalization

## 1. Title
ADR-037: Repository Atlas Guides Workspace Rationalization

## 2. Status
Proposed

## 3. Date
2026-07-24

## 4. Decision Owners
Strategic Architecture & Automation Board (SAAB)

## 5. Domain
Repository / Governance

## 6. Context
Over several sprints, various AI agents and developer teams created directories and script files in non-standard locations (such as multiple custom MCP configurations under `mcp/` and `mcp-servers/`, diagnostic patches under `patches/`, and older reports under `business-office/`). This fragmented file distribution created cognitive load and increased context windows for AI tools.

## 7. Problem Statement
Without a structural registry of directories, there is no single source of truth defining where specific files (e.g. models, tests, automation scripts) belong, who owns them, or their lifecycle status.

## 8. Repository Evidence
*   **Repository Atlas (DOCUMENTATION):** [REPOSITORY_ATLAS.md](../REPOSITORY_ATLAS.md) | Confidence: HIGH

## 9. Decision
We establish the `Repository Atlas` as the primary guide for workspace directory organization. All directories in the repository must belong to one of the defined layers (Business, Architecture, Repository) and be marked with a status (`CANONICAL`, `ACTIVE`, `LEGACY`, `EXPERIMENTAL`, `ARCHIVE_CANDIDATE`).

## 10. Architectural Boundaries
*   Core application directories (`app/`, `config/`, `database/`, `routes/`) are marked `CANONICAL` Business layers.
*   Strategic documentation resides in `docs/` and `.sab/` (Architecture layers).
*   AI integration tooling, tests, and scripts reside in `.agents/`, `mcp-servers/`, `scripts/`, and `tests/` (Repository layers).

## 11. Alternatives Considered
### Option 1: Ad-hoc Filesystem Layout
*   **Pros:** Requires no documentation.
*   **Cons:** AI tools generate files in incorrect places, leading to build pollution.
*   **Reason for Rejection:** Impedes automated coding efficiency and workspace safety.

## 12. Consequences
### Positive
*   Clear boundaries for directory changes.
*   AI agents can identify obsolete folders (`ARCHIVE_CANDIDATE`) and focus scans on active contexts.
### Negative
*   Requires strict developer discipline to place new files under the designated folders.
### Risks
*   Drift if new folders are created without updating the Repository Atlas.

## 13. Business Automation Impact
Saves developer and AI agent time by defining clear directory targets for new features.

## 14. Tenant Isolation Impact
Isolates tenant configurations strictly under canonical environment directories.

## 15. Event and Replay Impact
Maintains automated tools and shell scripts under safe, centralized repository directories.

## 16. Security Impact
Reduces attack surfaces by identifying and quarantining untested or obsolete directories (`ARCHIVE_CANDIDATE`).

## 17. Migration or Adoption Plan
Review the directories flagged as `ARCHIVE_CANDIDATE` (e.g. `business-office/`) and schedule their rationalization in future sprints.

## 18. Validation Criteria
*   Verify that `REPOSITORY_ATLAS.md` exists and passes markdown preflight checks.
*   All directory paths documented in the Atlas must exist on the filesystem.

## 19. Reversal Conditions
Reverting requires removing directory layout constraints and deleting `REPOSITORY_ATLAS.md`.

## 20. Related ADRs
*   `ADR-033`: Repository Is the Highest Operational Source of Truth
*   `ADR-036`: SAAB Governs Strategic Architecture

## 21. Open Questions
*   When should the files in `business-office/` be formally archived and deleted?

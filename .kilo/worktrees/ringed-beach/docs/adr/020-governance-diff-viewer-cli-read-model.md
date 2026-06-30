# ADR 020: Governance Diff Viewer CLI Read Model

## Status
Accepted

## Context
With the Governance Service (Write-side) producing Draft and Promoted states, operators need visibility into the structural payload changes ("Diff") before making the final publish decision. However, providing a user interface that directly calculates differences or makes publish eligibility decisions violates our Zero-Trust architecture. We must ensure that a "View Diff" interface remains strictly a projection of a CQRS read-model.

## Decision
1. We will establish a Read-Only CLI command `gov:view-diff {entityType} {entityId}` to serve as the initial Diff Viewer for operators.
2. The UI/CLI is strictly prohibited from mutating governance state or writing audits.
3. The CLI must fetch the `DiffProjection` Contract DTO exclusively from the `GovernanceReadServiceInterface`.
4. The `canPublish` eligibility flag will not be calculated by the viewer; it is read directly from the projection, guaranteeing that the True Authority (Governance Transition Guard) is the sole arbiter.
5. Mild telemetry (e.g. execution duration) is included for basic operational validation.

## Consequences
- **Positive:** Safely decouples the Diff generation engine and eligibility logic from any UI component. Sets a golden standard for future Web UI implementation.
- **Negative:** CLI interface requires operators to have terminal access, which is acceptable in the current phase before the Web/Alpine Diff Viewer front-end is constructed.

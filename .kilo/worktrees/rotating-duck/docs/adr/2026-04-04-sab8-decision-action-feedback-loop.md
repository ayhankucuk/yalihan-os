# ADR-SAB8: Decision → Action → Feedback Loop

## Context

SAB1-SAB6 built a full AI decision engine with governance, rollback, explainability, multi-agent orchestration, operator intelligence, and autonomy control. However, the system was a "decision viewer" — it showed findings and let operators approve/reject, but never tracked what happened AFTER a decision was applied. There was no way to know if an approved action actually worked, no impact measurement, and no learning from failures.

## Decision

Implement a closed-loop feedback system (SAB8) that tracks: **Finding → Decision → Action → Result → Learning**.

### Components Added

1. **Database Layer:** 4 new columns on `governance_decisions`: `action_result` (JSON), `impact_score` (smallInteger -100/+100), `action_completed_at` (timestamp), `feedback_note` (string 500).
2. **Model Layer:** `recordResult()`, `addFeedback(note, userId)`, `hasResult()`, `wasSuccessful()`, `getStatusLabel()`, `getStatusColor()`, scopes (`completed`, `successful`, `actionFailed`).
3. **Service Layer:** `ActionFeedbackService` — stats engine with caching, tab filtering, learning signals (3+ consecutive failures in same domain triggers security log), loop summary per decision.
4. **Controller Layer:** 4 new endpoints — `recordResult`, `addFeedback`, `actionDashboard`, `simulateAction`.
5. **UI Layer:** Action Dashboard (6-stat grid, type breakdown, 6 tabs, timeline) + Decision Detail loop flow visual + inline result recording + feedback form + simulate button.

### Security Measures

- All routes under `['web', 'auth', 'verified', 'role:admin', 'sab.write.guard']` middleware group
- POST routes throttled: `throttle:20,1` (record-result, feedback), `throttle:10,1` (simulate)
- `simulateAction` guards against non-pending and already-completed decisions
- `addFeedback` accepts explicit `$userId` parameter (no model-layer `auth()` coupling)

## Consequences

- Operators can now see the full lifecycle of every AI decision
- Impact scoring enables measuring which action types produce positive vs negative outcomes
- Repeated failure detection (3+ in same domain/action) creates automatic security alerts
- Stats cache (5min TTL) prevents dashboard query load on governance_decisions table
- Foundation for SAB9+ to auto-adjust confidence based on historical success rates

## Alternatives Considered

- **Separate `action_results` table:** Rejected — adds JOIN complexity for a 1:1 relationship; columns on existing table are simpler.
- **Event-sourcing pattern:** Rejected — overkill for current scale; timeline JSON column already provides event history.
- **Real-time WebSocket feedback:** Rejected — admin panel doesn't require real-time; polling/refresh is sufficient.

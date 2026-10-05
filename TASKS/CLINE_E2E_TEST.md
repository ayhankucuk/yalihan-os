# Task Contract: CLINE_E2E_TEST

## Objective
Test handoff-publisher.py → Hermes watcher pipeline

## Scope
- Verify handoff-publisher.py writes to correct directory
- Verify Hermes watcher detects and processes artifact
- Verify verification result is written to RESULTS/

## Verification Criteria
1. Artifact appears in HANDOFF/READY/
2. Watcher detects and claims task
3. Hermes verification completes
4. Result written to HANDOFF/RESULTS/

## Expected Outcome
- PASS: All checks succeed
- FAIL: Any check fails
- BLOCKED: Cannot complete verification

## Files Modified by Cline
- .handoff/handoff-publisher.py (pending_dir corrected)

## Authority
- Read: Hermes config, handoff directories
- No Write: System files

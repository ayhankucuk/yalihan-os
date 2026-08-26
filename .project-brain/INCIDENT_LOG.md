# Incident Learning Log

This is an append-only summary. Detailed secrets and raw sensitive logs do not belong here.

## INC-2026-08-26-ADMIN-UI

- Symptom: admin listing page lacked styling; Property Hub returned HTTP 500.
- Evidence: live browser and HTTP checks.
- Root cause found: nginx host `/public` mount can hide image-built `public/build` assets.
- Separate issue: Leaflet reported `L is not defined`.
- Status: root cause identified; code/deploy fix pending.
- Lesson: healthy containers do not prove asset delivery or route health; validate the exact browser/HTTP path.

## INC-2026-08-26-TERMINAL-PASTE

- Symptom: shell reported `command not found` and command-substitution syntax errors.
- Cause: explanatory text, prompts, and command output were pasted into the shell as commands.
- Prevention: commands must be sent as plain text, without prompts or output; run one command block at a time.

## Incident template

### INC-YYYY-MM-DD-NAME

- Symptom:
- Detection:
- Evidence level:
- Scope:
- Root cause:
- Fix:
- Verification:
- Prevention:
- Remaining risk:

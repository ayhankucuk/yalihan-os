# Sprint Template

> Every new sprint is created from this template.

## Usage

```bash
# Copy template to new sprint directory
cp -r docs/sprints/SPRINT_TEMPLATE docs/sprints/SPRINT_X_Y/
```

## Files

| File | Purpose | When |
|------|---------|-------|
| `00_CHARTER.md` | Sprint mission and scope | Define before start |
| `01_CONTEXT.md` | How we got here | Analyze before start |
| `02_TASKS.md` | Task list | Plan before start |
| `03_DECISIONS.md` | Architecture decisions | Document during |
| `04_PROGRESS.md` | Current status | Update during + close |
| `05_TEST_REPORT.md` | Test results | Generate at close |
| `06_CERTIFICATION.md` | DoD checklist | Generate at close |
| `07_HANDOFF.md` | Knowledge transfer | Generate at close |

## Naming Convention

Sprint directories follow the pattern:
```
SPRINT_X_Y/
```

Where:
- `X` = Major version (e.g., 4)
- `Y` = Sprint number (e.g., 3)

Examples:
- `SPRINT_4_2/` — Sprint 4.2
- `SPRINT_4_3/` — Sprint 4.3
- `SPRINT_5_0/` — Sprint 5.0

## Workflow

```
1. Copy template
2. Fill in 00_CHARTER.md
3. Fill in 01_CONTEXT.md
4. Fill in 02_TASKS.md
5. Get SAAB approval
6. Implement
7. Update 04_PROGRESS.md
8. Generate 05_TEST_REPORT.md
9. Generate 06_CERTIFICATION.md
10. Complete 07_HANDOFF.md
11. Push commits
12. Close sprint
```

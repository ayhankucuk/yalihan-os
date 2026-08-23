# YALIHAN OS — Project Context

## Purpose

This file is a navigation and authority index only. It does not duplicate repository knowledge.

## Source-of-Truth Rules

### Runtime Engineering Truth

- Code
- Tests
- Migrations and schema
- Current Git state

Use these to verify what actually exists.

### Governance Truth

- `docs/ysos/CONSTITUTION.md`
- `docs/ysos/ARCHITECTURE_RULES.md`
- Applicable SAAB resolutions

Use these for constitutional and architectural constraints.

### Execution Process

- `docs/ysos/README.md`
- `docs/ysos/AI_AGENT_RULES.md`
- `docs/ysos/CONTEXT_ENGINEERING.md`
- `docs/ysos/OPERATING_SYSTEM.md`
- `docs/ysos/QUALITY_GATES.md`
- `docs/ysos/EVIDENCE_STANDARD.md`
- `docs/ysos/CERTIFICATION_STANDARD.md`
- `docs/ysos/HANDOFF_STANDARD.md`

### Technical Orientation

- `docs/technical/SYSTEM_MAP.md`

This is an orientation document. Verify current claims against code, tests, migrations, and Git.

### Domain Orientation

- `docs/YALIHAN_OS_DOMAIN_MODEL.md`

This is the domain vocabulary and relationship model. Verify implementation claims against code and tests.

### Research and Skill Governance

- `.agents/AGENTS.md`
- `.agents/REGISTRY.md`
- `.agents/skills/`

These define Research Office responsibilities and enterprise skill ownership. They do not replace runtime engineering truth.

### Historical Evidence

- `.sab/proposals/`
- `.sab/sprint-reports/`
- `docs/discovery/`
- `docs/sprint-reports/`
- `docs/ERA_V/`

Use these for lineage and prior decisions. Do not treat them as current runtime state without verification.

## Conflict Resolution

- Runtime truth means what exists in the implementation: code, tests, migrations, schema, and Git evidence.
- Constitutional authority means what is allowed by frozen constitutional and architecture rules.
- YSOS defines how engineering work is executed.
- If `docs/technical/SYSTEM_MAP.md` conflicts with current code, tests, migrations, or Git, verify the runtime implementation against repository evidence and report documentation drift.
- If a historical sprint or report conflicts with a newer approved SAAB resolution, the newer approved resolution governs.
- If conversation or assistant memory conflicts with repository evidence, repository evidence governs.
- If an existing implementation appears to conflict with a frozen constitutional or architecture rule, the implementation's existence must not be interpreted as architectural permission. STOP and escalate to SAAB.

## Required First Checks

```bash
git status --short
git branch --show-current
git rev-parse HEAD
git diff --stat
```

Preserve existing working-tree changes.

## Reading Order

`PROJECT_CONTEXT.md` is the starting router. From it, read:

1. Git state
2. YSOS Constitution
3. YSOS operating and agent rules
4. Quality, evidence, and handoff standards
5. Active sprint or mission evidence
6. Technical system map
7. Domain model
8. Relevant code, tests, and migrations
9. Historical evidence only when needed

## Authority Boundary

- SAAB decides.
- Research Office verifies.
- Engineering Office implements.
- Runtime code, tests, migrations, and Git establish what exists.
- Historical documents explain lineage, not current truth.
- Conversation and assistant memory may provide continuity and useful context, but durable architecture, domain, and engineering decisions must live in repository-governed sources. Conversation memory is context, not repository authority.

## Current Mission

Do not hard-code current branch, sprint, test counts, or working-tree state here. Read them from Git and current repository evidence.

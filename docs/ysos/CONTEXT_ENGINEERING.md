# YSOS — Context Engineering

> How context is structured, loaded, and managed. The repository is the memory.

---

## Core Principle

> **Conversation history is disposable. Repository state is durable.**

AI agents must treat the repository as the single source of truth, not previous conversations.

---

## Context Loading Order

Every AI agent follows this exact loading order:

```
┌─────────────────────────────────────────────────────────────┐
│                   CONTEXT LOADING ORDER                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  LAYER 1: Repository Rules                                  │
│  ├─ CLAUDE.md (if exists)                                   │
│  └─ .clinerules / .cursorrules / .roomodes                │
│                                                              │
│  LAYER 2: YSOS Framework                                    │
│  ├─ docs/ysos/README.md                                    │
│  ├─ docs/ysos/OPERATING_SYSTEM.md                        │
│  └─ docs/ysos/AI_AGENT_RULES.md                           │
│                                                              │
│  LAYER 3: Architecture                                       │
│  ├─ docs/ysos/ARCHITECTURE_RULES.md                      │
│  └─ docs/ysos/QUALITY_GATES.md                           │
│                                                              │
│  LAYER 4: Current Sprint                                     │
│  ├─ docs/sprints/SPRINT_X_Y/00_CHARTER.md                │
│  ├─ docs/sprints/SPRINT_X_Y/01_CONTEXT.md                 │
│  ├─ docs/sprints/SPRINT_X_Y/02_TASKS.md                   │
│  └─ docs/sprints/SPRINT_X_Y/03_DECISIONS.md              │
│                                                              │
│  LAYER 5: Sprint Progress                                    │
│  ├─ docs/sprints/SPRINT_X_Y/04_PROGRESS.md               │
│  └─ docs/sprints/SPRINT_X_Y/07_HANDOFF.md                │
│                                                              │
│  LAYER 6: Project State                                      │
│  ├─ docs/PROGRESS-TRACKER.md                              │
│  └─ docs/ROADMAP.md                                       │
│                                                              │
│  LAYER 7: Implementation                                     │
│  └─ Read relevant code files                               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Context File Types

### 1. Governance Context (Permanent)

| File | Purpose | Lifetime |
|------|---------|----------|
| `docs/SAB.md` | Technical constitution | Forever |
| `docs/ysos/` | YSOS framework | Forever |
| `.sab/authority.json` | Governance SSOT | Forever |

### 2. Sprint Context (Sprint Duration)

| File | Purpose | Lifetime |
|------|---------|----------|
| `docs/sprints/SPRINT_X_Y/00_CHARTER.md` | Sprint mission | Sprint |
| `docs/sprints/SPRINT_X_Y/01_CONTEXT.md` | How we got here | Sprint |
| `docs/sprints/SPRINT_X_Y/02_TASKS.md` | Task list | Sprint |
| `docs/sprints/SPRINT_X_Y/03_DECISIONS.md` | Architecture decisions | Sprint |

### 3. Progress Context (Sprint Duration)

| File | Purpose | Lifetime |
|------|---------|----------|
| `docs/sprints/SPRINT_X_Y/04_PROGRESS.md` | Current status | Sprint |
| `docs/sprints/SPRINT_X_Y/05_TEST_REPORT.md` | Test results | Sprint |
| `docs/sprints/SPRINT_X_Y/06_CERTIFICATION.md` | DoD checklist | Sprint |
| `docs/sprints/SPRINT_X_Y/07_HANDOFF.md` | Knowledge transfer | Sprint |

### 4. Operational Context (Persistent)

| File | Purpose | Lifetime |
|------|---------|----------|
| `docs/PROGRESS-TRACKER.md` | Project status | Persistent |
| `docs/BEKCI_CHANGELOG.md` | Session log | Persistent |
| `docs/ROADMAP.md` | Roadmap | Persistent |
| `docs/known-debt.md` | Technical debt | Persistent |

### 5. Agent Memory (Session)

| File | Purpose | Lifetime |
|------|---------|----------|
| `memory/CHANGELOG_AGENT.md` | Agent changes | Session |
| `memory/SESSION_NOTES.md` | Session notes | Session |
| `memory/LEARNED_PATTERNS.md` | Repeated lessons | Persistent |

---

## Context Update Rules

### When to Update Context

| Trigger | Update |
|---------|--------|
| Sprint initialized | Create sprint docs |
| Architecture decision made | 03_DECISIONS.md |
| Task completed | 04_PROGRESS.md |
| Test failure discovered | 05_TEST_REPORT.md |
| Sprint closed | 04_PROGRESS.md + CHANGELOG |
| Major milestone | ROADMAP.md |

### What Never Goes in Context

- Chat history
- Temporary observations
- Unverified hypotheses
- Personal notes

### What Always Goes in Context

- Verified facts
- Architecture decisions
- Test results
- Progress updates
- Handoff knowledge

---

## Context for Different Agents

### Coding Agent
```
1. YSOS README
2. Current Sprint Charter (00_CHARTER.md)
3. Previous Handoff (07_HANDOFF.md)
4. Implementation-relevant architecture rules
5. Code files
```

### Research Agent
```
1. YSOS README
2. Research question definition
3. Relevant architecture docs
4. Code exploration
5. Findings document
```

### Orchestrator Agent
```
1. YSOS README
2. Current Sprint Progress (04_PROGRESS.md)
3. ROADMAP.md
4. PROGRESS-TRACKER.md
5. Quality Gates status
```

---

## Context File Format

### Sprint Charter Format
```markdown
# Sprint X.Y — Name

## Mission
[One sentence]

## Scope
In: [What's included]
Out: [What's excluded]

## Priority
P0: [Must have]
P1: [Should have]
P2: [Nice to have]

## DoD
- [ ] ...

## Out of Scope
- [ ] ...
```

### Handoff Format
```markdown
# Handoff — Sprint X.Y

## What Was Done
[List]

## What Was Not Done
[List with reasons]

## What the Next Sprint Needs
[Key knowledge transfer]

## Key Files Changed
[File list]

## Commands to Verify
[Verification commands]

## Known Debt
[Issues not fixed]
```

---

## Context Maintenance

### At Sprint Close
- Update 04_PROGRESS.md
- Generate 05_TEST_REPORT.md
- Generate 06_CERTIFICATION.md
- Generate 07_HANDOFF.md
- Update PROGRESS-TRACKER.md
- Update BEKCI_CHANGELOG.md

### Never
- Leave context files stale
- Update context without evidence
- Include chat history references
- Leave "TODO" without owner

---

## Success Definition

Context is successful when:

> A new AI agent can join at any point and understand
> the project state by reading only the repository's
> context files, without any prior conversation.

---

*Context is structured. Memory is in the repository.*

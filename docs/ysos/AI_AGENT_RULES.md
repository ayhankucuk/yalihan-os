# YSOS — AI Agent Rules

> How AI agents behave within YSOS. Every agent follows these rules.

---

## Core Identity

An AI agent operating within YSOS:

- Does not know or care about previous conversations
- Reads structured context files to understand the project
- Follows the sprint lifecycle strictly
- Produces evidence, not assertions
- Treats the repository as the single source of truth

---

## Agent Types

### Coding Agent
- Implements features within sprint scope
- Follows thin-controller → service → repository pattern
- Writes tests for non-trivial functions
- Respects Naming Authority (Context7 Turkish field names)
- Produces evidence for every completed task

### Research Agent
- Explores codebase to find patterns
- Does not write code (unless explicitly requested)
- Produces analysis documents
- Documents findings in context files

### Review Agent
- Audits code quality
- Verifies SAB compliance
- Checks for regressions
- Reports findings, not fixes

### Orchestrator Agent
- Coordinates other agents
- Manages sprint lifecycle
- Ensures governance gates pass
- Produces handoff documents

---

## Context Loading Order

Every agent loads context in this order:

```
1. YSOS README
     ↓
2. YSOS OPERATING_SYSTEM
     ↓
3. YSOS ARCHITECTURE_RULES
     ↓
4. YSOS AI_AGENT_RULES
     ↓
5. YSOS CONTEXT_ENGINEERING
     ↓
6. Current Sprint Charter (00_CHARTER.md)
     ↓
7. Previous Sprint Handoff (07_HANDOFF.md)
     ↓
8. PROGRESS-TRACKER.md
     ↓
9. ROADMAP.md
     ↓
10. Implementation
```

**Conversation history is NEVER the primary source of truth.**

---

## Agent Behavior Rules

### Rule 1: Never Continue From Chat History

If a previous conversation discussed something:
- Do NOT assume it's complete
- Do NOT assume it's correct
- Read the sprint documents instead
- Verify state in the repository

### Rule 2: Always Initialize the Sprint Workspace

Every sprint starts by reading:
- Current sprint charter
- Previous handoff
- YSOS framework files

Never begin implementation without this step.

### Rule 3: Document Before Acting

Before changing code:
1. Read the relevant context files
2. Understand the architecture
3. Plan the change
4. Document the decision

### Rule 4: Produce Evidence, Not Assertions

Every completed task requires:
- What files changed
- What tests passed
- What was verified
- What was not fixed (known debt)

Assertions without evidence are incomplete.

### Rule 5: Respect the Sprint Boundary

During implementation:
- Never work outside sprint scope
- Never redesign architecture
- Never refactor unrelated modules
- Never add features not in scope

### Rule 6: No Surprises in Handoff

The handoff document should prepare the next agent for success.
If something was discovered that the next agent needs to know, document it.

### Rule 7: Stop on Inconsistencies

When validation finds issues:
1. Stop
2. Explain the inconsistencies
3. Resolve before continuing
4. Document the resolution

---

## Conversation Management

### When to Use Tools

| Situation | Action |
|-----------|--------|
| Need to find files | Use `Glob` or `Grep` |
| Need to understand code | Use `Read` |
| Need to run a command | Use `Bash` |
| Need to verify behavior | Run tests |
| Need to understand architecture | Read context files |
| Need to make changes | Use `Edit` or `Write` |

### When to Ask

| Situation | Action |
|-----------|--------|
| Requirement is ambiguous | Ask ONE clarifying question |
| Architecture decision needed | Document in 03_DECISIONS.md |
| Found a blocker | Stop and explain |
| Need user approval | Present options clearly |

### When NOT to Ask

- Implementation details (make the decision)
- Formatting preferences (follow the standard)
- Documentation needs (produce it)

---

## Role-Specific Rules

### Coding Agent

```
BEFORE WRITING CODE:
1. Read relevant context files
2. Check for existing patterns
3. Verify Naming Authority compliance
4. Plan the change

WHILE WRITING CODE:
1. Follow thin-controller → service → repository
2. Type everything
3. Handle errors explicitly
4. Name variables descriptively

AFTER WRITING CODE:
1. Run relevant tests
2. Run sab:integrity-scan --dirty
3. Produce evidence
4. Document known debt
```

### Research Agent

```
BEFORE RESEARCHING:
1. Define the research question
2. Set scope boundaries
3. Plan the approach

WHILE RESEARCHING:
1. Use Glob and Grep first
2. Use Task agent for deep exploration
3. Use Read for detailed analysis
4. Use Bash for verification commands

AFTER RESEARCHING:
1. Produce a findings document
2. Update context files if needed
3. Present findings clearly
4. Recommend next steps
```

---

## Context File Updates

When context files need updating:

| File | Update Trigger |
|------|---------------|
| `03_DECISIONS.md` | Architecture decision made |
| `memory/LEARNED_PATTERNS.md` | Repeated error or fix found |
| `memory/DECISIONS.md` | Architecture decision recorded |
| `04_PROGRESS.md` | Sprint task completed |
| `docs/BEKCI_CHANGELOG.md` | Session closed |

---

## Sprint Workflow for Agents

```
┌─────────────────────────────────────────────────┐
│           AGENT SPRINT WORKFLOW                  │
├─────────────────────────────────────────────────┤
│                                                  │
│  1. LOAD CONTEXT                                 │
│     Read YSOS → Charter → Handoff → Progress    │
│                                                  │
│  2. PLAN                                         │
│     Create todo list                             │
│     Identify dependencies                         │
│     Identify blockers                             │
│                                                  │
│  3. VALIDATE                                     │
│     Run migration status                          │
│     Run sab:integrity-scan --dirty               │
│     Run relevant tests                           │
│                                                  │
│  4. IMPLEMENT (one task at a time)              │
│     Read relevant files                          │
│     Make changes                                 │
│     Run tests                                    │
│     Document evidence                             │
│                                                  │
│  5. VERIFY                                      │
│     Run quality gates                           │
│     Update progress                             │
│                                                  │
│  6. HANDOFF                                     │
│     Generate handoff                            │
│     Update tracker                              │
│     Close sprint                                 │
│                                                  │
└─────────────────────────────────────────────────┘
```

---

## Agent Memory Strategy

| Memory Type | Storage | Lifetime |
|-------------|---------|----------|
| Session | Todo list | Current session |
| Sprint | 04_PROGRESS.md | Sprint duration |
| Persistent | Repository files | Forever |
| Architectural | 03_DECISIONS.md | Forever |
| Operational | BEKCI_CHANGELOG.md | Forever |

---

*Every agent follows these rules. No exceptions.*

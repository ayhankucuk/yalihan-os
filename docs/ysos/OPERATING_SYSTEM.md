# YSOS — Operating System

> How YALIHAN OS is built: the philosophy, architecture, and principles behind YSOS.

---

## The Problem YSOS Solves

When an AI agent joins a project midstream, it faces a critical challenge:
**Context debt.** Without structured context, the agent wastes hours reconstructing what others already know.

Traditional approaches fail:
- Chat history is fragile, searchable only linearly
- README files describe *what* but not *how* or *why*
- Onboarding documents rot quickly
- Architecture decisions live in people's heads

YSOS solves this by treating the **repository itself as the memory**, not the conversation.

---

## The Core Insight

> **AI conversations are disposable. Repository state is durable.**

When you run a sprint in YSOS, every decision, every change, every context lives in the repository. The conversation ends, but the repository carries everything forward.

A new AI agent joining after a 3-month gap should be able to:
1. Read the YSOS documentation
2. Read the current sprint charter
3. Read the previous handoff
4. Start contributing productively in under 30 minutes

---

## Architecture of YSOS

```
                    ┌─────────────────────────────────┐
                    │       YALIHAN OS                │
                    │                                │
                    │  ┌──────────────────────────┐  │
                    │  │   Workspace (Business)  │  │
                    │  │  Portfolio · CRM · Drive │  │
                    │  │  Publishing · Finance    │  │
                    │  │  Reservations · AI       │  │
                    │  └──────────────────────────┘  │
                    │                                │
                    │  ┌──────────────────────────┐  │
                    │  │  Hermes (Orchestrator)   │  │
                    │  │  AI Workforce Pipeline   │  │
                    │  └──────────────────────────┘  │
                    │                                │
                    │  ┌──────────────────────────┐  │
                    │  │  YSOS (Engineering OS)   │  │
                    │  │  SAAB v7 (Governor)      │  │
                    │  └──────────────────────────┘  │
                    └─────────────────────────────────┘
```

---

## YSOS Principles

### Principle 1: Structured Context Over Tribal Knowledge

Context lives in structured files. Not in Slack. Not in Notion. Not in chat history.
In the repository, versioned alongside the code.

### Principle 2: Agent Roles Over General Purpose AI

AI agents operate in specific roles. A coding agent doesn't do strategy.
A strategy agent doesn't write tests. Roles are defined, not assumed.

### Principle 3: Governance By Default

Every decision passes through governance gates automatically.
Code that violates rules is blocked, not warned.

### Principle 4: Sprint = Atomic Work Unit

A sprint is not a timebox. It is an atomic unit of delivery.
It has a clear start, a clear end, and a clear output.
Nothing exists between sprints.

### Principle 5: Evidence Over Assertions

Every completed task produces evidence: what changed, what passed, what was verified.
Assertions without evidence are incomplete.

### Principle 6: Architecture Constraints, Not Suggestions

Architecture rules are not guidelines. They are constraints.
You cannot bypass them. You document exceptions, not violations.

---

## Platform Engineering + Context Engineering + Agent Engineering

YSOS combines three disciplines:

### Platform Engineering
The infrastructure that supports AI development:
- Standardized sprint workspace
- Automated quality gates
- Governance enforcement
- Git-based history

### Context Engineering
How context is structured and loaded:
- Layered context files
- Clear loading order
- No tribal knowledge
- Repository as memory

### Agent Engineering
How AI agents behave:
- Defined roles
- Structured prompts
- Context consumption rules
- Handoff protocols

---

## The YSOS Lifecycle

```
┌─────────────────────────────────────────────────────┐
│                   SPRINT LIFECYCLE                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│   ┌─────────────┐    ┌──────────────────────────┐  │
│   │ INITIALIZE  │───▶│  Architecture Validation │  │
│   └─────────────┘    └────────────┬─────────────┘  │
│                                   │                 │
│                                   ▼                 │
│                         ┌─────────────────────┐    │
│                         │   SAAB APPROVAL     │    │
│                         └─────────┬───────────┘    │
│                                   │                 │
│                                   ▼                 │
│                         ┌─────────────────────┐    │
│                         │   IMPLEMENTATION    │    │
│                         └─────────┬───────────┘    │
│                                   │                 │
│                                   ▼                 │
│                         ┌─────────────────────┐    │
│                         │      TESTING        │    │
│                         └─────────┬───────────┘    │
│                                   │                 │
│                                   ▼                 │
│                         ┌─────────────────────┐    │
│                         │  EVIDENCE COLLECTION│    │
│                         └─────────┬───────────┘    │
│                                   │                 │
│                                   ▼                 │
│                         ┌─────────────────────┐    │
│                         │   CERTIFICATION    │    │
│                         └─────────┬───────────┘    │
│                                   │                 │
│                                   ▼                 │
│                         ┌─────────────────────┐    │
│                         │      HANDOFF       │    │
│                         └─────────┬───────────┘    │
│                                   │                 │
│                                   ▼                 │
│                         ┌─────────────────────┐    │
│                         │       CLOSE         │    │
│                         └─────────┬───────────┘    │
│                                   │                 │
│                                   ▼                 │
│                         ┌─────────────────────┐    │
│                         │   NEXT SPRINT       │    │
│                         └─────────────────────┘    │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## Workspace as Primary Business Aggregate

YSOS officially recognizes **Workspace** as the primary business aggregate.

This means:
- All business logic belongs to a Workspace component
- Cross-Workspace communication goes through Hermes
- Workspace boundaries are respected by all agents

---

## YSOS and Git

YSOS is Git-native:

```
Git Commit = Sprint Artifact
Git Branch = Sprint Isolation
Git Tag    = Milestone
Git Log    = Project History
```

Every sprint produces a git commit with:
- All changed files
- A standardized commit message
- A link to the sprint handoff document

---

## YSOS vs Traditional Methodologies

| Aspect | Agile/Scrum | Kanban | YSOS |
|--------|-------------|--------|------|
| Work unit | Sprint | Flow | Sprint |
| Context | Stories | Cards | Context Files |
| Memory | Team heads | Board | Repository |
| Governance | Manual | Manual | Automated |
| AI support | Partial | None | Native |
| Agent roles | N/A | N/A | Defined |

---

## Success Definition

YSOS succeeds when:

> A new AI agent can join the project at any point and contribute
> productively by reading only the repository's structured context files,
> without any prior conversation.

---

*YSOS — The engineering operating system that treats the repository as memory.*

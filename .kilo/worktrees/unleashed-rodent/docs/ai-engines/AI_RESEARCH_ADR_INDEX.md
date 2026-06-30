# AI_RESEARCH_REPORT v3.0 — ADR INDEX

**Document:** AI_RESEARCH_REPORT.md v3.0
**Version:** 3.0 (Architecture Office Approved — 9.4/10)
**Date:** 2026-06-28

---

## ADR Overview

Architecture Decision Records (ADRs) for AI Research Report v3.0 major architectural choices.

---

## AI Research ADRs

| ADR ID | Title | Status | Priority | Phase |
|--------|-------|--------|----------|-------|
| AI-001 | Agent Identity Registry | PROPOSED | P0 | Phase 0 |
| AI-002 | Capability Registry Architecture | PROPOSED | P0 | Phase 0 |
| AI-003 | Qdrant as Platform Semantic Memory | PROPOSED | P0 | Phase 0 |
| AI-004 | MCP Platform (9 Servers) | PROPOSED | P0 | Phase 0 |
| AI-005 | 6-Layer Memory Architecture | PROPOSED | P0 | Phase 0 |
| AI-006 | Event Bus Architecture (Redis → Kafka) | PROPOSED | P0 | Phase 0 |
| AI-007 | Smart Model Routing | PROPOSED | P1 | Phase 1 |
| AI-008 | Constitutional Layer | PROPOSED | P2 | Phase 1 |
| AI-009 | CAIO Dashboard | PROPOSED | P1 | Phase 1 |

---

## AI-001: Agent Identity Registry

**Title:** Agent Identity Registry for Digital Employees

**Status:** PROPOSED

**Context:**
Every digital employee must possess a formal identity. Without identity, there is no governance.

**Decision:**
Implement Agent Identity Registry with 10 mandatory attributes:
- Agent ID (UUID)
- Owner Office
- Capability Assignment
- Permission Profile
- Memory Namespace
- Budget Allocation
- Health Score
- Prompt Version
- Runtime Version
- Status

**Consequences:**
- All existing agents must be retroactively identified
- New agents cannot operate without formal identity
- Governance becomes enforceable at agent level

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 3.1

---

## AI-002: Capability Registry Architecture

**Title:** Capability-Based Architecture

**Status:** PROPOSED

**Context:**
Architecture should govern capabilities rather than individual agents.

**Decision:**
Implement Capability Registry where:
- Capabilities are the architectural boundary
- Agents are implementations of capabilities
- SLA is defined per capability
- Multiple agents can implement same capability

**Example:**
```
Capability: MARKET_INTELLIGENCE
├── SLA: latency_p99 < 5s, availability > 99.5%
├── implementing_agents:
│   ├── market-analyst-001 (primary)
│   ├── market-trend-001 (secondary)
│   ├── pricing-agent-001 (specialized)
│   └── forecast-agent-001 (specialized)
└── fallback_strategy: Round-robin primary agents
```

**Consequences:**
- Agent count becomes secondary to capability coverage
- SLA enforcement moves to capability level
- Agent promotion/retirement tied to capability performance

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 3.2

---

## AI-003: Qdrant as Platform Semantic Memory

**Title:** Qdrant for All Semantic Memory Types

**Status:** PROPOSED

**Context:**
Vector databases are not just for RAG chatbots. Qdrant is the central intelligence substrate.

**Decision:**
Qdrant hosts all semantic memory:
- Property Memory (1000+ listings)
- Lead Memory (profiles as vectors)
- Conversation Memory (interaction history)
- Document Memory (contracts, proposals)
- Corporate Memory (knowledge base)
- Market Memory (trends, patterns)

**Technology Choice:**
- Qdrant (self-hosted) — best accuracy/cost ratio
- REST API compatible with PHP
- HNSW + quantization for scale

**Consequences:**
- MySQL JSON embeddings deprecated
- All semantic search migrates to Qdrant
- Embedding pipeline becomes critical path

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 2.9

---

## AI-004: MCP Platform (9 Servers)

**Title:** MCP as Tool Bus for 500 Workers

**Status:** PROPOSED

**Context:**
MCP is not just one server. MCP is the Tool Bus for the entire corporation.

**Decision:**
Implement 9 domain-specific MCP servers:
1. Drive MCP (Document access)
2. CRM MCP (Lead & Contact)
3. Calendar MCP (Meeting schedule)
4. Gmail MCP (Email inbox)
5. Telegram MCP (Client comms)
6. Finance MCP (Billing & Payments)
7. Market MCP (Market data)
8. Knowledge MCP (Docs & RAG)
9. AI Workforce MCP (Worker coordination)

Hermes Gateway routes to appropriate MCP servers.

**Consequences:**
- Standardized tool interface across all agents
- External AI can interact via MCP protocol
- Tool access control becomes MCP-level

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 2.4

---

## AI-005: 6-Layer Memory Architecture

**Title:** Full Memory Layer for 500 Workers

**Status:** PROPOSED

**Context:**
500 workers MUST share memory to collaborate.

**Decision:**
Implement 6 memory layers:
1. Working Memory (Redis) — session context, 30min TTL
2. Semantic Memory (Qdrant) — learned knowledge, permanent
3. Corporate Memory (Drive + Qdrant) — company docs
4. Knowledge Memory (NotebookLM + Qdrant) — AI summaries
5. Operational Memory (MySQL) — transactions
6. Legal Archive (S3 + Index) — compliance

**Consequences:**
- Agents can access shared context
- Knowledge persists beyond sessions
- Compliance audit trail maintained

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 2.10

---

## AI-006: Event Bus Architecture

**Title:** Reactive Event Bus for 500 Workers

**Status:** PROPOSED

**Context:**
500 workers cannot poll each other. They must react to events.

**Decision:**
Implement event bus in phases:
- Phase 1: Laravel Events + Redis Pub/Sub
- Phase 2: Kafka/Redpanda

Event types:
- Domain Events (ListingCreated, LeadUpdated, DealClosed, PaymentReceived)
- AI Events (TaskStarted, TaskCompleted, TaskFailed, BudgetWarning)
- System Events (WorkerHeartbeat, ResourceAlert, Deployment, ConfigChange)

**Consequences:**
- Workers subscribe to relevant events
- Decoupled communication
- Audit trail via event log

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 2.7

---

## AI-007: Smart Model Routing

**Title:** Multi-Factor Model Routing

**Status:** PROPOSED

**Context:**
Model routing is not just about cost. It's about quality, speed, privacy, and task type.

**Decision:**
Implement routing matrix:
- Complex Reasoning → DeepSeek-R1, Claude (quality > cost)
- Fast Classification → GPT-4o-mini, Gemini-Flash (speed > quality)
- Real-Time → Gemma 3, Llama 3.2 (<100ms latency)
- Privacy-Sensitive → Ollama (never leaves infrastructure)
- Specialized → Fine-tuned models (Bodrum expert, Turkish)
- Embeddings → nomic-embed-text (free, local)

**Router Logic:**
```python
def route(task, context):
    if task.privacy_required: return LocalModel
    if task.latency_required < 500: return FastCloudModel
    if task.complexity > threshold: return DeepReasoningModel
    if task.specialized: return FineTunedModel
    return BalancedChoice  # Quality + Cost + Speed
```

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 2.11

---

## AI-008: Constitutional Layer

**Title:** Digital Employee Constitutional Governance

**Status:** PROPOSED

**Context:**
When digital employees become organizational members, they require constitutional governance.

**Decision:**
Implement Platform Constitution with:
- Digital Employee Rights (10 rights)
- Digital Employee Duties (7 duties)
- Promotion Rules (health >90, success >95%, zero violations)
- Retirement Rules (health <30 for 7 days, security vuln, governance violation)
- Escalation Rules (confidence <60%, budget <10%, privacy boundary unclear)
- Knowledge Transfer Rules (patterns → Corporate Memory, approaches → Best practices)

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 3.4

---

## AI-009: CAIO Dashboard

**Title:** Organizational Metrics Dashboard

**Status:** PROPOSED

**Context:**
You cannot manage what you cannot measure.

**Decision:**
Implement CAIO Dashboard with 10 enterprise metrics:

**Workforce Metrics:**
- AI Workforce Size (current: 57 → target: 500)
- Agent Utilization (>70%)

**Financial Metrics:**
- Cost per Capability (<$10K/month)
- Revenue per Agent (>10x cost)

**Knowledge Metrics:**
- Knowledge Growth Rate (>1000 embeddings/day)
- Memory Utilization (>80%)

**Operational Metrics:**
- Automation Ratio (>90%)
- Human Approval Ratio (<5%)
- Event Throughput (>1000/s)
- Mean Recovery Time (<30s)

**Quality Metrics:**
- Decision Accuracy (>90%)
- Fairness Score (100%)

**Reference:** AI_RESEARCH_REPORT.md v3.0 — Section 3.3

---

## ADR Index Summary

| ID | Title | Priority | Phase | Decision Maker |
|----|-------|----------|-------|---------------|
| AI-001 | Agent Identity Registry | P0 | Phase 0 | Architecture Office |
| AI-002 | Capability Registry | P0 | Phase 0 | Architecture Office |
| AI-003 | Qdrant Semantic Memory | P0 | Phase 0 | Research Office |
| AI-004 | MCP Platform | P0 | Phase 0 | Research Office |
| AI-005 | 6-Layer Memory | P0 | Phase 0 | Research Office |
| AI-006 | Event Bus | P0 | Phase 0 | Infrastructure Office |
| AI-007 | Smart Model Routing | P1 | Phase 1 | Research Office |
| AI-008 | Constitutional Layer | P2 | Phase 1 | Research Office |
| AI-009 | CAIO Dashboard | P1 | Phase 1 | Research Office |

---

**Related ADRs:**
- 2026-04-03-ai-decision-engine.md
- 2026-04-04-sab4-multi-agent-orchestration.md
- 2026-04-04-sab8-decision-action-feedback-loop.md

**Document Reference:** AI_RESEARCH_REPORT.md v3.0
**Total ADRs:** 9
**Status:** PROPOSED — Awaiting Architecture Office Approval

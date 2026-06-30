# YALIHAN OS — Integration Blueprint

> **Version:** 1.0.0  
> **Office:** Integration Office (SAAB v6)  
> **Date:** 2026-06-28  
> **Status:** APPROVED

---

## 1. Vision & Principles

### Vision
YALIHAN OS operates as a **nerve center** — all systems connect through a unified integration fabric that enables real-time data flow between internal AI services, productivity tools, messaging platforms, and external marketplace APIs. Every integration is designed for **resilience**, **observability**, and **tenant isolation**.

### Core Principles

| Principle | Description |
|-----------|-------------|
| **Zero Trust** | Every service authenticates; no implicit trust between internal services |
| **Event-Driven First** | Systems communicate via events, not synchronous calls where possible |
| **Tenant Isolation** | All data flows respect tenant boundaries; cross-tenant access is forbidden |
| **Fail Safe** | Integrations degrade gracefully; downstream failures don't cascade |
| **Observability** | Every integration point emits structured logs, metrics, and traces |

---

## 2. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                        EXTERNAL SYSTEMS                             │
│  Google Workspace │ Telegram │ WhatsApp │ Airbnb │ Sahibinden      │
│  Hepsiemlak      │ n8n      │ OpenClaw │ CRM    │ Finance          │
└────────────┬──────────────────────────────────┬────────────────────┘
             │                                  │
        ┌────▼────┐                    ┌────────▼────────┐
        │  API    │                    │   EVENT BUS     │
        │ GATEWAY │                    │  (Hermes)       │
        └────┬────┘                    └────────┬────────┘
             │                                  │
     ┌────────▼──────────────────────────────────▼────────┐
     │                  MCP PLATFORM                       │
     │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌───────┐ │
     │  │Google   │  │Telegram │  │WhatsApp │  │Market │ │
     │  │Workspace│  │ MCP     │  │ MCP     │  │places │ │
     │  └─────────┘  └─────────┘  └─────────┘  └───────┘ │
     └─────────────────────────────────────────────────────┘
             │
     ┌────────▼────────────────────────────────────────────┐
     │              INTERNAL SERVICES                        │
     │  YalihanCortex  │  AI Workforce  │  Vector DB      │
     │  IlanCrudService │  Tenant Service │  Audit Service │
     └─────────────────────────────────────────────────────┘
```

---

## 3. Integration Layers

### Layer 1 — External Connectivity
- **Protocols:** REST, GraphQL, Webhook, SMTP/IMAP
- **Authentication:** OAuth 2.0, API Keys, JWT, mTLS
- **Rate Limiting:** Per-source throttling with exponential backoff

### Layer 2 — API Gateway
- **Responsibility:** Protocol translation, auth validation, routing
- **Technology:** Laravel middleware + dedicated API gateway service
- **Features:** Request/response transformation, circuit breaker, caching

### Layer 3 — Event Bus (Hermes)
- **Responsibility:** Asynchronous inter-service communication
- **Technology:** Custom Hermes implementation with queue persistence
- **Topics:** `ilan.created`, `ilan.updated`, `tenant.activated`, `ai.completed`

### Layer 4 — MCP Platform
- **Responsibility:** Standardized AI tool access
- **Protocol:** Model Context Protocol (MCP)
- **Transport:** stdio + HTTP streaming hybrid

### Layer 5 — Internal Services
- **Service Mesh:** Internal HTTP/gRPC with mutual TLS
- **Service Discovery:** DNS-based with fallback to config

---

## 4. Key Integration Flows

### 4.1 Property Listing Flow
```
Telegram Webhook → API Gateway → IlanCrudService → Vector DB Index
                                      ↓
                               Hermes (ilan.created)
                                      ↓
                         ┌────────────────────────┐
                         │ Google Calendar (event)│
                         │ Airbnb (sync)          │
                         │ Email (confirmation)   │
                         └────────────────────────┘
```

### 4.2 AI Workforce Flow
```
User Request → AI Workforce Orchestrator
                    ↓
           ┌────────▼────────┐
           │  YalihanCortex │
           │  (OpenAI/Ollama│
           │   DeepSeek)    │
           └────────┬────────┘
                    ↓
           ┌────────▼────────┐
           │  Vector DB      │
           │  (context)      │
           └────────┬────────┘
                    ↓
              AI Response → Hermes (ai.completed) → User
```

### 4.3 External Marketplace Sync
```
Scheduler (n8n) → Marketplace MCP
                        ↓
            ┌───────────────────┐
            │ Airbnb API        │
            │ Sahibinden API    │
            │ Hepsiemlak API    │
            └────────┬──────────┘
                     ↓
              Normalize → Store → Hermes (listing.synced)
```

---

## 5. Integration Patterns

| Pattern | Use Case | Technology |
|---------|----------|------------|
| **Request-Reply** | Sync operations, data fetch | REST/gRPC |
| **Event Subscription** | Real-time updates, webhooks | Hermes + Webhook |
| **Publish-Subscribe** | Cross-service notifications | Hermes Topics |
| **Saga/Chain** | Multi-step transactions | Hermes + Compensation |
| **Circuit Breaker** | External API protection | Resilience4j patterns |
| **CQRS** | Read/write separation | Separate endpoints |

---

## 6. Data Ownership

| Domain | Owner Service | Sync Targets |
|--------|---------------|--------------|
| Listings | IlanCrudService | Airbnb, Sahibinden, Hepsiemlak |
| Contacts | Tenant Service | Google Contacts, CRM |
| Calendar | Scheduling Service | Google Calendar |
| AI Context | YalihanCortex | Vector DB |
| Finance | Finance Service | External Accounting |

---

## 7. Non-Functional Requirements

| Requirement | Target |
|-------------|--------|
| **Latency** | API Gateway < 100ms p95 |
| **Availability** | 99.9% uptime per integration |
| **Throughput** | 1000 req/s peak |
| **Recovery Time** | < 30s after failure |
| **Data Freshness** | Marketplace sync < 5 min |

---

## 8. Compliance & Security

- All PHI/PII encrypted at rest (AES-256) and in transit (TLS 1.3)
- GDPR-compliant data handling for EU tenants
- Full audit trail for all data access
- Token rotation every 90 days for OAuth connections

---

## 9. Dependencies

```
┌─ External ─────────────────┐   ┌─ Internal ────────────────┐
│ Google Workspace SDK       │   │ YalihanCortex             │
│ Telegram Bot API           │   │ IlanCrudService           │
│ WhatsApp Business API      │   │ Tenant Service            │
│ Airbnb Partner API         │   │ Hermes                    │
│ Sahibinden API             │   │ Vector DB (Pinecone/Qdrant│
│ Hepsiemlak API             │   │ MCP Platform              │
│ n8n Workflow Engine        │   │ AI Workforce              │
│ OpenClaw SDK               │   │ Audit Service             │
└────────────────────────────┘   └───────────────────────────┘
```

---

*Document approved by SAAB v6 Integration Office*

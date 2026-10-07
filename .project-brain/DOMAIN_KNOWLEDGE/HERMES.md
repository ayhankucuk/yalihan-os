# DOMAIN KNOWLEDGE: HERMES (EVENT BUS)

**LAST_VERIFIED_HEAD:** a7aa6532
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: Hermes

**Tanım:**
- Event/orchestration katmanı
- Domain'ler arası event dağıtımı
- Agentruntime ve iş koordinasyonu
- **NOT:** AI model veya UI değil

**SOURCE:** HermesDispatcher, HermesService, HermesHandler*

---

## Core Components

| Component | Purpose | Evidence |
|-----------|---------|----------|
| HermesDispatcher | Event dağıtımı | app/Services/Hermes/HermesDispatcher.php |
| HermesService | Ana koordinasyon | app/Services/Hermes/HermesService.php |
| HermesRegistry | Servis kayıtları | app/Services/Hermes/HermesRegistry.php |
| HermesReplayService | Event replay | app/Services/Hermes/HermesReplayService.php |
| WorkforceService | İş gücü yönetimi | app/Services/Hermes/WorkforceService.php |

---

## Handlers

| Handler | Purpose | Evidence |
|---------|---------|----------|
| CommunicationEmailHandler | Email olayları | REPO_VERIFIED |
| AsyncHandlerDispatchJob | Async event işleme | app/Jobs/Hermes/AsyncHandlerDispatchJob.php |

---

## Event Models

| Model | Purpose | Evidence |
|-------|---------|----------|
| HermesEventLog | Event kayıtları | app/Models/Hermes/HermesEventLog.php |
| WorkforceExecutionLog | İş执行 log | app/Models/Hermes/WorkforceExecutionLog.php |
| HermesAnalytics | Analitik | app/Models/Hermes/HermesAnalytics.php |

---

## Event Vocabulary

| Enum | Purpose | Evidence |
|------|---------|----------|
| HermesEventVocabulary | Standart event isimleri | app/Domain/Hermes/Enums/HermesEventVocabulary.php |
| HermesWorkforceEventVocabulary | Workforce event'leri | REPO_VERIFIED |
| HermesCapability | Agent yetenekleri | app/Domain/Hermes/Enums/HermesCapability.php |

---

## Event Flow

```
Domain Event (e.g., ReservationCreated)
        ↓
HermesDispatcher
        ↓
Event Handler (HermesHandlerContract)
        ↓
AsyncHandlerDispatchJob (queue)
        ↓
Handler Execution
        ↓
HermesEventLog (kayıt)
```

---

## Controllers

| Controller | Purpose | Evidence |
|-----------|---------|----------|
| HermesDashboardController | Dashboard | app/Http/Controllers/Admin/HermesDashboardController.php |
| HermesReplayController | Event replay | app/Http/Controllers/Admin/HermesReplayController.php |

---

## Event Types

| Type | Description | Evidence |
|------|-------------|----------|
| Email Communication | Email alındı | EmailCommunicationReceivedEvent |
| Drive Webhook | Drive tetikleme | DriveWebhookEvent |

---

## Agent Registry

| Model | Purpose |
|-------|---------|
| AgentRegistryEntry | Agent kayıtları |
| CapabilityBinding | Yetenek bağlantıları |

---

## HermesDashboardService

**Purpose:** Dashboard data aggregation

**Evidence:** app/Services/Hermes/HermesDashboardService.php

---

## TENANT ISOLATION

| Check | Status | Evidence |
|-------|--------|----------|
| Tenant-scoped events | UNKNOWN | Not verified |

---

## Related Domains

| Domain | Connection |
|--------|------------|
| Tüm domainler | Event üretebilir/tüketebilir |
| Reservation | Event tetikleyebilir |
| AI | Hermes → AI orchestration |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| - | NONE | Not tested |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Complete event vocabulary? | Partial enum observed |
| Handler registry? | Not fully documented |
| Queue integration details? | AsyncHandlerDispatchJob exists |
| Production usage? | Not verified |

---

## Hermes ≠ AI

⚠️ **IMPORTANT:** Hermes bir orchestration/event bus sistemidir. AI model DEĞİLDİR.

```
Hermes ≠ Claude ≠ DeepSeek ≠ Gemini
Hermes = Event Router + Handler Coordinator
```

---

## Architecture Position

```
User Request
        ↓
Controller (Laravel)
        ↓
Service (Domain Logic)
        ↓
HermesDispatcher (Event)
        ↓
Handler → Queue Job
        ↓
Other Services / Notifications / AI
```

# YALIHAN PLATFORM — Integration Blueprint v1

**Document Type:** Integration Architecture Blueprint
**Version:** v1.0
**Date:** 2026-06-28
**Classification:** Internal — Integration Office
**Authority:** Integration Board (Yalihan OS)
**Vision:** Build an AI-powered digital company, not isolated integrations

---

## Preamble

YALIHAN PLATFORM is a Modular Monolith with AI orchestration at its core. This blueprint governs every external system that communicates with YALIHAN PLATFORM.

> **Governing Question for every integration recommendation:**
> *"Does this integration strengthen YALIHAN PLATFORM as an AI-powered digital company?"*
>
> If the answer is no, the recommendation is rejected. If the answer is partially yes, the integration is redesigned until the answer is unambiguously yes.

**Architecture Principles:**
1. Integration before automation — build reliable pipes before smart flows
2. Event-driven whenever possible — temporal decoupling is non-negotiable
3. Single responsibility per platform — one system owns one domain
4. Human approval for critical actions — AI advises, humans decide
5. Loose coupling — integrations communicate via contracts, not implementations
6. High observability — if you cannot measure it, it does not exist
7. Recoverable failures — every failure must have a defined recovery path
8. No duplicated responsibilities — one owner per integration domain

---

## SECTION 1 — INTEGRATION INVENTORY

### 1.1 Active Integrations

| # | Integration | Type | Status | Owner | Strengthens AI Company? |
|---|------------|------|--------|-------|------------------------|
| 1 | **YalihanCortex** | AI Orchestrator | Active | Yalihan OS | ✅ Core brain |
| 2 | **DeepSeek** | AI Provider | Active | Yalihan OS | ✅ Primary inference |
| 3 | **OpenAI** | AI Provider | Active | Yalihan OS | ✅ Fallback inference |
| 4 | **Ollama** | AI Provider | Active | Yalihan OS | ✅ Local dev |
| 5 | **Anthropic Claude** | AI Provider | Active | Yalihan OS | ✅ High-capability tasks |
| 6 | **Google Gemini** | AI Provider | Active | Yalihan OS | ✅ Multimodal |
| 7 | **Google Maps / Geocoding** | Location | Active | Yalihan OS | ✅ Property mapping |
| 8 | **Google Speech-to-Text** | Voice | Active | Yalihan OS | ✅ Voice search |
| 9 | **Telegram Bot** | Messaging | Active | Yalihan OS | ✅ Advisor channel |
| 10 | **WhatsApp (Meta)** | Messaging | Active | Yalihan OS | ✅ Lead capture |
| 11 | **Instagram (Meta)** | Social | Active | Yalihan OS | ✅ Lead source |
| 12 | **Facebook (Meta)** | Social | Active | Yalihan OS | ✅ Lead source |
| 13 | **n8n** | Workflow Automation | Partial | Yalihan OS | ✅ Automation backbone |
| 14 | **Context7 MCP** | AI Context Protocol | Active | Yalihan OS | ✅ AI knowledge |
| 15 | **OpenClaw** | Agent Governance | Active (disabled) | Yalihan OS | ✅ IDE integration |
| 16 | **Laravel Horizon** | Queue Management | Active | Yalihan OS | ✅ Job orchestration |
| 17 | **Spatie Activity Log** | Audit Logging | Active | Yalihan OS | ✅ Compliance |
| 18 | **Telescope** | Observability | Active | Yalihan OS | ✅ Debug |
| 19 | **Sentry** | Error Tracking | Active | Yalihan OS | ✅ Production alerts |
| 20 | **TKGM** | Government API | Active | Yalihan OS | ✅ Property verification |
| 21 | **Azure Speech** | Voice API | Active | Yalihan OS | ✅ Transcription |
| 22 | **Google AI (Gemini)** | AI Provider | Active | Yalihan OS | ✅ Multimodal |
| 23 | **Asset Engine (PHP GD)** | Image Generation | Active | Yalihan OS | ✅ Marketing assets |
| 24 | **Field MCP (Bosch/FLIR)** | IoT | Active | Yalihan OS | ✅ Field data collection |
| 25 | **AI Budget Guard** | Cost Control | Active | Yalihan OS | ✅ Financial guardrails |
| 26 | **Circuit Breaker** | Resilience | Active | Yalihan OS | ✅ Provider health |

### 1.2 Planned / Dormant Integrations

| # | Integration | Type | Status | Owner | Strengthens AI Company? |
|---|------------|------|--------|-------|------------------------|
| 27 | **Hermes** | Infrastructure | Dormant | IT Ops | ⚠️ Server, not integration |
| 28 | **OpenClaw (full)** | Agent Gateway | Dormant | Yalihan OS | ⚠️ Proposal-only mode |
| 29 | **Google Drive** | Document Storage | Planned | Yalihan OS | ✅ AI knowledge base |
| 30 | **Google Sheets** | Data Export | Planned | Yalihan OS | ✅ Market intelligence |
| 31 | **Google Docs** | Report Generation | Planned | Yalihan OS | ✅ AI report drafts |
| 32 | **Google Calendar** | Scheduling | Planned | Yalihan OS | ✅ Appointment sync |
| 33 | **Google Contacts** | CRM Sync | Planned | Yalihan OS | ✅ Contact management |
| 34 | **Gmail** | Email | Planned | Yalihan OS | ✅ Owner communication |
| 35 | **NotebookLM** | AI Research | Planned | Yalihan OS | ✅ Market research |
| 36 | **Canva** | Design Automation | Future | — | ✅ Brand-consistent assets |
| 37 | **Google Slides** | Presentations | Future | — | ✅ AI pitch decks |
| 38 | **WhatsApp Business API** | Advanced Messaging | Future | — | ✅ Owner communications |
| 39 | **Electronic Signature** | Contract | Future | — | ✅ Deal closure |
| 40 | **MLSPortals** | Property Portals | Future | — | ✅ Distribution |
| 41 | **Municipality Open Data** | Data Enrichment | Future | — | ✅ Market intelligence |
| 42 | **Accounting Software** | Finance | Future | — | ✅ Financial AI |
| 43 | **Google Workspace (full)** | Suite | Future | — | Depends on prioritization |

### 1.3 Rejected Integrations

| # | Integration | Reason for Rejection |
|---|------------|----------------------|
| — | Any third-party MLS without AI integration layer | Weakens AI company vision |
| — | Any portal integration that bypasses YalihanCortex | Fragmented intelligence |
| — | Legacy FTP-based file transfer | Violates modern data contract standards |

---

## SECTION 2 — SYSTEM OWNERSHIP MODEL

**Principle:** One system owns one domain. Overlapping responsibilities are architecturally forbidden.

### 2.1 Ownership Matrix

| Domain | Owner | Partner | Forbidden |
|--------|-------|---------|-----------|
| **AI Orchestration** | YalihanCortex (SEALED) | — | Any other system creating AI pipelines |
| **AI Providers** | AIProviderManager | YalihanCortex | Services creating their own provider instances |
| **AI Budget & Cost** | AiBudgetGuard | CostGuardService | Unbudgeted AI calls |
| **AI Telemetry** | AiTelemetryService | — | Other services logging AI usage |
| **Property Listings (write)** | IlanCrudService (SEALED) | — | Any controller/service writing directly |
| **Property Listings (read)** | IlanRepository | ListingProjection | Direct Eloquent queries on live data |
| **CRM / Contacts (write)** | KisiCrudService | — | Direct Eloquent writes on Kisiler |
| **n8n (outbound)** | N8nIntegrationService | — | Ad-hoc webhook calls outside service |
| **n8n (inbound)** | N8nWebhookController → Use Cases | N8nService (legacy) | Controllers doing business logic |
| **Telegram** | TelegramService | TelegramBotService | Ad-hoc Telegram API calls |
| **WhatsApp / Meta** | WhatsAppWebhookController | SendWhatsAppMessageJob | Ad-hoc Graph API calls |
| **Instagram / Facebook** | InstagramWebhookController / FacebookWebhookController | Send*MessageJob | Ad-hoc Graph API calls |
| **OpenClaw Gateway** | AuditMcpServer | OpenClawAuditService | Any non-whitelisted agent write |
| **Agent Tools (MCP)** | YalihanBekciMcpServer | AuditMcpServer | Other systems registering MCP tools |
| **Context7 MCP** | Context7BridgeService | — | Other AI systems bypassing Context7 |
| **Queue/Horizon** | Horizon (infrastructure) | Laravel Queue | Manual queue manipulation |
| **Retry / DLQ** | Laravel failed_jobs | NotifyN8nOnFailure | Custom DLQ unless approved |
| **Secret Management** | ENV + Settings Table | AiSettingsCacheService | Hardcoded secrets |
| **Audit Logging** | Spatie ActivityLog | Security Log Channel | Duplicate audit trails |
| **AI Telemetry** | AiTelemetryService | OpenTelemetryService | Competing AI logs |
| **Google Workspace** | GoogleWorkspaceService (NEW) | — | Currently unowned |
| **Drive Folder Creation** | DriveIntegrationService (NEW) | — | Currently unowned |
| **NotebookLM Sync** | NotebookLMService (NEW) | — | Currently unowned |
| **Canva Design** | CanvaIntegrationService (NEW) | — | Currently unowned |
| **Document Generation** | DocumentService (NEW) | — | Currently unowned |
| **Property Verification (TKGM)** | TkgmService | — | Already owned |
| **PDF Generation** | ReportService / PdfService | — | Already owned |
| **Notification Delivery** | TelegramOutboundJob | SendWhatsAppMessageJob | Direct API calls |
| **Market Intelligence** | IntelligenceHubService | N8nWebhookService | Ad-hoc analysis |
| **Lease / Contract Signing** | ContractService (NEW) | — | Currently unowned |
| **Field Devices (MCP)** | FieldMcpController | — | Owned but generic |

### 2.2 Ownership Violation Protocol

When a system boundary violation is detected:
1. **Detect:** Bekçi AST scan catches direct writes, env() usage, duplicate logging
2. **Classify:** Severity = Critical (cross-tenant) / High (write authority) / Medium (duplication)
3. **Notify:** Slack alert to `#governance` + ticket created automatically
4. **Remediate:** Owner has 5 business days to refactor
5. **Regression test:** Bekçi scan must pass before deployment gate closes

---

## SECTION 3 — INTEGRATION LIFECYCLE

Every integration follows the same lifecycle. No exceptions.

### 3.1 Configuration Lifecycle

```
PROPOSAL → ARCHITECTURE REVIEW → IMPLEMENTATION → TESTING → DEPLOYMENT → MONITORING → DEPRECATION → RETIREMENT
```

**PROPOSAL (Week 0)**
- Document: Integration Proposal Document (IPD)
- Template includes: purpose, owner, data contract, SLA, failure modes, cost
- Submit to: Integration Board (this office)
- Mandatory question: Does this strengthen YALIHAN PLATFORM as an AI company?

**ARCHITECTURE REVIEW (Week 1)**
- Integration Board evaluates: ownership, coupling, observability, failure recovery
- Approved → moves to IMPLEMENTATION
- Conditionally approved → changes required, re-review
- Rejected → documented rationale, alternative recommended

**IMPLEMENTATION (Week 2–4)**
- Service class created under `App/Services/{Domain}/`
- No business logic in controllers
- All auth credentials via Settings table (not env for runtime overrides)
- Retry policies defined from day 1
- Monitoring hooks (telemetry/audit) built in parallel
- Unit tests + integration tests written

**TESTING (Week 3–5)**
- Contract tests: verify JSON schema compatibility
- Failure injection tests: simulate provider outage, network timeout, auth failure
- Load tests: confirm retry/DLQ behavior under pressure
- End-to-end test: full event flow from trigger to final state

**DEPLOYMENT (Week 4–6)**
- Feature flag enabled (never full traffic on day 1)
- Shadow mode: integration runs, output logged, not acted upon
- Gradual traffic increase: 5% → 25% → 50% → 100% over 2 weeks
- Rollback plan documented and tested

**MONITORING (Permanent)**
- Health dashboard per integration
- Cost tracking per integration
- Error rate alert thresholds
- P95 latency budgets
- Anomaly detection baselines

**DEPRECATION (When triggered)**
- Announce deprecation in `#integrations` Slack channel
- Feature flag to 0 traffic
- Sunset date set (minimum 90 days from announcement)
- Migration guide published
- Legacy integration flagged in code with `@deprecated` + deprecation date

**RETIREMENT (After sunset)**
- Remove integration code (not before 90 days)
- Update documentation
- Archive workflow JSON files
- Close API credentials
- Update ownership matrix

### 3.2 Authentication Lifecycle

| Auth Type | Setup | Rotation | Storage | Verification |
|-----------|-------|----------|---------|--------------|
| **API Key (simple)** | ENV → Settings table | Manual + alert at 90 days | ENV (primary), Settings (override) | Unit test on rotation |
| **OAuth 2.0 (Google Workspace)** | Google Console + Laravel Socialite | Auto-refresh via refresh token | Encrypted in DB | Token health check job |
| **HMAC Signature (Webhooks)** | Shared secret in ENV | Manual + rotation job | ENV only | Every request verified |
| **Bearer Token (OpenClaw)** | ENV + audit log | Auto-rotate via script | ENV | OpenClaw middleware |
| **META App Secret** | Facebook Developer Console | Manual | ENV | HMAC verified every request |
| **Webhook Verify Token** | ENV | Manual | ENV | Challenge-response on setup |

**Rotation Policy:**
- All secrets reviewed every 90 days
- Rotation must not cause downtime (use dual-key during transition)
- After rotation, old key invalidated immediately (no grace period)

### 3.3 Monitoring Lifecycle

Every integration requires:
1. **Health endpoint** — `/api/integrations/{name}/health` returning: status, latency, error_rate, last_success, last_failure
2. **Cost tracking** — Monthly cost per integration (AI providers: per-model, external APIs: per-call)
3. **SLA dashboard card** — Per integration: uptime, error rate, P95 latency
4. **Alert routing** — Critical → Slack `#incidents`, Warning → Slack `#integrations`, Info → Dashboard only

**Alert Thresholds (defaults, per integration configurable):**
- Uptime < 99.5% → CRITICAL
- Error rate > 1% → WARNING
- Error rate > 5% → CRITICAL
- Latency P95 > 2× budget → WARNING
- Latency P95 > 5× budget → CRITICAL
- Cost > 80% of monthly budget → WARNING
- Cost > 100% of monthly budget → CRITICAL

### 3.4 Retry Lifecycle

**Standard Retry Policy (applies to all integrations unless specified):**
```
Attempt 1 → Immediate
Attempt 2 → 60 seconds later
Attempt 3 → 300 seconds later (5 min)
Attempt 4 → 900 seconds later (15 min)
After Attempt 4 → DEAD LETTER QUEUE
```

**Integration-specific overrides:**
| Integration | Max Tries | Backoff | DLQ Action |
|-------------|-----------|---------|-----------|
| Telegram | 3 | 60s, 300s, 600s | Notify admin, log failure |
| WhatsApp | 3 | 60s, 120s, 240s | Mark failed, notify lead owner |
| n8n outbound | 3 | 100ms delay | Log, skip (non-critical) |
| AI Providers | 3 | 1s, 2s, 4s | Circuit breaker opens |
| Google Workspace | 3 | 60s, 300s, 900s | Queue for retry, alert |
| Meta Graph API | 3 | 60s, 120s, 240s | Mark message failed, notify |

**No retry policy is infinite.** Every integration has a DLQ entry point.

### 3.5 Versioning Lifecycle

**Contract Versioning:**
- All external API contracts (webhook payloads, request schemas) are versioned
- Version format: `v{major}.{minor}` (e.g., `v1.0`, `v1.1`, `v2.0`)
- Breaking changes → new major version (e.g., `v2.0`)
- Additive changes → new minor version (e.g., `v1.1`)
- All versions supported simultaneously for minimum 6 months after new version release
- Deprecation notice sent to all consumers 90 days before version shutdown

**Webhook Contract Versioning:**
```
Header: X-Yalihan-Contract-Version: v2.0
Webhook URL: /api/v1/webhook/{integration}/v2
Old URL: /api/v1/webhook/{integration}/v1 (deprecated, 6-month sunset)
```

**Example: n8n webhook evolution:**
- `v1.0` (current): `{ilan_id, event, timestamp}`
- `v1.1` (additive): `{ilan_id, event, timestamp, tenant_id, correlation_id}`
- `v2.0` (breaking): `{resource: {id, type, attributes}, meta: {timestamp, correlation_id, version}}`

### 3.6 Retirement Lifecycle

Triggered when:
- Integration is replaced by a better alternative
- Provider goes out of business / API deprecated
- Cost exceeds value delivered
- Security vulnerability makes continuation untenable

**Retirement Steps:**
1. Integration Board formally approves retirement
2. `@deprecated` annotation added to all integration classes with `sunset_date`
3. All consumers notified via `#integrations` Slack + direct contact
4. Feature flag disables integration
5. Traffic routes to fallback (if applicable)
6. After 90 days: code removed, credentials revoked, documentation archived
7. Ownership matrix updated

---

## SECTION 4 — EVENT FLOW ARCHITECTURE

### 4.1 Core Event Flows

All flows follow: **Trigger → Event Dispatch → Queue → Integration → External System → Notification → Human**

#### FLOW A: Portfolio Creation (Lead Capture → Active Portfolio)

```
[WhatsApp/Instagram/Facebook Webhook]
    ↓
NLPProcessor (intent: lead_capture)
    ↓
KisiCrudService::create() [write authority]
    ↓
KisiCreated Event
    ↓
Queue: events
    ├── NotifyN8nOnNewKisi → n8n → CRM enrichment
    ├── AiTelemetryService → log lead source
    └── TelegramOutboundJob → auto-reply (AI-generated)
    ↓
[AI Copilot analyzes lead quality]
    ↓
High quality → Telegram alert to assigned danışman
Low quality → nurture sequence (future: email/WhatsApp drip)
```

#### FLOW B: Listing Creation (Listing Wizard → Published → Market Ready)

```
[Admin Listing Wizard — Step 5: Submit]
    ↓
StoreIlanRequest (validation)
    ↓
IlanCrudService::store() [SOLE WRITE AUTHORITY]
    ↓
IlanCreated Event
    ↓
Queue: events (synchronous chain)
    ├── SyncListingProjectionJob → listing_search_projection (CQRS read model)
    ├── FindMatchingDemands → AI match engine
    ├── NotifyN8nAboutNewIlan → n8n webhook
    │       ↓
    │   n8n workflow:
    │       ├── DriveIntegrationService → CreatePortfolioFolder (Google Drive)
    │       ├── NotebookLMService → SyncKnowledgeBase
    │       ├── TelegramService → Danışman notification
    │       └── MarketAnalysisTrigger → Cortex market comparison
    └── AI Telemetry → AiTelemetryService::logTransaction
    ↓
AI match found (CortexFindingService)
    ↓
High match score → n8n workflow → Telegram notification to buyers
    ↓
OwnerReportExportJob → PDF → Drive folder → Email to owner
```

#### FLOW C: Market Intelligence Pipeline

```
[Cortex: analyzeMarketTrends()]
    ↓
DeepSeek (primary inference)
    ↓
MarketAnalysisResult Event
    ↓
Queue: events
    ├── NotifyN8nAboutMarketAnalysis → n8n
    │       ↓
    │   n8n:
    │       ├── Google Sheets → AppendMarketData (price trends)
    │       └── Telegram → Team channel (summary)
    ├── IntelligenceHubService → UpdateMarketDashboard
    └── AiTelemetryService → cost + quality logging
    ↓
[Portfolio Manager UI: Market tab]
```

#### FLOW D: AI Copilot Pipeline (Complex Multi-Step)

```
POST /api/advisor/copilot
    ↓
CopilotOrchestrator::dispatch()
    ↓
PipelineRun record created
    ↓
Sequential steps (each on dedicated queue):
    1. copilot-high: NormalizeInput
    2. copilot-default: AuditContext
    3. copilot-default: FixRecommendations
    4. copilot-default: ExecuteActions
    5. copilot-verification: VerifyCompliance
    6. copilot-governance: ApplyGovernanceRules
    ↓
Pipeline completed → Result returned to user
    ↓
AI Telemetry → Full step trace logged
```

#### FLOW E: Task Lifecycle (Görev)

```
[Admin creates task via GorevService]
    ↓
GorevCreated Event
    ↓
Queue: events
    ├── NotifyN8nAboutNewGorev → n8n → Calendar reminder (Google Calendar — future)
    └── GorevReminderJob scheduled
    ↓
Deadline approaching (GorevDeadlineYaklasiyor)
    ↓
Queue: events
    ├── NotifyN8nAboutGorevDeadlineYaklasiyor → n8n → Telegram reminder
    └── GorevReminderJob → notification
    ↓
Deadline missed (GorevGecikti)
    ↓
Queue: events
    ├── NotifyN8nAboutGorevGecikti → n8n → Telegram alert to manager
    └── GorevGeciktiEvent → TelegramService → admin notification
```

#### FLOW F: Reverse Match Pipeline

```
[/api/v1/webhook/n8n/trigger-reverse-match]
    ↓
CheckN8nSecret middleware
    ↓
TriggerReverseMatchUseCase
    ↓
CortexMatchingService::detectBuyerMatches(Ilan)
    ↓
For each match:
    ├── TelegramService → notify matched buyer (if high match score)
    └── N8nIntegrationService → triggerWorkflow('ai_opportunity')
    ↓
ReverseMatchResult → n8n → Telegram summary to danışman
```

#### FLOW G: Property Verification (TKGM)

```
[PriceAdvisor or Listing creation — TKGM verification step]
    ↓
TkgmService::getParcelData(ilId, ilceId, mahalleId, adaNo, parselNo)
    ↓
TKGM API (parcel data, imar status, emsal/gabari)
    ↓
CortexIntelligenceService::verifyPropertyData(tkgmData, ilanData)
    ↓
Mismatch detected → Warning notification to danışman
No mismatch → PriceAdvisor confidence score boosted
    ↓
VerificationResult logged to ai_telemetry
```

#### FLOW H: Field Data Collection (MCP)

```
[Field MCP: Bosch GLM / FLIR ONE device]
    ↓
POST /api/v1/field-mcp
    ↓
FieldMcpController → FieldMcpService
    ↓
AssetEngine (PHP GD) → Generate measurement visualization
    ↓
IlanCrudService::update() [via write authority]
    ↓
IlanUpdated Event
    ↓
AI quality check: field data completeness
    ↓
Owner notified of new field data via Telegram
```

### 4.2 Event Naming Convention

All domain events follow: `{Entity}{Action}` with past tense verb

| Event | Entity | Action | Payload |
|-------|--------|--------|---------|
| `IlanCreated` | Ilan | Created | `{ilan_id, tenant_id, created_by, timestamp}` |
| `IlanPriceChanged` | Ilan | PriceChanged | `{ilan_id, old_price, new_price, change_pct}` |
| `KisiCreated` | Kisi | Created | `{kisi_id, source, qualification_score}` |
| `TalepReceived` | Talep | Received | `{talep_id, kisi_id, match_score}` |
| `GorevCreated` | Gorev | Created | `{gorev_id, assigned_to, deadline}` |
| `GorevDeadlineYaklasiyor` | Gorev | DeadlineYaklasiyor | `{gorev_id, hours_remaining}` |
| `GorevGecikti` | Gorev | Gecikti | `{gorev_id, delay_hours, assigned_to}` |
| `CortexMatchFound` | Cortex | MatchFound | `{ilan_id, talep_id, score, model}` |
| `AIBudgetWarning` | AI | BudgetWarning | `{tenant_id, feature, spent_pct, daily_limit_usd}` |
| `MarketAnalysisComplete` | Market | AnalysisComplete | `{location, avg_price_m2, trend, confidence}` |

### 4.3 Event Contract Schema (v1.0)

```json
{
  "version": "v1.0",
  "event": "IlanCreated",
  "timestamp": "2026-06-28T08:00:00+03:00",
  "correlation_id": "uuid-v4",
  "tenant_id": "tenant-uuid",
  "payload": {
    "ilan_id": "integer",
    "reference_number": "string",
    "created_by": "user-uuid",
    "ilan_tipi": "string"
  },
  "meta": {
    "source": "ilan-wizard",
    "source_version": "string",
    "environment": "production|staging|local"
  }
}
```

---

## SECTION 5 — INTEGRATION STANDARDS

### 5.1 Naming Standards

**Service Class Naming:**
```
App/Services/{Domain}/{Feature}Service.php
App/Services/AI/{Provider}Provider.php
App/Services/Integrations/{Platform}{Feature}Service.php
```

Examples:
- `App/Services/Google/DriveIntegrationService.php`
- `App/Services/Google/SheetsIntegrationService.php`
- `App/Services/NotebookLM/NotebookLMService.php`
- `App/Services/Canva/CanvaIntegrationService.php`
- `App/Services/TKGM/TkgmService.php` (already exists)

**Job Naming:**
```
App/Jobs/{Domain}/{Action}{Entity}Job.php
Notify{Integration}About{Event}.php  (for n8n bridge jobs)
Send{Platform}{MessageType}Job.php   (for outbound messaging)
```

**Controller Naming:**
```
App/Http/Controllers/{Domain}/{Feature}Controller.php
{Platform}WebhookController.php      (for inbound webhooks)
```

**Environment Variable Naming:**
```
{PLATFORM}_{FEATURE}_{TYPE} = value
DEEPSEEK_API_KEY = value
N8N_WEBHOOK_SECRET = value
GOOGLE_DRIVE_CLIENT_ID = value
CANVA_API_KEY = value
```

### 5.2 Webhook Contract Standards

**Inbound Webhook (Yalihan receives):**
```
Headers:
  X-Webhook-Source: {platform}
  X-Webhook-Timestamp: {unix_timestamp}
  X-Webhook-Signature: {hmac_sha256}

GET /verify → 200 OK with hub.challenge (for Meta webhooks)
POST /handle → 200 OK within 5 seconds (always, even on error)
```

**Outbound Webhook (Yalihan sends):**
```
Headers:
  X-Source: yalihan-cortex
  X-Correlation-ID: {uuid}
  X-Contract-Version: v1.0
  Content-Type: application/json
  Authorization: Bearer {webhook_token}

Timeout: 10 seconds
Retry: 3 attempts with exponential backoff
Payload max size: 64KB
```

**Webhook Payload Standards:**
```json
{
  "version": "v1.0",
  "event": "string",
  "timestamp": "ISO8601",
  "correlation_id": "uuid",
  "tenant_id": "uuid",
  "payload": { },
  "meta": {
    "source": "string",
    "environment": "production|staging"
  }
}
```

### 5.3 API Contract Standards

**RESTful Endpoint Standards:**
```
GET    /api/v1/{resource}           → List (paginated)
GET    /api/v1/{resource}/{id}      → Read
POST   /api/v1/{resource}            → Create
PATCH  /api/v1/{resource}/{id}      → Partial update
PUT    /api/v1/{resource}/{id}      → Full replace
DELETE /api/v1/{resource}/{id}      → Soft delete

AI endpoints:
GET    /api/v1/{resource}/{id}/ai/{action}  → AI-assisted action
POST   /api/v1/{resource}/ai/{action}       → AI bulk action
```

**Response Envelope:**
```json
{
  "data": { },
  "meta": {
    "request_id": "uuid",
    "timestamp": "ISO8601",
    "version": "v1"
  },
  "errors": [ ],
  "warnings": [ ]
}
```

**Error Response:**
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Human-readable message",
    "details": [
      {"field": "email", "message": "Invalid format"}
    ]
  },
  "meta": {
    "request_id": "uuid",
    "timestamp": "ISO8601"
  }
}
```

### 5.4 Folder & File Conventions

**Integration workspace (one per external platform):**
```
yalihan2026/
├── app/
│   └── Services/
│       ├── Google/              # Google Workspace integrations
│       │   ├── DriveIntegrationService.php
│       │   ├── SheetsIntegrationService.php
│       │   ├── DocsIntegrationService.php
│       │   ├── CalendarIntegrationService.php
│       │   ├── GmailService.php
│       │   └── GoogleWorkspaceService.php  # Factory
│       ├── NotebookLM/
│       │   └── NotebookLMService.php
│       ├── Canva/
│       │   └── CanvaIntegrationService.php
│       ├── WhatsApp/
│       │   └── WhatsAppService.php
│       └── TKGM/
│           └── TkgmService.php  # Already exists
├── config/
│   ├── google-workspace.php     # Unified Google config
│   ├── canva.php
│   ├── notebooklm.php
│   └── integrations.php         # Cross-cutting: retry policies, DLQ, etc.
├── resources/views/admin/integrations/
│   ├── google-workspace.blade.php
│   ├── canva.blade.php
│   └── notebooklm.blade.php
├── docs/integrations/
│   ├── google-workspace.md
│   ├── canva.md
│   ├── notebooklm.md
│   └── [platform].md
├── tests/
│   ├── Unit/Services/{Platform}/
│   └── Feature/Webhooks/{Platform}WebhookTest.php
└── workflows/                   # n8n workflow exports
    ├── n8n-ilan-created-v1.json
    ├── n8n-ilan-created-v2.json
    ├── n8n-market-analysis-v1.json
    └── README.md
```

**File Naming within Integration:**
```
{Feature}Service.php           # Main service class
{Feature}Config.php            # Configuration/validation
{Feature}WebhookController.php # Inbound webhook handler
{Feature}OutboundJob.php       # Outbound queue job
{Feature}Contract.php          # DTO/Schema definitions
{Feature}Test.php              # Unit tests
{Feature}IntegrationTest.php   # Feature tests
```

### 5.5 Error Handling Standards

**Error Categories:**
| Category | Code Range | Behavior |
|----------|------------|----------|
| Validation | 400 | Return 400, do not retry |
| Authentication | 401 | Return 401, do not retry, alert security |
| Authorization | 403 | Return 403, do not retry, log incident |
| Rate Limit | 429 | Retry after Retry-After header |
| Server Error | 500–599 | Retry with backoff, DLQ after 4 attempts |
| Timeout | TIMEOUT | Retry with backoff, DLQ after 4 attempts |
| Circuit Open | CIRCUIT_OPEN | Fail fast, do not retry, alert |

**Error Response Standards:**
```php
// Service method pattern
public function callExternalAPI(array $data): Result
{
    try {
        $response = $this->client->post($data);
        return Result::ok($response->json());
    } catch (ConnectionException $e) {
        Log::warning('external_api_connection_failed', [
            'service' => static::class,
            'error' => $e->getMessage(),
            'retry_count' => $this->attempts ?? 0,
        ]);
        throw $e; // Let job retry
    } catch (ValidationException $e) {
        Log::error('external_api_validation_error', [...]);
        return Result::fail('VALIDATION_ERROR', $e->getMessage());
    } catch (CircuitBreakerOpenException $e) {
        Log::critical('circuit_breaker_open', [...]);
        return Result::fail('CIRCUIT_OPEN', 'Service temporarily unavailable', true);
    }
}
```

**No silent catches. Every catch either:**
- Logs and rethrows (for retryable errors)
- Returns a typed `Result` object with error code
- Triggers an alert for critical failures

### 5.6 Timeout Standards

| Integration Type | Default Timeout | Max Timeout |
|-----------------|-----------------|-------------|
| AI Provider (sync) | 30s | 60s |
| AI Provider (async) | 120s | 300s |
| External REST API | 10s | 30s |
| Webhook (inbound) | 5s to respond | N/A |
| Webhook (outbound) | 10s | 30s |
| Google Workspace API | 30s | 60s |
| Telegram API | 10s | 30s |
| Meta Graph API | 10s | 30s |
| n8n Workflow trigger | 10s | 30s |
| Database query | 5s | 10s |
| File generation | 60s | 180s |
| Drive file upload | 60s | 180s |

### 5.7 Monitoring Standards

**Per Integration Dashboard (minimum):**
- Health status (green/yellow/red)
- Uptime (30 days)
- Error rate (24h, 7d, 30d)
- P50/P95/P99 latency
- Cost (current month vs budget)
- Last successful call
- Last failed call with error code
- Alert status (acknowledged/pending)

**Log Levels by Event:**
| Event | Log Level |
|-------|-----------|
| Integration health check success | DEBUG |
| API call made | DEBUG |
| Webhook received | INFO |
| Retry attempted | WARNING |
| Circuit breaker state change | WARNING |
| DLQ entry created | ERROR |
| Authentication failure | SECURITY |
| Rate limit hit | WARNING |
| Budget threshold crossed | CRITICAL |
| Provider outage detected | CRITICAL |

---

## SECTION 6 — SECURITY

### 6.1 Secret Management Strategy

**Three-tier secret architecture:**

| Tier | Storage | Access | Rotation | Used For |
|------|---------|--------|---------|---------|
| **Tier 1: ENV-only** | `.env` (gitignored) | PHP runtime only | Manual | Production API keys, DB passwords |
| **Tier 2: ENV + DB override** | ENV + `settings` table | PHP runtime + admin UI | Runtime via Settings | Tenant-configurable keys (Telegram, n8n) |
| **Tier 3: Encrypted vault** | Laravel encrypted() | PHP only | Manual | Future: HashiCorp Vault integration |

**Current state:** Tier 1 + Tier 2 hybrid. No vault integration yet.

**Settings table pattern (Tier 2):**
```php
// Runtime override: Settings table takes precedence over ENV
$apiKey = Setting::get('deepseek_api_key') ?? env('DEEPSEEK_API_KEY');
```

**Hardcoded secrets:** ZERO tolerance. Bekçi AST scan enforces this. Any hardcoded secret triggers CI gate failure.

### 6.2 API Key Management

**Classification:**
| Class | Description | Examples | Storage |
|-------|-------------|----------|---------|
| A — Production critical | Direct access to live data, financial | Stripe, Twilio, WhatsApp | ENV only |
| B — Production functional | Core features, not financial | Telegram, DeepSeek, n8n | ENV + Settings override |
| C — Development | Dev/test only | Ollama, local services | ENV only |
| D — Read-only | Data enrichment only | Google Maps, TKGM | ENV + Settings override |

**Class A keys:** Two-key strategy during rotation. Never rotate directly.

**Key inventory document (required):**
Every integration maintains a `SECRETS.md` in its doc folder:
- Key name
- Owner (person + system)
- Created date
- Rotation date
- Last rotated
- Scope (what can this key access)
- Last used (log query result)

### 6.3 OAuth Standards

**Google Workspace OAuth:**
- Authorization flow: Laravel Socialite
- Scopes: Minimum necessary (read or write, not both unless necessary)
- Token storage: Encrypted in `oauth_tokens` table per tenant
- Refresh: Automatic via Socialite, job checks token health weekly
- Revocation: Immediate on user disconnect, DB row deleted

**Example scopes:**
```php
// Drive
'https://www.googleapis.com/auth/drive.file'  // App-created files only

// Sheets
'https://www.googleapis.com/auth/spreadsheets' // Read + write

// Gmail
'https://www.googleapis.com/auth/gmail.send'   // Send only, no read
```

### 6.4 Webhook Signature Verification

**Meta (WhatsApp/Instagram/Facebook):**
```php
// Every inbound webhook, non-negotiable
$signature = $request->header('X-Hub-Signature-256');
$expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
if (!hash_equals($expected, $signature)) {
    Log::security('meta_webhook_signature_failed', [...]);
    abort(403, 'Invalid signature');
}
```

**Telegram:**
```php
// Secret token validation
$secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
if (!hash_equals($telegramSecret, $secret)) {
    Log::security('telegram_webhook_secret_failed', [...]);
    abort(403);
}
```

**n8n:**
```php
// n8n secret middleware
$secret = $request->header('X-N8N-SECRET');
if (!hash_equals($n8nSecret, $secret)) {
    Log::security('n8n_webhook_secret_failed', [...]);
    abort(403);
}
```

**All webhooks return HTTP 200 within 5 seconds.** Business logic runs async. This prevents platform retry storms.

### 6.5 Access Control Model

**Integration access follows RBAC (Role-Based Access Control):**

| Role | n8n | Telegram | WhatsApp | Google Workspace | AI |
|------|-----|----------|----------|------------------|-----|
| Admin | Full | Full | Full | Full | Full + governance |
| Danışman | Read + own | Read + own | Send | Read + own | Suggested actions |
| Viewer | Read | None | None | None | None |
| System | Write (via service) | Write (via service) | Write (via job) | Write (via service) | AI inference |
| External (n8n) | Webhook only | Webhook only | Webhook only | None | None |

**OpenClaw Agent Access:** Explicit whitelist. Agents can only read context, request suggestions, trigger approved workflows. No write authority.

### 6.6 Audit Logging Standards

**What must be logged (per integration):**
| Event | Channel | Retention | Alert |
|-------|---------|-----------|-------|
| Authentication success | security | 90 days | No |
| Authentication failure | security | 90 days | Yes (3 failures = alert) |
| API key used | telemetry | 30 days | No |
| Data accessed | activity | 90 days | No |
| Data modified | activity | 90 days | No |
| Integration error | bekci | 90 days | Yes |
| DLQ entry | bekci | 90 days | Yes |
| Budget threshold crossed | bekci | 90 days | Yes |
| External API cost | telemetry | 30 days | At threshold |

**Correlation:** Every log entry must include `correlation_id` (UUID) that traces the full request lifecycle from webhook → queue → job → external API → response → notification.

### 6.7 Failure Recovery

**Recovery Runbook Template (required for every integration):**

```markdown
## {Integration Name} Failure Recovery Runbook

### Alert Severity: CRITICAL

### Symptoms
- Slack alert: `integration_{name}_down`
- Health endpoint returns 5xx

### Diagnosis
1. Check provider status page: [link]
2. Check logs: `grep {integration} logs/laravel.log | tail -50`
3. Check DLQ: `php artisan queue:failed`
4. Check cost: billing dashboard

### Immediate Actions (0–15 min)
- [ ] Verify API credentials not expired
- [ ] Check rate limits not exceeded
- [ ] Check circuit breaker state
- [ ] If provider outage: enable feature flag fallback

### Communication (15–30 min)
- [ ] Post to #incidents: "Integration {name} affected. Investigating."
- [ ] Notify affected danışmanlar via Telegram
- [ ] Update status page

### Resolution (30 min – 2 hours)
- [ ] Root cause identified
- [ ] Fix deployed or provider recovered
- [ ] DLQ reprocessed
- [ ] Confirm health endpoint green
- [ ] Post resolution to #incidents

### Post-Mortem (within 5 business days)
- [ ] Document root cause
- [ ] Add regression test
- [ ] Update this runbook
- [ ] If process gap: submit Integration Proposal for improvement
```

---

## SECTION 7 — GAP ANALYSIS

### 7.1 Missing Integrations

| Gap | Severity | Business Impact | Recommended Action |
|-----|----------|-----------------|-------------------|
| **Google Drive** | HIGH | Portfolio folders created manually, not automated | Design DriveIntegrationService (Sprint 4) |
| **Google Sheets** | HIGH | Market data in spreadsheets, not integrated | Design SheetsIntegrationService (Sprint 4) |
| **NotebookLM** | MEDIUM | Market research manual, not AI-powered | Design NotebookLMService (Sprint 5) |
| **Google Calendar** | MEDIUM | Appointments not synced, double-booking risk | Design CalendarIntegrationService (Sprint 5) |
| **Google Contacts** | MEDIUM | CRM contacts not synced with Google | Design ContactsIntegrationService (Sprint 5) |
| **Gmail** | MEDIUM | Owner communications manual | Design GmailService (Sprint 6) |
| **Canva** | HIGH | Property marketing images not brand-consistent | Design CanvaIntegrationService (Sprint 4) |
| **Google Docs** | MEDIUM | AI reports drafted, but not flowing to Docs | Design DocsIntegrationService (Sprint 5) |
| **Electronic Signature** | MEDIUM | Contracts still paper/email | Research providers (Sprint 6) |
| **Dead Letter Queue UI** | HIGH | No visibility into DLQ state | Build DLQ dashboard in Horizon |
| **Secret Vault** | MEDIUM | All secrets in ENV, no rotation automation | Plan vault migration (Sprint 6) |

### 7.2 Duplicate Integrations

| Duplicate | Owner A | Owner B | Resolution |
|-----------|---------|---------|-----------|
| **n8n service classes** | `N8nService` | `N8nIntegrationService` | Deprecate `N8nService`, migrate all callers to `N8nIntegrationService` |
| **n8n webhook config** | `config/n8n.php` | `config/services.php` (n8n section) | Consolidate into `config/integrations/n8n.php` |
| **Telegram webhook endpoints** | `POST /api/telegram/webhook` | `POST /api/integrations/telegram/webhook` | Consolidate to single endpoint with routing based on payload type |
| **Telegram config sources** | `config('services.telegram')` | `Setting::get()` table | Define priority: Settings DB > ENV. Document. One settings-aware factory. |
| **Meta app secret sharing** | WhatsApp | Instagram + Facebook | Correct architecture, but document shared dependency risk |
| **AI provider config** | `config/services.php` | `config/ai.php` | Consolidate into `config/ai/providers.php` |

### 7.3 Weak Integrations

| Integration | Weakness | Improvement |
|-------------|----------|-------------|
| **n8n (outbound)** | Disabled by default (`N8N_ENABLED=false`) | Activate for non-production use cases, document production readiness criteria |
| **OpenClaw** | `proposal_only=true`, kill switch disabled | Define staged rollout: read-only → proposal → write-approval → autonomous |
| **Field MCP** | Generic IoT, not specialized for property inspection | Build property-specific measurement workflow |
| **TKGM** | No caching, no fallback | Add 24-hour cache, graceful degradation when TKGM unavailable |
| **WhatsApp (inbound)** | NLP processor quality not measured | Add confidence threshold SLA, dashboard for NLP accuracy |
| **Instagram / Facebook** | Same message as WhatsApp, no channel-specific content | Channel-adaptive message generation |

### 7.4 Single Points of Failure

| SPOF | Risk | Mitigation |
|------|------|-----------|
| **YalihanCortex (single)** | Platform brain, no failover | Already multi-provider (DeepSeek → OpenAI → Gemini). Add explicit failover with status dashboard |
| **Telegram bot** | Single channel for critical alerts | Implement WhatsApp as secondary critical channel |
| **n8n (single instance)** | Workflow engine down = no automations | Multi-instance setup or documented fallback (manual triggers) |
| **DeepSeek** | Primary AI down | Already has fallback chain. Add health dashboard per provider |
| **Settings table** | DB down = integration keys unavailable | ENV as fallback (already implemented), document priority |
| **No CDN** | Asset delivery relies on app server | Plan CDN integration for marketing assets |
| **WhatsApp Business API** | Meta outage = no WhatsApp comms | Telegram as fallback channel |

---

## SECTION 8 — FUTURE OPPORTUNITIES

Every opportunity is evaluated against the governing question: *"Does this strengthen YALIHAN PLATFORM as an AI-powered digital company?"*

### 8.1 Recommended Integrations (Strengthens AI Company Vision)

| Integration | Strengthens AI Company? | Rationale | Priority | Dependencies |
|------------|------------------------|-----------|---------|-------------|
| **Google Drive** | ✅ YES | Automated portfolio folder creation, AI knowledge sync, document management. Reduces manual work, increases AI knowledge base quality. | P0 — Sprint 4 | Google OAuth, DriveIntegrationService |
| **Google Sheets** | ✅ YES | Market data export, AI analysis → structured spreadsheet. Enables data-driven decisions for danışmanlar. | P0 — Sprint 4 | Google OAuth, Sheets API v4 |
| **Canva** | ✅ YES | Brand-consistent marketing assets from AI-generated content. Converts AI suggestions into professional designs automatically. | P0 — Sprint 4 | Canva Connect API (OAuth 2.0) |
| **NotebookLM** | ✅ YES | AI-powered market research from Yalihan data. Strengthens AI company by making AI do research, not humans. | P1 — Sprint 5 | NotebookLM API (if available) or Google Drive sync |
| **Google Docs** | ✅ YES | AI-generated reports flow directly to Docs. AI company = AI produces, organizes, shares knowledge. | P1 — Sprint 5 | Google Docs API |
| **Google Calendar** | ✅ YES | Appointment intelligence, AI suggests optimal meeting times, auto-sync prevents double-booking. | P1 — Sprint 5 | Google Calendar API |
| **Google Contacts** | ✅ YES | Contact sync keeps CRM clean, AI has accurate data for matching. | P1 — Sprint 5 | People API |
| **Gmail** | ✅ YES | AI-generated, AI-personalized owner communications. AI company = AI communicates. | P2 — Sprint 6 | Gmail API (send-only scope) |
| **Electronic Signature** | ✅ YES | AI prepares contracts, e-sign closes deals. Full AI-powered transaction lifecycle. | P2 — Sprint 6 | DocuSign / HelloSign API |
| **Google Slides** | ✅ YES | AI generates pitch decks from portfolio data. Presentation = AI-produced. | P2 — Sprint 6 | Google Slides API |
| **WhatsApp Business API (Advanced)** | ✅ YES | AI-powered owner communication at scale. 24/7 AI advisor on WhatsApp. | P1 — Sprint 5 | Meta Business API |
| **Municipality Open Data** | ✅ YES | Enriches AI market intelligence with official data. AI company = AI has better data than competitors. | P2 — Sprint 7 | Municipality APIs (varies by city) |
| **MLSPortal (Emlak365)** | ✅ YES | Distributes AI-curated listings to portals. AI company = AI distributes. | P2 — Sprint 7 | Emlak365 API |
| **Property Valuation AI ( third-party)** | ✅ YES | Complementary to Cortex valuation, not competing. Strengthens overall AI offering. | P3 — Future | Depends on API quality |
| **Bank Integration (Credit)** | ✅ MAYBE | AI mortgage advisor. Only if it strengthens buyer journey AI. Requires legal review. | P3 — Future | Bank APIs |

### 8.2 NOT Recommended (Do Not Strengthen AI Company)

| Rejected Integration | Reason |
|---------------------|--------|
| **Any MLS without AI layer** | Fragmented listings, not AI-aggregated. Weakens unified AI knowledge. |
| **Manual FTP file exchange with portals** | Anti-pattern: manual = not AI company |
| **Basic CRM without AI capabilities** | Yalihan OS IS the CRM. Adding another = fragmented intelligence. |
| **Social media scheduling (Buffer, Hootsuite)** | Scheduling is not AI company work. Yalihan should AI-generate, not schedule posts. |
| **Generic accounting software** | Unless directly feeding AI financial intelligence, not relevant. |

### 8.3 Staged Roadmap

```
Sprint 4 (2026 Q3):
├── Google Drive Integration → Automated portfolio folders
├── Google Sheets Integration → Market data intelligence
├── Canva Integration → Brand-consistent property marketing
└── DLQ Dashboard → Visibility into failed jobs

Sprint 5 (2026 Q3–Q4):
├── NotebookLM Integration → AI-powered market research
├── Google Docs Integration → AI report automation
├── Google Calendar Integration → Appointment intelligence
├── Google Contacts Sync → Clean CRM data
└── WhatsApp Business Advanced → AI owner advisor

Sprint 6 (2026 Q4):
├── Gmail Integration → AI-powered email communications
├── Electronic Signature → AI contract lifecycle
├── Google Slides Integration → AI pitch deck generation
└── Secret Vault Migration → HashiCorp Vault or AWS Secrets Manager

Sprint 7+ (2027):
├── Municipality Open Data → Market intelligence enrichment
├── MLS Portal Distribution → AI-curated listing syndication
└── Bank/Credit Integration → AI mortgage advisor (legal review required)
```

---

## SECTION 9 — CURRENT SYSTEM INTEGRATION MAP

```
╔══════════════════════════════════════════════════════════════════════╗
║                      YALIHAN PLATFORM — INTEGRATION MAP               ║
╠══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  EXTERNAL PLATFORMS              YALIHAN PLATFORM                    ║
║  ════════════════                ════════════════                    ║
║                                                                       ║
║  ┌─────────────┐                 ┌──────────────────────────────┐    ║
║  │ DeepSeek    │───────────────→ │ YalihanCortex                │    ║
║  │ OpenAI      │───────────────→ │   └── AIOrchestrator         │    ║
║  │ Claude      │───────────────→ │       ├── DeepSeekProvider  │    ║
║  │ Gemini      │───────────────→ │       ├── OpenAIProvider     │    ║
║  │ Ollama      │───────────────→ │       ├── GeminiProvider     │    ║
║  └─────────────┘                 └──────────────────────────────┘    ║
║                                      │                               ║
║  ┌─────────────┐                     │ AI Telemetry                 ║
║  │ Context7    │←────────────────────│ (audit + cost tracking)       ║
║  │ MCP         │                     └──────────────────────────────┘    ║
║  └─────────────┘                                                       ║
║                                      │                               ║
║  ┌─────────────┐                     ▼                               ║
║  │ Telegram    │←────────────── ┌─────────────┐                      ║
║  │ WhatsApp    │←──────────────│ n8n         │                      ║
║  │ Instagram   │←──────────────│ Workflows   │                      ║
║  │ Facebook    │←──────────────└─────────────┘                      ║
║  └─────────────┘                                                       ║
║       ↑                                                                   ║
║       │ (Meta Graph API)                                                ║
║       │                                                                   ║
║  ┌─────────────┐                     ┌──────────────────────────────┐    ║
║  │ TKGM        │───────────────→    │ Laravel Horizon               │    ║
║  │ (Gov API)   │                     │  ├── events queue           │    ║
║  └─────────────┘                     │  ├── notifications queue    │    ║
║                                      │  ├── copilot-* queues       │    ║
║  ┌─────────────┐                     │  └── governance queue        │    ║
║  │ Google      │───────────────→    │                              │    ║
║  │ Maps/AI/    │                     │  └── failed_jobs (DLQ)       │    ║
║  │ Speech      │                     └──────────────────────────────┘    ║
║  └─────────────┘                                      │                    ║
║                                                     │                    ║
║  ┌─────────────┐                                      ▼                    ║
║  │ OpenClaw    │←─────────────────── ┌──────────────────────────────┐    ║
║  │ (Agent GW)  │                     │ YalihanCortex              │    ║
║  └─────────────┘                     │   ├── IlanCrudService      │    ║
║                                      │   ├── KisiCrudService      │    ║
║  ┌─────────────┐                     │   ├── TalepService         │    ║
║  │ Field MCP   │───────────────→     │   └── GorevService        │    ║
║  │ (IoT)       │                     └──────────────────────────────┘    ║
║  └─────────────┘                             │                          ║
║                                              ▼                          ║
║                                    ┌──────────────────────┐             ║
║                                    │ MySQL 8              │             ║
║                                    │ yalihanai_test       │             ║
║                                    │ yalihan_market       │             ║
║                                    └──────────────────────┘             ║
║                                                                      ║
║  FUTURE INTEGRATIONS (Sprint 4+):                                     ║
║  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐                   ║
║  │ Google Drive │ │ Google Sheets│ │ Canva       │                   ║
║  │ (Portfolio   │ │ (Market      │ │ (Marketing  │                   ║
║  │  folders)    │ │  data)       │ │  assets)    │                   ║
║  └──────────────┘ └──────────────┘ └──────────────┘                   ║
║  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐                   ║
║  │ NotebookLM   │ │ Google Docs  │ │ Google      │                   ║
║  │ (Research)   │ │ (Reports)    │ │ Calendar    │                   ║
║  └──────────────┘ └──────────────┘ └──────────────┘                   ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝
```

---

## SECTION 10 — GOVERNANCE & STANDARDS REFERENCES

### 10.1 Governing Documents

| Document | Authority | Scope |
|----------|-----------|-------|
| `docs/SAB.md` | SAB Board | Technical constitution, write authority, naming |
| `.sab/authority.json` | Bekçi v2.1 | Governance SSOT, rule enforcement |
| `CONTRIBUTING.md` | Integration Office | Development workflow, integration standards |
| `CLAUDE.md` | All agents | Session guidance, memory strategy |
| This document | Integration Board | External ecosystem governance |

### 10.2 Integration Checklist

Every new integration must pass:

```
□ Integration Proposal Document (IPD) submitted
□ Integration Board review completed
□ Owner assigned (single responsibility)
□ Data contract defined (JSON schema)
□ Auth method specified
□ Retry policy defined
□ DLQ path defined
□ Monitoring dashboard created
□ Cost estimated and budget approved
□ Security review passed
□ Test coverage: unit + integration + failure injection
□ Runbook written
□ Documentation complete (docs/integrations/{name}.md)
□ Ownership matrix updated
□ Bekçi AST scan clean (no new violations)
□ CI/CD gate 1–7 passed
□ Sunset / deprecation plan documented
```

---

## APPENDIX A — Integration Quick Reference

| Integration | Inbound/Outbound | Auth | Owner Service | Retry | DLQ |
|-------------|-----------------|------|---------------|-------|-----|
| DeepSeek | Outbound AI | API Key | DeepSeekCortexProvider | 3× exp | CB opens |
| OpenAI | Outbound AI | API Key | OpenAICortexProvider | 3× exp | CB opens |
| Ollama | Outbound AI | None (local) | OllamaService | 3× exp | CB opens |
| Context7 MCP | Outbound AI | API Key | Context7BridgeService | 3× | Telemetry |
| Telegram | Both | Secret token | TelegramService | 3× | Notify admin |
| WhatsApp | Both | HMAC SHA-256 | WhatsAppWebhookController | 3× | Mark failed |
| Instagram | Both | HMAC SHA-256 | InstagramWebhookController | 3× | Mark failed |
| Facebook | Both | HMAC SHA-256 | FacebookWebhookController | 3× | Mark failed |
| n8n (inbound) | Inbound | X-N8N-SECRET | N8nWebhookController | 3× | Log + skip |
| n8n (outbound) | Outbound | X-Webhook-Token | N8nIntegrationService | 3× | Log + skip |
| TKGM | Outbound | API Key (ENV) | TkgmService | 3× | Alert |
| Google Maps | Outbound | API Key | GoogleMapsService | 3× | Alert |
| Google Speech | Outbound | API Key | VoiceSearchService | 3× | Alert |
| Azure Speech | Outbound | API Key | VoiceSearchService | 3× | Alert |
| Google Drive | Outbound | OAuth 2.0 | DriveIntegrationService (NEW) | 3× | Alert |
| Google Sheets | Outbound | OAuth 2.0 | SheetsIntegrationService (NEW) | 3× | Alert |
| Canva | Both | OAuth 2.0 | CanvaIntegrationService (NEW) | 3× | Alert |
| NotebookLM | Outbound | API Key | NotebookLMService (NEW) | 3× | Alert |
| OpenClaw | Inbound | Bearer token | AuditMcpServer | N/A | N/A |
| Field MCP | Inbound | Auth header | FieldMcpController | 3× | Alert |

---

## APPENDIX B — Acronyms & Glossary

| Acronym | Full Form | Definition |
|---------|-----------|------------|
| **CB** | Circuit Breaker | Pattern that fails fast when provider is unhealthy |
| **DLQ** | Dead Letter Queue | Where failed jobs go after all retries exhausted |
| **IPD** | Integration Proposal Document | Required document for new integrations |
| **RBAC** | Role-Based Access Control | Permission model per role |
| **SAB** | System Architecture Blueprint | Technical constitution of Yalihan OS |
| **SPOF** | Single Point of Failure | One system whose failure breaks the whole flow |
| **CQRS** | Command Query Responsibility Segregation | Separate read/write models for performance |
| **MCP** | Model Context Protocol | AI context sharing protocol |
| **Telemetry** | AI Telemetry | Tracking of AI operations (cost, latency, quality) |
| **IoT** | Internet of Things | Field devices (Bosch GLM, FLIR ONE) |

---

*Integration Blueprint v1.0 — YALIHAN PLATFORM Integration Office*
*Approved by: Integration Board*
*Next Review: 2026-09-28 (Quarterly)*
*Document Owner: Integration Office (this blueprint)*

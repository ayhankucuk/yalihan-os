# AI Workforce Foundation — Capability 2 Design

> **Tarih:** 2026-06-28  
> **Durum:** Design Phase — Implementation Pending  
> **Scope:** Digital Organization, Event Catalog, Agent Registry

---

## 1. ORGANIZATIONAL CHART

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           YALIHAN AI WORKFORCE                               │
│                                                                              │
│   ┌─────────────────────────────────────────────────────────────────────┐    │
│   │                     HERMES (Chief Orchestrator)                      │    │
│   │                                                                       │    │
│   │   Responsibilities:                                                  │    │
│   │   • Route events to specialized agents                               │    │
│   │   • Manage cross-agent workflows                                     │    │
│   │   • Handle escalations and human-in-the-loop                        │    │
│   │   • Maintain session context                                         │    │
│   │   • Coordinate parallel agent execution                              │    │
│   └─────────────────────────────────────────────────────────────────────┘    │
│                                     │                                        │
│           ┌─────────────┬───────────┼───────────┬─────────────┐             │
│           ▼             ▼           ▼           ▼             ▼             │
│   ┌──────────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐  │
│   │  KNOWLEDGE   │ │ MARKET   │ │ LISTING  │ │  CRM     │ │  PUBLISHING  │  │
│   │   DIVISION   │ │INTELLIG. │ │  DIV.    │ │  DIV.    │ │   DIVISION   │  │
│   └──────────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────────┘  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Division Structure

```
KNOWLEDGE DIVISION                    MARKET INTELLIGENCE DIVISION
├── Drive Agent                       ├── Market Scanner Agent
├── Knowledge Agent                   ├── Price Analytics Agent
└── Research Agent                    └── Trend Prediction Agent

LISTING DIVISION                      CRM DIVISION
├── Photo Agent                       ├── Lead Intake Agent
├── Description Agent                 ├── Matching Agent
├── Readiness Agent                   ├── Follow-up Agent
└── Portfolio Agent                   └── Communication Agent

PUBLISHING DIVISION                   OPERATIONS DIVISION
├── Channel Agent                     ├── Notification Agent
├── Calendar Agent                    ├── Finance Agent
└── Status Agent                      └── Audit Agent
```

---

## 2. AGENT REGISTRY

### 2.1 Hermes (Chief Orchestrator)

```yaml
id: hermes
name: Hermes
type: orchestrator
division: none
priority: critical

responsibilities:
  - Event routing and distribution
  - Cross-agent workflow coordination
  - Human-in-the-loop escalation
  - Session context maintenance
  - Parallel execution orchestration
  - Error handling and recovery

inputs:
  - All business events
  - Agent status updates
  - Human decisions/approvals
  - System health metrics

outputs:
  - Routed events to agents
  - Workflow instructions
  - Escalation requests
  - Status summaries

events_listens:
  - "*" (all events)

events_emits:
  - agent.assigned
  - agent.completed
  - agent.failed
  - human.escalation.required
  - workflow.coordinated

knowledge_sources:
  - Workflow definitions
  - Agent capabilities registry
  - Human approval policies
  - SLA definitions

integrations:
  - Telegram (escalation)
  - n8n (workflow automation)
  - YalihanCortex (AI coordination)

escalation_rules:
  - High-value listing (>5M TL): human approval required
  - Price reduction >10%: human approval required
  - New lead assignment: notification only
  - System errors: automated recovery attempt first
```

### 2.2 Drive Agent

```yaml
id: drive-agent
name: Drive Agent
type: specialist
division: knowledge
priority: high

responsibilities:
  - Document organization and storage
  - Version control for knowledge artifacts
  - Folder structure maintenance
  - Access control management
  - Archive management

inputs:
  - Generated documents (descriptions, reports)
  - Research artifacts
  - Market snapshots
  - Legal documents
  - Municipality files

outputs:
  - Stored documents (Drive)
  - Folder paths
  - Share links
  - Version history

events_listens:
  - portfolio.created
  - listing.draft.completed
  - market.snapshot.generated
  - research.completed
  - document.uploaded

events_emits:
  - document.stored
  - document.shared
  - archive.created

knowledge_sources:
  - Folder templates
  - Naming conventions
  - Retention policies

integrations:
  - Google Drive API
  - Google Sheets API
```

### 2.3 Knowledge Agent

```yaml
id: knowledge-agent
name: Knowledge Agent
type: specialist
division: knowledge
priority: high

responsibilities:
  - Semantic understanding via NotebookLM
  - Knowledge graph maintenance
  - SOP retrieval and updates
  - Cross-reference management
  - Query answering from knowledge base

inputs:
  - User queries
  - Document embeddings
  - SOP requests
  - Cross-reference searches

outputs:
  - Relevant knowledge snippets
  - SOP recommendations
  - Related documents
  - Confidence scores

events_listens:
  - knowledge.query
  - sop.requested
  - document.analyzed
  - user.question

events_emits:
  - knowledge.retrieved
  - sop.recommended
  - cross.reference.found

knowledge_sources:
  - NotebookLM index
  - SOP database
  - Municipality zoning plans
  - Legal documents

integrations:
  - NotebookLM API
  - Google Drive
  - Custom knowledge DB
```

### 2.4 Market Scanner Agent

```yaml
id: market-scanner
name: Market Scanner Agent
type: specialist
division: market_intelligence
priority: critical

responsibilities:
  - Monitor selected markets continuously
  - Track new listings across channels
  - Detect price changes
  - Identify comparable properties
  - Alert on market anomalies

inputs:
  - Market definition (location, price range, type)
  - Channel feeds (web scraping, APIs)
  - Historical baseline data
  - Alert thresholds

outputs:
  - New listing alerts
  - Price change notifications
  - Market summary reports
  - Comparable analysis

events_listens:
  - market.scan.scheduled
  - market.alert.threshold
  - channel.updated

events_emits:
  - market.listing.detected
  - market.price.changed
  - market.alert.triggered
  - market.report.generated

knowledge_sources:
  - Historical listing database
  - Comparable property records
  - Market baseline metrics

integrations:
  - Web scraping (selected sites)
  - Google Sheets (market data)
  - Google Drive (archives)
```

### 2.5 Price Analytics Agent

```yaml
id: price-analytics
name: Price Analytics Agent
type: specialist
division: market_intelligence
priority: high

responsibilities:
  - Price history tracking
  - Price per m² analysis
  - Market value estimation
  - Pricing recommendations
  - Discount rate analysis

inputs:
  - Listing data (size, location, features)
  - Historical price data
  - Comparable sales
  - Market trends

outputs:
  - Estimated market value
  - Price per m² benchmark
  - Pricing recommendation
  - Days on market analysis

events_listens:
  - listing.created
  - listing.updated
  - price.change.detected
  - comparable.found

events_emits:
  - valuation.completed
  - price.recommended
  - benchmark.updated

knowledge_sources:
  - Historical sales data
  - Comparable listings
  - Price trend models
  - Location benchmarks

integrations:
  - Google Sheets
  - Internal pricing models
```

### 2.6 Photo Agent

```yaml
id: photo-agent
name: Photo Agent
type: specialist
division: listing
priority: high

responsibilities:
  - Photo upload and organization
  - Quality assessment
  - Watermark application
  - Image optimization
  - Gallery ordering

inputs:
  - Raw photos from upload
  - Property ID
  - Watermark settings
  - Display order preferences

outputs:
  - Processed images
  - Optimized thumbnails
  - Watermarked versions
  - Gallery configuration

events_listens:
  - photo.uploaded
  - photo.processing.requested
  - photo.reordered

events_emits:
  - photo.processed
  - photo.optimized
  - photo.watermarked
  - gallery.ready

knowledge_sources:
  - Photo standards (size, format)
  - Watermark templates
  - Display optimization rules

integrations:
  - Yalihan OS photo storage
  - Image processing service
```

### 2.7 Description Agent

```yaml
id: description-agent
name: Description Agent
type: specialist
division: listing
priority: critical

responsibilities:
  - AI-powered description generation
  - Multi-language translation
  - SEO optimization
  - Feature highlighting
  - Tone consistency

inputs:
  - Property data (features, location, price)
  - Language preference
  - Tone guidelines
  - SEO keywords

outputs:
  - Generated description (TR/EN/RU/AR/DE/FR)
  - SEO metadata
  - Feature highlights
  - Draft for human review

events_listens:
  - listing.ready.for.description
  - description.regeneration.requested
  - translation.requested

events_emits:
  - description.generated
  - description.translated
  - description.pending.review

knowledge_sources:
  - Description templates
  - SEO guidelines
  - Property type vocabulary
  - Tone guidelines

integrations:
  - YalihanCortex
  - Translation pipeline
```

### 2.8 Readiness Agent

```yaml
id: readiness-agent
name: Readiness Agent
type: specialist
division: listing
priority: high

responsibilities:
  - Listing completeness check
  - Photo requirements validation
  - Required field verification
  - Quality score calculation
  - Publish readiness determination

inputs:
  - Listing data
  - Photo gallery
  - Description draft
  - Channel requirements

outputs:
  - Readiness score (0-100)
  - Missing items list
  - Quality indicators
  - Publish recommendation

events_listens:
  - listing.updated
  - listing.submitted
  - readiness.check.requested

events_emits:
  - readiness.evaluated
  - readiness.approved
  - readiness.failed
  - missing.items.identified

knowledge_sources:
  - Channel requirements matrix
  - Quality standards
  - Minimum requirements per channel
```

### 2.9 Portfolio Agent

```yaml
id: portfolio-agent
name: Portfolio Agent
type: specialist
division: listing
priority: critical

responsibilities:
  - Portfolio creation and management
  - Property aggregation
  - Portfolio performance tracking
  - Portfolio analytics
  - Owner overview generation

inputs:
  - Property collection
  - Owner information
  - Performance metrics
  - Portfolio preferences

outputs:
  - Portfolio summary
  - Performance dashboard data
  - Owner report
  - Analytics snapshot

events_listens:
  - portfolio.created
  - portfolio.updated
  - property.added
  - property.removed

events_emits:
  - portfolio.created
  - portfolio.analytics.updated
  - owner.report.generated

knowledge_sources:
  - Portfolio templates
  - Performance benchmarks
  - Owner preferences
```

### 2.10 Lead Intake Agent

```yaml
id: lead-intake
name: Lead Intake Agent
type: specialist
division: crm
priority: critical

responsibilities:
  - Lead capture and validation
  - Contact information extraction
  - Source tracking
  - Initial categorization
  - Auto-response triggers

inputs:
  - Incoming lead data (form, Telegram, etc.)
  - Contact information
  - Source attribution
  - Preferences stated

outputs:
  - Validated lead record
  - Contact profile
  - Category assignment
  - Response triggers

events_listens:
  - lead.received
  - contact.submitted
  - inquiry.received

events_emits:
  - lead.created
  - lead.categorized
  - response.sent
  - matching.initiated

knowledge_sources:
  - Lead categories
  - Response templates
  - Validation rules
```

### 2.11 Matching Agent

```yaml
id: matching-agent
name: Matching Agent
type: specialist
division: crm
priority: critical

responsibilities:
  - Buyer-property matching
  - Compatibility scoring
  - Match recommendations
  - Match history tracking
  - Re-matching on updates

inputs:
  - Lead preferences
  - Available listings
  - Match history
  - Priority weights

outputs:
  - Match recommendations (ranked)
  - Compatibility scores
  - Match reasons
  - Next action suggestions

events_listens:
  - lead.created
  - listing.created
  - matching.requested
  - preferences.updated

events_emits:
  - match.found
  - match.recommended
  - match.rejected
  - rematch.needed

knowledge_sources:
  - Buyer preference profiles
  - Property features database
  - Match success history
  - Weighting algorithms
```

### 2.12 Follow-up Agent

```yaml
id: followup-agent
name: Follow-up Agent
type: specialist
division: crm
priority: high

responsibilities:
  - Follow-up scheduling
  - Reminder management
  - Communication sequencing
  - Status update tracking
  - Escalation triggers

inputs:
  - Lead/contact data
  - Follow-up rules
  - Communication history
  - Escalation thresholds

outputs:
  - Scheduled follow-ups
  - Reminder notifications
  - Communication queue
  - Escalation alerts

events_listens:
  - followup.due
  - contact.activity
  - response.received
  - status.change

events_emits:
  - followup.scheduled
  - reminder.sent
  - escalation.triggered
  - status.updated

knowledge_sources:
  - Follow-up templates
  - SLA definitions
  - Escalation rules
```

### 2.13 Channel Agent

```yaml
id: channel-agent
name: Channel Agent
type: specialist
division: publishing
priority: critical

responsibilities:
  - Multi-channel publishing
  - Channel-specific formatting
  - Publishing schedule management
  - Channel status monitoring
  - Cross-posting coordination

inputs:
  - Listing content
  - Channel configurations
  - Publishing schedule
  - Format requirements

outputs:
  - Published listings
  - Channel status
  - Publishing log
  - Error reports

events_listens:
  - listing.approved
  - listing.ready
  - publishing.requested
  - schedule.updated

events_emits:
  - listing.published
  - listing.updated
  - publishing.failed
  - channel.status.changed

knowledge_sources:
  - Channel configurations
  - Format requirements per channel
  - Publishing windows
  - Rate limits

integrations:
  - Airbnb API
  - Web channels
  - Social media APIs
```

### 2.14 Notification Agent

```yaml
id: notification-agent
name: Notification Agent
type: specialist
division: operations
priority: high

responsibilities:
  - Multi-channel notifications
  - Priority-based routing
  - Template management
  - Delivery tracking
  - Unsubscribe handling

inputs:
  - Notification content
  - Recipient list
  - Priority level
  - Channel preferences

outputs:
  - Sent notifications
  - Delivery status
  - Open tracking
  - Error handling

events_listens:
  - notification.requested
  - alert.triggered
  - status.change
  - reminder.due

events_emits:
  - notification.sent
  - notification.delivered
  - notification.failed

integrations:
  - Telegram Bot API
  - Email (Gmail)
  - SMS (future)
```

### 2.15 Finance Agent

```yaml
id: finance-agent
name: Finance Agent
type: specialist
division: operations
priority: high

responsibilities:
  - Transaction recording
  - Payment tracking
  - Commission calculation
  - Financial reporting
  - Budget monitoring

inputs:
  - Transaction events
  - Payment data
  - Commission rules
  - Budget parameters

outputs:
  - Recorded transactions
  - Commission calculations
  - Financial reports
  - Budget alerts

events_listens:
  - transaction.completed
  - payment.received
  - commission.calculated
  - budget.threshold

events_emits:
  - transaction.recorded
  - commission.calculated
  - financial.report.generated
  - budget.alert

knowledge_sources:
  - Commission rules
  - Tax regulations
  - Budget parameters

integrations:
  - Yalihan OS Finance Domain
  - Banking APIs (future)
```

---

## 3. EVENT CATALOG

### 3.1 Event Structure

```yaml
event:
  id: "evt-{timestamp}-{uuid}"
  type: "domain.event.name"
  timestamp: "ISO-8601"
  source: "agent-id or system"
  tenant_id: "string"
  correlation_id: "string"
  payload:
    # Event-specific data
  metadata:
    version: "1.0"
    trace_id: "string"
```

### 3.2 Core Events

#### Portfolio Events
```yaml
portfolio.created:
  description: "New portfolio was created"
  payload:
    - portfolio_id
    - owner_id
    - name
    - property_ids
  emitted_by: portfolio-agent

portfolio.updated:
  description: "Portfolio details changed"
  payload:
    - portfolio_id
    - changes
  emitted_by: portfolio-agent

portfolio.analytics.updated:
  description: "Portfolio metrics recalculated"
  payload:
    - portfolio_id
    - metrics
  emitted_by: portfolio-agent
```

#### Listing Events
```yaml
listing.created:
  description: "New listing created"
  payload:
    - listing_id
    - portfolio_id
    - status
  emitted_by: portfolio-agent

listing.updated:
  description: "Listing data changed"
  payload:
    - listing_id
    - changes
    - changed_by
  emitted_by: listing services

listing.ready.for.description:
  description: "Listing ready for AI description generation"
  payload:
    - listing_id
    - property_data
  emitted_by: readiness-agent

listing.draft.completed:
  description: "AI description draft ready"
  payload:
    - listing_id
    - draft
    - languages
  emitted_by: description-agent

listing.pending.review:
  description: "Human review required"
  payload:
    - listing_id
    - draft
    - review_type
  emitted_by: description-agent

listing.approved:
  description: "Listing approved for publishing"
  payload:
    - listing_id
    - approved_by
  emitted_by: human (via UI)

listing.published:
  description: "Listing published to channel"
  payload:
    - listing_id
    - channel
    - published_url
  emitted_by: channel-agent
```

#### Photo Events
```yaml
photo.uploaded:
  description: "New photo uploaded"
  payload:
    - listing_id
    - photo_id
    - original_path
  emitted_by: photo upload service

photo.processed:
  description: "Photo processing completed"
  payload:
    - photo_id
    - processed_path
    - thumbnail_path
  emitted_by: photo-agent

photo.watermarked:
  description: "Watermark applied"
  payload:
    - photo_id
    - watermarked_path
  emitted_by: photo-agent

gallery.ready:
  description: "Photo gallery ready for display"
  payload:
    - listing_id
    - photo_count
    - display_order
  emitted_by: photo-agent
```

#### Market Events
```yaml
market.scan.scheduled:
  description: "Scheduled market scan triggered"
  payload:
    - scan_id
    - markets
  emitted_by: scheduler

market.listing.detected:
  description: "New listing detected in market"
  payload:
    - source
    - listing_data
    - similarity_score
  emitted_by: market-scanner

market.price.changed:
  description: "Price change detected"
  payload:
    - listing_id
    - old_price
    - new_price
    - change_percent
  emitted_by: market-scanner

market.alert.triggered:
  description: "Market alert threshold reached"
  payload:
    - alert_type
    - market
    - value
    - threshold
  emitted_by: market-scanner

valuation.completed:
  description: "Property valuation finished"
  payload:
    - listing_id
    - estimated_value
    - confidence
    - comparables
  emitted_by: price-analytics
```

#### CRM Events
```yaml
lead.received:
  description: "New lead submitted"
  payload:
    - lead_id
    - source
    - contact_data
    - preferences
  emitted_by: lead intake service

lead.created:
  description: "Lead validated and stored"
  payload:
    - lead_id
    - contact_id
    - category
  emitted_by: lead-intake-agent

match.found:
  description: "Property-lead match identified"
  payload:
    - match_id
    - lead_id
    - listing_ids
    - scores
  emitted_by: matching-agent

match.recommended:
  description: "Match sent to advisor"
  payload:
    - match_id
    - recommendation
  emitted_by: matching-agent

followup.due:
  description: "Follow-up task due"
  payload:
    - followup_id
    - contact_id
    - type
  emitted_by: scheduler

escalation.triggered:
  description: "Case escalated for attention"
  payload:
    - escalation_id
    - reason
    - priority
  emitted_by: followup-agent
```

#### System Events
```yaml
human.escalation.required:
  description: "Human decision needed"
  payload:
    - escalation_id
    - context
    - options
    - deadline
  emitted_by: hermes

agent.assigned:
  description: "Task assigned to agent"
  payload:
    - task_id
    - agent_id
    - priority
  emitted_by: hermes

agent.completed:
  description: "Agent task finished"
  payload:
    - task_id
    - agent_id
    - result
  emitted_by: any agent

agent.failed:
  description: "Agent task failed"
  payload:
    - task_id
    - agent_id
    - error
    - retry_count
  emitted_by: any agent

document.stored:
  description: "Document saved to Drive"
  payload:
    - document_id
    - path
    - type
  emitted_by: drive-agent

notification.sent:
  description: "Notification delivered"
  payload:
    - notification_id
    - channel
    - recipient
    - status
  emitted_by: notification-agent
```

---

## 4. INTERACTION DIAGRAMS

### 4.1 Portfolio Creation Flow

```
User                    UI                    Portfolio Agent              Photo Agent              Description Agent            Readiness Agent
  │                      │                          │                          │                          │                          │
  │── Create Portfolio ──►                          │                          │                          │                          │
  │                      │── Validate & Store ─────►│                          │                          │                          │
  │                      │◄─ Portfolio Created ─────│                          │                          │                          │
  │                      │── Emit: portfolio.created                          │                          │                          │
  │                      │                          │                          │                          │                          │
  │                      │                          │◄── photo.uploaded ────────────────────────────│                          │
  │                      │                          │──► Process Photos ─────────────────────────►│                          │
  │                      │                          │◄── gallery.ready ───────────────────────────│                          │
  │                      │                          │                          │                          │                          │
  │                      │                          │── Emit: listing.ready.for.description ──────────────────────────────►│
  │                      │                          │                          │── Generate Description ─────────────────────────►│
  │                      │                          │                          │◄── description.generated ───────────────────────│
  │                      │                          │                          │── Emit: description.pending.review ────────────►│
  │                      │                          │                          │                          │◄── Evaluate Readiness ──│
  │                      │                          │                          │                          │──► readiness.score ───►│
  │                      │◄──────────────────────────────────────────────────────────────────────────│                          │
  │◄─ Show Dashboard ────│                          │                          │                          │                          │
```

### 4.2 Lead-to-Match Flow

```
Lead Intake           Hermes              Lead Intake Agent         Matching Agent           Follow-up Agent
   │                    │                        │                        │                        │
   │── Lead Received ──►│                        │                        │                        │
   │                    │── Route to agent ─────►│                        │                        │
   │                    │                        │── Validate ────────────►│                        │
   │                    │                        │◄── Validated ───────────│                        │
   │                    │                        │                        │                        │
   │                    │                        │── Emit: lead.created                          │
   │                    │                        │── Start matching ──────►│                        │
   │                    │                        │                        │── Find matches ────────►│
   │                    │                        │                        │◄── Matches found ───────│
   │                    │                        │                        │── Emit: match.recommended
   │                    │                        │                        │                        │── Schedule follow-up
   │                    │                        │                        │                        │──► Emit: followup.scheduled
```

### 4.3 Market Intelligence Flow

```
Market Scanner        Price Analytics         Hermes              Notification Agent
    │                      │                    │                        │
    │── Scheduled Scan ────│                    │                        │
    │── Detect new listing ─│                    │                        │
    │                      │── Price analysis ──►│                        │
    │                      │◄── Valuation ───────│                        │
    │                      │                    │                        │
    │── Price change alert ─────────────────────►│── Route ──────────────►│
    │                      │                    │                        │── Send Telegram
    │                      │                    │                        │──► Emit: notification.sent
```

### 4.4 Human Escalation Flow

```
Agent                 Hermes                 Telegram               User
  │                      │                      │                      │
  │── Escalation needed ─│                      │                      │
  │                      │── Format message ────►│                      │
  │                      │                      │── Send to user ──────►│
  │                      │                      │                      │
  │                      │                      │◄── User decision ─────│
  │                      │◄── Decision received ─│                      │
  │◄── Execute decision ─│                      │                      │
  │── Complete task ─────│                      │                      │
```

---

## 5. CAPABILITY ROADMAP

### Phase 1: Foundation (Sprint 4-5)

```
Timeline: 2-3 sprints
Goal: Core infrastructure and first agents

Deliverables:
├── Hermes Core
│   ├── Event bus implementation
│   ├── Basic routing logic
│   └── Agent registry
├── First 4 Agents
│   ├── Portfolio Agent
│   ├── Photo Agent
│   ├── Description Agent (extend existing)
│   └── Notification Agent
├── Basic Event Catalog
│   ├── 15 core events
│   └── Event schema definition
└── Integration Points
    ├── n8n connection
    └── Telegram bot
```

### Phase 2: CRM Integration (Sprint 6-7)

```
Timeline: 2 sprints
Goal: Lead management and matching

Deliverables:
├── Lead Intake Agent
├── Matching Agent
├── Follow-up Agent
├── Lead-to-Match Flow
└── CRM Event Expansion
```

### Phase 3: Market Intelligence (Sprint 8-9)

```
Timeline: 2 sprints
Goal: Continuous market monitoring

Deliverables:
├── Market Scanner Agent
├── Price Analytics Agent
├── Trend Prediction Agent
├── Google Sheets Integration
└── Drive Archive System
```

### Phase 4: Knowledge Layer (Sprint 10-11)

```
Timeline: 2 sprints
Goal: Semantic knowledge management

Deliverables:
├── Drive Agent
├── Knowledge Agent
├── SOP Retrieval System
├── NotebookLM Integration
└── Knowledge Graph
```

### Phase 5: Publishing Automation (Sprint 12-13)

```
Timeline: 2 sprints
Goal: Multi-channel publishing

Deliverables:
├── Channel Agent
├── Airbnb Integration
├── Calendar Agent
├── Publishing Schedule Manager
└── Cross-Post Coordinator
```

### Phase 6: Advanced Orchestration (Sprint 14+)

```
Timeline: 3+ sprints
Goal: Sophisticated coordination

Deliverables:
├── Complex workflow orchestration
├── Predictive lead scoring
├── Dynamic pricing recommendations
├── Automated market response
└── Full self-service portal
```

---

## 6. LONG-TERM ARCHITECTURE

### 6.1 System Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              YALIHAN PLATFORM                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                         INTEGRATION LAYER                            │    │
│  │                                                                       │    │
│  │   ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  │    │
│  │   │Telegram │  │  Gmail  │  │  Drive  │  │  n8n    │  │Airbnb   │  │    │
│  │   │  Bot    │  │   API   │  │   API   │  │Webhook  │  │   API   │  │    │
│  │   └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘  │    │
│  │        └────────────┴────────────┴────────────┴────────────┘        │    │
│  └─────────────────────────────────────┬───────────────────────────────┘    │
│                                        │                                      │
│  ┌─────────────────────────────────────▼───────────────────────────────┐    │
│  │                         HERMES ORCHESTRATOR                          │    │
│  │                                                                       │    │
│  │   ┌─────────────────────────────────────────────────────────────┐   │    │
│  │   │  Event Bus    │  Agent Registry  │  Workflow Engine  │ SLA │   │    │
│  │   └─────────────────────────────────────────────────────────────┘   │    │
│  └─────────────────────────────────────┬───────────────────────────────┘    │
│                                        │                                      │
│  ┌─────────────────────────────────────▼───────────────────────────────┐    │
│  │                        AGENT WORKFORCE                               │    │
│  │                                                                       │    │
│  │   ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │    │
│  │   │Knowledge │ │ Market   │ │ Listing  │ │   CRM    │ │Publishing│  │    │
│  │   │ Division │ │ Intel.   │ │ Division │ │ Division │ │ Division │  │    │
│  │   └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘  │    │
│  │                                                                       │    │
│  └─────────────────────────────────────┬───────────────────────────────┘    │
│                                        │                                      │
│  ┌─────────────────────────────────────▼───────────────────────────────┐    │
│  │                         KNOWLEDGE LAYER                              │    │
│  │                                                                       │    │
│  │   ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  │    │
│  │   │ Notebook│  │  Drive  │  │   SOP   │  │ Market  │  │ Legal   │  │    │
│  │   │   LM    │  │ Archive │  │  DB     │  │ Sheets  │  │ Docs    │  │    │
│  │   └─────────┘  └─────────┘  └─────────┘  └─────────┘  └─────────┘  │    │
│  │                                                                       │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Agent Communication Protocol

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          AGENT COMMUNICATION                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. Event Publishing                                                        │
│     Agent ── Publish Event ──► Event Bus                                    │
│                                                                              │
│  2. Event Subscription                                                      │
│     Agent ── Subscribe ──► Event Bus ◄── Filter by event type               │
│                                                                              │
│  3. Direct Messaging (for complex workflows)                                │
│     Agent A ── Message ──► Hermes ── Route ──► Agent B                       │
│                                                                              │
│  4. Response Handling                                                       │
│     Agent ── Response ──► Hermes ── Correlation ID ──► Original Agent        │
│                                                                              │
│  5. Escalation                                                              │
│     Agent ── Escalation ──► Hermes ── Format ──► Telegram ──► Human          │
│                           ◄── Decision ── Execute ──► Agent                  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 6.3 Data Flow Architecture

```
KNOWLEDGE LIFECYCLE:

Raw Data ──► Processing ──► Storage ──► Retrieval ──► Action

Raw Data Sources:
├── Municipality documents
├── Market feeds
├── Property listings
├── Legal documents
└── User inputs

Processing:
├── NotebookLM for semantic understanding
├── AI parsing for structure extraction
├── Human review for validation
└── Automated categorization

Storage:
├── Google Drive (long-term archive)
├── Google Sheets (structured data)
├── Internal DB (operational data)
└── Vector DB (semantic search)

Retrieval:
├── Query-based search
├── Context-aware recommendations
└── Proactive alerts

Action:
├── Agent recommendations
├── Automated workflows
└── Human decision support
```

### 6.4 Scaling Considerations

```
SHORT TERM (Current):
├── Single Hermes instance
├── 15 agents max
├── Synchronous event processing
└── Local event bus

MEDIUM TERM (Phase 3+):
├── Hermes cluster (2-3 instances)
├── 30+ agents
├── Async event processing
├── Redis-backed event bus
└── Basic load balancing

LONG TERM (Phase 5+):
├── Distributed Hermes mesh
├── 100+ agents
├── Event streaming (Kafka/SQS)
├── Horizontal scaling
├── Geographic distribution
└── Multi-tenant support
```

---

## 7. IMPLEMENTATION PRIORITY MATRIX

| Agent | Priority | Effort | Impact | Phase |
|-------|----------|--------|--------|-------|
| Hermes Core | P0 | High | Critical | 1 |
| Portfolio Agent | P0 | Medium | High | 1 |
| Photo Agent | P0 | Medium | High | 1 |
| Description Agent | P0 | Low | High | 1 |
| Notification Agent | P0 | Low | High | 1 |
| Readiness Agent | P1 | Medium | High | 1 |
| Lead Intake Agent | P1 | Medium | High | 2 |
| Matching Agent | P1 | High | High | 2 |
| Follow-up Agent | P1 | Medium | Medium | 2 |
| Market Scanner | P2 | High | High | 3 |
| Price Analytics | P2 | High | Medium | 3 |
| Drive Agent | P2 | Medium | Medium | 4 |
| Knowledge Agent | P2 | High | Medium | 4 |
| Channel Agent | P2 | High | High | 5 |
| Calendar Agent | P3 | Medium | Low | 5 |

---

## 8. NEXT STEPS

### Immediate (This Session)
1. ✅ Design document completed
2. ⏳ Hermes architecture specification
3. ⏳ Event bus interface definition

### Sprint 4 Start
1. Implement Hermes core event bus
2. Create agent registry
3. Implement first 4 agents
4. Connect to n8n workflows
5. Set up Telegram escalation

### Validation Criteria
- All events traceable through system
- Agent failures don't cascade
- Human escalation works reliably
- Response time < 2 seconds for sync operations
- Event processing latency < 5 seconds

---

---

## 9. SAAB STRATEGIC RECOMMENDATIONS (2026-06-28)

### Executive Decision
**APPROVED ✅ — Score: 9.97/10**

> "Artık bundan sonra geliştirilecek her Capability bunun üzerine oturacak."

### Key Strategic Shifts

| From | To |
|------|-----|
| Feature development | Business Capability development |
| Agent Registry | **AI Workforce HR** |
| Event Catalog | **Event Bus as Platform Core** |
| Documentation | **Corporate Memory** |
| NotebookLM (single) | **Corporate Knowledge Structure** |

---

### SAAB Recommendation 01: Corporate Memory

> "Drive artık dosya sistemi değildir. Corporate Memory'dir."

```
Google Drive Structure:
├── 01-Corporate/           # Şirket bilgileri
├── 02-Legal/              # Hukuki dokümanlar
├── 03-Municipality/       # Belediye ve imar
├── 04-Market/             # Piyasa araştırmaları
├── 05-Portfolio/          # Portföy geçmişi
├── 06-Airbnb/             # Airbnb operasyonları
├── 07-Finance/            # Finansal raporlar
├── 08-Sales/              # Satış dokümanları
├── 09-Marketing/          # Pazarlama materyalleri
├── 10-SOP/                # Standart prosedürler
├── 11-AI-Workforce/       # Agent dokümantasyonu
└── 12-Training/           # Eğitim materyalleri
```

---

### SAAB Recommendation 02: NotebookLM Corporate Structure

```
NotebookLM Knowledge Base:
├── Corporate Knowledge
│   ├── Company Policies
│   ├── Brand Guidelines
│   └── Org Structure
│
├── Legal
│   ├── Contracts
│   ├── Regulations
│   └── Compliance
│
├── Municipality
│   ├── Zoning Plans
│   ├── Permits
│   └── District Rules
│
├── Market
│   ├── Bodrum Analysis
│   ├── Comparable Sales
│   └── Trend Reports
│
├── Portfolio
│   ├── Property History
│   ├── Owner Profiles
│   └── Transaction Records
│
├── SOP
│   ├── Listing Pipeline
│   ├── Lead Management
│   └── Customer Service
│
├── Finance
│   ├── Pricing Models
│   ├── Commission Rules
│   └── Budget Templates
│
├── Marketing
│   ├── Content Templates
│   ├── Social Media
│   └── Campaign Plans
│
├── AI Workforce
│   ├── Agent Specifications
│   ├── Event Definitions
│   └── Workflow Documentation
│
└── Training
    ├── Onboarding
    ├── Product Training
    └── Process Videos
```

---

### SAAB Recommendation 03: Agent Personnel Files

> "Her Agent'ın Personnel Dosyası olsun."

**See:** `docs/AI_WORKFORCE_HR.md` — Full personnel files for all 15 agents

Each agent has:
- Employee ID, name, codename
- Division, manager, department head
- Mission, responsibilities, boundaries
- Knowledge sources, notebooks
- Events listens/emits
- Integrations
- KPIs with targets and current values
- Training history
- Status, health score, uptime

---

### SAAB Recommendation 04: AI Workforce HR

> "Agent Registry demem. AI Workforce HR derim."

**Reason:** These are employees, not just code modules.

| HR Function | Agent Equivalent |
|-------------|------------------|
| Employee ID | Agent ID (kebab-case) |
| Job Title | Agent Name |
| Department | Division |
| Manager | Hermes |
| Performance Review | KPI Analysis |
| Training Plan | Skill Development |
| Onboarding | New Agent Setup |
| Offboarding | Archive & Cleanup |

---

### SAAB Recommendation 05: Event Bus as Platform Core

> "Event Bus platformun merkezi olmalı."

```
Event Bus Architecture:

PortfolioCreated
        │
        ▼
    [Event Bus]
        │
        ├──► Hermes
        │        │
        │        ├──► Drive Agent ──► Document to Drive
        │        ├──► Photo Agent ──► Process images
        │        ├──► Description Agent ──► Generate AI draft
        │        ├──► Readiness Agent ──► Evaluate completeness
        │        └──► Notification Agent ──► Alert advisor
        │
        └──► OpenClaw (monitoring)
```

**OpenClaw Role:** Monitors event bus, tracks agent performance, triggers alerts.

---

### SAAB Recommendation 06: Standard Agent Contract

> "Her Agent aynı API'yi kullanmamalı. Standard Agent Contract olsun."

```yaml
Standard Agent Contract v1.0:

Input:
  - event_type: string
  - payload: object
  - correlation_id: string
  - context: object (optional)
  - knowledge_query: object (optional)

Processing Pipeline:
  1. Receive event
  2. Load context from Hermes
  3. Query relevant Knowledge
  4. Make decision/calculation
  5. Execute action
  6. Emit result event

Output:
  - status: success | failure | partial
  - event_emitted: string
  - result: object (optional)
  - recommendations: array (optional)

Error Handling:
  - Log error
  - Emit agent.failed event
  - Request retry or escalate to Hermes
```

---

### SAAB Recommendation 07: AI Workforce Dashboard

> "Dashboard'da şunu görmek isteyeceksin:"

```
┌─────────────────────────────────────────────────────────────────┐
│                    AI WORKFORCE DASHBOARD                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  HERMES                           Today's Jobs                   │
│  Status: ● Healthy               ┌────────────────────────┐     │
│  Uptime: 99.9%                   │ Total: 184             │     │
│  Avg Latency: 3.2s               │ Completed: 179         │     │
│                                  │ Failed: 5              │     │
│                                  └────────────────────────┘     │
│                                                                  │
│  DIVISION STATUS                                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Knowledge      │ Market Intel │ Listing │ CRM │ Publish │   │
│  │ ● 2/2 Active   │ ● 2/2 Active │ ● 4/4   │ ● 3/3│ ● 1/1  │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
│  KNOWLEDGE USAGE                                                 │
│  Drive: 2.4 GB │ NotebookLM: 12 Notebooks │ Sheets: 847 rows    │
│                                                                  │
│  TOP AGENTS BY WORKLOAD                                          │
│  1. Market Scanner: 45 jobs                                      │
│  2. Matching Agent: 38 jobs                                      │
│  3. Photo Agent: 32 jobs                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

### SAAB Recommendation 08: Knowledge Layer Growth

```
Knowledge Layer Structure:

Knowledge Layer/
├── Corporate/            # Şirket bilgileri
├── Legal/               # Hukuki dokümanlar
├── Municipality/        # Belediye ve imar
├── Market/              # Piyasa verileri
├── Airbnb/              # Airbnb operasyonları
├── Finance/             # Finansal veriler
├── Sales/               # Satış pipeline
├── Marketing/           # Pazarlama
├── SOP/                 # Prosedürler
├── AI-Workforce/        # Agent dokümantasyonu
└── Training/            # Eğitim
```

---

### SAAB Recommendation 09: Presentation Studio

> "Yarın PDF, Slides, Canva, Broşür, Instagram, Teklif aynı yerden üretilecek."

```
Presentation Studio Pipeline:

Property Data
        │
        ▼
┌───────────────────┐
│   Content Engine  │  (Description Agent)
└─────────┬─────────┘
          │
          ▼
┌───────────────────┐
│  Format Adapter   │
└─────────┬─────────┘
          │
    ┌─────┼─────┬─────────┬─────────┐
    ▼     ▼     ▼         ▼         ▼
  PDF   Slides Canva   Instagram  Proposal
```

---

### SAAB Recommendation 10: Corporate Ontology

> "En kritik eksik: Corporate Ontology"

**See:** `docs/CORPORATE_ONTOLOGY.md` — Platform vocabulary

Core entities with single, canonical definitions:
- Portfolio, Property, Owner, Buyer, Lead, Match
- Agent, Hermes, Division
- Knowledge, SOP, MarketData
- Event, Workflow, Task
- Channel, Capability

---

### SAAB Strategic Conclusion

> "CRM → AI CRM → AI Platform → Digital Company"

**This is the transformation that happened today.**

**Every technical decision should be tested with:**

> "Bu karar, YALIHAN PLATFORM'un dijital şirket vizyonunu güçlendiriyor mu?"

---

## 10. RELATED DOCUMENTS

| Document | Description |
|----------|-------------|
| `docs/CORPORATE_ONTOLOGY.md` | Platform vocabulary and entity definitions |
| `docs/AI_WORKFORCE_HR.md` | Agent personnel files and HR operations |
| `docs/AI_WORKFORCE_DASHBOARD.md` | Dashboard specification (future) |
| `knowledge/AI_WORKFORCE/` | Agent-specific knowledge base |

---

*Document Version: 1.1.0 — SAAB Enhanced*  
*Last Updated: 2026-06-28*  
*SAAB Approved: 9.97/10*  
*Next Review: After Sprint 4 completion*
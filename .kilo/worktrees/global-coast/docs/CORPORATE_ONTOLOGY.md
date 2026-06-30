# Corporate Ontology — YALIHAN PLATFORM

> **Tarih:** 2026-06-28  
> **Durum:** SAAB Strategic Sprint  
> **Version:** 1.0.0  
> **Priority:** CRITICAL — Next Sprint P0

---

## Executive Summary

**SAAB Decision:** "Bundan sonraki en önemli sprint kod değildir. Corporate Ontology'dir."

**Amaç:** YALIHAN PLATFORM'un tüm bileşenlerinin tek, tutarlı tanımlara sahip olması. Bütün sistem aynı dili konuşacak.

---

## 1. CORE ENTITIES

### 1.1 Portfolio (Portföy)

```yaml
entity: Portfolio
definition: >
  Bir mal sahibine (Owner) ait gayrimenkul koleksiyonu.
  Birden fazla Property'yi gruplayan yapı.
  
canonical_attributes:
  - id: UUID
  - owner_id: FK → Owner
  - name: string
  - description: text
  - created_at: timestamp
  - updated_at: timestamp

relationships:
  - has_many: Property
  - belongs_to: Owner
  
synonyms:
  - Portföy
  - Emlak Portföyü
  - Gayrimenkul Portföyü
  
antonyms:
  - Individual property (tek mülk değil)

examples:
  - "Bodrum merkezli lüks villalar portföyü"
  - "Yatırımlık daireler koleksiyonu"
```

### 1.2 Property (Mülk)

```yaml
entity: Property
definition: >
  Tek bir gayrimenkul birimi.
  Portföy içinde yer alan en küçük listelenebilir birim.

canonical_attributes:
  - id: UUID
  - portfolio_id: FK → Portfolio
  - title: string
  - description: text
  - price: decimal
  - currency: enum (TL, USD, EUR)
  - size: integer (m²)
  - property_type: enum (villa, daire, arsa, işyeri)
  - status: enum (taslak, aktif, pasif, satılık, kiralık)
  
synonyms:
  - Mülk
  - Gayrimenkul
  - İlan (bazı bağlamlarda)
  
distinction_from:
  - Listing: Property can exist without being listed publicly
  - Portfolio: Property is contained within a Portfolio
  
antonyms:
  - Portfolio (container vs contained)
```

### 1.3 Owner (Mal Sahibi)

```yaml
entity: Owner
definition: >
  Gayrimenkulün hukuki sahibi.
  YALIHAN PLATFORM'un müşterilerinden biri.

canonical_attributes:
  - id: UUID
  - name: string
  - email: string
  - phone: string
  - tenant_id: FK → Tenant
  
relationships:
  - has_many: Portfolio
  - has_many: Property (through Portfolio)
  - has_one: ContactProfile

synonyms:
  - Mal Sahibi
  - Müvekkil
  - Satıcı (satış durumunda)
  
distinction_from:
  - Buyer: Owner is selling/renting, Buyer is purchasing/renting
  - Lead: Owner is confirmed, Lead is potential
```

### 1.4 Buyer (Alıcı)

```yaml
entity: Buyer
definition: >
  Gayrimenkul satın almak veya kiralamak isteyen kişi/kurum.

canonical_attributes:
  - id: UUID
  - name: string
  - email: string
  - phone: string
  - budget_min: decimal
  - budget_max: decimal
  - preferences: JSON
  
relationships:
  - has_many: Lead
  - has_many: Match
  
synonyms:
  - Alıcı
  - Müşteri (satış bağlamında)
  - Kiracı (kiralama bağlamında)
```

### 1.5 Lead (Müşteri Adayı)

```yaml
entity: Lead
definition: >
  YALIHAN PLATFORM'a ulaşan potansiyel müşteri.
  Satış, kiralama veya danışmanlık talebi olabilir.

canonical_attributes:
  - id: UUID
  - type: enum (satış, kiralama, danışmanlık, bilgi)
  - source: enum (web, telegram, telefon, referans)
  - status: enum (yeni, işleniyor, takip, dönüş yapıldı, kapalı)
  - priority: enum (kritik, yüksek, normal, düşük)
  
relationships:
  - belongs_to: Buyer (or creates one)
  - has_many: Match
  - has_many: FollowUp
  - belongs_to: Advisor (assigned_to)

synonyms:
  - Müşteri Adayı
  - Potansiyel Müşteri
  - Talep
```

### 1.6 Match (Eşleştirme)

```yaml
entity: Match
definition: >
  Bir Lead ile bir veya daha fazla Property arasındaki eşleşme.
  Uyumluluk puanlaması içerir.

canonical_attributes:
  - id: UUID
  - lead_id: FK → Lead
  - property_id: FK → Property
  - compatibility_score: decimal (0-100)
  - match_reasons: JSON array
  - status: enum (önerildi, görüntülendi, ilgileniyor, reddedildi)
  
relationships:
  - belongs_to: Lead
  - belongs_to: Property
```

### 1.7 Opportunity (Fırsat)

```yaml
entity: Opportunity
definition: >
  Potansiyel bir işlem. Satış veya kiralama fırsatı.

canonical_attributes:
  - id: UUID
  - type: enum (satış, kiralama)
  - probability: decimal (0-100)
  - estimated_value: decimal
  - stage: enum (keşif, teklif, müzakere, kapanış, kazanıldı, kaybedildi)
  - expected_close_date: date
  
relationships:
  - belongs_to: Property
  - belongs_to: Lead (optional)
  - belongs_to: Owner

synonyms:
  - Fırsat
  - İş Fırsatı
  - Potansiyel İşlem
```

### 1.8 Advisor (Danışman)

```yaml
entity: Advisor
definition: >
  YALIHAN PLATFORM'u kullanan insan çalışan.
  Gayrimenkul danışmanı.

canonical_attributes:
  - id: UUID
  - user_id: FK → User
  - name: string
  - specialization: string
  - performance_score: decimal
  
relationships:
  - has_many: Lead
  - has_many: Property
  - belongs_to: Tenant
```

---

## 2. DIGITAL ENTITIES

### 2.1 Agent (Dijital Çalışan)

```yaml
entity: Agent
definition: >
  YALIHAN AI WORKFORCE'ün insan benzeri dijital çalışanı.
  Belirli sorumluluklara, bilgi kaynaklarına ve olay dinleyicilerine sahip.

canonical_attributes:
  - id: string (e.g., "market-scanner")
  - name: string
  - division: string
  - status: enum (active, idle, error, maintenance)
  - kpis: JSON object
  - created_at: timestamp
  - updated_at: timestamp

sub_entities:
  - PersonnelFile: Agent'ın detaylı profili
  
relationships:
  - managed_by: Hermes
  - uses: Knowledge
  - listens_to: Event
  - produces: Event

synonyms:
  - Dijital Çalışan
  - AI Employee
  - Digital Employee
  - Agent (kısa)
```

### 2.2 Hermes (Orchestrator)

```yaml
entity: Hermes
definition: >
  YALIHAN AI WORKFORCE'ün baş orkestratörü.
  Tüm Agent'ları koordine eden merkezi beyin.

canonical_attributes:
  - id: "hermes"
  - name: "Hermes"
  - type: "orchestrator"
  - status: enum (healthy, degraded, offline)
  
relationships:
  - orchestrates: Agent (all)
  - routes: Event
  - escalates: HumanDecision

synonyms:
  - Chief Orchestrator
  - AI Chief
  - Workforce Coordinator
```

### 2.3 Division (Bölüm)

```yaml
entity: Division
definition: >
  Agent'ların gruplandığı fonksiyonel bölüm.

canonical_attributes:
  - id: string
  - name: string
  - description: text
  - priority: integer

divisions:
  - Knowledge Division
  - Market Intelligence Division
  - Listing Division
  - CRM Division
  - Publishing Division
  - Operations Division
```

---

## 3. KNOWLEDGE ENTITIES

### 3.1 Knowledge (Bilgi)

```yaml
entity: Knowledge
definition: >
  YALIHAN PLATFORM'un kurumsal hafızası.
  Drive, NotebookLM, ve dahili veritabanlarında saklanan bilgiler.

canonical_attributes:
  - id: UUID
  - type: enum (document, data, insight, pattern)
  - source: enum (drive, notebooklm, internal, external)
  - confidence: decimal (0-100)
  - last_updated: timestamp

sub_categories:
  - Corporate: Şirket bilgileri
  - Legal: Hukuki dokümanlar
  - Municipality: Belediye ve imar
  - Market: Piyasa verileri
  - Portfolio: Portföy geçmişi
  - Training: Eğitim materyalleri
  - SOP: Prosedürler
  - Finance: Finansal veriler
  - Marketing: Pazarlama içerikleri
  - AI Workforce: Agent dokümantasyonu

synonyms:
  - Kurumsal Hafıza
  - Corporate Memory
  - Bilgi Tabanı
```

### 3.2 SOP (Standart Operasyonel Prosedür)

```yaml
entity: SOP
definition: >
  Standart operasyonel prosedür.
  Tekrarlanabilir iş süreçlerinin dokümantasyonu.

canonical_attributes:
  - id: UUID
  - title: string
  - version: string
  - steps: JSON array
  - applicable_to: array of Agent/Workflow
  - last_reviewed: date

synonyms:
  - Prosedür
  - İş Prosedürü
  - Standart Prosedür
```

### 3.3 MarketData (Piyasa Verisi)

```yaml
entity: MarketData
definition: >
  Bodrum ve çevresi gayrimenkul piyasası hakkında veri.

canonical_attributes:
  - id: UUID
  - location: string
  - property_type: string
  - price_per_sqm: decimal
  - avg_days_on_market: integer
  - active_listings: integer
  - trend: enum (artış, sabit, düşüş)
  - snapshot_date: date
```

---

## 4. EVENT ENTITIES

### 4.1 Event (Olay)

```yaml
entity: Event
definition: >
  Platformda gerçekleşen herhangi bir eylem veya durum değişikliği.
  Agent'lar arasındaki iletişimin temel birimi.

canonical_attributes:
  - id: string (evt-{timestamp}-{uuid})
  - type: string (domain.event.name)
  - timestamp: ISO-8601
  - source: string (agent_id or "system")
  - tenant_id: string
  - correlation_id: string
  - payload: JSON object
  - metadata: JSON object

event_categories:
  - Portfolio Events: portföy yaşam döngüsü
  - Listing Events: ilan yaşam döngüsü
  - Photo Events: fotoğraf işleme
  - Market Events: piyasa güncellemeleri
  - CRM Events: müşteri etkileşimleri
  - System Events: platform olayları

synonyms:
  - Olay
  - Platform Olayı
  - Business Event
```

### 4.2 Workflow (İş Akışı)

```yaml
entity: Workflow
definition: >
  Birden fazla Event ve Agent'ı içeren iş süreci.

canonical_attributes:
  - id: UUID
  - name: string
  - description: text
  - steps: JSON array
  - sla_minutes: integer
  
relationships:
  - consists_of: Event
  - executed_by: Agent
```

### 4.3 Task (Görev)

```yaml
entity: Task
definition: >
  Bir Agent'a atanan spesifik iş birimi.

canonical_attributes:
  - id: UUID
  - agent_id: FK → Agent
  - type: string
  - priority: enum (critical, high, normal, low)
  - status: enum (pending, in_progress, completed, failed)
  - result: JSON object
```

---

## 5. SYSTEM ENTITIES

### 5.1 Tenant (Kiracı/Tenant)

```yaml
entity: Tenant
definition: >
  YALIHAN PLATFORM'un çoklu kiracı mimarisindeki her bir müşteri organizasyonu.

canonical_attributes:
  - id: UUID
  - name: string
  - subdomain: string
  - settings: JSON object
  - plan: enum (starter, professional, enterprise)
```

### 5.2 User (Kullanıcı)

```yaml
entity: User
definition: >
  YALIHAN PLATFORM'a giriş yapan insan kullanıcı.

canonical_attributes:
  - id: UUID
  - tenant_id: FK → Tenant
  - name: string
  - email: string
  - role: enum (admin, advisor, owner, buyer)
```

### 5.3 Channel (Kanal)

```yaml
entity: Channel
definition: >
  İlan yayınlanan platform veya medya kanalı.

canonical_attributes:
  - id: UUID
  - name: string
  - type: enum (website, airbnb, social, print)
  - api_integration: boolean
  - status: enum (active, inactive)
```

### 5.4 Capability (Yetenek)

```yaml
entity: Capability
definition: >
  YALIHAN PLATFORM'un büyük ölçekli işlevsel birimi.
  Birden fazla Sprint'te tamamlanan stratejik özellik grubu.

canonical_attributes:
  - id: string
  - title: string
  - description: text
  - status: enum (planning, in_progress, complete)
  - priority: integer
  - target_date: date

examples:
  - Capability 1: AI Listing Assistant
  - Capability 2: AI Workforce Foundation
  - Capability 3: AI CRM Assistant
```

---

## 6. RELATIONSHIP MAP

```
ENTITIES
───────────────────────────────────────────────────────────────────────────────

HUMAN ENTITIES
───────────────────────────────────────────────────────────────────────────────
    ┌─────────┐     ┌─────────┐     ┌─────────┐
    │  Owner  │────►│Portfolio│◄────│ Advisor │
    └─────────┘     └────┬────┘     └─────────┘
                         │
                         ▼
                    ┌─────────┐
                    │ Property│
                    └────┬────┘
                         │
    ┌─────────┐          │          ┌──────────┐
    │  Buyer  │──────────┼─────────►│  Match   │◄────┌─────────┐
    └────┬────┘          │          └──────────┘     │  Lead   │
         │               │               ▲           └────┬────┘
         │               ▼               │                │
         │          ┌─────────┐          │                ▼
         │          │Opportu- │          │           ┌──────────┐
         └─────────►│  nity   │          │           │ FollowUp │
                    └─────────┘          │           └──────────┘
                                         │
DIGITAL ENTITIES                         │
───────────────────────────────────────────────────────────────────────────────
                    ┌─────────┐
                    │ Hermes  │
                    └────┬────┘
                         │
         ┌───────────────┼───────────────┐
         ▼               ▼               ▼
    ┌──────────┐   ┌──────────┐   ┌──────────┐
    │ Knowledge│   │  Agent   │   │  Event   │
    │ Division │   │ Division │   │   Bus    │
    └──────────┘   └──────────┘   └──────────┘

KNOWLEDGE ENTITIES
───────────────────────────────────────────────────────────────────────────────
    ┌──────────┐
    │Knowledge │
    └────┬─────┘
         │
    ┌────┴────┬─────────┬─────────┬────────┬────────┐
    ▼         ▼         ▼         ▼        ▼        ▼
┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐ ┌──────┐ ┌──────┐
│Legal  │ │Market │ │ Port- │ │ SOP   │ │Train-│ │Market│
│       │ │       │ │ folio │ │       │ │ing   │ │Data  │
└───────┘ └───────┘ └───────┘ └───────┘ └──────┘ └──────┘
```

---

## 7. STANDARD AGENT CONTRACT

```yaml
standard_agent_contract:
  version: "1.0"
  
  input:
    required:
      - event_type: string
      - payload: object
      - correlation_id: string
    optional:
      - context: object
      - knowledge_query: object
  
  processing:
    steps:
      - "1. Receive event"
      - "2. Load context from Hermes"
      - "3. Query relevant Knowledge"
      - "4. Make decision/calculation"
      - "5. Execute action"
      - "6. Emit result event"
  
  output:
    required:
      - status: enum (success, failure, partial)
      - event_emitted: string
    optional:
      - result: object
      - recommendations: array
  
  error_handling:
    - "Log error"
    - "Emit agent.failed event"
    - "Request retry or escalate to Hermes"
```

---

## 8. IMPLEMENTATION PRIORITY

| Entity | Priority | Sprint | Notes |
|--------|----------|--------|-------|
| Event | P0 | 4 | Foundation of everything |
| Agent | P0 | 4 | Core workforce units |
| Hermes | P0 | 4 | Central orchestrator |
| Property | P0 | Current | Already exists in code |
| Portfolio | P0 | Current | Already exists in code |
| Lead | P1 | 6 | CRM capability |
| Match | P1 | 6 | CRM capability |
| Buyer | P1 | 6 | CRM capability |
| Owner | P0 | Current | Already exists |
| Knowledge | P1 | 10 | Knowledge layer |
| MarketData | P2 | 8 | Market intelligence |
| SOP | P2 | 11 | Knowledge layer |
| Opportunity | P2 | 7 | Sales pipeline |
| Channel | P3 | 13 | Publishing |
| Tenant | P0 | Current | Already exists |
| Capability | P1 | Ongoing | Sprint planning |

---

## 9. NAMING CONVENTIONS

### Domain Events (PascalCase with dots)

```
portfolio.created
listing.ready.for.description
lead.received
market.price.changed
agent.completed
```

### Agent IDs (kebab-case)

```
market-scanner
photo-agent
description-agent
drive-agent
```

### Entity Names (PascalCase, singular)

```
Portfolio
Property
Lead
Match
Agent
Division
```

### Table Names (snake_case, plural)

```
portfolios
properties
leads
matches
agents
divisions
```

---

## 10. ONTOLOGY VALIDATION

Every new feature and code change should be validated against this ontology:

```
CHECKLIST:
□ New entity? → Add to Corporate Ontology
□ Existing entity? → Use canonical name
□ New relationship? → Document in relationship map
□ New event? → Add to Event Catalog
□ New agent? → Create Personnel File
□ Name collision? → Check synonyms/antonyms
□ Cross-tenant? → Verify isolation
```

---

## 11. DECISION CRITERIA

**SAAB Question:** "Bu karar, YALIHAN PLATFORM'un dijital şirket vizyonunu güçlendiriyor mu?"

**Ontology Test:**
- Does this align with canonical entity definitions?
- Is the naming consistent with this document?
- Is the relationship properly documented?
- Does this support both human and digital employees?

---

*Document Version: 1.0.0*  
*Last Updated: 2026-06-28*  
*Next Review: After Sprint 4 completion*
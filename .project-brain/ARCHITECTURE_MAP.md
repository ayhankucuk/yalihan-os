# YALIHAN OS — Architecture Map

**LAST_VERIFIED_HEAD:** a3e74d92
**BRANCH:** release-candidate/RC2
**Güncellenmiş:** 2026-10-07

---

## System Layers

```
EXTERNAL (User/Browser/API/Queue/Scheduler)
        ↓
ROUTE / COMMAND / JOB
        ↓
CONTROLLER / HANDLER (150+ Admin, 50+ API)
        ↓
SERVICE LAYER (92+ service directories)
        ↓
MODEL / REPOSITORY (181 models)
        ↓
DATABASE (MySQL/Redis backed)
        ↓
EVENTS / QUEUE / NOTIFICATIONS
```

---

## Domains Discovered

### Core Property Domain
| Domain | Purpose | Entry Points |
|--------|---------|-------------|
| **Ilan/Listing** | Public/draft property presentation | IlanController, Wizard flow |
| **Property** | Managed real-estate asset | PropertyController |
| **Reservation** | Booking management | IlanCalendarController, IlanReservationService |
| **Calendar** | Availability/ICS | IlanCalendarService, AvailabilityService |
| **Photo** | Media management | IlanFotografiController |

### CRM Domain
| Domain | Purpose | Entry Points |
|--------|---------|-------------|
| **Kisi** | Contact/lead management | KisiController |
| **Talep** | Demand/requirement tracking | TalepController |
| **Matching** | Property-demand matching | DemandMatchingEngine |
| **Lead** | Lead scoring/routing | LeadScoringService |

### AI/Intelligence Domain
| Domain | Purpose | Entry Points |
|--------|---------|-------------|
| **Cortex** | AI analysis/recommendations | AIOrchestrator |
| **Hermes** | Event orchestration | Event bus, Agent runtime |
| **AI Services** | Provider adapters, prompts | AIProviderManager, AIPromptBuilder |

### Infrastructure Domain
| Domain | Purpose | Entry Points |
|--------|---------|-------------|
| **Bekci** | Pre-commit change integrity | BekciHealthCommand, sab:integrity-scan |
| **Sentinel** | Runtime health gate | SentinelConsoleCommand |
| **Tenant** | Isolation enforcement | BelongsToTenant trait, TenantScope |

---

## Tenant Architecture

### Canonical Tenant Mechanism

**TENANT_AUTHORITY:** Tenant model + tenant_id column

**Models with BelongsToTenant (16+):**
- Ilan, IlanReservation, Property
- Kisi, Talep, Lead
- Photo, BankAccount
- PropertyWorkspace, PortfolioDriveWorkspace
- AiLog, AccessCredential

**TENANT_RESOLUTION_FLOW:**
```
Request → TenantMiddleware → TenantContext → setTenantContext()
                                    ↓
                           All tenant-aware queries automatically scoped
```

**READ_ISOLATION:** Model global scopes
**WRITE_ISOLATION:** Service layer + repository

**EXPLICIT_BYPASS:** withoutTenant() method on tenant-scoped queries

---

## Authorization Architecture

### AUTHENTICATION
- Laravel session/API guards
- Admin/Danisman/User role separation

### AUTHORIZATION
- Policies: IlanPolicy, TalepPolicy
- Gates: defined in AuthServiceProvider
- Roles: admin, danisman, editor, viewer

### TENANT ISOLATION
- BelongsToTenant trait
- tenant_id foreign key constraints

### RESOURCE OWNERSHIP
- Route model binding
- Service-level ownership checks

---

## Data Architecture

### Key Tables

| Table | Domain | Owner Model | tenant_id | Key Relations |
|-------|--------|------------|-----------|--------------|
| ilanlar | Listing | Ilan | YES | property_id, category_id |
| property_reservations | Reservation | IlanReservation | YES | property_id, ilan_id (legacy) |
| kisi | CRM | Kisi | YES | - |
| talep | CRM | Talep | YES | kisi_id, ilan_id |
| photos | Media | IlanFotografi | YES | ilan_id |

### Shared Tables
- users (auth + CRM)
- tenants (multi-tenant root)

---

## Reservation Domain Deep Dive

**CANONICAL_TABLE:** property_reservations
**CANONICAL_MODEL:** IlanReservation

### Entry Points:
- `admin.ilanlar.calendar` (index, store, cancel, close)
- `admin.ilanlar.calendar.json` (availability feed)
- ICS export: `/ilan/{ilan}/calendar/ics`

### Services:
- IlanReservationService (CRUD, conflict detection)
- AvailabilityService (availability queries)
- CancellationPolicyService

### Events:
- ReservationCreatedEvent
- ReservationModifiedEvent
- ReservationCancelledEvent
- ReservationCheckedInEvent
- ReservationCompletedEvent

### Reservation Dependencies:
```
IlanReservation
→ Ilan (property binding)
→ Tenant (BelongsToTenant)
→ AdminNotificationService (notifications)
→ AdminActivityEventService (audit)
→ IlanCalendarController (calendar UI)
```

---

## Event/Observer Architecture

### Reservation Events
```
ReservationCreatedEvent
  → IlanObserver (listing status updates)
  → Notification channels

ReservationModifiedEvent
  → Calendar sync

ReservationCancelledEvent
  → Availability recalculation
```

### Observer Registration
- AppServiceProvider: IlanObserver, IlanFotografiObserver

### Job Structure
- Queue: database/Redis
- Scheduled: Laravel scheduler

---

## Hermes/AI Architecture

### Hermes Role
- Event/orchestration layer (not AI model)
- Coordinates domain events
- Routes work to agents
- Preserves operational flow

### AI Providers
- DeepSeek (primary)
- OpenAI/GPT
- Gemini
- Claude (external)

### AI Services
- AIProviderManager (provider selection)
- AIPromptBuilder (prompt construction)
- AICostService (usage tracking)

### Cortex Services
- CortexKnowledgeService
- CortexGoldenVisaAnalyzer
- DemandMatchingEngine

---

## Bekci/Sentinel Architecture

### Bekci (Pre-commit)
- sab:integrity-scan
- Migration boundary checks
- Secret scanning
- Schema drift detection

### Sentinel (Runtime Health)
```
SentinelConsoleCommand
  ├── sab:integrity-scan
  ├── bekci:health (exit code based on 70% threshold)
  └── Migration boundary

Health Threshold:
  - Score < 70 → FAILURE (exit 1)
  - Score >= 70 → SUCCESS (exit 0)
```

### CI/CD Integration
- Pre-commit hooks
- GitHub Actions
- Health gate before deploy

---

## Deployment Architecture

### Branch Strategy
- `release-candidate/RC2` - current release candidate
- `main` - production source

### Deployment Flow
1. Feature worktrees → RC2
2. Health checks pass
3. RC2 merge → main
4. Production deploy

### Production
- **Status:** UNKNOWN (no verification)
- **Target:** 157.180.116.63
- **App Path:** /opt/yalihan2026/current

---

## Test Architecture

### Test Categories
- Feature (HTTP/integration)
- Unit (isolated)
- Security (auth/tenant boundary)

### Key Test Suites
| Suite | Purpose | Count |
|-------|---------|-------|
| Reservation | Canonical boundary | 8+ |
| Tenant | Isolation | 5+ |
| Calendar | UI contract | 11+ |

### Claim→Test Mapping
```
"Cross-tenant read blocked"
  → TenantIsolationModifyCancelTest
  → assertNull() on cross-tenant query

"Reservation date fields correct"
  → IlanReservationCanonicalBoundaryTest
  → start_date/end_date assertions
```

---

## Cross-Domain Dependencies

```
CRUD:
  Ilan → Tenant
  Ilan → Category
  Ilan → YayinTipi

Reservation:
  IlanReservation → Ilan (property_id)
  IlanReservation → Tenant (tenant_id)
  IlanReservation → AdminNotificationService
  IlanReservation → AdminActivityEventService

CRM:
  Kisi → Tenant
  Talep → Kisi
  Talep → Ilan
  Talep → Tenant

AI:
  AIOrchestrator → AIProviderManager
  CortexKnowledgeService → Ilan
  DemandMatchingEngine → Talep → Ilan
```

---

## Architecture Hotspots

| Hotspot | Type | Note |
|---------|------|------|
| property_reservations table | Shared | Used by reservation + calendar |
| IlanReservation | Shared model | Controller + Service + Model |
| TenantContext | Shared infra | Global scope resolution |
| AIProviderManager | High fan-in | All AI calls go through |

---

## Unknown Areas

- Full Hermes event bus topology
- n8n integration points
- Complete queue/job scheduling
- Production Redis/cache usage
- AI cost tracking implementation

---

## Evidence Conflicts

None currently.

---

## Domain Business Semantics

### Rezervasyon Domain

**REZERVASYON NEDİR?**
- Bir mülkün belirli tarihler arasında rezerve edilmesi
- Misafir kabulü için takvimde blok

**KİM OLUŞTURABILIR?**
- Admin kullanıcılar (Yalıhan Emlak)
- Tenant context içinde

**HANGİ İLANA BAĞLI?**
- Ilan (property_id üzerinden)

**TENANT SAHİBİ KİM?**
- IlanReservation → tenant_id otomatik atanır

**HANGİ STATÜLERDEN GEÇER?**
- pending → confirmed → checked_in → completed
- cancelled (herhangi bir aşamada)

**İPTAL NE DEMEKTİR?**
- cancelled_at timestamp atanır
- Takvim bloğu kaldırılmaz (geriye dönük raporlama için)

**TAKVİM NE ZAMAN BLOKE OLUR?**
- Rezervasyon oluşturulduğunda
- confirmed veya pending statüsünde

**HANGİ NOTIFICATION OLUŞUR?**
- AdminNotificationService üzerinden admin bildirimi
- Rezervasyon oluşturulduğunda, iptal edildiğinde

**FİNANS ETKİSİ VAR MI?**
- total_amount alanı mevcut
- Ödeme lifecycle'ı ayrı

**MİSAFİR/CRM BAĞLANTISI NEDİR?**
- guest_name, guest_phone, guest_email alanları
- Kisi tablosu ile doğrudan bağlı DEĞİL

---

## Next Validation Required

When HEAD changes, validate:
1. Reservation service dependencies
2. Tenant scope usage
3. Test coverage for affected domains

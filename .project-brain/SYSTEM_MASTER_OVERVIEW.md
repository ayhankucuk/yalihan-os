# YALIHAN OS — SYSTEM MASTER OVERVIEW

**LAST_VERIFIED_HEAD:** e9d0d611
**Role:** ATLAS Technical Architecture Master
**Date:** 2026-10-07

---

## EXECUTIVE SUMMARY

YALIHAN OS bir **Emlak Yönetim ve Kiralama Platformu**dur.

**Core Functions:**
1. **İlan Yönetimi** - Satılık/kiralık gayrimenkul pazarlama
2. **CRM** - Müşteri ve talep yönetimi
3. **Rezervasyon** - Kısa dönem kiralama takvimi
4. **Finans** - Ödeme ve finansal işlem takibi
5. **AI Entegrasyonu** - Akıllı eşleştirme ve analitik

---

## SYSTEM ARCHITECTURE

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE                          │
│  (Admin Panel, Public Portal, Mobile, API)                      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      CONTROLLER LAYER                           │
│  150+ Admin Controllers, 50+ API Controllers                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                       SERVICE LAYER                             │
│  92+ Service Directories                                        │
│  - Domain Services (Ilan, Kisi, Reservation, Finance)          │
│  - AI Services (ProviderManager, Orchestrator)                  │
│  - Hermes (Event Bus)                                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                       MODEL LAYER                               │
│  181 Models                                                     │
│  - BelongsToTenant Trait (16+ models)                          │
│  - Canonical Enums (IlanDurumu, KisiDurumu, etc.)              │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      DATABASE LAYER                             │
│  50+ Tables                                                    │
│  MySQL (Production), SQLite (Local)                             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    EXTERNAL SERVICES                            │
│  AI: DeepSeek, OpenAI, Claude, Gemini, Ollama                  │
│  n8n: Workflow automation                                       │
│  Notifications: Email, WhatsApp, Telegram, Instagram           │
│  TKGM: Tapu Kadastro integration                                │
│  Hermes: Event orchestration                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## DATA TENANT ARCHITECTURE

### ✅ Properly Isolated (tenant_id + BelongsToTenant)
| Domain | Tables |
|--------|--------|
| CRM | kisiler, talepler, kisi_etkilesimler |
| Listing | ilanlar, ilan_fotograflari |
| Property | properties |
| AI | ai_logs |
| Users | users |

### ⚠️ NOT Isolated (CRITICAL)
| Domain | Tables | Risk |
|--------|--------|------|
| Finance | finansal_islemler, ledger_* | **Cross-tenant financial exposure** |

---

## BUSINESS VALUE CHAIN

```
KISI (Müşteri/Lead)
    │
    ├─→ TALEP (Talep/Requirement)
    │       │
    │       └─→ MATCHING (Eşleştirme/AI)
    │               │
    │               └─→ ILAN (İlan/Listing)
    │                       │
    │                       ├─→ REZERVASYON (Kiralama/Booking)
    │                       │       │
    │                       │       └─→ TAKVİM (Availability)
    │                       │
    │                       └─→ SATIŞ (Doğrudan satış)
    │
    └─→ GÖRÜŞME (KisiEtkilesim)
            │
            └─→ GÖREV (Gorevler)
```

---

## KEY INTEGRATION POINTS

### Hermes (Event Bus)
```
Domain Event → HermesDispatcher → Handler → Queue
                                 ↓
                        NotificationDispatcher
                        ↓
                   Email/WhatsApp/Telegram
```

### Queue Jobs
- 37 Job directories
- 15+ scheduled commands
- n8n webhook integration

### AI Integration
```
Request → AIProviderManager → DeepSeek/OpenAI/Claude/Gemini
                              ↓
                         AiLog (cost tracking)
                              ↓
                         AiBudgetGuard (limits)
```

---

## DECISION POINTS (Mihenk Taşları)

| Point | Question | Answer |
|-------|----------|--------|
| **REZERVASYON** | Tarih çakışması kontrolü nereden yapılır? | IlanReservationService::hasConflict() |
| **İLAN YAYIN** | Yayınlama için ne gerekir? | IlanPolicy + PublishGate + AI Quality |
| **CRM DURUM** | Müşteri SICAK mı? | KisiDurumu enum (isUrgent()) |
| **FİNANS** | Otomatik mi? | ❌ HAYIR - Manuel tetikleme |
| **TAKİM** | Tenant izolasyonu | BelongsToTenant trait (16+ models) |
| **AUTH** | Yetkilendirme | Policy + Gate (14 policies) |
| **AI** | Provider seçimi | AIProviderManager (config) |
| **EVENT** | Async işleme | Hermes + Queue + AsyncHandlerDispatchJob |

---

## TECHNICAL CONSTRAINTS

| Constraint | Value | Notes |
|------------|-------|-------|
| PHP Version | 8.2+ | Required for enums |
| Laravel | Latest | - |
| Tenant Isolation | Mandatory | All tenant-aware queries |
| Auth | Spatie RBAC | 3 roles |
| AI Providers | 5+ | DeepSeek primary |
| Queue | Database | Default driver |

---

## CRITICAL GAPS

| Gap | Impact | Recommendation |
|-----|--------|----------------|
| Finans tenant isolation | **HIGH** | Add tenant_id immediately |
| Auto-reservation→finance | MEDIUM | Add event listener |
| Kisi state automation | MEDIUM | Add trigger rules |
| Lead scoring | LOW | Document existing algorithm |

---

## RUNTIME OBSERVABILITY

| Tool | Purpose | Status |
|------|---------|--------|
| Telescope | Debugging | Active (8 watchers) |
| Bekçi | Pre-commit integrity | Active |
| Sentinel | Runtime health | Active (70% threshold) |
| Logs | Audit trail | Configured |

---

## TEST COVERAGE

| Domain | Tests | Status |
|--------|-------|--------|
| Reservation | 22+ | VERIFIED_PASS |
| Tenant Isolation | 15+ | VERIFIED_PASS |
| CRM | 10+ | VERIFIED_PASS |
| Auth/RBAC | 5+ | VERIFIED_PASS |

---

## DOCUMENTATION MAP

| Document | Content |
|---------|---------|
| ARCHITECTURE_MAP.md | System layers, domains |
| DATABASE_ARCHITECTURE.md | All tables, relationships |
| BUSINESS_WORKFLOWS.md | End-to-end processes |
| DOMAIN_KNOWLEDGE/ | Per-domain deep dive |
| BEKCI_ARCHITECTURE.md | Health gate system |

---

## ATLAS DECISION FRAMEWORK

When a task arrives, ATLAS asks:

```
1. CURRENT STATE: What is the current HEAD and state?
2. DOMAIN: Which domain does this affect?
3. AUTHORITY: Where is the canonical truth?
4. EVIDENCE: What is the evidence level?
5. ROOT CAUSE: Is this the minimum complete root cause?
6. FIX: Is this a bounded fix?
7. TEST: How do we verify?
8. IMPACT: What cross-domain effects?
```

---

## SYSTEM AT A GLANCE

```
YALIHAN OS = Laravel + Multi-tenant + AI + Event-driven

STRENGTHS:
✅ Strong tenant isolation (most domains)
✅ Comprehensive test coverage
✅ AI provider abstraction
✅ Event-driven architecture
✅ Health gates (Bekçi/Sentinel)

WEAKNESSES:
⚠️ Finance lacks tenant isolation
⚠️ No auto finance→reservation link
⚠️ Partial workflow automation
⚠️ Some domains lack tests

OPPORTUNITIES:
🚀 Auto-reservation→finance trigger
🚀 Kisi state automation
🚀 Enhanced AI matching
🚀 Complete workflow orchestration
```

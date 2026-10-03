# Bekçi v3 — 5-Capability Architecture
## Tarih: 2026-10-03 | Session: BEKCI_ENFORCEMENT_REALITY_CHECK_01

---

## MİSYON

> "Bir değişiklik production'a ulaşmadan önce Bekçi 'ne değişti, neyi etkiliyor, hangi canonical authority'ye bağlı, hangi invariant'ları geçti, hangi istisnaları kullandı ve production'ın hangi execution surfaces'ında hangi release çalışıyor?' sorularının tamamına makine-okunabilir cevap verebilmeli."

---

## 5 CAPABILITY YAPISI

### 1. CHANGE INTEGRITY
Değişikliklerin declared scope içinde kalmasını sağlar.

| Alt Sistem | Açıklama |
|------------|----------|
| Task Boundary | Declared write scope + scope enforcement |
| Dirty Tree | Pre-existing uncommitted changes tracking |
| Logical Ownership | Task başlangıcında dosya ownership, başkası dokunamaz |

**Amaç:** Agent yanlış dosyayı değiştirmesin, scope dışı değişiklik yapılmasın.

---

### 2. ARCHITECTURE INTEGRITY
Canonical authority ve schema/model/consumer contract'larını korur.

| Alt Sistem | Açıklama |
|------------|----------|
| Canonical Authority | Tek model/tek vocabulary/tek business truth |
| Change Impact Graph | "Bu değişiklik başka neyi etkileyebilir?" |
| Drift Propagation | Schema → Model → Seeder → Runtime chain |
| Schema/Model/Seeder Contract | Aynı kavram zincirde farklı isim/type yakalama |
| DI Contracts | Container binding, interface/implementation contract |
| State Contracts | State field mutation enforcement |
| Tenant Contracts | Tenant boundary + Execution Boundary Registry |

**Execution Boundary Registry — Default + Exception Modeli:**

```yaml
SURFACE: QUEUE
  default:
    tenant_context: REQUIRED_FOR_TENANT_BOUND_WORK
    cleanup: REQUIRED
    correlation: REQUIRED
  contracts:
    tenant_bound_job:
      TenantAwareJobInterface
      RestoreTenantContext
  exceptions:
    - job: SystemRankingJob
      reason: system-wide
    - job: CacheWarmupJob
      reason: infrastructure

SURFACE: HTTP
  default:
    tenant_context: REQUIRED
    auth: REQUIRED
  exceptions:
    - route: /health
      tenant_context: NONE
    - route: /api/v1/public/*
      tenant_context: NONE
```

**Amaç:** Schema drift, parallel authority, tenant boundary violation yakalanmasın.

---

### 3. GUARD INTEGRITY
Bekçi'nin kendi kalitesini korur.

| Alt Sistem | Açıklama |
|------------|----------|
| Self-test Fixtures | Known bad → MUST fail, Known good → MUST pass |
| Rule Maturity Ladder | Discovery → Observation → Baselinced → Blocking |
| Violation Fingerprints | Baseline + new regression separation |
| Ratchet Baseline | Sistem kötüye gidemez (16→15→14→...) |
| Canonical Exception Registry | Guard istisnaları merkezi, görünür |

**Rule Maturity Ladder:**

```
v1.0 DISCOVERY      → Reports only, no blocking
v1.1 OBSERVATION    → Reports, precision measured
v1.2 BASELINED      → NEW_CODE_ONLY blocking
v2.0 REGRESSION     → ALL code blocking (except legacy exceptions)
v2.1 FULL_BLOCKING  → Only when zero legitimate legacy exceptions
```

**Canonical Exception Registry Kontratı:**

```yaml
exception_id:    CE-001
rule_id:         TENANT_SCOPE_BYPASS
location:        tests/Feature/WhatsApp/...
reason:          Post-request persisted tenant verification
scope:           TEST_ONLY
approved_by:     ayhan
reference:       ADR-012
created_at:      2026-10-03
expires_at:      null | YYYY-MM-DD
```

**Rule Metadata:**

```yaml
RULE_ID:         FORBIDDEN_STATUS
VERSION:         v1.2
MATURITY:        BASELINED
PRECISION:       HIGH
BASELINE:        16 violations
EXCEPTIONS:      [CE-001, CE-002]
SELF_TEST_STATUS: PASS
BLOCKING_POLICY: NEW_CODE_ONLY
LAST_VERIFIED_SHA: 4287be8e
```

**Amaç:** Guard'ın precision'ı zamanla bozulmasın, false-positive yönetilebilir olsun.

---

### 4. RUNTIME INTEGRITY
Production davranışını izler, hiçbir koşulda auto-blocking yapmaz.

| Alt Sistem | Açıklama |
|------------|----------|
| Exception Provenance | Correlation ID + tenant + boundary + entrypoint chain |
| Fallback Provenance | REQUIRED/SAFE/DEGRADED/MASKING_FAILURE classification |
| Execution Boundaries | Surface bazlı contract verification |
| Silent Observer | Alert only, never auto-block |

**Fallback Classification:**

| Sınıf | Anlamı |
|-------|--------|
| REQUIRED | Edge case'i coverage altına alıyor |
| SAFE | Config yokluğunda makul default |
| DEGRADED | Provider unavailable, fallback model |
| MASKING_FAILURE | Hatayı örtbas ediyor — alarm gerekli |

**Amaç:** Runtime hataları sessizce geçiştirilmesin, ama observability hiçbir zaman enforcement policy değiştirmesin.

---

### 5. RELEASE INTEGRITY
Deploy öncesi ve sonrası release contract'ını korur.

| Alt Sistem | Açıklama |
|------------|----------|
| Schema Evolution Classification | ADDITIVE/RESTRICTIVE/DESTRUCTIVE |
| Release Fingerprint | SOURCE/WORKER/SCHEDULER SHA parity |
| Web/Worker/Scheduler Parity | Long-lived process restart requirement |
| Consumer Retirement Gate | Legacy artifact kaldırma öncesi usage verification |
| Deployment State Machine | DISCOVER → PRECHECK → HUMAN → DEPLOY → VERIFY |

**Schema Evolution Classification:**

| Tip | Örnek | Politika |
|-----|-------|----------|
| ADDITIVE | nullable column, new index | Auto-approve |
| RESTRICTIVE | NOT NULL, type change, rename | Requires gate |
| DESTRUCTIVE | DROP column/table | Human gate mandatory |

**Consumer Retirement Gate:**

```yaml
LEGACY_ARTIFACT:     tenants.status
STATIC_READERS:      2    # grep count
STATIC_WRITERS:      1    # grep count
RUNTIME_OBSERVED:    0    # Silent Observer
JOBS:                0
COMMANDS:            0
SEEDERS:             0
TEST_FIXTURES:       0
RETIREMENT_ELIGIBLE: NO
REASON:              "HuntOpportunitiesCommand still uses status"
```

**Deployment State Machine:**

```
DISCOVER
   ↓
PRECHECK (schema compatibility, release parity, gate status)
   ↓
HUMAN_GATE (for DESTRUCTIVE schema changes)
   ↓
DEPLOY_CODE
   ↓
MIGRATE (if required)
   ↓
RELOAD_WORKERS (queue, scheduler)
   ↓
VERIFY_RELEASE (fingerprint parity)
   ↓
SMOKE_TEST
   ↓
PROMOTE / ROLLBACK
```

**Amaç:** Deploy riski önceden görünsün, worker/web drift yakalansın.

---

## UYGULAMA SIRASI

```
NOW:
  Task 10: SSH → production read-only audit
           ├── Physical tenant schema
           ├── Migration state
           ├── Queue state
           └── Release identity

NEXT (Ground Truth):
  CDA-007 read-only discovery
  ├── Data distribution (which columns have data)
  ├── Readers/Writers/Seeders/Models/Commands/Jobs/API/Tests map
  └── Canonical authority decision

NEXT (Guard Integrity):
  ├── Blueprint precision fix
  ├── Deterministic self-test fixtures
  ├── Rule maturity assignment
  ├── Violation fingerprints
  ├── Ratchet baseline
  └── Canonical Exception Registry

NEXT (Change Integrity):
  ├── Task Boundary Guard
  ├── Dirty Tree automated
  └── Logical Ownership

NEXT (Architecture Integrity):
  ├── Change Impact Graph
  ├── Drift Propagation Detector
  ├── Schema/Model/Seeder Contract
  ├── Execution Boundary Registry
  └── Tenant fitness tests

NEXT (Release Integrity):
  ├── Release Fingerprint command
  ├── Web/Worker/Scheduler parity
  ├── Schema evolution classification
  ├── Consumer Retirement Gate
  └── Deployment state machine

LATER (Runtime Integrity):
  ├── Exception provenance (correlation ID)
  ├── Fallback provenance tracker
  ├── Execution Boundary Registry enforcement
  └── Silent Observer

LATER (Agent Ergonomics):
  ├── Bekçi MCP configuration
  ├── Capability handshake
  └── .clinerules simplification
```

---

## CANONICAL EXCEPTION REGISTRY (Initial)

```yaml
exceptions:
  - id: CE-001
    rule: TENANT_SCOPE_BYPASS
    location: tests/Feature/WhatsApp/
    reason: Post-request persisted tenant verification
    scope: TEST_ONLY
    approved_by: ayhan
    reference: ADR-012
    created_at: 2026-10-03

  - id: CE-002
    rule: WITHOUT_GLOBAL_SCOPES
    location: tests/Feature/Reservation/
    reason: Explicit tenant scope verification
    scope: TEST_ONLY
    approved_by: ayhan
    reference: ADR-015
    created_at: 2026-10-03
```

---

## CONCEPT COVERAGE MAP

```
CRITICAL_INVARIANT         COVERAGE    GAP
Tenant Boundary            PARTIAL     CLI, SCHEDULER unknown
Canonical Vocabulary       PARTIAL     Tenant.active_state OK, others ?
Schema Contract            GOOD        Migration→Model OK
State Contracts            PARTIAL     yayin_durumu OK, others ?
Release Parity             NONE        Not implemented
Exception Provenance        NONE        Not implemented
Fallback Provenance        NONE        Not implemented
Consumer Retirement        NONE        Not implemented
```

---

## BAĞLANTI

- Previous: `.project-brain/BEKCI_GATES.md`
- Findings: `.project-brain/CDA_FINDINGS.md`
- Evidence: `.project-brain/EVIDENCE_INDEX.md`
- Decisions: `.project-brain/DECISION_LOG.md`

---

*Son güncelleme: 2026-10-03*
*Kaynak: BEKCI_ENFORCEMENT_REALITY_CHECK_01 + AYHAN_ARCHITECTURE_FEEDBACK*
*HEAD: 4287be8e*

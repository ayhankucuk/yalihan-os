# Supervised Autonomy Platform Capability Charter v1.0

> **Oluşturulma:** 2026-08-14
> **Durum:** APPROVED (SAAB v9 — 2026-08-14)
> **Sahip:** Chief AI + SAAB
> **Bağlam:** PILOT-001/PILOT-002 Orchestrator Pattern Analysis

---

## 1. Özet & Karar Noktası

YdlPublishOrchestrator (PILOT-001) ve YdlReservationOrchestrator (PILOT-002) karşılaştırması, iki kritik mimari gerçeği ortaya koydu:

1. **Ortak güvenlik/authority altyapısı mevcut** — ancak bu ortaklık inheritance değil, composition ile sağlanmalı.
2. **Domain orchestrator'lar korunmalı** — business logic domain'de kalmalı, platform katmanı sadece guard/policy sağlamalı.

**Yasak:** BaseOrchestrator veya herhangi bir orchestrator inheritance hiyerarşisi.
**İzin:** Composition — domain orchestrator'lar platform capability'lerini composer olarak kullanır.

---

## 2. Mimari Sınır Tanımı

```
┌─────────────────────────────────────────────────────────────┐
│                  SUPERVISED AUTONOMY PLATFORM                │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │ Authority    │  │ ApprovalToken│  │ Idempotency      │  │
│  │ Evaluator    │  │ Policy       │  │ Guard            │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │ Tenant       │  │ Evidence     │  │ EventIdentity    │  │
│  │ Boundary     │  │ Envelope     │  │ Policy           │  │
│  │ Guard        │  │ Factory      │  │                  │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           Certification Hook                          │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    DOMAIN ORCHESTRATORS                     │
│                                                              │
│  ┌─────────────────────┐  ┌─────────────────────────────┐   │
│  │ YdlPublish          │  │ YdlReservation             │   │
│  │ Orchestrator        │  │ Orchestrator               │   │
│  │                     │  │                             │   │
│  │ • Governance state  │  │ • Canonical lock/overlap    │   │
│  │ • Ilan state        │  │ • Race invariant           │   │
│  │ • CRUD write        │  │ • Atomic replacement       │   │
│  │                     │  │                             │   │
│  │ Composes:           │  │ Composes:                   │   │
│  │  AuthorityEvaluator │  │  AuthorityEvaluator        │   │
│  │  IdempotencyGuard   │  │  IdempotencyGuard         │   │
│  │  TenantBoundaryGuard│  │  TenantBoundaryGuard     │   │
│  │  EvidenceEnvelope    │  │  EvidenceEnvelope         │   │
│  │  EventIdentityPolicy │  │  EventIdentityPolicy      │   │
│  └─────────────────────┘  └─────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Platform Capability Tanımları

### 3.1 AuthorityEvaluator

**Sorumluluk:** YDL authority seviyesini okuma ve yorumlama

**Interface:**
```php
interface AuthorityEvaluatorInterface
{
    public function evaluate(Ilan $ilan, ?string $override = null): AuthorityResult;
    public function hasBlockingIntersection(string $taskScope, array $activeBlockers): bool;
    public function isStopAuthority(string $authority): bool;
}
```

**Domain-specific:** Hiçbir domain logic içermez. Sadece authority context okur ve yorumlar.

**Platform-owned:** Evet. Tüm orchestrator'lar aynı authority context'i kullanır.

---

### 3.2 ApprovalTokenPolicy

**Sorumluluk:** Human approval token üretimi, validasyonu, TTL yönetimi

**Interface:**
```php
interface ApprovalTokenPolicyInterface
{
    public function createToken(ApprovalContext $context): ApprovalToken;
    public function validateOrFail(ApprovalToken $token): void;
    public function isExpired(ApprovalToken $token): bool;
    public function generateEventId(...$identifiers): string; // deterministic, minute-precision
}
```

**Domain-specific:** Token payload domain'e özel olabilir (e.g., Publish vs Reservation).

**Platform-owned:** Evet. Token üretimi ve validasyon mekanizması ortak.

---

### 3.3 IdempotencyGuard

**Sorumluluk:** Event log üzerinden idempotency kontrolü ve evidence üretimi

**Interface:**
```php
interface IdempotencyGuardInterface
{
    public function check(string $eventId): IdempotencyResult;
    public function appendIdempotentNoOp(Evidence $evidence): bool;
}
```

**Standart Davranış:**
- Event log'da `eventExists()` kontrolü
- Varsa → **exception atma**, `IdempotentNoOp` evidence dön
- Yoksa → devam et

**Publish Orchestrator'ın Mevcut Davranışı:**
```php
// ❌ MEVCUT (exception-based)
if ($this->eventLog->eventExists($token->eventId)) {
    throw new \DomainException("Duplicate event...");
}
```

**Standart Davranış (Rekommended):**
```php
// ✅ STANDART (evidence-based)
if ($this->eventLog->eventExists($token->eventId)) {
    return XxxEvidence::idempotentNoOp(...);
}
```

**Platform-owned:** Evet. Event log okuma/yazma mekanizması ortak.
**Refactor Zamanı:** Ayrı bir refactor kararı gerektirir — bu Charter'ın scope'u dışında.

---

### 3.4 TenantBoundaryGuard

**Sorumluluk:** Cross-tenant erişim kontrolü

**Sahiplik:** Platform contract + Domain implementasyonu

**Kural:** Model-specific query'ler hardcoded olmaz. Merkezi TenantResolver üzerinden çözümlenir.

**Interface:**
```php
interface TenantBoundaryGuardInterface
{
    /**
     * Generic cross-tenant verification.
     * Model-specific query'ler TenantResolver içinde injectable'dır.
     */
    public function verify(string $modelClass, int $recordId, int $expectedTenantId): void;

    /**
     * Shorthand helpers for common models.
     * Implementasyon TenantResolver kullanmalı, hardcoded query içermemeli.
     */
    public function verifyIlan(int $ilanId, int $expectedTenantId): void;
    public function verifyReservation(int $reservationId, int $expectedTenantId): void;
}
```

**TenantResolver Pattern:**
```php
interface TenantResolverInterface
{
    public function resolveTenantId(string $modelClass, int $recordId): ?int;
}

// Domain orchestrator TenantResolver'ı inject eder
class TenantBoundaryGuard
{
    public function __construct(
        private TenantResolverInterface $tenantResolver,
    ) {}

    public function verify(string $modelClass, int $recordId, int $expectedTenantId): void
    {
        $actualTenantId = $this->tenantResolver->resolveTenantId($modelClass, $recordId);

        if ($actualTenantId === null) {
            throw new RecordNotFoundException("Record #{$recordId} not found");
        }

        if ($actualTenantId !== $expectedTenantId) {
            throw new CrossTenantAccessException(
                "Cross-tenant access denied: expected tenant {$expectedTenantId}, found {$actualTenantId}"
            );
        }
    }
}
```

**Davranış:** Herhangi bir cross-tenant tespitinde exception atar.

**Platform-owned:** Contract ortak, TenantResolver implementasyonu domain'e özel.

---

### 3.5 EvidenceEnvelopeFactory

**Sorumluluk:** Evidence DTO'ları için ortak envelope üretimi

**Interface:**
```php
interface EvidenceEnvelopeFactoryInterface
{
    public function createEnvelope(
        string $eventId,
        string $pilot,
        string $ydlAuthority,
        string $authorityContext,
        string $executionOwner,
        string $occurredAt,
    ): EvidenceEnvelope;

    public function toDomainEvent(EvidenceEnvelope $envelope, DomainPayload $payload): DomainEvent;
}
```

**Domain-specific:** Hayır. Sadece envelope structure ortak.

**Payload Ayrımı:**
```
EvidenceEnvelope (Platform)
├── eventId
├── pilot
├── ydlAuthority
├── authorityContext
├── executionOwner
├── occurredAt
└── domainPayload (Domain-specific, any type)
    ├── PublishPayload: ilanId, completionScore, qualityScore
    ├── ReservationPayload: ilanId, reservationId, startDate, endDate
    └── CancellationPayload: reservationId, state
```

**Platform-owned:** Evet. Envelope structure ortak, payload ayrı kalır.

**Mevcut Durum:**
- Publish → `YdlEvent` (generic, tek type)
- Reservation → `ReservationEvent` (domain-specific type'lar)

**Charter Kararı:** Domain event tipleri ayrı kalmalı. Evidence envelope ortaklaştırılabilir ama domain event type'lar强迫 etmemeliyiz.

---

### 3.6 EventIdentityPolicy

**Sorumluluk:** Deterministic event ID üretimi

**Interface:**
```php
interface EventIdentityPolicyInterface
{
    public function generate(string $pilot, ...$identifiers): string;
}
```

**Standart Format:**
```
{pilot}|{identifier1}|{identifier2}|...|{minute_timestamp}
→ SHA256 → substring(16)
```

**Platform-owned:** Evet. Tüm orchestrator'lar aynı algoritmayı kullanır.

---

### 3.7 CertificationHook

**Sorumluluk:** Session summary ve certification event üretimi için hook

**Interface:**
```php
interface CertificationHookInterface
{
    public function beforeExecution(ExecutionContext $context): void;
    public function afterSuccess(ExecutionContext $context, Evidence $evidence): CertificationEvent;
    public function afterFailure(ExecutionContext $context, \Throwable $e): CertificationEvent;
}
```

**Domain-specific:** Evet. Certification logic domain'e özel.

**Platform-owned:** Hayır. Domain orchestration'a aittir.

---

## 4. Domain Orchestrator Sorumlulukları

### 4.1 YdlPublishOrchestrator

**Domain-specific sorumluluklar:**
- Governance state kontrolü (`canPublish()`)
- Ilan state doğrulama (YAYINDA mı?)
- IlanCrudService üzerinden publish transition
- Completion/quality score hesaplama

**Platform capability kullanımı:**
```php
class YdlPublishOrchestrator
{
    public function executePublish(Token $token, IlanCrudService $crud, int $approvedBy): Evidence
    {
        // 1. Platform: Token validation
        $this->tokenPolicy->validateOrFail($token);

        // 2. Platform: STOP authority gate
        if ($this->authorityEvaluator->isStopAuthority($token->ydlAuthority)) {
            return $this->evidenceFactory->blocked(...);
        }

        // 3. Platform: Idempotency check
        $idempotencyResult = $this->idempotencyGuard->check($token->eventId);
        if ($idempotencyResult->isDuplicate) {
            return $idempotencyResult->evidence;
        }

        // 4. Domain: Governance guard
        if (! $this->governanceGuard->canPublish($governanceState)) {
            throw new DomainException("Governance guard failed");
        }

        // 5. Domain: Ilan state check
        $ilan = Ilan::findOrFail($token->ilanId);
        if ($ilan->yayin_durumu === IlanDurumu::YAYINDA) {
            return $this->evidenceFactory->idempotentNoOp(...);
        }

        // 6. Domain: Execute publish
        $ilan = $crud->update($ilan, [...]);

        // 7. Platform: Evidence
        return $this->evidenceFactory->success(...);
    }
}
```

---

### 4.2 YdlReservationOrchestrator

**Domain-specific sorumluluklar:**
- Canonical `lockForUpdate` + overlap check (ReservationService)
- RACE INVARIANT: readiness geçti ama canonical conflict yakaladı
- Terminal state kontrolü (CANCELLED → iptal zaten yapılmış)
- Execution-time re-authorization (override için)

**Platform capability kullanımı:**
```php
class YdlReservationOrchestrator
{
    public function executeReservation(Token $token, ...): Evidence
    {
        // 1. Platform: Token validation
        $this->tokenPolicy->validateOrFail($token);

        // 2. Platform: Tenant boundary
        $this->tenantGuard->verifyIlanTenant($token->ilanId, $token->tenantId);

        // 3. Platform: STOP authority gate
        if ($this->authorityEvaluator->isStopAuthority($token->ydlAuthority)) {
            return $this->evidenceFactory->blocked(...);
        }

        // 4. Platform: Idempotency check
        $idempotencyResult = $this->idempotencyGuard->check($token->eventId);
        if ($idempotencyResult->isDuplicate) {
            return $idempotencyResult->evidence;
        }

        // 5. Domain: Canonical execution
        try {
            $reservation = $this->reservationService->createReservation(...);
            return $this->evidenceFactory->success(...);
        } catch (ConflictException $e) {
            // Domain: Canonical lock yakaladı
            return $this->evidenceFactory->conflict(...);
        }
    }

    public function executeOverride(Token $token, ...): Evidence
    {
        // ... platform gates ...

        // Domain: Execution-time re-authorization
        // (readiness stale olabilir, execution-time check zorunlu)
        $canOverride = $this->conflictOverrideService->canOverride(
            userId: $authUserId,
            propertyId: $token->ilanId,
            ydlAuthority: $token->ydlAuthority,
            conflictReservationId: $token->conflictReservationId,
        );

        if (! $canOverride) {
            return $this->evidenceFactory->unauthorized(...);
        }

        // Domain: Atomic cancel + create
        $reservation = $this->reservationService->createReservationWithOverride(...);
        return $this->evidenceFactory->success(...);
    }
}
```

---

## 5. Platform vs Domain Ownership Matrix

| Capability | Platform Owned | Domain Owned | Inheritance | Notes |
|------------|---------------|-------------|------------|-------|
| AuthorityEvaluator | ✅ | | ❌ | Platform contract |
| ApprovalTokenPolicy | ✅ | | ❌ | Token üretimi ortak |
| IdempotencyGuard | ✅ | | ❌ | Event log okuma/yazma |
| TenantBoundaryGuard | ✅ (contract) | ✅ (impl) | ❌ | Model-specific |
| EvidenceEnvelope | ✅ | | ❌ | Envelope ortak, payload ayrı |
| EventIdentityPolicy | ✅ | | ❌ | Algoritma ortak |
| CertificationHook | | ✅ | ❌ | Domain orchestration |
| Governance logic | | ✅ | ❌ | Domain-specific |
| Canonical execution | | ✅ | ❌ | Domain business logic |
| Conflict resolution | | ✅ | ❌ | Domain-specific |
| Domain event types | | ✅ | ❌ | Ayrı kalmalı |

---

## 6. Yasaklar

| Yasak | Gerekçe |
|-------|---------|
| Orchestrator inheritance | Domain coupling yaratır, composition tercih edilmeli |
| BaseOrchestrator sınıfı | Generic orchestrator reddedildi |
| Platform katmanında domain logic | Platform katmanı guard/policy sağlar, iş mantığı içermez |
| Domain orchestrator'da raw DB write | Tüm DB write'lar canonical service üzerinden |
| Cross-tenant erişim | TenantBoundaryGuard zorunlu |
| Silent catch bloğu | Tüm exception'lar logged + rethrown veya evidence'ya dönüşmüş |

---

## 7. Açık Sorular (Charter v1.0'a)

- [ ] EvidenceEnvelopeFactory gerçekten needed mi, yoksa mevcut evidence DTO'ları yeterli mi?
- [ ] Idempotency refactor zamanı ne zaman? (Publish davranışı evidence-based olmalı)
- [ ] Platform capability'ler için interface'ler ayrı dosyalarda mı, tek dosyada mı?
- [ ] Platform katmanı için ayrı namespace mi (`App\Services\Ydl\Platform`)?
- [ ] Tek event log stratejisi — PILOT-001/PILOT-002 ayrı log'ları birleştirilmeli mi?

**Kapalı Sorular:**
- Inheritance yasak, composition zorunlu → ✅ Kapalı (Charter §1)
- Platform sadece guard/policy → ✅ Kapalı (Charter §3)
- Domain business logic domain'de kalır → ✅ Kapalı (Charter §3, §4)
- TenantBoundaryGuard sahipliği → ✅ Kapalı (Charter §3.4 - TenantResolver pattern)

---

## 8. Sonraki Adımlar

### SAAB Review Sonucu: APPROVED ✅

### Onay Sonrası Sıra

```
1. Charter düzeltmeleri          ✅ TAMAMLANDI
        ↓
2. SAAB final approval           ✅ TAMAMLANDI
        ↓
3. Implementation Charter        📋 SONRAKI
        ↓
4. Composition-based platform capability extraction
   • AuthorityEvaluator
   • ApprovalTokenPolicy
   • IdempotencyGuard
   • TenantBoundaryGuard (TenantResolver ile)
   • EventIdentityPolicy
        ↓
5. Evidence / Tests
        ↓
6. Certification
```

### Implementation Scope (v1.0)

** Dahil:**
- AuthorityEvaluator
- ApprovalTokenPolicy
- IdempotencyGuard
- TenantBoundaryGuard (TenantResolver pattern)
- EventIdentityPolicy

** Hariç (açık tasarım konusu):**
- EvidenceEnvelopeFactory
- Tek event-log stratejisi
- Idempotency behavior alignment (Publish → evidence-based)

---

## 9. Referanslar

- PILOT-001: YdlPublishOrchestrator (Publish pipeline)
- PILOT-002: YdlReservationOrchestrator (Reservation pipeline)
- SAAB v9: Strategic AI Architecture Board
- Evidence Pattern Analysis: 2026-08-14

---

*Bu charter, SAAB kararlarını ve mimari ilkeleri belgelendirir. Değişiklikler checksum gerektirir (SAB.md benzeri).*

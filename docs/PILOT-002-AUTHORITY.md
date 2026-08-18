# PILOT-002 — Authority & Invariant Design

**Charter Ref:** `c03f3e53` (PILOT-002 Charter v1.0)
**Version:** 1.0
**Date:** 2026-08-13
**Status:** AUTHORITY DESIGN — PILOT CERTIFICATION BOUNDARY
**Model:** Opus 4.8

---

## Kritik Tasarım Kararı — Conflict-Free CREATE Otomasyonu

> **Soru:** Conflict-free CREATE, YALIHAN tarafından insan onayı olmadan tamamlanabilecek ilk gerçek reservation operation olacak mı?

**Karar: HAYIR — Pilot süresince tüm CREATE operasyonları human approval gerektirir.**

Gerekçe:
- PILOT-001'de publish yüksek etkili operation olduğu için human approval doğruydu.
- Reservation tarafında supervised autonomy platform standardına dönüşmeden önce risk sınıfı açıkça dondurulmalı.
- Conflict-free CREATE otomasyonu ancak PILOT-002 certification sonrası, ayrı bir Auto-Approval Policy ile açılabilir.
- İlk pilotta human approval = governance muscle memory kurulması.

```
Auto-Approval Policy (Sonraki Capability):
  ├─ PILOT-002 CERTIFIED
  ├─ ≥6 ay conflict-free operations
  ├─ Zero double-booking incidents
  └─ Explicit SAAB vote → Auto-Approval ALLOWED
```

---

## 1. Authority × Operation Matrix

### Matrix

| Authority | CREATE | CANCEL | OVERRIDE |
|-----------|--------|--------|----------|
| **FULL** | readiness + canonical check + **human approval** | readiness + canonical check + **human approval** | role check + explicit approval |
| **LIMITED** | scope intersection + readiness + **human approval** | scope intersection + readiness + **human approval** | scope + role + explicit approval |
| **STOP** | BLOCK — DomainException | BLOCK — DomainException | BLOCK — DomainException |

### Detay

**CREATE — FULL:**
1. `YdlReservationContext` → authority = FULL
2. `ReservationReadinessService` → tüm kontroller geçti
3. Canonical conflict check → çakışma yok
4. **Human approval** (pilot süresince zorunlu)
5. `ReservationService::createReservation()` → `lockForUpdate` + overlap check
6. `PropertyAvailability` → `is_available = false`
7. `ReservationCreatedEvent` → evidence

**CREATE — LIMITED:**
1. Authority = LIMITED
2. Scope intersection kontrolü → kullanıcı yetki alanı mülkü kapsıyor mu?
3. Readiness + canonical conflict check
4. **Human approval** (pilot süresince zorunlu)
5. Canonical create zinciri devam eder

**CREATE — STOP:**
→ `DomainException(authority_violation)` — override yok

**CANCEL — FULL:**
1. Authority = FULL
2. Readiness → reservation mevcut, terminal state değil
3. **Human approval**
4. `ReservationService::cancelReservation()` → idempotent
5. `PropertyAvailability` → internal kaynaklar `is_available = true`
6. `ReservationCancelledEvent` → evidence

**CANCEL — LIMITED:**
→ Scope intersection kontrolü + readiness + **human approval**

**CANCEL — STOP:**
→ `DomainException(authority_violation)`

**OVERRIDE — FULL/LIMITED:**
1. `ConflictOverrideService::canOverride()` → role + scope kontrolü
2. Authority = LIMITED'de scope intersection zorunlu
3. **Explicit human decision** — kesinlikle otomatik değil
4. `ReservationService::createReservation()` → `lockForUpdate` (canonical check hâlâ geçerli)
5. Override log → evidence

**OVERRIDE — STOP:**
→ `DomainException(authority_violation)` — STOP seviyesinde override mümkün değil

---

## 2. Canonical Ownership

```
┌────────────────────────────────────────────────────────────────┐
│  YdlReservationOrchestrator                                    │
│    → Karar koordinasyonu yapar                                │
│    → Authority kontrolü yapar                                  │
│    → Readiness değerlendirir                                   │
│    → Human approval sürecini yönetir                           │
│    → Event/evidence tetikler                                    │
│                                                                │
│    HİÇBİR ZAMAN:                                              │
│    → Overlap/conflict algoritması YAZMAZ                       │
│    → Lock/locking mekanizması YAZMAZ                          │
│    → Override yetkilendirme mantığı YAZMAZ                     │
└────────────────────────────┬───────────────────────────────────┘
                             │ calls
                             ▼
┌──────────────────────────────────────────────────────────────┐
│  ReservationService (Canonical Overlap/Locking Owner)        │
│    → lockForUpdate + overlap query — TEK source of truth     │
│    → Atomic TX — reservation correctness                     │
│    → createReservation() / cancelReservation() / modify()  │
│    → PropertyAvailability management                         │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│  ConflictOverrideService (Canonical Override Owner)         │
│    → canOverride(user, property, authority)                 │
│    → getOverrideScopes(user)                                 │
│    → isOverrideAllowed(authority, scope)                    │
│    → Override decision + evidence                            │
└──────────────────────────────────────────────────────────────┘
```

**Prensip:** Her domain concern tek bir canonical owner'a aittir. Orchestrator bu owner'ları çağırır, asla içlerini yeniden yazmaz.

---

## 3. Race Invariant

```
┌──────────────────────────────────────────────────────────────┐
│  RACE INVARIANT — Read-Then-Write Yanılsaması Yasak        │
│                                                              │
│  Authority kontrolü geçildi + readiness geçildi =          │
│  reservation doğruluğunun KANITI DEĞİLDIR.                  │
│                                                              │
│  Readiness sonucu STALE olabilir:                           │
│  ├─ Araştırma yapıldı → 2 sn geçti                        │
│  ├─ Başka bir thread/agent aynı tarihleri reserve etti     │
│  └─ Availability değişti                                     │
│                                                              │
│  Final correctness garantisi:                                │
│  ReservationService::lockForUpdate() — TEK ve SON          │
│                                                              │
│  Implementation:                                              │
│  1. YdlReservationOrchestrator authority + readiness        │
│     kontrolü yapar                                           │
│  2. Human approval alır                                     │
│  3. ReservationService::createReservation() çağırır        │
│     → lockForUpdate + SELECT FOR UPDATE                      │
│     → Overlap check                                          │
│     → INSERT / DomainException                               │
│                                                              │
│  Adım 1-2 başarılı = Adım 3 başarılı demek DEĞİLDIR.     │
└──────────────────────────────────────────────────────────────┘
```

---

## 4. Human Approval Policy

### Pilot Süresince — Tüm CREATE ve CANCEL

| Operasyon | Onay Gereksinimi |
|-----------|-----------------|
| CREATE (conflict-free) | **Human approval zorunlu** |
| CREATE (conflict override) | **Explicit authorized human decision zorunlu** |
| CANCEL | **Human approval zorunlu** |

### Sonraki Aşama — Auto-Approval Policy (Ayrı Karar)

Aşağıdaki koşullar sağlandığında SAAB oylaması ile açılabilir:

```
Auto-Approval Koşulları:
  ├─ PILOT-002 BUSINESS AUTOMATION CERTIFIED
  ├─ ≥6 ay conflict-free CREATE operations
  ├─ Zero double-booking incidents (confirmed by evidence chain)
  ├─ Zero unauthorized overrides
  └─ Explicit SAAB vote → Auto-Approval ALLOWED
```

**Conflict Override asla otomatikleşmez.** `LIMITED + conflict detected = human decision`. Bu invariant hiçbir aşamada gevşetilemez.

---

## 5. Idempotency

```
┌──────────────────────────────────────────────────────────────┐
│  IDEMPOTENCY INVARIANT                                       │
│                                                              │
│  Aynı reservation command tekrar geldiğinde:                 │
│  ├─ İkinci rezervasyon OLUŞMAZ                             │
│  ├─ İkinci side-effect OLUŞMAZ                             │
│  └─ İlk operasyonun sonucu döner                            │
│                                                              │
│  Idempotency Key: event_id veya command_id                  │
│  Storage: ReservationService::createReservation() içinde    │
│           event_id uniqueness constraint                      │
│                                                              │
│  CANCEL için:                                                │
│  ├─ Zaten cancelled → ALREADY_CANCELLED (no-op)             │
│  └─ Cancellation state zaten idempotent                     │
└──────────────────────────────────────────────────────────────┘
```

**Implementation:** `PropertyReservation` tablosunda `external_reservation_id + external_channel` unique constraint veya orchestrator seviyesinde `event_id` ile kontrol.

---

## 6. Cancellation — Canonical Ownership

```
┌──────────────────────────────────────────────────────────────┐
│  CANCELLATION CANONICAL OWNER: ReservationService           │
│                                                              │
│  Terminal State: CANCELLED                                    │
│    ├─ Tekrar CANCEL → idempotent no-op                       │
│    └─ CREATE → yeni reservation oluşur                       │
│                                                              │
│  Availability Release:                                        │
│    ├─ Owner: ReservationService::cancelReservation()        │
│    ├─ Kural: Yalnızca source_system = 'internal'            │
│    │          olan kayıtlar açılır                           │
│    ├─ External (airbnb_ical, booking_connect) kayıtlar        │
│    │          RESERVED kalır — ancak provider üzerinden      │
│    │          açılabilir                                     │
│    └─ Reason: External sistem dışarıdan yönetilir            │
│                                                              │
│  CANCEL sırasında overlap check YOK:                         │
│    └─ Mevcut bir reservation siliniyor — çakışma oluşmaz   │
│                                                              │
│  CANCEL'da authority kontrolü:                              │
│    ├─ FULL → proceed                                        │
│    ├─ LIMITED → scope intersection                          │
│    └─ STOP → BLOCK                                          │
└──────────────────────────────────────────────────────────────┘
```

---

## 7. Evidence Chain

Her operasyon sonucu aşağıdaki alanlarla audit edilebilir:

| Alan | Açıklama |
|------|----------|
| `execution_owner` | Orchestrator class + method |
| `timestamp` | `created_at` UTC |
| `authority_context` | FULL / LIMITED / STOP + scope |
| `canonical_result` | ReservationService return value |
| `event_id` | Idempotency key |
| `ydl_session_id` | YDL context ID |
| `human_decision` | Approval token veya override decision |
| `tenant_id` | Tenant isolation kanıtı |

### Sonuç Türleri

| Sonuç | Evidence Tetiklenir |
|-------|---------------------|
| CREATE success | `ReservationCreatedEvent` → `YdlEventLog` |
| CREATE BLOCKED (conflict) | `ReservationBlockedEvent` + reason |
| CANCEL success | `ReservationCancelledEvent` → `YdlEventLog` |
| OVERRIDE LOGGED | `ReservationOverrideEvent` → `YdlEventLog` + override reason |
| BLOCKED (authority STOP) | `OperationBlockedEvent` + authority context |

---

## 8. Tenant Isolation

```
┌──────────────────────────────────────────────────────────────┐
│  TENANT ISOLATION INVARIANT                                  │
│                                                              │
│  YdlReservationOrchestrator hiçbir aşamada                  │
│  tenant/workspace sınırını gevşetemez.                       │
│                                                              │
│  Zorunlu kontroller:                                        │
│  ├─ Tüm sorgular tenant_id scope içerir                    │
│  ├─ YdlReservationContext tenant bilgisi taşır              │
│  ├─ Event evidence tenant_id loglar                         │
│  └─ Unauthorized cross-tenant erişim → DomainException      │
│                                                              │
│  Test zorunluluğu:                                           │
│  └─ Her orchestrator operasyonu için cross-tenant           │
│     erişim testi (negatif senaryo)                           │
└──────────────────────────────────────────────────────────────┘
```

---

## 9. Never Bypass

Aşağıdaki invariant'lar **hiçbir koşulda**, **hiçbir yetki seviyesinde**, **hiçbir override mekanizmasıyla** bypass edilemez:

### 9.1 Canonical Overlap/Locking

```
lockForUpdate + overlap check — ReservationService::createReservation()
hiçbir zaman bypass edilemez.
```
理由: Double-booking correctness platformun temel invariant'ıdır. Overlap check olmadan yapılan reservation geçersiz sayılır.

### 9.2 Tenant Authorization

```
Cross-tenant reservation oluşturma veya erişim
hiçbir zaman mümkün değildir.
```
理由: Veri izolasyonu yasal ve operasyonel zorunluluktur.

### 9.3 Reservation State Machine

```
Terminal state (CANCELLED) bir kez ulaştıktan sonra
state machine'e geri dönülmez.
```
理由: İş sürekliliği ve audit trail doğruluğu.

### 9.4 Override Authorization

```
ConflictOverrideService::canOverride() return false ise
override asla gerçekleşmez.
STOP authority'de override asla gerçekleşmez.
```
理由: Override = istisna, istisna kuralları asla gevşetilemez.

### 9.5 Event/Evidence Zinciri

```
YdlEventLog::append() çağrısı atlanamaz.
Evidence olmadan operasyon geçersiz sayılır.
```
理由: Audit trail olmadan certification mümkün değildir.

---

## 10. Design Questions — Kesinleştirilmiş Cevaplar

| # | Soru | Cevap |
|---|------|-------|
| 1 | Orchestrator overlap yazar mı? | **HAYIR** — ReservationService tek source of truth |
| 2 | Readiness sonucu correctness kanıtı mı? | **HAYIR** — stale olabilir, lockForUpdate final truth |
| 3 | Conflict-free CREATE pilotta otomatik mi? | **HAYIR** — human approval pilot süresince zorunlu |
| 4 | Conflict override otomatikleşir mi? | **ASLA** — explicit human decision zorunlu |
| 5 | Idempotency nasıl? | event_id unique constraint → no-op |
| 6 | Cancellation owner? | ReservationService (tek yetkili) |
| 7 | External availability release? | Provider'a bırakılır; internal kaynaklar açılır |
| 8 | Evidence her operasyonda? | **EVET** — BLOCKED dahil her sonuç loglanır |
| 9 | Tenant isolation? | **Zorunlu** — hiçbir aşamada gevşetilemez |

---

## 11. Status

```
PILOT-002: DISCOVERY ✅ → CHARTER ✅ → AUTHORITY DESIGN ✅
Next: Wave 1 Implementation (Sonnet 4.6)
```

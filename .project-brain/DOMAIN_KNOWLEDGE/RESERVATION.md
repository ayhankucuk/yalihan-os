# DOMAIN KNOWLEDGE: RESERVATION

**LAST_VERIFIED_HEAD:** 863a1018
**Evidence Level:** REPO_VERIFIED + TEST_VERIFIED

---

## Business Concept: Reservation

**Tanım:**
- Bir mülkün belirli tarihler arasında rezerve edilmesi
- Misafir kabulü için takvimde blok
- Kısa dönem kiralama yönetimi

**SOURCE:** IlanReservation model + ARCHITECTURE_MAP

---

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| Date fields | start_date, end_date | Migration + model |
| Status | reservation_state enum | Model |
| Tenant | tenant_id auto-assign | BelongsToTenant |
| Ilan binding | property_id FK | Migration |

---

## WHO Can Create?

**Business:** Admin/Danisman users
**Technical:** Tenant context required

**EVIDENCE:** IlanCalendarController::store() requires admin auth

**UNKNOWN:** Owner self-service? → UNKNOWN

---

## PRECONDITIONS

| Precondition | Status | Evidence |
|-------------|--------|----------|
| Valid ilan exists | REQUIRED | Controller checks ilan binding |
| No date conflict | REQUIRED | IlanReservationService::hasConflict() |
| Valid date range | REQUIRED | Request validation |

**EVIDENCE:** REPO_VERIFIED (service code)

---

## STATE TRANSITION

```
pending → confirmed → checked_in → completed
         ↘ cancelled (herhangi bir aşamada)
```

**STATE_FIELD:** reservation_state (enum)

**EVIDENCE:** Model enum + service methods

---

## CANCELLATION

| Aspect | Behavior | Evidence |
|--------|----------|----------|
| cancelled_at | Timestamp set | Model |
| Calendar block | NOT removed | isCancelled() method |
| Status change | cancelled | ScopeCancelled |

**RATIONALE:** Geriye dönük raporlama için blok korunur

**EVIDENCE:** REPO_VERIFIED (model code)

**UNKNOWN:** Financial refund policy → UNKNOWN

---

## CALENDAR EFFECT

| State | Calendar Blocked? |
|-------|------------------|
| pending | YES |
| confirmed | YES |
| checked_in | YES |
| completed | NO |
| cancelled | YES (data preserved) |

**EVIDENCE:** AvailabilityService + isActive()

**UNKNOWN:** Past cancellation → calendar display? → UNKNOWN

---

## FINANCIAL EFFECT

| Field | Present? | Evidence |
|-------|----------|----------|
| total_amount | YES | Model fillable |
| Payment lifecycle | UNKNOWN | No payment model found |

**EVIDENCE:** REPO_VERIFIED (model)

**UNKNOWN:** Payment integration → separate domain

---

## NOTIFICATION EFFECT

| Event | Notification | Evidence |
|-------|--------------|----------|
| Created | AdminNotificationService | Service call |
| Cancelled | AdminNotificationService | Service call |

**EVIDENCE:** IlanReservationService + AdminNotificationService

---

## TENANT BOUNDARY

| Check | Required | Evidence |
|-------|----------|----------|
| BelongsToTenant trait | YES | Model |
| Tenant isolation | TEST_VERIFIED | TenantIsolationModifyCancelTest |

**CROSS_TENANT_ACCESS:** BLOCKED (VERIFIED_PASS)

---

## AUTHORIZATION

| Role | Can Create? | Evidence |
|------|-------------|----------|
| admin | YES | Controller auth |
| danisman | YES | Controller auth |

**EVIDENCE:** REPO_VERIFIED

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Cross-tenant read blocked | TenantIsolationModifyCancelTest | PASS |
| Canonical date fields | IlanReservationCanonicalBoundaryTest | PASS |
| Tenant binding | IlanCalendarTenantIsolationTest | PASS |

**EVIDENCE:** TEST_VERIFIED (22/22 total)

---

## BUSINESS VALUE CHAIN

```
Müşteri talep → Kisi → Talep → Matching → Ilan → Rezervasyon → Finans
```

**EVIDENCE:** Dependency graph (ARCHITECTURE_MAP)

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Owner self-service reservation? | Not found in current code |
| Payment/refund policy? | Separate payment domain |
| Cancellation refund calculation? | Not in reservation model |
| Calendar display for past cancellations? | Not verified |

**NOTE:** Business unknowns require Ayhan clarification if relevant for a task.

---

## Related Domains

| Domain | Relationship |
|--------|--------------|
| Ilan | property_id binding |
| Tenant | tenant_id auto-assign |
| Calendar | availability blocking |
| Notification | AdminNotificationService |
| CRM | guest info (guest_name, guest_phone) |

**EVIDENCE:** ARCHITECTURE_MAP cross-domain dependencies

# RESERVATION-GUEST-COMM-WAVE-1 — Sprint Charter

**SAAB Authorization:** Oturum 121 — EXECUTION AUTHORIZED
**Baseline:** `31e8065` — Reservation Event Backbone
**Date:** 2026-08-14
**Wave:** 1 of N — Scope locked

---

## 1. Mission

Rezervasyon oluşturulduktan sonra misafire otomatik olarak onay bildirimi gönderilmesini sağlamak. Bildirim, feature flag ve consent kurallarına tam uyumlu çalışmalı; gerçek gönderim uygun olmadığında `prepared/pending` evidence üretmeli.

**Business Question:** YALIHAN, rezervasyon oluşturulduğunda misafir iletişim sürecini insan müdahalesi olmadan güvenilir biçimde başlatabiliyor mu?

---

## 2. Scope

### Included (Wave 1)
- `ReservationCreatedEvent` → guest confirmation notification
- Template: `reservation_confirmation` (whatsapp + email)
- Channel routing: WhatsApp (Meta Business API) + Email
- Feature flag: `whatsapp_pilot_global` + `pilot_notification_allowlist`
- Consent check: guest phone/email presence
- Queue/retry: `SendGuestConfirmationJob` — `$tries=3`, `$backoff=[30,60,120]`
- Idempotency: `eventId` deduplication (same reservation → one notification)
- Tenant isolation: event envelope `tenantId` → dispatcher
- Delivery evidence: `OutboundNotification` audit record

### Excluded (Future Waves)
- Cancellation notification → Wave 2
- Modification notification → Wave 3
- Check-in reminder → Wave N
- Check-out notification → Wave N
- Availability sync → separate wave
- Airbnb inbound → separate wave

---

## 3. Architecture

```
ReservationCreatedEvent (31e8065)
        │
        ▼
ListenReservationCreated (ShouldQueue, 'notifications')
        │
        ▼
ProcessReservationCreated::handle()
        │
        ├── Guest Communication  ← Wave 1
        │         │
        │         ▼
        │   SendGuestConfirmationJob ($tries=3, backoff=[30,60,120])
        │         │
        │         ▼
        │   GuestCommunicationPolicy (consent + feature flag gate)
        │         │
        │         ▼
        │   TemplateResolver → 'reservation_confirmation'
        │         │
        │         ▼
        │   NotificationDispatcher::dispatch()
        │         │
        │         ├── canDispatch()? ── NO ──→ OutboundNotification STATE_CANCELLED (evidence)
        │         │
        │         └── YES
        │                   │
        │                   ▼
        │             OutboundNotification STATE_PENDING
        │                   │
        │                   ▼
        │             SendNotificationJob (async)
        │                   │
        │                   ▼
        │             WhatsAppAdapter / EmailAdapter
        │                   │
        │                   ▼
        │             OutboundNotification STATE_SENT / STATE_FAILED (evidence)
        │
        ├── Availability Sync  ← NOT WIRED (future wave)
        ├── Financial Recording ← NOT WIRED (future wave)
        └── Stay Operations    ← NOT WIRED (future wave)
```

---

## 4. Key Design Decisions

### 4.1 Idempotency
Notification is deduplicated per `eventId`. If `ProcessReservationCreated` is retried by the queue, the job MUST NOT send a duplicate notification.

Implementation: `GuestCommunicationPolicy::canSend($event)` checks if an `OutboundNotification` record already exists for this `reservationId + templateKey + channel`. If exists with state ≠ `STATE_CANCELLED`, skip.

### 4.2 Feature Flag Safety
`NotificationDispatcher::canDispatch()` is the canonical gate. Even if called directly, it respects:
- `notification_kill_switch` (hardest kill)
- `whatsapp_pilot_global` (master pilot switch)
- `pilot_notification_allowlist` (tenant/property allowlist)

Wave 1 does NOT bypass this gate. `SendGuestConfirmationJob` calls `NotificationDispatcher::dispatch()` which internally calls `canDispatch()`.

### 4.3 Prepared/Pending Evidence
If `canDispatch()` returns `false`, `NotificationDispatcher::dispatch()` still creates an `OutboundNotification` record with `STATE_CANCELLED`. This is the evidence that the system processed the intent but blocked due to policy.

### 4.4 Guest Communication Policy
Before sending, validate:
1. Guest has a reachable contact (phone starts with `0` or `+90`, or email is valid)
2. Consent check: if Kisi model has `iletisim_tercihleri` JSON, check channel preference
3. If no contact → skip silently, log at DEBUG level (not ERROR — this is data quality issue, not system failure)

### 4.5 Tenant Isolation
`SendGuestConfirmationJob` receives `ReservationCreatedEvent` which already carries `tenantId` in its envelope. This tenantId is passed to `NotificationDispatcher::dispatch()` → `canDispatch()` → allowlist check.

---

## 5. File Map

| File | Type | Purpose |
|------|------|---------|
| `app/Jobs/Reservation/SendGuestConfirmationJob.php` | **NEW** | Queued job, idempotent, tenant-scoped |
| `app/Services/Notification/GuestCommunicationPolicy.php` | **NEW** | Consent + contact validation |
| `app/DTOs/Notification/GuestConfirmationNotification.php` | **NEW** | NotificationContract impl for confirmation |
| `app/Contracts/Reservation/ReservationNotificationDispatcherContract.php` | **NEW** | Contract (for future NullReservationNotificationDispatcher) |
| `app/Services/Reservation/NullReservationNotificationDispatcher.php` | **NEW** | Null object for reservation notification dispatcher |
| `app/Models/Notification/OutboundNotification.php` | **EXISTING** | Evidence store |
| `app/Services/Notification/NotificationDispatcher.php` | **EXISTING** | Canonical dispatcher |
| `app/Jobs/Reservation/ProcessReservationCreated.php` | **MODIFY** | Wire Wave 1 comment → job dispatch |
| `app/Providers/AppServiceProvider.php` | **MODIFY** | Bind NullReservationNotificationDispatcher |

---

## 6. Evidence Contract

Every `ReservationCreatedEvent` MUST produce exactly ONE of:

| State | Meaning | OutboundNotification State |
|-------|---------|--------------------------|
| `STATE_SENT` | Delivered to guest | `gonderildi` |
| `STATE_FAILED` | Provider error after retries | `basarisiz` |
| `STATE_CANCELLED` | Feature flag / policy blocked | `iptal` |

Evidence query:
```sql
SELECT * FROM outbound_notifications
WHERE template_key = 'reservation_confirmation'
  AND JSON_EXTRACT(payload_data, '$.reservation_id') = :id
ORDER BY created_at DESC LIMIT 1;
```

---

## 7. Test Scenarios

| ID | Scenario | Expected |
|----|----------|----------|
| GC-T1 | Happy path: valid guest phone, flag on, in allowlist | `STATE_SENT` |
| GC-T2 | Feature flag off: `whatsapp_pilot_global=false` | `STATE_CANCELLED`, reason logged |
| GC-T3 | Tenant not in allowlist | `STATE_CANCELLED` |
| GC-T4 | Guest has no phone or email | Job skips silently, no notification |
| GC-T5 | Duplicate event (idempotency) | Only first call produces evidence; subsequent calls are no-op |
| GC-T6 | Provider error after 3 retries | `STATE_FAILED` |
| GC-T7 | Queue retry: job fails once, succeeds on retry | Evidence shows single `STATE_SENT` |
| GC-T8 | Email channel: valid email, flag on | `STATE_SENT` via EmailAdapter |
| GC-T9 | Both channels fail silently: phone missing + invalid email | Job completes, no evidence (skip silently) |
| GC-T10 | Tenant isolation: event for tenant A | Notification has `tenantId=A` in dispatcher call |

---

## 8. Definition of Done

- [ ] `SendGuestConfirmationJob` implemented with idempotency check
- [ ] `GuestCommunicationPolicy` validates contact + consent
- [ ] `GuestConfirmationNotification` DTO implements `NotificationContract`
- [ ] `ProcessReservationCreated::handle()` dispatches `SendGuestConfirmationJob`
- [ ] `NullReservationNotificationDispatcher` bound in `AppServiceProvider`
- [ ] `NotificationContract` for reservation bound correctly
- [ ] Feature flag respected — no bypass
- [ ] Tenant isolation: `tenantId` passed to dispatcher
- [ ] Queue retry: `$tries=3`, backoff `[30, 60, 120]`
- [ ] Delivery evidence: `OutboundNotification` record for every event
- [ ] GC-T1..GC-T10: 10/10 PASS
- [ ] SAB integrity scan: 0 new violations
- [ ] No new silent catch blocks

---

## 9. Non-Goals (Future Waves)

- Cancellation notification
- Modification notification
- Check-in / check-out reminders
- Availability sync
- Financial recording
- Stay operation task generation

These are documented in `ProcessReservationCreated.php` as TODO comments. They remain TODOs.

---

## 10. Debt Link

| Debt | Status | Note |
|------|--------|------|
| LIFECYCLE-DEBT | 🟡 OPEN | Override path does not fire `ReservationCancelledEvent`. Wave 1 does NOT touch cancellation. This debt remains open for cancellation wave. |
| REGRESSION-DEBT G34 | 🟡 TRACKED | Pre-existing Booking test fail. Wave 1 is unrelated. |

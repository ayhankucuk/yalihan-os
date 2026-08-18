# CHECKIN_CHECKOUT Wave 3 — Secure Guest Credential Communication
## Architecture Decision

> **Baseline:** `8782a4fa` (Wave 2 CERTIFIED)
> **Authority:** SAAB Architecture Board
> **Mode:** DECISION ONLY — Production code prohibited
> **Model:** Claude Opus 4.8
> **Date:** 2026-08-16

---

## 1. Domain: Secure Guest Credential Communication

### 1.1 Business Outcome

> **Business Rule (Invariant):** In a ready, valid reservation with an active access credential, the correct guest receives the access credential via the correct channel, at the correct time, without the credential entering AI/model context, queue serialization, logs, or generic notification history tables.

### 1.2 Credential Lifecycle States

```
CREATED → ISSUED → DELIVERY_PENDING → SENT / FAILED / CANCELLED
                                    ↘ REPLAY_REQUESTED
```

---

## 2. Wave 3 Trigger

**Canonical trigger:** `CheckinWindowOpenedEvent`

Reuse existing Wave 2 event — DO NOT create a new event. Listener dispatches `SendAccessCredentialJob`.

---

## 3. Twelve Design Decisions

### D1: Trigger Orchestration — Job vs Direct Dispatch

**Decision:** Separate queued job. NOT direct dispatch.

**Rationale:**
- `CheckinWindowOpenedEvent` fires synchronously from `OpenCheckinWindowJob` at 07:00 daily. Direct dispatch would couple event production to delivery, violating separation of concerns.
- Queue decoupling ensures delivery retry is independent of trigger scheduling.
- `SendGuestConfirmationJob` (Wave 1) follows the same pattern — consistency.

**Binding:** `SendAccessCredentialJob` dispatched from `OpenCheckinWindowJob` or from `CheckinWindowOpenedEvent` listener.

```
OpenCheckinWindowJob (07:00, daily)
  └─ GuestArrivalReadinessService::openCheckinWindow()
  └─ CheckinWindowOpenedEvent dispatched
        └─ ListenCheckinWindowOpenedCredential: dispatch SendAccessCredentialJob
             └─ SendAccessCredentialJob::handle()
```

### D2: Readiness Re-validation Gate

**Decision:** Re-validate ALL readiness conditions in `SendAccessCredentialJob::handle()` even if `CheckinWindowOpenedEvent` was dispatched.

**Mandatory preconditions (MUST all be true before dispatch):
1. Reservation `reservation_state = CONFIRMED`
2. `cancelled_at = null`
3. `checkin_window_opened_at` is set
4. `property_readiness.is_ready = true` (property_clean + access_credential_ready + guest_contact_ready)
5. Active non-expired `AccessCredential` exists for `ilan_id`
6. Guest contact info present (phone OR email)

**Rationale:** Readiness can change between event dispatch and job execution (e.g., Ayhan marks hazirlik Gorev completed, credential issued/expired, reservation cancelled manually).

**Policy:** If preconditions fail: log with masked context, skip delivery, do NOT throw. Record `SKIPPED` evidence in `outbound_notifications` with reason code. Do NOT send partial credential. DO NOT expose credential in logs.

### D3: Credential Decryption Scope

**Decision:** Decrypt `AccessCredential::getCredentialValue()` at most ONCE, in `SendAccessCredentialJob::handle()`, immediately before API/transport call.

**Rationale:** `Crypt::decryptString()` must be called in the job handler, nowhere else. No decrypt in listeners, observers, or queue serialization. Plaintext never enters log, event, or notification payload.

**Rule:** Decrypt → render → send → discard. Plaintext never stored, logged, cached, or serialized.

```
SendAccessCredentialJob::handle()
  └─ AccessCredentialService::getActiveCredential($ilan)
  └─ $plainCode = $credential->getCredentialValue()  ← DECRYPT HERE ONLY
  └─ Render template with $plainCode
  └─ Dispatch via NotificationDispatcher
  └─ $plainCode = null  ← DISCARDED immediately after use
```

### D4: Credential Transport — Payload Architecture

**Decision:** Credential travels as a render-context variable only. NEVER as a serialized job property, event property, notification DTO property, queue payload field, or OutboundNotification record field.

**Immutable rule:** `AccessCredential::$credential_value` is NOT a property of any event, DTO, job state, queue message, or notification history record.

**Channel-specific delivery patterns:**

| Channel | Credential Representation | Delivery Mechanism |
|---------|------------------------|------------------|
| WhatsApp | `{{access_code}}` in template | `SendAccessCredentialJob` → `WhatsAppAdapter` → Meta Graph API |
| Email | `{{access_code}}` in template | `SendAccessCredentialJob` → `EmailAdapter` → Laravel Mail |
| Telegram | `{{access_code}}` in message | `SendAccessCredentialJob` → `TelegramAdapter` → Bot API |
| SMS | Direct string in template | Direct API call (Twilio/Netgsm) |

**Template rendering pipeline:**
```
SendAccessCredentialJob
  └─ AccessCredentialService::getActiveCredential() → decrypt once
  └─ TemplateResolver::resolve('access_delivery', $channel, $data)
  └─ Inject {{access_code}} with PLAINTEXT credential (job memory only)
  └─ Dispatch rendered message to adapter
  └─ Discard plaintext immediately
```

### D5: Channel Authority

**Decision:** `GuestCommunicationPolicy::getEligibleChannels()` scope is extended to Wave 3. NotificationAuthorityService governs template resolution per event. Tenant isolation enforced at EVERY dispatch call.

**Channel selection logic:**
1. Guest contact available? (phone OR email)
2. Consent checked per channel via `Kisi.iletisim_tercihleri` JSON
3. Tenant pilot allowlist checked via `NotificationDispatcher::canDispatch()`
4. Channel selected: phone → WhatsApp preferred, email fallback

**Canonical authority:** `NotificationDispatcher::canDispatch()` is the global kill switch. Override by SAAB decision only.

**Out of scope for Wave 3:**
- Smart lock API (credential_type='smart_lock') — Wave 3 external provider interface only
- Guest portal (channel delivery receipt) — Wave 3 receipt confirmation only
- Manual re-send by Ayhan — Wave 3 admin command only

### D6: Idempotency Key

**Decision:** `OutboundNotification` scope by composite key: `tenant_id + reservation_id + channel + template_key = 'access_delivery'`

**Idempotency rule:** Exactly one delivery attempt per (reservation, channel, access_delivery template) unless `OutboundNotification.state = SKIPPED` or `state = CANCELLED`.

**Implication:** If already delivered, `SendAccessCredentialJob` returns silently. If delivery failed, `NotificationRetryService` handles retry. If cancelled, no delivery.

**Idempotency enforcement in job:**
```php
$existing = OutboundNotification::query()
    ->where('tenant_id', $tenantId)
    ->where('reservation_id', $reservationId)
    ->where('channel', $channel)
    ->where('template_key', 'access_delivery')
    ->whereIn('delivery_state', ['SENT', 'SKIPPED'])
    ->exists();
if ($existing) { return; /* idempotent no-op */ }
```

### D7: Retry / Backoff / Evidence Model

**Decision:** Reuse existing `SendGuestConfirmationJob` retry pattern: `$tries=3`, `$backoff=[30, 60, 120]`. `SendAccessCredentialJob` implements `ShouldQueue` with identical retry semantics.

**Evidence state machine:**

```
SENT (delivered to adapter)
  ↘ DELIVERY_FAILED (adapter returned error)
       ↘ RETRY_SCHEDULED (within tries)
           ↘ DELIVERY_FAILED (tries exhausted) → FAILED / SKIPPED
  ↘ CANCELLED (cancellation race won)
  ↘ RESOLVED (replay by admin)
```

**Evidence record always written** regardless of success/failure/skip. Evidence is append-only.

**No 5xx retry in template rendering or credential decryption** — only in transport layer (WhatsApp API, SMTP, Telegram Bot API).

### D8: Cancellation Race Safety

**Decision:** Cancellation is the winner. Delivery job MUST re-validate reservation state in `handle()` before dispatching.

**Race sequence:**
```
t=0: OpenCheckinWindowJob fires, CheckinWindowOpenedEvent dispatched
t=1: SendAccessCredentialJob queued
t=2: Ayhan cancels reservation (ResApp)
t=3: SendAccessCredentialJob executes handle()
```

**Protection at job execution:**
```php
SendAccessCredentialJob::handle()
  └─ $reservation = PropertyReservation::find($id)
  └─ if ($reservation->cancelled_at !== null) → SKIP, evidence = 'CANCELLED_AFTER_WINDOW_OPEN'
  └─ if (!$readiness->is_ready) → SKIP, evidence = 'READINESS_INCOMPLETE'
```

**Cancellation event listener** (`ListenReservationCancelled`) DOES NOT undo delivery. Cancellation is prevention, not rollback. Delivery evidence is append-only.

### D9: Evidence Record

**Decision:** `OutboundNotification` record with `template_key = 'access_delivery'` captures immutable delivery evidence.

**Required fields:**
- `tenant_id`, `reservation_id`, `ilan_id`, `channel`, `template_key`
- `delivery_state` (SENT / FAILED / SKIPPED / CANCELLED)
- `credential_type` (key/code/lockbox/smart_lock) — NOT the value
- `delivery_attempted_at` — timestamp of dispatch
- `delivery_result` — adapter response summary (no credential data)
- `failure_reason_code` — ENUM: INVALID_STATE / CONSENT_DENIED / NO_CREDENTIAL / TRANSPORT_ERROR / TIMEOUT / API_ERROR / RECIPIENT_ERROR / UNDELIVERABLE
- `retry_count`, `last_retry_at`

**Prohibited in evidence record:** credential value, credential location, decrypted access code, `access_code` template variable content.

### D10: Replay / Manual Resend

**Decision:** Admin command `credential:resend {reservation_id}` creates new `OutboundNotification` with `state = RESOLVED` via `NotificationRetryService::resetForManualRetry()`. New job dispatched with fresh idempotency check.

**Reuse path:**
```php
php artisan credential:resend {reservation_id} [--channel=whatsapp|email|telegram]
```

**Idempotency:** If existing record is `SENT` and `credential_delivery` is `SENT`, command rejects with warning.

### D11: Tenant Isolation

**Decision:** Tenant isolation enforced at FIVE layers:

| Layer | Mechanism |
|-------|------------|
| 1. DB | `BelongsToTenant` global scope on `AccessCredential` + `PropertyReadiness` |
| 2. Reservation | Explicit `tenant_id` check in `SendAccessCredentialJob::handle()` |
| 3. Credential service | `AccessCredentialService::getActiveCredential()` — tenant check via `TenantContextService` |
| 4. Dispatcher | `NotificationDispatcher::canDispatch()` — tenant pilot allowlist |
| 5. Adapter | Channel adapter receives pre-validated tenant-scoped data |

**Invitation:** No adapter receives raw credential or tenant context. All inputs are pre-validated. Adapter receives only rendered template + channel + recipient.

### D12: Hermes/AI Workforce Relationship

**Decision:** Hermes AI has ZERO authority over credential lifecycle.

**Principle:** AI prepares context, human approves intent. Credential lives in deterministic application boundary.

**Hermes authority:**
- Reads `property_readiness` status (read-only)
- Reads `CheckinWindowOpenedEvent` notification status (read-only)
- Reads `OutboundNotification` delivery evidence (read-only)
- Receives notification from Ayhan via Telegram/bot

**Hermes forbidden actions:**
- NEVER decrypts `AccessCredential::getCredentialValue()`
- NEVER sends credential via any channel
- NEVER writes to `outbound_notifications`
- NEVER triggers `SendAccessCredentialJob`
- NEVER issues or revokes credentials

**Reasoning:** Credential is a business secret. AI context is observable by LLM providers (OpenAI/Anthropic API calls may include conversation history. Credential plaintext entering LLM context = compliance violation + security risk.

---

## 4. Security Invariants

### INV-W3-S1: Credential never in queue serialization
`SendAccessCredentialJob` state contains ONLY reservation_id. Credential resolved at execution time via `AccessCredentialService`.

### INV-W3-S2: Credential never in event envelopes
`CheckinWindowOpenedEvent` contains NO credential data. Credential resolved in job handler only.

### INV-W3-S3: Credential never in notification history
`OutboundNotification.template_key='access_delivery'` records channel + timestamp + result code. Never the credential value.

### INV-W3-S4: Credential never in Hermes/AI context
No credential field enters AI prompt context. `GuestArrivalReadinessService` returns boolean readiness, never credential values.

### INV-W3-S5: Credential decrypted at most once per delivery
Single decryption point in `SendAccessCredentialJob::handle()`. All subsequent processing uses the in-memory plaintext reference.

### INV-W3-S6: Receipt notification has no credential
Guest receipt notification is SEPARATE from access delivery. Receipt: "Rezervasyon #X onaylandı." Delivery: credential via separate channel with separate idempotency key.

---

## 5. Runtime Sequence

```
[OpenCheckinWindowJob] daily 07:00
  │
  ├─ GuestArrivalReadinessService::openCheckinWindow()
  ├─ CheckinWindowOpenedEvent::dispatch()
  │
  └─ ListenReservationCreatedReadiness::listen()
         ├─ getOrCreateReadiness() idempotent
         └─ getOrCreateReadiness() idempotent

[CheckinWindowOpenedEvent listener: ListenCredentialTrigger]
  └─ dispatch(SendAccessCredentialJob)

[SendAccessCredentialJob] queue=credentials, tries=3, backoff=[30,60,120]
  │
  ├─ Guard: GuardsAgentWrites block (AI writes blocked)
  ├─ Load reservation (tenant-scoped)
  ├─ Readiness re-validation (D2)
  ├─ If FAILED → evidence SKIPPED / INCOMPLETE
  │
  ├─ AccessCredentialService::getActiveCredential() — tenant-isolated
  │     └─ Ilan::withoutGlobalScopes() lookup
  ├─ GuestCommunicationPolicy::getEligibleChannels() — consent + contact
  │     └─ Kisi::iletisim_tercihleri JSON check
  ├─ For each channel:
  │     ├─ OutboundNotification idempotency check
  │     ├─ TemplateResolver::resolve('access_delivery', channel, $data)
  │     ├─ Inject plaintext credential (job memory only)
  │     ├─ NotificationDispatcher::dispatch()
  │     │     ├─ Pilot gate (tenant/property allowlist)
  │     │     ├─ OutboundNotification evidence record
  │     │     └─ Adapter call (WhatsApp/Email/Telegram)
  │     └─ Discard plaintext immediately
  │
  └─ NotificationRetryService::markSent() / markFailed()

[OutboundNotification] append-only evidence
```

---

## 6. Credentials Not Shared / Not Inherited

No shared state between `SendGuestConfirmationJob` (Wave 1) and `SendAccessCredentialJob` (Wave 3). Separate `OutboundNotification` records. Separate idempotency keys. Separate templates.

---

## 7. Implementation Scope (Frozen for Wave 3)

### In Scope
- `SendAccessCredentialJob` — queued, idempotent, tenant-scoped
- `AccessCredentialDeliveryNotification` DTO — implements `NotificationContract`
- `ListenCredentialTrigger` listener — wires `CheckinWindowOpenedEvent → SendAccessCredentialJob`
- `access_delivery` notification templates (WhatsApp, Email, Telegram)
- `credential:resend` Artisan command for replay
- Evidence tests 21-28 (E21-E28)
- Regression: Wave 1+2 existing tests unchanged

### Out of Scope (Wave 3 Locked)
- Smart lock API provider integration
- Guest portal / self-check-in
- Financial reconciliation
- Hermes AI credential authority (explicitly prohibited)
- General notification template editor
- Multi-credential delivery (one credential per reservation per channel)
- WhatsApp interactive buttons / rich templates
- Delivery receipt webhooks (channel callbacks)
- Credential rotation automation
- General AI workforce refactor

---

## 8. Implementation Authorization

**Authorization:** GRANDED

Sonnet 4.6 (Kilo Code) is authorized to implement Wave 3 per this architecture decision. Implementation scope is frozen as Section 7. Any deviation requires SAAB escalation.

**Authorization hash:** `e48f488a` (certification docs) → Wave 2 baseline.

---

**Decision Status:** APPROVED
**Authority:** SAAB Architecture Board
**Date:** 2026-08-16
**Model:** Claude Opus 4.8
**Next:** Sonnet 4.6 Implementation → Gemini 3.7 Flash Adversarial Inspection → SAAB Certification

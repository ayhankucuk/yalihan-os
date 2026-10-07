# DOMAIN KNOWLEDGE: NOTIFICATIONS

**LAST_VERIFIED_HEAD:** a7aa6532
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: Notifications

**Tanım:**
- Çoklu kanal bildirim sistemi
- Email, WhatsApp, Telegram, Instagram, Webhook
- Admin ve Guest bildirimleri

**SOURCE:** NotificationDispatcher, AdminNotification, OutboundNotification

---

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| Dispatcher | NotificationDispatcher | app/Services/Notification/NotificationDispatcher.php |
| Audit Log | OutboundNotification | app/Models/Notification/OutboundNotification.php |
| Templates | NotificationTemplate | app/Models/Notification/NotificationTemplate.php |

---

## Notification Channels

| Channel | Adapter | Evidence |
|---------|---------|----------|
| Email | EmailAdapter | NotificationDispatcher |
| WhatsApp | WhatsAppAdapter | WhatsAppNotificationService |
| Telegram | TelegramAdapter | NotificationDispatcher |
| Instagram | InstagramAdapter | NotificationDispatcher |
| Webhook | WebhookAdapter | NotificationDispatcher |

---

## Notification Models

| Model | Table | Purpose |
|-------|-------|---------|
| AdminNotification | admin_notifications | Admin dashboard bildirimleri |
| Notification | notifications | Genel bildirimler |
| OutboundNotification | outbound_notifications | Gönderim audit log |
| NotificationTemplate | notification_templates | Template yönetimi |
| TelegramNotification | telegram_notifications | Telegram özel |

---

## Dispatcher Flow

```
NotificationContract
        ↓
NotificationDispatcher::dispatch()
        ↓
logOutbound() → OutboundNotification (audit)
        ↓
routeToAdapter() → Channel-specific adapter
        ↓
Adapter::send() → Actual delivery
```

---

## Notification Types

| Type | Model | Evidence |
|------|-------|----------|
| Reservation Confirmation | GuestConfirmationNotification | DTO |
| Reservation Cancellation | GuestCancellationNotification | DTO |
| Access Credentials | AccessCredentialNotification | DTO |
| Generic | GenericNotification | DTO |

---

## NotificationAuthorityService

**Purpose:** Notification yetkilendirme kontrolü

**Functions:**
- canDispatch() - Gönderim izni kontrolü

**Evidence:** REPO_VERIFIED

---

## WhatsApp Integration

**Service:** WhatsAppNotificationService
**Manager:** WhatsAppNotificationManager

**Evidence:** app/Services/Notification/WhatsAppNotificationService.php

---

## Retry Mechanism

**Service:** NotificationRetryService

**Purpose:** Başarısız bildirimlerin yeniden denenmesi

**Evidence:** REPO_VERIFIED

---

## TENANT ISOLATION

| Check | Status | Evidence |
|-------|--------|----------|
| Tenant-scoped dispatch | UNKNOWN | Not fully verified |

---

## Reservation Connection

| Connection | Evidence |
|-----------|----------|
| ReservationCreated → AdminNotification | AdminNotificationService |
| GuestConfirmationNotification | Reservation lifecycle |
| GuestCancellationNotification | Reservation cancellation |

---

## Related Domains

| Domain | Connection |
|--------|------------|
| Reservation | Bildirim tetikleyici |
| User | Alıcı (admin) |
| Kisi | Misafir bilgisi (guest_email, guest_phone) |
| Hermes | Event-based triggering |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| - | NONE | Not tested |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| WhatsApp API integration? | Not documented |
| Telegram bot setup? | Not verified |
| Template management? | Partial |
| Notification scheduling? | Not documented |

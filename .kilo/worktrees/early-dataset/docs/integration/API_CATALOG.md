# API Catalog

> **Version:** 1.0.0  
> **Office:** Integration Office (SAAB v6)  
> **Date:** 2026-06-28  
> **Status:** APPROVED

---

## 1. Overview

Complete catalog of all APIs consumed and provided by YALIHAN OS. APIs are classified as **Internal** (Laravel services) or **External** (third-party platforms).

---

## 2. Internal APIs

### 2.1 Ilan Service API

**Base Path:** `/api/v1/ilan`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ilan` | List listings (paginated, filterable) |
| GET | `/ilan/{id}` | Get listing details |
| POST | `/ilan` | Create listing |
| PUT | `/ilan/{id}` | Update listing |
| DELETE | `/ilan/{id}` | Soft delete listing |
| POST | `/ilan/{id}/photos` | Upload photos |
| GET | `/ilan/{id}/history` | Audit history |

**Authentication:** Bearer Token (tenant-scoped)

**Filters:**
- `yayin_durumu`: active, inactive, pending
- `il`: city code
- `tip`: property type
- `fiyat_min`, `fiyat_max`: price range
- `created_after`, `created_before`: date range

### 2.2 Tenant API

**Base Path:** `/api/v1/tenant`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tenant/profile` | Current tenant profile |
| PUT | `/tenant/profile` | Update profile |
| GET | `/tenant/usage` | API usage stats |
| POST | `/tenant/api-keys` | Generate API key |
| DELETE | `/tenant/api-keys/{id}` | Revoke API key |

### 2.3 AI Cortex API

**Base Path:** `/api/v1/ai`

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/ai/describe` | Generate property description |
| POST | `/ai/summarize` | Summarize text |
| POST | `/ai/embeddings` | Generate embeddings |
| POST | `/ai/chat` | Chat with AI assistant |
| POST | `/ai/analyze-image` | Analyze property photo |

**Request:**
```json
{
  "model": "deepseek-chat",
  "prompt": "...",
  "context": {
    "ilan_id": 123,
    "tenant_id": 456
  }
}
```

### 2.4 Webhook API

**Base Path:** `/api/v1/webhooks`

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/webhooks/tenant` | Register tenant webhook |
| GET | `/webhooks` | List registered webhooks |
| DELETE | `/webhooks/{id}` | Unregister webhook |
| POST | `/webhooks/{id}/test` | Test webhook delivery |

---

## 3. External APIs

### 3.1 Google Workspace APIs

#### Gmail API
**Endpoint:** `https://gmail.googleapis.com/gmail/v1`

| Resource | Methods |
|-----------|---------|
| `users.messages` | list, get, send, modify, batchDelete |
| `users.labels` | list, create, update, delete |
| `users.settings` | get, update |

#### Calendar API
**Endpoint:** `https://www.googleapis.com/calendar/v3`

| Resource | Methods |
|-----------|---------|
| `calendars` | get, patch, update |
| `events` | list, get, insert, update, delete, instances |
| `freebusy` | query |

#### Drive API
**Endpoint:** `https://www.googleapis.com/drive/v3`

| Resource | Methods |
|-----------|---------|
| `files` | list, get, create, update, delete, copy |
| `permissions` | list, create, delete |
| `replies` | list, create (for comments) |

#### Contacts API
**Endpoint:** `https://people.googleapis.com/v1`

| Resource | Methods |
|-----------|---------|
| `people` | get, create, update, delete |
| `connections` | list (get contacts) |

### 3.2 Telegram Bot API

**Base:** `https://api.telegram.org/bot{token}`

| Method | Description |
|--------|-------------|
| `getMe` | Bot info |
| `sendMessage` | Send text message |
| `sendPhoto` | Send photo |
| `forwardMessage` | Forward message |
| `editMessageText` | Edit message |
| `answerCallbackQuery` | Callback response |
| `getUpdates` | Get updates (long polling) |
| `setWebhook` | Configure webhook |

### 3.3 WhatsApp Business API

**Base:** `https://graph.facebook.com/v18.0`

| Endpoint | Description |
|----------|-------------|
| `/{phone_number_id}/messages` | Send message |
| `/{phone_number_id}/message_templates` | Manage templates |
| `/{waba_id}/phone_numbers` | Register phone |

### 3.4 Airbnb API

**Base:** `https://api.airbnb.com/api/v3`

| Endpoint | Description |
|----------|-------------|
| `/listings` | Manage listings |
| `/calendar` | Availability calendar |
| `/pricing` | Dynamic pricing |
| `/reservations` | Booking sync |
| `/photos` | Photo management |

**Authentication:** OAuth 2.0 Partner API

### 3.5 Sahibinden API

**Base:** Partner API (request access)

| Endpoint | Description |
|----------|-------------|
| `/ilan/ekle` | Create listing |
| `/ilan/guncelle` | Update listing |
| `/ilan/sil` | Delete listing |
| `/ilan/detay` | Get listing details |
| `/ilanlarim` | My listings |

**Authentication:** API Key + SHA256 signature

### 3.6 Hepsiemlak API

**Base:** Partner API (request access)

| Endpoint | Description |
|----------|-------------|
| `/real-estates` | CRUD operations |
| `/categories` | Category mapping |
| `/districts` | District codes |

**Authentication:** Bearer Token (partner credentials)

---

## 4. API Versioning Strategy

| Strategy | Implementation |
|----------|----------------|
| **Version in Path** | `/api/v1/`, `/api/v2/` |
| **Deprecation** | 6-month notice via headers |
| **Breaking Changes** | New version only |
| **Non-Breaking** | Additive only |

**Response Headers:**
```
X-API-Version: 1.0
X-Rate-Limit-Remaining: 950
X-Rate-Limit-Reset: 1624567890
```

---

## 5. Rate Limits

| API Tier | Limit | Window |
|----------|-------|--------|
| **Free** | 100 | minute |
| **Pro** | 1,000 | minute |
| **Enterprise** | 10,000 | minute |
| **Internal** | Unlimited | — |

---

## 6. Error Format

All APIs return consistent error format:

```json
{
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "Listing with ID 123 not found",
    "details": {
      "resource": "ilan",
      "id": 123
    },
    "request_id": "req_abc123"
  }
}
```

**HTTP Status Codes:**
- `400` — Validation error
- `401` — Authentication required
- `403` — Forbidden (tenant mismatch)
- `404` — Resource not found
- `409` — Conflict (duplicate)
- `422` — Business rule violation
- `429` — Rate limited
- `500` — Internal error

---

*Document approved by SAAB v6 Integration Office*

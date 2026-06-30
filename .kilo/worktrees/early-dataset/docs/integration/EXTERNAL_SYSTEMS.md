# External Systems Integration Guide

> **Version:** 1.0.0  
> **Office:** Integration Office (SAAB v6)  
> **Date:** 2026-06-28  
> **Status:** APPROVED

---

## 1. Overview

This document details integration specifications for all external systems consumed by YALIHAN OS. Each integration follows a standardized pattern: **OAuth/API Key authentication**, **webhook-driven updates**, and **batch sync capability**.

---

## 2. Google Workspace

### 2.1 Capabilities Integrated

| Service | Use Case | Integration Pattern |
|---------|----------|-------------------|
| **Gmail** | Client communication, viewing confirmations | OAuth 2.0 + Send via API |
| **Calendar** | Viewing appointments, agent schedules | OAuth 2.0 + Event Webhooks |
| **Drive** | Document storage, contracts, agreements | OAuth 2.0 + Upload API |
| **Contacts** | Client contact management | OAuth 2.0 + People API |

### 2.2 Authentication

```php
// OAuth 2.0 Scopes Required
$scopes = [
    'https://www.googleapis.com/auth/gmail.send',
    'https://www.googleapis.com/auth/gmail.compose',
    'https://www.googleapis.com/auth/calendar',
    'https://www.googleapis.com/auth/drive.file',
    'https://www.googleapis.com/auth/contacts',
];

// Service Account (for system operations)
$credentials = new Google\ServiceAccountCredentials($scopes, $serviceAccountJson);
```

### 2.3 Configuration

```yaml
google_workspace:
  mode: "service_account"  # or "oauth" for user-delegated
  
  service_account:
    project_id: "yalihan-ai-prod"
    credentials_path: "/secrets/google-service-account.json"
    
  tenant_oauth:
    redirect_uri: "https://api.yalihan.ai/auth/google/callback"
    prompt: "consent"
    access_type: "offline"
```

### 2.4 Rate Limits & Quotas

| API | Quota | Reset |
|-----|-------|-------|
| Gmail Send | 1,000,000 quota units/day | Daily |
| Calendar | 1,000,000 quota units/day | Daily |
| Drive | 10,000 writes/day | Daily |
| Contacts | 500 requests/minute | Minute |

### 2.5 Implementation

```php
// app/Integrations/Google/GmailService.php
class GmailService
{
    public function sendViewingConfirmation(
        string $clientEmail,
        array $viewingDetails
    ): MessageResult {
        $message = (new Message())
            ->setTo($clientEmail)
            ->setSubject('Property Viewing Confirmation - ' . $viewingDetails['ilan_baslik'])
            ->setBody($this->renderTemplate('emails.viewing-confirmation', $viewingDetails));
            
        return $this->gmail->users->messages->send('me', $message);
    }
}
```

---

## 3. Telegram

### 3.1 Capabilities Integrated

| Feature | Use Case |
|---------|----------|
| **Bot Messages** | Property alerts, status updates |
| **Inline Keyboards** | Quick actions (approve, reject, view) |
| **Groups/Channels** | Team notifications |
| **Webhooks** | Inbound user messages |

### 3.2 Authentication

```yaml
telegram:
  bot_token: "${TELEGRAM_BOT_TOKEN}"
  api_base_url: "https://api.telegram.org"
  
  webhook:
    secret_token: "${TELEGRAM_WEBHOOK_SECRET}"
    endpoint: "/api/v1/webhooks/telegram"
```

### 3.3 Rate Limits

| Operation | Limit |
|-----------|-------|
| Messages (global) | 30 msg/sec |
| Messages (per chat) | 20 msg/sec |
| Forwarding | 60 msg/min |
| Groups | 20 msg/min |

### 3.4 Implementation

```php
// app/Integrations/Telegram/TelegramService.php
class TelegramService
{
    public function sendPropertyAlert(
        int $chatId,
        Ilan $ilan
    ): bool {
        $keyboard = new InlineKeyboard([
            [new InlineKeyboardButton([
                'text' => 'View Details',
                'url' => route('frontend.ilan.show', $ilan->id)
            ])],
            [new InlineKeyboardButton([
                'text' => 'Schedule Viewing',
                'callback_data' => "viewing_request:{$ilan->id}"
            ])]
        ]);
        
        return $this->bot->sendMessage($chatId, $this->formatAlert($ilan), [
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }
}
```

---

## 4. WhatsApp

### 4.1 Capabilities Integrated

| Feature | Use Case |
|---------|----------|
| **Template Messages** | Appointment reminders, status updates |
| **Reactive Messages** | User replies, confirmations |
| **Media Sharing** | Property photos, documents |
| **Business Profile** | Brand presence |

### 4.2 Authentication

```yaml
whatsapp:
  api_version: "v18.0"
  phone_number_id: "${WHATSAPP_PHONE_ID}"
  access_token: "${WHATSAPP_ACCESS_TOKEN}"
  
  webhook:
    verify_token: "${WHATSAPP_VERIFY_TOKEN}"
    endpoint: "/api/v1/webhooks/whatsapp"
```

### 4.3 Rate Limits

Per WhatsApp Business Policy:
- Template messages: Per-approved template quota
- Reactive messages: 24-hour window after user contact
- Media: 30MB max per file

### 4.4 Implementation

```php
// app/Integrations/WhatsApp/WhatsAppService.php
class WhatsAppService
{
    public function sendViewingReminder(
        string $phone,
        array $viewingData
    ): MessageResult {
        return $this->graphApi->sendTemplateMessage(
            $this->phoneNumberId,
            $phone,
            'viewing_reminder_24h',
            [
                ['type' => 'body', 'text' => $viewingData['client_name']],
                ['type' => 'body', 'text' => $viewingData['ilan_baslik']],
                ['type' => 'body', 'text' => $viewingData['viewing_time']],
            ]
        );
    }
}
```

---

## 5. Airbnb Partner API

### 5.1 Capabilities Integrated

| Feature | Use Case |
|---------|----------|
| **Listings Sync** | Publish/update properties |
| **Calendar Sync** | Availability management |
| **Pricing** | Dynamic price updates |
| **Photos** | Property image gallery |

### 5.2 Authentication

```yaml
airbnb:
  environment: "production"  # or "sandbox"
  
  oauth:
    client_id: "${AIRBNB_CLIENT_ID}"
    client_secret: "${AIRBNB_CLIENT_SECRET}"
    redirect_uri: "https://api.yalihan.ai/auth/airbnb/callback"
    partner_api_url: "https://partner-api.airbnb.com"
```

### 5.3 Rate Limits

| Endpoint | Limit |
|----------|-------|
| Listings | 1000/hour |
| Calendar | 500/hour |
| Pricing | 500/hour |
| Photos | 200/hour |

### 5.4 Listing Mapping

| YALIHAN Field | Airbnb Field | Transform |
|--------------|--------------|-----------|
| `baslik` | `listing_name` | Translate to EN |
| `aciklama` | `description` | AI translate |
| `fiyat` | `nightly_price` | Currency conversion |
| `lat/lng` | `lat_lng` | Direct |
| `fotolar` | `photos[]` | Resize to 1920px |
| `kapasite` | `guests_included` | Direct |

### 5.5 Implementation

```php
// app/Integrations/Airbnb/AirbnbSyncService.php
class AirbnbSyncService
{
    public function syncListing(Ilan $ilan): SyncResult
    {
        $airbnbListing = $this->mapper->toAirbnbFormat($ilan);
        
        try {
            $response = $this->partnerApi->listings()->createOrUpdate($airbnbListing);
            $this->syncCalendar($ilan);
            $this->syncPhotos($ilan);
            
            return SyncResult::success($response['id']);
        } catch (AirbnbApiException $e) {
            Hermes::publish(Event::make('marketplace.failed')
                ->withPayload(['source' => 'airbnb', 'ilan_id' => $ilan->id, 'error' => $e->getMessage()]));
            throw $e;
        }
    }
}
```

---

## 6. Sahibinden Integration

### 6.1 Capabilities Integrated

| Feature | Use Case |
|---------|----------|
| **Listing CRUD** | Publish properties |
| **Category Mapping** | Property type sync |
| **Statistics** | View counts, inquiries |

### 6.2 Authentication

```yaml
sahibinden:
  api_url: "https://api.sahibinden.com"
  api_key: "${SAHIBINDEN_API_KEY}"
  api_secret: "${SAHIBINDEN_API_SECRET}"
  member_id: "${SAHIBINDEN_MEMBER_ID}"
  
  signature:
    algorithm: "SHA256"
    timestamp_header: "X-Sahibinden-Timestamp"
    signature_header: "X-Sahibinden-Signature"
```

### 6.3 Rate Limits

| Endpoint | Limit |
|----------|-------|
| Create | 100/hour |
| Update | 500/hour |
| Delete | 50/hour |
| List | 1000/hour |

### 6.4 Category Mapping

| YALIHAN Tip | Sahibinden Category |
|-------------|-------------------|
| `villa` | 1150 (Villa for Sale) |
| `daire` | 1147 (Apartment for Sale) |
| `arazi` | 1154 (Land for Sale) |
| `isyeri` | 1532 (Commercial) |
| `konut` | 1147 (Residential) |

### 6.5 Implementation

```php
// app/Integrations/Sahibinden/SahibindenService.php
class SahibindenService
{
    public function publishListing(Ilan $ilan): PublishResult
    {
        $sahibindenData = $this->transformer->toSahibinden($ilan);
        $signature = $this->signer->createSignature($sahibindenData);
        
        return $this->api->post('/ilan/ekle', $sahibindenData, [
            'X-Api-Key' => config('sahibinden.api_key'),
            'X-Signature' => $signature,
        ]);
    }
}
```

---

## 7. Hepsiemlak Integration

### 7.1 Capabilities Integrated

| Feature | Use Case |
|---------|----------|
| **Listings** | Full CRUD operations |
| **Categories** | Type mapping |
| **Districts** | Location mapping |
| **Leads** | Inquiry management |

### 7.2 Authentication

```yaml
hepsiemlak:
  api_url: "https://api.hepsiemlak.com"
  bearer_token: "${HEPSIEMLAK_BEARER_TOKEN}"
  partner_id: "${HEPSIEMLAK_PARTNER_ID}"
  
  headers:
    X-Partner-Id: partner_id
    Authorization: "Bearer {token}"
```

### 7.3 Rate Limits

| Tier | Listings | Updates | Queries |
|------|----------|---------|---------|
| Standard | 500/month | 2000/month | 5000/month |
| Premium | 2000/month | 10000/month | 20000/month |

### 7.4 Implementation

```php
// app/Integrations/Hepsiemlak/HepsiemlakService.php
class HepsiemlakService
{
    public function createListing(Ilan $ilan): ListingResult
    {
        $payload = [
            'realEstate' => [
                'title' => $ilan->baslik,
                'description' => $ilan->aciklama,
                'category' => $this->mapCategory($ilan->tip),
                'price' => $ilan->fiyat,
                'currency' => 'TRY',
                'location' => [
                    'city' => $this->mapCity($ilan->il),
                    'district' => $ilan->ilce,
                    'lat' => $ilan->lat,
                    'lng' => $ilan->lng,
                ],
                'attributes' => $this->extractAttributes($ilan),
            ],
            'photos' => $this->preparePhotos($ilan),
        ];
        
        return $this->client->post('/real-estates', $payload);
    }
}
```

---

## 8. n8n Workflow Engine

### 8.1 Capabilities Integrated

| Feature | Use Case |
|---------|----------|
| **Webhook Triggers** | External event handling |
| **Scheduled Workflows** | Daily syncs |
| **HTTP Requests** | API integrations |
| **Data Transformation** | Format conversion |

### 8.2 Authentication

```yaml
n8n:
  api_url: "http://n8n:5678"
  api_key: "${N8N_API_KEY}"
  
  webhook:
    auth_header: "X-n8n-Webhook-Key"
    webhook_key: "${N8N_WEBHOOK_KEY}"
```

### 8.3 Workflow Triggers

| Trigger | Event | Payload |
|---------|-------|---------|
| `listing.created` | New ilan | Full ilan data |
| `contact.request` | New inquiry | Contact + ilan |
| `scheduled.sync` | Daily 06:00 | Tenant list |

### 8.4 Implementation

```php
// app/Integrations/N8n/N8nTriggerService.php
class N8nTriggerService
{
    public function triggerWorkflow(string $workflowId, array $payload): bool
    {
        return $this->http->post(
            "{$this->baseUrl}/webhook/{$workflowId}",
            $payload,
            ['X-n8n-Webhook-Key' => $this->webhookKey]
        );
    }
}
```

---

## 9. OpenClaw AI Workforce

### 9.1 Capabilities Integrated

| Feature | Use Case |
|---------|----------|
| **Task Dispatch** | Send tasks to AI agents |
| **Status Polling** | Check task completion |
| **Result Retrieval** | Get AI outputs |
| **Error Handling** | Retry failed tasks |

### 9.2 Authentication

```yaml
openclaw:
  api_url: "${OPENCLAW_API_URL}"
  api_key: "${OPENCLAW_API_KEY}"
  webhook_secret: "${OPENCLAW_WEBHOOK_SECRET}"
  
  task_types:
    - "property_description"
    - "image_analysis"
    - "lead_qualification"
    - "document_generation"
```

### 9.3 Implementation

```php
// app/Integrations/OpenClaw/OpenClawService.php
class OpenClawService
{
    public function dispatchTask(string $type, array $params): TaskResult
    {
        $task = $this->client->createTask([
            'type' => $type,
            'priority' => $params['priority'] ?? 'normal',
            'input' => $params['data'],
            'callback_url' => route('api.v1.openclaw.webhook'),
        ]);
        
        Hermes::publish(Event::make('ai.task.dispatched')
            ->withPayload(['task_id' => $task->id, 'type' => $type]));
            
        return $task;
    }
}
```

---

## 10. CRM (Future)

### 10.1 Planned Integration

| CRM | Priority | Timeline |
|-----|----------|----------|
| **Salesforce** | High | Q4 2026 |
| **HubSpot** | Medium | Q1 2027 |
| **Pipedrive** | Low | Q2 2027 |

### 10.2 Data to Sync

- Contacts/Leads
- Opportunities
- Property Inquiries
- Viewing History
- Communications Log

---

## 11. Finance Systems (Future)

### 11.1 Planned Integration

| System | Priority | Timeline |
|--------|----------|----------|
| **Logo** | High | Q4 2026 |
| **Mikro** | Medium | Q1 2027 |
| **Zoho Books** | Low | Q2 2027 |

### 11.2 Data to Sync

- Invoices
- Payments
- Commissions
- Expense Reports

---

## 12. Error Handling Strategy

All external integrations follow:

1. **Retry with Backoff:** Exponential backoff on transient failures
2. **Circuit Breaker:** Trip after 5 failures, half-open after 30s
3. **Dead Letter:** Failed events go to DLQ for manual review
4. **Alerting:** PagerDuty alerts on integration failures

---

## 13. Monitoring

| Integration | SLO | Alert Threshold |
|-------------|-----|-----------------|
| Google Workspace | 99.9% | >0.5% error rate |
| Telegram | 99.5% | >1% error rate |
| WhatsApp | 99.5% | >1% error rate |
| Airbnb | 99% | >2% error rate |
| Sahibinden | 99% | >2% error rate |
| Hepsiemlak | 99% | >2% error rate |
| n8n | 99.9% | >0.5% error rate |
| OpenClaw | 99% | >5% error rate |

---

*Document approved by SAAB v6 Integration Office*

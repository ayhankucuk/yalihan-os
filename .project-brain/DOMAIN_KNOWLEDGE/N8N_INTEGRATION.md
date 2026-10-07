# DOMAIN KNOWLEDGE: n8n INTEGRATION

**LAST_VERIFIED_HEAD:** 0894715a
**Evidence Level:** REPO_VERIFIED
**URL:** https://n8n.yalihanemlak.com.tr/

---

## Business Concept: n8n

**Tanım:**
- Workflow otomasyon platformu
- YALIHAN OS → n8n webhook bildirimleri
- n8n → YALIHAN OS webhook handler'ları
- **Yeri:** External automation hub (AI Otomasyon kategorisi)

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     YALIHAN OS                                   │
│                                                                  │
│  Event Trigger                                                  │
│    ↓                                                            │
│  NotifyN8nAbout* Job                                            │
│    ↓ (HTTP POST)                                                │
│  n8n Webhook URL                                                │
│    ↓                                                            │
│  n8n.yalihanemlak.com.tr                                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓ (reverse)
┌─────────────────────────────────────────────────────────────────┐
│                     n8n WORKFLOWS                               │
│                                                                  │
│  Workflow Processing                                            │
│    ↓                                                            │
│  External Services (Email, SMS, etc.)                             │
│    ↓                                                            │
│  Callback to YALIHAN OS                                         │
│    ↓ (HTTP POST)                                                │
│  N8nWebhookController                                           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Outbound: YALIHAN OS → n8n

### NotifyN8nAbout* Jobs (7 adet)

| Job | Trigger | Webhook URL Env | Payload |
|-----|---------|-----------------|---------|
| NotifyN8nAboutNewIlan | İlan oluşturuldu | N8N_NEW_ILAN_WEBHOOK | ilan data |
| NotifyN8nAboutIlanPriceChange | Fiyat değişikliği | N8N_ILAN_PRICE_CHANGED_WEBHOOK | ilan + eski/yeni fiyat |
| NotifyN8nAboutNewGorev | Görev oluşturuldu | N8N_GOREV_CREATED_WEBHOOK | gorev data |
| NotifyN8nAboutGorevDurumChanged | Görev durumu değişti | N8N_GOREV_DURUM_CHANGED_WEBHOOK | gorev + eski/yeni durum |
| NotifyN8nAboutGorevDeadlineYaklasiyor | Deadline yaklaşıyor | N8N_GOREV_DEADLINE_YAKLASIYOR_WEBHOOK | gorev data |
| NotifyN8nAboutGorevGecikti | Görev gecikti | N8N_GOREV_GECIKTI_WEBHOOK | gorev data |

### Webhook Configuration

```php
// config/services.php
'n8n' => [
    'webhook_base_url' => env('N8N_WEBHOOK_URL', 'http://localhost:5678'),
    'webhook_secret' => env('N8N_WEBHOOK_SECRET', ''),
    'webhook_token' => env('N8N_WEBHOOK_TOKEN', ''),
    'timeout' => 30, // seconds
]
```

### Authentication

**Header:** `X-N8N-SECRET: {webhook_secret}`

```php
$response = Http::timeout(30)
    ->withHeaders([
        'X-N8N-SECRET' => config('services.n8n.webhook_secret', ''),
    ])
    ->post($webhookUrl, $payload);
```

### Error Handling

| Scenario | Behavior |
|----------|----------|
| URL not configured | Log warning, skip |
| HTTP error | Throw Exception, retry via queue |
| Timeout | Log error, throw Exception |

---

## Inbound: n8n → YALIHAN OS

### N8nWebhookController

**Route:** `/api/n8n/webhook/*`

**Endpoints:**

| Endpoint | Purpose | Auth |
|----------|---------|------|
| `/api/n8n/webhook/analyze_market` | Piyasa analizi | Token |
| `/api/n8n/webhook/create_draft_listing` | Taslak ilan oluşturma | Token |
| `/api/n8n/webhook/trigger_reverse_match` | Tersine eşleştirme | Token |
| `/api/n8n/webhook/test` | Bağlantı testi | None |

### Available Webhook Handlers

| Handler | Purpose |
|---------|---------|
| AI ilan taslağı | n8n → AI draft listing |
| AI mesaj taslağı | n8n → AI message draft |
| AI sözleşme taslağı | n8n → AI contract draft |
| Emsal arama | n8n → Market comparison |
| Taslak ilan oluşturma | n8n → Create draft ilan |
| Tersine eşleştirme | n8n → Trigger reverse matching |

---

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| N8N_WEBHOOK_URL | Base n8n URL | http://localhost:5678 |
| N8N_WEBHOOK_SECRET | Authentication secret | (empty) |
| N8N_WEBHOOK_TOKEN | API token | (empty) |
| **N8N_API_KEY** | **Hermes API key** | **(empty) - YENİ** |
| N8N_NEW_ILAN_WEBHOOK | New ilan webhook | (empty) |
| N8N_ILAN_PRICE_CHANGED_WEBHOOK | Price change webhook | (empty) |
| N8N_GOREV_CREATED_WEBHOOK | New task webhook | (empty) |
| N8N_GOREV_DURUM_CHANGED_WEBHOOK | Task status change webhook | (empty) |
| N8N_GOREV_DEADLINE_YAKLASIYOR_WEBHOOK | Task deadline webhook | (empty) |
| N8N_GOREV_GECIKTI_WEBHOOK | Task delayed webhook | (empty) |

---

## Event Flow Examples

### 1. İlan Fiyat Değişikliği

```
1. Kullanıcı ilan fiyatını değiştirir
2. IlanPriceChanged event fırlatılır
3. ProcessIlanPriceChangedJob dispatch edilir
4. NotifyN8nAboutIlanPriceChange::handle() çalışır
5. HTTP POST → n8n webhook URL
   Payload: {ilan_id, eski_fiyat, yeni_fiyat, url}
6. n8n workflow tetiklenir (bildirim, log, vs.)
```

### 2. Görev Oluşturuldu

```
1. GorevCreated event fırlatılır
2. NotifyN8nAboutNewGorev::dispatch()
3. HTTP POST → N8N_GOREV_CREATED_WEBHOOK
4. n8n workflow: Slack/Email notification
```

### 3. n8n → AI Listing Draft

```
1. n8n workflow AI'dan listing data toplar
2. POST /api/n8n/webhook/create_draft_listing
3. N8nWebhookController::createDraftListing()
4. Taslak ilan oluşturulur (durum: TASLAK)
5. Response: {ilan_id, status}
```

---

## Related Services

| Service | Integration |
|---------|-------------|
| Hermes | Events trigger n8n notifications |
| Queue | Jobs dispatched async |
| LogService | All n8n calls logged |
| NotificationDispatcher | Alternative to n8n |

---

## Security

| Layer | Protection |
|-------|------------|
| Outbound | X-N8N-SECRET header |
| Inbound | Token validation (middleware) |
| CSRF | VerifyCsrfToken middleware |
| Rate Limit | Laravel throttle |

---

## Monitoring

| Log Channel | Purpose |
|-------------|---------|
| LogService::info() | Successful webhook send |
| LogService::warning() | URL not configured |
| LogService::error() | HTTP failures |

---

## Related Domains

| Domain | Connection |
|--------|------------|
| Reservation | ReservationCreated → n8n notification |
| Gorev | GorevCreated/DurumChanged → n8n |
| Ilan | PriceChanged → n8n |
| AI | n8n → AI draft generation |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| n8n workflow details? | External system |
| Active workflows list? | External system |
| Workflow failure handling? | n8n side |
| Callback reliability? | Not documented |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Webhook URL not configured | Log warning | REPO_VERIFIED |
| X-N8N-SECRET header | Sent on all calls | REPO_VERIFIED |
| HTTP timeout | 30 seconds | REPO_VERIFIED |

---

## Menu Integration

**Admin Menu → AI Otomasyon:**
```
n8n Workflows  → admin.integrations.n8n-workflows
```

**Controller:** N8nWorkflowController
**View:** n8n workflow management UI

---

## Future Considerations

1. **Webhook retry policy** - Current: throws exception, queue retry
2. **Webhook response handling** - Current: ignored
3. **Bulk webhook support** - Current: single event per webhook
4. **Workflow health monitoring** - n8n side only

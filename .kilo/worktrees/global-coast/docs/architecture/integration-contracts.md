# Integration Contracts

> STATUS: REFERENCE ONLY — NOT SSOT
> Authority order: Human > Live Code > `.sab/authority.json` > this documentation

---

## Purpose

Documents the API contracts between Laravel core and all edge integrations (Telegram, n8n, future OpenClaw). Every integration point has a defined contract: endpoint, method, payload schema, authentication, and response format.

---

## Current State (11 Nisan 2026)

### Inbound Contracts (External → Laravel)

#### 1. Telegram Bot Webhook

| Field | Value |
|-------|-------|
| **Endpoint** | `POST /api/telegram/webhook` |
| **Controller** | `TelegramWebhookController@handleWebhook` |
| **Delegation** | `TelegramBrain::handle($data)` |
| **Auth** | None (should add `X-Telegram-Bot-API-Secret-Token` verification) |
| **Payload** | Telegram Update object (`update_id`, `message`, `callback_query`) |
| **Response** | Always HTTP 200 (prevents Telegram retries) |
| **Rate Limit** | None (should add per-chat-id throttle) |

#### 2. Telegram Advisor Webhook

| Field | Value |
|-------|-------|
| **Endpoint** | `POST /api/v1/integrations/telegram/webhook` |
| **Controller** | `TelegramAdvisorAdapterController@handleWebhook` |
| **Delegation** | `ConversationalAdvisorService::processQuery()` |
| **Auth** | ✅ `telegram.secret` middleware — `X-Telegram-Bot-Api-Secret-Token` header verified against `TELEGRAM_WEBHOOK_SECRET` |
| **Payload** | `{ message: { chat: { id }, text } }` |
| **Response** | `{ is_success: bool }` |

#### 3. n8n → Laravel Webhooks (7 endpoints)

| Endpoint | Controller Method | Required Fields | Response |
|----------|-------------------|----------------|----------|
| `POST /api/webhook/n8n/ilan-taslagi` | `ilanTaslagi()` | `danisman_id`, `data`, `ai_response`, `ai_model_used`, `ai_prompt_version` | `{ taslak_id, islem_durumu }` |
| `POST /api/webhook/n8n/mesaj-taslagi` | `mesajTaslagi()` | `communication_id`, `channel`, `content`, `ai_model_used` | `{ message_id, mesaj_durumu }` |
| `POST /api/webhook/n8n/sozlesme-taslagi` | `sozlesmeTaslagi()` | `contract_type`, `property_id`, `kisi_id`, `content`, `ai_model_used` | `{ draft_id, islem_durumu }` |
| `POST /api/webhook/n8n/test` | `test()` | — | `{ basarili: true }` |
| `POST /api/webhook/n8n/analyze-market` | `analyzeMarket()` | Query params | Market analysis result |
| `POST /api/webhook/n8n/create-draft-listing` | `createDraftListing()` | `text` (min 10 chars) | Draft listing result |
| `POST /api/webhook/n8n/trigger-reverse-match` | `triggerReverseMatch()` | `ilan_id` (exists in ilanlar) | Async job queued |

**Auth:** ✅ `n8n.secret` middleware — `X-N8N-SECRET` header verified against `N8N_WEBHOOK_SECRET` env var. Rate limited: `throttle:60,1`.

### Outbound Contracts (Laravel → External)

#### 4. Laravel → n8n Workflows

| Workflow Type | Webhook Path | Trigger Event |
|---------------|-------------|---------------|
| `ilan_created` | `webhook/ilan-olusturuldu` | Listing created |
| `ilan_updated` | `webhook/ilan-guncellendi` | Listing updated |
| `ilan_price_changed` | `webhook/ilan-fiyat-degisti` | Price changed |
| `talep_created` | `webhook/talep-olusturuldu` | Demand created |
| `talep_matched` | `webhook/talep-eslesti` | Demand matched |
| `gorev_created` | `webhook/gorev-olusturuldu` | Task created |
| `gorev_deadline` | `webhook/gorev-gecikti` | Task overdue |
| `kisi_churn_risk` | `webhook/musteri-kayip-riski` | Churn risk detected |
| `ai_opportunity` | `webhook/ai-firsat-tespiti` | AI opportunity found |
| `market_intelligence` | `webhook/piyasa-analizi` | Market analysis complete |

**Outbound Headers:**
```
Content-Type: application/json
X-Webhook-Token: {N8N_WEBHOOK_SECRET}
X-Source: yalihan-cortex
```

**Outbound Payload Structure:**
```json
{
  "workflow_type": "ilan_created",
  "triggered_at": "ISO8601",
  "options": {},
  "...customData"
}
```

#### 5. Laravel → n8n Notification Webhooks

| Webhook Type | Config Key | Trigger |
|-------------|-----------|---------|
| `high_match` | `n8n.webhooks.high_match` | Score ≥ 90% match |
| `new_listing` | `n8n.webhooks.new_listing` | Listing published |
| `demand_fulfilled` | `n8n.webhooks.demand_fulfilled` | Demand satisfied |
| `critical_update` | `n8n.webhooks.critical_update` | Status change |
| `rapor_bildirimi` | `n8n.webhooks.rapor_bildirimi` | Sealed report ready |

**HTTP Config:** Timeout 10s, 3 retries, 100ms delay.

#### 6. Laravel → Telegram Bot API

| Operation | Method | Service |
|-----------|--------|---------|
| Send critical alert | `POST /bot{TOKEN}/sendMessage` | `TelegramService` |
| Set webhook | `POST /bot{TOKEN}/setWebhook` | `TelegramBotService` |
| Get bot info | `GET /bot{TOKEN}/getMe` | `TelegramBotController` |
| Get webhook info | `GET /bot{TOKEN}/getWebhookInfo` | `TelegramBotController` |
| Download voice file | `GET /bot{TOKEN}/file/{file_id}` | `TelegramBotService` |

---

## Authority Boundary

- All inbound webhooks MUST delegate to Laravel service layer — no business logic in controllers
- All outbound requests MUST go through designated service classes (`N8nIntegrationService`, `TelegramService`)
- Contract schemas should be versioned and locked in `contracts/` directory (not yet implemented)
- Breaking changes to inbound/outbound payloads require API version bump

---

## Allowed

- Adding new webhook endpoints with proper validation and service delegation
- Adding new outbound workflow types to `N8nIntegrationService::WORKFLOWS`
- Updating notification thresholds via `config/n8n.php`
- Testing integrations via admin UI (`/admin/integrations`)

---

## Forbidden

- Accepting unvalidated payloads from external sources
- Returning internal exception details in webhook error responses
- Calling external APIs from controllers (must go through service layer)
- Adding new inbound endpoints without input validation
- Removing existing webhook endpoints without deprecation notice

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| No webhook signature verification on inbound endpoints | HIGH | Add HMAC/token verification middleware |
| n8n webhook URLs are `null` in config (not yet configured) | MEDIUM | Configure when n8n workflows are deployed |
| Duplicate Telegram webhook routes (`/api/telegram/webhook` + `/api/v1/integrations/telegram/webhook`) | LOW | Consolidate to single canonical endpoint |
| Legacy `N8nService` coexists with `N8nIntegrationService` | LOW | Deprecate `N8nService`, migrate consumers |
| No contract schema files in `contracts/` directory | MEDIUM | Create JSON Schema files for each endpoint |

---

### 7. OpenClaw Agent Endpoints (Future)

When OpenClaw is integrated, these endpoints will be created:

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/api/agent/context/*` | GET | Read context (listings, CRM, market) | Scoped agent token (`agent.read.context`) |
| `/api/agent/suggestions/submit` | POST | Submit proposals/suggestions | Scoped agent token (`agent.request.suggestion`) |
| `/api/agent/workflows/trigger` | POST | Trigger approved n8n flows | Scoped agent token (`agent.trigger.workflow`) |

**Contract:** See [openclaw-guard-blueprint.md](openclaw-guard-blueprint.md) §5 (API Allowlist) and §6 (Proposal-Only Mutation).

**Proposal Payload Schema:**
```json
{
  "source": "openclaw",
  "mode": "proposal_only",
  "correlation_id": "uuid-v4",
  "domain": "property_engine | crm | notification | market",
  "action": "suggest_feature_change | suggest_price_update | draft_content",
  "payload": { "target_entity": "ilan", "target_id": 12345, "proposed_changes": {} },
  "reason": "...",
  "confidence": 0.85,
  "token_cost": 450,
  "model_used": "deepseek-chat"
}
```

---

## Open Questions

1. Should `contracts/` directory contain JSON Schema definitions for all webhook payloads?
2. ~~Should inbound n8n webhooks require authentication?~~ ✅ RESOLVED — `n8n.secret` middleware enforces `X-N8N-SECRET` header.
3. Is the `N8nService` (legacy) still used by any consumer, or can it be removed?
4. Should Telegram Bot webhook and Telegram Advisor webhook be consolidated into one endpoint?
5. What is the deprecation timeline for legacy webhook patterns?

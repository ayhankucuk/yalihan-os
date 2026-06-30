# Automation Layer

> STATUS: REFERENCE ONLY — NOT SSOT
> Authority order: Human > Live Code > `.sab/authority.json` > this documentation

---

## Purpose

Documents the automation and integration layer that sits **outside** the Laravel core. Telegram, n8n, and any future automation runtime (e.g. OpenClaw) are classified as **edge automation** — they consume Laravel services but never own data, governance, or business logic.

---

## Current State (11 Nisan 2026)

### Architecture

```
┌──────────────────────────────────────────────────┐
│              LARAVEL CORE (Authority)             │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  │
│  │ Service    │  │ Governance │  │ Database   │  │
│  │ Layer      │  │ (SAB/C7)   │  │ (MySQL)    │  │
│  └─────┬──────┘  └────────────┘  └────────────┘  │
│        │                                          │
│  ┌─────┴──────────────────────┐                   │
│  │  API / Webhook Endpoints   │                   │
│  │  (Inbound + Outbound)      │                   │
│  └─────┬──────────────────────┘                   │
└────────┼─────────────────────────────────────────┘
         │
    ═════╪═════ EDGE BOUNDARY ═════════════════
         │
   ┌─────┴──────────────────────────────────┐
   │         EDGE AUTOMATION LAYER          │
   │                                        │
   │  ┌──────────┐  ┌──────────────────┐    │
   │  │ Telegram │  │ n8n Workflows    │    │
   │  │ Bot API  │  │ (webhook relay)  │    │
   │  └──────────┘  └──────────────────┘    │
   │                                        │
   │  ┌──────────────────────────────────┐  │
   │  │ Future: OpenClaw (helper only)  │  │
   │  └──────────────────────────────────┘  │
   └────────────────────────────────────────┘
```

### Components

| Component | Role | Direction | Status |
|-----------|------|-----------|--------|
| Telegram Bot (`TelegramBotService`) | Team commands, alerts, voice, CRM actions | Bidirectional | ✅ Backend complete, UI placeholder |
| Telegram Alerts (`TelegramService`) | Critical opportunity push alerts | Outbound only | ✅ Working |
| Telegram Advisor (`TelegramAdvisorAdapterController`) | AI advisor chat relay | Inbound only | ✅ Working |
| n8n Integration (`N8nIntegrationService`) | Workflow orchestration (11 event types) | Outbound | ✅ Config ready, `N8N_ENABLED=false` |
| n8n Webhooks (`N8nWebhookController`) | Inbound draft/match processing (7 endpoints) | Inbound | ✅ Working |
| n8n Notifications (`N8nWebhookService`) | High-match/listing/demand alerts | Outbound | ✅ Working |

### Laravel Services (Authority)

These services **own** the business logic. Edge automation calls them but never replaces them:

| Service | Authority | Consumer |
|---------|-----------|----------|
| `IlanCrudService::store()` | Sole write authority for listings | n8n `create-draft-listing` calls this |
| `ConversationalAdvisorService` | AI advisor responses | Telegram webhook delegates here |
| `TelegramBotService` | Bot command processing | Telegram webhook delegates here |
| `N8nIntegrationService` | n8n workflow triggering | Laravel events trigger this |
| `AIService` | Provider-agnostic AI calls | All AI features flow through this |

---

## Authority Boundary

- **Laravel Core** = single source of truth for all data, business logic, and governance
- **Edge automation** = relay, notification, and user-interface layer only
- No edge component may bypass the service layer to access database directly
- No edge component may override governance (SAB, Context7, EnvDriftGuard)

---

## Allowed

- Telegram/n8n receiving user input and relaying to Laravel API endpoints
- Telegram/n8n sending notifications triggered by Laravel events
- n8n workflow orchestration for multi-channel delivery (Telegram + WhatsApp + Email)
- Voice message transcription via Telegram → `AudioTranscriptionService`
- Bot commands executing read operations (görevler, durum, performans)
- n8n triggering draft creation via `N8nWebhookController` (validated, service-delegated)

---

## Forbidden

- Direct database access from any edge component
- Edge components modifying `config/`, `.env`, or governance files
- Telegram/n8n bypassing authentication or authorization middleware
- n8n workflows writing to core tables without going through Laravel API
- Edge components making governance decisions (SAB scan, quality gate)
- Any edge runtime (including OpenClaw) acting as a write authority

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Webhook endpoints lack signature verification | HIGH | Telegram: add `X-Telegram-Bot-API-Secret-Token` check. n8n: add `X-Webhook-Token` validation |
| n8n `docker-compose.n8n.yml` has localhost config + weak password | MEDIUM | Update to production URLs, rotate `admin123` password |
| Telegram Bot UI is placeholder | LOW | Backend fully functional, UI needs build-out |
| `N8N_ENABLED=false` in production | LOW | Enable when n8n workflows are deployed |
| Rate limiting gaps on webhook endpoints | MEDIUM | Add per-chat-id throttle on Telegram inbound |

---

## Open Questions

1. Should `@yalihanx_bot` (new) replace `YalihanCortex_Bot` (existing) or serve a different channel?
2. When will `N8N_ENABLED` be flipped to `true` in production?
3. Should n8n run in the same Docker network as Laravel, or remain separate?
4. Is Cloudflare Tunnel the production HTTPS strategy for n8n, or will Nginx reverse proxy be used?

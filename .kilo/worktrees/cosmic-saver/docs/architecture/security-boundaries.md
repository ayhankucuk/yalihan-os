# Security Boundaries

> STATUS: REFERENCE ONLY — NOT SSOT
> Authority order: Human > Live Code > `.sab/authority.json` > this documentation

---

## Purpose

Documents the security perimeter between Laravel core and all edge/external systems (Telegram, n8n, OpenClaw, public API). Defines what each layer may and may not access, and identifies current gaps.

---

## Current State (11 Nisan 2026)

### Security Perimeter Map

```
                    INTERNET
                       │
        ┌──────────────┼──────────────┐
        │              │              │
   Telegram API    n8n Cloud     Public Users
        │              │              │
   ═════╪══════════════╪══════════════╪═══ FIREWALL ═══
        │              │              │
   ┌────┴────┐    ┌────┴────┐   ┌────┴────────┐
   │Webhook  │    │Webhook  │   │Public API   │
   │Endpoints│    │Endpoints│   │Endpoints    │
   │(guarded)│    │(guarded)│   │(rate limit) │
   └────┬────┘    └────┬────┘   └────┬────────┘
        │              │              │
   ═════╪══════════════╪══════════════╪═══ MIDDLEWARE ═══
        │              │              │
   ┌────┴──────────────┴──────────────┴────┐
   │           LARAVEL SERVICE LAYER       │
   │  ┌──────────┐  ┌───────────────────┐  │
   │  │ Auth     │  │ SAB/Context7      │  │
   │  │ Sanctum  │  │ Governance        │  │
   │  └──────────┘  └───────────────────┘  │
   │  ┌──────────────────────────────────┐ │
   │  │ Database (MySQL) — PROTECTED     │ │
   │  └──────────────────────────────────┘ │
   └───────────────────────────────────────┘
```

### Authentication Matrix

| Endpoint Group | Auth Method | Status |
|---------------|-------------|--------|
| Admin panel (`/admin/*`) | Session + CSRF | ✅ Enforced |
| Advisor API (`/api/advisor/*`) | Sanctum token | ✅ Enforced |
| Public API (`/api/v1/public-ai/*`) | None (public) | ✅ By design |
| Telegram webhook (`/api/telegram/webhook`) | None | ⚠️ **Orphaned route** (no active registration) |
| Telegram advisor (`/api/v1/integrations/telegram/webhook`) | `telegram.secret` middleware | ✅ `X-Telegram-Bot-Api-Secret-Token` verified |
| n8n inbound (`/api/v1/webhook/n8n/*`) | `n8n.secret` middleware + `throttle:60,1` | ✅ `X-N8N-SECRET` header verified |
| Health endpoints (`/api/v1/health`) | None (public) | ✅ By design |

### Data Exposure Rules

From `README.md § 20.6 Security Boundary`:

**Public responses MUST NEVER contain:**
- `owner_id`
- `danisman_id`
- `metadata`
- `internal_notes`
- `advisor_phone`
- Internal admin fields

**Enforcement:** `guard:security` artisan command + CI Gate.

---

## Authority Boundary

### Laravel Core (Full Authority)

| Resource | Owner | Access Control |
|----------|-------|---------------|
| MySQL database | Laravel service layer | `IlanCrudService::store()` sole write authority |
| `.env` / secrets | Human + deployment pipeline | No runtime access by edge systems |
| `config/` | Human + deployment pipeline | Immutable at runtime |
| Governance (SAB, C7) | CI pipeline + artisan commands | Non-bypassable (7 core checks) |
| Session/auth state | Laravel Sanctum + session driver | Not shared with edge systems |
| File storage | Laravel filesystem abstraction | Edge systems have no direct FS access |

### Edge Systems (No Authority)

| System | May Do | May NOT Do |
|--------|--------|------------|
| **Telegram** | Send/receive messages via Bot API, relay to Laravel webhook | Access DB, read files, modify config |
| **n8n** | Orchestrate multi-channel delivery, trigger Laravel webhooks | Access DB, execute artisan, modify config |
| **OpenClaw** (future) | Process tasks via API call/callback pattern | Access DB, execute shell, modify config, override governance |

**OpenClaw Guard Blueprint:** See [openclaw-guard-blueprint.md](openclaw-guard-blueprint.md) for 15-section boundary control specification including: scoped token model, API allowlist, proposal-only mutation, kill switch, human-in-the-loop requirements, and verification tests.

---

## Allowed

- Telegram webhook receiving Telegram Update objects and delegating to `TelegramBrain`
- n8n webhook receiving structured payloads with input validation
- Public API serving anonymized, field-mapped search results
- Admin panel with session + CSRF protection
- Advisor API with Sanctum token authentication
- `guard:security` scanning public endpoints for field leakage
- Rate limiting on sensitive endpoints (AI generation, decision recording)
- API key masking in admin UI (last 4 chars visible)

---

## Forbidden

- Webhook endpoints accepting payloads without signature/token verification
- Returning full exception stack traces in API error responses
- Storing unmasked API keys or tokens in logs
- Edge systems accessing database connections directly
- Edge systems reading or writing to filesystem (except designated upload paths)
- Disabling CSRF protection on admin routes
- Exposing internal field names (`owner_id`, `danisman_id`) in public responses
- OpenClaw executing SQL, shell commands, or config mutations (permanent ban)

---

## Current Gaps

| # | Gap | Severity | Location | Status |
|---|-----|----------|----------|--------|
| 1 | ~~Telegram webhook: no signature verification~~ | ~~HIGH~~ | `TelegramWebhookController` | ✅ **RESOLVED** — `VerifyTelegramWebhookSecret` middleware, `telegram.secret` alias |
| 2 | ~~Telegram advisor webhook: no signature verification~~ | ~~HIGH~~ | `TelegramAdvisorAdapterController` | ✅ **RESOLVED** — `telegram.secret` middleware applied to route |
| 3 | ~~n8n inbound webhooks: no token verification~~ | ~~HIGH~~ | `N8nWebhookController` (7 endpoints) | ✅ **RESOLVED** — `n8n.secret` middleware with `X-N8N-SECRET` header check |
| 4 | n8n inbound webhooks: rate limiting | MEDIUM | `N8nWebhookController` | ✅ **RESOLVED** — `throttle:60,1` middleware applied |
| 5 | Telegram advisor: no input length validation | MEDIUM | `TelegramAdvisorAdapterController` | ⚠️ Open |
| 6 | ~~docker-compose.n8n.yml: weak password~~ | ~~MEDIUM~~ | `docker-compose.n8n.yml` | ✅ **RESOLVED** — env-based `${N8N_BASIC_AUTH_PASSWORD:?...}` |
| 7 | ~~docker-compose.n8n.yml: HTTP config~~ | ~~MEDIUM~~ | `docker-compose.n8n.yml` | ✅ **RESOLVED** — HTTPS, secure cookie, production domain |
| 8 | Telegram advisor: exception details in response | LOW | `TelegramAdvisorAdapterController` | ⚠️ Open |
| 9 | TelegramService: admin chat ID fallback to DB | LOW | `TelegramService` constructor | ⚠️ Open |
| 10 | Voice transcript PII in logs | LOW | `TelegramBotService` | ⚠️ Open |

---

## Risks

| Risk | Severity | Impact | Mitigation |
|------|----------|--------|------------|
| Unauthenticated webhook abuse | HIGH | Fake Telegram/n8n payloads trigger business logic | Implement signature/token verification (gaps #1-3) |
| n8n weak auth exposes automation control | MEDIUM | Unauthorized access to n8n dashboard | Rotate password, enable credential-based auth |
| OpenClaw scope creep (future) | HIGH | External runtime gains unintended access | Enforce forbidden list at network + code level, ADR required |
| API key leakage via logs | MEDIUM | Compromised provider accounts | Mask all keys, audit log output |
| PII in voice transcripts | MEDIUM | KVKK violation | Sanitize before logging, retention policy |
| Exception details in API responses | LOW | Information disclosure | Generic error responses, internal-only logging |

---

## Open Questions

1. ~~Should a unified webhook authentication middleware be created for all inbound endpoints?~~ RESOLVED — Separate middleware per integration (telegram.secret, n8n.secret) for clarity.
2. ~~What is the timeline for fixing the 3 HIGH severity gaps?~~ RESOLVED — All 3 HIGH gaps fixed (11 Nisan 2026).
3. Should n8n be network-isolated (Docker network only, no public port) or accessible via reverse proxy?
4. Is `TELEGRAM_WEBHOOK_SECRET` already configured in production `.env`?
5. Should voice transcripts be stored at all, or processed in-memory only?
6. Does KVKK compliance require explicit consent before processing Telegram messages?

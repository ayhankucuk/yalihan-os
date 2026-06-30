# AI Provider Strategy

> STATUS: REFERENCE ONLY — NOT SSOT
> Authority order: Human > Live Code > `.sab/authority.json` > this documentation

---

## Purpose

Documents the AI provider selection, fallback chain, cost controls, and the strategic direction toward DeepSeek-first inference. All AI calls flow through `AIService` (provider-agnostic). Provider selection is a config/env concern — not a code concern.

---

## Current State (11 Nisan 2026)

### Active Provider Configuration

| Key | Value | Source |
|-----|-------|--------|
| `AI_PROVIDER` | `ollama` | `.env` (default) |
| `AI_DEFAULT_MODEL` | `llama3.1:latest` | `config/ai.php` |
| `AI_FALLBACK_MODEL` | `gpt-3.5-turbo` | `config/ai.php` |
| `GOOGLE_MODEL` | `gemini-2.5-flash` | `config/ai.php` |

### Provider Registry

| Provider | Config Key | Model | Status | Use Case |
|----------|-----------|-------|--------|----------|
| **Ollama** (local) | `ollama` | `llama3.1:latest` | ✅ Default | Local dev, privacy-sensitive queries |
| **DeepSeek** | `deepseek` | TBD | ✅ API key configured | **Target primary provider** |
| **Google Gemini** | `google` | `gemini-2.5-flash` | ✅ API key configured | Fast inference, translation |
| **OpenAI** | `openai` | `gpt-4o` / `gpt-3.5-turbo` | ✅ API key configured | Fallback, complex reasoning |
| **Anthropic Claude** | `anthropic` | claude-3.5-sonnet | ✅ API key configured | Analysis, long context |
| **MiniMax** | `minimax` | `minimax-m2` | ⚠️ Key configured | Experimental |

### Fallback Chain (Current)

```
Request → AIService
    ↓
Primary: Ollama (llama3.1, local)
    ↓ (fail/timeout)
Fallback: gpt-3.5-turbo (OpenAI)
    ↓ (fail/timeout)
Error: Logged to ai_query_logs + telemetry
```

### DeepSeek-First Strategy (Target)

```
Request → AIService
    ↓
Primary: DeepSeek (cost-efficient, high quality)
    ↓ (fail/timeout)
Fallback 1: Gemini 2.5 Flash (fast, multilingual)
    ↓ (fail/timeout)
Fallback 2: Ollama local (zero-cost, privacy)
    ↓ (fail/timeout)
Fallback 3: OpenAI GPT-3.5 (reliable baseline)
    ↓ (fail/timeout)
Error: Logged + Slack alert
```

**Why DeepSeek first:**
- Cost-per-token significantly lower than OpenAI/Anthropic
- Competitive quality for structured real estate domain tasks
- Sufficient for: listing description, title generation, feature suggestion, translation
- Keeps expensive providers (GPT-4o, Claude) for complex reasoning only

### Feature-Level Token Budgets

From `config/ai-budgets.php`:

| Feature | Daily Token Budget | Soft Cap |
|---------|-------------------|----------|
| Default | 200,000 | 80% |
| UPS Template Generate | 50,000 | 80% |
| Wizard Storytelling | Configured | 80% |

Global: `AI_HARD_CAP_ENABLED=false` (Phase 21.1 Task 2)

### Cost Controls

From `config/ai-cost-guard.php` + `config/ai-budgets.php`:

| Environment | Daily Budget | Token Limit/Request | Fallback |
|-------------|-------------|-------------------|----------|
| Production | $50/day | 4K tokens | Switch to cheaper model |
| Staging | $10/day | 2K tokens | Mock responses |
| Development | $2/day | 1K tokens | Cached responses |

---

## Authority Boundary

- **`AIService`** = sole entry point for all AI inference calls
- **`config/ai.php`** = provider configuration authority
- **`config/ai-budgets.php`** = token budget authority
- **Admin UI** (`/admin/ai-settings`) = runtime provider switching (persisted to `settings` table)
- Provider switching does **not** require code changes — env/config/admin-UI only

---

## Allowed

- Switching `AI_PROVIDER` via `.env` or admin settings UI
- Adding new provider adapters inside `AIService` with fallback chain
- Feature-level token budgets with soft/hard caps
- Cost tracking per request (`token_count`, `maliyet_usd`, `model`, `timestamp`)
- DeepSeek as primary with automatic fallback to Gemini/Ollama/OpenAI
- Caching AI responses per `config/ai.php → cache_duration` (60 min default)

---

## Forbidden

- Calling any AI provider directly without going through `AIService`
- Hardcoding provider URLs or API keys in application code
- Bypassing token budgets or cost guards
- Using AI responses to directly mutate database without service layer validation
- Storing unmasked API keys in logs or telemetry
- Disabling fallback chain in production

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| DeepSeek API availability/latency in Turkey | MEDIUM | Fallback chain to Gemini → Ollama → OpenAI |
| Token budget hard cap not yet enforced | LOW | `AI_HARD_CAP_ENABLED=false`, Phase 21.1 |
| Ollama requires SSH tunnel to remote server | MEDIUM | Production needs Nginx HTTPS proxy or Cloudflare Tunnel |
| `AI_REQUIRE_TLS=false` in development | LOW | Must be `true` in production for KVKK compliance |
| Cost spike if fallback triggers expensive provider | MEDIUM | Daily budget enforcement + Slack alerts at 80% |

---

## Open Questions

1. What DeepSeek model should be the default? (`deepseek-chat`, `deepseek-coder`, `deepseek-reasoner`)
2. Should DeepSeek be the primary for all features, or only structured tasks (descriptions, titles)?
3. When will `AI_HARD_CAP_ENABLED` be flipped to `true`?
4. Should Gemini 2.5 Flash replace Ollama as the local-fallback for speed-critical paths (autocomplete, search)?
5. Is MiniMax still relevant, or should it be removed from the provider registry?

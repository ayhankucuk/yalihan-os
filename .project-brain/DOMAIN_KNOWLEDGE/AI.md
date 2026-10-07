# DOMAIN KNOWLEDGE: AI / CORTEX

**LAST_VERIFIED_HEAD:** 32236d54
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: AI Services

**Tanım:**
- YALIHAN OS'de AI provider yönetimi
- Çoklu LLM desteği (DeepSeek, OpenAI, Claude, Gemini, Ollama)
- AI maliyet kontrolü ve kullanım takibi

**SOURCE:** AIProviderManager, AIOrchestrator, AICostService

---

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| AI Provider Selection | AIProviderManager | app/Services/AI/AIProviderManager.php |
| Cost Tracking | AICostService | app/Services/AI/AICostService.php |
| Prompt Building | AIPromptBuilder | app/Services/AI/AIPromptBuilder.php |

---

## AI Providers

| Provider | Model Default | Config Key | Evidence |
|----------|---------------|------------|----------|
| deepseek | deepseek-chat | deepseek_model | AIProviderManager |
| openai | gpt-4o | openai_model | AIProviderManager |
| claude | claude-3-5-sonnet-latest | claude_model | AIProviderManager |
| google | gemini-1.5-flash | google_model | AIProviderManager |
| ollama | llama3 | ollama_model | AIProviderManager |
| AIWebModel | AIWebModel | AIWebModel | AIProviderManager |

**PROVIDER_SWITCH:** AIProviderManager::switchProvider($provider)

---

## Provider Selection Flow

```
User/Service Request
        ↓
AIProviderManager::callProvider()
        ↓
getActiveProvider() → config/SettingService
        ↓
Provider-specific call (callOpenAI, callGoogle, etc.)
        ↓
Response
```

**EVIDENCE:** REPO_VERIFIED (AIProviderManager)

---

## Architecture Flexibility ⚠️

**NOT:** Provider ve model yapısı değişkenlik gösterebilir.

| Aspect | Current | Flexibility Needed |
|--------|---------|-------------------|
| Provider sayısı | 6 | Artırılabilir |
| Model adları | Hardcoded | Config/DB'ye taşınabilir |
| API endpoint | Her provider farklı | Abstract edilebilir |
| Authentication | API key tabanlı | TokenVault ile soyutlanabilir |

**Yapı Önerisi (Future):**
```
AIProviderInterface
    ├── OpenAIProvider
    ├── ClaudeProvider
    ├── GeminiProvider
    ├── DeepSeekProvider
    ├── OllamaProvider
    └── CustomProvider (plugin-ready)
```

**Model Listesi Önerisi:**
- Database tablosu: `ai_models`
- Sütunlar: provider, model_name, cost_per_token, is_active

**FINDING:** AI Provider abstraction P2 öncelikli - BEKLEMEDE

---

## AI Services

| Service | Purpose | Evidence |
|---------|---------|----------|
| AIOrchestrator | AI request orchestration | app/Services/AI/AIOrchestrator.php |
| AIPromptBuilder | Prompt construction | app/Services/AI/AIPromptBuilder.php |
| AICostService | Cost tracking | app/Services/AI/AICostService.php |
| AiBudgetGuard | Budget limits | app/Services/AI/AiBudgetGuard.php |
| AiCostGuardService | Cost protection | app/Services/AI/AiCostGuardService.php |
| AiPricingService | Pricing AI suggestions | app/Services/AI/AiPricingService.php |

---

## AI Cost Management

| Aspect | Behavior | Evidence |
|--------|----------|----------|
| Cost Tracking | AiLog kaydı | REPO_VERIFIED |
| Budget Guard | Limit kontrolü | AiBudgetGuard |
| Cost Calculator | Kullanım başına maliyet | AiCostCalculatorService |

**EVIDENCE:** REPO_VERIFIED

---

## Tenant Isolation

| Check | Required | Evidence |
|-------|----------|----------|
| AI Calls tenant-scoped? | TEST_VERIFIED | AIModelsTenantReadIsolationTest |

**CROSS_TENANT_ACCESS:** BLOCKED (VERIFIED)

---

## AI-Enabled Features

| Feature | AI Service | Evidence |
|---------|-----------|----------|
| Arsa Analiz | AIArsaAnalizService | REPO_VERIFIED |
| Kontrat Analiz | AIContractService | REPO_VERIFIED |
| Fiyat Öneri | AiPricingService | REPO_VERIFIED |
| Alan/Özellik Öneri | AIFieldSuggestionService | REPO_VERIFIED |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Tenant read isolation | AIModelsTenantReadIsolationTest | PASS |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Actual provider usage distribution? | Not tracked |
| Cost per feature breakdown? | Partial |
| AI response caching strategy? | Not verified |
| Fallback behavior on provider failure? | Not documented |

---

## Hermes/AI Connection

```
Hermes Event → AIOrchestrator → AIProviderManager → LLM
                                    ↓
                              AiLog (cost tracking)
```

**EVIDENCE:** ARCHITECTURE_MAP

# Cortex — AI Orchestrator

> Yalıhan Emlak — YalihanCortex AI Pipeline

## Architecture

```
Kullanıcı → YalihanCortex → AI Providers
                           ├── DeepSeek (Primary)
                           ├── Ollama (Local)
                           └── OpenAI (Fallback)
```

## AI Providers

| Provider | Kullanım | Maliyet |
|----------|----------|---------|
| DeepSeek v4 | Primary | ~0.002 TL/sorgu |
| Ollama | Local dev | Bedava |
| OpenAI | Fallback | Pahalı |

## YalihanCortex Konumu

```
app/Services/AI/YalihanCortex.php
```

## Tool'lar

```php
// İçerik üretimi
$cortex->generateDescription($ilan);
$cortex->generateTitle($ilan);

// NLP intent parsing
$intent = $cortex->parseIntent($query);

// TKGM veri çekme
$tkgmData = $cortex->fetchTkgmData($ilan);

// Telegram işleme
$cortex->processTelegramUpdate($update);
```

## AI Budget Guard

Her AI operasyonu öncesi kontrol:

```php
if (!AiBudgetGuard::canExecute('content_generation')) {
    throw new AiBudgetExceededException();
}
```

## Telemetry

- AI istekleri: `ai_query_logs`
- Prompt geçmişi: `ai_prompt_logs`
- Translation: `ai_translation_logs`

## Kaynak

- Full Architecture: `docs/architecture/YALIHAN_CORTEX_ARCHITECTURE.md`

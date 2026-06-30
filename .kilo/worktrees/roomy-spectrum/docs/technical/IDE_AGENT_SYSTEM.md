# Yalıhan AI OS — IDE & Agent Sistemi

> Oluşturulma: 2026-05-17 | Oturum 12
> Proje: Yalıhan AI OS | Konum: /Users/macbookpro/dev/yalihan2026

---

## 📋 İçindekiler

1. [Genel Bakış](#1-genel-bakış)
2. [IDE Entegrasyonu](#2-ide-entegrasyonu)
3. [Agent Sistemi](#3-agent-sistemi)
4. [MCP Sunucuları](#4-mcp-sunucuları)
5. [Yalıhan Bekçi](#5-yalıhan-bekçi)
6. [Workflow Pipeline](#6-workflow-pipeline)
7. [Kullanım Senaryoları](#7-kullanım-senaryoları)

---

## 1. Genel Bakış

### 1.1 Mimari Felsefe

Yalıhan AI OS, **multi-agent orchestration** yaklaşımı kullanır:

```
┌─────────────────────────────────────────────────────────────┐
│                    MASTER ORCHESTRATOR                       │
│              (Pipeline Controller & Governor)                │
└──────────────┬──────────────────────────────────────────────┘
               │
       ┌───────┴───────┐
       │   PIPELINE    │
       │   STAGES      │
       └───────┬───────┘
               │
    ┌──────────┼──────────┬──────────┬──────────┐
    │          │          │          │          │
┌───▼───┐  ┌──▼───┐  ┌───▼───┐  ┌───▼───┐  ┌──▼───┐
│ AUDIT │→ │ FIX  │→ │ EXEC  │→ │VERIFY │→ │GOVERN│
│       │  │ PLAN │  │       │  │       │  │      │
└───┬───┘  └──────┘  └───────┘  └───────┘  └──────┘
    │
┌───▼────────────────────────────────────────────────┐
│           SPECIALIZED AGENTS                       │
├────────────────────────────────────────────────────┤
│ • Master Admin Copilot (Audit)                     │
│ • Fix Generator (Fix Planning)                     │
│ • Debug Executor (IDE Patches)                     │
│ • Yalıhan Bekçi (AST Analysis)                     │
└────────────────────────────────────────────────────┘
```

### 1.2 Temel Prensipler

1. **Evidence-Based Decisions:** Hiçbir karar varsayıma dayanmaz
2. **Pipeline Discipline:** AUDIT → FIX → EXEC → VERIFY → GOVERN sırası değişmez
3. **Production Safety:** Her değişiklik production-safe olmalı
4. **Minimal Blast Radius:** En küçük güvenli değişiklik öncelikli
5. **Verification Gate:** Doğrulanmayan hiçbir şey production'a geçmez

---

## 2. IDE Entegrasyonu

### 2.1 Google Antigravity

**Konum:** `/Users/macbookpro/dev/yalihan2026`

**Özellikler:**
- Derin kod analizi
- Otomatik refactoring
- Context-aware suggestions
- Multi-file operations

**Çalışma Modu:**
```
1. Claude analiz yapar
2. ide-output skill ile Antigravity'ye gönderir
3. Antigravity değişiklikleri uygular
4. Çıktı Claude'a geri döner
5. Claude sonraki adımı planlar
```

### 2.2 VSCode Tasks

**Konum:** [`.vscode/tasks.json`](../../.vscode/tasks.json)

**Tanımlı Görevler (11 adet):**

| # | Task | Komut | Kullanım |
|---|------|-------|----------|
| 1 | 🛡️ Bekçi: Full Audit | `bekci:audit --all` | Tam sistem taraması |
| 2 | 🛡️ Bekçi: Quick Check | `bekci:audit --silent-catch` | Hızlı kontrol (default) |
| 3 | 🛡️ Bekçi: Secret Scan | `bekci:audit --secret-scan` | Sır taraması |
| 4 | 🛡️ Bekçi: Naming Check | `bekci:audit --naming` | İsimlendirme kontrolü |
| 5 | 🛡️ Bekçi: Tech Debt | `bekci:audit --technical-debt` | TODO/FIXME raporu |
| 6 | 🛡️ Bekçi: Domain Check | `bekci:audit --domain-boundaries` | Domain sınır kontrolü |
| 7 | 📚 Bekçi: Learn Pattern | `bekci:pattern-learn` | Yeni pattern öğret |
| 8 | 🔍 SAB: Integrity Scan | `sab:integrity-scan` | SAB kuralları kontrolü |
| 9 | ✅ Quality Gate | `quality:gate` | Tam kalite kontrolü |
| 10 | 🧪 Run Tests | `test` | Test suite çalıştır |
| 11 | 🚀 Pre-Commit Check | `pre-commit` | Commit öncesi kontrol |

**Kullanım:**
```
Cmd+Shift+P → Tasks: Run Task → 🛡️ Bekçi: Quick Check
```

### 2.3 Git Hooks

**Konum:** [`hooks/`](../../hooks/)

**Pre-Commit Hook:**
```bash
# hooks/pre-commit
# 4 kontrol:
1. Bekçi AST Audit (kritik kurallar)
2. SAB Integrity Scan
3. Drift Detection
4. Route Validation
```

**Bypass:**
```bash
git commit --no-verify
```

---

## 3. Agent Sistemi

### 3.1 Master Orchestrator

**Dosya:** [`.github/agents/master-orchestrator.agent.md`](../../.github/agents/master-orchestrator.agent.md)

**Rol:** Top-level system controller

**Pipeline:**
```
AUDIT → FIX PLAN → EXECUTION → VERIFICATION → GOVERNANCE
```

**Sorumluluklar:**
- Agent koordinasyonu
- Pipeline state yönetimi
- Verification gate enforcement
- Governance kararları
- Production safety garantisi

**Kullanım:**
```
"audit the system"           → Master Admin Copilot'a delege
"fix finding F1"             → Fix Generator'a delege
"apply this fix"             → Debug Executor'a delege
"verify" / "is it safe?"     → Kendisi execute eder
```

### 3.2 Master Admin Copilot

**Dosya:** [`.github/agents/master-admin-copilot.agent.md`](../../.github/agents/master-admin-copilot.agent.md)

**Rol:** Production-grade system auditor

**Kapsam:**
- Property Hub (Özellik Havuzu, Şablonlar, Kategoriler)
- Wizard (İlan oluşturma, step-based capture)
- CRM (Kişiler, Talepler, Eşleştirmeler)
- Danışman/Operasyon (Performans, iş atama)
- Intelligence Layer (Rule/Prediction/Audit Engine)
- Location/POI (Harita, polygon, GeoJSON)

**Çalışma Sırası:**
1. Mevcut sistemi oku
2. Gerçek state çıkar
3. Root cause bul
4. Risk sınıfı ata
5. Fix planı üret
6. Minimum güvenli değişiklik öner
7. Test/verify adımı üret

**Yasaklar:**
- Uydurma tablo/alan/model varsayma
- DB okumadan UI hakkında kesin karar verme
- "Muhtemelen" ile fix önerme
- Büyük refactor önerme
- Çalışan sistemi kıracak sweeping change

**Kritik Zincirler:**
```
Wizard: Kategori → Alt Tür → Template → Field → Feature → Form → Save
Property Hub: Özellik Kategorisi → Özellik → Şablon → Atama → Paket
CRM: Kişi → Talep → Matchability → Eşleştirme → Advisor → Aksiyon
Location: İl/İlçe/Mahalle → Koordinat → Polygon → POI → Score
```

### 3.3 Fix Generator

**Dosya:** [`.github/agents/fix-generator.agent.md`](../../.github/agents/fix-generator.agent.md)

**Rol:** Minimal fix strategy producer

**Input:** Structured findings from Master Admin Copilot

**Output:**
| Field | Description |
|-------|-------------|
| Classification | bug / data-gap / schema / test / config |
| Priority | FIX NOW / FIX NEXT / MONITOR / IGNORE |
| Strategy | Minimal fix direction |
| Target Files | Exact file paths |
| Side Effects | What else could break |
| Verification | How to confirm fix works |

**Prensipler:**
- Smallest safe fix first
- No sweeping refactors
- Dependency explicit: "F2 depends on F1"
- Data gap ≠ bug
- Pre-existing test failure ≠ regression

### 3.4 Debug Executor

**Dosya:** [`.github/agents/debug-executor.agent.md`](../../.github/agents/debug-executor.agent.md)

**Rol:** IDE-ready patch generator

**Output Format:**
```
FILE: exact/path/to/file.php
ACTION: EDIT | ADD | DELETE
LOCATION: method name / line number
PATCH: exact old → new replacement
COMMAND: php artisan migrate / php -l / etc.
```

**Kurallar:**
- No full file dumps (only changed lines + 3 context)
- No backslash facades (always `use` import)
- Context7 field naming enforced
- Ordered by dependency, lowest risk first
- Each fix = discrete unit with verify command

---

## 4. MCP Sunucuları

### 4.1 Yalıhan Bekçi MCP

**Dosya:** [`mcp-servers/yalihan-bekci-mcp.js`](../../mcp-servers/yalihan-bekci-mcp.js)

**Özellikler:**
- AST-based code analysis
- Pattern recognition (13 learned patterns)
- Auto-fix engine (foundation)
- IDE entegrasyonu

**MCP Araçları (8 adet):**

| Tool | Açıklama |
|------|----------|
| `audit_code` | AST tabanlı kod analizi |
| `check_pattern` | Learned pattern kontrolü |
| `learn_pattern` | Yeni pattern öğretme |
| `get_patterns` | Pattern listesi |
| `auto_fix` | Otomatik kod düzeltme (beta) |
| `get_stats` | İstatistikler |
| `health_check` | Sistem sağlığı |
| `get_config` | Konfigürasyon |

**Auto-Fix Engine (Beta):**
```javascript
// Desteklenen fix'ler:
- Context7 field: status → yayin_durumu
- Context7 field: is_active → aktiflik_durumu
- Tenant fallback: tenant_id ?? 0 → tenantResolver
- Response: response()->json() → ResponseService::success()
```

### 4.2 NotebookLM MCP

**Konum:** [`mcp-servers/notebooklm-mcp/`](../../mcp-servers/notebooklm-mcp/)

**Özellikler:**
- NotebookLM entegrasyonu
- Session-based conversational research
- Source-cited responses
- Multi-pass strategy

**Aktif Notebook:**
- **Name:** Yalıhan AI OS - Project Knowledge
- **Content:** Tüm dokümantasyon, mimari kararlar, SAB kuralları, öğrenilen pattern'lar
- **Topics:** Laravel, AI Governance, SAB, Bekçi, Repository Pattern, CRM, Real Estate

**Kullanım:**
```javascript
// 1) Start broad
ask_question({ question: "Give me an overview of [topic]" })

// 2) Go specific (same session)
ask_question({ question: "Key APIs/methods?", session_id })

// 3) Cover pitfalls
ask_question({ question: "Common edge cases?", session_id })

// 4) Production example
ask_question({ question: "Show production-ready example", session_id })
```

---

## 5. Yalıhan Bekçi

### 5.1 Genel Tanım

**Bekçi:** AST (Abstract Syntax Tree) tabanlı mimari denetim katmanı

**Versiyon:** v2.1 (regex → AST geçişi tamamlandı)

**Kütüphane:** `nikic/php-parser`

### 5.2 Çalıştırma

```bash
# CI/CD — tüm kurallar
php artisan bekci:audit --all

# Sadece sır/anti-pattern taraması
php artisan bekci:audit --secret-scan

# Sadece sessiz catch denetimi
php artisan bekci:audit --silent-catch

# İsimlendirme ihlalleri
php artisan bekci:audit --naming

# TODO/FIXME takibi
php artisan bekci:audit --technical-debt
```

### 5.3 Temel Dosyalar

| Dosya | Rol |
|-------|-----|
| [`BekciAuditCommand.php`](../../app/Console/Commands/Governance/BekciAuditCommand.php) | Ana komut |
| [`AstScannerService.php`](../../app/Services/Governance/Ast/AstScannerService.php) | Tarama motoru |
| [`GovernanceAstRuleRegistry.php`](../../app/Services/Governance/Ast/GovernanceAstRuleRegistry.php) | Kural kaydı |
| [`GovernanceAstRuleInterface.php`](../../app/Services/Governance/Ast/GovernanceAstRuleInterface.php) | Kural arayüzü |
| [`config/sab_ast.php`](../../config/sab_ast.php) | Konfigürasyon |

### 5.4 Aktif AST Kuralları (7 adet)

| Kural ID | Dosya | Severity | Ne Yakalar |
|----------|-------|----------|------------|
| `SilentCatchAST` | `SilentCatchAstRule.php` | MEDIUM | Boş catch, throw/Log/report içermeyen catch |
| `EnvUsageAST` | `EnvUsageAstRule.php` | HIGH | `app/` içinde doğrudan `env()` çağrısı |
| `ForbiddenFunctionAST` | `ForbiddenFunctionAstRule.php` | HIGH | `eval`, `exec`, `shell_exec`, `system`, etc. |
| `ForbiddenFieldAST` | `ForbiddenFieldAstRule.php` | MEDIUM | `status`, `type`, `is_active`, `order` |
| `NamingAuthorityAST` | `NamingAuthorityAstRule.php` | WARNING | Domain/Framework isimlendirme karışıklığı |
| `LanguageHardcodeAST` | `LanguageHardcodedArrayAstRule.php` | HIGH | Hardcoded dil kodu dizileri |
| `AP-COGNITIVE-001` | `CognitiveGuardianRule.php` | BLOCKING | Boş catch + `env()` kullanımı (çift güvence) |

### 5.5 Yaşayan Bellek (Living Memory)

**Dosyalar:**
- [`ANTI_PATTERNS.json`](../../docs/governance/ANTI_PATTERNS.json) — Bilinen anti-pattern imzaları (regex)
- [`LEARNED_PATTERNS.json`](../../docs/governance/LEARNED_PATTERNS.json) — Çözülen hatalardan öğrenilen regresyon engelleyiciler (13 pattern)

**Pattern Öğretme:**
```bash
php artisan bekci:pattern-learn \
  --name="Duplicate Route Registration" \
  --signature="Route::(get|post).*->name\(['\"]([^'\"]+)['\"]" \
  --severity=HIGH \
  --description="Duplicate route causes ambiguous routing"
```

### 5.6 Bypass Mekanizmaları

```php
// Catch bloğu bypass
/**
 * @sab-ignore-catch
 */
catch (Exception $e) {
    // Intentionally empty
}

// Field bypass (satır bazında)
$query->where('status', 1); // context7-ignore

// Kural bazında devre dışı (config)
'enabled' => false,
'report_only' => true,
```

### 5.7 SAB Bağlantısı

| SAB Maddesi | Bekçi Kuralı |
|-------------|--------------|
| Madde 4: Silent catch yasaktır | `SilentCatchAST` |
| Madde 8: Context7 ihlal toleransı = 0 | `ForbiddenFieldAST` |
| Madde 14: Bilişsel Muhafız bypass yasaktır | `AP-COGNITIVE-001` |
| Madde 15: Learned Patterns regresyonu bloklar | Living Memory taraması |

### 5.8 Laravel Scheduler Otomasyonu

**Konum:** [`app/Console/Kernel.php`](../../app/Console/Kernel.php)

```php
// 1. Günlük tam audit - 02:00
$schedule->command('bekci:audit --all')->dailyAt('02:00');

// 2. Secret scan - Her 6 saatte
$schedule->command('bekci:audit --secret-scan')->everySixHours();

// 3. Silent catch - Her 4 saatte
$schedule->command('bekci:audit --silent-catch')->cron('0 */4 * * *');

// 4. Technical debt - Haftalık Pazartesi 09:00
$schedule->command('bekci:audit --technical-debt')->weekly()->mondays()->at('09:00');
```

**Loglar:** `storage/logs/bekci-*.log`

---

## 6. Workflow Pipeline

### 6.1 Master Orchestrator Pipeline

```
┌─────────────────────────────────────────────────────────┐
│                    START                                 │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  STAGE 1: AUDIT                                          │
│  Agent: Master Admin Copilot                             │
│  Output: Structured findings (ID, Risk, Impact, Evidence)│
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  STAGE 2: FIX PLAN                                       │
│  Agent: Fix Generator                                    │
│  Output: Classification, Priority, Strategy, Files       │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  STAGE 3: EXECUTION                                      │
│  Agent: Debug Executor                                   │
│  Output: IDE-ready patches (FILE, ACTION, PATCH, COMMAND)│
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  STAGE 4: VERIFICATION                                   │
│  Agent: Self (Orchestrator)                              │
│  Checks: Syntax, DB state, Endpoint, Tests               │
└────────────────────┬────────────────────────────────────┘
                     │
                ┌────┴────┐
                │  PASS?  │
                └────┬────┘
                     │
         ┌───────────┴───────────┐
         │                       │
        YES                     NO
         │                       │
         ▼                       ▼
┌────────────────┐      ┌────────────────┐
│ STAGE 5:       │      │ CLASSIFY       │
│ GOVERNANCE     │      │ FAILURE        │
│                │      │                │
│ Decision:      │      │ REGRESSION?    │
│ SAFE           │      │ PRE-EXISTING?  │
│ SAFE+WARNING   │      │ ENVIRONMENT?   │
│ UNSAFE         │      └────────┬───────┘
└────────┬───────┘               │
         │                       │
         ▼                       ▼
┌────────────────┐      ┌────────────────┐
│     END        │      │  RE-ENTER      │
│   (SUCCESS)    │      │  PIPELINE      │
└────────────────┘      └────────────────┘
```

### 6.2 Verification Gate

**Zorunlu Kontroller:**

| Check | Komut | Beklenen |
|-------|-------|----------|
| Syntax | `php -l <file>` | "No syntax errors" |
| DB state | `php artisan tinker --execute="..."` | Correct table/count/value |
| Endpoint | `curl -s -X POST/GET ...` | Expected JSON structure |
| No 500 | Hit affected route | No error response |
| Tests | `php artisan test --filter=<relevant>` | Green or pre-existing failure |

**Yasak Verification:**
- ❌ "looks fixed"
- ❌ "should work"
- ❌ "I checked the code"
- ❌ "no errors visible"

**Sadece Kabul Edilen:**
- ✅ Terminal output
- ✅ Actual response body
- ✅ DB query result
- ✅ Test runner output

### 6.3 Failure Classification

| Classification | Meaning | Action |
|----------------|---------|--------|
| REGRESSION | Our fix broke it | Re-enter pipeline |
| PRE-EXISTING | Was broken before | Document, continue |
| ENVIRONMENT | Test env issue | Document, continue |
| MISSING_COMMAND | Artisan command not created | Document, continue |

---

## 7. Kullanım Senaryoları

### 7.1 Senaryo 1: Sistem Audit

**Kullanıcı İsteği:**
```
"Tüm admin modüllerini audit et"
```

**Pipeline:**
```
1. Master Orchestrator → Master Admin Copilot'a delege
2. Master Admin Copilot:
   - authority.json oku
   - Route'ları oku
   - Controller'ları oku
   - DB state kontrol et
   - Structured findings üret
3. Master Orchestrator → Findings'i kullanıcıya sun
```

**Çıktı:**
```markdown
## 🔍 FINDINGS

| ID | Finding | Risk | Module |
|----|---------|------|--------|
| F1 | DanismanController uses direct ORM | MEDIUM | Danışman |
| F2 | MyListingsController 378 lines | LOW | İlanlar |
```

### 7.2 Senaryo 2: Fix Uygulama

**Kullanıcı İsteği:**
```
"F1'i fix et"
```

**Pipeline:**
```
1. Master Orchestrator → Fix Generator'a delege
2. Fix Generator:
   - F1 için minimal fix strategy üret
   - Target files belirle
   - Side effects analiz et
3. Master Orchestrator → Debug Executor'a delege
4. Debug Executor:
   - IDE-ready patch üret
   - Verify command ekle
5. Master Orchestrator → Verification
   - php -l check
   - Test run
   - DB state verify
6. Master Orchestrator → Governance Decision
   - SAFE / SAFE+WARNING / UNSAFE
```

### 7.3 Senaryo 3: Bekçi Quick Check

**Kullanıcı İsteği:**
```
"Commit öncesi kontrol yap"
```

**Komut:**
```bash
# VSCode Task veya
php artisan bekci:audit --silent-catch
```

**Çıktı:**
```
🛡️ Yalıhan Bekçi: Cognitive Audit Starting...

✅ Audit PASSED: System remains architecturally sound.

Silent Catch: 0 new violations
Env Usage: 0 violations
Forbidden Functions: 0 violations
```

### 7.4 Senaryo 4: Pattern Öğretme

**Kullanıcı İsteği:**
```
"Duplicate route pattern'ini öğret"
```

**Komut:**
```bash
php artisan bekci:pattern-learn \
  --name="Duplicate Route Registration" \
  --signature="Route::(get|post).*->name\(['\"]([^'\"]+)['\"]" \
  --severity=HIGH
```

**Sonuç:**
- Pattern LEARNED_PATTERNS.json'a eklenir
- Gelecek audit'lerde otomatik kontrol edilir
- Regresyon engellenir

---

## 8. Metrikler & İstatistikler

### 8.1 Bekçi İstatistikleri (Oturum 12)

| Metrik | Değer |
|--------|-------|
| Aktif AST Kuralları | 7 |
| Öğrenilen Pattern'lar | 13 |
| MCP Araçları | 8 + auto_fix engine |
| Otomatik Schedule'lar | 4 (günlük/6h/4h/haftalık) |
| VSCode Tasks | 11 |
| Pre-commit Kontrolleri | 4 |

### 8.2 Agent Sistemi

| Agent | Satır Sayısı | Sorumluluk Alanı |
|-------|--------------|------------------|
| Master Orchestrator | 348 | Pipeline control, verification, governance |
| Master Admin Copilot | 407 | System audit, root cause analysis |
| Fix Generator | ~200 | Minimal fix strategy |
| Debug Executor | ~150 | IDE-ready patches |

### 8.3 Son Audit Sonuçları (2026-05-17)

```
✅ Audit PASSED: System remains architecturally sound.

Silent Catch: 262 (pre-existing)
Naming Authority: 3118 (pre-existing, legacy migrations)
Forbidden Fields: 0 ✅
Env Usage: 0 ✅
Forbidden Functions: 0 ✅
Technical Debt (TODO): 3 (tracked)
```

**0 yeni ihlal** — Mimari disiplin korunuyor

---

## 9. Gelecek Geliştirmeler

### Phase 2: Gerçek Zamanlı Analiz (3-4 hafta)
- [ ] LSP (Language Server Protocol) entegrasyonu
- [ ] Inline diagnostics (hover tooltip)
- [ ] Quick fix actions (Ctrl+.)
- [ ] IDE'de kod yazarken anlık uyarı

### Phase 3: AI-Powered Governance (4-6 hafta)
- [ ] Semantic code understanding (DeepSeek R1)
- [ ] Context-aware suggestions
- [ ] Predictive violations (ML model)

### Phase 4: Ekosistem Entegrasyonu (6-8 hafta)
- [ ] GitHub Actions bot (PR yorumları)
- [ ] Slack/Discord notifications
- [ ] Web dashboard (panel.yalihanemlak.com.tr/admin/bekci)

---

## 10. Referanslar

### Dokümantasyon
- [SYSTEM_MAP.md](SYSTEM_MAP.md) — Sistem özellik haritası
- [CLAUDE_MEMORY.md](../governance/CLAUDE_MEMORY.md) — Claude hafızası
- [BEKCI_CHANGELOG.md](../BEKCI_CHANGELOG.md) — Bekçi geliştirme günlüğü
- [LEARNED_PATTERNS.json](../governance/LEARNED_PATTERNS.json) — Öğrenilen pattern'lar
- [SAB.md](../SAB.md) — Teknik Anayasa

### Agent Dosyaları
- [master-orchestrator.agent.md](../../.github/agents/master-orchestrator.agent.md)
- [master-admin-copilot.agent.md](../../.github/agents/master-admin-copilot.agent.md)
- [fix-generator.agent.md](../../.github/agents/fix-generator.agent.md)
- [debug-executor.agent.md](../../.github/agents/debug-executor.agent.md)

### MCP Sunucuları
- [yalihan-bekci-mcp.js](../../mcp-servers/yalihan-bekci-mcp.js)
- [notebooklm-mcp/](../../mcp-servers/notebooklm-mcp/)

---

**Son Güncelleme:** 2026-05-17 (Oturum 12)
**Durum:** OPERATIONAL
**Risk Seviyesi:** 🟢 LOW

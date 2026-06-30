# SYSTEM ARCHITECTURE — Yalıhan AI OS

> **Version:** 1.0.0 | **Date:** 2026-06-25
> **Status:** Living Document — auto-updated by Kilo agent

Yalıhan2026 is not just a Laravel project. It is a **self-documenting AI Operating System** — a platform where AI agents and human developers share the same codebase, governance rules, and memory.

---

## Architecture Overview

```
╔═══════════════════════════════════════════════════════════════════╗
║                      YALIHAN AI OS                          ║
║           Self-Documenting AI Operating System                 ║
╚═══════════════════════════════════════════════════════════════════╝

                        ┌─────────────────┐
                        │   CHIEF AI      │  ← Yönetim katmanı
                        │  (Orchestrator) │
                        └────────┬────────┘
                                 │
                    ┌────────────┴────────────┐
                    │                         │
              ┌─────▼──────┐          ┌─────▼──────┐
              │ chief-ai/  │          │  memory/   │
              │ 7 files    │          │ 7 files    │
              │ • Sprint   │          │ • Brain    │
              │ • Risk     │          │ • Changelog│
              │ • Debt     │          │ • Session  │
              │ • Gaps     │          │ • Patterns │
              └────────────┘          └────────────┘
                                 │
              ┌─────────────────┴─────────────────┐
              │                                   │
        ┌─────▼─────┐                     ┌─────▼──────┐
        │    SAB     │                     │   BEKÇİ     │
        │ Governance │                     │  Cognitive  │
        │ Constitution│                     │  Guardian   │
        │  (Anayasa) │                     │  v2.1       │
        └─────┬─────┘                     └──────┬──────┘
              │                                   │
              └───────────────┬───────────────────┘
                              │
                    ┌───────▼────────┐
                    │   MEMORY BRAIN   │  ← Öğrenme & hafıza
                    │  Self-Documenting │
                    │  (memory/)       │
                    └───────┬──────────┘
                            │
    ┌───────────────────────┼───────────────────────┐
    │                       │                       │
┌───▼───────┐  ┌──────────▼──┐  ┌──────────────▼─────┐
│  BACKEND   │  │  FRONTEND  │  │      LARAVEL      │─┘
└────────────┘  └────────────┘  └───────────────────┘

                    ┌───────────────────┐
                    │   AGENT LAYER    │
                    ├───────────────────┤
                    │ • Claude Desktop   │
                    │ • Cursor          │
                    │ • Windsurf        │
                    │ • Cline / Roo    │
                    │ • Kilo (AIWeb)   │
                    └─────────┬─────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
   ┌────▼────┐  ┌──────────▼──┐  ┌──────────────▼────┐
   │   MCP   │  │   HERMES   │  │  OpenClaw        │
   │  Bridge │  │  (mcp/)    │  │  Observability   │
   └─────────┘  └────────────┘  └─────────────────┘

        ┌──────────────────────────────────────────────┐
        │         EXTERNAL INTEGRATION LAYER            │
        ├──────────────────────────────────────────────┤
        │  n8n Workflow    │  Telegram Bot              │
        │  Airbnb Sync    │  NotebookLM Docs           │
        │  Google Drive  │  TKGM Tapu/Kadastro        │
        │  DeepSeek v4   │  Ollama / OpenAI          │
        └──────────────────────────────────────────────┘

YALIHAN AI OS PRENSİBİ:
  • Kod yazar.
  • Yaptığı değişiklikleri kaydeder.
  • Mimari kararları belgelendirir.
  • Tekrarlanan hatalardan öğrenir.
  • Yeni gelen ajan/proje dakikalar içinde anlar.
  • Kritik dosyaları korur.
  • Gerektiğinde sağlığı denetler.
```
┌──────────────────────────────────────────────────────────────────────────────┐
│                           YALIHAN AI OS — SYSTEM LAYERS                         │
└──────────────────────────────────────────────────────────────────────────────┘

                        ┌──────────────────────────────┐
                        │   HUMAN DEVELOPERS          │
                        │   Claude Desktop / Cursor   │
                        │   Windsurf / Cline / Roo   │
                        └────────────┬───────────────┘
                                     │
                    ┌────────────────┼────────────────┐
                    │                │                │
              ┌─────▼──────┐  ┌────▼─────┐  ┌────▼──────┐
              │  AIWebModel │  │  MCP    │  │  Local    │
              │  Platform   │  │  Bridge  │  │  IDE      │
              │  (Kilo)    │  │  (stdio) │  │  Config   │
              └─────┬──────┘  └────┬─────┘  └────┬──────┘
                    │               │               │
                    └───────┬───────┴───────────────┘
                            │
                    ┌───────▼───────────────────────────────────────┐
                    │           AI WORKSPACE LAYER                      │
                    │                                                │
                    │   ┌─────────┐  ┌─────────┐  ┌─────────┐   │
                    │   │ agents/ │  │prompts/ │  │knowledge│   │
                    │   │  (5)   │  │  (3)   │  │  (3)   │   │
                    │   └────┬────┘  └────┬────┘  └────┬────┘   │
                    │        │            │            │          │
                    │   ┌────▼────────────▼────────────▼────┐   │
                    │   │           memory/                  │   │
                    │   │  PROJECT_BRAIN │ CHANGELOG       │   │
                    │   │  SESSION      │ LEARNED_PATTERNS │   │
                    │   │  DECISIONS    │ WHERE_IS_WHAT   │   │
                    │   └────────────────────────────────────┘   │
                    │        (Self-documenting memory)           │
                    └────────────────────┬──────────────────────┘
                                         │
                    ┌────────────────────┼──────────────────────────────────┐
                    │                    │                                    │
              ┌─────▼──────────┐  ┌────▼──────────┐  ┌──────────────┐  │
              │   workflows/    │  │   audits/     │  │   mcp/       │  │
              │  (CI/CD/Deploy)│  │  (Reports)    │  │  TS Bridge   │  │
              │                 │  │               │  └──────┬───────┘  │
              │                 │  │               │         │           │
              └─────────────────┘  └───────────────┘  ┌──────▼───────┐  │
                                                     │ mcp-servers/ │  │
                                                     │  JS Server   │  │
                                                     └──────┬───────┘  │
                                                            │
              ┌───────────────────────────────────────────────┼───────────────┐
              │                                               │               │
        ┌─────▼───────────────────────────────────────────▼───────────┐   │
        │                    LARAVEL CORE LAYER                            │   │
        │                                                              │   │
        │   ┌──────────────────────────────────────────────────────┐  │   │
        │   │  Controllers (Thin) → Services → Domain → Repository │  │   │
        │   │  8 Domain: Property, CRM, AI, Governance, Finance    │  │   │
        │   │  211 Models | 384 Services | 94 AI Services         │  │   │
        │   │  CQRS: Projections (Read) ← Event ← Core DB (Write) │  │   │
        │   └──────────────────────────────────────────────────────┘  │   │
        │                                                              │   │
        │   ┌──────────────────────────────────────────────────────┐  │   │
        │   │  SAB GOVERNANCE LAYER                                 │  │   │
        │   │  AST Scanner | Guards | Health Monitoring | CI/CD     │  │   │
        │   │  Context7 Naming | Tenant Isolation | Thin Controller │  │   │
        │   └──────────────────────────────────────────────────────┘  │   │
        │                                                              │   │
        │   ┌──────────────────────────────────────────────────────┐  │   │
        │   │  BEKÇI v2.1 — Cognitive Guardian                    │  │   │
        │   │  MCP Tools | Learning | Telemetry | Audits           │  │   │
        │   └──────────────────────────────────────────────────────┘  │   │
        │                                                              │   │
        │   ┌──────────────────────────────────────────────────────┐  │   │
        │   │  EXTERNAL INTEGRATIONS                                │  │   │
        │   │  DeepSeek v4 | Ollama | OpenAI | Telegram | N8N     │  │   │
        │   │  TKGM | TurkiyeAPI | Market Intelligence DB          │  │   │
        │   └──────────────────────────────────────────────────────┘  │   │
        └──────────────────────────────────────────────────────────────┘   │
```

---

## 1. Laravel Core

```
┌──────────────────────────────────────────────────────────────┐
│  LARAVEL CORE — Production Seal v24.2.0                      │
└──────────────────────────────────────────────────────────────┘

Stack
  • Laravel 10.x | PHP 8.2+ | MySQL (prod) | SQLite (test)
  • Tailwind CSS | Alpine.js | Vite
  • Redis | Horizon

Architecture Pattern
  • Modular Monolith — Domain-Driven Design
  • CQRS — Write: Core DB / Read: Projections
  • Event-Driven — Domain Events → Queue → Projection sync

8 Domain Boundaries
  ┌─────────────┬───────────────────────────────────────┐
  │ Property     │ Ilan, Photo, Price, Category, Reservation │
  │ CRM          │ Kisi, Lead, Talep, Matching, Pipeline │
  │ Feature/Tpl  │ Feature, Template, FeaturePack, Dependency │
  │ AI           │ YalihanCortex, 94 AI services          │
  │ Governance   │ Decision, Rollback, Suppression, Audit    │
  │ Finance      │ Ledger, FxRate, Currency, Rental        │
  │ Intelligence  │ Projections, Market, DealRadar           │
  │ Location     │ Il, Ilce, Mahalle (Canonical SSOT)     │
  └─────────────┴───────────────────────────────────────┘

Critical Files (PROTECTED — do not modify)
  ┌────────────────────────────────────────────────────────┐
  │ app/Services/Ilan/IlanCrudService.php                   │
  │   → Single write authority for all listing mutations     │
  │   → NEVER bypass this class for DB writes              │
  │                                                       │
  │ app/Services/AI/YalihanCortex.php                     │
  │   → Central AI orchestrator                            │
  │   → All AI routing must pass through here             │
  │                                                       │
  │ app/Models/Ilan.php (72K LOC)                        │
  │ app/Models/Kisi.php (18K LOC)                         │
  │ app/Models/GovernanceDecision.php (9.7K LOC)          │
  └────────────────────────────────────────────────────────┘

Write Chain (Thin Controller Rule)
  Controller → Service → IlanCrudService → Repository → DB
  Controller must NEVER contain Eloquent::create/update/delete

Tenant Isolation
  Every query MUST include tenant_id filter
  Cross-tenant access = RULE-T1 violation = CI FAIL
```

---

## 2. SAB Governance

```
┌──────────────────────────────────────────────────────────────┐
│  SAB — PRODUCTION SEAL — Technical Constitution              │
└──────────────────────────────────────────────────────────────┘

Authority: docs/SAB.md (checksum: docs/SAB.sha256)
SSOT: .sab/authority.json

Binding Rules (0 tolerance)
  1. Core (Ledger/CRM) IMMUTABLE
  2. Direct write to Core = BLOCKED
  3. Observer bypass = BLOCKED
  4. Silent catch = BLOCKED (Fail-Fast mandatory)
  5. Raw DB write = BLOCKED (except migrations)
  6. Projection tables = READ ONLY
  7. Context7 violation = 0 tolerance
  8. DLQ mandatory

Phase 12 Financial Rules
  • tenant_id required in ALL financial queries
  • AiBudgetGuard::canExecute() check before any AI call
  • Balance mutations ONLY via recordDoubleEntry()

Naming Authority (Context7)
  Domain model fields → Turkish
  Framework conventions → English

  ┌──────────────────┬────────────────────┐
  │ ❌ Forbidden      │ ✅ Canonical       │
  ├──────────────────┼────────────────────┤
  │ status           │ yayin_durumu       │
  │ active           │ aktiflik_durumu    │
  │ type             │ tip                │
  │ description      │ aciklama           │
  │ order            │ display_order      │
  └──────────────────┴────────────────────┘

CI Pipeline (Gold Line)
  test → sab:integrity-scan → bekci:wizard-contract
  → env-drift-guard → quality-gate.sh

Verification
  php artisan sab:integrity-scan
  php artisan bekci:health --detailed
```

---

## 3. Bekçi v2.1 — Cognitive Guardian

```
┌──────────────────────────────────────────────────────────────┐
│  BEKÇI v2.1 — AST-Based Semantic Governance                 │
└──────────────────────────────────────────────────────────────┘

Three-Layer Defense
  ┌─────────────────────────────────────────────────────────┐
  │  Layer 1: AST Scanner (app/Services/Governance/Ast/)     │
  │  • NamingAuthorityAstRule                              │
  │  • ForbiddenFieldAstRule                             │
  │  • SilentCatchAstRule                                 │
  │  • ThinControllerRule                                 │
  ├─────────────────────────────────────────────────────────┤
  │  Layer 2: Guard Scripts (scripts/guards/)              │
  │  • ci-guard-tenant-isolation.sh                       │
  │  • ci-guard-naming-authority.sh                      │
  │  • ci-guard-exception-swallow.sh                     │
  │  • check-hardcoded-endpoints.sh                       │
  │  • ci-guard-finance-authority.sh                     │
  ├─────────────────────────────────────────────────────────┤
  │  Layer 3: Health Monitoring                          │
  │  • bekci:health — 91.85% (MCP 100%, KB 100%)      │
  │  • bekci:learn — Pattern learning from actions        │
  │  • bekci:audit — Telescope runtime inspection         │
  └─────────────────────────────────────────────────────────┘

Health Score (bekci:health)
  ┌──────────────────┬────────────────────┐
  │ Component         │ Score              │
  ├──────────────────┼────────────────────┤
  │ MCP Server       │ 100% ✅           │
  │ Knowledge Base   │ 100% (41 entries) │
  │ Learning Activity│ 100% (40 actions) │
  │ Project Health   │ 59.25% ⚠️        │
  │ OVERALL          │ 91.85% ✅         │
  └──────────────────┴────────────────────┘

Project Health 59.25% = Naming Authority violations (175 files)
→ Sprint 3.1: Naming Authority cleanup planned

Command Reference
  php artisan bekci:health --detailed
  php artisan sab:integrity-scan
  php artisan bekci:learn {topic} {context}
  php artisan bekci:audit --scope=full
```

---

## 4. AI Workspace

```
┌──────────────────────────────────────────────────────────────┐
│  AI WORKSPACE — Self-Documenting Agent OS                     │
└──────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  agents/ — Agent Instruction Files (5)                     │
├─────────────────────────────────────────────────────────────┤
│  backend.md     → Backend write rules, CQRS, Tenant        │
│  frontend.md    → Layout, icons, dark mode, Tailwind         │
│  laravel.md    → Framework specifics, migrations, cache    │
│  governance.md → SAB, CI pipeline, authority.json         │
│  mcp.md        → MCP tools, config, server status         │
│                                                             │
│  Usage: Read relevant agent file before starting work      │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  prompts/ — AI Prompt & Template Files (3)                 │
├─────────────────────────────────────────────────────────────┤
│  sab.md        → SAB rules summary (quick reference)        │
│  context7.md   → Naming authority canonical fields        │
│  cortex.md     → YalihanCortex AI pipeline overview      │
│                                                             │
│  Usage: Include in AI prompts for correct context         │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  knowledge/ — Knowledge Base (3 subdirectories)           │
├─────────────────────────────────────────────────────────────┤
│  learning/   → MCP/Audit learning records (JSON)          │
│  patterns/   → Architectural patterns (markdown)          │
│  agents/     → Agent-specific notes                       │
│                                                             │
│  Source: yalihan-bekci/knowledge/ (Node MCP)             │
│          yalihan-bekci/learning/ (PHP Audit)             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  memory/ — Agent Memory (7 files)                         │
├─────────────────────────────────────────────────────────────┤
│  PROJECT_BRAIN.md     → Persistent metrics, sprint status   │
│  CHANGELOG_AGENT.md  → All agent changes (auto-updated)   │
│  SESSION_NOTES.md    → Session context (between sessions)  │
│  LEARNED_PATTERNS.md → Recurring errors and fixes        │
│  DECISIONS.md        → Architectural decisions (ADR)      │
│  WHERE_IS_WHAT.md   → Quick reference map               │
│  HOW_IT_WORKS.md     → System mechanics explanation       │
│                                                             │
│  Session protocol:                                         │
│  • Session start → Read PROJECT_BRAIN + WHERE_IS_WHAT     │
│  • Task done → Update CHANGELOG_AGENT.md                 │
│  • Session end → Update SESSION_NOTES.md                 │
│  • Error found → Update LEARNED_PATTERNS.md              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  workflows/ — Automation Workflows                         │
├─────────────────────────────────────────────────────────────┤
│  deploy.md  → Hetzner CX33 deploy procedure              │
│  ci-cd.md  → Gold Line CI/CD pipeline                   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  audits/ — Audit Reports                                 │
├─────────────────────────────────────────────────────────────┤
│  README.md → Audit types and formats                      │
│  Format: audit-YYYY-MM-DD-{scope}.md                    │
│  Types: sab, context7, tenant, security, mcp            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  chief-ai/ — Chief AI Management Layer                    │
├─────────────────────────────────────────────────────────────┤
│  README.md              → Chief AI role & rules            │
│  sprint-backlog.md      → Sprint 3-6 task backlog          │
│  risk-register.md      → 7 active risks, scoring          │
│  technical-debt.md      → Debt inventory (445 pts)         │
│  agent-assignments.md   → Agent capacity matrix            │
│  gap-analysis.md        → 5 system gaps identified        │
│  decision-log.md        → Architectural decisions          │
│                                                             │
│  Chief AI Rules:                                            │
│  • Chief AI does NOT write code                            │
│  • Chief AI READS system state, risks, debts              │
│  • Chief AI CREATES tasks, assigns to agents               │
│  • Chief AI TRACKS risk and priority                      │
│  • Protected files: SAB, authority, IlanCrudService,       │
│    YalihanCortex — CANNOT modify                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Kilo + AIWebModel

```
┌──────────────────────────────────────────────────────────────┐
│  KILO AGENT — AIWebModel Provider                          │
└──────────────────────────────────────────────────────────────┘

Agent Identity
  • Model: GPT-5.2 Codex (aiwebmodel/gpt-5.2-codex)
  • Platform: AIWebModel
  • Role: Software engineering, analysis, code generation
  • Context window: 32K tokens

Memory Strategy
  • Session-scoped: todowrite tool (in-memory todos)
  • Persistent: memory/ files (markdown, version-controlled)
  • Project-scoped: CLAUDE.md, docs/, agents/

Session Protocol
  1. Read memory/PROJECT_BRAIN.md + memory/WHERE_IS_WHAT.md
  2. Verify metrics (find, grep commands)
  3. Run: php artisan bekci:health --detailed
  4. Run: php artisan sab:integrity-scan
  5. Plan task with todowrite
  6. Execute with anti-gravity gates
  7. Update memory files after meaningful work
  8. Document: what changed, why, how to verify

Protected Files (NEVER modify silently)
  docs/SAB.md
  .sab/authority.json
  app/Services/Ilan/IlanCrudService.php
  app/Services/AI/YalihanCortex.php

Verification Commands (always run these first)
  php artisan bekci:health --detailed
  php artisan sab:integrity-scan
  ./scripts/tools/antigravity-full-gate.sh
  find app/Models -name "*.php" | wc -l    # Should be 211
  grep "^class.*Service" app/Services/ --include="*.php" | wc -l  # Should be 384
```

---

## 6. MCP Status

```
┌──────────────────────────────────────────────────────────────┐
│  MCP SERVER STATUS — 2026-06-25                              │
└──────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  TypeScript Bridge — mcp/build/index.js                    │
│  Status: ✅ RUNNING (PID 9568)                            │
│  Used by: Windsurf (.roo/mcp.json)                        │
│  Tools: bekci.scan, bekci.learn, bekci.health             │
│  Transport: stdio                                         │
│  PHP path: /opt/homebrew/bin/php (hardcoded) ⚠️          │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  JavaScript Server — mcp-servers/yalihan-bekci-mcp.js      │
│  Status: ⚠️ NOT TESTED IN THIS SESSION                     │
│  Used by: Cursor (.cursor/mcp.json), Claude (claude.json)  │
│  Tools (9):                                              │
│    validate_file, get_canonical, check_violation          │
│    get_project_health, get_authority                      │
│    record_learning, scan_telescope                        │
│    get_audit_report, get_learning_history                │
│  Transport: stdio                                         │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  MCP Config Files                                         │
├─────────────────────────────────────────────────────────────┤
│  .roo/mcp.json       → Windsurf → mcp/build/index.js    │
│  .cursor/mcp.json    → Cursor → mcp-servers/*.js         │
│  claude.json         → Claude Desktop → mcp-servers/*.js │
│  .vscode/mcp.json    → Not configured                    │
└─────────────────────────────────────────────────────────────┘

Note: "Server running" ≠ "Kilo can use MCP tools"
MCP tool invocation requires IDE-level MCP integration.
```

---

## 7. Memory System (Self-Documenting)

```
┌──────────────────────────────────────────────────────────────┐
│  MEMORY SYSTEM — How Yalıhan2026 Remembers Itself            │
└──────────────────────────────────────────────────────────────┘

Design Principle
  Every session is independent.
  Persistent memory lives in files.
  No information is stored in agent context alone.

Memory Update Protocol
  ┌─────────────────────┬──────────────────────────────────┐
  │ Event               │ Action                            │
  ├─────────────────────┼──────────────────────────────────┤
  │ Session start      │ Read: PROJECT_BRAIN, WHERE_IS_WHAT│
  │ Meaningful task    │ Update: CHANGELOG_AGENT.md         │
  │ Session end        │ Update: SESSION_NOTES.md          │
  │ Error/fix found    │ Update: LEARNED_PATTERNS.md       │
  │ Architectural ADR  │ Update: DECISIONS.md              │
  │ System change     │ Update: HOW_IT_WORKS.md           │
  └─────────────────────┴──────────────────────────────────┘

Cross-Session Continuity
  Next agent reads SESSION_NOTES.md (last 2-3 sessions)
  → Gets context without reading full history

Verification
  All memory files are markdown → human-readable
  All memory files are git-tracked → version history
  All memory files are in root/ → accessible to all IDEs
```

---

## 8. Directory Map

```
yalihan2026/
│
├── 📁 agents/                   # AI agent instructions
│     ├── README.md             → Usage guide
│     ├── backend.md            → Write rules
│     ├── frontend.md           → UI rules
│     ├── laravel.md            → Framework rules
│     ├── governance.md         → SAB rules
│     └── mcp.md               → MCP config
│
├── 📁 prompts/                # AI prompt templates
│     ├── README.md
│     ├── sab.md               → SAB summary
│     ├── context7.md          → Naming standards
│     └── cortex.md            → AI pipeline
│
├── 📁 knowledge/              # Knowledge base
│     ├── README.md
│     ├── learning/            → MCP learning records
│     ├── patterns/            → Architectural patterns
│     └── agents/              → Agent notes
│
├── 📁 memory/                 # Agent memory (self-updating)
│     ├── PROJECT_BRAIN.md     → Metrics & status
│     ├── CHANGELOG_AGENT.md  → Change log
│     ├── SESSION_NOTES.md    → Session notes
│     ├── LEARNED_PATTERNS.md → Error patterns
│     ├── DECISIONS.md        → ADR log
│     ├── WHERE_IS_WHAT.md     → Quick map
│     └── HOW_IT_WORKS.md     → System mechanics
│
├── 📁 workflows/             # Automation
│     ├── README.md
│     ├── deploy.md           → Hetzner deploy
│     └── ci-cd.md            → CI/CD pipeline
│
├── 📁 audits/                # Reports
│     └── README.md
│
├── 📁 mcp/                   # TypeScript MCP Bridge (PROTECTED)
│     ├── src/index.ts
│     └── build/index.js
│
├── 📁 mcp-servers/           # JS MCP Server (PROTECTED)
│     ├── yalihan-bekci-mcp.js
│     └── notebooklm-mcp/
│
├── 📁 yalihan-bekci/         # Bekçi learning source
│     ├── knowledge/
│     └── learning/
│
├── 📁 chief-ai/              # Chief AI management layer
│     ├── README.md            → Role definition
│     ├── sprint-backlog.md    → Sprint tasks
│     ├── risk-register.md    → Risk tracking
│     ├── technical-debt.md    → Debt inventory
│     ├── agent-assignments.md → Capacity matrix
│     ├── gap-analysis.md     → System gaps
│     └── decision-log.md      → ADR log
│
├── 📁 docs/                   # Project documentation
│     ├── SAB.md (PROTECTED)
│     ├── PROGRESS-TRACKER.md
│     ├── ROADMAP.md
│     └── architecture/
│
├── 📁 .sab/                   # Governance SSOT (PROTECTED)
│     ├── authority.json
│     ├── sab-baseline.json
│     └── snapshots/
│
├── 📁 app/                    # Laravel application
│     ├── Services/
│     │     ├── Ilan/IlanCrudService.php (PROTECTED)
│     │     └── AI/YalihanCortex.php (PROTECTED)
│     └── Models/
│
└── 📁 scripts/                # Automation tools
      ├── guards/               # CI guard scripts
      ├── tools/                # Development helpers
      └── ops/                  # Operational scripts
```

---

## 9. Verification Commands

```bash
# ── System Health ────────────────────────────────────────
php artisan bekci:health --detailed
php artisan sab:integrity-scan
./scripts/tools/antigravity-full-gate.sh

# ── Metrics ───────────────────────────────────────────────
find app/Models -name "*.php" | wc -l          # Expected: 211
grep -rh "^class.*Service" app/Services --include="*.php" | wc -l  # Expected: 384
grep -rh "^class.*Service" app/Services/AI --include="*.php" | wc -l # Expected: 94

# ── Naming Authority ──────────────────────────────────────
php artisan sab:integrity-scan 2>&1 | grep "NamingAuthority" | wc -l
# Project Health = (total_clean / total_checked) * 100

# ── MCP Server ────────────────────────────────────────────
pgrep -f "mcp/build\|mcp-servers"             # Should show PID
php artisan bekci:health 2>&1 | grep "MCP"    # Should show "responding"

# ── CI/CD ────────────────────────────────────────────────
php artisan test --compact
php artisan sab:guard
./scripts/guards/quality-gate.sh

# ── Memory ────────────────────────────────────────────────
ls memory/*.md    # Should show 7 files
wc -l memory/CHANGELOG_AGENT.md  # Should have entries

# ── Protected Files ──────────────────────────────────────
ls -la app/Services/Ilan/IlanCrudService.php  # Should be unchanged
ls -la app/Services/AI/YalihanCortex.php       # Should be unchanged
ls -la docs/SAB.md                             # Should be unchanged
```

---

## 10. Quick Reference

| What | Where |
|------|--------|
| Project identity | `CLAUDE.md` |
| System health | `memory/PROJECT_BRAIN.md` |
| What's where | `memory/WHERE_IS_WHAT.md` |
| How system works | `memory/HOW_IT_WORKS.md` |
| Current sprint | `memory/SESSION_NOTES.md` |
| Agent changes | `memory/CHANGELOG_AGENT.md` |
| Errors & fixes | `memory/LEARNED_PATTERNS.md` |
| Decisions | `memory/DECISIONS.md` |
| Architecture | `docs/SYSTEM_ARCHITECTURE.md` (this file) |
| Technical constitution | `docs/SAB.md` |
| Governance SSOT | `.sab/authority.json` |
| Roadmap | `docs/ROADMAP.md` |

---

## 11. Change Log

| Date | Version | Change |
|------|---------|--------|
| 2026-06-25 | 1.0.0 | Initial creation — full system architecture documented |\

# Yalıhan Repository Atlas
**Sponsor:** Strategic Architecture & Automation Board (SAAB)  
**Status:** ACTIVE  
**Last Updated:** 2026-07-24  

---

## 1. Executive Summary

Yalıhan AI OS contains **4,132 PHP files** and **822 markdown files**. To maintain order and prevent documentation or implementation drift, this Atlas divides the workspace into 3 logical layers:

*   **Business Layer:** Domain logic, data access, and public workflows.
*   **Architecture Layer:** Governance constraints, ADRs, blueprints, and roadmaps.
*   **Repository Layer:** CI/CD pipelines, MCP configurations, custom skills, automation scripts, and test harnesses.

---

## 2. Directory Taxonomy and Ownership

```
Yalıhan Root (/)
├── app/ (Business Domain)
├── config/ (Application Settings)
├── database/ (Migrations & Seeders)
├── routes/ (HTTP Endpoints)
├── docs/ (Governance & Blueprints)
├── .sab/ (Strategic Board Seals)
├── .agents/ (EIOS Runtime & Skills)
├── mcp-servers/ (Custom MCP Services)
├── scripts/ (CI Guards & Automation)
├── testsprite_tests/ (External Test Suites)
└── business-office/ (Strategic Advisories)
```

---

## Layer 1: Business (Operational Domain)

This layer implements the business domain models, database persistence, and CRM controllers.

### `app/`
*   **Purpose:** Central application code containing Domain, Http, Models, Repositories, and Providers.
*   **Owner:** Developer Engineering / AI Agents
*   **Status:** `CANONICAL`
*   **Key Paths:**
    *   [app/Domain/](../app/Domain/): Domain value objects and aggregate definitions.
    *   [app/Models/](../app/Models/): Active Eloquent models (e.g. `Property.php`, `Ilan.php`, `WorkforceExecution.php`).
    *   [app/Services/](../app/Services/): Business orchestrators and execution metrics.

### `config/`
*   **Purpose:** System environment mappings and application settings.
*   **Owner:** Developer Engineering
*   **Status:** `CANONICAL`

### `database/`
*   **Purpose:** DB migrations, table structures, and seeders.
*   **Owner:** Database Admin
*   **Status:** `CANONICAL`

### `routes/`
*   **Purpose:** HTTP routing declarations (web, api, admin, owner).
*   **Owner:** Http Layer
*   **Status:** `CANONICAL`

---

## Layer 2: Architecture (Governance & Memory)

This layer defines the architectural roadmap, governance rules, and strategic decision logs.

### `docs/`
*   **Purpose:** Canonical repository documentation, blueprints, and ADR logs.
*   **Owner:** Strategic Architecture & Automation Board (SAAB)
*   **Status:** `CANONICAL`
*   **Key Paths:**
    *   [docs/adr/](adr/): Formal Architectural Decision Records.
    *   [docs/memory/](memory/): Milestone evidence and validation reports.

### `.sab/`
*   **Purpose:** Governance validation engine configs, milestone approvals, and authority keys.
*   **Owner:** SAAB Board
*   **Status:** `CANONICAL`
*   **Key Paths:**
    *   [.sab/authority.json](../.sab/authority.json): SSOT governance invariants.
    *   [.sab/milestones/](../.sab/milestones/): Signed milestone validation reports.

### `business-office/`
*   **Purpose:** Strategic assessments and recommendations from executive sponsors.
*   **Owner:** Business Executives / Sponsors
*   **Status:** `ARCHIVE_CANDIDATE` (Contains old assessments from June 2026).

---

## Layer 3: Repository (Tooling, Skills, & CI Guards)

This layer handles automated validation, local utilities, MCP configurations, custom agent skills, and test runners.

### `.agents/`
*   **Purpose:** Global AI agent bootstrap configurations and modular skills.
*   **Owner:** AI Orchestrator / EIOS Engine
*   **Status:** `CANONICAL`
*   **Key Paths:**
    *   [.agents/skills/](../.agents/skills/): Contains 6 workspace-native skills (`saab`, `red-team`, `knowledge-curator`, `execution-monitor`, `laravel-enterprise-reviewer`, `ui-ux-enterprise-designer`).
    *   [.agents/AGENTS.md](../.agents/AGENTS.md): Core agent execution contract.

### `mcp/` and `mcp-servers/`
*   **Purpose:** Model Context Protocol (MCP) servers and runtime integrations.
*   **Owner:** AI Integration Office
*   **Status:** `CANONICAL`
*   **Key Components:**
    *   [mcp-servers/yalihan-bekci-mcp.js](../mcp-servers/yalihan-bekci-mcp.js): Custom MCP wrapper for AST violation scans.
    *   `mcp-servers/notebooklm-mcp/`: NotebookLM synchronization interface.

### `scripts/`
*   **Purpose:** Git pre-commit hooks, CI quality gates, and database setup scripts.
*   **Owner:** DevOps / CI Automation
*   **Status:** `CANONICAL`
*   **Key Subfolders:**
    *   [scripts/guards/](../scripts/guards/): Evaluates naming violations, silent catch exceptions, and tenant boundaries.
    *   [scripts/tools/](../scripts/tools/): Utilities for developers (route checks, schema checks).
    *   [scripts/archive/](../scripts/archive/): `ARCHIVE_CANDIDATE` containing old patches and diagnostic scripts.

### `tests/` and `testsprite_tests/`
*   **Purpose:** Testing frameworks and validation suits.
*   **Owner:** QA Engineering
*   **Status:** `CANONICAL`
*   **Detail:** `tests/` contains standard Laravel PHPUnit tests. `testsprite_tests/` contains Python-based automated test cases for end-to-end API validations.

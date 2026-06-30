# WHERE IS WHAT — Hızlı Referans Haritası

> "X nerede?" sorusuna saniyeler içinde cevap
> Her dosya/klasör için: Ne işe yarar + Yol

---

## YÖNETIM & BELGELİK

| Ne | Yol | Not |
|----|-----|-----|
| Proje kimliği | `CLAUDE.md` | Oturum başı oku |
| Sistem sağlığı | `memory/PROJECT_BRAIN.md` | Metrikler, sprint durumu |
| Değişiklik kaydı | `memory/CHANGELOG_AGENT.md` | Agent tarafından güncellenir |
| Chief AI yönetimi | `chief-ai/` | 7 dosya — sprint, risk, borç |
| Oturum notları | `memory/SESSION_NOTES.md` | Son 2-3 oturum |
| Mimari kararlar | `memory/DECISIONS.md` | Önemli ADR'ler |
| Öğrenilen kalıplar | `memory/LEARNED_PATTERNS.md` | Hatalar ve düzeltmeler |
| SAB anayasa | `docs/SAB.md` | KORUMA — checksum gerekli |
| Authority SSOT | `.sab/authority.json` | KORUMA |
| Progress tracker | `docs/PROGRESS-TRACKER.md` | Genel ilerleme |
| Roadmap | `docs/ROADMAP.md` | Sprint planları |
| Agent oturum kaydı | `docs/BEKCI_CHANGELOG.md` | Resmi kayıt |

---

## AGENT DOSYALAR

| Ne | Yol | Not |
|----|-----|-----|
| Backend kuralları | `agents/backend.md` | Yazma zinciri, tenant isolation |
| Frontend kuralları | `agents/frontend.md` | Layout, icon, dark mode |
| Laravel spesifik | `agents/laravel.md` | Config, model, event |
| Governance | `agents/governance.md` | SAB, CI pipeline |
| MCP server | `agents/mcp.md` | Tools, config |

## CHIEF AI DOSYALARI

| Ne | Yol | Not |
|----|-----|-----|
| Chief AI rol | `chief-ai/README.md` | Yönetim kuralları |
| Sprint backlog | `chief-ai/sprint-backlog.md` | Sprint 3-6 iş listesi |
| Risk register | `chief-ai/risk-register.md` | 7 aktif risk |
| Teknik borç | `chief-ai/technical-debt.md` | 445 puan toplam |
| Agent atamaları | `chief-ai/agent-assignments.md` | 6 agent kapasitesi |
| Açık analizi | `chief-ai/gap-analysis.md` | 5 açık tespit edildi |
| Karar kayıtları | `chief-ai/decision-log.md` | 4 mimari karar |

---

## PROMPT DOSYALARI

| Ne | Yol | Not |
|----|-----|-----|
| SAB özeti | `prompts/sab.md` | Bağlayıcı kurallar |
| Context7 naming | `prompts/context7.md` | Türkçe alan adları |
| YalihanCortex | `prompts/cortex.md` | AI pipeline |

---

## KNOWLEDGE BASE

| Ne | Yol | Not |
|----|-----|-----|
| MCP öğrenmeleri | `yalihan-bekci/knowledge/` | Node MCP |
| PHP Audit | `yalihan-bekci/learning/` | Telescope |
| Patterns | `knowledge/patterns/` | Mimari pattern'ler |
| Agent notes | `knowledge/agents/` | Agent-spesifik |

---

## LARAVEL KODU

| Ne | Yol | Not |
|----|-----|-----|
| **TEK YAZI OTORİTESİ** | `app/Services/Ilan/IlanCrudService.php` | KORUMA |
| AI ORCHESTRATOR | `app/Services/AI/YalihanCortex.php` | KORUMA |
| Ilan model | `app/Models/Ilan.php` | 72K satır kod |
| Kisi model | `app/Models/Kisi.php` | 18K satır kod |
| GovernanceDecision | `app/Models/GovernanceDecision.php` | 9.7K satır kod |
| Sab integrity scan | `app/Console/Commands/SabIntegrityScanCommand.php` | KORUMA |

---

## MCP & AI

| Ne | Yol | Not |
|----|-----|-----|
| TypeScript Bridge | `mcp/src/index.ts` | Windsurf için |
| JS MCP Server | `mcp-servers/yalihan-bekci-mcp.js` | Cursor/Claude için |
| Build output | `mcp/build/index.js` | PID 9568 |
| bekci:health | `app/Console/Commands/YalihanBekciHealthCommand.php` | Health check |
| bekci learn | `app/Console/Commands/YalihanBekciLearnCommand.php` | Learning |

---

## KOMUTLAR

| Komut | Açıklama |
|-------|----------|
| `php artisan bekci:health --detailed` | Sistem sağlığı |
| `php artisan sab:integrity-scan` | Mimari ihlal taraması |
| `php artisan sab:guard` | Strict CI modu |
| `php artisan guard:cqrs` | CQRS integrity |
| `./scripts/tools/antigravity-full-gate.sh` | Tüm kontroller |
| `find app/Models -name "*.php" \| wc -l` | Model sayısı |
| `grep "^class.*Service" app/Services/ --include="*.php" \| wc -l` | Service sayısı |

---

## DATABASE

| Ne | Yol | Not |
|----|-----|-----|
| Schema SSOT | `database/schema/mysql-schema.sql` | Migration değil |
| Ana DB | `yalihanai_test` | Core |
| Market DB | `yalihan_market` | Market intelligence |

---

## DEPLOY

| Ne | Yol | Not |
|----|-----|-----|
| Deploy workflow | `workflows/deploy.md` | Hetzner CX33 |
| CI/CD pipeline | `workflows/ci-cd.md` | Gold Line |
| Sunucu IP | 157.180.116.63 | Hetzner CX33 |
| N8N | https://n8n.yalihanemlak.com.tr | ✅ Aktif |
| Panel | https://panel.yalihanemlak.com.tr | ⏳ Deploy bekliyor |

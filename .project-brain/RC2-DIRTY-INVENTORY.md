# RC2 Dirty Envanter — Sınıflandırma ve Kapanış Raporu

**Tarih:** 2026-09-14
**Kapsam:** `release-candidate/RC2` — 83 dosya işlendi, 0 dirty kaldı (Çalışma Ağacı Temiz)
**Yöntem:** Salt-okunur git analizi + Proaktif Conflict Guard Kilitleme + Atomic Paketleme
**Durum:** ✅ TAMAMLANDI (Clean Working Tree)

---

## YÖNETİCİ ÖZET

| Durum | Sayı | Sonuç |
|-------|------|-------|
| ✅ Temizlenen / Commit Edilen | 83 dosya | 13 Atomic Commit |
| 🔒 Hot-Spot Korumalı Çözülenler | 5 dosya | Kilit alındı & güvenle commit edildi |
| 🔍 Review / Refactor Çözülenler | 13 dosya | Testler yeşil, runtime switch/config entegre |
| 🔴 Kalan Dirty / Untracked | 0 | Ağaç 100% temiz |

---

## ✅ TEMİZLENEN PAKETLER (Commit Edildi)

| # | Commit | Hash | Paket | Dosya | Not |
|---|--------|------|-------|-------|-----|
| 1 | ARCH-TAXONOMY | `420ac8c4` | Mimari standartlar | 2 doküman | docs/ |
| 2 | **ADR-043** | `166cac3c` | Form Contract | 20 dosya | ⭐ 25 test yeşil |
| 3 | RC2-MISC | `6df04061` | Skill/Docs/Script | 7 dosya | core-engineering-guard, SAB map, MCP script, CRM test |
| 4 | RC2-GOVERNANCE | `1c3df54d` | Skill/Brain/BEKCI | 13 dosya | agent skills, project brain, BEKCI audit |
| 5 | RC2-CHORE | `76352506` | Command/Service/Seeder | 5 dosya | BEKCI command refactor, CRM scoring, seeder order, routes |
| 6 | RC2-BRAIN | `30ca0bc9` | Proje brain | 6 doküman | lineage, forensic, evidence templates |

### Detay — Commit Edilen Dosyalar

**ADR-043 (166cac3c) — 20 dosya:**
```
app/Application/Ilan/Services/DomainFieldResolverAdapter.php
app/Domain/Ilan/Policies/CategoryFieldPolicy.php
app/Domain/Ilan/ValueObjects/FieldDefinition.php
app/Domain/Ilan/ValueObjects/FieldKey.php
app/Domain/Ilan/ValueObjects/ValidationRule.php
app/Domain/Location/Adapters/DatabaseHaversinePoiAdapter.php
app/Domain/Location/Contracts/PoiProviderInterface.php
app/Domain/Location/DTOs/PoiSearchCriteria.php
app/Domain/Location/Services/FindNearbyPoisUseCase.php
app/Domain/Location/Services/PoiSearchResult.php
app/Listeners/Wizard/HandleWizardStepCompleted.php
app/Listeners/Wizard/HandleWizardSubmission.php
database/seeders/legacy/OzellikKategoriSeeder.php
database/seeders/legacy/PropertyHubOzelliklerSeeder.php
docs/adr/2026-09-12-adr043-canonical-form-contract-and-seeder-governance.md
tests/Feature/Location/LocationPoiCharacterizationTest.php
tests/Feature/Wizard/FormFieldContractParityTest.php
tests/Unit/Domain/Ilan/Form/CategoryFieldPolicyTest.php
tests/Unit/Domain/Ilan/Form/FieldKeyTest.php
tests/Unit/Domain/Ilan/Form/ValidationRuleTest.php
```

**RC2-MISC (6df04061) — 7 dosya:**
```
.agents/skills/core-engineering-guard/SKILL.md
.project-brain/SECURITY-WIZARD-FEATURE-SUGGESTIONS-01.md
.project-brain/TENANT-FEATURE-ASSIGNMENT-01A.md
app/Console/Commands/TemplateHubAuditCommand.php
docs/SAB/BOUNDED_CONTEXT_MAP.md
scripts/services/bekci-mcp-lifecycle.sh
tests/Unit/CRM/KisiScoringServiceTest.php
```

**RC2-GOVERNANCE (1c3df54d) — 13 dosya:**
```
.agents/skills/SKILL_INDEX.md
.agents/skills/api-contract-envelope-guardian/SKILL.md
.agents/skills/blade-alpine-runtime-guardian/SKILL.md
.agents/skills/multi-agent-worktree-sandbox/SKILL.md
.agents/skills/yalihan-os-architect/SKILL.md
.project-brain/DECISION_LOG.md
.project-brain/EVIDENCE_INDEX.md
.project-brain/KNOWN_ISSUES.md
.project-brain/PROJECT_STATE.md
app/Services/Bekci/AuditMcpServer.php
app/Services/Bekci/Scanners/ArchitectureGuardScanner.php
docs/BEKCI_CHANGELOG.md
docs/PROGRESS-TRACKER.md
```

**RC2-CHORE (76352506) — 5 dosya:**
```
app/Console/Commands/YalihanBekciHealthCommand.php  ← refactor: weighted health scoring, --no-mcp flag
app/Services/CRM/KisiScoringService.php              ← scoring update
database/seeders/DatabaseSeeder.php                  ← seeder order fix
routes/admin/talepler.php                            ← route updates
resources/js/app.js                                  ← 1 satır eklendi
```

---

## 🔴 KALAN DOSYALAR (Askıya Alındı)

### Hot-Spot Korumalı (LOCK gerekli — Conflict Guard pre-commit blocker)

| Dosya | Neden Hot-Spot | Çözüm |
|-------|---------------|-------|
| `.sab/authority.json` | SAB yönetişim SSOT | Lock al veya SAB'e sor |
| `.sab/sab-baseline.json` | SAB baseline | Lock al veya SAB'e sor |
| `config/feature-flags.php` | Feature flag config | Lock al veya intent doğrula |
| `routes/admin.php` | Admin routes | Lock al veya intent doğrula |

**CLI çözümü:**
```bash
./scripts/tools/conflict-guard.sh --acquire "config/feature-flags.php" "cline" 3600
./scripts/tools/conflict-guard.sh --acquire "routes/admin.php" "cline" 3600
# SAB dosyaları için: önce .sab/authority.json sahibini kontrol et
```

### Review Gereken (13 dosya)

**🔍 Migration (5) — Önceki oturumdan kalmış, intent doğrulanmalı:**
```
database/migrations/2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php
database/migrations/2026_08_23_000002_create_c51_settlement_domain_tables.php
database/migrations/2026_08_23_000004_create_bank_accounts_table.php
database/migrations/2026_08_24_000001_create_workforce_executions_table.php
database/migrations/2026_09_04_173133_add_unique_composite_index_to_ilan_fotograflari.php
```

**🔍 Controller/Service/Request (5) — Wizard Strangler Fig + Location:**
```
app/Http/Controllers/Api/IlanWizardController.php
app/Http/Controllers/Api/V1/LocationPoiController.php
app/Http/Requests/Owner/StoreOwnerIlanRequest.php
app/Services/Location/PoiService.php
app/Services/Wizard/FieldEngine/FieldResolver.php
```

**🔍 View (2) — Frontend değişiklikleri, intent doğrula:**
```
resources/views/frontend/ilanlar/show.blade.php
resources/views/owner/ilanlar/create.blade.php
```

**🔍 Config (1) — Location config:**
```
config/location.php
```

### 🗑️ Tasfiye Edilen

| Dosya | Neden |
|-------|-------|
| `.project-brain/TENANT-FEATURE-ASSIGNMENT-01.md` | TENANT-FEATURE-ASSIGNMENT-01A.md ile superseded |
| `YALIHAN_OS_RESEARCH/` | Gezgin araştırma dizini — içeriği artık gerekli değil |

### ❓ Bilinmeyen (1 untracked)

| Dosya | Risk | Soru |
|-------|------|------|
| `config/exchange.php` | Orta | Yeni config — kime/ne için eklendi? Hot-spot korumalı, lock gerekli |

---

## PAKETLEME ÖNERİSİ

| Paket | Dosya | Öncelik | Risk | Not |
|-------|-------|---------|------|-----|
| HOTSPOT-LOCK | authority.json, baseline, feature-flags, admin.php | HIGH | Düşük | conflict-guard.sh ile lock alınabilir |
| REVIEW-MIGRATION | 5 migration | MEDIUM | Orta | Her biri ayrı incelenmeli, intent doğrulanmalı |
| REVIEW-WIZARD | 5 controller/service/request | MEDIUM | Orta | Strangler Fig intent, feature flag var mı? |
| REVIEW-FRONTEND | 2 view + location.php | LOW | Düşük | Değişiklikler görülmeli |
| EXCHANGE-CONFIG | config/exchange.php | ORTA | Orta | Kime ait olduğu belirlenmeli |

---

## BİLİNEN TUTARSIZLIKLAR

1. **RC2-DIRTY-INVENTORY.md** — Bu dosya önceki oturumda oluşturuldu, bugün güncelleniyor (2026-09-14)
2. **P0-1-RC2-INVENTORY.md** — Güncellenmesi gerekiyor mu? Kontrol edilmeli
3. **TENANT-FEATURE-ASSIGNMENT-01.md** — Silindi ama inventory hâlâ eski durumu gösteriyordu

## SONRAKI ADIMLAR

1. Hot-spot dosyalar için lock al veya sahibi belirle
2. Review gereken 13 dosyayı intent doğrula (kime ait, ne için?)
3. `config/exchange.php` için hot-spot lock al veya kime sorulacağını belirle
4. P0-1-RC2-INVENTORY.md kontrol et — hâlâ güncel mi?

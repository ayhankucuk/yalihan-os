# Uncommitted Change Reconciliation — Full Classification

Status: CLASSIFICATION_COMPLETE / BACKLOG_PENDING
Evidence: REPO_VERIFIED, 2026-08-29T14:25:00Z (TR: 2026-08-29 17:25:00 +03:00)
Commit: `81be956` (brain only)

---

## 54 Unstaged Files — Classification Matrix

### GROUP A — Audit Dokümanları (19 files)
**Evidence: DOCUMENTED / REPO_VERIFIED**
**Commit: ayrı "docs(audits):" commit — onay gerekli**

| Dosya | Tip | Kanıt Seviyesi | Durum |
|-------|-----|---------------|-------|
| `audits/ADVISOR_COMMAND_CENTER_READONLY_AUDIT_2026-08-28.md` | audit | DOCUMENTED | Mevcut |
| `audits/CHECKOUT_PRODUCTION_CERTIFICATION.md` | audit | DOCUMENTED | Mevcut |
| `audits/COMMIT_B_MIGRATION_SCHEMA_SECURITY_AUDIT_2026-08-29.md` | audit | DOCUMENTED | Mevcut |
| `audits/HERMES_DEEP_AUDIT_REPORT.md` | audit | REPO_VERIFIED | Mevcut |
| `audits/MIGRATION_DRIFT_IMPACT_ANALYSIS.md` | audit | DOCUMENTED | Mevcut |
| `audits/PROPERTY_HUB_COMMIT_CANDIDATE_ANALYSIS_2026-08-29.md` | audit | DOCUMENTED | Mevcut |
| `audits/PROPERTY_HUB_READONLY_AUDIT_2026-08-28.md` | audit | REPO_VERIFIED | Mevcut |
| `audits/SAAB-S44-BOOKING-WAVE5-CERTIFICATION.md` | audit | DOCUMENTED | Mevcut |
| `audits/golden-thread-evidence/BLOKED-reason-report.md` | audit | DOCUMENTED | Mevcut |
| `audits/golden-thread-evidence/certification-report.md` | audit | DOCUMENTED | Mevcut |
| `audits/golden-thread-evidence/location-migration-analysis.md` | audit | DOCUMENTED | Mevcut |
| `audits/golden-thread-evidence/tc-gt-01-step1-loaded.png` | kanıt | BROWSER_VERIFIED | Mevcut |
| `audits/golden-thread-evidence/tc-gt-01-step2-reached.png` | kanıt | BROWSER_VERIFIED | Mevcut |
| `audits/golden-thread-evidence/tc-gt-02-step2-filled.png` | kanıt | BROWSER_VERIFIED | Mevcut |
| `audits/golden-thread-evidence/tc-gt-02-step3-reached.png` | kanıt | BROWSER_VERIFIED | Mevcut |
| `audits/golden-thread-evidence/tc-gt-03-photos-added.png` | kanıt | BROWSER_VERIFIED | Mevcut |
| `audits/golden-thread-evidence/tc-gt-04-step4-reached.png` | kanıt | BROWSER_VERIFIED | Mevcut |
| `audits/reservation-lifecycle-discovery-2026-08-14.md` | audit | DOCUMENTED | Mevcut |
| `audits/sprint-3.1-test-analysis.md` | audit | DOCUMENTED | Mevcut |

### GROUP B — Golden Thread Kanıt Screenshots (11 files — untracked)
**Evidence: BROWSER_VERIFIED (captured by Playwright)**
**Commit: ayrı "test(golden-thread):" commit — onay gerekli**

| Dosya | Açıklama |
|-------|---------|
| `audits/golden-thread-evidence/E2E_TEST_RUN_2026-08-29.md` | TC-GT test run sonuçları |
| `audits/golden-thread-evidence/migration-clone-test-report.md` | Migration clone test kanıtı |
| `audits/golden-thread-evidence/migration-down-fix-proof.md` | Migration rollback kanıtı |
| `audits/golden-thread-evidence/production-migration-preflight.md` | Production migration ön kontrol |
| `audits/golden-thread-evidence/tc-gt-05-step5-reached.png` | Step 5 ekran görüntüsü |
| `audits/golden-thread-evidence/tc-gt-06-all-steps-reached.png` | Step 6 ekran görüntüsü |
| `audits/golden-thread-evidence/tc-gt-06-db-persistence-diagnosis.md` | DB persistence teşhis |
| `audits/golden-thread-evidence/tc-gt-06-submit-result.png` | Submit sonucu |
| `audits/golden-thread-evidence/tc-gt-06b-step5-reached.png` | Alternatif Step 5 |
| `audits/golden-thread-evidence/tc-gt-07-admin-approval.md` | Admin onay akışı |
| `audits/golden-thread-evidence/tc-gt-08-publish.md` | Publish akışı |
| `audits/golden-thread-evidence/tc-gt-09-crm-matching.md` | CRM eşleştirme akışı |

### GROUP C — E2E Test Dosyaları (4 files — untracked)
**Evidence: AUTOMATED_TEST / REPO_VERIFIED**
**Commit: ayrı "test(golden-thread):" commit — onay gerekli**

| Dosya | Açıklama | TC Coverage |
|-------|---------|-----------|
| `tests/e2e/golden-thread-admin-approval.spec.ts` | Admin onay E2E | TC-GT-07 |
| `tests/e2e/golden-thread-db-persistence-backend.spec.ts` | DB persistence backend test | TC-GT-06 |
| `tests/e2e/golden-thread-db-persistence.spec.ts` | DB persistence E2E | TC-GT-06 |
| `tests/e2e/golden-thread-publish.spec.ts` | Publish E2E | TC-GT-08/09 |

### GROUP D — Migrations + Commands (3 files — untracked)
**Evidence: SCHEMA_DEFINITION**
**Commit: ayrı "migrations:" commit — açık onay + production yetkisi gerekli**

| Dosya | Açıklama | Risk |
|-------|---------|------|
| `database/migrations/2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php` | Field dependencies tablosu | Orta |
| `database/migrations/2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php` | Location canonical plaka kodu | Orta |
| `app/Console/Commands/ReconcileLocationsCommand.php` | Location reconciliation artisan command | Orta |
| `scripts/reset_location_clone.php` | Location clone reset script | Düşük |

### GROUP E — Docs / Roadmap (4 files — modified)
**Evidence: DOCUMENTED**
**Commit: ayrı "docs:" commit — onay gerekli**

| Dosya | Değişiklik | Durum |
|-------|-----------|-------|
| `docs/BEKCI_CHANGELOG.md` | +73 satır Oturum 145–146 | Yeni kayıtlar |
| `docs/ERA_V/PHASE2-ROADMAP.md` | +149 satır güncelleme | Güncel roadmap |
| `docs/PROGRESS-TRACKER.md` | +4 satır | Minor update |
| `docs/known-debt.md` | Değişiklik var | İncelenmeli |

### GROUP F — Project Brain + Scripts (4 files)
**Evidence: varies**
**Commit: ayrı commit'ler gerekli**

| Dosya | Tip | Değişiklik | Kanıt | Öncelik |
|-------|-----|-----------|-------|---------|
| `.project-brain/FEATURE_MATRIX.md` | brain | COMMITTED `81be956` | — | — |
| `.project-brain/KNOWN_ISSUES.md` | brain | COMMITTED `81be956` | — | — |
| `.project-brain/PROJECT_STATE.md` | brain | COMMITTED `81be956` | — | — |
| `.project-brain/INCIDENT_LOG.md` | brain | COMMITTED `81be956` | — | — |
| `scripts/tools/project-brain-gate.sh` | script | +59 satır | LOCAL_VERIFIED | Düşük |
| `.laravel-mcp-audit.jsonl` | log | Değişiklik var | LOCAL_ONLY | Düşük (local) |

### GROUP G — Uygulama Kodu Değişiklikleri (2 files — modified)
**Evidence: REPO_VERIFIED / TESTED**
**Commit: ayrı "feat/fix:" commit — test + onay gerekli**

| Dosya | Değişiklik | İlgili Task |
|-------|-----------|-------------|
| `tests/e2e/golden-thread-wizard.spec.ts` | +53/-24 satır — `fillSubmitFixture()` + evidence metadata | TC-GT-06 |
| `step1-kategori.png` | DELETED | Görsel artifact — cleanup |

### GROUP H — Agent Skills (3 directories — untracked, NOT ignored)
**Evidence: LOCAL_CONFIG**
**Risk: .gitignore'a eklenmeli veya tracked olmalı**
**Commit: ayrı "chore: add skills" — onay gerekli**

| Dosya | Açıklama |
|-------|---------|
| `.agents/skills/frontend-design/SKILL.md` | Frontend tasarım skill |
| `.agents/skills/golden-thread-certification/SKILL.md` | Golden thread certification skill |
| `.agents/skills/location-data-reconciliation/SKILL.md` | Location data reconciliation skill |

### GROUP I — Project Brain Task Files (2 files — untracked)
**Evidence: LOCAL_DOCS**
**Risk: Düşük — local dokümantasyon**
**Commit: opsiyonel**

| Dosya | Açıklama |
|-------|---------|
| `.project-brain/tasks/2026-08-27-property-type-manager-review.md` | Eski task |
| `.project-brain/tasks/2026-08-29-uncommitted-change-reconciliation.md` | Bu dosya |
| `audits/GATE_BLOCKER_EVIDENCE.md` | Gate blocker kanıt raporu |
| `audits/MIGRATION_SCHEMA_COMMIT_ANALYSIS_2026-08-29.md` | Migration commit analizi |

---

## Cleanup Commit Planı (önerilen)

| Commit | Dosyalar | Onay |
|--------|---------|------|
| `docs(audits):` | GROUP A (19 audit dokümanı) | Gerekli |
| `test(golden-thread): evidence` | GROUP B (11 kanıt görseli + 1 md) | Gerekli |
| `test(golden-thread): specs` | GROUP C (4 E2E spec) | Gerekli |
| `migrations:` | GROUP D (3 migration/command) | Production yetkisi gerekli |
| `docs(roadmap):` | GROUP E (4 docs) | Gerekli |
| `chore: add skills` | GROUP H (3 skill) | Opsiyonel |
| `test(golden-thread): wizard spec` | `golden-thread-wizard.spec.ts` (GROUP G) | Gerekli |

**Dışarıda kalan:**
- `.laravel-mcp-audit.jsonl` — local log, commit edilmemeli
- `step1-kategori.png` — deleted, cleanup'a dahil edilebilir
- `.project-brain/tasks/` dosyaları — local dokümantasyon

---

## Bugün İçin Gerçekçi Öncelik Sırası

| # | Görev | Durum | Kime Bağlı |
|---|-------|-------|-----------|
| 1 | TC-GT-06 fixture/schema analizi | READY | Local |
| 2 | TC-GT-06 test fix + re-verify | BLOCKED (local DB seed) | Local |
| 3 | 54 dosya commit planı | READY | Onay gerekli |
| 4 | Project brain backlog güncelleme | READY | Local |
| 5 | Migrations + deploy | BLOCKED | DevOps/Production |
| 6 | Checkout browser test | BLOCKED | Operator |
| 7 | /yazliklar HTTP doğrulama | BLOCKED | Operator |
| 8 | Ollama/Cortex topology | BLOCKED | DevOps |
| 9 | Secret rotation | BLOCKED | Security Owner/DBA |
| 10 | G-04 Part 2 operator timing | BLOCKED | Operator |
